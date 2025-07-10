<?php


use mod_meltassessment\constants;

defined('MOODLE_INTERNAL') || die();


// $evaluationid = evaluation id
function get_meltassessment_market($meltassessmentid){
    global $DB;

    $meltassessmentmarket = $DB->get_records('meltassessment_market',['meltassessmentid'=>$meltassessmentid]);

    return $meltassessmentmarket;
}

function get_meltassessment_section($meltassessmentid){
    global $DB;

    $meltassessmentsecrions = $DB->get_records('meltassessment_section',['meltassessmentid'=>$meltassessmentid]);

    return $meltassessmentsecrions;
}

function get_meltassessment_sections_skill($sectionid){
    global $DB;


    $meltassessmentskill = $DB->get_records('meltassessment_skill',['sectionid' => $sectionid]);
    return $meltassessmentskill;
}

function userfield_data($field,$userid,$meltassessmentuserid,$attempt,$fieldid){
    global $DB,$USER;

    if($field == 'fullname'){
        $dataqry = $DB->get_record('user',['id'=>$userid],'firstname,lastname');
        $data = $dataqry->firstname.' '.$dataqry->lastname;
    }else if($field == constants::GROUPS[constants::FILLABLE]){
        if(empty($attempt)){
            $data = '';
        }else{
            $fileddata = $DB->get_record('meltassessment_field_info',['meltassessmentuserid' => $meltassessmentuserid,'fieldid' => $fieldid]);
            $data = $fileddata->fieldvalue;
        }
    }else if($field == constants::GROUPS[constants::INSTUCTNAME]){
        if(empty($attempt)){
            $data = $USER->firstname.' '.$USER->lastname;
        }else{
            $fileddata = $DB->get_field('meltassessment_field_info','fieldvalue',['meltassessmentuserid' => $meltassessmentuserid,'fieldid' => $fieldid]);
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
        } elseif ($fielddata == 'address'){
            $newdata = str_replace(':/', ' ', $data);
        } else {
            $newdata = $data;
        }
    }else{
        $newdata = '';
    }
    return $newdata;
}

function get_validation_type($skillid){
    global $DB;
    $type = $DB->get_field('meltassessment_skill','validation',['id' => $skillid]);
    return $type;
}

function delete_check_sections($sectionid,$meltassessmentuserid){
    global $DB;

    $skills = $DB->get_records('meltassessment_skill', ['sectionid' => $sectionid]);
    if (!empty($skills)) {
        foreach ($skills as $skill) {
            $deleteuserdata = $DB->get_field('meltassessment_user_skill', 'id',
                    ['skillid' => $skill->id, 'meltassessmentuserid' => $meltassessmentuserid]);
            $deleteuser = $deleteuserdata;
        }
        return $deleteuser;
    } else {
        $deleteuser = 0;
        return $deleteuser;
    }
}

function get_user_skill_meltassessment($meltassessmentuserid,$skillid){
    global $DB;
    $levels = $DB->get_record('meltassessment_user_skill',['meltassessmentuserid'=>$meltassessmentuserid,'skillid'=>$skillid]);
    return $levels;
}