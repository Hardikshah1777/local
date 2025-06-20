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
 * Structure step to restore one behaviour activity
 *
 * @package    mod_behaviour
 * @copyright  2011 Artem Andreev <andreev.artem@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define all the restore steps that will be used by the restore_behaviour_activity_task
 *
 * @copyright  2011 Artem Andreev <andreev.artem@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_behaviour_activity_structure_step extends restore_activity_structure_step {

    /**
     * Define the structure of the restore workflow.
     *
     * @return restore_path_element $structure
     */
    protected function define_structure() {

        $paths = array();

        $userinfo = $this->get_setting_value('userinfo'); // Are we including userinfo?

        // XML interesting paths - non-user data.
        $paths[] = new restore_path_element('behaviour', '/activity/behaviour');

        $paths[] = new restore_path_element('behaviour_status',
                       '/activity/behaviour/statuses/status');

        $paths[] = new restore_path_element('behaviour_warning',
            '/activity/behaviour/warnings/warning');

        $paths[] = new restore_path_element('behaviour_session',
                       '/activity/behaviour/sessions/session');

        $paths[] = new restore_path_element('customfield',
                       '/activity/behaviour/customfields/customfield');

        // End here if no-user data has been selected.
        if (!$userinfo) {
            return $this->prepare_activity_structure($paths);
        }

        // XML interesting paths - user data.
        $paths[] = new restore_path_element('behaviour_log',
                       '/activity/behaviour/sessions/session/logs/log');

        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Process an behaviour restore.
     *
     * @param object $data The data in object form
     * @return void
     */
    protected function process_behaviour($data) {
        global $DB;

        $data = (object)$data;
        $data->course = $this->get_courseid();

        // Insert the behaviour record.
        $newitemid = $DB->insert_record('behaviour', $data);
        // Immediately after inserting "activity" record, call this.
        $this->apply_activity_instance($newitemid);
    }

    /**
     * Process behaviour status restore
     * @param object $data The data in object form
     * @return void
     */
    protected function process_behaviour_status($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->behaviourid = $this->get_new_parentid('behaviour');

        $newitemid = $DB->insert_record('behaviour_statuses', $data);
        $this->set_mapping('behaviour_status', $oldid, $newitemid);
    }

    /**
     * Process behaviour warning restore
     * @param object $data The data in object form
     * @return void
     */
    protected function process_behaviour_warning($data) {
        global $DB;

        $data = (object)$data;

        $data->idnumber = $this->get_new_parentid('behaviour');

        $DB->insert_record('behaviour_warning', $data);
    }

    /**
     * Process behaviour session restore
     * @param object $data The data in object form
     * @return void
     */
    protected function process_behaviour_session($data) {
        global $DB;

        $userinfo = $this->get_setting_value('userinfo'); // Are we including userinfo?

        $data = (object)$data;
        $oldid = $data->id;

        $data->behaviourid = $this->get_new_parentid('behaviour');
        $data->groupid = $this->get_mappingid('group', $data->groupid);
        $data->sessdate = $this->apply_date_offset($data->sessdate);
        $data->timemodified = $this->apply_date_offset($data->timemodified);
        $data->caleventid = $this->get_mappingid('event', $data->caleventid);

        if ($userinfo) {
            $data->lasttaken = $this->apply_date_offset($data->lasttaken);
            $data->lasttakenby = $this->get_mappingid('user', $data->lasttakenby);
        } else {
            $data->lasttaken = 0;
            $data->lasttakenby = 0;
        }

        $newitemid = $DB->insert_record('behaviour_sessions', $data);
        $data->id = $newitemid;
        $this->set_mapping('behaviour_session', $oldid, $newitemid, true);

        // Create Calendar event.
        behaviour_create_calendar_event($data);
    }

    /**
     * Process behaviour log restore
     * @param object $data The data in object form
     * @return void
     */
    protected function process_behaviour_log($data) {
        global $DB;

        $data = (object)$data;

        $data->sessionid = $this->get_mappingid('behaviour_session', $data->sessionid);
        $data->studentid = $this->get_mappingid('user', $data->studentid);
        $data->statusid = $this->get_mappingid('behaviour_status', $data->statusid);
        $statusset = explode(',', $data->statusset);
        foreach ($statusset as $st) {
            $st = $this->get_mappingid('behaviour_status', $st);
        }
        $data->statusset = implode(',', $statusset);
        $data->timetaken = $this->apply_date_offset($data->timetaken);
        $data->takenby = $this->get_mappingid('user', $data->takenby);

        $DB->insert_record('behaviour_log', $data);
    }

    /**
     * Process custom fields
     *
     * @param array $data
     */
    public function process_customfield($data) {
        $handler = mod_behaviour\customfield\session_handler::create();
        $data['sessionid'] = $this->get_mappingid('behaviour_session', $data['sessionid']);
        $handler->restore_instance_data_from_backup($this->task, $data);
    }

    /**
     * Once the database tables have been fully restored, restore the files and clean up any calendar stuff.
     * @return void
     */
    protected function after_execute() {
        $this->add_related_files('mod_behaviour', 'session', 'behaviour_session');
    }
}
