<?php

namespace local_test2\form;

use moodleform;
require_once($CFG->libdir . '/formslib.php');

class test2_form extends \moodleform
{
    public function definition()
    {
        $mform = $this->_form;
        $id = $this->_customdata;

        $mform->addElement('hidden','id',$id['id']);

        $mform->addElement( 'text', 'firstname', 'First name' );
        $mform->addRule( 'firstname', get_string( 'required' ), 'required', null, 'client' );
        $mform->addElement( 'text', 'lastname', 'Last name' );
        $mform->addRule( 'lastname', get_string( 'required' ), 'required', null, 'client' );
        $mform->addElement( 'text', 'email', 'Email' );
        $mform->addRule( 'email', get_string( 'required' ), 'required', null, 'client' );
        $mform->addElement( 'text', 'city', 'City' );
        $mform->addRule( 'city', get_string( 'required' ), 'required', null, 'client' );

        $this->add_action_buttons( get_string( 'cancle'), get_string( 'submit'));
    }

    public function validation($data, $files)
    {
        global $DB;
        if (empty(trim($data['firstname']))){
            $errors['firstname'] = get_string('required');
        }
        if (empty(trim($data['lastname']))){
            $errors['lastname'] = get_string('required');
        }
        if (empty(trim($data['email']))){
            $errors['email'] = get_string('required');
        }
        if (empty(trim($data['city']))){
            $errors['city'] = get_string('required');
        }
        if (!validate_email($data['email'])) {
            $errors['email'] = get_string('invalidemail');
        }
        if (!empty($data['email'])) {
            $email = $DB->get_record('local_test2', ['email' => $data['email']], 'email, id');
            if ($email && $data['id'] != $email->id) {
                $errors['email'] = get_string('emailalreadyexist', 'local_registration');
            }
        }
        return $errors;
    }
}