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
 * @package    mod_evaluation
 * @subpackage backup-moodle2
 * @copyright 2010 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define all the restore steps that will be used by the restore_evaluation_activity_task
 */

/**
 * Structure step to restore one evaluation activity
 */
class restore_evaluation_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {

        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        $evaluation = new restore_path_element('evaluation', '/activity/evaluation');
        $paths[] = $evaluation;

        $level = new restore_path_element('evaluation_level', '/activity/evaluation/levels/level');
        $paths[] = $level;

        $userinfonew = new restore_path_element('evaluation_userinfo', '/activity/evaluation/userinfos/userinfo');
        $paths[] = $userinfonew;

        $section = new restore_path_element('evaluation_section', '/activity/evaluation/sections/section');
        $paths[] = $section;

        $skill = new restore_path_element('evaluation_skill', '/activity/evaluation/sections/section/skills/skill');
        $paths[] = $skill;

        if ($userinfo) {

            $user = new restore_path_element('evaluation_user','/activity/evaluation/users/user');
            $paths[] = $user;

            $user_skill_level = new restore_path_element('evaluation_user_skill_level','/activity/evaluation/users/user/user_skill_levels/user_skill_level');
            $paths[] = $user_skill_level;

            $userfield_info = new restore_path_element('evaluation_userfields_info','/activity/evaluation/users/user/userfields_infos/userfields_info');
            $paths[] = $userfield_info;
        }
        // Return the paths wrapped into standard activity structure
        return $this->prepare_activity_structure($paths);
    }

    protected function process_evaluation($data) {
        global $DB;

        $data = (object)$data;
        $data->course = $this->get_courseid();
        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        // insert the evaluation record
        $newitemid = $DB->insert_record('evaluation', $data);
        $this->apply_activity_instance($newitemid);
    }

    protected function process_evaluation_level($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->evaluationid = $this->get_new_parentid('evaluation');
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        $newitemid = $DB->insert_record('evaluation_level', $data);
        $this->set_mapping('evaluation_level', $oldid, $newitemid);
    }

    protected function process_evaluation_userinfo($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->evaluationid = $this->get_new_parentid('evaluation');
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        $newitemid = $DB->insert_record('evaluation_userinfo', $data);
        $this->set_mapping('evaluation_userinfo', $oldid, $newitemid);
    }

    protected function process_evaluation_section($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->evaluationid = $this->get_new_parentid('evaluation');
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        $newitemid = $DB->insert_record('evaluation_section', $data);
        $this->set_mapping('evaluation_section', $oldid, $newitemid);
    }

    protected function process_evaluation_skill($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->sectionid = $this->get_new_parentid('evaluation_section');
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        $newitemid = $DB->insert_record('evaluation_skill', $data);
        $this->set_mapping('evaluation_skill', $oldid, $newitemid);
    }

    protected function process_evaluation_user($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->evaluationid = $this->get_new_parentid('evaluation');
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        $newitemid = $DB->insert_record('evaluation_user', $data);
        $this->set_mapping('evaluation_user', $oldid, $newitemid);
    }

   protected function process_evaluation_user_skill_level($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->skillid = $this->get_mappingid('evaluation_skill', $data->skillid);
        $data->levelid = $this->get_mappingid('evaluation_level', $data->levelid);
        $data->evaluationuserid = $this->get_mappingid('evaluation_user', $data->evaluationuserid);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        $newitemid = $DB->insert_record('evaluation_user_skill_level', $data);
        $this->set_mapping('evaluation_user_skill_level', $oldid, $newitemid);
    }

    protected function process_evaluation_userfields_info($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->evaluationuserid = $this->get_mappingid('evaluation_user', $data->evaluationuserid);
        $data->userfieldid = $this->get_mappingid('evaluation_userinfo', $data->userfieldid);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        $newitemid = $DB->insert_record('evaluation_userfields_info', $data);
        $this->set_mapping('evaluation_userfields_info', $oldid, $newitemid);
    }

    protected function after_execute() {
        // Add evaluation related files, no need to match by itemname (just internally handled context)
        $this->add_related_files('mod_evaluation', 'intro', null);
    }
}
