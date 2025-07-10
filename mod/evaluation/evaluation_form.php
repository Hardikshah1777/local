<?php

use mod_evaluation\constants;
use mod_evaluation\output\evaluationform;
use mod_evaluation\output\level;
use mod_evaluation\output\section;
use mod_evaluation\output\skill;
use mod_evaluation\output\userfield ;

define('DEFAULTVAL',90);
define('PASSGRADE',80);
define('TRAINIGREQUIRED',1);
define('MAXIMUM',100);
define('REQUIRED',1);

require_once('../../config.php');
require_once ($CFG->dirroot.'/mod/evaluation/locallib.php');

$userid = optional_param('userid',0,PARAM_INT);
$evaluationid = optional_param('evaluationid',0,PARAM_INT);
$moduleid = optional_param('moduleid',0,PARAM_INT);
$sesskey = optional_param('sesskey','reset', PARAM_RAW);

$url = new moodle_url('/mod/evaluation/evaluation_form.php',['id'=>$userid,'evaluationid'=>$evaluationid,'moduleid'=>$moduleid]);
$redirecturl = new moodle_url('/mod/evaluation/view.php',['id'=>$moduleid]);
if($moduleid) {
    if (!$cm = get_coursemodule_from_id('evaluation', $moduleid)) {
        print_error('invalidcoursemodule');
    }
    $course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);
    require_course_login($course, true, $cm);
    $context = context_module::instance($cm->id);
}


$PAGE->set_url($url);
$PAGE->set_title(get_string('pluginname','mod_evaluation'));
$PAGE->set_heading(get_string('pluginname','mod_evaluation'));
require_login();

echo $OUTPUT->header();

$userdata = core_user::get_user($userid);

$evaluationform = new evaluationform();
$evaluation = $DB->get_record('evaluation',['id'=>$evaluationid]);
$evaluationuserinfo = $DB->get_records('evaluation_userinfo',['evaluationid'=>$evaluationid]);

foreach ($evaluationuserinfo as $userinfo){
    $userfielddata = new userfield($userinfo->infofiled,$userinfo->infovalue,$userid);
    $userfielddata->id = $userinfo->id;
    $userfielddata->evaluationid = $evaluationid;
    $userfielddata->attemptuser = 0;
    $evaluationform->add_userfield($userfielddata);
}

$evaluationlevels = get_evaluation_level($evaluationid);
foreach ($evaluationlevels as $evaluationlevel){
    $level = new level($evaluationlevel->id,$evaluationlevel->name,$evaluationlevel->status,$evaluationlevel->grade);
    $evaluationform->add_level($level);
}


$evaluationform->userid = $userdata->id;
$evaluationform->moduleid = $USER->moduleid;
$evaluationform->evaluationname = $evaluation->name;
$evaluationform->evaluationid = $evaluation->id;
$evaluationform->evaluationurl = new moodle_url('/mod/evaluation/evaluation_form.php',['userid'=>$userid,'evaluationid'=>$evaluationid]);

$evaluationsection = get_evaluation_sections($evaluationid);
foreach ($evaluationsection as $evaluationsec){
    if(empty($evaluationsec->deleted) && empty($evaluationsec->visible)) {
        $section = new section($evaluationsec->id, $evaluationsec->name);
        $section->saferskill = $evaluationsec->saferskill;
        $evaluationform->add_section($section);
        $skills = get_evaluation_sections_skill($evaluationsec->id);
        foreach ($skills as $skill) {
            if (empty($skill->deleted) && empty($skill->visible)) {
                $skillnew = new skill($skill->id, $skill->name);
                $section->add_skill($skillnew);
                $skillnew->validation($skill->validation);
            }
        }
    }
}

