<?php

/// Library of functions and constants for module autoattendmod

defined('MOODLE_INTERNAL') || die;


/*
 function autoattendmod_supports($feature)
 function autoattendmod_add_instance($autoattendmod)
 function autoattendmod_update_instance($autoattendmod)
 function autoattendmod_delete_instance($id) 
 function autoattendmod_user_outline($course, $user, $mod, $autoattendmod) 
 function autoattendmod_user_complete($course, $user, $mod, $autoattendmod) 
 function autoattendmod_print_recent_activity($course, $isteacher, $timestart)
 function autoattendmod_cron()
 function autoattendmod_update_grades($autoattendmod, $userid=0, $nullifnone=true)
 function autoattendmod_grade_item_update($autoattendmod, $grades=NULL)
 function autoattendmod_grade_item_delete($autoattendmod)
*/


//
function autoattendmod_supports($feature)
{
    switch($feature) {
        case FEATURE_GROUPS:                  return false;
        case FEATURE_GROUPINGS:               return false;
        case FEATURE_GROUPMEMBERSONLY:        return false;
        case FEATURE_MOD_INTRO:               return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return false;
        case FEATURE_COMPLETION_HAS_RULES:    return false;
        case FEATURE_GRADE_HAS_GRADE:         return false;
        case FEATURE_GRADE_OUTCOMES:          return false;
        case FEATURE_BACKUP_MOODLE2:          return true;
        case FEATURE_SHOW_DESCRIPTION:        return true;
        //case FEATURE_MOD_PURPOSE:             return MOD_PURPOSE_ADMINISTRATION;

        default: return null;
    }
}


/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @global object
 * @param object $label
 * @return bool|int
 */
function autoattendmod_add_instance($autoattendmod)
{
    global $DB, $CFG;

    $courseURL = $CFG->wwwroot.'/course/view.php?id='.$autoattendmod->course;

    if (file_exists($CFG->dirroot.'/blocks/autoattend/jbxl/jbxl_moodle_tools.php')) {
        require_once($CFG->dirroot.'/blocks/autoattend/jbxl/jbxl_moodle_tools.php');
    }
    else {
        notice('<center>'.get_string('firstinstallblock', 'autoattendmod').'</center>', $courseURL);
    }
    require_once($CFG->dirroot.'/mod/autoattendmod/locallib.php');

    // check block
    $context = jbxl_get_course_context($autoattendmod->course);
    $ret = $DB->get_record('block_instances', array('blockname'=>'autoattend', 'parentcontextid'=>$context->id));
    if (!$ret) {
        notice('<center>'.get_string('firstinstanceblock', 'autoattendmod').'</center>', $courseURL);
    }

    $mod = autoattendmod_get_course_module($autoattendmod->course); 
    if ($mod) {
        notice('<center>'.get_string('onlyonemodule', 'autoattendmod').'</center>', $courseURL);
    }

    if (!property_exists($autoattendmod, 'emailenable'))   $autoattendmod->emailenable   = 0;
    if (!property_exists($autoattendmod, 'allreports'))    $autoattendmod->allreports    = 0;
    if (!property_exists($autoattendmod, 'emailkey'))      $autoattendmod->emailkey      = 0;
    if (!property_exists($autoattendmod, 'emailuser'))     $autoattendmod->emailuser     = 0;
    if (!property_exists($autoattendmod, 'summertime'))    $autoattendmod->summertime    = 0;
    if (!property_exists($autoattendmod, 'excelver'))      $autoattendmod->excelver      = 0;
    if (!property_exists($autoattendmod, 'dispinfo'))      $autoattendmod->dispinfo      = 0; 
    if (!property_exists($autoattendmod, 'homeroom'))      $autoattendmod->homeroom      = 0;
    if (!property_exists($autoattendmod, 'localhostname')) $autoattendmod->localhostname = 0;
    if (!property_exists($autoattendmod, 'feedback'))      $autoattendmod->feedback      = 0;
    if (!property_exists($autoattendmod, 'backupblock'))   $autoattendmod->backupblock   = 0; 
    //
    $autoattendmod->timemodified = time();

    $ret = $DB->insert_record('autoattendmod', $autoattendmod);
    if ($ret) {
        $autoattendmod->id = $ret;
        autoattendmod_grade_item_update($autoattendmod);
    }
    return $ret;
}


