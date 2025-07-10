<?php

namespace local_coursenotify;

require_once $CFG->libdir . '/formslib.php';

use moodleform;

class editform extends moodleform{
    public function definition()
    {
        $this->add_header();
        $this->common_fields();
        $this->specific_fields();
        $this->add_action_buttons();
    }
    public function add_header()
    {
        $mform = $this->_form;
        $mform->addElement('header', 'heading', get_string('formheader', utility::$component));
    }
    public function common_fields()
    {
        $mform = $this->_form;
        $mform->addElement('hidden', 'id', 0);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('text', 'title', get_string('title', utility::$component));
        $mform->setType('title', PARAM_NOTAGS);
        $mform->addRule('title', get_string('required'), 'required');

        $mform->addElement('text', 'subject', get_string('subject', utility::$component));
        $mform->setType('subject', PARAM_NOTAGS);
        $mform->addRule('subject', get_string('required'), 'required');

        $statusopt = array(
            get_string('disable'),
            get_string('enable'),
        );
        $select = $mform->addElement('select', 'status', get_string('status', utility::$component),$statusopt);
        $select->setSelected(1);
        $mform->setType('status', PARAM_INT);
    }
    public function specific_fields()
    {
        $mform = $this->_form;
        $mform->addElement('editor', 'message_editor', get_string('message', utility::$component), null, $this->_customdata['editoroptions']);
        $mform->setType('message_editor', PARAM_RAW);
        $mform->addRule('message_editor', get_string('required'), 'required');

        $options = array('optional' => false, 'defaultunit' => 86400);
        $threshold = $mform->addElement('duration', 'threshold', get_string('threshold', utility::$component), $options);
        $threshold->getElements()[1]->removeOption(3600);//hour
        $threshold->getElements()[1]->removeOption(60);//minute
        $threshold->getElements()[1]->removeOption(1);//second

        $beforeafteropt = utility::get_beforeafteropt();
        $elarr[] = $mform->createElement('select','beforeafter',get_string('beforeafter',utility::$component),$beforeafteropt);

        $refdateopt = utility::get_refdateopt();
        $elarr[] = $mform->createElement('select','refdate',get_string('refdate',utility::$component),$refdateopt);

        $mform->addGroup($elarr,'ref','',null,false);

        $mform->addElement('checkbox', 'immediate', null, get_string('immediately',utility::$component), array(), false);

        $mform->disabledIf('threshold','immediate','checked');
        $mform->disabledIf('ref','immediate','checked');

        $expirynotifyopt = utility::get_expirynotifyopt();
        $mform->addElement('select','expirynotify',get_string('expirynotify',utility::$component),$expirynotifyopt);
    }
}
