<?php

namespace local_test1\form;
use moodleform;

class logfilter extends moodleform
{
    public function definition()
    {
        global $DB;

        $mform = $this->_form;
        $userid = $this->_customdata;
        $alltypes = $DB->get_records_menu('local_test1_mail_log', ['userid'=> $userid['userid']]);
        if (count($alltypes) <= 1) {
            return;
        }
        $types[0] = get_string('choosedots');
        foreach ($alltypes as $type) {
            $types[$type] = $type;
        }

        $mform->addElement( 'select', 'type', get_string( 'search', 'local_test1' ), $types, ['multiple' => false, 'onchange' => 'this.form.elements.formupdater.click()']);
        $mform->setType( 'type', PARAM_TEXT);

        $mform->registerNoSubmitButton('formupdater');
        $mform->addElement('submit', 'formupdater', 'type', ['class' => 'd-none']);
    }
}