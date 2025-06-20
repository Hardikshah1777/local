<?php
require_once(dirname(__FILE__).'/../../config.php');
require_once('lib.php');
global $DB,$USER;

$contextid = required_param('contextid', PARAM_INT);

require_login($SITE);
 
$qid = optional_param('qid', 0, PARAM_INT);
$answer = optional_param('answer', '', PARAM_RAW);

list ($context, $course, $currentcm) = get_context_info_array($contextid);

	$courseid=$course->id;
    $lastlogin =$USER->lastlogin;
    $currentlogin =$USER->currentlogin;
	$data = $DB->get_record('user_info_data',array('userid'=>$USER->id,'fieldid'=>$qid));
	$status = 'wrong';
	
	if(!empty($data) ){
		$record = new stdClass();
		$record->userid = $USER->id;
		$record->courseid = $courseid;
		$record->qid = $qid;
		$record->answer = $answer;
		$record->userstatus = 1;//online
		$record->timecreated = time();
		
		$attempts = 0;
		
		$sql = "select * from {timetracker_securitycheck} where  
							answerstatus>0 AND userstatus=1 AND userid=".$USER->id." ORDER BY timecreated DESC LIMIT 0,1";
		$qidattempt = $DB->get_record_sql($sql);
		
		if(strtolower($data->data) === strtolower($answer)){
			$record->answerstatus = $attempts;//correct
			$wrong =0;
		}
		else{
			$attempts = 1;
        if (!empty($qidattempt)) {
				$attempts =$qidattempt->answerstatus+1;
        }
			$record->answerstatus = $attempts;//wrong answer count
			$wrong =1;				
		}
		
		/*if($qidattempt){
			$record->id = $qidattempt->id;
			$DB->update_record('timetracker_securitycheck', $record);
		}
		else*/
			$DB->insert_record('timetracker_securitycheck', $record);
		
		if($attempts >= 3){
			
			$lockdata = $DB->get_record('timetracker_lock',array('userid'=>$USER->id));
			if($lockdata){
				$DB->update_record('timetracker_lock ',array('id'=>$lockdata->id,'userstatus'=>1,'timecreated' =>time()));
				$lockdataid = $lockdata->id;
			}
			else{
				$record = new stdClass();
				$record->userid = $USER->id;
				$record->courseid = 1;//site lock
				$record->userstatus = 1;//lock
				$record->timecreated = time();
				$lockdataid = $DB->insert_record('timetracker_lock', $record);
			}
			
			$sql = "select * from {timetracker_securitycheck} where  userstatus=1 AND userid=".$USER->id;
			$tracks = $DB->get_records_sql($sql);
			foreach($tracks as $track){
				$DB->update_record('timetracker_securitycheck ',array('id'=>$track->id,'userstatus'=>0));//offline status
				
			}
			$status = 'logout';
			unlock_notification($lockdataid);
        require_logout();
    } else if ($wrong == 1) {
			$status = 'wrong';
    } else {
			$status = 'proceed';
	 }
}

    // Output status
    echo $status;die;
