<?php

namespace local_policies;

global $CFG;

use core_table\external\dynamic\get;
use moodleform;

require_once($CFG->libdir . '/formslib.php');

class policies_form extends moodleform
{
    public function definition()
    {
        global $DB;
        $mform = $this->_form;
        $categories = $DB->get_records('local_policycategories_table');
        $catarray[0] = 'Choose';
        foreach ($categories as $category) {
            $catarray[$category->id] = $category->name;
        }

        $mform->addElement('text', 'name', get_string('name', 'local_policies'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('errname', 'local_policies'), 'required', null, 'client');

        $mform->addElement('autocomplete', 'categoryid', get_string('categoryname', 'local_policies'), $catarray);
        $mform->setType('categoryid', PARAM_INT);
        $mform->addRule('categoryid', get_string('errcategoryname', 'local_policies'), 'required', null, 'client');

        $mform->addElement('filemanager', 'overview_filemanager', get_string('file'), null,
            array('subdirs' => 0, 'maxbytes' => 0, 'maxfiles' => 5, 'accepted_types' => ['.pdf', '.ppt', '.pptx', '.doc', '.docx']));
        $mform->addRule('overview_filemanager', get_string('errdoc', 'local_policies'), 'required', null, 'client');

        $this->add_action_buttons(true,get_string('submit','local_policies'));
    }

    public function validation($data, $files)
    {
        $errors =  parent::validation($data, $files);

        if (empty($data['categoryid'])) {
            $errors['categoryid'] =  get_string('errcategoryname','local_policies');
        }
        return $errors;
    }
}