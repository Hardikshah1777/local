<?php

namespace mod_evaluation;
use moodleform;
require_once($CFG->libdir . '/formslib.php');

class section_form extends moodleform{

    /**
     * @inheritDoc
     */
    protected function definition() {

        $mform = $this->_form;

        $mform->addElement('header','section',get_string('addsection','mod_evaluation'));

        $mform->addElement('text','sectionname',get_string('section:name','mod_evaluation'),array('size' => '32'));
        $mform->setType('sectionname',PARAM_TEXT);
        $mform->addRule('sectionname',null,'required',null,'client');

        $choice = ['0' => get_string('visible'),'1'=>get_string('hide')];
        $mform->addElement('select','visiblestatus',get_string('section:visiblestatus','mod_evaluation'),$choice);
        $mform->setType('visiblestatus',PARAM_TEXT);

        $mform->addElement('advcheckbox', 'saferskill', get_string('saferskill', 'mod_evaluation'), '' , null , array(0, 1));
        $this->add_action_buttons(true,get_string('savechanges'));
    }
}