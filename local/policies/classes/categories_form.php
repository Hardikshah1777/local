<?php

namespace local_policies;

global $CFG;

use moodleform;

require_once($CFG->libdir . '/formslib.php');

class categories_form extends moodleform
{
    public function definition()
    {
        $mform = $this->_form;

        $mform->addElement('text', 'name', get_string('catname', 'local_policies'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('errcatname', 'local_policies'), 'required', null, 'client');

        $this->add_action_buttons(true, get_string('submit', 'local_policies'));
    }
}