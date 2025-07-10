<?php
require_once(dirname(__FILE__).'/../../config.php');
require_once('lib.php');
global $DB,$USER;
$contextid = required_param('contextid', PARAM_INT);

require_login($SITE);

$page = optional_param('page', '', PARAM_RAW);
$status = optional_param('status', '', PARAM_RAW);
list ($context, $course, $currentcm) = get_context_info_array($contextid);
if($status == 'logout')
	$reason = 'idletime';
if($status == 'proceed') {
	$reason = 'proceed';
	$record = new stdClass();
	$record->userid = $USER->id;
		
    if (empty($currentcm)) {
		$record->eventname ='\core\event\course_viewed' ;
		$record->component ='core' ;
		$record->action ='viewed' ;
        $record->target = 'course';

    } else {

        $record->eventname = "\\mod_" . $currentcm->modname . "\\event\course_module_viewed";
        $record->component = "mod_" . $currentcm->modname;
		$record->action ='viewed' ;
		$record->target = 'course_module';

	}

	$record->courseid = $course->id;
	$record->contextid = $context->id;
	$record->contextlevel = $context->contextlevel;
	$record->contextinstanceid = $context->instanceid;
	$record->timecreated = time();

    /* AND
                eventname = '$record->eventname' AND component = '$record->component' AND
                action = '$record->action' AND contextinstanceid = $record->contextinstanceid AND
                courseid = $record->courseid AND contextid = $record->contextid AND
                contextlevel = $record->contextlevel AND target = '$record->target'
     *
     * */
	$sql ="SELECT * FROM {timetracker_log} WHERE userid=".$USER->id ."
						AND timemodified > ".$USER->currentlogin ."
					ORDER BY timecreated DESC Limit 0,1";

	$lastlog = $DB->get_record_sql($sql);
	if($lastlog){

		if($lastlog->eventname == $record->eventname && $lastlog->component == $record->component &&
			$lastlog->action == $record->action &&	$lastlog->contextinstanceid == $record->contextinstanceid &&
			$lastlog->courseid == $record->courseid && $lastlog->contextid == $record->contextid &&
			$lastlog->contextlevel == $record->contextlevel && $lastlog->target == $record->target ){

			 $sql ="SELECT * FROM {logstore_standard_log} WHERE userid=".$USER->id ."
			 AND action IN ('loggedout','loggedin') AND timecreated > ".$lastlog->timemodified;

			$newlogin = $DB->get_records_sql($sql);

			if($newlogin){
				$record->timemodified = time();
				$DB->insert_record('timetracker_log', $record);
            } else {
				$DB->update_record('timetracker_log',array('id'=>$lastlog->id,'timemodified'=>time()));
		}
        } else {
			$DB->update_record('timetracker_log',array('id'=>$lastlog->id,'timemodified'=>time()));
			$record->timemodified = time();
			$DB->insert_record('timetracker_log', $record);

		}
	}
	else{
		$record->timemodified = time();
		$DB->insert_record('timetracker_log', $record);
	}

	
	//check active time	
	$beginOfDay = strtotime("midnight", time());
    $endOfDay = strtotime("tomorrow", $beginOfDay) - 1;  

	$sql ="SELECT SUM(timemodified-timecreated) as active FROM {timetracker_log} 
	where userid=".$USER->id ." AND timecreated > $beginOfDay";
	$total = $DB->get_record_sql($sql);
	$hours = floor($total->active / 3600);
	$daybreak = (get_config('local_timetracker', 'daybreak')) ? get_config('local_timetracker', 'daybreak') : 3;
 	$coursebreak = (get_config('local_timetracker', 'coursebreak')) ? get_config('local_timetracker', 'coursebreak') : 2;

    $daybreak = 9999999999;
    $coursebreak = 9999999999;

	if(0 && $hours == $coursebreak ){
		$sql ="SELECT * FROM {timetracker_coursebreak} WHERE userid=".$USER->id ."
					AND timecreated > ".$beginOfDay;

		$coursebreak = $DB->get_record_sql($sql);
		if($coursebreak){
			if($coursebreak->status ==0 )
				$break = false;
			else
				$break = true;
			
		}
		else{
			$break = true;
			$record = new stdClass();
			$record->userid = $USER->id;
			$record->courseid = 1;
			$record->status = 1;
			$record->timecreated = time();
			$record->timemodified = time();
			$DB->insert_record('timetracker_coursebreak', $record);
		}
		if($break) {
			$status = 'logout';
			$reason = 'smallbreak';
		}
	}
	else if(0 && $hours == $daybreak  ){
		$status = 'logout';
		$reason = 'coursebreak';
	}
	if(0 && time() == $endOfDay ){
		$status = 'logout';
		$reason = 'endofday';
	}
	
    if ($reason == 'proceed') {
        $alreadypassed = local_timetracker_isalreadypassed($contextid);
        if (!$alreadypassed) {
            $reason = 'askquestion';
        }
    }
}

if($status == 'logout')
    require_logout();
	
// Output status
echo $reason;die;


