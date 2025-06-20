<?php
require_once(dirname(__FILE__).'/../../config.php');

global $DB,$USER,$CFG;

require_login($SITE);

$status = 'unlocked';

//check active time	
$beginOfDay = strtotime("midnight", time());

$sql ="SELECT SUM(timemodified-timecreated) as active FROM {timetracker_log} 
where userid=".$USER->id ." AND timecreated > $beginOfDay";
$total = $DB->get_record_sql($sql);
$hours = floor($total->active / 3600);

$daybreak = (get_config('local_timetracker', 'daybreak')) ? get_config('local_timetracker', 'daybreak') : 3;
$coursebreak = (get_config('local_timetracker', 'coursebreak')) ? get_config('local_timetracker', 'coursebreak') : 2;
$daybreak = 9999999999;
$coursebreak = 9999999999;


if(0 && $hours == $daybreak ){
	$status = 'locked';
	//require_logout();
}
else if(0 && $hours == $coursebreak ){
	$sql ="SELECT * FROM {timetracker_coursebreak} WHERE userid=".$USER->id ."
			AND timecreated > ".$beginOfDay;
	$coursebreak = $DB->get_record_sql($sql);
	if($coursebreak){
		if($coursebreak->status == 1 ){
			$currentdifference = time() - $coursebreak->timecreated;
			$breakhours = floor($currentdifference / 3600);
			$minutes = floor(($currentdifference / 60) % 60);
			
			if($breakhours == 0 && $minutes < 11){
				$status = 'locked';
				//require_logout();
			}
			else{
				$DB->update_record('timetracker_coursebreak ',array('id'=>$coursebreak->id,'status'=>0, 'timemodified' => time()));

			}
		}
		
	}
	
				
	
}
//else{

	$time = time();

	$data = $DB->get_record('timetracker_lock',array('userid'=>$USER->id,'userstatus'=>1));
	if($data){
		$timediff = $time - $data->timecreated;
		$locktime = floor($timediff/(60*60));
		if($locktime < 24){
			$status = 'locked';
			//require_logout();
		}
		else{
			$DB->update_record('timetracker_lock ',array('id'=>$data->id,'userstatus'=>0));
		}

	}
//}

if($status == 'locked') {
    require_logout();
}

//if($status == 'unlocked'){
	redirect($CFG->wwwroot);
//}


// Output status
echo $status;die;


