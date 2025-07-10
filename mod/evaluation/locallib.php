<?php

use mod_evaluation\constants;

defined('MOODLE_INTERNAL') || die();


// $evaluationid = evaluation id
function get_evaluation_level($evaluationid){
    global $DB;

    $evaluationlevels = $DB->get_records('evaluation_level',['evaluationid'=>$evaluationid]);

    return $evaluationlevels;
}

function get_evaluation_sections($evaluationid){
    global $DB;

    $evaluationsections = $DB->get_records('evaluation_section',['evaluationid'=>$evaluationid]);

    return $evaluationsections;
}

function get_evaluation_sections_skill($sectionid){
    global $DB;


    $evaluationskills = $DB->get_records('evaluation_skill',['sectionid' => $sectionid]);
    return $evaluationskills;
}

function get_user_skill_level($evaluationuserid,$skillid){
    global $DB;
    $levels = $DB->get_record('evaluation_user_skill_level',['evaluationuserid'=>$evaluationuserid,'skillid'=>$skillid]);
    return $levels;
}

function get_validation_type($skillid){
    global $DB;
    $type = $DB->get_field('evaluation_skill','validation',['id' => $skillid]);
    return $type;
}

function delete_check_section($sectionid,$evaluationuserid){
    global $DB;

    $skills = $DB->get_records('evaluation_skill', ['sectionid' => $sectionid]);
    if (!empty($skills)) {
        foreach ($skills as $skill) {
            $deleteuserdata = $DB->get_field('evaluation_user_skill_level', 'id',
                    ['skillid' => $skill->id, 'evaluationuserid' => $evaluationuserid]);
            $deleteuser = $deleteuserdata;
        }
        return $deleteuser;
    } else {
        $deleteuser = 0;
        return $deleteuser;
    }
}

/**
 * Moves a section within a Evaluation, from a position to another.
 * */

function move_evaluation_section($evaluation,$section,$destination){
    global $DB;



    return $DB;
}


function userfield_data($field,$userid,$evaluationuserid,$attempt,$fieldid){
    global $DB,$USER;

    if($field == 'fullname'){
        $dataqry = $DB->get_record('user',['id'=>$userid],'firstname,lastname');
        $data = $dataqry->firstname.' '.$dataqry->lastname;
    }else if($field == constants::GROUPS[constants::FILLABLE]){
        if(empty($attempt)){
            $data = constants::GROUPS[constants::FILLABLE];
        }else{

            $fileddata = $DB->get_record('evaluation_userfields_info',['evaluationuserid' => $evaluationuserid,'userfieldid' => $fieldid]);
            $data = $fileddata->userfieldvalue;
        }
    }else if($field == constants::GROUPS[constants::INSTUCTNAME]){
        if(empty($attempt)){
            $data = $USER->firstname.' '.$USER->lastname;
        }else{
            $fileddata = $DB->get_field('evaluation_userfields_info','userfieldvalue',['evaluationuserid' => $evaluationuserid,'userfieldid' => $fieldid]);
            $data = $fileddata;
        }
    }else{
        $data = $DB->get_field('user',$field,['id'=>$userid]);

    }
    return $data;
}

function profile_data($field,$userid){
    global $DB;
    $data = $DB->get_field('user_info_data','data',['userid'=>$userid,'fieldid' => $field]);
    if(!empty($data)) {
        $fielddata = $DB->get_field('user_info_field', 'datatype', ['id' => $field]);
        if ($fielddata == 'datetime') {
            $newdata = gmdate('d M Y', $data);
        } else {
            $newdata = $data;
        }
    }else{
        $newdata = '';
    }
    return $newdata;
}