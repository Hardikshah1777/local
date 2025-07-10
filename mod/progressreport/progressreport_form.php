<?php

use mod_progressreport\constants;
use mod_progressreport\output\field;
use mod_progressreport\output\market;
use mod_progressreport\output\progressreport_form;
use mod_progressreport\output\section;
use mod_progressreport\output\skill;
define('SAVE',1);
define('REQUIREDSKIL',1);
include_once ('../../config.php');
require_once ($CFG->dirroot.'/mod/progressreport/locallib.php');

$id = optional_param('id', 0, PARAM_INT); // Course Module ID
$userid = optional_param('userid',0,PARAM_INT);
$progressreportid = optional_param('progressreportid',0,PARAM_INT);
$sesskey = optional_param('sesskey','reset', PARAM_RAW);

$redirecturl = new moodle_url('/mod/progressreport/view.php',['id'=>$id]);
if($id){
    if (!$cm = get_coursemodule_from_id('progressreport', $id)) {
        print_error('invalidcoursemodule');
    }

    $progressreport = $DB->get_record('progressreport', array('id'=>$cm->instance), '*', MUST_EXIST);
}

$course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);

$url = new moodle_url('/mod/progressreport/view.php', array('id' => $cm->id));
$PAGE->set_url($url);
$PAGE->set_title($course->shortname.': '.$progressreport->name);
$PAGE->set_heading($course->fullname);
$PAGE->set_activity_record($progressreport);

$userdata = core_user::get_user($userid);

$progressreportform = new progressreport_form();

$progressreport = $DB->get_record('progressreport',['id'=>$progressreportid]);
$progressreportnuserinfo = $DB->get_records('progressreport_field',['progressreportid'=>$progressreportid]);

$progressreportform->userid = $userdata->id;
$progressreportform->moduleid = $id;
$progressreportform->progressreportid = $progressreport->id;
$progressreportform->progressreportname = $progressreport->name;
$progressreportform->progressreporturl = new moodle_url('/mod/progressreport/progressreport_form.php',['userid'=>$userid,'progressreportid'=>$progressreportid,'id'=>$id]);
$progressreportform->lesson = $progressreport->nolesson;


foreach ($progressreportnuserinfo as $userinfo){
    $userfielddata = new field($userinfo->field,$userinfo->fieldvalue,$userid);
    $userfielddata->id = $userinfo->id;
    $userfielddata->progressreportid = $progressreportid;
    $userfielddata->attemptuser = 0;
    $progressreportform->add_field($userfielddata);
}

$progressreportmarkets = get_progressreport_market($progressreportid);
$i =0;
foreach ($progressreportmarkets as $progressreportmarket){
    $i++;
    $market = new market($progressreportmarket->id,$progressreportmarket->name);
    $progressreportform->add_market($market);
    $market->number = $i;
}


$progressreportsections = $DB->get_records('progressreport_section',['progressreportid'=>$progressreportid],'sortorder');
foreach ($progressreportsections as $progressreportsec){
    if(empty($progressreportsec->deleted) && empty($progressreportsec->visible)) {
        $section = new section($progressreportsec->id, $progressreportsec->name);
        $progressreportform->add_section($section);
        $skills = get_progressreport_sections_skill($progressreportsec->id);
        foreach ($skills as $skill) {
            if (empty($skill->deleted) && empty($skill->visible)) {
                $skillnew = new skill($skill->id, $skill->name);
                $section->add_skill($skillnew);
                $skillnew->nolesson = $progressreport->nolesson;
                $skillnew->validation($skill->validation);
            }
        }
    }
}

