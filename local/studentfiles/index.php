<?php

use local_studentfiles\util;

require_once dirname(__FILE__) . '/../../config.php';
require_once("{$CFG->libdir}/formslib.php");

$userid = optional_param('userid',$USER->id,PARAM_INT);
$url = new moodle_url('/local/studentfiles/index.php',['userid' => $userid,]);

require_login();

$usercontext = context_user::instance($userid);
$title = util::get_string('pluginname');
$returnurl = new moodle_url($url);

$PAGE->set_context($usercontext);
$PAGE->set_url($url);
$PAGE->set_title($title);

$canmanage = util::user_can_access();

if($userid != $USER->id && !$canmanage) {
    print_error('nopermission',util::component);
}

if($canmanage) {
    $PAGE->add_body_class('studentsfilemanager');
}

class files_form extends moodleform {

    public function definition() {
        $mform = $this->_form;

        $data = $this->_customdata['data'];
        $options = $this->_customdata['options'];

        $mform->addElement('filemanager', 'files_filemanager', get_string('files'), null, $options);

        if($this->_customdata['canmanage']){
            $this->add_action_buttons(true, get_string('savechanges'));
        }

        $this->set_data($data);
    }
}

$data = new stdClass();
$options = array('subdirs' => 0, 'maxbytes' => 5 * \core_admin\local\settings\filesize::UNIT_MB, 'accepted_types' => '.pdf',);
file_prepare_standard_filemanager($data, 'files', $options, $usercontext, util::component, util::filearea, 0);

$mform = new files_form($url, array('data' => $data, 'options' => $options, 'canmanage' => $canmanage,));

if ($mform->is_cancelled()) {
    redirect($returnurl);
} else if ($formdata = $mform->get_data()) {
    $formdata = file_postupdate_standard_filemanager($formdata, 'files', $options, $usercontext, util::component, util::filearea, 0);
    redirect($returnurl);
}
$user= $DB->get_record('user',array('id' => $userid));
echo $OUTPUT->header();
echo '<h4>'.$user->firstname.' '.$user->lastname.'</h4>';
echo '<p><b>Instructions:</b></p>';
echo '<p>Below you will find documents that are related to your learning journey. To download them click on the file name and select the download button.</p>';
echo $OUTPUT->box_start('generalbox');

if(util::user_can_access()) {
    echo $OUTPUT->container_start('text-right mb-2');
    echo $OUTPUT->single_button('upload.php',util::get_string('studentfiles'),'post',['primary' =>true,]);
    echo $OUTPUT->container_end();
}

$mform->display();

echo <<<HTML
<style>
body:not(.studentsfilemanager) .fp-file-delete,
body:not(.studentsfilemanager) .fp-file-update,
body:not(.studentsfilemanager) .fp-file-cancel,
body:not(.studentsfilemanager) .fp-toolbar {
    display: none;
}
body:not(.studentsfilemanager) .fp-saveas input {
    user-select: none;
    cursor: not-allowed;
    background-color: #f3f7f9;
    pointer-events: none;
}
</style>
HTML;


echo $OUTPUT->box_end();
echo $OUTPUT->footer();
