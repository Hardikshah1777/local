<?php

namespace local_squibit;

require_once($CFG->libdir . '/filelib.php');

use coding_exception;
use stored_file;

class curl {
    /** @var bool Caches http request contents */
    public  $cache    = false;
    /** @var bool Uses proxy, null means automatic based on URL */
    public  $proxy    = null;
    /** @var string library version */
    public  $version  = '0.4 dev';
    /** @var array http's response */
    public  $response = array();
    /** @var array Raw response headers, needed for BC in download_file_content(). */
    public $rawresponse = array();
    /** @var array http header */
    public  $header   = array();
    /** @var string cURL information */
    public  $info;
    /** @var string error */
    public  $error;
    /** @var int error code */
    public  $errno;
    /** @var bool use workaround for open_basedir restrictions, to be changed from unit tests only! */
    public $emulateredirects = null;

    /** @var array cURL options */
    private $options;

    /** @var string Proxy host */
    private $proxy_host = '';
    /** @var string Proxy auth */
    private $proxy_auth = '';
    /** @var string Proxy type */
    private $proxy_type = '';
    /** @var bool Debug mode on */
    private $debug    = false;
    /** @var bool|string Path to cookie file */
    private $cookie   = false;
    /** @var bool tracks multiple headers in response - redirect detection */
    private $responsefinished = false;
    /** @var security helper class, responsible for checking host/ports against allowed/blocked entries.*/
    private $securityhelper;
    /** @var bool ignoresecurity a flag which can be supplied to the constructor, allowing security to be bypassed. */
    private $ignoresecurity;
    /** @var array $mockresponses For unit testing only - return the head of this list instead of making the next request. */
    private static $mockresponses = [];

    /**
     * Curl constructor.
     *
     * Allowed settings are:
     *  proxy: (bool) use proxy server, null means autodetect non-local from url
     *  debug: (bool) use debug output
     *  cookie: (string) path to cookie file, false if none
     *  cache: (bool) use cache
     *  module_cache: (string) type of cache
     *  securityhelper: (\core\files\curl_security_helper_base) helper object providing URL checking for requests.
     *  ignoresecurity: (bool) set true to override and ignore the security helper when making requests.
     *
     * @param array $settings
     */
    public function __construct($settings = array()) {
        global $CFG;
        if (!function_exists('curl_init')) {
            $this->error = 'cURL module must be enabled!';
            trigger_error($this->error, E_USER_ERROR);
            return false;
        }

        // All settings of this class should be init here.
        $this->resetopt();
        if (!empty($settings['debug'])) {
            $this->debug = true;
        }
        if (!empty($settings['cookie'])) {
            if($settings['cookie'] === true) {
                $this->cookie = $CFG->dataroot.'/curl_cookie.txt';
            } else {
                $this->cookie = $settings['cookie'];
            }
        }
        if (!empty($settings['cache'])) {
            if (class_exists('curl_cache')) {
                if (!empty($settings['module_cache'])) {
                    $this->cache = new curl_cache($settings['module_cache']);
                } else {
                    $this->cache = new curl_cache('misc');
                }
            }
        }
        if (!empty($CFG->proxyhost)) {
            if (empty($CFG->proxyport)) {
                $this->proxy_host = $CFG->proxyhost;
            } else {
                $this->proxy_host = $CFG->proxyhost.':'.$CFG->proxyport;
            }
            if (!empty($CFG->proxyuser) and !empty($CFG->proxypassword)) {
                $this->proxy_auth = $CFG->proxyuser.':'.$CFG->proxypassword;
                $this->setopt(array(
                            'proxyauth'=> CURLAUTH_BASIC | CURLAUTH_NTLM,
                            'proxyuserpwd'=>$this->proxy_auth));
            }
            if (!empty($CFG->proxytype)) {
                if ($CFG->proxytype == 'SOCKS5') {
                    $this->proxy_type = CURLPROXY_SOCKS5;
                } else {
                    $this->proxy_type = CURLPROXY_HTTP;
                    $this->setopt(array('httpproxytunnel'=>false));
                }
                $this->setopt(array('proxytype'=>$this->proxy_type));
            }

            if (isset($settings['proxy'])) {
                $this->proxy = $settings['proxy'];
            }
        } else {
            $this->proxy = false;
        }

        if (!isset($this->emulateredirects)) {
            $this->emulateredirects = ini_get('open_basedir');
        }

        // Curl security setup. Allow injection of a security helper, but if not found, default to the core helper.
        if (isset($settings['securityhelper']) && $settings['securityhelper'] instanceof \core\files\curl_security_helper_base) {
            $this->set_security($settings['securityhelper']);
        } else {
            $this->set_security(new \core\files\curl_security_helper());
        }
        $this->ignoresecurity = isset($settings['ignoresecurity']) ? $settings['ignoresecurity'] : false;
    }

