<?php
use mod_meltassessment\meltassessmentuser_table;

require_once ('../../config.php');

$id = optional_param('id', 0, PARAM_INT); // Course Module ID
$userid = optional_param('userid',0,PARAM_INT);
$meltassessmentid= optional_param('meltassessmentid',0,PARAM_INT);
if($id){
    if (!$cm = get_coursemodule_from_id('meltassessment', $id)) {
        print_error('invalidcoursemodule');
    }

    $meltassessment = $DB->get_record('meltassessment', array('id'=>$cm->instance), '*', MUST_EXIST);
}
$user = core_user::get_user($userid);
$course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);

$url = new moodle_url('/mod/meltassessment/view.php', array('id' => $cm->id));
$PAGE->set_url($url);
$PAGE->set_title($course->shortname.': '.$meltassessment->name);
$PAGE->set_heading($course->fullname);
$PAGE->set_activity_record($meltassessment);

$col =  [
        'date' => "Date",
        'meltassessment' => get_string('pluginname','mod_meltassessment'),
];

$table = new meltassessmentuser_table('meltassessmentuser');
$table->set_sql('mu.id,mu.meltassessmentid,mu.timemodified,mu.userid,mu.attempt,cm.id as cmid',
    '{meltassessment_user} mu LEFT JOIN 
           {course_modules} cm ON cm.id = :cmid',
    'mu.userid = :userid AND mu.meltassessmentid = :meltassessmentid AND mu.confirm = 1', ['userid' => $userid, 'meltassessmentid' => $meltassessmentid, 'cmid' => $id]);
$table->define_headers(array_values($col));
$table->define_columns(array_keys($col));
$table->collapsible(false);
$table->define_baseurl($url);
$table->is_sortable = false;

echo $OUTPUT->header();
//if(!has_capability('mod/meltassessment:meltassessment',$context)){
//    print_error('notaccess','mod_meltassessment');
//}
echo $OUTPUT->heading(format_string($user->firstname), 2);
$table->out(20,false);
echo $OUTPUT->footer();

