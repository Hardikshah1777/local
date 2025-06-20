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
 * Lists all the users within a given course.
 *
 * @copyright 1999 Martin Dougiamas  http://dougiamas.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package core_user
 */

use local_userstatus\status;

require_once('../../config.php');
require_once($CFG->dirroot.'/user/lib.php');
require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->dirroot.'/notes/lib.php');
require_once($CFG->libdir.'/tablelib.php');
require_once($CFG->libdir.'/filelib.php');
require_once($CFG->dirroot.'/enrol/locallib.php');
require_once($CFG->dirroot.'/user/renderer.php');
require_once($CFG->dirroot.'/cohort/lib.php');

define('DEFAULT_PAGE_SIZE', 20);
define('SHOW_ALL_PAGE_SIZE', 5000);
define('USER_FILTER_COHORT', 7);
define('USER_FILTER_STATUSID', 8);

class user_renderer extends \core_user_renderer {
    public function unified_filter($course, $context, $filtersapplied, $baseurl = null) {
        global $CFG, $DB, $USER;

        require_once($CFG->dirroot . '/enrol/locallib.php');
        require_once($CFG->dirroot . '/lib/grouplib.php');
        $manager = new course_enrolment_manager($this->page, $course);

        $filteroptions = [];

        // Filter options for role.
        $roleseditable = has_capability('moodle/role:assign', $context);
        $roles = get_viewable_roles($context);
        if ($roleseditable) {
            $roles += get_assignable_roles($context, ROLENAME_ALIAS);
        }

        $criteria = get_string('role');
        $roleoptions = $this->format_filter_option(USER_FILTER_ROLE, $criteria, -1, get_string('noroles', 'role'));
        foreach ($roles as $id => $role) {
            $roleoptions += $this->format_filter_option(USER_FILTER_ROLE, $criteria, $id, $role);
        }
        $filteroptions += $roleoptions;

        // Filter options for groups, if available.
        if (has_capability('moodle/site:accessallgroups', $context) || $course->groupmode != SEPARATEGROUPS) {
            // List all groups if the user can access all groups, or we are in visible group mode or no groups mode.
            $groups = $manager->get_all_groups();
            if (!empty($groups)) {
                // Add 'No group' option, to enable filtering users without any group.
                $nogroup[USERSWITHOUTGROUP] = (object)['name' => get_string('nogroup', 'group')];
                $groups = $nogroup + $groups;
            }
        } else {
            // Otherwise, just list the groups the user belongs to.
            $groups = groups_get_all_groups($course->id, $USER->id);
        }
        $criteria = get_string('group');
        $groupoptions = [];
        foreach ($groups as $id => $group) {
            $groupoptions += $this->format_filter_option(USER_FILTER_GROUP, $criteria, $id, $group->name);
        }
        $filteroptions += $groupoptions;

        //cohort
        $allcohorts = cohort_get_all_cohorts(0,0);
        $criteria = get_string('cohort','core_cohort');
        $cohortoptions = [];
        foreach ($allcohorts['cohorts'] as $id => $cohort) {
            $cohortoptions += $this->format_filter_option(USER_FILTER_COHORT, $criteria, $id, $cohort->name);
        }
        $filteroptions += $cohortoptions;
        //*cohort

        //workflow
        $allstatus = status::get_options();
        $criteria = get_string('workflowcrit',status::component);
        $statusoptions = [];
        foreach ($allstatus as $id => $status) {
            $statusoptions += $this->format_filter_option(USER_FILTER_STATUSID, $criteria, $id, $status);
        }
        $filteroptions += $statusoptions;
        //*cohort

        $canreviewenrol = has_capability('moodle/course:enrolreview', $context);

        // Filter options for status.
        if ($canreviewenrol) {
            $criteria = get_string('status');
            // Add statuses.
            $filteroptions += $this->format_filter_option(USER_FILTER_STATUS, $criteria, ENROL_USER_ACTIVE, get_string('active'));
            $filteroptions += $this->format_filter_option(USER_FILTER_STATUS, $criteria, ENROL_USER_SUSPENDED,
                    get_string('inactive'));
        }

        // Filter options for enrolment methods.
        if ($canreviewenrol && $enrolmentmethods = $manager->get_enrolment_instance_names(true)) {
            $criteria = get_string('enrolmentinstances', 'enrol');
            $enroloptions = [];
            foreach ($enrolmentmethods as $id => $enrolname) {
                $enroloptions += $this->format_filter_option(USER_FILTER_ENROLMENT, $criteria, $id, $enrolname);
            }
            $filteroptions += $enroloptions;
        }

        $isfrontpage = ($course->id == SITEID);

        // Get the list of fields we have to hide.
        $hiddenfields = array();
        if (!has_capability('moodle/course:viewhiddenuserfields', $context)) {
            $hiddenfields = array_flip(explode(',', $CFG->hiddenuserfields));
        }
        $haslastaccess = !isset($hiddenfields['lastaccess']);
        // Filter options for last access.
        if ($haslastaccess) {
            // Get minimum lastaccess for this course and display a dropbox to filter by lastaccess going back this far.
            // We need to make it diferently for normal courses and site course.
            if (!$isfrontpage) {
                $params = ['courseid' => $course->id, 'timeaccess' => 0];
                $select = 'courseid = :courseid AND timeaccess != :timeaccess';
                $minlastaccess = $DB->get_field_select('user_lastaccess', 'MIN(timeaccess)', $select, $params);
                $lastaccess0exists = $DB->record_exists('user_lastaccess', $params);
            } else {
                $params = ['lastaccess' => 0];
                $select = 'lastaccess != :lastaccess';
                $minlastaccess = $DB->get_field_select('user', 'MIN(lastaccess)', $select, $params);
                $lastaccess0exists = $DB->record_exists('user', $params);
            }
            $now = usergetmidnight(time());
            $timeoptions = [];
            $criteria = get_string('usersnoaccesssince');

            // Days.
            for ($i = 1; $i < 7; $i++) {
                $timestamp = strtotime('-' . $i . ' days', $now);
                if ($timestamp < $minlastaccess) {
                    break;
                }
                $value = get_string('numdays', 'moodle', $i);
                $timeoptions += $this->format_filter_option(USER_FILTER_LAST_ACCESS, $criteria, $timestamp, $value);
            }
            // Weeks.
            for ($i = 1; $i < 10; $i++) {
                $timestamp = strtotime('-'.$i.' weeks', $now);
                if ($timestamp < $minlastaccess) {
                    break;
                }
                $value = get_string('numweeks', 'moodle', $i);
                $timeoptions += $this->format_filter_option(USER_FILTER_LAST_ACCESS, $criteria, $timestamp, $value);
            }
            // Months.
            for ($i = 2; $i < 12; $i++) {
                $timestamp = strtotime('-'.$i.' months', $now);
                if ($timestamp < $minlastaccess) {
                    break;
                }
                $value = get_string('nummonths', 'moodle', $i);
                $timeoptions += $this->format_filter_option(USER_FILTER_LAST_ACCESS, $criteria, $timestamp, $value);
            }
            // Try a year.
            $timestamp = strtotime('-1 year', $now);
            if ($timestamp >= $minlastaccess) {
                $value = get_string('numyear', 'moodle', 1);
                $timeoptions += $this->format_filter_option(USER_FILTER_LAST_ACCESS, $criteria, $timestamp, $value);
            }
            if (!empty($lastaccess0exists)) {
                $value = get_string('never', 'moodle');
                $timeoptions += $this->format_filter_option(USER_FILTER_LAST_ACCESS, $criteria, $timestamp, $value);
            }
            if (count($timeoptions) > 1) {
                $filteroptions += $timeoptions;
            }
        }

        // Add missing applied filters to the filter options.
        $filteroptions = $this->handle_missing_applied_filters($filtersapplied, $filteroptions);

        $indexpage = new \core_user\output\unified_filter($filteroptions, $filtersapplied, $baseurl);
        $context = $indexpage->export_for_template($this->output);

        return $this->output->render_from_template('core_user/unified_filter', $context);
    }