    /**
     * Resets the CURL options that have already been set
     */
    public function resetopt() {
        $this->options = array();
        $this->options['CURLOPT_USERAGENT']         = \core_useragent::get_moodlebot_useragent();
        // True to include the header in the output
        $this->options['CURLOPT_HEADER']            = 0;
        // True to Exclude the body from the output
        $this->options['CURLOPT_NOBODY']            = 0;
        // Redirect ny default.
        $this->options['CURLOPT_FOLLOWLOCATION']    = 1;
        $this->options['CURLOPT_MAXREDIRS']         = 10;
        $this->options['CURLOPT_ENCODING']          = '';
        // TRUE to return the transfer as a string of the return
        // value of curl_exec() instead of outputting it out directly.
        $this->options['CURLOPT_RETURNTRANSFER']    = 1;
        $this->options['CURLOPT_SSL_VERIFYPEER']    = 0;
        $this->options['CURLOPT_SSL_VERIFYHOST']    = 2;
        $this->options['CURLOPT_CONNECTTIMEOUT']    = 30;

        if ($cacert = self::get_cacert()) {
            $this->options['CURLOPT_CAINFO'] = $cacert;
        }
    }

    /**
     * Get the location of ca certificates.
     * @return string absolute file path or empty if default used
     */
    public static function get_cacert() {
        global $CFG;

        // Bundle in dataroot always wins.
        if (is_readable("$CFG->dataroot/moodleorgca.crt")) {
            return realpath("$CFG->dataroot/moodleorgca.crt");
        }

        // Next comes the default from php.ini
        $cacert = ini_get('curl.cainfo');
        if (!empty($cacert) and is_readable($cacert)) {
            return realpath($cacert);
        }

        // Windows PHP does not have any certs, we need to use something.
        if ($CFG->ostype === 'WINDOWS') {
            if (is_readable("$CFG->libdir/cacert.pem")) {
                return realpath("$CFG->libdir/cacert.pem");
            }
        }

        // Use default, this should work fine on all properly configured *nix systems.
        return null;
    }

    /**
     * Reset Cookie
     */
    public function resetcookie() {
        if (!empty($this->cookie)) {
            if (is_file($this->cookie)) {
                $fp = fopen($this->cookie, 'w');
                if (!empty($fp)) {
                    fwrite($fp, '');
                    fclose($fp);
                }
            }
        }
    }

    /**
     * Set curl options.
     *
     * Do not use the curl constants to define the options, pass a string
     * corresponding to that constant. Ie. to set CURLOPT_MAXREDIRS, pass
     * array('CURLOPT_MAXREDIRS' => 10) or array('maxredirs' => 10) to this method.
     *
     * @param array $options If array is null, this function will reset the options to default value.
     * @return void
     * @throws coding_exception If an option uses constant value instead of option name.
     */
    public function setopt($options = array()) {
        if (is_array($options)) {
            foreach ($options as $name => $val) {
                if (!is_string($name)) {
                    throw new coding_exception('Curl options should be defined using strings, not constant values.');
                }
                if (stripos($name, 'CURLOPT_') === false) {
                    $name = strtoupper('CURLOPT_'.$name);
                } else {
                    $name = strtoupper($name);
                }
                $this->options[$name] = $val;
            }
        }
    }

    /**
     * Reset http method
     */
    public function cleanopt() {
        unset($this->options['CURLOPT_HTTPGET']);
        unset($this->options['CURLOPT_POST']);
        unset($this->options['CURLOPT_POSTFIELDS']);
        unset($this->options['CURLOPT_PUT']);
        unset($this->options['CURLOPT_INFILE']);
        unset($this->options['CURLOPT_INFILESIZE']);
        unset($this->options['CURLOPT_CUSTOMREQUEST']);
        unset($this->options['CURLOPT_FILE']);
    }

