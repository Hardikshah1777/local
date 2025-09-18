<?php

namespace report_kln\form;

use context;
use context_system;
use core_form\dynamic_form;
use moodle_url;
use report_kln\util;

require_once($CFG->libdir . '/formslib.php');

class userfilter extends dynamic_form {

    protected function get_context_for_dynamic_submission(): context {
        return context_system::instance();
    }

    protected function check_access_for_dynamic_submission(): void {
    }

    public function process_dynamic_submission() {
        global $PAGE;
        $formdata = $this->get_data();

        $PAGE->start_collecting_javascript_requirements();
        $html = $this->render();
        $js = $PAGE->requires->get_end_code();
        if ($formdata->endtime) {
            $formdata->endtime = strtotime(date("Y-m-d", $formdata->endtime) . ' 23:59:59');
        }
        return [
            'formdata' => $formdata,
            'html' => $html,
            'js' => $js
        ];
    }

    public function set_data_for_dynamic_submission(): void {
    }

    protected function get_page_url_for_dynamic_submission(): moodle_url {
        return new moodle_url('/report/kln/index.php');
    }

    protected function definition() {
        $mform = $this->_form;

        $useroptions = [0 => get_string('choose')] + util::get_users();
        $mform->addElement('autocomplete', 'userid', get_string('userfilter:userid', util::COMPONENT), $useroptions);

        $courseoptions = [0 => get_string('choose')] + util::get_courses();
        $mform->addElement('autocomplete', 'courseid', get_string('coursefilter:course', util::COMPONENT), $courseoptions);

        $this->render_common_filters($mform);
    }

    public function render_common_filters($mform) {
        $options = [
            'startyear' => 2000,
            'stopyear'  => date('Y'),
            'optional' => true
        ];
        $mform->addElement('date_selector', 'starttime', get_string('userfilter:starttime', util::COMPONENT), $options);
        $mform->addElement('date_selector', 'endtime', get_string('userfilter:endtime', util::COMPONENT), $options);

        $mform->addElement('submit', 'submit', get_string('userfilter:filter', util::COMPONENT));

    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (!empty($data['starttime']) && !empty($data['endtime']) && $data['starttime'] > $data['endtime']) {
            $errors['endtime'] = get_string('userfilter:invaliddate', util::COMPONENT);
        }

        return $errors;
    }
}