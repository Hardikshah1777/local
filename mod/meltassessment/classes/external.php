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
 * Web services
 *
 * @package     mod_meltassessment
 * @copyright   2018 Marina Glancy
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

/**
 * mod_meltassessment external function
 *
 * @package    mod_meltassessment
 * @copyright  2018 Moodle
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_meltassessment_external extends external_api {

    /**
     * Parameters for the 'mod_meltassessment_invoke_move_action' WS
     * @return external_function_parameters
     */
    public static function invoke_move_action_parameters() {

        $sectionfileds = [
                'sectionid' => new external_value(PARAM_INT, 'Section ID', VALUE_OPTIONAL),
                'position' => new external_value(PARAM_INT, 'Section Postion', VALUE_OPTIONAL),
                ];
        return new external_function_parameters([
                'action' => new external_value(PARAM_TEXT, 'Action'),
                'data' => new external_multiple_structure(
                        new external_single_structure($sectionfileds)
                ),
        ]);
    }

    /**
     * WS 'mod_meltassessment_invoke_move_action' that invokes a move action
     *
     * @param string $action
     * @param array $data
     * @param int $position
     * @throws coding_exception
     */
    public static function invoke_move_action($action,$data) {
        global $DB;
        foreach ($data as $value){
            $updatedata = new stdClass();
            $updatedata->id = $value['sectionid'];
            $updatedata->sortorder = $value['position'];
            $update = $DB->update_record('meltassessment_section',$updatedata);
        }
    }

    /**
     * Return structure for the 'mod_meltassessment_invoke_move_action' WS
     * @return null
     */
    public static function invoke_move_action_returns() {
        return null;
    }

}