    /**
     * Resets the HTTP Request headers (to prepare for the new request)
     */
    public function resetHeader() {
        $this->header = array();
    }

    /**
     * Set HTTP Request Header
     *
     * @param array $header
     */
    public function setHeader($header) {
        if (is_array($header)) {
            foreach ($header as $v) {
                $this->setHeader($v);
            }
        } else {
            // Remove newlines, they are not allowed in headers.
            $newvalue = preg_replace('/[\r\n]/', '', $header);
            if (!in_array($newvalue, $this->header)) {
                $this->header[] = $newvalue;
            }
        }
    }

    /**
     * Get HTTP Response Headers
     * @return array of arrays
     */
    public function getResponse() {
        return $this->response;
    }

    /**
     * Get raw HTTP Response Headers
     * @return array of strings
     */
    public function get_raw_response() {
        return $this->rawresponse;
    }

    /**
     * private callback function
     * Formatting HTTP Response Header
     *
     * We only keep the last headers returned. For example during a redirect the
     * redirect headers will not appear in {@link self::getResponse()}, if you need
     * to use those headers, refer to {@link self::get_raw_response()}.
     *
     * @param resource $ch Apparently not used
     * @param string $header
     * @return int The strlen of the header
     */
    private function formatHeader($ch, $header) {
        $this->rawresponse[] = $header;

        if (trim($header, "\r\n") === '') {
            // This must be the last header.
            $this->responsefinished = true;
        }

        if (strlen($header) > 2) {
            if ($this->responsefinished) {
                // We still have headers after the supposedly last header, we must be
                // in a redirect so let's empty the response to keep the last headers.
                $this->responsefinished = false;
                $this->response = array();
            }
            $parts = explode(" ", rtrim($header, "\r\n"), 2);
            $key = rtrim($parts[0], ':');
            $value = isset($parts[1]) ? $parts[1] : null;
            if (!empty($this->response[$key])) {
                if (is_array($this->response[$key])) {
                    $this->response[$key][] = $value;
                } else {
                    $tmp = $this->response[$key];
                    $this->response[$key] = array();
                    $this->response[$key][] = $tmp;
                    $this->response[$key][] = $value;

                }
            } else {
                $this->response[$key] = $value;
            }
        }
        return strlen($header);
    }

