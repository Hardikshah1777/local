<?php

namespace local_userstatus\output\user;

use local_userstatus\status;

class renderer extends \core_user_renderer {
    public function unified_filter($course, $context, $filtersapplied, $baseurl = null) {
        global $CFG, $DB, $USER;

        require_once($CFG->dirroot . '/enrol/locallib.php');
        require_once($CFG->dirroot . '/lib/grouplib.php');
        $manager = new \course_enrolment_manager($this->page, $course);

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

        $indexpage = new unified_filter($filteroptions, $filtersapplied, $baseurl);
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

class unified_filter implements \renderable, \templatable {

    /** @var array $filteroptions The filter options. */
    protected $filteroptions;

    /** @var array $selectedoptions The list of selected filter option values. */
    protected $selectedoptions;

    /** @var moodle_url|string $baseurl The url with params needed to call up this page. */
    protected $baseurl;

    /**
     * unified_filter constructor.
     *
     * @param array $filteroptions The filter options.
     * @param array $selectedoptions The list of selected filter option values.
     * @param string|moodle_url $baseurl The url with params needed to call up this page.
     */
    public function __construct($filteroptions, $selectedoptions, $baseurl = null) {

        $this->filteroptions = $filteroptions;
        $this->selectedoptions = $selectedoptions;
        if (!empty($baseurl)) {
            $this->baseurl = new \moodle_url($baseurl);
        }
    }

    /**
     * Function to export the renderer data in a format that is suitable for a mustache template.
     *
     * @param renderer_base $output Used to do a final render of any components that need to be rendered for export.
     * @return stdClass|array
     */
    public function export_for_template(\renderer_base $output) {
        global $PAGE;
        $data = new \stdClass();
        if (empty($this->baseurl)) {
            $this->baseurl = $PAGE->url;
        }
        $data->action = $this->baseurl->out(false);

        foreach ($this->selectedoptions as $option) {
            if (!isset($this->filteroptions[$option])) {
                $this->filteroptions[$option] = $option;
            }
        }

        $data->filteroptions = [];
        $originalfilteroptions = [];
        foreach ($this->filteroptions as $value => $label) {
            $selected = in_array($value, $this->selectedoptions);
            $filteroption = (object)[
                    'value' => $value,
                    'label' => $label
            ];
            $originalfilteroptions[] = $filteroption;
            $filteroption->selected = $selected;
            $data->filteroptions[] = $filteroption;
        }
        $data->originaloptionsjson = json_encode($originalfilteroptions);
        return $data;
    }
}