if(confirm_sesskey($sesskey)){

    $comments = optional_param('comments',null,PARAM_TEXT);
    $postlevel = optional_param_array('level',[],PARAM_INT);
    $skillcomment = optional_param_array('skillcomment',[],PARAM_TEXT);
    $skills = $evaluationform->skillids($OUTPUT);
    $postlevel['comments'] = $_POST['comments'];
    $userfields = $_POST['userfieldid'];
    $nameuserfields = $_POST['name'];

    $sql = "SELECT MAX(attempt) as attempt FROM {evaluation_user} WHERE userid = :userid AND evaluationid = :evaluationid";
    $attemptrec = $DB->get_record_sql($sql,array('userid' => $userid,'evaluationid' => $evaluationid));
    if(empty($attemptrec->attempt)){
        $attempt = 1;
    }else{
        $attempt = $attemptrec->attempt + 1;
    }

    $errors = [];
    foreach (array_keys($skills) as $skillid){
        $validation = get_validation_type($skillid);
        if($validation == REQUIRED && empty($postlevel[$skillid])){
            $errors[$skillid] = true;
        }
    }
    $evaluationform->set_errors($errors);
    $evaluationform->set_evaluationdata($postlevel);

    if(empty($errors)) {
        $data = new stdClass();
        $data->userid = $userid;
        $data->evaluationid = $evaluationid;
        $data->comments = $comments;
        $data->timemodified = time();
        $data->attempt = $attempt;
        $insert = $DB->insert_record('evaluation_user', $data);
        if ($insert) {
            $total = DEFAULTVAL;
            foreach (array_keys($skills) as $skillid) {
                $dataobject = new stdClass();
                $dataobject->skillid = $skillid;
                $dataobject->levelid = $postlevel[$skillid];
                $dataobject->evaluationuserid = $insert;
                $dataobject->comment = $skillcomment[$skillid];
                $dataobject->timemodified = time();
                $newrec = $DB->insert_record('evaluation_user_skill_level', $dataobject);

                foreach ($evaluationform->get_levels() as $level) {
                    if(!empty($dataobject->levelid)){
                        if ($dataobject->levelid == $level->id) {
                            $value = $level->grade;
                        }
                    }else{
                        $value = 0;
                    }
                }
                $total += $value;
            }
            foreach ($evaluationlevels as $evaluationlevel) {
                if (strtolower($evaluationlevel->name) == 'urg') {
                    $urgid = $evaluationlevel->id;
                }
            }

            if (!in_array($urgid, $postlevel)) {
                $datat = new stdClass();
                    if ($total >= 100) {
                        $datat->grade = MAXIMUM;
                    } else {
                        $datat->grade = $total;
                    }
                if ($datat->grade >= PASSGRADE) {
                    $datat->pass = get_string('pass', 'mod_evaluation');
                } else {
                    $datat->pass = get_string('fail', 'mod_evaluation');
                    $datat->additionaltraining = TRAINIGREQUIRED;
                }
                $datat->id = $insert;
                $update = $DB->update_record('evaluation_user', $datat);
            } else {
                $dataobjectupdate = new stdClass();
                if ($total >= 100) {
                    $dataobjectupdate->grade = MAXIMUM;
                } else {
                    $dataobjectupdate->grade = $total;
                }
                $dataobjectupdate->id = $insert;
                $dataobjectupdate->pass = get_string('fail', 'mod_evaluation');
                $dataobjectupdate->additionaltraining = TRAINIGREQUIRED;
                $update = $DB->update_record('evaluation_user', $dataobjectupdate);
            }

            foreach ($userfields as $userfield){
                $fielddata = $DB->get_record('evaluation_userinfo',['id' => $userfield]);
                if($fielddata->infovalue == constants::GROUPS[constants::FILLABLE] || $fielddata->infovalue == constants::GROUPS[constants::INSTUCTNAME]) {
                    $fileddataobject = new stdClass();
                    $fileddataobject->evaluationuserid = $insert;
                    $fileddataobject->userfieldid = $userfield;
                    $fileddataobject->userfieldvalue  = $nameuserfields[$userfield];
                    $fileddataobject->timemodified  = time();
                    $update = $DB->insert_record('evaluation_userfields_info',$fileddataobject);
                }
            }

            redirect($redirecturl);
        }
    }
}

echo $OUTPUT->render($evaluationform);
echo $OUTPUT->footer();