    /**
     * Set options for individual curl instance
     *
     * @param resource $curl A curl handle
     * @param array $options
     * @return resource The curl handle
     */
    private function apply_opt($curl, $options) {
        // Clean up
        $this->cleanopt();
        // set cookie
        if (!empty($this->cookie) || !empty($options['cookie'])) {
            $this->setopt(array('cookiejar'=>$this->cookie,
                            'cookiefile'=>$this->cookie
                             ));
        }

        // Bypass proxy if required.
        if ($this->proxy === null) {
            if (!empty($this->options['CURLOPT_URL']) and is_proxybypass($this->options['CURLOPT_URL'])) {
                $proxy = false;
            } else {
                $proxy = true;
            }
        } else {
            $proxy = (bool)$this->proxy;
        }

        // Set proxy.
        if ($proxy) {
            $options['CURLOPT_PROXY'] = $this->proxy_host;
        } else {
            unset($this->options['CURLOPT_PROXY']);
        }

        $this->setopt($options);

        // Reset before set options.
        curl_setopt($curl, CURLOPT_HEADERFUNCTION, array(&$this,'formatHeader'));

        // Setting the User-Agent based on options provided.
        $useragent = '';

        if (!empty($options['CURLOPT_USERAGENT'])) {
            $useragent = $options['CURLOPT_USERAGENT'];
        } else if (!empty($this->options['CURLOPT_USERAGENT'])) {
            $useragent = $this->options['CURLOPT_USERAGENT'];
        } else {
            $useragent = \core_useragent::get_moodlebot_useragent();
        }

        // Set headers.
        if (empty($this->header)) {
            $this->setHeader(array(
                'User-Agent: ' . $useragent,
                'Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7',
                'Connection: keep-alive'
                ));
        } else if (!in_array('User-Agent: ' . $useragent, $this->header)) {
            // Remove old User-Agent if one existed.
            // We have to partial search since we don't know what the original User-Agent is.
            if ($match = preg_grep('/User-Agent.*/', $this->header)) {
                $key = array_keys($match)[0];
                unset($this->header[$key]);
            }
            $this->setHeader(array('User-Agent: ' . $useragent));
        }
        curl_setopt($curl, CURLOPT_HTTPHEADER, $this->header);

        if ($this->debug) {
            echo '<h1>Options</h1>';
            var_dump($this->options);
            echo '<h1>Header</h1>';
            var_dump($this->header);
        }

        // Do not allow infinite redirects.
        if (!isset($this->options['CURLOPT_MAXREDIRS'])) {
            $this->options['CURLOPT_MAXREDIRS'] = 0;
        } else if ($this->options['CURLOPT_MAXREDIRS'] > 100) {
            $this->options['CURLOPT_MAXREDIRS'] = 100;
        } else {
            $this->options['CURLOPT_MAXREDIRS'] = (int)$this->options['CURLOPT_MAXREDIRS'];
        }

        // Make sure we always know if redirects expected.
        if (!isset($this->options['CURLOPT_FOLLOWLOCATION'])) {
            $this->options['CURLOPT_FOLLOWLOCATION'] = 0;
        }

        // Limit the protocols to HTTP and HTTPS.
        if (defined('CURLOPT_PROTOCOLS')) {
            $this->options['CURLOPT_PROTOCOLS'] = (CURLPROTO_HTTP | CURLPROTO_HTTPS);
            $this->options['CURLOPT_REDIR_PROTOCOLS'] = (CURLPROTO_HTTP | CURLPROTO_HTTPS);
        }

        // Set options.
        foreach($this->options as $name => $val) {
            if ($name === 'CURLOPT_FOLLOWLOCATION' and $this->emulateredirects) {
                // The redirects are emulated elsewhere.
                curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 0);
                continue;
            }
            $name = constant($name);
            curl_setopt($curl, $name, $val);
        }

