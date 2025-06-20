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
 * @package    mod_progressreport
 * @subpackage backup-moodle2
 * @copyright 2010 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define all the restore steps that will be used by the restore_progressreport_activity_task
 */

/**
 * Structure step to restore one evaluation activity
 */
class restore_progressreport_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {

        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        $progressreport = new restore_path_element('progressreport', '/activity/progressreport');
        $paths[] = $progressreport;

        $market = new restore_path_element('progressreport_market', '/activity/progressreport/markets/market');
        $paths[] = $market;

        $field = new restore_path_element('progressreport_field', '/activity/progressreport/fields/field');
        $paths[] = $field;

        $section = new restore_path_element('progressreport_section', '/activity/progressreport/sections/section');
        $paths[] = $section;

        $skill = new restore_path_element('progressreport_skill', '/activity/progressreport/sections/section/skills/skill');
        $paths[] = $skill;

        if ($userinfo) {

            $user = new restore_path_element('progressreport_user','/activity/progressreport/users/user');
            $paths[] = $user;

            $user_skill_level = new restore_path_element('progressreport_user_skill','/activity/progressreport/users/user/user_skills/user_skill');
            $paths[] = $user_skill_level;

            $userfield_info = new restore_path_element('progressreport_field_info','/activity/progressreport/users/user/field_infos/field_info');
            $paths[] = $userfield_info;

            $userlesson = new restore_path_element('progressreport_user_lesson','/activity/progressreport/users/user/user_lessons/user_lesson');
            $paths[] = $userlesson;
        }
        // Return the paths wrapped into standard activity structure
        return $this->prepare_activity_structure($paths);
    }

    protected function process_progressreport($data) {
        global $DB;

        $data = (object)$data;
        $data->course = $this->get_courseid();
        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        // insert the evaluation record
        $newitemid = $DB->insert_record('progressreport', $data);
        $this->apply_activity_instance($newitemid);
    }

    protected function process_progressreport_market($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->progressreportid = $this->get_new_parentid('progressreport');
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        $newitemid = $DB->insert_record('progressreport_market', $data);
        $this->set_mapping('progressreport_market', $oldid, $newitemid);
    }

    protected function process_progressreport_field($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->progressreportid = $this->get_new_parentid('progressreport');
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        $newitemid = $DB->insert_record('progressreport_field', $data);
        $this->set_mapping('progressreport_field', $oldid, $newitemid);
    }

    protected function process_progressreport_section($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->progressreportid = $this->get_new_parentid('progressreport');
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        $newitemid = $DB->insert_record('progressreport_section', $data);
        $this->set_mapping('progressreport_section', $oldid, $newitemid);
    }

    protected function process_progressreport_skill($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->sectionid = $this->get_new_parentid('progressreport_section');
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        $newitemid = $DB->insert_record('progressreport_skill', $data);
        $this->set_mapping('progressreport_skill', $oldid, $newitemid);
    }

    protected function process_progressreport_user($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->progressreportid = $this->get_new_parentid('progressreport');
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        $newitemid = $DB->insert_record('progressreport_user', $data);
        $this->set_mapping('progressreport_user', $oldid, $newitemid);
    }

   protected function process_progressreport_user_skill($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->skillid = $this->get_mappingid('progressreport_skill', $data->skillid);
        $data->marketid = $this->get_mappingid('progressreport_market', $data->marketid);
        $data->progressreportuserid = $this->get_mappingid('progressreport_user', $data->progressreportuserid);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        $newitemid = $DB->insert_record('progressreport_user_skill', $data);
        $this->set_mapping('progressreport_user_skill', $oldid, $newitemid);
    }

    protected function process_progressreport_field_info($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->progressreportuserid = $this->get_mappingid('progressreport_user', $data->progressreportuserid);
        $data->fieldid = $this->get_mappingid('progressreport_field', $data->fieldid);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        $newitemid = $DB->insert_record('progressreport_field_info', $data);
        $this->set_mapping('progressreport_field_info', $oldid, $newitemid);
    }

    protected function process_progressreport_user_lesson($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->progressreportuserid = $this->get_mappingid('progressreport_user', $data->progressreportuserid);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        $newitemid = $DB->insert_record('progressreport_user_lesson', $data);
        $this->set_mapping('progressreport_user_lesson', $oldid, $newitemid);
    }

    protected function after_execute() {
        // Add evaluation related files, no need to match by itemname (just internally handled context)
        $this->add_related_files('mod_progressreport', 'intro', null);
    }
}
