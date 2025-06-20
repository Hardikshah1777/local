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
 * Contains the class used for the displaying the participants table.
 *
 * @package    core_user
 * @copyright  2017 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_userstatus;

use context;
use core_user\output\status_field;
use DateTime;

defined('MOODLE_INTERNAL') || die;

global $CFG;

require_once($CFG->libdir . '/tablelib.php');
require_once($CFG->dirroot . '/user/lib.php');

/**
 * Class for the displaying the participants table.
 *
 * @package    core_user
 * @copyright  2017 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class _participants_table extends \table_sql {

    /**
     * @var int $courseid The course id
     */
    protected $courseid;

    /**
     * @var int|false False if groups not used, int if groups used, 0 for all groups.
     */
    protected $currentgroup;

    /**
     * @var int $accesssince The time the user last accessed the site
     */
    protected $accesssince;

    /**
     * @var int $roleid The role we are including, 0 means all enrolled users
     */
    protected $roleid;

    /**
     * @var int $enrolid The applied filter for the user enrolment ID.
     */
    protected $enrolid;

    /**
     * @var int $status The applied filter for the user's enrolment status.
     */
    protected $status;

    /**
     * @var string $search The string being searched.
     */
    protected $search;

    /**
     * @var bool $selectall Has the user selected all users on the page?
     */
    protected $selectall;

    /**
     * @var string[] The list of countries.
     */
    protected $countries;

    /**
     * @var \stdClass[] The list of groups with membership info for the course.
     */
    protected $groups;

    /**
     * @var string[] Extra fields to display.
     */
    protected $extrafields;

    /**
     * @var \stdClass $course The course details.
     */
    protected $course;

    /**
     * @var  context $context The course context.
     */
    protected $context;

    /**
     * @var \stdClass[] List of roles indexed by roleid.
     */
    protected $allroles;

    /**
     * @var \stdClass[] List of roles indexed by roleid.
     */
    protected $allroleassignments;

    /**
     * @var \stdClass[] Assignable roles in this course.
     */
    protected $assignableroles;

    /**
     * @var \stdClass[] Profile roles in this course.
     */
    protected $profileroles;

    /** @var \stdClass[] $viewableroles */
    private $viewableroles;

    /**
     * Sets up the table.
     *
     * @param int $courseid
     * @param int|false $currentgroup False if groups not used, int if groups used, 0 all groups, USERSWITHOUTGROUP for no group
     * @param int $accesssince The time the user last accessed the site
     * @param int $roleid The role we are including, 0 means all enrolled users
     * @param int $enrolid The applied filter for the user enrolment ID.
     * @param int $status The applied filter for the user's enrolment status.
     * @param string|array $search The search string(s)
     * @param bool $bulkoperations Is the user allowed to perform bulk operations?
     * @param bool $selectall Has the user selected all users on the page?
     */
    public function __construct($courseid, $currentgroup, $accesssince, $roleid, $enrolid, $status, $search,
            $bulkoperations, $selectall) {
        global $CFG, $OUTPUT;

        parent::__construct('user-index-participants-' . $courseid);

        $this->selectall = $selectall;

        // Get the context.
        $this->course = get_course($courseid);
        $context = \context_course::instance($courseid, MUST_EXIST);
        $this->context = $context;

        // Define the headers and columns.
        $headers = [];
        $columns = [];

        if ($bulkoperations) {
            $mastercheckbox = new \core\output\checkbox_toggleall('participants-table', true, [
                'id' => 'select-all-participants',
                'name' => 'select-all-participants',
                'label' => $this->selectall ? get_string('deselectall') : get_string('selectall'),
                'labelclasses' => 'sr-only',
                'classes' => 'm-1',
                'checked' => $this->selectall
            ]);
            $headers[] = $OUTPUT->render($mastercheckbox);
            $columns[] = 'select';
        }

        $headers[] = get_string('fullname');
        $columns[] = 'fullname';

        $extrafields = \core_user\fields::for_identity($context, false)->get_required_fields();
        foreach ($extrafields as $field) {
            $headers[] = \core_user\fields::get_display_name($field);
            $columns[] = $field;
        }

        $headers[] = get_string('roles');
        $columns[] = 'roles';

        // Get the list of fields we have to hide.
        $hiddenfields = array();
        if (!has_capability('moodle/course:viewhiddenuserfields', $context)) {
            $hiddenfields = array_flip(explode(',', $CFG->hiddenuserfields));
        }

        // Add column for groups if the user can view them.
        $canseegroups = !isset($hiddenfields['groups']);
        if ($canseegroups) {
            $headers[] = get_string('groups');
            $columns[] = 'groups';
        }

        // Do not show the columns if it exists in the hiddenfields array.
        if (!isset($hiddenfields['lastaccess'])) {
            if ($courseid == SITEID) {
                $headers[] = get_string('lastsiteaccess');
            } else {
                $headers[] = get_string('lastcourseaccess');
            }
            $columns[] = 'lastaccess';
        }

        $canreviewenrol = has_capability('moodle/course:enrolreview', $context);
        if ($canreviewenrol && $courseid != SITEID) {
            $columns[] = 'status';
            $headers[] = get_string('participationstatus', 'enrol');
            $this->no_sorting('status');
        };

        $this->define_columns($columns);
        $this->define_headers($headers);

        // Make this table sorted by last name by default.
        $this->sortable(true, 'lastname');

        $this->no_sorting('select');
        $this->no_sorting('roles');
        if ($canseegroups) {
            $this->no_sorting('groups');
        }

        $this->set_attribute('id', 'participants');

        // Set the variables we need to use later.
        $this->currentgroup = $currentgroup;
        $this->accesssince = $accesssince;
        $this->roleid = $roleid;
        $this->search = $search;
        $this->enrolid = $enrolid;
        $this->status = $status;
        $this->countries = get_string_manager()->get_list_of_countries(true);
        $this->extrafields = $extrafields;
        $this->context = $context;
        if ($canseegroups) {
            $this->groups = groups_get_all_groups($courseid, 0, 0, 'g.*', true);
        }
        $this->allroles = role_fix_names(get_all_roles($this->context), $this->context);
        $this->assignableroles = get_assignable_roles($this->context, ROLENAME_ALIAS, false);
        $this->profileroles = get_profile_roles($this->context);
        $this->viewableroles = get_viewable_roles($this->context);
    }

    /**
     * Render the participants table.
     *
     * @param int $pagesize Size of page for paginated displayed table.
     * @param bool $useinitialsbar Whether to use the initials bar which will only be used if there is a fullname column defined.
     * @param string $downloadhelpbutton
     */
    public function out($pagesize, $useinitialsbar, $downloadhelpbutton = '') {
        global $PAGE;

        parent::out($pagesize, $useinitialsbar, $downloadhelpbutton);

        if (has_capability('moodle/course:enrolreview', $this->context)) {
            $params = ['contextid' => $this->context->id, 'courseid' => $this->course->id];
            $PAGE->requires->js_call_amd('core_user/status_field', 'init', [$params]);
        }
    }

    /**
     * Generate the select column.
     *
     * @param \stdClass $data
     * @return string
     */
    public function col_select($data) {
        global $OUTPUT;

        $checkbox = new \core\output\checkbox_toggleall('participants-table', false, [
            'classes' => 'usercheckbox m-1',
            'id' => 'user' . $data->id,
            'name' => 'user' . $data->id,
            'checked' => $this->selectall,
            'label' => get_string('selectitem', 'moodle', fullname($data)),
            'labelclasses' => 'accesshide',
        ]);

        return $OUTPUT->render($checkbox);
    }

    /**
     * Generate the fullname column.
     *
     * @param \stdClass $data
     * @return string
     */
    public function col_fullname($data) {
        global $OUTPUT;

        return $OUTPUT->user_picture($data, array('size' => 35, 'courseid' => $this->course->id, 'includefullname' => true));
    }

    /**
     * User roles column.
     *
     * @param \stdClass $data
     * @return string
     */
    public function col_roles($data) {
        global $OUTPUT;

        $roles = isset($this->allroleassignments[$data->id]) ? $this->allroleassignments[$data->id] : [];
        $editable = new \core_user\output\user_roles_editable($this->course,
                                                              $this->context,
                                                              $data,
                                                              $this->allroles,
                                                              $this->assignableroles,
                                                              $this->profileroles,
                                                              $roles,
                                                              $this->viewableroles);

        return $OUTPUT->render_from_template('core/inplace_editable', $editable->export_for_template($OUTPUT));
    }

    /**
     * Generate the groups column.
     *
     * @param \stdClass $data
     * @return string
     */
    public function col_groups($data) {
        global $OUTPUT;

        $usergroups = [];
        foreach ($this->groups as $coursegroup) {
            if (isset($coursegroup->members[$data->id])) {
                $usergroups[] = $coursegroup->id;
            }
        }
        $editable = new \core_group\output\user_groups_editable($this->course, $this->context, $data, $this->groups, $usergroups);
        return $OUTPUT->render_from_template('core/inplace_editable', $editable->export_for_template($OUTPUT));
    }

    /**
     * Generate the country column.
     *
     * @param \stdClass $data
     * @return string
     */
    public function col_country($data) {
        if (!empty($this->countries[$data->country])) {
            return $this->countries[$data->country];
        }
        return '';
    }

    /**
     * Generate the last access column.
     *
     * @param \stdClass $data
     * @return string
     */
    public function col_lastaccess($data) {
        if ($data->lastaccess) {
            return format_time(time() - $data->lastaccess);
        }

        return get_string('never');
    }

    /**
     * Generate the status column.
     *
     * @param \stdClass $data The data object.
     * @return string
     */
    public function col_status($data) {
        global $CFG, $OUTPUT, $PAGE;

        $enrolstatusoutput = '';
        $canreviewenrol = has_capability('moodle/course:enrolreview', $this->context);
        if ($canreviewenrol) {
            $canviewfullnames = has_capability('moodle/site:viewfullnames', $this->context);
            $fullname = fullname($data, $canviewfullnames);
            $coursename = format_string($this->course->fullname, true, array('context' => $this->context));
            require_once($CFG->dirroot . '/enrol/locallib.php');
            $manager = new \course_enrolment_manager($PAGE, $this->course);
            $userenrolments = $manager->get_user_enrolments($data->id);
            foreach ($userenrolments as $ue) {
                $timestart = $ue->timestart;
                $timeend = $ue->timeend;
                $timeenrolled = $ue->timecreated;
                $actions = $ue->enrolmentplugin->get_user_enrolment_actions($manager, $ue);
                $instancename = $ue->enrolmentinstancename;

                // Default status field label and value.
                $status = get_string('participationactive', 'enrol');
                $statusval = status_field::STATUS_ACTIVE;
                switch ($ue->status) {
                    case ENROL_USER_ACTIVE:
                        $currentdate = new DateTime();
                        $now = $currentdate->getTimestamp();
                        $isexpired = $timestart > $now || ($timeend > 0 && $timeend < $now);
                        $enrolmentdisabled = $ue->enrolmentinstance->status == ENROL_INSTANCE_DISABLED;
                        // If user enrolment status has not yet started/already ended or the enrolment instance is disabled.
                        if ($isexpired || $enrolmentdisabled) {
                            $status = get_string('participationnotcurrent', 'enrol');
                            $statusval = status_field::STATUS_NOT_CURRENT;
                        }
                        break;
                    case ENROL_USER_SUSPENDED:
                        $status = get_string('participationsuspended', 'enrol');
                        $statusval = status_field::STATUS_SUSPENDED;
                        break;
                }

                $statusfield = new status_field($instancename, $coursename, $fullname, $status, $timestart, $timeend,
                    $actions, $timeenrolled);
                $statusfielddata = $statusfield->set_status($statusval)->export_for_template($OUTPUT);
                $enrolstatusoutput .= $OUTPUT->render_from_template('core_user/status_field', $statusfielddata);
            }
        }
        return $enrolstatusoutput;
    }

    /**
     * This function is used for the extra user fields.
     *
     * These are being dynamically added to the table so there are no functions 'col_<userfieldname>' as
     * the list has the potential to increase in the future and we don't want to have to remember to add
     * a new method to this class. We also don't want to pollute this class with unnecessary methods.
     *
     * @param string $colname The column name
     * @param \stdClass $data
     * @return string
     */
    public function other_cols($colname, $data) {
        // Do not process if it is not a part of the extra fields.
        if (!in_array($colname, $this->extrafields)) {
            return '';
        }

        return s($data->{$colname});
    }

    /**
     * Query the database for results to display in the table.
     *
     * @param int $pagesize size of page for paginated displayed table.
     * @param bool $useinitialsbar do you want to use the initials bar.
     */
    public function query_db($pagesize, $useinitialsbar = true) {
        list($twhere, $tparams) = $this->get_sql_where();

        $total = user_get_total_participants($this->course->id, $this->currentgroup, $this->accesssince,
            $this->roleid, $this->enrolid, $this->status, $this->search, $twhere, $tparams);

        $this->pagesize($pagesize, $total);

        $sort = $this->get_sql_sort();
        if ($sort) {
            $sort = 'ORDER BY ' . $sort;
        }

        $rawdata = user_get_participants($this->course->id, $this->currentgroup, $this->accesssince,
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

        // Set initial bars.
        if ($useinitialsbar) {
            $this->initialbars(true);
        }
    }

    /**
     * Override the table show_hide_link to not show for select column.
     *
     * @param string $column the column name, index into various names.
     * @param int $index numerical index of the column.
     * @return string HTML fragment.
     */
    protected function show_hide_link($column, $index) {
        if ($index > 0) {
            return parent::show_hide_link($column, $index);
        }
        return '';
    }
}

