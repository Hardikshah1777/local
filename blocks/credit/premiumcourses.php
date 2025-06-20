<?php

require_once '../../config.php';
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/tablelib.php');

$courseid = optional_param('courseid',0,PARAM_INT);
$creditcourseid = optional_param('creditcourseid',0,PARAM_INT);
$search = optional_param( 'search', '', PARAM_ALPHANUM );

$url = new moodle_url('/blocks/credit/premiumcourses.php');
$context = context_system::instance();

$PAGE->set_title(get_string( 'title', 'block_credit'));
$PAGE->set_heading(get_string( 'heading', 'block_credit'));
$PAGE->set_url($url);
$PAGE->set_context($context);
require_login();

class coursesearchform extends moodleform
{
    public function definition()
    {
        $mform = $this->_form;
        $mform->addElement( 'text', 'search', get_string( 'searchcourse', 'block_credit'));
        $this->add_action_buttons( false, get_string( 'search', 'block_credit'));
    }
}
$coursesearchform = new coursesearchform($url);
$where = '';
$params = [];
if ($formdata = $coursesearchform->get_data()) {
    if (!empty($formdata->search)) {
        $search = trim( $formdata->search);
        $where = " AND (".$DB->sql_like('shortname',':shortname', false).
                 " OR " .$DB->sql_like('fullname',':fullname', false).")";
        $params['shortname'] = $params['fullname'] =  '%'.$search.'%';
    }
}

class premiumcourses extends table_sql
{
    public function col_coursecredit($row){
        $url = new moodle_url('/blocks/credit/premiumcourses.php');
        $coursecredit = '';
        if (!empty($row->coursecredit)){
            $coursecredit = $row->coursecredit;
        }
        $creditinput ='';
        $creditinput .= html_writer::start_tag('form',['action' => $url, 'name' => 'coursecreditform', 'method' => 'post']);
        $creditinput .= html_writer::tag('input','', ['name'=> 'creditcourseid', 'type' => 'hidden', 'value' => $row->id]);
        $creditinput .= html_writer::tag('input','',['type' => 'text', 'name' => 'coursecredit', 'value' => $coursecredit, 'class' => 'form-control w-50',
                         'placeholder' => get_string('coursecredit','block_credit')]);
        $creditinput .= html_writer::tag('button','submit',['type'=>'submit', 'name' => 'coursecreditform', 'class'=>'d-none']);
        $creditinput .= html_writer::end_tag('form');
        return $creditinput;
    }

    public function col_premium($row){
        $url = new moodle_url('/blocks/credit/premiumcourses.php');
        if (!empty($row->premium)){
            $selected = 'selected';
        }
        $premiumselect = '';
        $premiumselect .= html_writer::start_tag('form',['action' => $url, 'name' => 'formupdater', 'method' => 'post']);
        $premiumselect .= html_writer::tag('input','', ['name'=> 'courseid', 'type' => 'hidden', 'value' => $row->id]);
            $premiumselect .= html_writer::start_tag('select',['name' => 'premium', 'value'=>$row->id ,'class' => 'form-control w-50', 'onchange' => 'this.form.formupdater.click()']);
                $premiumselect .= html_writer::tag('option',get_string('no','block_credit'), [$selected=>$selected, 'value' => 0]);
                $premiumselect .= html_writer::tag('option',get_string('yes','block_credit'), [$selected=>$selected, 'value' => 1]);
            $premiumselect .= html_writer::end_tag('select');
        $premiumselect .= html_writer::tag('button','submit',['type'=>'submit', 'name' => 'formupdater', 'class'=>'d-none']);
        $premiumselect .= html_writer::end_tag('form');
        return $premiumselect;
    }
}
if (!empty($creditcourseid)){
    if(isset($_POST['coursecreditform'])){
        $coursecredit = $_POST['coursecredit'];
        $DB->set_field('course','coursecredit', $coursecredit, ['id' => $creditcourseid]);
    }
}
if (!empty($courseid)){
    $course = $DB->get_field('course', 'premium', ['id' => $courseid]);
    if (!empty($course)) {
        $DB->set_field('course','premium',0,['id' => $courseid]);
    } else {
        $DB->set_field('course','premium',1,['id' => $courseid]);
    }
    redirect($url);
}

$premiumcourses = new premiumcourses('id');
$premiumcourses->set_sql( '*', '{course}', 'id > 1 AND visible = 1 '.$where, $params);

$col = [
    'shortname' => get_string('course','block_credit'),
    'coursecredit' => get_string('credit','block_credit'),
    'premium' => get_string('premium','block_credit'),
];

$premiumcourses->define_headers(array_values($col));
$premiumcourses->define_columns(array_keys($col));
$premiumcourses->sortable(false);
$premiumcourses->collapsible(false);
$premiumcourses->is_downloadable(false);
$premiumcourses->define_baseurl($url);

echo $OUTPUT->header();
$coursesearchform->display();
$premiumcourses->out(30, false);
echo $OUTPUT->footer();
