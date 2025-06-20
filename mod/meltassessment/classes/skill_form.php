<?php

namespace mod_meltassessment;

use moodleform;
require_once($CFG->libdir . '/formslib.php');

class skill_form extends moodleform {
    protected function definition() {
        global $DB;
        $mform = $this->_form;

        $mform->addElement('header','skill',get_string('addskill','mod_meltassessment'));

        $mform->addElement('text','skillname',get_string('skill:name','mod_meltassessment'),array('size' => '32'));
        $mform->setType('skillname',PARAM_TEXT);
        $mform->addRule('skillname',null,'required',null,'client');

        $choice = ['0'=>get_string('visible'),'1'=>get_string('hide')];
        $mform->addElement('select','visiblestatus',get_string('section:visiblestatus','mod_meltassessment'),$choice);
        $mform->setType('visiblestatus',PARAM_INT);

        $choicenew = ['0'=>get_string('optional'),'1'=>get_string('required')];
        $mform->addElement('select','validaitonstatus',get_string('section:validationstatus','mod_meltassessment'),$choicenew);
        $mform->setType('validaitonstatus',PARAM_INT);

        $this->add_action_buttons(true,get_string('savechanges'));
    }
}