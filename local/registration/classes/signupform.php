<?php
defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/formslib.php');

class users_form extends \moodleform
{
    public function definition()
    {
        $mform = $this->_form;

        $mform->addElement('text', 'firstname', get_string('firstname', 'local_registration'));
        $mform->addRule('firstname', get_string('firstnamerequired', 'local_registration'), 'required', null, 'client');
        $mform->setType('firstname', PARAM_TEXT);

        $mform->addElement('text', 'lastname', get_string('lastname', 'local_registration'));
        $mform->addRule('lastname', get_string('lastnamerequired', 'local_registration'), 'required', null, 'client');
        $mform->setType('lastname', PARAM_TEXT);

        $mform->addElement('text', 'email', get_string('email'));
        $mform->setType('email', core_user::get_property_type('email'));
        $mform->addRule('email', get_string('emailrequired', 'local_registration'), 'required', null, 'client');

        $mform->addElement('text', 'phone1', get_string('phone1', 'local_registration'), ['maxlength' => 20]);
        $mform->addRule('phone1', get_string('phone1required', 'local_registration'), 'required', null, 'client');
        $mform->setType('phone1', PARAM_RAW);

        $mform->addElement('text', 'couponcode', get_string('couponcode', 'local_registration'));
        $mform->addRule('couponcode', get_string('couponcoderequired', 'local_registration'), 'required', null, 'client');
        $mform->setType('couponcode', PARAM_ALPHANUM);

        $this->add_action_buttons(true, get_string('submit', 'local_registration'));

    }

    public function validation($data, $files)
    {
        $errors = parent::validation($data, $files);
        global $DB;

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = get_string('invalidemail', 'local_registration');
        }
        if ($data['email']) {
            $email = $DB->get_record('user', ['email' => $data['email']], 'email');
            if (!empty($email)) {
                $errors['email'] = get_string('emailalreadyexist', 'local_registration');
            }
        }
        $code = $DB->get_record('local_registration', ['couponcode' => $data['couponcode'], 'visible' => 1,], '*');

        if (!$code) {
            $errors['couponcode'] = get_string('invalidcouponcode', 'local_registration');
        }elseif ($code->type == 1) {
            $params['couponid'] = $code->id;
            $registercode = $DB->get_record_sql('SELECT DISTINCT id,couponid,userid FROM {local_registration_users} WHERE couponid = :couponid', $params);
            if(!empty($registercode->userid)){
                $errors['couponcode'] = get_string('couponalreadyused', 'local_registration');
            }
        }

        return $errors;
    }
}