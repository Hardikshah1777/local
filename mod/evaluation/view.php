<?php

use mod_evaluation\users_table;

require_once ('../../config.php');
require_once $CFG->libdir . '/tablelib.php';

$id = optional_param('id', 0, PARAM_INT); // Course Module ID

if($id){
    if (!$cm = get_coursemodule_from_id('evaluation', $id)) {
        print_error('invalidcoursemodule');
    }

    $evaluation = $DB->get_record('evaluation', array('id'=>$cm->instance), '*', MUST_EXIST);
}

$course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);

$url = new moodle_url('/mod/evaluation/view.php', array('id' => $cm->id));
$PAGE->set_url($url);
$PAGE->set_title($course->shortname.': '.$evaluation->name);
$PAGE->set_heading($course->fullname);
$PAGE->set_activity_record($evaluation);
$USER->evaluationid = $evaluation->id;
$USER->moduleid = $id;

class user_table extends table_sql{
    public function start_html() {
        $oldvalue = $this->use_pages;
        $this->use_pages = false;
        parent::start_html();
        $this->use_pages = $oldvalue;
    }
    public function col_evaluation($row){
        global $OUTPUT,$USER,$DB;

        $sql = "SELECT MAX(attempt) as attempt FROM {evaluation_user} WHERE userid = :userid AND evaluationid = :evaluationid";
        $attemptrec = $DB->get_record_sql($sql,array('userid' => $row->userid,'evaluationid' => $USER->evaluationid));

        if(!empty($attemptrec->attempt)){

            $completeurl = new moodle_url('/mod/evaluation/evaluationuser.php',['userid'=>$row->userid,'evaluationid'=>$USER->evaluationid,'moduleid'=>$USER->moduleid]);
            $complete = $OUTPUT->single_button($completeurl, get_string('completeevaluation','mod_evaluation'), 'get');

            $newurl = new moodle_url('/mod/evaluation/evaluation_form.php',['userid'=>$row->userid,'evaluationid'=>$USER->evaluationid,'moduleid'=>$USER->moduleid]);
            $newevaluation = $OUTPUT->single_button($newurl, get_string('newevaluation','mod_evaluation'), 'get');

            return($complete.$newevaluation);
        }else{
            $newurl = new moodle_url('/mod/evaluation/evaluation_form.php',['userid'=>$row->userid,'evaluationid'=>$USER->evaluationid,'moduleid'=>$USER->moduleid]);
            $newevaluation = $OUTPUT->single_button($newurl, get_string('newevaluation','mod_evaluation'), 'get');
            return ($newevaluation);
        }
    }
}

$col =  [
        'firstname' => 'Name',
        'email' => 'email',
        'evaluation' => get_string('pluginname','mod_evaluation'),
];
//list($sql,$params) = get_enrolled_sql($context,'u.id');
$table = new user_table('usertable');
$table->set_sql('ue.id,u.firstname,u.email,u.id as userid',
        '{user} u JOIN {user_enrolments} ue ON ue.userid = u.id JOIN {enrol} e ON e.id = ue.enrolid JOIN {course} c ON c.id = e.courseid',
            'u.deleted = 0 AND u.suspended = 0 AND c.id = :courseid',['courseid' => $course->id]);
$table->define_headers(array_values($col));
$table->define_columns(array_keys($col));
$table->collapsible(false);
$table->define_baseurl($url);
$table->is_sortable = false;

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($evaluation->name), 2);

if(!has_capability('mod/evaluation:evaluations',$context)){

    $sql = "SELECT MAX(attempt) as attempt FROM {evaluation_user} WHERE userid = :userid AND evaluationid = :evaluationid";
    $attemptrec = $DB->get_record_sql($sql,array('userid' => $USER->id,'evaluationid' => $evaluation->id));

    if(empty($attemptrec->attempt)){
        $value = '<h5 class="mt-2">'.get_string('noattempt','mod_evaluation').'</h5>';
        echo $value;
    }else{

        $col =  [
                'date' => "date",
                'evaluation' => get_string('pluginname','mod_evaluation'),
        ];
        $userstable = new users_table('evaluationusertable');
        $userstable->set_sql('*',
                '{evaluation_user} eu',
                'eu.userid = :userid AND evaluationid = :evaluationid',['userid' => $USER->id,'evaluationid' => $evaluation->id]);
        $userstable->define_headers(array_values($col));
        $userstable->define_columns(array_keys($col));
        $userstable->collapsible(false);
        $userstable->define_baseurl($url);

        $userstable->out(20,false);
    }
    echo $OUTPUT->footer();
    die();
}

$table->out(20,false);
echo $OUTPUT->footer();