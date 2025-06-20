<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Library of functions and constants for module behaviour
 *
 * @package   mod_behaviour
 * @copyright  2011 Artem Andreev <andreev.artem@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once(dirname(__FILE__) . '/classes/calendar_helpers.php');

/**
 * Returns the information if the module supports a feature
 *
 * @see plugin_supports() in lib/moodlelib.php
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed true if the feature is supported, null if unknown
 */
function behaviour_supports($feature) {
    switch($feature) {
        case FEATURE_GRADE_HAS_GRADE:
            return true;
        case FEATURE_GROUPS:
            return true;
        case FEATURE_GROUPINGS:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        // Artem Andreev: AFAIK it's not tested.
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return false;
        case FEATURE_MOD_PURPOSE:
            return MOD_PURPOSE_ADMINISTRATION;
        default:
            return null;
    }
}

/**
 * Add default set of statuses to the new behaviour.
 *
 * @param int $attid - id of behaviour instance.
 */
function beh_add_default_statuses($attid) {
    global $DB;

    $statuses = $DB->get_recordset('behaviour_statuses', array('behaviourid' => 0), 'id');
    foreach ($statuses as $st) {
        $rec = $st;
        $rec->behaviourid = $attid;
        $DB->insert_record('behaviour_statuses', $rec);
    }
    $statuses->close();
}

/**
 * Add default set of warnings to the new behaviour.
 *
 * @param int $id - id of behaviour instance.
 */
function behaviour_add_default_warnings($id) {
    global $DB, $CFG;
    require_once($CFG->dirroot.'/mod/behaviour/locallib.php');

    $warnings = $DB->get_recordset('behaviour_warning',
        array('idnumber' => 0), 'id');
    foreach ($warnings as $n) {
        $rec = $n;
        $rec->idnumber = $id;
        $DB->insert_record('behaviour_warning', $rec);
    }
    $warnings->close();
}

/**
 * Add new behaviour instance.
 *
 * @param stdClass $behaviour
 * @return bool|int
 */
function behaviour_add_instance($behaviour) {
    global $DB;

    $behaviour->timemodified = time();

    // Default grade (similar to what db fields defaults if no grade attribute is passed),
    // but we need it in object for grading update.
    if (!isset($behaviour->grade)) {
        $behaviour->grade = 100;
    }

    $behaviour->id = $DB->insert_record('behaviour', $behaviour);

    beh_add_default_statuses($behaviour->id);

    behaviour_add_default_warnings($behaviour->id);

    behaviour_grade_item_update($behaviour);

    return $behaviour->id;
}

/**
 * Update existing behaviour instance.
 *
 * @param stdClass $behaviour
 * @return bool
 */
function behaviour_update_instance($behaviour) {
    global $DB;

    $behaviour->timemodified = time();
    $behaviour->id = $behaviour->instance;

    if (! $DB->update_record('behaviour', $behaviour)) {
        return false;
    }

    behaviour_grade_item_update($behaviour);

    return true;
}

/**
 * Delete existing behaviour
 *
 * @param int $id
 * @return bool
 */
function behaviour_delete_instance($id) {
    global $DB, $CFG;
    require_once($CFG->dirroot.'/mod/behaviour/locallib.php');

    if (! $behaviour = $DB->get_record('behaviour', array('id' => $id))) {
        return false;
    }

    if ($sessids = array_keys($DB->get_records('behaviour_sessions', array('behaviourid' => $id), '', 'id'))) {
        if (behaviour_existing_calendar_events_ids($sessids)) {
            behaviour_delete_calendar_events($sessids);
        }
        $DB->delete_records_list('behaviour_log', 'sessionid', $sessids);
        $DB->delete_records('behaviour_sessions', array('behaviourid' => $id));
    }
    $DB->delete_records('behaviour_statuses', array('behaviourid' => $id));

    $DB->delete_records('behaviour_warning', array('idnumber' => $id));

    $DB->delete_records('behaviour', array('id' => $id));

    behaviour_grade_item_delete($behaviour);

    return true;
}

/**
 * Called by course/reset.php
 * @param moodleform $mform form passed by reference
 */
