<?php

namespace mod_meltassessment;

use moodleform;
require_once($CFG->libdir . '/formslib.php');
class section_form extends moodleform {
    protected function definition() {

        $mform = $this->_form;

        $mform->addElement('header','section',get_string('addsection','mod_meltassessment'));

        $mform->addElement('text','sectionname',get_string('section:name','mod_meltassessment'),array('size' => '32'));
        $mform->setType('sectionname',PARAM_TEXT);
        $mform->addRule('sectionname',null,'required',null,'client');

        $choice = ['0' => get_string('visible'),'1'=>get_string('hide')];
        $mform->addElement('select','visiblestatus',get_string('section:visiblestatus','mod_meltassessment'),$choice);
        $mform->setType('visiblestatus',PARAM_TEXT);

        $this->add_action_buttons(true,get_string('savechanges'));
    }
}