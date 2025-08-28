<?php

namespace local_test1\form;
use moodleform;
require_once($CFG->libdir . '/formslib.php');

class logfilter extends moodleform
{
    public function definition()
    {
        global $DB;

        $mform = $this->_form;
        $customdata = $this->_customdata;
        $alltypes = $DB->get_records_menu('local_test1_mail_log', ['userid'=> $customdata['userid']]);

        if (count($alltypes) <= 1) {
            return;
        }

        $types[0] = get_string('choosedots');
        foreach ($alltypes as $type) {
            $types[$type] = $type;
        }

        $mform->addElement( 'select', 'type', get_string( 'selecttype', 'local_test1' ), $types, ['multiple' => false, 'onchange' => 'this.form.elements.formupdater.click()']);
        $mform->setType( 'type', PARAM_TEXT);

        $mform->addElement( 'date_selector', 'starttime', get_string( 'starttime', 'local_test1'), ['optional' => true], ['onchange' => 'this.form.elements.formupdater.click()']);
        $mform->addElement( 'date_selector', 'endtime', get_string( 'endtime', 'local_test1'), ['optional' => true], ['onchange' => 'this.form.elements.formupdater.click()']);

        $mform->registerNoSubmitButton('formupdater');
        $mform->addElement('submit', 'formupdater', 'type', ['class' => 'd-none']);
    }
}