    private function handle_missing_applied_filters($filtersapplied, $filteroptions) {
        global $DB;

        foreach ($filtersapplied as $filter) {
            if (!array_key_exists($filter, $filteroptions)) {
                $filtervalue = explode(':', $filter);
                if (count($filtervalue) !== 2) {
                    continue;
                }
                $key = $filtervalue[0];
                $value = $filtervalue[1];

                switch($key) {
                    case USER_FILTER_LAST_ACCESS:
                        $now = usergetmidnight(time());
                        $criteria = get_string('usersnoaccesssince');
                        // Days.
                        for ($i = 1; $i < 7; $i++) {
                            $timestamp = strtotime('-' . $i . ' days', $now);
                            if ($timestamp < $value) {
                                break;
                            }
                            $val = get_string('numdays', 'moodle', $i);
                            $filteroptions += $this->format_filter_option(USER_FILTER_LAST_ACCESS, $criteria, $timestamp, $val);
                        }
                        // Weeks.
                        for ($i = 1; $i < 10; $i++) {
                            $timestamp = strtotime('-'.$i.' weeks', $now);
                            if ($timestamp < $value) {
                                break;
                            }
                            $val = get_string('numweeks', 'moodle', $i);
                            $filteroptions += $this->format_filter_option(USER_FILTER_LAST_ACCESS, $criteria, $timestamp, $val);
                        }
                        // Months.
                        for ($i = 2; $i < 12; $i++) {
                            $timestamp = strtotime('-'.$i.' months', $now);
                            if ($timestamp < $value) {
                                break;
                            }
                            $val = get_string('nummonths', 'moodle', $i);
                            $filteroptions += $this->format_filter_option(USER_FILTER_LAST_ACCESS, $criteria, $timestamp, $val);
                        }
                        // Try a year.
                        $timestamp = strtotime('-1 year', $now);
                        if ($timestamp >= $value) {
                            $val = get_string('numyear', 'moodle', 1);
                            $filteroptions += $this->format_filter_option(USER_FILTER_LAST_ACCESS, $criteria, $timestamp, $val);
                        }
                        break;
                    case USER_FILTER_ROLE:
                        $criteria = get_string('role');
                        if ($role = $DB->get_record('role', array('id' => $value))) {
                            $role = role_get_name($role);
                            $filteroptions += $this->format_filter_option(USER_FILTER_ROLE, $criteria, $value, $role);
                        }
                        break;
                }
            }
        }
        return $filteroptions;
    }

}

