<?php

require_once(dirname(__FILE__).'/../../config.php');
require_once('lib.php');
$contextid = required_param('contextid', PARAM_INT);
$askquestion = optional_param('askquestion', '', PARAM_RAW);
require_login($SITE);

$referer = get_local_referer(false);
if (!empty($referer) && (
            strpos($referer, $CFG->wwwroot . '/login/?') !== false ||
            strpos($referer, $CFG->wwwroot . '/login/index.php') !== false)) { // There might be some extra params such as ?lang=.

	$SESSION->wantsurl = $CFG->wwwroot;
	redirect($SESSION->wantsurl);

}

global $DB,$USER;
$questions=array();
if(!is_siteadmin($USER->id)){
    $alreadypassed = local_timetracker_isalreadypassed($contextid);
    if ($askquestion == 0 && !$alreadypassed) {
		$lastlogin =$USER->lastlogin;
		$currentlogin =$USER->currentlogin;
		if(empty($currentlogin))
		   redirect($CFG->wwwroot .'/my');
		$sql = "select * from {timetracker_securitycheck} where  userstatus=1 AND userid=".$USER->id." 
						AND timecreated < $currentlogin";
		$tracks = $DB->get_records_sql($sql);
		if($tracks){
			foreach($tracks as $track){
				$DB->update_record('timetracker_securitycheck ',array('id'=>$track->id,'userstatus'=>0));//offline status
			}
		}

		$time = time();
		/*$sql = "select * from {timetracker_securitycheck} where
							answerstatus>0 AND userstatus=1 AND userid=".$USER->id." ORDER BY timecreated DESC LIMIT 0,1";
		$wrongattempt= $DB->get_record_sql($sql);
		if(!empty($wrongattempt)){
			$askquestion =1;
		}
		else{*/
			$askquestion =1;//ask first question
			$currenttracks = $DB->get_records('timetracker_securitycheck',array('userid'=>$USER->id,'userstatus'=>1,'answerstatus'=>0));
			$sql = "SELECT * FROM {timetracker_securitycheck} 
					WHERE userid=".$USER->id." AND userstatus=1 AND answerstatus=0 
					ORDER BY timecreated DESC LIMIT 0,1";
			$currenttrack= $DB->get_record_sql($sql);
			if($currenttracks){
				$numtracks = count($currenttracks);
				$online = $time - $currenttrack->timecreated;//in sec
				$hours = floor($online / 900);
				if ($hours == $numtracks )
					$askquestion =1;
				else
					$askquestion =0;
			}
        /*}*/
	}

	$category = $DB->get_record('user_info_category',array('name'=>'Security Questions'));

	if($category){
		$categoryid= $category->id;
		$fields = $DB->get_records('user_info_field',array('categoryid'=>$categoryid));
		if($fields){
			foreach($fields as $field){
				$data = $DB->get_record('user_info_data',array('userid'=>$USER->id,'fieldid'=>$field->id));
				if(!$data){
					$askquestion =0;
					break;
				}
			}
		}
	}

    if (!$alreadypassed) {
        $askquestion = 1;
    }

	if($category && $askquestion == 1){
		if($fields){
			foreach($fields as $field){
				$keys[$field->id] =$field->name;
			}
			if(!empty($wrongattempt)){
				$key = $wrongattempt->qid;
				$questions[$key]=$keys[$key];
			}
			else{
				$key = array_rand($keys);
				$questions[$key]=$keys[$key];
			}
		}

	}



}
 echo json_encode($questions);
