<?php

use mod_meltassessment\constants;
use mod_meltassessment\output\field;
use mod_meltassessment\output\market;
use mod_meltassessment\output\meltassessment_form;
use mod_meltassessment\output\section;
use mod_meltassessment\output\skill;

define('SAVE', 1);
define('REQUIRED',1);
include_once('../../config.php');
require_once($CFG->dirroot . '/mod/meltassessment/locallib.php');

$id = optional_param('id', 0, PARAM_INT); // Course Module ID
$userid = optional_param('userid', 0, PARAM_INT);
$meltassessmentid = optional_param('meltassessmentid', 0, PARAM_INT);
$sesskey = optional_param('sesskey', 'reset', PARAM_RAW);
$redirecturl = new moodle_url('/mod/meltassessment/view.php', ['id' => $id]);

$meltassessmentuserid = optional_param('meltassessmentuserid',0,PARAM_INT);

if ($id) {
    if (!$cm = get_coursemodule_from_id('meltassessment', $id)) {
        print_error('invalidcoursemodule');
    }

    $meltassessment = $DB->get_record('meltassessment', array('id' => $cm->instance), '*', MUST_EXIST);
}

$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);

$url = new moodle_url('/mod/meltassessment/editmeltassessment_form.php', array('id' => $cm->id));
$PAGE->set_url($url);
$PAGE->set_title($course->shortname . ': ' . $meltassessment->name);
$PAGE->set_heading($course->fullname);
$PAGE->set_activity_record($meltassessment);

$userdata = core_user::get_user($userid);
$meltassessmentuser = $DB->get_record('meltassessment_user',['id' => $meltassessmentuserid]);
$meltassessmentform = new meltassessment_form();
$meltassessmentform->meltassessmentuserid = $meltassessmentuserid;

$meltassessmentform->notes = $meltassessmentuser->notes;
$meltassessmentform->save = $meltassessmentuser->save;
$meltassessment = $DB->get_record('meltassessment', ['id' => $meltassessmentid]);
$meltassessmentnuserinfo = $DB->get_records('meltassessment_field', ['meltassessmentid' => $meltassessmentid]);

$meltassessmentform->userid = $userdata->id;
$meltassessmentform->moduleid = $id;
$meltassessmentform->meltassessmentid = $meltassessment->id;
$meltassessmentform->meltassessmentname = $meltassessment->name;
$meltassessmentform->meltassessmenturl = new moodle_url('/mod/meltassessment/editmeltassessment_form.php',
        ['userid' => $userid, 'meltassessmentid' => $meltassessmentid, 'id' => $id,'meltassessmentuserid' => $meltassessmentuserid]);
$meltassessmentform->lesson = $meltassessmentuser->nolesson;

foreach ($meltassessmentnuserinfo as $userinfo) {
    $userfielddata = new field($userinfo->field, $userinfo->fieldvalue, $userid);
    $userfielddata->id = $userinfo->id;
    $userfielddata->meltassessmentid = $meltassessmentuserid;
    $userfielddata->attemptuser = $meltassessmentuser->save;
    $meltassessmentform->add_field($userfielddata);
}

$meltassessmentmarkets = get_meltassessment_market($meltassessmentid);
$i = 0;
foreach ($meltassessmentmarkets as $meltassessmentmarket) {
    $i++;
    $market = new market($meltassessmentmarket->id, $meltassessmentmarket->name);
    $meltassessmentform->add_market($market);
    $market->number = $i;
}

$averages = $DB->get_records('meltassessment_user_lesson',['meltassessmentuserid' => $meltassessmentuserid]);
$meltassessmentform->average = $averages;