class participants extends \local_userstatus\participants_table {
    private $cohortid;
    private $statusid;

    public function __construct($courseid, $currentgroup, $accesssince, $roleid, $enrolid, $status, $search, $bulkoperations,
            $selectall,$cohortid,$statusid) {
        parent::__construct($courseid, $currentgroup, $accesssince, $roleid, $enrolid, $status, $search, $bulkoperations,
                $selectall);
        $this->cohortid = $cohortid;
        $this->statusid = $statusid;
        $columns = array_keys($this->columns);
        $headers = $this->headers;
        array_splice($columns,2,0,['workflow']);
        array_splice($headers,2,0,[get_string('workflow','local_userstatus')]);

        $statuskey = array_search('status',$columns);
        if($statuskey !== false){
            unset($columns[$statuskey],$headers[$statuskey]);
        }
        $this->define_columns($columns);
        $this->define_headers($headers);
    }

    public function query_db($pagesize, $useinitialsbar = true) {
        list($twhere, $tparams) = $this->get_sql_where();

        if($this->cohortid > 0){
            $twhere .= ($twhere ? " AND ":"")." u.id IN (SELECT userid FROM {cohort_members} WHERE cohortid = :cohortid)";
            $tparams['cohortid'] = $this->cohortid;
        }
        if($this->statusid > 0){
            $table = status::dbtable;
            $statuswhere = " u.id IN (SELECT userid FROM {{$table}} WHERE statusid = :statusid AND courseid = :courseid2)";
            if($this->statusid == status::inprogess){
                $statuswhere = " ({$statuswhere} OR u.id NOT IN (SELECT userid FROM {{$table}} WHERE courseid = :courseid3))";
            }
            $twhere .= ($twhere ? " AND ":"").$statuswhere;
            $tparams['statusid'] = $this->statusid;
            $tparams['courseid2'] = $tparams['courseid3'] = $this->course->id;
        }

        $total = user_get_total_participants($this->course->id, $this->currentgroup, $this->accesssince,
                $this->roleid, $this->enrolid, $this->status, $this->search, $twhere, $tparams);

        $this->pagesize($pagesize, $total);

        $sort = $this->get_sql_sort();
        if ($sort) {
            $sort = 'ORDER BY ' . $sort;
        }

        $rawdata = $this->user_get_participants($this->course->id, $this->currentgroup, $this->accesssince,
                $this->roleid, $this->enrolid, $this->status, $this->search, $twhere, $tparams, $sort, $this->get_page_start(),
                $this->get_page_size());
        $this->rawdata = [];
        foreach ($rawdata as $user) {
            $this->rawdata[$user->id] = $user;
        }
        $rawdata->close();

        if ($this->rawdata) {
            $this->allroleassignments = get_users_roles($this->context, array_keys($this->rawdata),
                    true, 'c.contextlevel DESC, r.sortorder ASC');
        } else {
            $this->allroleassignments = [];
        }

        /*global $DB;
        foreach ($this->rawdata as $userid => $raw){
            $this->rawdata[$userid]->workflow = status::inprogess;
        }
        $rawset = $DB->get_recordset(status::dbtable,['courseid' => $this->courseid,]);
        foreach ($rawset as $raw){
            if(array_key_exists($raw->userid,$this->rawdata)){
                $this->rawdata[$raw->userid]->workflow = $raw->statusid;
            }
        }
        $rawset->close();*/

        // Set initial bars.
        if ($useinitialsbar) {
            $this->initialbars(true);
        }
    }

