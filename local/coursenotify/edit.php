<?php
require_once(dirname(__FILE__) . '/../../config.php');
use local_coursenotify\utility;
use local_coursenotify\editform;

$id = optional_param('id',0,PARAM_INT);
$courseid = required_param('courseid', PARAM_INT);

$url = new moodle_url('/local/coursenotify/edit.php', array('courseid' => $courseid));
$redirecturl = new moodle_url('/local/coursenotify/index.php',array('courseid' => $courseid));
$PAGE->set_url($url);

require_login($courseid);

$PAGE->set_title(get_string('edittitle',utility::$component));
$PAGE->set_heading(get_string('edittitle',utility::$component));

require_capability('local/coursenotify:editnotification', $PAGE->context);

$editoroptions = utility::get_editoroptions();
$editoroptions['context'] = $PAGE->context;
$customdata['editoroptions'] = $editoroptions;

$form = new editform($url,$customdata);

if (!empty($id) && $rc = $DB->get_record('local_coursenotify', array('id' => $id))) {
    $rc = file_prepare_standard_editor($rc, 'message', $editoroptions, $PAGE->context, utility::$component, utility::$filearea, $rc->id);
    if($rc->beforeafter == LOCAL_COURSENOTIFY_IMMEDIATE) $rc->immediate = 1;
    $form->set_data($rc);
}

if ($data = $form->get_data()) {
    $data->courseid = $courseid;
    if(!empty($data->immediate)){
        $data->beforeafter = LOCAL_COURSENOTIFY_IMMEDIATE;
        $data->refdate = LOCAL_COURSENOTIFY_IMMEDIATE;
    }

    if (!empty($data->id)) {
        $data->timemodified = time();
        $DB->update_record('local_coursenotify', $data);
        $msg = get_string('updatemsg', utility::$component);
    } else {
        $data->timecreated = $data->timemodified = time();
        $data->id = $DB->insert_record('local_coursenotify', $data);
        $msg = get_string('insertmsg', utility::$component);
    }
    $data = file_postupdate_standard_editor($data, 'message', $editoroptions, $PAGE->context, utility::$component, utility::$filearea, $data->id);
    $DB->update_record('local_coursenotify',$data);
    redirect($redirecturl, $msg);
}elseif ($form->is_cancelled()) {
    redirect($redirecturl);
}

echo $OUTPUT->header();
$form->display();
echo $OUTPUT->footer();
