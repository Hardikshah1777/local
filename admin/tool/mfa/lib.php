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
 * Moodle MFA plugin lib
 *
 * @package     tool_mfa
 * @author      Mikhail Golenkov <golenkovm@gmail.com>
 * @copyright   Catalyst IT
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Main hook.
 *
 * @return void
 * @throws \moodle_exception
 */
function tool_mfa_after_require_login($courseorid = null, $autologinguest = null, $cm = null,
    $setwantsurltome = null, $preventredirect = null) {

    global $SESSION, $DB, $USER;
    // Tests for hooks being fired to test patches.
    if (PHPUNIT_TEST) {
        $SESSION->mfa_login_hook_test = true;
    }
    $course = get_config('tool_mfa','course');
    $enrol = false;
    if (!empty($course)) {
        $courseid = explode(',', $course);
        $enrol = $DB->record_exists_sql("SELECT 1 FROM {user_enrolments} ue
                JOIN {enrol} e ON e.id = ue.enrolid
                WHERE ue.userid = ? AND ue.status = 0 AND e.status = 0
                AND e.courseid IN (" . join(',', $courseid) . ")"
                , [$USER->id]);
    }
    $skipuser = $DB->record_exists('tool_mfa_skipusers', ['userid' => $USER->id, 'status' => 1]);
    if (1 && empty($skipuser)) {
        \tool_mfa\manager::require_auth($courseorid, $autologinguest, $cm, $setwantsurltome, $preventredirect);
    }
}

/**
 * Extends navigation bar and injects MFA Preferences menu to user preferences.
 *
 * @param navigation_node $navigation
 * @param stdClass $user
 * @param context_user $usercontext
 * @param stdClass $course
 * @param context_course $coursecontext
 *
 * @return void or null
 * @throws \moodle_exception
 */
function tool_mfa_extend_navigation_user_settings($navigation, $user, $usercontext, $course, $coursecontext) {
    global $PAGE;

    // Only inject if user is on the preferences page.
    $onpreferencepage = $PAGE->url->compare(new moodle_url('/user/preferences.php'), URL_MATCH_BASE);
    if (!$onpreferencepage) {
        return null;
    }

    if (\tool_mfa\manager::is_ready() && \tool_mfa\manager::possible_factor_setup()) {
        $url = new moodle_url('/admin/tool/mfa/user_preferences.php');
        $node = navigation_node::create(get_string('preferences:header', 'tool_mfa'), $url,
            navigation_node::TYPE_SETTING);
        $usernode = $navigation->find('useraccount', navigation_node::TYPE_CONTAINER);
        $usernode->add_node($node);
    }
}

function tool_mfa_after_config() {
    global $DB, $CFG, $SESSION, $USER;

    // Tests for hooks being fired to test patches.
    // Store in $CFG, $SESSION not present at this point.
    if (PHPUNIT_TEST) {
        $CFG->mfa_config_hook_test = true;
    }

    // Check for not logged in.
    if (isloggedin() && !isguestuser()) {
        // If not authenticated, force login required.

        $course = get_config('tool_mfa','course');
        $enrol = false;
        if (!empty($course)) {
            $courseid = explode(',', $course);
            $enrol = $DB->record_exists_sql("SELECT 1 FROM {user_enrolments} ue
                JOIN {enrol} e ON e.id = ue.enrolid
                WHERE ue.userid = ? AND ue.status = 0 AND e.status = 0
                AND e.courseid IN (" . join(',', $courseid) . ")"
                    , [$USER->id]);
        }


        if (empty($SESSION->tool_mfa_authenticated)) {
            if(is_siteadmin() || $enrol){
                \tool_mfa\manager::require_auth();
            }
        }
    }
}
