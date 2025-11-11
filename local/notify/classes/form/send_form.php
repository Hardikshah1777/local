<?php
namespace local_notify\form;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/formslib.php');

class send_form extends \moodleform {
    public function definition() {
        $mform = $this->_form;
        $users = $this->_customdata;
        $userdata[0] = get_string('choosedots');
        foreach ($users as $user){
            $userdata[$user->username] = fullname($user);
        }

        $mform->addElement('autocomplete', 'username', get_string('recipient', 'local_notify'), $userdata);
        $mform->setType('username', PARAM_USERNAME);
        $mform->addRule('username', null, 'required', null, 'client');

        $mform->addElement('textarea', 'message', get_string('messagetext', 'local_notify'), '" rows="5" cols="50"');
        $mform->addRule('message', null, 'required', null, 'client');

        $this->add_action_buttons(true, get_string('send', 'local_notify'));
    }
}
