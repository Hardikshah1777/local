<?php

defined('MOODLE_INTERNAL') || die();
require_once $CFG->libdir . '/formslib.php';

class form2 extends \moodleform {
    protected function definition() {
        $data = $this->_customdata['data'];
        $colsmap =  $this->_customdata['columns'];
        $mform = $this->_form;

        $mform->addElement('hidden', 'iid');
        $mform->setType('iid', PARAM_INT);

        $mform->addElement('hidden', 'previewrows');
        $mform->setType('previewrows', PARAM_INT);

        foreach ($colsmap as $index => $col) {

            $mform->addElement('hidden', 'colsmap[' . $index . ']', $col);
            $mform->setType('colsmap[' . $index . ']', PARAM_ALPHANUMEXT);  
        }

        $this->add_action_buttons(false, get_string('uploadcoupon', 'local_registration'));
        $this->set_data($data);
    }
}