function behaviour_reset_course_form_definition(&$mform) {
    $mform->addElement('header', 'behaviourheader', get_string('modulename', 'behaviour'));

    $mform->addElement('static', 'description', get_string('description', 'behaviour'),
                                get_string('resetdescription', 'behaviour'));
    $mform->addElement('checkbox', 'reset_behaviour_log', get_string('deletelogs', 'behaviour'));

    $mform->addElement('checkbox', 'reset_behaviour_sessions', get_string('deletesessions', 'behaviour'));
    $mform->disabledIf('reset_behaviour_sessions', 'reset_behaviour_log', 'notchecked');

    $mform->addElement('checkbox', 'reset_behaviour_statuses', get_string('resetstatuses', 'behaviour'));
    $mform->setAdvanced('reset_behaviour_statuses');
    $mform->disabledIf('reset_behaviour_statuses', 'reset_behaviour_log', 'notchecked');
}

/**
 * Course reset form defaults.
 *
 * @param stdClass $course
 * @return array
 */
function behaviour_reset_course_form_defaults($course) {
    return array('reset_behaviour_log' => 0, 'reset_behaviour_statuses' => 0, 'reset_behaviour_sessions' => 0);
}

/**
 * Reset user data within behaviour.
 *
 * @param stdClass $data
 * @return array
 */
function behaviour_reset_userdata($data) {
    global $DB;

    $status = array();

    $attids = array_keys($DB->get_records('behaviour', array('course' => $data->courseid), '', 'id'));

    if (!empty($data->reset_behaviour_log)) {
        $sess = $DB->get_records_list('behaviour_sessions', 'behaviourid', $attids, '', 'id');
        if (!empty($sess)) {
            list($sql, $params) = $DB->get_in_or_equal(array_keys($sess));
            $DB->delete_records_select('behaviour_log', "sessionid $sql", $params);
            list($sql, $params) = $DB->get_in_or_equal($attids);
            $DB->set_field_select('behaviour_sessions', 'lasttaken', 0, "behaviourid $sql", $params);
            if (empty($data->reset_behaviour_sessions)) {
                // If sessions are being retained, clear automarkcompleted value.
                $DB->set_field_select('behaviour_sessions', 'automarkcompleted', 0, "behaviourid $sql", $params);
            }

            $status[] = array(
                'component' => get_string('modulenameplural', 'behaviour'),
                'item' => get_string('behaviourdata', 'behaviour'),
                'error' => false
            );
        }
    }

    if (!empty($data->reset_behaviour_statuses)) {
        $DB->delete_records_list('behaviour_statuses', 'behaviourid', $attids);
        foreach ($attids as $attid) {
            beh_add_default_statuses($attid);
        }

        $status[] = array(
            'component' => get_string('modulenameplural', 'behaviour'),
            'item' => get_string('sessions', 'behaviour'),
            'error' => false
        );
    }

    if (!empty($data->reset_behaviour_sessions)) {
        $sessionsids = array_keys($DB->get_records_list('behaviour_sessions', 'behaviourid', $attids, '', 'id'));
        if (behaviour_existing_calendar_events_ids($sessionsids)) {
            behaviour_delete_calendar_events($sessionsids);
        }
        $DB->delete_records_list('behaviour_sessions', 'behaviourid', $attids);

        $status[] = array(
            'component' => get_string('modulenameplural', 'behaviour'),
            'item' => get_string('statuses', 'behaviour'),
            'error' => false
        );
    }

    return $status;
}
/**
 * Return a small object with summary information about what a
 *  user has done with a given particular instance of this module
 *  Used for user activity reports.
 *  $return->time = the time they did it
 *  $return->info = a short text description
 *
 * @param stdClass $course - full course record.
 * @param stdClass $user - full user record
 * @param stdClass $mod
 * @param stdClass $behaviour
 * @return stdClass.
 */
