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
 * Steps definitions related to mod_behaviour
 *
 * @package   mod_behaviour
 * @copyright 2021 Dan Marsden
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

use Behat\Gherkin\Node\TableNode as TableNode;

use Behat\Mink\Exception\ExpectationException as ExpectationException;

/**
 * Steps definitions related to mod_behaviour.
 *
 * @copyright 2021 Dan Marsden
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_mod_behaviour extends behat_question_base {
    /**
     * Convert page names to URLs for steps like 'When I am on the "[identifier]" "[page type]" page'.
     *
     * Recognised page names are:
     * | pagetype          | name meaning                                | description                                  |
     * | View              | behaviour name                             | The behaviour info page (view.php)          |
     *
     * @param string $type identifies which type of page this is, e.g. 'Attempt review'.
     * @param string $identifier identifies the particular page, e.g. 'Test behaviour > student > Attempt 1'.
     * @return moodle_url the corresponding URL.
     * @throws Exception with a meaningful error message if the specified page cannot be found.
     */
    protected function resolve_page_instance_url(string $type, string $identifier): moodle_url {
        switch (strtolower($type)) {
            case 'view':
                return new moodle_url('/mod/behaviour/view.php',
                        ['id' => $this->get_cm_by_behaviour_name($identifier)->id]);
            case 'report':
                return new moodle_url('/mod/behaviour/report.php',
                       ['id' => $this->get_cm_by_behaviour_name($identifier)->id]);
            default:
                throw new Exception('Unrecognised behaviour page type "' . $type . '."');
        }
    }

    /**
     * Get a behaviour by name.
     *
     * @param string $name behaviour name.
     * @return stdClass the corresponding DB row.
     */
    protected function get_behaviour_by_name(string $name): stdClass {
        global $DB;
        return $DB->get_record('behaviour', array('name' => $name), '*', MUST_EXIST);
    }

    /**
     * Get a quiz cmid from the quiz name.
     *
     * @param string $name quiz name.
     * @return stdClass cm from get_coursemodule_from_instance.
     */
    protected function get_cm_by_behaviour_name(string $name): stdClass {
        $behaviour = $this->get_behaviour_by_name($name);
        return get_coursemodule_from_instance('behaviour', $behaviour->id, $behaviour->course);
    }
}
