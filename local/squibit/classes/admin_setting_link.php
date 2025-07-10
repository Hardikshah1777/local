<?php

namespace local_squibit;

require_once($CFG->libdir . '/adminlib.php');

class admin_setting_link extends \admin_setting_configtext {

    /**
     * @var string the link.
     */
    protected $link;

    /**
     * @var string the link name.
     */
    protected $linkname;

    /**
     * The admin_setting_link constructor.
     *
     * @param string $name
     * @param string $visiblename
     * @param string $description
     * @param string $linkname
     * @param mixed|string $link
     * @param int|null $defaultsetting
     * @param string $paramtype
     * @param null $size
     */
    public function __construct($name, $visiblename, $description, $linkname, $link, $defaultsetting,
                                $paramtype = PARAM_RAW, $size=null) {
        $this->link = $link;
        $this->linkname = $linkname;
        parent::__construct($name, $visiblename, $description, $defaultsetting, $paramtype, $size);
    }

    /**
     * Output the link to the upload image page.
     *
     * @param mixed $data
     * @param string $query
     * @return string
     */
    public function output_html($data, $query = '') {
        // Create a dummy variable for this field to avoid being redirected back to the upgrade settings page.
        $this->config_write($this->name, '');

        return format_admin_setting($this, $this->visiblename,
            \html_writer::link($this->link, $this->linkname), $this->description, true, '', null, $query);
    }
}
