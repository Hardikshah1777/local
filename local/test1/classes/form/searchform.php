<?php

namespace local_test1\form;

use moodleform;

class searchform extends moodleform
{
    public function definition()
    {
        $mform = $this->_form;
        $mform->addElement( 'text', 'search', get_string( 'search', 'local_test1' ) );
        $mform->setType( 'search', PARAM_TEXT );
        $this->add_action_buttons( false, get_string( 'search', 'local_test1' ) );
    }
}