    function user_get_participants($courseid, $groupid = 0, $accesssince, $roleid, $enrolid = 0, $statusid, $search,
            $additionalwhere = '', $additionalparams = array(), $sort = '', $limitfrom = 0, $limitnum = 0) {
        global $DB;

        list($select, $from, $where, $params) = user_get_participants_sql($courseid, $groupid, $accesssince, $roleid, $enrolid,
                $statusid, $search, $additionalwhere, $additionalparams);

        $select .= " , COALESCE(us.statusid,:defaultstatus) AS rawstatus";
        $select .= " , (CASE";
        foreach (array_keys(status::get_sortstatus()) as $sortindex => $statusid) {
            $select .= " WHEN us.statusid = :status{$sortindex} THEN {$sortindex}";
            $params["status{$sortindex}"] = $statusid;
        }
        $select .= " ELSE 0 END) AS workflow";
        $from .= " LEFT JOIN {".status::dbtable."} us ON us.userid = u.id AND us.courseid = :statuscourseid";
        $params['statuscourseid'] = $courseid;
        $params['defaultstatus'] = status::inprogess;
        return $DB->get_recordset_sql("$select $from $where $sort", $params, $limitfrom, $limitnum);
    }

    public function col_workflow($row){
        if(isset($row->rawstatus)){
            return status::get_statushtml_by_id($row->rawstatus);
        }
        return '<span class="badge" data-statusid=""></span>';
    }

}

$page         = optional_param('page', 0, PARAM_INT); // Which page to show.
$perpage      = optional_param('perpage', DEFAULT_PAGE_SIZE, PARAM_INT); // How many per page.
$contextid    = optional_param('contextid', 0, PARAM_INT); // One of this or.
$courseid     = optional_param('id', 0, PARAM_INT); // This are required.
$newcourse    = optional_param('newcourse', false, PARAM_BOOL);
$selectall    = optional_param('selectall', false, PARAM_BOOL); // When rendering checkboxes against users mark them all checked.
$roleid       = optional_param('roleid', 0, PARAM_INT);
$groupparam   = optional_param('group', 0, PARAM_INT);
$cohortid     = optional_param('cohort', 0, PARAM_INT);
$statusid     = optional_param('status', 0, PARAM_INT);
//$perpage = 5;

$PAGE->set_url('/local/userstatus/index.php', array(
        'page' => $page,
        'perpage' => $perpage,
        'contextid' => $contextid,
        'id' => $courseid,
        'newcourse' => $newcourse));

if ($contextid) {
    $context = context::instance_by_id($contextid, MUST_EXIST);
    if ($context->contextlevel != CONTEXT_COURSE) {
        print_error('invalidcontext');
    }
    $course = $DB->get_record('course', array('id' => $context->instanceid), '*', MUST_EXIST);
} else {
    $course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
    $context = context_course::instance($course->id, MUST_EXIST);
}
// Not needed anymore.
unset($contextid);
unset($courseid);

require_login($course);

$systemcontext = context_system::instance();
$isfrontpage = ($course->id == SITEID);

$frontpagectx = context_course::instance(SITEID);

if ($isfrontpage) {
    $PAGE->set_pagelayout('admin');
    course_require_view_participants($systemcontext);
} else {
    $PAGE->set_pagelayout('incourse');
    course_require_view_participants($context);
}

