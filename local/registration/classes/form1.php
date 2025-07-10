<?php
defined('MOODLE_INTERNAL') || die();

require_once $CFG->libdir.'/formslib.php';

class form1  extends moodleform {
    function definition () {
        $mform = $this->_form;

        $mform->addElement('header', 'settingsheader', get_string('upload'));

        $link = html_writer::link(new moodle_url('/local/registration/example.csv'), 'example.csv');
        $mform->addElement('static', 'examplecsv', get_string('examplecsv', 'local_registration'), $link);
   
        $mform->addElement('filepicker', 'userfile', get_string('file','local_registration'));
        $mform->addRule('userfile', get_string('files','local_registration'), 'required',null,'client');

        $choices = csv_import_reader::get_delimiter_list();
        $mform->addElement('select', 'delimiter_name', get_string('csvdelimiter', 'local_registration'), $choices);
        if (array_key_exists('cfg', $choices)) {
            $mform->setDefault('delimiter_name', 'cfg');
        } else if (get_string('listsep', 'langconfig') == ';') {
            $mform->setDefault('delimiter_name', 'semicolon');
        } else {
            $mform->setDefault('delimiter_name', 'comma');
        }

        $choices = core_text::get_encodings();
        $mform->addElement('select', 'encoding', get_string('encoding', 'local_registration'), $choices);
        $mform->setDefault('encoding', 'UTF-8');

        $choices = array('10'=>10, '20'=>20, '100'=>100, '1000'=>1000, '100000'=>100000);
        $mform->addElement('select', 'previewrows', get_string('rowpreviewnum', 'local_registration'), $choices);
        $mform->setType('previewrows', PARAM_INT);

        $this->add_action_buttons(false, get_string('uploadcoupon', 'local_registration'));
    }
}