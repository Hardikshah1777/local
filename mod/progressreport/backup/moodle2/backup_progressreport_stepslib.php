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
 * Define all the backup steps that will be used by the backup_evaluation_activity_task
 */

/**
 * Define the complete progressreport structure for backup, with file and id annotations
 */
class backup_progressreport_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {

        // To know if we are including userinfo
        $userinfoincluded = $this->get_setting_value('userinfo');

        //// Define each element separated
        $evaluation = new backup_nested_element('progressreport', array('id'), array(
                'name', 'intro', 'introformat', 'timecreated','timemodified','nolesson'));

        $levels = new backup_nested_element('markets');
        $level = new backup_nested_element('market', array('id'), array(
                'name','mnumber','timemodified'));

        $sections = new backup_nested_element('sections');
        $section = new backup_nested_element('section',array('id'),array(
                'name','visible','sortorder','deleted','timemodified'
        ));

        $skills = new backup_nested_element('skills');
        $skill = new backup_nested_element('skill',array('id'),array(
                'name','sectionid','visible','validation','deleted','timemodified'
            ));


        $users = new backup_nested_element('users');
        $user = new backup_nested_element('user',array('id'),array(
                    'userid','notes','attempt','agree','save','confirm','nolesson','timemodified',
        ));

        $userinfos = new backup_nested_element('fields');
        $userinfo = new backup_nested_element('field',array('id'),array(
                    'field','fieldvalue','timemodified'
        ));

        $userskilllevels = new backup_nested_element('user_skills');
        $userskilllevel = new backup_nested_element('user_skill',array('id'),array(
                    'skillid','marketid','progressreportuserid','lessonnumber','timemodified'
        ));

        $userlessons = new backup_nested_element('user_lessons');
        $userlesson= new backup_nested_element('user_lesson',array('id'),array(
                    'progressreportuserid','lesson','average','time'
        ));

        $userfieldinfos = new backup_nested_element('field_infos');
        $userfieldinfo = new backup_nested_element('field_info',array('id'),array(
                'progressreportuserid','fieldid','fieldvalue','timemodified'
        ));

        // Build the tree

        $evaluation->add_child($levels);
        $levels->add_child($level);

        $evaluation->add_child($sections);
        $sections->add_child($section);

        $section->add_child($skills);
        $skills->add_child($skill);

        $evaluation->add_child($userinfos);
        $userinfos->add_child($userinfo);

        $evaluation->add_child($users);
        $users->add_child($user);

        $user->add_child($userskilllevels);
        $userskilllevels->add_child($userskilllevel);

        $user->add_child($userfieldinfos);
        $userfieldinfos->add_child($userfieldinfo);

        $user->add_child($userlessons);
        $userlessons->add_child($userlesson);

        // Define sources
        $evaluation->set_source_table('progressreport', array('id' => backup::VAR_ACTIVITYID));

        // All the rest of elements only happen if we are including user info
        $level->set_source_table('progressreport_market', array('progressreportid' => backup::VAR_PARENTID));

        $section->set_source_table('progressreport_section', array('progressreportid' => backup::VAR_PARENTID));

        $skill->set_source_table('progressreport_skill', array('sectionid' => backup::VAR_PARENTID));

        $userinfo->set_source_table('progressreport_field', array('progressreportid' => backup::VAR_PARENTID));

        if ($userinfoincluded) {

            $user->set_source_table('progressreport_user', array('progressreportid' => backup::VAR_PARENTID));

            $userskilllevel->set_source_table('progressreport_user_skill', array('progressreportuserid' => backup::VAR_PARENTID));

            $userfieldinfo->set_source_table('progressreport_field_info', array('progressreportuserid' => backup::VAR_PARENTID));

            $userlesson->set_source_table('progressreport_user_lesson', array('progressreportuserid' => backup::VAR_PARENTID));

        }

        // Define id annotations
        $user->annotate_ids('user', 'userid');

        // Define file annotations
        $evaluation->annotate_files('mod_progressreport', 'intro', null); // This file area hasn't itemid

        // Return the root element (evaluation), wrapped into standard activity structure
        return $this->prepare_activity_structure($evaluation);
    }
}
