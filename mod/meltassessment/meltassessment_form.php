<?php

use mod_meltassessment\constants;
use mod_meltassessment\output\field;
use mod_meltassessment\output\market;
use mod_meltassessment\output\meltassessment_form;
use mod_meltassessment\output\section;
use mod_meltassessment\output\skill;
define('SAVE',1);
define('REQUIREDSKIL',1);
include_once ('../../config.php');
require_once ($CFG->dirroot.'/mod/meltassessment/locallib.php');

$id = optional_param('id', 0, PARAM_INT); // Course Module ID
$userid = optional_param('userid',0,PARAM_INT);
$meltassessmentid = optional_param('meltassessmentid',0,PARAM_INT);
$sesskey = optional_param('sesskey','reset', PARAM_RAW);

$redirecturl = new moodle_url('/mod/meltassessment/view.php',['id'=>$id]);
if($id){
    if (!$cm = get_coursemodule_from_id('meltassessment', $id)) {
        print_error('invalidcoursemodule');
    }

    $meltassessment = $DB->get_record('meltassessment', array('id'=>$cm->instance), '*', MUST_EXIST);
}

$course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);

$url = new moodle_url('/mod/meltassessment/view.php', array('id' => $cm->id));
$PAGE->set_url($url);
$PAGE->set_title($course->shortname.': '.$meltassessment->name);
$PAGE->set_heading($course->fullname);
$PAGE->set_activity_record($meltassessment);

$userdata = core_user::get_user($userid);

$meltassessmentform = new meltassessment_form();

$meltassessment = $DB->get_record('meltassessment',['id'=>$meltassessmentid]);
$meltassessmentnuserinfo = $DB->get_records('meltassessment_field',['meltassessmentid'=>$meltassessmentid]);

$meltassessmentform->userid = $userdata->id;
$meltassessmentform->moduleid = $id;
$meltassessmentform->meltassessmentid = $meltassessment->id;
$meltassessmentform->meltassessmentname = $meltassessment->name;
$meltassessmentform->meltassessmenturl = new moodle_url('/mod/meltassessment/meltassessment_form.php',['userid'=>$userid,'meltassessmentid'=>$meltassessmentid,'id'=>$id]);
$meltassessmentform->lesson = $meltassessment->nolesson;


foreach ($meltassessmentnuserinfo as $userinfo){
    $userfielddata = new field($userinfo->field,$userinfo->fieldvalue,$userid);
    $userfielddata->id = $userinfo->id;
    $userfielddata->meltassessmentid = $meltassessmentid;
    $userfielddata->attemptuser = 0;
    $meltassessmentform->add_field($userfielddata);
}

$meltassessmentmarkets = get_meltassessment_market($meltassessmentid);
$i =0;
foreach ($meltassessmentmarkets as $meltassessmentmarket){
    $i++;
    $market = new market($meltassessmentmarket->id,$meltassessmentmarket->name);
    $meltassessmentform->add_market($market);
    $market->number = $i;
}


$meltassessmentsections = $DB->get_records('meltassessment_section',['meltassessmentid'=>$meltassessmentid],'sortorder');
foreach ($meltassessmentsections as $meltassessmentsec){
    if(empty($meltassessmentsec->deleted) && empty($meltassessmentsec->visible)) {
        $section = new section($meltassessmentsec->id, $meltassessmentsec->name);
        $meltassessmentform->add_section($section);
        $skills = get_meltassessment_sections_skill($meltassessmentsec->id);
        foreach ($skills as $skill) {
            if (empty($skill->deleted) && empty($skill->visible)) {
                $skillnew = new skill($skill->id, $skill->name);
                $section->add_skill($skillnew);
                $skillnew->nolesson = $meltassessment->nolesson;
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
    $meltassessmentform->postnote = $_POST['notes'];
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
    $meltassessmentform->set_errors($errors);
    $meltassessmentform->setPostdata($lessonskill);

    $sql = "SELECT MAX(attempt) as attempt FROM {meltassessment_user} WHERE userid = :userid AND meltassessmentid = :meltassessmentid";
    $attemptrec = $DB->get_record_sql($sql,array('userid' => $userid,'meltassessmentid' => $meltassessmentid));
    if(empty($attemptrec->attempt)){
        $attempt = 1;
    }else{
        $attempt = $attemptrec->attempt + 1;
    }
    if(empty($errors)) {

        $data = new stdClass();
        $data->meltassessmentid = $meltassessmentid;
        $data->userid = $userid;
        $data->notes = $notes;
        $data->save = SAVE;
        $data->attempt = $attempt;
        if(!empty($confirm)){
            $data->confirm = $confirm;
        }
        $data->nolesson = $meltassessment->nolesson;
        $data->timemodified = time();
        $insert = $DB->insert_record('meltassessment_user', $data);

        if ($insert) {
            foreach (array_keys($skillid) as $skill) {
                $lessondatas = $lessonskill[$skill];
                foreach ($lessondatas as $key => $lessondata) {
                    $skilldataobject = new stdClass();
                    $skilldataobject->meltassessmentuserid = $insert;
                    $skilldataobject->skillid = $skill;
                    $skilldataobject->marketid = $lessondata;
                    $skilldataobject->lessonnumber = $key;
                    $skilldataobject->timemodified = time();
                    $userskill = $DB->insert_record('meltassessment_user_skill', $skilldataobject);
                }
            }

            for ($lesson = 1; $lesson <= $meltassessment->nolesson; $lesson++) {
                $lessonno = 0;
                $count = 0;
                foreach (array_keys($skillid) as $skill) {
                    $lessondatas = $lessonskill[$skill];
                    if (!empty($lessondatas[$lesson])) {
                        $lessonnew = $DB->get_field('meltassessment_market', 'mnumber', ['id' => $lessondatas[$lesson]]);
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
                $lessondata->meltassessmentuserid = $insert;
                $lessondata->lesson = $lesson;
                $lessondata->average = $newaverage;
                $lessondata->time = time();
                $userlesson = $DB->insert_record('meltassessment_user_lesson', $lessondata);
            }

            foreach ($field as $fielddata) {
                $fielddata = $DB->get_record('meltassessment_field', ['id' => $fielddata]);
                $fielddataid = $fielddata->id;
                if ($fielddata->fieldvalue == constants::GROUPS[constants::FILLABLE] ||
                        $fielddata->fieldvalue == constants::GROUPS[constants::INSTUCTNAME]) {
                    $fileddataobject = new stdClass();
                    $fileddataobject->meltassessmentuserid = $insert;
                    $fileddataobject->fieldid = $fielddataid;
                    $fileddataobject->fieldvalue = $fieldname[$fielddataid];
                    $fileddataobject->timemodified = time();
                    $userfield = $DB->insert_record('meltassessment_field_info', $fileddataobject);
                }
            }
            redirect($redirecturl);
        }
    }
}


echo $OUTPUT->header();
echo $OUTPUT->render($meltassessmentform);
echo $OUTPUT->footer();