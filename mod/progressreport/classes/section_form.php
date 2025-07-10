<?php

namespace mod_progressreport;

use moodleform;
require_once($CFG->libdir . '/formslib.php');
class section_form extends moodleform {
    protected function definition() {

        $mform = $this->_form;

        $mform->addElement('header','section',get_string('addsection','mod_progressreport'));

        $mform->addElement('text','sectionname',get_string('section:name','mod_progressreport'),array('size' => '32'));
        $mform->setType('sectionname',PARAM_TEXT);
        $mform->addRule('sectionname',null,'required',null,'client');

        $choice = ['0' => get_string('visible'),'1'=>get_string('hide')];
        $mform->addElement('select','visiblestatus',get_string('section:visiblestatus','mod_progressreport'),$choice);
        $mform->setType('visiblestatus',PARAM_TEXT);

        $this->add_action_buttons(true,get_string('savechanges'));
    }
}