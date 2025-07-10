<?php
// This file is part of AssignSubmission_Changes plugin for Moodle - http://moodle.org/
//
// AssignSubmission_Changes is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// AssignSubmission_Changes is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with AssignSubmission_Changes.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Assign Submission Changes
 *
 * @package   assignsubmission_changes
 * @author    Hendrik Wuerz <hendrikmartin.wuerz@stud.tu-darmstadt.de>
 * @copyright (c) 2017 Hendrik Wuerz
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once(dirname(__FILE__) . '/definitions.php');

/**
 * Class admin_setting_configcheckbox_diff.
 *
 * Simple helper to add a validation around the diff generation. Should ensures that diff can only be enables by
 * default if the changelog is enabled too.
 * Works in most cases, BUT: If the user has enabled both (changelog and diff) and disables the changelog, this code
 * will not be executed and no error becomes displayed. Nevertheless this validation can help is some cases.
 * @copyright (c) 2017 Hendrik Wuerz
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class admin_setting_configcheckbox_assignsubmission_changes_diff extends admin_setting_configcheckbox {

    /**
     * Wrapper around the writing of the new settings to inject a validation.
     * @param mixed $data The input data.
     * @return bool|string true if no errors. Error string if errors.
     */
    public function write_setting($data) {
        $validation = $this->validate($data);
        if ($validation !== true) {
            return $validation;
        }
        return parent::write_setting($data);
    }

    /**
     * Performs a validation to ensures that the changelog is enabled if the diff should be enabled.
     * @param string $data The diff input data
     * @return bool|string true if no errors. Error string if errors.
     */
    public function validate($data) {
        $changelog = get_config(ASSIGNSUBMISSION_CHANGES_NAME, 'default');
        if ($changelog != '1' && $data == '1') { // The changelog is disabled and the diff should be enabled --> forbidden.
            return get_string('diff_requires_changelog', ASSIGNSUBMISSION_CHANGES_NAME);
        }
        return true;
    }
}

