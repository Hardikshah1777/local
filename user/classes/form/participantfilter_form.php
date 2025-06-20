<?php


namespace core_user\form;

global $CFG;

use moodleform;

require_once($CFG->libdir . '/formslib.php');

class participantfilter_form extends moodleform
{
    public function definition()
    {
        $mform = $this->_form;

        $mform->addElement('text', 'firstname', get_string('firstname'));
        $mform->setType('firstname', PARAM_TEXT);

        $mform->addElement('text', 'lastname', get_string('lastname'));
        $mform->setType('lastname', PARAM_TEXT);

        $this->add_action_buttons(false, get_string('search'));
    }
}