        return $curl;
    }

    /**
     * Download multiple files in parallel
     *
     * Calls {@link multi()} with specific download headers
     *
     * <code>
     * $c = new curl();
     * $file1 = fopen('a', 'wb');
     * $file2 = fopen('b', 'wb');
     * $c->download(array(
     *     array('url'=>'http://localhost/', 'file'=>$file1),
     *     array('url'=>'http://localhost/20/', 'file'=>$file2)
     * ));
     * fclose($file1);
     * fclose($file2);
     * </code>
     *
     * or
     *
     * <code>
     * $c = new curl();
     * $c->download(array(
     *              array('url'=>'http://localhost/', 'filepath'=>'/tmp/file1.tmp'),
     *              array('url'=>'http://localhost/20/', 'filepath'=>'/tmp/file2.tmp')
     *              ));
     * </code>
     *
     * @param array $requests An array of files to request {
     *                  url => url to download the file [required]
     *                  file => file handler, or
     *                  filepath => file path
     * }
     * If 'file' and 'filepath' parameters are both specified in one request, the
     * open file handle in the 'file' parameter will take precedence and 'filepath'
     * will be ignored.
     *
     * @param array $options An array of options to set
     * @return array An array of results
     */
    public function download($requests, $options = array()) {
        $options['RETURNTRANSFER'] = false;
        return $this->multi($requests, $options);
    }

    /**
     * Returns the current curl security helper.
     *
     * @return \core\files\curl_security_helper instance.
     */
    public function get_security() {
        return $this->securityhelper;
    }

    /**
     * Sets the curl security helper.
     *
     * @param \core\files\curl_security_helper $securityobject instance/subclass of the base curl_security_helper class.
     * @return bool true if the security helper could be set, false otherwise.
     */
    public function set_security($securityobject) {
        if ($securityobject instanceof \core\files\curl_security_helper) {
            $this->securityhelper = $securityobject;
            return true;
        }
        return false;
    }

    /**
     * Multi HTTP Requests
     * This function could run multi-requests in parallel.
     *
     * @param array $requests An array of files to request
     * @param array $options An array of options to set
     * @return array An array of results
     */
    protected function multi($requests, $options = array()) {
        $count   = count($requests);
        $handles = array();
        $results = array();
        $main    = curl_multi_init();
        for ($i = 0; $i < $count; $i++) {
            if (!empty($requests[$i]['filepath']) and empty($requests[$i]['file'])) {
                // open file
                $requests[$i]['file'] = fopen($requests[$i]['filepath'], 'w');
                $requests[$i]['auto-handle'] = true;
            }
            foreach($requests[$i] as $n=>$v) {
                $options[$n] = $v;
            }
            $handles[$i] = curl_init($requests[$i]['url']);
            $this->apply_opt($handles[$i], $options);
            curl_multi_add_handle($main, $handles[$i]);
        }
        $running = 0;
        do {
            curl_multi_exec($main, $running);
        } while($running > 0);
        for ($i = 0; $i < $count; $i++) {
            if (!empty($options['CURLOPT_RETURNTRANSFER'])) {
                $results[] = true;
            } else {
                $results[] = curl_multi_getcontent($handles[$i]);
            }
            curl_multi_remove_handle($main, $handles[$i]);
        }
        curl_multi_close($main);

        for ($i = 0; $i < $count; $i++) {
            if (!empty($requests[$i]['filepath']) and !empty($requests[$i]['auto-handle'])) {
                // close file handler if file is opened in this function
                fclose($requests[$i]['file']);
            }
        }
        return $results;
    }

    /**
     * Helper function to reset the request state vars.
     *
     * @return void.
     */
    protected function reset_request_state_vars() {
        $this->info             = array();
        $this->error            = '';
        $this->errno            = 0;
        $this->response         = array();
        $this->rawresponse      = array();
        $this->responsefinished = false;
    }

    /**
     * For use only in unit tests - we can pre-set the next curl response.
     * This is useful for unit testing APIs that call external systems.
     * @param string $response
     */
    public static function mock_response($response) {
        if ((defined('PHPUNIT_TEST') && PHPUNIT_TEST)) {
            array_push(self::$mockresponses, $response);
        } else {
            throw new coding_exception('mock_response function is only available for unit tests.');
        }
    }

    /**
     * check_securityhelper_blocklist.
     * Checks whether the given URL is blocked by checking both plugin's security helpers
     * and core curl security helper or any curl security helper that passed to curl class constructor.
     * If ignoresecurity is set to true, skip checking and consider the url is not blocked.
     * This augments all installed plugin's security helpers if there is any.
     *
     * @param string $url the url to check.
     * @return string - an error message if URL is blocked or null if URL is not blocked.
     */
    protected function check_securityhelper_blocklist(string $url): ?string {

        // If curl security is not enabled, do not proceed.
        if ($this->ignoresecurity) {
            return null;
        }

        // Augment all installed plugin's security helpers if there is any.
        // The plugin's function has to be defined as plugintype_pluginname_security_helper in pluginname/lib.php file.
        $plugintypes = get_plugins_with_function('curl_security_helper');

        // If any of the security helper's function returns true, treat as URL is blocked.
        foreach ($plugintypes as $plugins) {
            foreach ($plugins as $pluginfunction) {
                // Get curl security helper object from plugin lib.php.
                $pluginsecurityhelper = $pluginfunction();
                if ($pluginsecurityhelper instanceof \core\files\curl_security_helper_base) {
                    if ($pluginsecurityhelper->url_is_blocked($url)) {
                        $this->error = $pluginsecurityhelper->get_blocked_url_string();
                        return $this->error;
                    }
                }
            }
        }

        // Check if the URL is blocked in core curl_security_helper or
        // curl security helper that passed to curl class constructor.
        if ($this->securityhelper->url_is_blocked($url)) {
            $this->error = $this->securityhelper->get_blocked_url_string();
            return $this->error;
        }

        return null;
    }

    /**
     * Single HTTP Request
     *
     * @param string $url The URL to request
     * @param array $options
     * @return bool
     */
    protected function request($url, $options = array()) {
        // Reset here so that the data is valid when result returned from cache, or if we return due to a blocked URL hit.
        $this->reset_request_state_vars();

        if ((defined('PHPUNIT_TEST') && PHPUNIT_TEST)) {
            if ($mockresponse = array_pop(self::$mockresponses)) {
                $this->info = [ 'http_code' => 200 ];
                return $mockresponse;
            }
        }

        // This will only check the base url. In the case of redirects, the blocking check is also after the curl_exec.
        $urlisblocked = $this->check_securityhelper_blocklist($url);
        if (!is_null($urlisblocked)) {
            return $urlisblocked;
        }

        // Set the URL as a curl option.
        $this->setopt(array('CURLOPT_URL' => $url));

        // Create curl instance.
        $curl = curl_init();

        $this->apply_opt($curl, $options);
        if ($this->cache && $ret = $this->cache->get($this->options)) {
            return $ret;
        }

        $ret = curl_exec($curl);
        $this->info  = curl_getinfo($curl);
        $this->error = curl_error($curl);
        $this->errno = curl_errno($curl);
        // Note: $this->response and $this->rawresponse are filled by $hits->formatHeader callback.

        // In the case of redirects (which curl blindly follows), check the post-redirect URL against the list of blocked list too.
        if (intval($this->info['redirect_count']) > 0) {
            $urlisblocked = $this->check_securityhelper_blocklist($this->info['url']);
            if (!is_null($urlisblocked)) {
                $this->reset_request_state_vars();
                curl_close($curl);
                return $urlisblocked;
            }
        }

        if ($this->emulateredirects and $this->options['CURLOPT_FOLLOWLOCATION'] and $this->info['http_code'] != 200) {
            $redirects = 0;

            while($redirects <= $this->options['CURLOPT_MAXREDIRS']) {

                if ($this->info['http_code'] == 301) {
                    // Moved Permanently - repeat the same request on new URL.

                } else if ($this->info['http_code'] == 302) {
                    // Found - the standard redirect - repeat the same request on new URL.

                } else if ($this->info['http_code'] == 303) {
                    // 303 See Other - repeat only if GET, do not bother with POSTs.
                    if (empty($this->options['CURLOPT_HTTPGET'])) {
                        break;
                    }

                } else if ($this->info['http_code'] == 307) {
                    // Temporary Redirect - must repeat using the same request type.

                } else if ($this->info['http_code'] == 308) {
                    // Permanent Redirect - must repeat using the same request type.

                } else {
                    // Some other http code means do not retry!
                    break;
                }

                $redirects++;

                $redirecturl = null;
                if (isset($this->info['redirect_url'])) {
                    if (preg_match('|^https?://|i', $this->info['redirect_url'])) {
                        $redirecturl = $this->info['redirect_url'];
                    }
                }
                if (!$redirecturl) {
                    foreach ($this->response as $k => $v) {
                        if (strtolower($k) === 'location') {
                            $redirecturl = $v;
                            break;
                        }
                    }
                    if (preg_match('|^https?://|i', $redirecturl)) {
                        // Great, this is the correct location format!

                    } else if ($redirecturl) {
                        $current = curl_getinfo($curl, CURLINFO_EFFECTIVE_URL);
                        if (strpos($redirecturl, '/') === 0) {
                            // Relative to server root - just guess.
                            $pos = strpos('/', $current, 8);
                            if ($pos === false) {
                                $redirecturl = $current.$redirecturl;
                            } else {
                                $redirecturl = substr($current, 0, $pos).$redirecturl;
                            }
                        } else {
                            // Relative to current script.
                            $redirecturl = dirname($current).'/'.$redirecturl;
                        }
                    }
                }

                curl_setopt($curl, CURLOPT_URL, $redirecturl);
                $ret = curl_exec($curl);

                $this->info  = curl_getinfo($curl);
                $this->error = curl_error($curl);
                $this->errno = curl_errno($curl);

                $this->info['redirect_count'] = $redirects;

                if ($this->info['http_code'] === 200) {
                    // Finally this is what we wanted.
                    break;
                }
                if ($this->errno != CURLE_OK) {
                    // Something wrong is going on.
                    break;
                }
            }
            if ($redirects > $this->options['CURLOPT_MAXREDIRS']) {
                $this->errno = CURLE_TOO_MANY_REDIRECTS;
                $this->error = 'Maximum ('.$this->options['CURLOPT_MAXREDIRS'].') redirects followed';
            }
        }

        if ($this->cache) {
            $this->cache->set($this->options, $ret);
        }

        if ($this->debug) {
            echo '<h1>Return Data</h1>';
            var_dump($ret);
            echo '<h1>Info</h1>';
            var_dump($this->info);
            echo '<h1>Error</h1>';
            var_dump($this->error);
        }

        curl_close($curl);

        return $ret;
    }

    /**
     * HTTP HEAD method
     *
     * @see request()
     *
     * @param string $url
     * @param array $options
     * @return bool
     */
    public function head($url, $options = array()) {
        $options['CURLOPT_HTTPGET'] = 0;
        $options['CURLOPT_HEADER']  = 1;
        $options['CURLOPT_NOBODY']  = 1;
        return $this->request($url, $options);
    }

    /**
     * HTTP PATCH method
     *
     * @param string $url
     * @param array|string $params
     * @param array $options
     * @return bool
     */
    public function patch($url, $params = '', $options = array()) {
        $options['CURLOPT_CUSTOMREQUEST'] = 'PATCH';
        if (is_array($params)) {
            $this->_tmp_file_post_params = array();
            foreach ($params as $key => $value) {
                if ($value instanceof stored_file) {
                    $value->add_to_curl_request($this, $key);
                } else {
                    $this->_tmp_file_post_params[$key] = $value;
                }
            }
            $options['CURLOPT_POSTFIELDS'] = $this->_tmp_file_post_params;
            unset($this->_tmp_file_post_params);
        } else {
            // The variable $params is the raw post data.
            $options['CURLOPT_POSTFIELDS'] = $params;
        }
        return $this->request($url, $options);
    }

    protected $_tmp_file_post_params;

    /**
     * HTTP POST method
     *
     * @param string $url
     * @param array|string $params
     * @param array $options
     * @return bool
     */
    public function post($url, $params = '', $options = array()) {
        $options['CURLOPT_POST']       = 1;
        if (is_array($params)) {
            $this->_tmp_file_post_params = array();
            foreach ($params as $key => $value) {
                if ($value instanceof stored_file) {
                    $value->add_to_curl_request($this, $key);
                } else {
                    $this->_tmp_file_post_params[$key] = $value;
                }
            }
            $options['CURLOPT_POSTFIELDS'] = $this->_tmp_file_post_params;
            unset($this->_tmp_file_post_params);
        } else {
            // $params is the raw post data
            $options['CURLOPT_POSTFIELDS'] = $params;
        }
        return $this->request($url, $options);
    }

    /**
     * HTTP GET method
     *
     * @param string $url
     * @param array $params
     * @param array $options
     * @return bool
     */
    public function get($url, $params = array(), $options = array()) {
        $options['CURLOPT_HTTPGET'] = 1;

        if (!empty($params)) {
            $url .= (stripos($url, '?') !== false) ? '&' : '?';
            $url .= http_build_query($params, '', '&');
        }
        return $this->request($url, $options);
    }

    /**
     * Downloads one file and writes it to the specified file handler
     *
     * <code>
     * $c = new curl();
     * $file = fopen('savepath', 'w');
     * $result = $c->download_one('http://localhost/', null,
     *   array('file' => $file, 'timeout' => 5, 'followlocation' => true, 'maxredirs' => 3));
     * fclose($file);
     * $download_info = $c->get_info();
     * if ($result === true) {
     *   // file downloaded successfully
     * } else {
     *   $error_text = $result;
     *   $error_code = $c->get_errno();
     * }
     * </code>
     *
     * <code>
     * $c = new curl();
     * $result = $c->download_one('http://localhost/', null,
     *   array('filepath' => 'savepath', 'timeout' => 5, 'followlocation' => true, 'maxredirs' => 3));
     * // ... see above, no need to close handle and remove file if unsuccessful
     * </code>
     *
     * @param string $url
     * @param array|null $params key-value pairs to be added to $url as query string
     * @param array $options request options. Must include either 'file' or 'filepath'
     * @return bool|string true on success or error string on failure
     */
    public function download_one($url, $params, $options = array()) {
        $options['CURLOPT_HTTPGET'] = 1;
        if (!empty($params)) {
            $url .= (stripos($url, '?') !== false) ? '&' : '?';
            $url .= http_build_query($params, '', '&');
        }
        if (!empty($options['filepath']) && empty($options['file'])) {
            // open file
            if (!($options['file'] = fopen($options['filepath'], 'w'))) {
                $this->errno = 100;
                return get_string('cannotwritefile', 'error', $options['filepath']);
            }
            $filepath = $options['filepath'];
        }
        unset($options['filepath']);
        $result = $this->request($url, $options);
        if (isset($filepath)) {
            fclose($options['file']);
            if ($result !== true) {
                unlink($filepath);
            }
        }
        return $result;
    }

    /**
     * HTTP PUT method
     *
     * @param string $url
     * @param array $params
     * @param array $options
     * @return bool
     */
    public function put($url, $params = array(), $options = array()) {
        $file = '';
        $fp = false;
        if (isset($params['file'])) {
            $file = $params['file'];
            if (is_file($file)) {
                $fp   = fopen($file, 'r');
                $size = filesize($file);
                $options['CURLOPT_PUT']        = 1;
                $options['CURLOPT_INFILESIZE'] = $size;
                $options['CURLOPT_INFILE']     = $fp;
            } else {
                return null;
            }
            if (!isset($this->options['CURLOPT_USERPWD'])) {
                $this->setopt(array('CURLOPT_USERPWD' => 'anonymous: noreply@moodle.org'));
            }
        } else {
            $options['CURLOPT_CUSTOMREQUEST'] = 'PUT';
            $options['CURLOPT_POSTFIELDS'] = $params;
        }

        $ret = $this->request($url, $options);
        if ($fp !== false) {
            fclose($fp);
        }
        return $ret;
    }

    /**
     * HTTP DELETE method
     *
     * @param string $url
     * @param array $param
     * @param array $options
     * @return bool
     */
    public function delete($url, $param = array(), $options = array()) {
        $options['CURLOPT_CUSTOMREQUEST'] = 'DELETE';
        if (!isset($options['CURLOPT_USERPWD'])) {
            $options['CURLOPT_USERPWD'] = 'anonymous: noreply@moodle.org';
        }
        $ret = $this->request($url, $options);
        return $ret;
    }

    /**
     * HTTP TRACE method
     *
     * @param string $url
     * @param array $options
     * @return bool
     */
    public function trace($url, $options = array()) {
        $options['CURLOPT_CUSTOMREQUEST'] = 'TRACE';
        $ret = $this->request($url, $options);
        return $ret;
    }

    /**
     * HTTP OPTIONS method
     *
     * @param string $url
     * @param array $options
     * @return bool
     */
    public function options($url, $options = array()) {
        $options['CURLOPT_CUSTOMREQUEST'] = 'OPTIONS';
        $ret = $this->request($url, $options);
        return $ret;
    }

    /**
     * Get curl information
     *
     * @return string
     */
    public function get_info() {
        return $this->info;
    }

    /**
     * Get curl error code
     *
     * @return int
     */
    public function get_errno() {
        return $this->errno;
    }

    /**
     * When using a proxy, an additional HTTP response code may appear at
     * the start of the header. For example, when using https over a proxy
     * there may be 'HTTP/1.0 200 Connection Established'. Other codes are
     * also possible and some may come with their own headers.
     *
     * If using the return value containing all headers, this function can be
     * called to remove unwanted doubles.
     *
     * Note that it is not possible to distinguish this situation from valid
     * data unless you know the actual response part (below the headers)
     * will not be included in this string, or else will not 'look like' HTTP
     * headers. As a result it is not safe to call this function for general
     * data.
     *
     * @param string $input Input HTTP response
     * @return string HTTP response with additional headers stripped if any
     */
    public static function strip_double_headers($input) {
        // I have tried to make this regular expression as specific as possible
        // to avoid any case where it does weird stuff if you happen to put
        // HTTP/1.1 200 at the start of any line in your RSS file. This should
        // also make it faster because it can abandon regex processing as soon
        // as it hits something that doesn't look like an http header. The
        // header definition is taken from RFC 822, except I didn't support
        // folding which is never used in practice.
        $crlf = "\r\n";
        return preg_replace(
                // HTTP version and status code (ignore value of code).
                '~^HTTP/1\..*' . $crlf .
                // Header name: character between 33 and 126 decimal, except colon.
                // Colon. Header value: any character except \r and \n. CRLF.
                '(?:[\x21-\x39\x3b-\x7e]+:[^' . $crlf . ']+' . $crlf . ')*' .
                // Headers are terminated by another CRLF (blank line).
                $crlf .
                // Second HTTP status code, this time must be 200.
                '(HTTP/1.[01] 200 )~', '$1', $input);
    }
}