$meltassessmentsections = get_meltassessment_section($meltassessmentid);
foreach ($meltassessmentsections as $meltassessmentsec) {
    if (empty($meltassessmentsec->deleted) && empty($meltassessmentsec->visible)) {
        $section = new section($meltassessmentsec->id, $meltassessmentsec->name);
        $meltassessmentform->add_section($section);
        $skills = get_meltassessment_sections_skill($meltassessmentsec->id);
        foreach ($skills as $skill) {
            if (empty($skill->deleted) && empty($skill->visible)) {
                $skillnew = new skill($skill->id, $skill->name);
                $section->add_skill($skillnew);
                $skillnew->nolesson = $meltassessmentuser->nolesson;
                $skillnew->meltassessmentuserid = $meltassessmentuser->id;
                $skillnew->validation($skill->validation);
            }
        }
    }
}

$meltassessmentdatas = $DB->get_records('meltassessment_user_skill', ['meltassessmentuserid'=>$meltassessmentuser->id],'','*');
$meltassessmentform->set_meltassessmentdata($meltassessmentdatas);




if (confirm_sesskey($sesskey)) {

    $skillid = optional_param_array('skillid', [], PARAM_TEXT);
    $field = optional_param_array('userfieldid', [], PARAM_TEXT);
    $fieldname = optional_param_array('name', [], PARAM_TEXT);
    $notes = optional_param('notes', null, PARAM_TEXT);
    $lessonskill = $_POST['market'];
    $editmeltassessmentid = optional_param('meltassessmentid','0',PARAM_INT);
    $editmeltassessmentuserid = optional_param('meltassessmentuserid','0',PARAM_INT);
    $confirm = optional_param('confirmindexes',0,PARAM_INT);
    $meltassessmentform->postnote = $_POST['notes'];

    if(!empty($confirm)){
        $errors = [];
        foreach (array_keys($skillid) as $skillnewid){
            $validation = get_validation_type($skillnewid);
            if($validation == REQUIRED && in_array(0,$lessonskill[$skillnewid])){
                $errors[$skillnewid] = true;
            }
        }

        $meltassessmentform->set_errors($errors);
        $meltassessmentform->setPostdata($lessonskill);
    }
    if(empty($errors)) {
        $data = new stdClass();
        $data->id = $editmeltassessmentuserid;
        $data->notes = $notes;
        $data->confirm = $confirm;
        $data->timemodified = time();
        $update = $DB->update_record('meltassessment_user', $data);

        if ($update) {
            $meltassessmentuserdatas =
                    $DB->get_records('meltassessment_user_skill', ['meltassessmentuserid' => $editmeltassessmentuserid]);
            foreach ($meltassessmentuserdatas as $meltassessmentdata) {
                if (!empty($lessonskill[$meltassessmentdata->skillid])) {
                    $updatedata = new stdClass();
                    $updatedata->id = $meltassessmentdata->id;
                    $updatedata->marketid = $lessonskill[$meltassessmentdata->skillid][$meltassessmentdata->lessonnumber];
                    $userskill = $DB->update_record('meltassessment_user_skill', $updatedata);
                }
            }

            $fielddatas = $DB->get_records('meltassessment_field_info', ['meltassessmentuserid' => $editmeltassessmentuserid]);
            foreach ($fielddatas as $item) {
                $fieldobject = new stdClass();
                $fieldobject->id = $item->id;
                $fieldobject->fieldvalue = $fieldname[$item->fieldid];
                $updaterec = $DB->update_record('meltassessment_field_info', $fieldobject);
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
                $id = $DB->get_field('meltassessment_user_lesson', 'id',
                        ['meltassessmentuserid' => $editmeltassessmentuserid, 'lesson' => $lesson]);

                $lessondata = new stdClass();
                $lessondata->id = $id;
                $lessondata->lesson = $lesson;
                $lessondata->average = $newaverage;
                $lessondata->time = time();
                $userlesson = $DB->update_record('meltassessment_user_lesson', $lessondata);
            }
        }
        redirect($redirecturl);
    }
}

echo $OUTPUT->header();
echo $OUTPUT->render($meltassessmentform);
echo $OUTPUT->footer();


