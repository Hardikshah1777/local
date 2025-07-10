<?php

require ('../config.php');
require_once($CFG->libdir . '/formslib.php');

$context = context_system::instance();
$url = new moodle_url('/blocks/test.php');
$userid = optional_param('userid',40,PARAM_INT);
$user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);

$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_title('Manage user');
$PAGE->set_heading('Manage user');

class manageuser_form extends \moodleform {

    protected function definition()
    {
        global $CFG;
        $mform = & $this->_form;
        $userdata = & $this->_customdata;
        $mform->addElement('hidden', 'id', $userdata->id);
        $mform->addElement('text', 'username', get_string('username'), 'maxlength="100" size="12" autocapitalize="none"');
        $mform->setDefault('username', $userdata->username);
        $mform->setType('username', PARAM_RAW);
        $mform->addRule('username', get_string('missingusername'), 'required', null, 'client');

//        if (!empty($CFG->passwordpolicy)){
//            $mform->addElement('static', 'passwordpolicyinfo', '', print_password_policy());
//        }

        $mform->addElement('text', 'password', get_string('password'), [
            'maxlength' => 32,
            'size' => 12,
            'autocomplete' => 'new-password'
        ]);
        $mform->setDefault('password', $userdata->password);
        $mform->setType('password', core_user::get_property_type('password'));
        $mform->addRule('password', get_string('missingpassword'), 'required', null, 'client');

        $this->add_action_buttons(false, 'submit');
    }

    public function validation($data, $files) {
        global $DB;
        $errors = parent::validation($data, $files);
        $userdata = $DB->get_record('user', ['username' => $data['username']]);
        if ($data['id']  !== $userdata->id){
            if ($DB->record_exists('user', ['username' => $userdata->username])) {
                $errors['username'] = get_string('usernameexists');
            }
        }
        return $errors;
    }
}

$form = new manageuser_form($url->out(false),$user);
if ($formdata = $form->get_data()){
    if (!empty($formdata)) {
        $DB->set_field( 'user', 'username', $formdata->username, ['id' => $formdata->id]);
        $DB->set_field( 'user', 'password', md5( $formdata->username ), ['id' => $formdata->id]);
    }
}
echo $OUTPUT->header();
echo $form->display();
echo $OUTPUT->footer();