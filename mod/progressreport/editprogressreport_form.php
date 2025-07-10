<?php

use mod_progressreport\constants;
use mod_progressreport\output\field;
use mod_progressreport\output\market;
use mod_progressreport\output\progressreport_form;
use mod_progressreport\output\section;
use mod_progressreport\output\skill;

define('SAVE', 1);
define('REQUIRED',1);
include_once('../../config.php');
require_once($CFG->dirroot . '/mod/progressreport/locallib.php');

$id = optional_param('id', 0, PARAM_INT); // Course Module ID
$userid = optional_param('userid', 0, PARAM_INT);
$progressreportid = optional_param('progressreportid', 0, PARAM_INT);
$sesskey = optional_param('sesskey', 'reset', PARAM_RAW);
$redirecturl = new moodle_url('/mod/progressreport/view.php', ['id' => $id]);

$progressreportuserid = optional_param('progressuserid',0,PARAM_INT);

if ($id) {
    if (!$cm = get_coursemodule_from_id('progressreport', $id)) {
        print_error('invalidcoursemodule');
    }

    $progressreport = $DB->get_record('progressreport', array('id' => $cm->instance), '*', MUST_EXIST);
}

$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);

$url = new moodle_url('/mod/progressreport/editprogressreport_form.php', array('id' => $cm->id));
$PAGE->set_url($url);
$PAGE->set_title($course->shortname . ': ' . $progressreport->name);
$PAGE->set_heading($course->fullname);
$PAGE->set_activity_record($progressreport);

$userdata = core_user::get_user($userid);
$progressreportuser = $DB->get_record('progressreport_user',['id' => $progressreportuserid]);
$progressreportform = new progressreport_form();
$progressreportform->progressreportuserid = $progressreportuserid;

$progressreportform->notes = $progressreportuser->notes;
$progressreportform->save = $progressreportuser->save;
$progressreport = $DB->get_record('progressreport', ['id' => $progressreportid]);
$progressreportnuserinfo = $DB->get_records('progressreport_field', ['progressreportid' => $progressreportid]);

$progressreportform->userid = $userdata->id;
$progressreportform->moduleid = $id;
$progressreportform->progressreportid = $progressreport->id;
$progressreportform->progressreportname = $progressreport->name;
$progressreportform->progressreporturl = new moodle_url('/mod/progressreport/editprogressreport_form.php',
        ['userid' => $userid, 'progressreportid' => $progressreportid, 'id' => $id,'progressuserid' => $progressreportuserid]);
$progressreportform->lesson = $progressreportuser->nolesson;

foreach ($progressreportnuserinfo as $userinfo) {
    $userfielddata = new field($userinfo->field, $userinfo->fieldvalue, $userid);
    $userfielddata->id = $userinfo->id;
    $userfielddata->progressreportid = $progressreportuserid;
    $userfielddata->attemptuser = $progressreportuser->save;
    $progressreportform->add_field($userfielddata);
}

$progressreportmarkets = get_progressreport_market($progressreportid);
$i = 0;
foreach ($progressreportmarkets as $progressreportmarket) {
    $i++;
    $market = new market($progressreportmarket->id, $progressreportmarket->name);
    $progressreportform->add_market($market);
    $market->number = $i;
}

$averages = $DB->get_records('progressreport_user_lesson',['progressreportuserid' => $progressreportuserid]);
$progressreportform->average = $averages;


$progressreportsections = get_progressreport_section($progressreportid);
foreach ($progressreportsections as $progressreportsec) {
    if (empty($progressreportsec->deleted) && empty($progressreportsec->visible)) {
        $section = new section($progressreportsec->id, $progressreportsec->name);
        $progressreportform->add_section($section);
        $skills = get_progressreport_sections_skill($progressreportsec->id);
        foreach ($skills as $skill) {
            if (empty($skill->deleted) && empty($skill->visible)) {
                $skillnew = new skill($skill->id, $skill->name);
                $section->add_skill($skillnew);
                $skillnew->nolesson = $progressreportuser->nolesson;
                $skillnew->progressreportuserid = $progressreportuser->id;
                $skillnew->validation($skill->validation);
            }
        }
    }
}

$progressreportdatas = $DB->get_records('progressreport_user_skill', ['progressreportuserid'=>$progressreportuser->id],'','*');
$progressreportform->set_progressreportdata($progressreportdatas);




if (confirm_sesskey($sesskey)) {

    $skillid = optional_param_array('skillid', [], PARAM_TEXT);
    $field = optional_param_array('userfieldid', [], PARAM_TEXT);
    $fieldname = optional_param_array('name', [], PARAM_TEXT);
    $notes = optional_param('notes', null, PARAM_TEXT);
    $lessonskill = $_POST['market'];
    $editprogressreportid = optional_param('progressrepotid','0',PARAM_INT);
    $editprogressreportuserid = optional_param('progressreportuserid','0',PARAM_INT);
    $confirm = optional_param('confirmindexes',0,PARAM_INT);
    $progressreportform->postnote = $_POST['notes'];

    if(!empty($confirm)){
        $errors = [];
        foreach (array_keys($skillid) as $skillnewid){
            $validation = get_validation_type($skillnewid);
            if($validation == REQUIRED && in_array(0,$lessonskill[$skillnewid])){
                $errors[$skillnewid] = true;
            }
        }

        $progressreportform->set_errors($errors);
        $progressreportform->setPostdata($lessonskill);
    }
    if(empty($errors)) {
        $data = new stdClass();
        $data->id = $editprogressreportuserid;
        $data->notes = $notes;
        $data->confirm = $confirm;
        $data->timemodified = time();
        $update = $DB->update_record('progressreport_user', $data);

        if ($update) {
            $progressreportuserdatas =
                    $DB->get_records('progressreport_user_skill', ['progressreportuserid' => $editprogressreportuserid]);
            foreach ($progressreportuserdatas as $progressreportdata) {
                if (!empty($lessonskill[$progressreportdata->skillid])) {
                    $updatedata = new stdClass();
                    $updatedata->id = $progressreportdata->id;
                    $updatedata->marketid = $lessonskill[$progressreportdata->skillid][$progressreportdata->lessonnumber];
                    $userskill = $DB->update_record('progressreport_user_skill', $updatedata);
                }
            }

            $fielddatas = $DB->get_records('progressreport_field_info', ['progressreportuserid' => $editprogressreportuserid]);
            foreach ($fielddatas as $item) {
                $fieldobject = new stdClass();
                $fieldobject->id = $item->id;
                $fieldobject->fieldvalue = $fieldname[$item->fieldid];
                $updaterec = $DB->update_record('progressreport_field_info', $fieldobject);
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
                $id = $DB->get_field('progressreport_user_lesson', 'id',
                        ['progressreportuserid' => $editprogressreportuserid, 'lesson' => $lesson]);

                $lessondata = new stdClass();
                $lessondata->id = $id;
                $lessondata->lesson = $lesson;
                $lessondata->average = $newaverage;
                $lessondata->time = time();
                $userlesson = $DB->update_record('progressreport_user_lesson', $lessondata);
            }
        }
        redirect($redirecturl);
    }
}

echo $OUTPUT->header();
echo $OUTPUT->render($progressreportform);
echo $OUTPUT->footer();