if(confirm_sesskey($sesskey)){
    $skillid = optional_param_array('skillid',[],PARAM_TEXT);
    $field = optional_param_array('userfieldid',[],PARAM_TEXT);
    $fieldname = optional_param_array('name',[],PARAM_TEXT);
    $notes = optional_param('notes',null,PARAM_TEXT);
    $lessonskill = $_POST['market'];
    $confirm = optional_param('confirmindexes',0,PARAM_INT);
    $progressreportform->postnote = $_POST['notes'];
    $averages = optional_param_array('lessonaverage',[],PARAM_RAW);

    $errors = [];
    if(!empty($confirm)){
        foreach (array_keys($skillid) as $skillnewid){
            $validation = get_validation_type($skillnewid);
            if($validation == REQUIREDSKIL){
                if(in_array(0,$lessonskill[$skillnewid])){
                    $errors[$skillnewid] = true;
                }
            }
        }
    }
    $progressreportform->set_errors($errors);
    $progressreportform->setPostdata($lessonskill);

    $sql = "SELECT MAX(attempt) as attempt FROM {progressreport_user} WHERE userid = :userid AND progressreportid = :progresssreportid";
    $attemptrec = $DB->get_record_sql($sql,array('userid' => $userid,'progresssreportid' => $progressreportid));
    if(empty($attemptrec->attempt)){
        $attempt = 1;
    }else{
        $attempt = $attemptrec->attempt + 1;
    }
    if(empty($errors)) {

        $data = new stdClass();
        $data->progressreportid = $progressreportid;
        $data->userid = $userid;
        $data->notes = $notes;
        $data->save = SAVE;
        $data->attempt = $attempt;
        if(!empty($confirm)){
            $data->confirm = $confirm;
        }
        $data->nolesson = $progressreport->nolesson;
        $data->timemodified = time();
        $insert = $DB->insert_record('progressreport_user', $data);

        if ($insert) {
            foreach (array_keys($skillid) as $skill) {
                $lessondatas = $lessonskill[$skill];
                foreach ($lessondatas as $key => $lessondata) {
                    $skilldataobject = new stdClass();
                    $skilldataobject->progressreportuserid = $insert;
                    $skilldataobject->skillid = $skill;
                    $skilldataobject->marketid = $lessondata;
                    $skilldataobject->lessonnumber = $key;
                    $skilldataobject->timemodified = time();
                    $userskill = $DB->insert_record('progressreport_user_skill', $skilldataobject);
                }
            }

            for ($lesson = 1; $lesson <= $progressreport->nolesson; $lesson++) {
                $lessonno = 0;
                $count = 0;
                foreach (array_keys($skillid) as $skill) {
                    $lessondatas = $lessonskill[$skill];
                    if (!empty($lessondatas[$lesson])) {
                        $lessonnew = $DB->get_field('progressreport_market', 'mnumber', ['id' => $lessondatas[$lesson]]);
                        $count += count($lessonnew);
                        $lessonno += $lessonnew;
                    }
                }
                $average = $lessonno / $count;

                if (is_nan($average)) {
                    $newaverage = 0;
                } else {
                    $newaverage = $average;
                }
                $lessondata = new stdClass();
                $lessondata->progressreportuserid = $insert;
                $lessondata->lesson = $lesson;
                $lessondata->average = $newaverage;
                $lessondata->time = time();
                $userlesson = $DB->insert_record('progressreport_user_lesson', $lessondata);
            }

            foreach ($field as $fielddata) {
                $fielddata = $DB->get_record('progressreport_field', ['id' => $fielddata]);
                $fielddataid = $fielddata->id;
                if ($fielddata->fieldvalue == constants::GROUPS[constants::FILLABLE] ||
                        $fielddata->fieldvalue == constants::GROUPS[constants::INSTUCTNAME]) {
                    $fileddataobject = new stdClass();
                    $fileddataobject->progressreportuserid = $insert;
                    $fileddataobject->fieldid = $fielddataid;
                    $fileddataobject->fieldvalue = $fieldname[$fielddataid];
                    $fileddataobject->timemodified = time();
                    $userfield = $DB->insert_record('progressreport_field_info', $fileddataobject);
                }
            }
            redirect($redirecturl);
        }
    }
}


echo $OUTPUT->header();
echo $OUTPUT->render($progressreportform);
echo $OUTPUT->footer();