// Trigger events.
user_list_view($course, $context);

$bulkoperations = has_capability('moodle/course:bulkmessaging', $context);

if(status::manage_templates($context)) {
    $singlebutton = new single_button(
            new moodle_url('templates.php',['contextid' => $context->id,]),
            get_string('managetemplates',status::component),
            'get'
    );
    $PAGE->set_button($OUTPUT->render($singlebutton));
}

$PAGE->set_title("$course->shortname: ".get_string('participants'));
$PAGE->set_heading($course->fullname);
$PAGE->set_pagetype('course-view-' . $course->format);
$PAGE->add_body_class('path-user');                     // So we can style it independently.
$PAGE->set_other_editing_capability('moodle/course:manageactivities');

// Expand the users node in the settings navigation when it exists because those pages
// are related to this one.
$node = $PAGE->settingsnav->find('users', navigation_node::TYPE_CONTAINER);
if ($node) {
    $node->force_open();
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('participants'));

// Get the currently applied filters.
$filtersapplied = optional_param_array('unified-filters', [], PARAM_NOTAGS);
$filterwassubmitted = optional_param('unified-filter-submitted', 0, PARAM_BOOL);

// If they passed a role make sure they can view that role.
if ($roleid) {
    $viewableroles = get_profile_roles($context);

    // Check if the user can view this role.
    if (array_key_exists($roleid, $viewableroles)) {
        $filtersapplied[] = USER_FILTER_ROLE . ':' . $roleid;
    } else {
        $roleid = 0;
    }
}

// Default group ID.
$groupid = false;
$canaccessallgroups = has_capability('moodle/site:accessallgroups', $context);
if ($course->groupmode != NOGROUPS) {
    if ($canaccessallgroups) {
        // Change the group if the user can access all groups and has specified group in the URL.
        if ($groupparam) {
            $groupid = $groupparam;
        }
    } else {
        // Otherwise, get the user's default group.
        $groupid = groups_get_course_group($course, true);
        if ($course->groupmode == SEPARATEGROUPS && !$groupid) {
            // The user is not in the group so show message and exit.
            echo $OUTPUT->notification(get_string('notingroup'));
            echo $OUTPUT->footer();
            exit;
        }
    }
}
$hasgroupfilter = false;
$lastaccess = 0;
$searchkeywords = [];
$enrolid = 0;
$status = -1;
foreach ($filtersapplied as $filter) {
    $filtervalue = explode(':', $filter, 2);
    $value = null;
    if (count($filtervalue) == 2) {
        $key = clean_param($filtervalue[0], PARAM_INT);
        $value = clean_param($filtervalue[1], PARAM_INT);
    } else {
        // Search string.
        $key = USER_FILTER_STRING;
        $value = clean_param($filtervalue[0], PARAM_TEXT);
    }

    switch ($key) {
        case USER_FILTER_ENROLMENT:
            $enrolid = $value;
            break;
        case USER_FILTER_GROUP:
            $groupid = $value;
            $hasgroupfilter = true;
            break;
        case USER_FILTER_COHORT:
            $cohortid = $value;
            break;
        case USER_FILTER_LAST_ACCESS:
            $lastaccess = $value;
            break;
        case USER_FILTER_ROLE:
            $roleid = $value;
            break;
        case USER_FILTER_STATUS:
            // We only accept active/suspended statuses.
            if ($value == ENROL_USER_ACTIVE || $value == ENROL_USER_SUSPENDED) {
                $status = $value;
            }
            break;
        case USER_FILTER_STATUSID:
            $statusid = $value;
            break;
        default:
            // Search string.
            $searchkeywords[] = $value;
            break;
    }
}
// If course supports groups we may need to set a default.
if (!empty($groupid)) {
    if ($canaccessallgroups) {
        // User can access all groups, let them filter by whatever was selected.
        $filtersapplied[] = USER_FILTER_GROUP . ':' . $groupid;
    } else if (!$filterwassubmitted && $course->groupmode == VISIBLEGROUPS) {
        // If we are in a course with visible groups and the user has not submitted anything and does not have
        // access to all groups, then set a default group.
        $filtersapplied[] = USER_FILTER_GROUP . ':' . $groupid;
    } else if (!$hasgroupfilter && $course->groupmode != VISIBLEGROUPS) {
        // The user can't access all groups and has not set a group filter in a course where the groups are not visible
        // then apply a default group filter.
        $filtersapplied[] = USER_FILTER_GROUP . ':' . $groupid;
    } else if (!$hasgroupfilter) { // No need for the group id to be set.
        $groupid = false;
    }
}

