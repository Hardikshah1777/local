<?php

require_once('../../config.php');
require_once($CFG->libdir.'/formslib.php');

$id = required_param('id', PARAM_INT);

$context = context_system::instance();
$url = new moodle_url('/mod/feedback/verify.php', ['id' => $id]);

$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_title(get_string('emailverify', 'feedback'));
$PAGE->set_heading(get_string('emailverify', 'feedback'));

class email_form extends moodleform {
    public function definition() {
        $mform =& $this->_form;
        $fcdata = $this->_customdata;
        $mform->addElement('hidden', 'id', $fcdata->id);

        $mform->addElement('text', 'email', get_string('email'), 'maxlength="100" size="30"');
        $mform->setType('email', PARAM_RAW_TRIMMED);
        $mform->addRule('email', get_string('emailrequired','feedback'), 'required', null, 'client');
        $mform->addElement('submit', 'submit', get_string('submit', 'feedback'));
    }

    public function validation($data, $files)
    {
        global $DB;
        $errors = parent::validation($data, $files);
        if ($data['email']){
            $invalidemail = $DB->record_exists('user', ['email' => $data['email']]);
            $user = $DB->get_record('user', ['email' => $data['email']]);
            if (!validate_email($data['email']) || empty($invalidemail) || !empty($user->suspended)) {
                $errors['email'] = get_string('invalidemail');
            }
        }
        return $errors;
    }
}

$cdata = new stdClass();
$cdata->id = $id;
$email_form = new email_form($url, $cdata);
$formdata = $email_form->get_data();

if (!empty($formdata)){
    $user = $DB->get_record('user', ['email' => $formdata->email]);
    if (!empty($user)){
        [$course, $cm] = get_course_and_cm_from_cmid($id);
        $modinfo = get_fast_modinfo($course, $user->id);
        $cm = $modinfo->get_cm($cm->id);
        if (!empty($cm->available)) {
            complete_user_login($user);
            redirect(new moodle_url('/mod/feedback/complete.php', ['id' => $id]));
        }
    }
}
echo $OUTPUT->header();
$email_form->display();
echo $OUTPUT->footer();