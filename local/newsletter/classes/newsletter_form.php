<?php

namespace local_newsletter;
global $CFG;
use moodleform;
use stdClass;

require_once($CFG->libdir . '/formslib.php');
class newsletter_form extends moodleform {
    protected function definition() {
        $mform = $this->_form;

        $feedbacks = [0 => get_string('choose')];
        $feedbacks += $this->_customdata['feedback'];
        $data = new stdClass();
        $data->sitelogo = $this->_customdata['sitelogo'];

        $mform->addElement('text', 'name', get_string('name', 'local_newsletter'));
        $mform->addRule('name', get_string('required'), 'required', null, 'client');
        $mform->setType('name', PARAM_RAW);

        $mform->addElement('text', 'subject', get_string('subject', 'local_newsletter'),'size = 75');
        $mform->setType('subject', PARAM_RAW);
        $mform->addRule('subject', get_string('required'), 'required', null, 'client');

        $mform->addElement('editor', 'message', get_string('message', 'local_newsletter'));
        $mform->setType('message', PARAM_RAW);
//        $messagebody = ['text' => get_string('messagebody', 'local_newsletter', $data)];

//        $mform->setDefault("message", $messagebody);
        $mform->addRule('message', get_string('required'), 'required', null,'client');
        $mform->addElement('static', 'note', null, 'Placeholder <br>Firstname: '.get_string('placeholder:firstname',
                'local_newsletter').'<br>Lastname: '.get_string('placeholder:lastname', 'local_newsletter')
                .'<br>Activitylink: '.get_string('placeholder:activitylink', 'local_newsletter'));

        $mform->addElement('date_time_selector', 'scheduledate', get_string('scheduledate', 'local_newsletter'));
        $mform->addRule('scheduledate', get_string('required'), 'required', null,'client');

        $mform->addElement('select', 'activitylist', get_string('nameofactivitys', 'local_newsletter'), $feedbacks);
        $mform->addRule('activitylist', get_string('required'), 'required', null,'client');

        $mform->addElement('advcheckbox', 'remindermail', null, get_string('remindermail', 'local_newsletter'));

        $mform->addElement('date_time_selector', 'enddate', get_string('enddate', 'local_newsletter'));

        $mform->hideIf('enddate', 'remindermail', 'eq', 0);

        $this->add_action_buttons(true, get_string('submit'));
    }

    public function validation($data, $files) {
        $errors =  parent::validation($data, $files);
        if (empty($data['activitylist'])) {
            $errors['activitylist'] = get_string('err:activitylist', 'local_newsletter');
        }

        if ($data['scheduledate'] < time()) {
            $errors['scheduledate'] = get_string('err:scheduledate', 'local_newsletter');
        }

        if (!empty($data['remindermail']) && $data['enddate'] < $data['scheduledate']) {
            $errors['enddate'] = get_string('err:enddate', 'local_newsletter');
        }
        return $errors;
    }
}