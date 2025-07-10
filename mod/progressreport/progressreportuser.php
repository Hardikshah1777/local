<?php
use mod_progressreport\progressreportuser_table;

require_once ('../../config.php');

$id = optional_param('id', 0, PARAM_INT); // Course Module ID
$userid = optional_param('userid',0,PARAM_INT);
$progressreportid= optional_param('progressreportid',0,PARAM_INT);
if($id){
    if (!$cm = get_coursemodule_from_id('progressreport', $id)) {
        print_error('invalidcoursemodule');
    }

    $progressreport = $DB->get_record('progressreport', array('id'=>$cm->instance), '*', MUST_EXIST);
}
$user = core_user::get_user($userid);
$course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);

$url = new moodle_url('/mod/progressreport/view.php', array('id' => $cm->id));
$PAGE->set_url($url);
$PAGE->set_title($course->shortname.': '.$progressreport->name);
$PAGE->set_heading($course->fullname);
$PAGE->set_activity_record($progressreport);

$col =  [
        'date' => "Date",
        'progressreport' => get_string('pluginname','mod_progressreport'),
];

$table = new progressreportuser_table('progressreportuser');
$table->set_sql('pu.id,pu.progressreportid,pu.timemodified,pu.userid,pu.attempt,cm.id as cmid',
        '{progressreport_user} pu LEFT JOIN 
                {course_modules} cm ON cm.id = :cmid',
        'pu.userid = :userid AND pu.progressreportid = :progressreportid AND pu.confirm = 1',['userid' => $userid,'progressreportid' => $progressreportid,'cmid' => $id]);
$table->define_headers(array_values($col));
$table->define_columns(array_keys($col));
$table->collapsible(false);
$table->define_baseurl($url);
$table->is_sortable = false;

echo $OUTPUT->header();
//if(!has_capability('mod/progressreport:progressreport',$context)){
//    print_error('notaccess','mod_progressreport');
//}
echo $OUTPUT->heading(format_string($user->firstname), 2);
$table->out(20,false);
echo $OUTPUT->footer();

