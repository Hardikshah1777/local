<?php

namespace local_squibit\form;

use core_form\dynamic_form;
use context;
use context_system;
use moodle_url;
use core_user;

class authform extends dynamic_form{

    protected function get_context_for_dynamic_submission(): context {
        return context_system::instance();
    }

    protected function check_access_for_dynamic_submission(): void {
    }

    public function process_dynamic_submission() {
        $formdata = $this->get_data();
        return $this->check_user_password($formdata->userpassword);
    }

    public function set_data_for_dynamic_submission(): void {
    }

    protected function get_page_url_for_dynamic_submission(): moodle_url {
        return new moodle_url('/admin/settings.php?section=local_squibit');
    }

    protected function definition() {
        $mform = $this->_form;
        $mform->addElement('password', 'userpassword', get_string('enterpassword', 'local_squibit'), ['size' => 12]);
        $mform->setType('userpassword', core_user::get_property_type('password'));
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (empty($data['userpassword'])) {
            $errors['userpassword'] = get_string('pleaseenterpassword', 'local_squibit');
        } else if (!$this->check_user_password($data['userpassword'])) {
            $errors['userpassword'] = get_string('incorrectpassword', 'local_squibit');
        }

        return $errors;
    }

    public function check_user_password($password) {
        global $USER;
        $currentuser = core_user::get_user($USER->id);
        return validate_internal_user_password($currentuser, $password);
    }
}