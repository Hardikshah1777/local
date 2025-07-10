<?php

use mod_progressreport\users_table;
use mod_progressreport\users_view_table;

require_once ('../../config.php');
require_once $CFG->libdir . '/formslib.php';

$id = optional_param('id', 0, PARAM_INT); // Course Module ID
$fullname = optional_param('fullname','',PARAM_TEXT);
$email = optional_param('email','',PARAM_TEXT);
if($id){
    if (!$cm = get_coursemodule_from_id('progressreport', $id)) {
        print_error('invalidcoursemodule');
    }

    $progressreport = $DB->get_record('progressreport', array('id'=>$cm->instance), '*', MUST_EXIST);
}

$course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);

$url = new moodle_url('/mod/progressreport/view.php', array('id' => $cm->id,'fullname' => $fullname,'email' => $email));
$PAGE->set_url($url);
$PAGE->set_title($course->shortname.': '.$progressreport->name);
$PAGE->set_heading($course->fullname);
$PAGE->set_activity_record($progressreport);
class sort_form extends moodleform{
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('text','fullname',get_string('fullname','mod_progressreport'));
        $mform->setType('fullname',PARAM_TEXT);

        $mform->addElement('text','email',get_string('email','mod_progressreport'));
        $mform->setType('email',PARAM_TEXT);

        $this->add_action_buttons(false,get_string('search'));
    }
}

$mform = new sort_form($url);
if(!empty($fullname) || !empty($email)){
    $data = ['fullname' => $fullname,'email' => $email];
    $mform->set_data($data);
}
echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($progressreport->name), 2);

$set = '';
if(!empty($fullname) && empty($email)){
    if(strrpos($fullname," ") != true){
        $set = 'AND (u.firstname LIKE "%'.$fullname.'%" OR u.lastname LIKE "%'.$fullname.'%") ';
    }else{
        $set = explode(" ", $fullname);
        $set = 'AND u.firstname LIKE "%'.$set[0].'%" AND u.lastname LIKE "%'.$set[1].'%"';
    }
}elseif (empty($fullname) && !empty($email)){
    $set = ' AND u.email LIKE "%'.$email.'%" ';
}else if(!empty($fullname) && !empty($email)) {
    if(strrpos($fullname," ") != true){
        $set = 'AND u.email LIKE "%'.$email.'%" AND (u.firstname LIKE "%'.$fullname.'%" OR u.lastname LIKE "%'.$fullname.'%") ';
    }else{
        $set = explode(" ", $fullname);
        $set = 'AND u.firstname LIKE "%'.$set[0].'%" AND u.lastname LIKE "%'.$set[1].'%" AND u.email LIKE "%'.$email.'%" ';
    }
}

$table = new users_table('usertable');
$table->set_sql('distinct(ue.userid),ue.id,u.firstname,u.lastname,u.email,u.id as userid,cm.id as cmid,instance as progressid',
        '{user} u JOIN 
                {user_enrolments} ue ON ue.userid = u.id JOIN 
                {enrol} e ON e.id = ue.enrolid JOIN 
                {course} c ON c.id = e.courseid LEFT JOIN 
                {course_modules} cm ON cm.id = :cmid',
        'u.deleted = 0 AND u.suspended = 0 AND u.id > 2 AND c.id = :courseid '.$set.'',['courseid' => $course->id,'cmid' => $id]);
$col =  [
        'fullname' => get_string('fullname','mod_progressreport'),
        'email' => get_string('email','mod_progressreport'),
        'progressreport' => get_string('pluginname','mod_progressreport'),
];
$table->define_headers(array_values($col));
$table->define_columns(array_keys($col));
$table->collapsible(false);
$table->define_baseurl($url);
$table->sortable(false);


if(!has_capability('mod/progressreport:progressreport',$context)){

    $sql = "SELECT MAX(attempt) as attempt FROM {progressreport_user} WHERE userid = :userid AND progressreportid = :progressreportid";
    $attemptrec = $DB->get_record_sql($sql,array('userid' => $USER->id,'progressreportid' => $progressreport->id));

    if(empty($attemptrec->attempt)){
        $value = '<h5 class="mt-2">'.get_string('noattempt','mod_progressreport').'</h5>';
        echo $value;
    }else{

        $col =  [
                'date' => "Date",
                'progressreport' => get_string('pluginname','mod_progressreport'),
        ];
        $userstable = new users_view_table('progressreporttable');
        $userstable->set_sql('pu.id,pu.timemodified,pu.attempt,cm.id as cmid',
                '{progressreport_user} pu
                        LEFT JOIN {course_modules} cm ON cm.id = :cmid',
                'pu.userid = :userid AND pu.progressreportid = :progressreportid AND pu.confirm = 1',['userid' => $USER->id,'progressreportid' => $progressreport->id,'cmid' => $id]);
        $userstable->define_headers(array_values($col));
        $userstable->define_columns(array_keys($col));
        $userstable->collapsible(false);
        $userstable->define_baseurl($url);
        $userstable->is_sortable = false;
        $userstable->out(20,false);
    }
    echo $OUTPUT->footer();
    die();
}
$mform->display();
$table->out(30,true);
echo $OUTPUT->footer();