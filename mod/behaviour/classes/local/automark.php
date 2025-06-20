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
 * Aehaviour module renderable component.
 *
 * @package    mod_behaviour
 * @copyright  2022 Dan Marsden
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_behaviour\local;

use stdClass;
/**
 * Url helpers
 *
 * @copyright  2011 Artem Andreev <andreev.artem@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class automark {
    /**
     * Auto mark a specifc session.
     *
     * @param stdClass $session
     * @param stdClass $course
     * @param stdClass $cm
     * @param stdClass $behaviour
     * @param boolean $returnerrors
     * @return string|void
     */
    public static function session($session, $course, $cm, $behaviour, $returnerrors = false) {
        global $CFG, $DB;
        $now = time(); // Store current time to use in queries so they all match nicely.
        if ($session->sessdate + $session->duration < $now || // If session is over.
            // OR if session is currently open and automark is set to do all.
            ($session->sessdate < $now && $session->automark == BEHAVIOUR_AUTOMARK_ALL)) {

            $userfirstaccess = [];
            $donesomething = false; // Only trigger grades/events when an update actually occurs.
            $sessionover = false; // Is this session over?
            if ($session->sessdate + $session->duration < $now) {
                $sessionover = true;
            }
            if (!isset($session->setunmarked)) {
                // Setunmarked not included in $session var, lets look it up.
                $session->setunmarked = $DB->get_field('behaviour_statuses', 'id',
                 ['behaviourid' => $behaviour->id, 'setunmarked' => 1, 'deleted' => 0, 'setnumber' => $session->statusset]);
            }

            if (empty($session->setunmarked)) {
                $coursemodule = get_coursemodule_from_instance('behaviour', $session->behaviourid, $course->id);
                $a = new stdClass;
                $a->sessionid = $session->id;
                $a->url = $CFG->wwwroot.'/mod/behaviour/preferences.php?id='.$coursemodule->id;;
                if (!$returnerrors) {
                    mtrace(get_string('nounmarkedstatusset', 'behaviour', $a));
                    return;
                } else {
                    return get_string('nounmarkedstatusset', 'behaviour', $a);
                }
            }

            $context = \context_module::instance($cm->id);

            $pageparams = new \mod_behaviour_take_page_params();
            $pageparams->group = $session->groupid;
            if (empty($session->groupid)) {
                $pageparams->grouptype  = 0;
            } else {
                $pageparams->grouptype  = 1;
            }
            $pageparams->sessionid  = $session->id;

            $att = new \mod_behaviour_structure($behaviour, $cm, $course, $context, $pageparams);

            if ($session->automark == BEHAVIOUR_AUTOMARK_ALL) {
                $userfirstaccess = [];
                // If set to do full automarking, get all users that have accessed course during session open.
                $id = $DB->sql_concat('userid', 'ip'); // Users may access from multiple ip, make the first field unique.
                $sql = "SELECT $id, userid, ip, min(timecreated) as timecreated
                         FROM {logstore_standard_log}
                        WHERE courseid = ? AND timecreated > ? AND timecreated < ?
                     GROUP BY userid, ip";

                $timestart = $session->sessdate;
                if (empty($session->lasttakenby) && $session->lasttaken > $timestart) {
                    // If the last time session was taken it was done automatically, use the last time taken
                    // as the start time for the logs we are interested in to help with performance.
                    $timestart = $session->lasttaken;
                }
                $duration = $session->duration;
                if (empty($duration)) {
                    $duration = get_config('behaviour', 'studentscanmarksessiontimeend') * 60;
                }
                $timeend = $timestart + $duration;
                $logusers = $DB->get_recordset_sql($sql, array($course->id, $timestart, $timeend));
                // Check if user access is in allowed subnet.
                foreach ($logusers as $loguser) {
                    if (!empty($session->subnet) && !address_in_subnet($loguser->ip, $session->subnet)) {
                        // This record isn't in the right subnet.
                        continue;
                    }
                    if (empty($userfirstaccess[$loguser->userid]) ||
                        $userfirstaccess[$loguser->userid] > $loguser->timecreated) {
                        // Users may have accessed from mulitple ip addresses, find the earliest access.
                        $userfirstaccess[$loguser->userid] = $loguser->timecreated;
                    }
                }
                $logusers->close();

            } else if ($session->automark == BEHAVIOUR_AUTOMARK_ACTIVITYCOMPLETION) {
                $existinglog = $DB->get_records_menu('behaviour_log',
                    ['sessionid' => $session->id], '', 'studentid, statusid');

                $newlogs = [];

                // Get users who have completed the course in this session.
                $completedusers = $DB->get_records_select('course_modules_completion',
                    'coursemoduleid = ? AND completionstate > 0', [$session->automarkcmid]);

                // Get automark status the users and update the behaviour log.
                foreach ($completedusers as $completionuser) {
                    if (empty($completionuser->timemodified) || (empty($completionuser->userid))) {
                        // Time modified or userid not set - we can't calculate for this record.
                        continue;
                    }
                    if (!empty($existinglog[$completionuser->userid])) {
                        // Status already set for this user.
                        continue;
                    }
                    if (!has_capability('mod/behaviour:canbelisted', $context, $completionuser->userid)) {
                        // This user can't be listed in this behaviour - skip them.
                        continue;
                    }
                    if (!empty($session->groupid) && !groups_is_member($session->groupid, $completionuser->userid)) {
                        // This is a group session, and the user is not a member of the group.
                        continue;
                    }
                    $newlog = new \stdClass();
                    $newlog->timetaken = $now;
                    $newlog->takenby = 0;
                    $newlog->sessionid = $session->id;
                    $newlog->remarks = get_string('autorecorded', 'behaviour');
                    $newlog->statusset = implode(',', array_keys( (array)$att->get_statuses()));
                    $newlog->studentid = $completionuser->userid;
                    $newlog->statusid = $att->get_automark_status($completionuser->timemodified, $session->id);
                    if (!empty($newlog->statusid)) {
                        $newlogs[] = $newlog;
                    }
                }
                if (!empty($newlogs)) {
                    $DB->insert_records('behaviour_log', $newlogs);
                }
            }

            // Get all unmarked students.
            $users = $att->get_users($session->groupid, 0);

            $existinglog = $DB->get_recordset('behaviour_log', array('sessionid' => $session->id));
            $updated = 0;

            foreach ($existinglog as $log) {
                if (empty($log->statusid)) {
                    if ($sessionover || !empty($userfirstaccess[$log->studentid])) {
                        // Status needs updating.
                        if (!empty($userfirstaccess[$log->studentid])) {
                            $log->statusid = $att->get_automark_status($userfirstaccess[$log->studentid], $session->id);
                        } else if ($sessionover) {
                            $log->statusid = $session->setunmarked;
                        }
                        if (!empty($log->statusid)) {
                            $log->timetaken = $now;
                            $log->takenby = 0;
                            $log->remarks = get_string('autorecorded', 'behaviour');

                            $DB->update_record('behaviour_log', $log);
                            $updated++;
                            $donesomething = true;
                        }
                    }
                }
                unset($users[$log->studentid]);
            }
            $existinglog->close();
            if (!$returnerrors) {
                mtrace($updated . " session status updated");
            }

            $newlogs = [];

            $added = 0;
            foreach ($users as $user) {
                if ($sessionover || !empty($userfirstaccess[$user->id])) {
                    $newlog = new \stdClass();
                    $newlog->timetaken = $now;
                    $newlog->takenby = 0;
                    $newlog->sessionid = $session->id;
                    $newlog->remarks = get_string('autorecorded', 'behaviour');
                    $newlog->statusset = implode(',', array_keys( (array)$att->get_statuses()));
                    if (!empty($userfirstaccess[$user->id])) {
                        $newlog->statusid = $att->get_automark_status($userfirstaccess[$user->id], $session->id);
                    } else if ($sessionover) {
                        $newlog->statusid = $session->setunmarked;
                    }
                    if (!empty($newlog->statusid)) {
                        $newlog->studentid = $user->id;
                        $newlogs[] = $newlog;
                        $added++;
                    }
                }
            }
            if (!empty($newlogs)) {
                $DB->insert_records('behaviour_log', $newlogs);
                $donesomething = true;
            }
            if (!$returnerrors) {
                mtrace($added . " session status inserted");
            }

            // Update lasttaken time and automarkcompleted for this session.
            $session->lasttaken = $now;
            $session->lasttakenby = 0;
            if ($sessionover) {
                $session->automarkcompleted = 2;
            } else {
                $session->automarkcompleted = 1;
            }

            $DB->update_record('behaviour_sessions', $session);

            if ($donesomething) {
                if ($att->grade != 0) {
                    $att->update_users_grade(array_keys($users));
                }

                $params = array(
                    'sessionid' => $att->pageparams->sessionid,
                    'grouptype' => $att->pageparams->grouptype);
                $event = \mod_behaviour\event\behaviour_taken::create(array(
                    'objectid' => $att->id,
                    'context' => $att->context,
                    'other' => $params));
                $event->add_record_snapshot('course_modules', $att->cm);
                $event->add_record_snapshot('behaviour_sessions', $session);
                $event->trigger();
            }
        }
    }
}
