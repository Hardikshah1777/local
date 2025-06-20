<?php
require_once ('../../config.php');
require_once $CFG->libdir . '/tablelib.php';

$moduleid = optional_param('moduleid', 0, PARAM_INT); // Course Module ID
$userid = optional_param('userid', 0, PARAM_INT); // Course Module ID
$evaluationid = optional_param('evaluationid', 0, PARAM_INT); // Course Module ID

if($moduleid){
    if (!$cm = get_coursemodule_from_id('evaluation', $moduleid)) {
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
$USER->moduleid = $moduleid;

class evaluationuser_table extends table_sql{
    public function start_html() {
        $oldvalue = $this->use_pages;
        $this->use_pages = false;
        parent::start_html();
        $this->use_pages = $oldvalue;
    }
    public function col_date($row){
        $a = $row->timemodified;
        $t = gmdate('d M Y', $a);
        return $t;
    }
    public function col_evaluation($row){
        global $OUTPUT,$USER,$DB;

        $sql = "SELECT MAX(attempt) as attempt FROM {evaluation_user} WHERE userid = :userid AND evaluationid = :evaluationid";
        $attemptrec = $DB->get_record_sql($sql,array('userid' => $row->userid,'evaluationid' => $USER->evaluationid));

        if(!empty($attemptrec->attempt)){

            $viewurl = new moodle_url('/mod/evaluation/userview.php',['id' => $row->id,'attempt'=>$attemptrec->attempt,'moduleid'=>$USER->moduleid]);
            $view = $OUTPUT->single_button($viewurl, get_string('view'), 'get');

            $pdfurl = new moodle_url('/mod/evaluation/pdf.php',['id' => $row->id]);
            $download = $OUTPUT->single_button($pdfurl, get_string('download'), 'get');

            return($view.$download);
        }
    }
}

$col =  [
        'date' => "Date",
        'evaluation' => get_string('pluginname','mod_evaluation'),
];
$table = new evaluationuser_table('evaluationuser_table');
$table->set_sql('*',
        '{evaluation_user} eu',
        'eu.userid = :userid AND eu.evaluationid = :evaluationid',['userid' => $userid,'evaluationid' => $evaluationid]);
$table->define_headers(array_values($col));
$table->define_columns(array_keys($col));
$table->collapsible(false);
$table->define_baseurl($url);
$table->is_sortable = false;
$user = core_user::get_user($userid);
echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($evaluation->name.' of '.$user->firstname.' '.$user->lastname), 2);
$table->out(20,false);
echo $OUTPUT->footer();