function behaviour_user_outline($course, $user, $mod, $behaviour) {
    global $CFG;
    require_once(dirname(__FILE__).'/locallib.php');
    require_once($CFG->libdir.'/gradelib.php');

    $grades = grade_get_grades($course->id, 'mod', 'behaviour', $behaviour->id, $user->id);

    $result = new stdClass();
    if (!empty($grades->items[0]->grades)) {
        $grade = reset($grades->items[0]->grades);
        $result->time = $grade->dategraded;
    } else {
        $result->time = 0;
    }
    if (has_capability('mod/behaviour:canbelisted', $mod->context, $user->id)) {
        $summary = new mod_behaviour_summary($behaviour->id, $user->id);
        $usersummary = $summary->get_all_sessions_summary_for($user->id);

        $result->info = $usersummary->pointsallsessions;
    }

    return $result;
}
/**
 * Print a detailed representation of what a  user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * @param stdClass $course
 * @param stdClass $user
 * @param stdClass $mod
 * @param stdClass $behaviour
 */
function behaviour_user_complete($course, $user, $mod, $behaviour) {
    global $CFG;

    require_once(dirname(__FILE__).'/renderhelpers.php');
    require_once($CFG->libdir.'/gradelib.php');

    if (has_capability('mod/behaviour:canbelisted', $mod->context, $user->id)) {
        echo behaviour_construct_full_user_stat_html_table($behaviour, $user);
    }
}

/**
 * Dummy function - must exist to allow quick editing of module name.
 *
 * @param stdClass $behaviour
 * @param int $userid
 * @param bool $nullifnone
 */
function behaviour_update_grades($behaviour, $userid=0, $nullifnone=true) {
    // We need this function to exist so that quick editing of module name is passed to gradebook.
}
/**
 * Create grade item for given behaviour
 *
 * @param stdClass $behaviour object with extra cmidnumber
 * @param mixed $grades optional array/object of grade(s); 'reset' means reset grades in gradebook
 * @return int 0 if ok, error code otherwise
 */
function behaviour_grade_item_update($behaviour, $grades=null) {
    global $CFG, $DB;

    require_once('locallib.php');

    if (!function_exists('grade_update')) { // Workaround for buggy PHP versions.
        require_once($CFG->libdir.'/gradelib.php');
    }

    if (!isset($behaviour->courseid)) {
        $behaviour->courseid = $behaviour->course;
    }
    if (!$DB->get_record('course', array('id' => $behaviour->course))) {
        error("Course is misconfigured");
    }

    if (!empty($behaviour->cmidnumber)) {
        $params = array('itemname' => $behaviour->name, 'idnumber' => $behaviour->cmidnumber);
    } else {
        // MDL-14303.
        $params = array('itemname' => $behaviour->name);
    }

    if ($behaviour->grade > 0) {
        $params['gradetype'] = GRADE_TYPE_VALUE;
        $params['grademax']  = $behaviour->grade;
        $params['grademin']  = 0;
    } else if ($behaviour->grade < 0) {
        $params['gradetype'] = GRADE_TYPE_SCALE;
        $params['scaleid']   = -$behaviour->grade;

    } else {
        $params['gradetype'] = GRADE_TYPE_NONE;
    }

    if ($grades === 'reset') {
        $params['reset'] = true;
        $grades = null;
    }

    return grade_update('mod/behaviour', $behaviour->courseid, 'mod', 'behaviour', $behaviour->id, 0, $grades, $params);
}

/**
 * Delete grade item for given behaviour
 *
 * @param object $behaviour object
 * @return object behaviour
 */
function behaviour_grade_item_delete($behaviour) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    if (!isset($behaviour->courseid)) {
        $behaviour->courseid = $behaviour->course;
    }

    return grade_update('mod/behaviour', $behaviour->courseid, 'mod', 'behaviour',
                        $behaviour->id, 0, null, array('deleted' => 1));
}

/**
 * This function returns if a scale is being used by one behaviour
 * it it has support for grading and scales. Commented code should be
 * modified if necessary. See book, glossary or journal modules
 * as reference.
 *
 * @param int $behaviourid
 * @param int $scaleid
 * @return boolean True if the scale is used by any behaviour
 */
function behaviour_scale_used ($behaviourid, $scaleid) {
    return false;
}

/**
 * Checks if scale is being used by any instance of behaviour
 *
 * This is used to find out if scale used anywhere
 *
 * @param int $scaleid
 * @return bool true if the scale is used by any book
 */
