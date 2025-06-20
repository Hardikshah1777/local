<?php

namespace local_squibit;

use html_writer;
use lang_string;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/adminlib.php');

class admin_setting extends \admin_setting_configtext {

    const TYPES = ['users', 'courses', 'reset'];

    protected $type;

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
    public function __construct($name, $visiblename, $type) {
        parent::__construct($name, $visiblename, null, null);
        $this->type = $type;
        $this->customcontrol = true;
    }

    /**
     * Output the link to the upload image page.
     *
     * @param mixed $data
     * @param string $query
     * @return string
     */
    public function output_html($data, $query = '') {
        global $OUTPUT, $PAGE;
        if ($this->type == self::TYPES[2]) {
            $this->customcontrol = false;
            $class = 'btn btn-primary';
            if (!utility::is_enabled()){
                $class .= ' disabled';
            }

            $btn = html_writer::div(
                    html_writer::tag('a',get_string('resetbtn', 'local_squibit'),['class'=> $class, 'id' => 'resetbtn']),
                    'form-item row');

            $PAGE->requires->js_call_amd('local_squibit/reset', 'init');
            return $btn;
        }

        $this->config_write($this->name, '');

        $setting = $this;
        $context = (object) [
            'name' => empty($setting->plugin) ? $setting->name : "$setting->plugin | $setting->name",
            'fullname' => $setting->get_full_name(),
        ];
        $context->id = 'admin-' . $setting->name;
        $context->title = highlightfast($query, $setting->visiblename);
        $context->name = highlightfast($query, $context->name);
        $context->forceltr = $setting->get_force_ltr();
        $context->customcontrol = $setting->has_custom_form_control();
        $context->buttondisabled = empty(utility::is_enabled());

        $context->viewlink = new moodle_url("/local/squibit/{$this->type}.php");
        $context->counts['total'] = $context->counts['pending'] = $context->counts['synced'] = 0;

        $context->strings['total'] = new lang_string("total{$this->type}count", 'local_squibit');
        $context->strings['pending'] = new lang_string("pending{$this->type}count", 'local_squibit');
        $context->strings['sync'] = new lang_string("sync{$this->type}count", 'local_squibit');
        $context->strings['viewlink'] = new lang_string("view{$this->type}link", 'local_squibit');
        if ($this->type == self::TYPES[1]) {
            $context->counts['total'] = utility::get_totalcourses();
            $context->counts['pending'] = utility::get_pendingcourses();
            $context->counts['sync'] = utility::get_syncedcourses();
        } else {
            $context->counts['total'] = utility::get_totalusers();
            $context->counts['pending'] = utility::get_pendingusers();
            $context->counts['sync'] = utility::get_syncedusers();
        }

        return $OUTPUT->render_from_template('local_squibit/setting', $context);
    }
}