if ($groupid > 0 && ($course->groupmode != SEPARATEGROUPS || $canaccessallgroups)) {
    $grouprenderer = $PAGE->get_renderer('core_group');
    $groupdetailpage = new \core_group\output\group_details($groupid);
    echo $grouprenderer->group_details($groupdetailpage);
}

// Manage enrolments.
$manager = new course_enrolment_manager($PAGE, $course);
$enrolbuttons = $manager->get_manual_enrol_buttons();
$enrolrenderer = $PAGE->get_renderer('core_enrol');
$enrolbuttonsout = '';
foreach ($enrolbuttons as $enrolbutton) {
    $enrolbuttonsout .= $enrolrenderer->render($enrolbutton);
}
echo html_writer::div($enrolbuttonsout, 'float-right');

// Should use this variable so that we don't break stuff every time a variable is added or changed.
$baseurl = new moodle_url('/local/userstatus/index.php', array(
        'contextid' => $context->id,
        'id' => $course->id,
        'perpage' => $perpage));

// Render the unified filter.
$renderer = new user_renderer($PAGE,RENDERER_TARGET_GENERAL);
echo $renderer->unified_filter($course, $context, $filtersapplied, $baseurl);

echo '<div class="userlist">';

// Add filters to the baseurl after creating unified_filter to avoid losing them.
foreach (array_unique($filtersapplied) as $filterix => $filter) {
    $baseurl->param('unified-filters[' . $filterix . ']', $filter);
}
$participanttable = new participants($course->id, $groupid, $lastaccess, $roleid, $enrolid, $status,
    $searchkeywords, $bulkoperations, $selectall, $cohortid, $statusid);
$participanttable->define_baseurl($baseurl);

// Do this so we can get the total number of rows.
ob_start();
$participanttable->out($perpage, true);
$participanttablehtml = ob_get_contents();
ob_end_clean();

echo html_writer::tag('p', get_string('participantscount', 'moodle', $participanttable->totalrows));

if ($bulkoperations) {
    echo '<form action="action_redir.php" method="post" id="participantsform">';
    echo '<div>';
    echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
    echo '<input type="hidden" name="returnto" value="'.s($PAGE->url->out(false)).'" />';
}

echo $participanttablehtml;

$perpageurl = clone($baseurl);
$perpageurl->remove_params('perpage');
if ($perpage == SHOW_ALL_PAGE_SIZE && $participanttable->totalrows > DEFAULT_PAGE_SIZE) {
    $perpageurl->param('perpage', DEFAULT_PAGE_SIZE);
    echo $OUTPUT->container(html_writer::link($perpageurl, get_string('showperpage', '', DEFAULT_PAGE_SIZE)), array(), 'showall');

} else if ($participanttable->get_page_size() < $participanttable->totalrows) {
    $perpageurl->param('perpage', SHOW_ALL_PAGE_SIZE);
    echo $OUTPUT->container(html_writer::link($perpageurl, get_string('showall', '', $participanttable->totalrows)),
        array(), 'showall');
}

