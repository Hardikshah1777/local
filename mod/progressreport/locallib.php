<?php


use mod_progressreport\constants;

defined('MOODLE_INTERNAL') || die();


// $evaluationid = evaluation id
function get_progressreport_market($progressreportid){
    global $DB;

    $progressreportmarket = $DB->get_records('progressreport_market',['progressreportid'=>$progressreportid]);

    return $progressreportmarket;
}

function get_progressreport_section($progressreportid){
    global $DB;

    $progressreportsecrions = $DB->get_records('progressreport_section',['progressreportid'=>$progressreportid]);

    return $progressreportsecrions;
}

function get_progressreport_sections_skill($sectionid){
    global $DB;


    $progressreportskill = $DB->get_records('progressreport_skill',['sectionid' => $sectionid]);
    return $progressreportskill;
}

function userfield_data($field,$userid,$progressreportuserid,$attempt,$fieldid){
    global $DB,$USER;

    if($field == 'fullname'){
        $dataqry = $DB->get_record('user',['id'=>$userid],'firstname,lastname');
        $data = $dataqry->firstname.' '.$dataqry->lastname;
    }else if($field == constants::GROUPS[constants::FILLABLE]){
        if(empty($attempt)){
            $data = '';
        }else{
            $fileddata = $DB->get_record('progressreport_field_info',['progressreportuserid' => $progressreportuserid,'fieldid' => $fieldid]);
            $data = $fileddata->fieldvalue;
        }
    }else if($field == constants::GROUPS[constants::INSTUCTNAME]){
        if(empty($attempt)){
            $data = $USER->firstname.' '.$USER->lastname;
        }else{
            $fileddata = $DB->get_field('progressreport_field_info','fieldvalue',['progressreportuserid' => $progressreportuserid,'fieldid' => $fieldid]);
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
    $type = $DB->get_field('progressreport_skill','validation',['id' => $skillid]);
    return $type;
}

function delete_check_sections($sectionid,$progressreportuserid){
    global $DB;

    $skills = $DB->get_records('progressreport_skill', ['sectionid' => $sectionid]);
    if (!empty($skills)) {
        foreach ($skills as $skill) {
            $deleteuserdata = $DB->get_field('progressreport_user_skill', 'id',
                    ['skillid' => $skill->id, 'progressreportuserid' => $progressreportuserid]);
            $deleteuser = $deleteuserdata;
        }
        return $deleteuser;
    } else {
        $deleteuser = 0;
        return $deleteuser;
    }
}

function get_user_skill_progress($progressreportuserid,$skillid){
    global $DB;
    $levels = $DB->get_record('progressreport_user_skill',['progressreportuserid'=>$progressreportuserid,'skillid'=>$skillid]);
    return $levels;
}