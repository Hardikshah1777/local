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
 * Define all the restore steps that will be used by the restore_behaviour_activity_task
 *
 * @package    mod_behaviour
 * @copyright  2011 Artem Andreev <andreev.artem@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/behaviour/backup/moodle2/restore_behaviour_stepslib.php');

/**
 * Behaviour restore task that provides all the settings and steps to perform one complete restore of the activity
 *
 * @copyright  2011 Artem Andreev <andreev.artem@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_behaviour_activity_task extends restore_activity_task {

    /**
     * Define (add) particular settings this activity can have
     */
    protected function define_my_settings() {
    }

    /**
     * Define (add) particular steps this activity can have
     */
    protected function define_my_steps() {
        $this->add_step(new restore_behaviour_activity_structure_step('behaviour_structure', 'behaviour.xml'));
    }

    /**
     * Define the contents in the activity that must be
     * processed by the link decoder
     */
    public static function define_decode_contents() {
        $contents = array();

        $contents[] = new restore_decode_content('behaviour_sessions',
                          array('description'), 'behaviour_session');

        return $contents;
    }

    /**
     * Define the decoding rules for links belonging
     * to the activity to be executed by the link decoder
     */
    public static function define_decode_rules() {
        $rules = array();

        $rules[] = new restore_decode_rule('BEHAVIOURVIEWBYID',
                    '/mod/behaviour/view.php?id=$1', 'course_module');
        $rules[] = new restore_decode_rule('BEHAVIOURVIEWBYIDSTUD',
                    '/mod/behaviour/view.php?id=$1&studentid=$2', array('course_module', 'user'));

        // Older style backups using previous plugin name.
        $rules[] = new restore_decode_rule('ATTFORBLOCKVIEWBYID',
            '/mod/behaviour/view.php?id=$1', 'course_module');
        $rules[] = new restore_decode_rule('ATTFORBLOCKVIEWBYIDSTUD',
            '/mod/behaviour/view.php?id=$1&studentid=$2', array('course_module', 'user'));

        return $rules;

    }

    /**
     * Define the restore log rules that will be applied
     * by the {@see restore_logs_processor} when restoring
     * behaviour logs. It must return one array
     * of {@see restore_log_rule} objects
     */
    public static function define_restore_log_rules() {
        $rules = array();

        // TODO: log restore.
        return $rules;
    }

    /**
     * Define the restore log rules that will be applied
     * by the {@see restore_logs_processor} when restoring
     * course logs. It must return one array
     * of {@see restore_log_rule} objects
     *
     * Note this rules are applied when restoring course logs
     * by the restore final task, but are defined here at
     * activity level. All them are rules not linked to any module instance (cmid = 0)
     */
    public static function define_restore_log_rules_for_course() {
        $rules = array();

        return $rules;
    }

    /**
     * After restore - clean up any incorrect calendar items that have been restored.
     * @throws dml_exception
     */
    public function after_restore() {
        global $DB;
        $behaviourid = $this->get_activityid();
        $courseid = $this->get_courseid();
        if (empty($courseid) || empty($behaviourid)) {
            return;
        }
        if (empty(get_config('behaviour', 'enablecalendar'))) {
            // Behaviour isn't using Calendar - delete anything that was created.
            $DB->delete_records('event', ['modulename' => 'behaviour', 'instance' => $behaviourid, 'courseid' => $courseid]);
        } else {
            // Clean up any orphaned events.
            $sql = "modulename = 'behaviour' AND courseid = :courseid AND id NOT IN (SELECT s.caleventid
                                                                                        FROM {behaviour_sessions} s
                                                                                        JOIN {behaviour} a on a.id = s.behaviourid
                                                                                       WHERE a.course = :courseid2)";
            $params = ['courseid' => $courseid, 'courseid2' => $courseid];
            $DB->delete_records_select('event', $sql, $params);
        }
    }
}
