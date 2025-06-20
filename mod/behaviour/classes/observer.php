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
 * Event observers supported by this module
 *
 * @package    mod_behaviour
 * @copyright  2017 Dan Marsden
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Event observers supported by this module
 *
 * @package    mod_behaviour
 * @copyright  2017 Dan Marsden
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_behaviour_observer {

    /**
     * Observer for the event course_content_deleted - delete all behaviour stuff.
     *
     * @param \core\event\course_content_deleted $event
     */
    public static function course_content_deleted(\core\event\course_content_deleted $event) {
        global $DB;

        $attids = array_keys($DB->get_records('behaviour', array('course' => $event->objectid), '', 'id'));
        $sessids = array_keys($DB->get_records_list('behaviour_sessions', 'behaviourid', $attids, '', 'id'));
        if (behaviour_existing_calendar_events_ids($sessids)) {
            behaviour_delete_calendar_events($sessids);
        }
        if ($sessids) {
            $DB->delete_records_list('behaviour_log', 'sessionid', $sessids);
        }
        if ($attids) {
            $DB->delete_records_list('behaviour_statuses', 'behaviourid', $attids);
            $DB->delete_records_list('behaviour_sessions', 'behaviourid', $attids);
        }
    }
}
