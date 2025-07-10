<?php

require_once $CFG->libdir . '/adminlib.php';

class local_authtimer_settings_course_select extends admin_setting_configmultiselect {

    public function __construct($name, $visiblename, $description, $defaultsetting) {
        parent::__construct($name, $visiblename, $description, $defaultsetting, null);
    }

    public function load_choices() {
        global $CFG;
        require_once($CFG->dirroot.'/course/lib.php');
        if (is_array($this->choices) && !empty($this->choices)) {
            return true;
        }
        foreach (get_courses('all', 'c.fullname ASC', 'c.id,c.fullname,c.visible') as $course) {
            if ($course->id == SITEID) {
                continue;
            }
            $this->choices[$course->id] = $course->fullname;
        }
        return true;
    }

    public function output_html($data, $query = '') {
        global $PAGE;
        $PAGE->requires->js_call_amd('core/form-autocomplete', 'enhance', ['#'.$this->get_id(),]);
        return parent::output_html($data, $query);
    }

}