if ($bulkoperations) {
    echo '<br /><div class="buttons"><div class="form-inline">';

    if ($participanttable->get_page_size() < $participanttable->totalrows) {
        $perpageurl = clone($baseurl);
        $perpageurl->remove_params('perpage');
        $perpageurl->param('perpage', SHOW_ALL_PAGE_SIZE);
        $perpageurl->param('selectall', true);
        $showalllink = $perpageurl;
    } else {
        $showalllink = false;
    }

    echo html_writer::start_tag('div', array('class' => 'btn-group'));
    if ($participanttable->get_page_size() < $participanttable->totalrows) {
        // Select all users, refresh page showing all users and mark them all selected.
        $label = get_string('selectalluserswithcount', 'moodle', $participanttable->totalrows);
        echo html_writer::empty_tag('input', array('type' => 'button', 'id' => 'checkall', 'class' => 'btn btn-secondary',
                'value' => $label, 'data-showallink' => $showalllink));
    }
    echo html_writer::end_tag('div');
    $displaylist = array();
    if (!empty($CFG->messaging) && has_all_capabilities(['moodle/site:sendmessage', 'moodle/course:bulkmessaging'], $context)) {
        $displaylist['#messageselect'] = get_string('messageselectadd');
    }
    if (!empty($CFG->enablenotes) && has_capability('moodle/notes:manage', $context) && $context->id != $frontpagectx->id) {
        $displaylist['#addgroupnote'] = get_string('addnewnote', 'notes');
    }

    $params = ['operation' => 'download_participants'];

    $downloadoptions = [];
    $formats = core_plugin_manager::instance()->get_plugins_of_type('dataformat');
    foreach ($formats as $format) {
        if ($format->is_enabled()) {
            $params = ['operation' => 'download_participants', 'dataformat' => $format->name];
            $url = new moodle_url('bulkchange.php', $params);
            $downloadoptions[$url->out(false)] = get_string('dataformat', $format->component);
        }
    }

    if (!empty($downloadoptions)) {
        $displaylist[] = [get_string('downloadas', 'table') => $downloadoptions];
    }

    if ($context->id != $frontpagectx->id) {
        $instances = $manager->get_enrolment_instances();
        $plugins = $manager->get_enrolment_plugins(false);
        foreach ($instances as $key => $instance) {
            if (!isset($plugins[$instance->enrol])) {
                // Weird, some broken stuff in plugin.
                continue;
            }
            $plugin = $plugins[$instance->enrol];
            $bulkoperations = $plugin->get_bulk_operations($manager);

            $pluginoptions = [];
            foreach ($bulkoperations as $key => $bulkoperation) {
                $params = ['plugin' => $plugin->get_name(), 'operation' => $key];
                $url = new moodle_url('bulkchange.php', $params);
                $pluginoptions[$url->out(false)] = $bulkoperation->get_title();
            }
            if (!empty($pluginoptions)) {
                $name = get_string('pluginname', 'enrol_' . $plugin->get_name());
                $displaylist[] = [$name => $pluginoptions];
            }
        }
    }

    $selectactionparams = array(
        'id' => 'formactioncustom',
        'class' => 'ml-2',
        'data-action' => 'toggle',
        'data-togglegroup' => 'participants-table',
        'data-toggle' => 'action',
        'disabled' => empty($selectall)
    );
    $label = html_writer::tag('label', get_string("withselectedusers"),
            ['for' => 'formactioncustom', 'class' => 'col-form-label d-inline']);
    $select = html_writer::select($displaylist, 'formaction', '', ['' => 'choosedots'], $selectactionparams);
    echo html_writer::tag('div', $label . $select);

    echo '<input type="hidden" name="id" value="' . $course->id . '" />';
    echo '</div></div></div>';
    echo '</form>';

    $options = new stdClass();
    $options->courseid = $course->id;
    $options->noteStateNames = note_get_state_names();
    $options->stateHelpIcon = $OUTPUT->help_icon('publishstate', 'notes');
    $PAGE->requires->js_call_amd('core_user/participants', 'init', [$options]);
}

echo '</div>';  // Userlist.

$enrolrenderer = $PAGE->get_renderer('core_enrol');
echo '<div class="float-right">';
// Need to re-generate the buttons to avoid having elements with duplicate ids on the page.
$enrolbuttons = $manager->get_manual_enrol_buttons();
foreach ($enrolbuttons as $enrolbutton) {
    echo $enrolrenderer->render($enrolbutton);
}
echo '</div>';

if ($newcourse == 1) {
    $str = get_string('proceedtocourse', 'enrol');
    // The margin is to make it line up with the enrol users button when they are both on the same line.
    $classes = 'my-1';
    $url = course_get_url($course);
    echo $OUTPUT->single_button($url, $str, 'GET', array('class' => $classes));
}

$isadmin = is_siteadmin();

$templates = $DB->get_records_select(status::templatetable,'userid = :userid OR :siteadmin = 1',
        ['userid' => $USER->id, 'siteadmin' => $isadmin],'','id,name,message');

$options = $options ?? new stdClass();
$options->courseid = $course->id;
$options->statuses = status::get_options();
$options->templates = array_values($templates);
$options->notemplateid = status::notemplate;
$PAGE->requires->js_call_amd('local_userstatus/status38', 'init', [$options]);

echo $OUTPUT->footer();