function behaviour_scale_used_anywhere($scaleid) {
    return false;
}

/**
 * Serves the behaviour sessions descriptions files.
 *
 * @param object $course
 * @param object $cm
 * @param object $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @return bool false if file not found, does not return if found - justsend the file
 */
function behaviour_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload) {
    global $DB;

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    require_login($course, false, $cm);

    if (!$DB->record_exists('behaviour', array('id' => $cm->instance))) {
        return false;
    }

    // Session area is served by pluginfile.php.
    $fileareas = array('session');
    if (!in_array($filearea, $fileareas)) {
        return false;
    }

    $sessid = (int)array_shift($args);
    if (!$DB->record_exists('behaviour_sessions', array('id' => $sessid))) {
        return false;
    }

    $fs = get_file_storage();
    $relativepath = implode('/', $args);
    $fullpath = "/$context->id/mod_behaviour/$filearea/$sessid/$relativepath";
    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) || $file->is_directory()) {
        return false;
    }
    send_stored_file($file, 0, 0, true);
}

/**
 * Print tabs on behaviour settings page.
 *
 * @param string $selected - current selected tab.
 */
function behaviour_print_settings_tabs($selected = 'settings') {
    global $CFG;
    // Print tabs for different settings pages.
    $tabs = array();
    $tabs[] = new tabobject('settings', "{$CFG->wwwroot}/{$CFG->admin}/settings.php?section=modsettingbehaviour",
        get_string('settings', 'behaviour'), get_string('settings'), false);

    $tabs[] = new tabobject('defaultstatus', $CFG->wwwroot.'/mod/behaviour/defaultstatus.php',
        get_string('defaultstatus', 'behaviour'), get_string('defaultstatus', 'behaviour'), false);

    if (get_config('behaviour', 'enablewarnings')) {
        $tabs[] = new tabobject('defaultwarnings', $CFG->wwwroot . '/mod/behaviour/warnings.php',
            get_string('defaultwarnings', 'behaviour'), get_string('defaultwarnings', 'behaviour'), false);
    }

    $tabs[] = new tabobject('customfields', $CFG->wwwroot . '/mod/behaviour/customfields.php',
        get_string('customfields', 'behaviour'), get_string('customfields', 'behaviour'), false);

    $tabs[] = new tabobject('coursesummary', $CFG->wwwroot.'/mod/behaviour/coursesummary.php',
        get_string('coursesummary', 'behaviour'), get_string('coursesummary', 'behaviour'), false);

    if (get_config('behaviour', 'enablewarnings')) {
        $tabs[] = new tabobject('absentee', $CFG->wwwroot . '/mod/behaviour/absentee.php',
            get_string('absenteereport', 'behaviour'), get_string('absenteereport', 'behaviour'), false);
    }

    $tabs[] = new tabobject('resetcalendar', $CFG->wwwroot.'/mod/behaviour/resetcalendar.php',
        get_string('resetcalendar', 'behaviour'), get_string('resetcalendar', 'behaviour'), false);

    $tabs[] = new tabobject('importsessions', $CFG->wwwroot . '/mod/behaviour/import/sessions.php',
        get_string('importsessions', 'behaviour'), get_string('importsessions', 'behaviour'), false);

    ob_start();
    print_tabs(array($tabs), $selected);
    $tabmenu = ob_get_contents();
    ob_end_clean();

    return $tabmenu;
}

/**
 * Helper function to remove a user from the thirdpartyemails record of the behaviour_warning table.
 *
 * @param array $warnings - list of warnings to parse.
 * @param int $userid - User id of user to remove.
 */
function behaviour_remove_user_from_thirdpartyemails($warnings, $userid) {
    global $DB;

    // Update the third party emails list for all the relevant warnings.
    $updatedwarnings = array_map(
        function(stdClass $warning) use ($userid) : stdClass {
            $warning->thirdpartyemails = implode(',', array_diff(explode(',', $warning->thirdpartyemails), [$userid]));
            return $warning;
        },
        array_filter(
            $warnings,
            function (stdClass $warning) use ($userid) : bool {
                return in_array($userid, explode(',', $warning->thirdpartyemails));
            }
        )
    );

    // Sadly need to update each individually, no way to bulk update as all the thirdpartyemails field can be different.
    foreach ($updatedwarnings as $updatedwarning) {
        $DB->update_record('behaviour_warning', $updatedwarning);
    }
}