/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @global object
 * @param object $label
 * @return bool
 */
function autoattendmod_update_instance($autoattendmod)
{
    global $DB, $CFG;

    if (!property_exists($autoattendmod, 'emailenable'))   $autoattendmod->emailenable   = 0;
    if (!property_exists($autoattendmod, 'allreports'))    $autoattendmod->allreports    = 0;
    if (!property_exists($autoattendmod, 'emailkey'))      $autoattendmod->emailkey      = 0;
    if (!property_exists($autoattendmod, 'emailuser'))     $autoattendmod->emailuser     = 0;
    if (!property_exists($autoattendmod, 'summertime'))    $autoattendmod->summertime    = 0;
    if (!property_exists($autoattendmod, 'excelver'))      $autoattendmod->excelver      = 0;
    if (!property_exists($autoattendmod, 'dispinfo'))      $autoattendmod->dispinfo      = 0; 
    if (!property_exists($autoattendmod, 'homeroom'))      $autoattendmod->homeroom      = 0;
    if (!property_exists($autoattendmod, 'localhostname')) $autoattendmod->localhostname = 0;
    if (!property_exists($autoattendmod, 'feedback'))      $autoattendmod->feedback      = 0;
    if (!property_exists($autoattendmod, 'backupblock'))   $autoattendmod->backupblock   = 0; 
    //
    $autoattendmod->timemodified = time();

    $autoattendmod->id = $autoattendmod->instance;
    $ret = $DB->update_record('autoattendmod', $autoattendmod);
    if ($ret) {
        autoattendmod_grade_item_update($autoattendmod);
    }
    return $ret;
}


/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @global object
 * @param int $id
 * @return bool
 */
function autoattendmod_delete_instance($id) 
{
    global $DB;

    $autoattendmod = $DB->get_record('autoattendmod', array('id'=>$id));
    if (!$autoattendmod) {
        return false;
    }

    $result = true;
    $ret = $DB->delete_records('autoattendmod', array('id'=>$autoattendmod->id));
    if (!$ret) $result = false;

    if ($result) autoattendmod_grade_item_delete($autoattendmod);

    return $result;
}


/**
 * Return a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 * $ret->time = the time they did it
 * $ret->info = a short text description
 *
 * @return null
 * @TODO: implement this moodle function (if needed)
 **/
function autoattendmod_user_outline($course, $user, $mod, $autoattendmod) 
{
    global $CFG;

    if (file_exists($CFG->dirroot.'/blocks/autoattend/locallib.php')) {
        require_once($CFG->dirroot.'/blocks/autoattend/locallib.php');
    }
    else return false;
 
    $summary = autoattend_get_user_summary($user->id, $course->id);
    if (!$summary) return false;

    $maxtime = 0;
    foreach($summary['attitems'] as $att) {
        $maxtime = ($maxtime >= $att->calledtime ? $maxtime : $att->calledtime);
    }
        
    $ret = new stdClass();
    $ret->info = get_string('grade').': '.$summary['grade'].' / '.$summary['maxgrade'].' ('.$summary['percent'].'%)';
    $ret->time = $maxtime;
    return $ret;
}


/**
 * Print a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * @return boolean
 * @TODO: implement this moodle function (if needed)
 **/
function autoattendmod_user_complete($course, $user, $mod, $autoattendmod) 
{
    global $CFG;

    if (file_exists($CFG->dirroot.'/blocks/autoattend/locallib.php')) {
        require_once($CFG->dirroot.'/blocks/autoattend/locallib.php');
    }
    else return false;

    $context   = jbxl_get_course_context($course->id);
    $isstudent = jbxl_is_student($user->id, $context);
    if ($isstudent) autoattend_print_user($user, $course->id);

    return true;
}


