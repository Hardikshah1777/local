<?php
require_once(dirname(__FILE__).'/../../config.php');
require_once($CFG->dirroot . '/course/renderer.php');

global $DB,$USER,$CFG;

require_login($SITE);
 
$contextid = required_param('contextid', PARAM_INT);

list ($context, $course, $currentcm) = get_context_info_array($contextid);
	
	$return = array();
	$return['status']="completed";
	$prevmodid =0;
    $sql ="SELECT * FROM {course_sections} WHERE course=".$course->id ."				
					ORDER BY section ASC ";
						
	$coursesections = $DB->get_records_sql($sql);
	
	if($coursesections){
		$sequences = '';
		foreach($coursesections as $coursesection){
			if(!empty($coursesection->sequence)){
				$sequences .= $coursesection->sequence.',';
				
				
			}
		}
		$sequences = trim($sequences,',');
		$sequence = explode(',',$sequences);
		
		$sequence = array_reverse($sequence, false) ;
		
		foreach($sequence as $key => $value){
			$sql = "select m.name from {course_modules} cm 
						join {modules} m on m.id=cm.module
						where cm.id=".$value;
			$activitymod = $DB->get_record_sql($sql);
			list ($course, $cm) = get_course_and_cm_from_cmid($value, $activitymod->name);

			if(empty($cm->availableinfo)){
				if (!$cm->uservisible) {
						continue;
				}
				else{
					$lastmodid = $value;
					break;
				}
			}
			else if(!empty($cm->availableinfo)) {
				$lastmodid = $value;
				break;
			}
			else{
				$lastmodid = $value;
				break;
			}
			

		}
    if (isset($sequence[$key + 1])) {
			$prevmodid = $sequence[$key+1];
    }

	}
	//check active time
	$currentlogin =$USER->currentlogin;
	$beginOfDay = strtotime("midnight", time());//for testing
	$sql ="SELECT SUM(timemodified-timecreated) as active FROM {timetracker_log} 
				where userid=".$USER->id." AND courseid=".$course->id;
	$total = $DB->get_record_sql($sql);
	$hours = floor($total->active / 3600);
	
if ($lastmodid == $currentcm->id) {
		if($hours < 20){
			$return['status']="inprogress";
			$return['hours']=$hours;
			if($prevmodid >0){
				$sql = "select m.name from {course_modules} cm 
							join {modules} m on m.id=cm.module
							where cm.id=".$prevmodid;
				$prevmod = $DB->get_record_sql($sql);
				$return['returnurl']=$CFG->wwwroot."/mod/".$prevmod->name."/view.php?id=".$prevmodid;
        } else {
				$return['returnurl']=$CFG->wwwroot."/course/view.php?id=".$course->id;
		}
	}
}

    // Output status
 echo json_encode($return);