class participants_table extends _participants_table {
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


function user_get_total_participants($courseid, $groupid = 0, $accesssince = 0, $roleid = 0, $enrolid = 0, $statusid = -1,
        $search = '', $additionalwhere = '', $additionalparams = array()) {
    global $DB;

    list($select, $from, $where, $params) = user_get_participants_sql($courseid, $groupid, $accesssince, $roleid, $enrolid,
            $statusid, $search, $additionalwhere, $additionalparams);

    return $DB->count_records_sql("SELECT COUNT(u.id) $from $where", $params);
}

function user_get_participants_sql($courseid, $groupid = 0, $accesssince = 0, $roleid = 0, $enrolid = 0, $statusid = -1,
        $search = '', $additionalwhere = '', $additionalparams = array()) {
    global $DB, $USER, $CFG;

    // Get the context.
    $context = \context_course::instance($courseid, MUST_EXIST);

    $isfrontpage = ($courseid == SITEID);

    // Default filter settings. We only show active by default, especially if the user has no capability to review enrolments.
    $onlyactive = true;
    $onlysuspended = false;
    if (has_capability('moodle/course:enrolreview', $context) && (has_capability('moodle/course:viewsuspendedusers', $context))) {
        switch ($statusid) {
            case ENROL_USER_ACTIVE:
                // Nothing to do here.
                break;
            case ENROL_USER_SUSPENDED:
                $onlyactive = false;
                $onlysuspended = true;
                break;
            default:
                // If the user has capability to review user enrolments, but statusid is set to -1, set $onlyactive to false.
                $onlyactive = false;
                break;
        }
    }

    list($esql, $params) = get_enrolled_sql($context, null, $groupid, $onlyactive, $onlysuspended, $enrolid);

    $joins = array('FROM {user} u');
    $wheres = array();

    // TODO Does not support custom user profile fields (MDL-70456).
    $userfields = \core_user\fields::get_identity_fields($context, false);
    $userfieldsapi = \core_user\fields::for_userpic()->including(...$userfields);
    $userfieldssql = $userfieldsapi->get_sql('u', false, '', '', false)->selects;

    if ($isfrontpage) {
        $select = "SELECT $userfieldssql, u.lastaccess";
        $joins[] = "JOIN ($esql) e ON e.id = u.id"; // Everybody on the frontpage usually.
        if ($accesssince) {
            $wheres[] = user_get_user_lastaccess_sql($accesssince);
        }
    } else {
        $select = "SELECT $userfieldssql, COALESCE(ul.timeaccess, 0) AS lastaccess";
        $joins[] = "JOIN ($esql) e ON e.id = u.id"; // Course enrolled users only.
        // Not everybody has accessed the course yet.
        $joins[] = 'LEFT JOIN {user_lastaccess} ul ON (ul.userid = u.id AND ul.courseid = :courseid)';
        $params['courseid'] = $courseid;
        if ($accesssince) {
            $wheres[] = user_get_course_lastaccess_sql($accesssince);
        }
    }

    // Performance hacks - we preload user contexts together with accounts.
    $ccselect = ', ' . \context_helper::get_preload_record_columns_sql('ctx');
    $ccjoin = 'LEFT JOIN {context} ctx ON (ctx.instanceid = u.id AND ctx.contextlevel = :contextlevel)';
    $params['contextlevel'] = CONTEXT_USER;
    $select .= $ccselect;
    $joins[] = $ccjoin;

    // Limit list to users with some role only.
    if ($roleid) {
        // We want to query both the current context and parent contexts.
        list($relatedctxsql, $relatedctxparams) = $DB->get_in_or_equal($context->get_parent_context_ids(true),
                SQL_PARAMS_NAMED, 'relatedctx');

        // Get users without any role.
        if ($roleid == -1) {
            $wheres[] = "u.id NOT IN (SELECT userid FROM {role_assignments} WHERE contextid $relatedctxsql)";
            $params = array_merge($params, $relatedctxparams);
        } else {
            $wheres[] = "u.id IN (SELECT userid FROM {role_assignments} WHERE roleid = :roleid AND contextid $relatedctxsql)";
            $params = array_merge($params, array('roleid' => $roleid), $relatedctxparams);
        }
    }

    if (!empty($search)) {
        if (!is_array($search)) {
            $search = [$search];
        }
        foreach ($search as $index => $keyword) {
            $searchkey1 = 'search' . $index . '1';
            $searchkey2 = 'search' . $index . '2';
            $searchkey3 = 'search' . $index . '3';
            $searchkey4 = 'search' . $index . '4';
            $searchkey5 = 'search' . $index . '5';
            $searchkey6 = 'search' . $index . '6';
            $searchkey7 = 'search' . $index . '7';

            $conditions = array();
            // Search by fullname.
            $fullname = $DB->sql_fullname('u.firstname', 'u.lastname');
            $conditions[] = $DB->sql_like($fullname, ':' . $searchkey1, false, false);

            // Search by email.
            $email = $DB->sql_like('email', ':' . $searchkey2, false, false);
            if (!in_array('email', $userfields)) {
                $maildisplay = 'maildisplay' . $index;
                $userid1 = 'userid' . $index . '1';
                // Prevent users who hide their email address from being found by others
                // who aren't allowed to see hidden email addresses.
                $email = "(". $email ." AND (" .
                        "u.maildisplay <> :$maildisplay " .
                        "OR u.id = :$userid1". // User can always find himself.
                        "))";
                $params[$maildisplay] = \core_user::MAILDISPLAY_HIDE;
                $params[$userid1] = $USER->id;
            }
            $conditions[] = $email;

            // Search by idnumber.
            $idnumber = $DB->sql_like('idnumber', ':' . $searchkey3, false, false);
            if (!in_array('idnumber', $userfields)) {
                $userid2 = 'userid' . $index . '2';
                // Users who aren't allowed to see idnumbers should at most find themselves
                // when searching for an idnumber.
                $idnumber = "(". $idnumber . " AND u.id = :$userid2)";
                $params[$userid2] = $USER->id;
            }
            $conditions[] = $idnumber;

            // TODO Does not support custom user profile fields (MDL-70456).
            $extrasearchfields = \core_user\fields::get_identity_fields($context, false);
            if (!empty($extrasearchfields)) {
                // Search all user identify fields.
                foreach ($extrasearchfields as $extrasearchfield) {
                    if (in_array($extrasearchfield, ['email', 'idnumber', 'country'])) {
                        // Already covered above. Search by country not supported.
                        continue;
                    }
                    $param = $searchkey3 . $extrasearchfield;
                    $condition = $DB->sql_like($extrasearchfield, ':' . $param, false, false);
                    $params[$param] = "%$keyword%";
                    if (!in_array($extrasearchfield, $userfields)) {
                        // User cannot see this field, but allow match if their own account.
                        $userid3 = 'userid' . $index . '3' . $extrasearchfield;
                        $condition = "(". $condition . " AND u.id = :$userid3)";
                        $params[$userid3] = $USER->id;
                    }
                    $conditions[] = $condition;
                }
            }

            // Search by middlename.
            $middlename = $DB->sql_like('middlename', ':' . $searchkey4, false, false);
            $conditions[] = $middlename;

            // Search by alternatename.
            $alternatename = $DB->sql_like('alternatename', ':' . $searchkey5, false, false);
            $conditions[] = $alternatename;

            // Search by firstnamephonetic.
            $firstnamephonetic = $DB->sql_like('firstnamephonetic', ':' . $searchkey6, false, false);
            $conditions[] = $firstnamephonetic;

            // Search by lastnamephonetic.
            $lastnamephonetic = $DB->sql_like('lastnamephonetic', ':' . $searchkey7, false, false);
            $conditions[] = $lastnamephonetic;

            $wheres[] = "(". implode(" OR ", $conditions) .") ";
            $params[$searchkey1] = "%$keyword%";
            $params[$searchkey2] = "%$keyword%";
            $params[$searchkey3] = "%$keyword%";
            $params[$searchkey4] = "%$keyword%";
            $params[$searchkey5] = "%$keyword%";
            $params[$searchkey6] = "%$keyword%";
            $params[$searchkey7] = "%$keyword%";
        }
    }

    if (!empty($additionalwhere)) {
        $wheres[] = $additionalwhere;
        $params = array_merge($params, $additionalparams);
    }

    $from = implode("\n", $joins);
    if ($wheres) {
        $where = 'WHERE ' . implode(' AND ', $wheres);
    } else {
        $where = '';
    }

    return array($select, $from, $where, $params);
}