/**
 * Given a course and a date, prints a summary of all the new
 * messages posted in the course since that date
 *
 * @param object $course
 * @param bool $viewfullnames capability
 * @param int $timestart
 * @return bool success
 */
function autoattendmod_print_recent_activity($course, $isteacher, $timestart)
{
    global $CFG;

    // True if anything was printed, otherwise false 
    return false;
}


/**
 * Function to be run periodically according to the moodle cron
 * This function searches for things that need to be done, such
 * as sending out mail, toggling flags etc ...
 *
 * @uses $CFG
 * @return boolean
 **/
function autoattendmod_cron()
{
    global $CFG, $DB;

    if (file_exists($CFG->dirroot.'/blocks/autoattend/locallib.php')) {
        require_once($CFG->dirroot.'/blocks/autoattend/locallib.php');
    }
    else return false;

    $autoattendmods = $DB->get_records('autoattendmod');
    if ($autoattendmods) {
        foreach ($autoattendmods as $autoattendmod) {
            $ret = autoattend_update_sessions($autoattendmod->course);
        }
    }

    return true;
}


//
function autoattendmod_update_grades($autoattendmod, $userid=0, $nullifnone=true)
{
    global $CFG;

    if (file_exists($CFG->dirroot.'/blocks/autoattend/locallib.php')) {
        require_once($CFG->dirroot.'/blocks/autoattend/locallib.php');
    }
    else return false;

    $context  = jbxl_get_course_context($autoattendmod->course);
    $students = jbxl_get_course_students($context);
    if (!$students) return false;

    foreach ($students as $student) {
        if ($userid==0 or $userid==$student->id) {
            $grade = new stdClass();
            $grade->userid   = $student->id;
            $grade->rawgrade = autoattend_get_grade($student->id, $autoattendmod->course);
            autoattendmod_grade_item_update($autoattendmod, $grade);
            if ($userid!=0 and $userid==$student->id) break;
        }
    }
    autoattendmod_grade_item_update($autoattendmod);

    return;
}


//
// $autoattendmod: id(instance番号の事), course が必要
//
function autoattendmod_grade_item_update($autoattendmod, $grades=NULL)
{
    global $CFG;

    if (file_exists($CFG->dirroot.'/blocks/autoattend/locallib.php')) {
        require_once($CFG->dirroot.'/blocks/autoattend/locallib.php');
    }
    else return null;

    require_once($CFG->dirroot.'/mod/autoattendmod/locallib.php');
    require_once($CFG->libdir.'/gradelib.php');

    //
    $summary = autoattend_get_session_summary($autoattendmod->course);
    if (empty($summary['maxgrade'])) $summary['maxgrade'] = 0; 
    if (empty($summary['mingrade'])) $summary['mingrade'] = 0; 

    if (!property_exists($autoattendmod, 'idnumber')) {
        $autoattendmod->idnumber = autoattendmod_get_idnumber($autoattendmod->course);
    }

    $params = array('itemname'=>'autoattendmod', 'idnumber'=>$autoattendmod->idnumber);
    $params['gradetype'] = GRADE_TYPE_VALUE;
    $params['grademax']  = $summary['maxgrade'];
    $params['grademin']  = $summary['mingrade'];

    if ($grades==='reset') {
        $params['reset'] = true;
        $grades = NULL;
    }

    $ret = grade_update('mod/autoattendmod', $autoattendmod->course, 'mod', 'autoattendmod', $autoattendmod->id, 0, $grades, $params);
    return $ret;
}


//
function autoattendmod_grade_item_delete($autoattendmod)
{
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    $ret = grade_update('mod/autoattendmod', $autoattendmod->course, 'mod', 'autoattendmod', $autoattendmod->id, 0, NULL, array('deleted'=>1));
    return $ret;
}