/**
 * Add nodes to myprofile page.
 *
 * @param \core_user\output\myprofile\tree $tree Tree object
 * @param stdClass $user user object
 * @param bool $iscurrentuser
 * @param stdClass $course Course object
 *
 * @return bool
 */
function mod_behaviour_myprofile_navigation(core_user\output\myprofile\tree $tree, $user, $iscurrentuser, $course) {
    if (empty($course)) {
        return;
    }
    $cms = get_all_instances_in_course('behaviour', $course, $user->id);
    if (empty($cms)) {
        return;
    }
    $cm = reset($cms);
    if (!empty($cm->coursemodule) && has_capability('mod/behaviour:viewreports', context_module::instance($cm->coursemodule))) {
        $url = new moodle_url('/mod/behaviour/view.php', ['id' => $cm->coursemodule,
                                                           'mode' => mod_behaviour_view_page_params::MODE_THIS_COURSE,
                                                           'studentid' => $user->id]);

        $node = new core_user\output\myprofile\node('reports', 'behaviouruserreport',
                                                    get_string('behaviouruserreport', 'behaviour'),
                                                    null, $url);
        $tree->add_node($node);
    }
}

/**
 * Adds module specific settings to the settings block
 *
 * @param settings_navigation $settingsnav The settings navigation object
 * @param navigation_node $behaviournode The node to add module settings to
 */
function behaviour_extend_settings_navigation(settings_navigation $settingsnav, navigation_node $behaviournode) {

    $context = $settingsnav->get_page()->cm->context;
    $cm = $settingsnav->get_page()->cm;
    $nodes = [];
//    if (has_capability('mod/behaviour:viewreports', $context)) {
//        $nodes[] = ['url' => new moodle_url('/mod/behaviour/report.php', ['id' => $cm->id]),
//                    'title' => get_string('report', 'behaviour')];
//    }
//    if (has_capability('mod/behaviour:import', $context)) {
//        $nodes[] = ['url' => new moodle_url('/mod/behaviour/import.php', ['id' => $cm->id]),
//                    'title' => get_string('import', 'behaviour')];
//    }
    if (has_capability('mod/behaviour:export', $context)) {
        $nodes[] = ['url' => new moodle_url('/mod/behaviour/export.php', ['id' => $cm->id]),
                    'title' => get_string('export', 'behaviour')];
    }

    if (has_capability('mod/behaviour:viewreports', $context) && get_config('behaviour', 'enablewarnings')) {
        $nodes[] = ['url' => new moodle_url('/mod/behaviour/absentee.php', ['id' => $cm->id]),
                    'title' => get_string('absenteereport', 'behaviour')];
    }
    if (has_capability('mod/behaviour:changepreferences', $context)) {
        $nodes[] = ['url' => new moodle_url('/mod/behaviour/preferences.php', ['id' => $cm->id]),
                    'title' => get_string('statussetsettings', 'behaviour')];
        if (get_config('behaviour', 'enablewarnings')) {
            $nodes[] = ['url' => new moodle_url('/mod/behaviour/warnings.php', ['id' => $cm->id]),
            'title' => get_string('warnings', 'behaviour')];
        }
    }

//    if (has_capability('mod/behaviour:managetemporaryusers', context_module::instance($cm->id))) {
//        $nodes[] = ['url' => new moodle_url('/mod/behaviour/tempusers.php', ['id' => $cm->id]),
//        'title' => get_string('tempusers', 'behaviour'),
//        'more' => true];
//    }

    foreach ($nodes as $node) {
        $settingsnode = navigation_node::create($node['title'],
                                                $node['url'],
                                                navigation_node::TYPE_SETTING);
        if (isset($settingsnode)) {
            if (!empty($node->more)) {
                $settingsnode->set_force_into_more_menu(true);
            }
            $behaviournode->add_node($settingsnode);
        }
    }
}
