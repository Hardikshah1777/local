<?php

namespace local_probeit_bookmark;

use moodleform;
require_once($CFG->libdir . '/formslib.php');

class probeitbookmark_form extends moodleform
{
    public function definition()
    {
        $mform = $this->_form;

        $mform->addElement('text', 'title', get_string('formtitle', 'local_probeit_bookmark'), 'maxlength="100"');
        $mform->setType('title', PARAM_TEXT);
        $mform->addRule('title', null, 'required', null, 'client');

        $mform->addElement('text', 'link', get_string('link', 'local_probeit_bookmark'));
        $mform->setType('link', PARAM_URL);
        $mform->addRule('link', null, 'required', null, 'client');

        $mform->addElement('textarea', 'description', get_string('description', 'local_probeit_bookmark'), array('rows' => 5));
        $mform->setType('description', PARAM_TEXT);

        $this->add_action_buttons(true, get_string('submit','local_probeit_bookmark'));
    }

    public function validation($data, $files)
    {
        $errors =  parent::validation($data, $files);

        if (empty(trim($data['title']))) {
            $errors['title'] =  get_string('errtitle','local_probeit_bookmark');
        }

        if (empty(trim($data['link']))) {
            $errors['link'] =  get_string('errlink','local_probeit_bookmark');
        }else {
            if (!filter_var($data['link'],FILTER_VALIDATE_URL)){
                $errors['link'] =  get_string('errlink','local_probeit_bookmark');
            }
        }

        return $errors;
    }
}