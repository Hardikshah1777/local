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
require_once(dirname(__FILE__) . '/configcheckbox_assignsubmissionchanges_diff.php');
require_once(dirname(__FILE__) . '/../../../../local/changeloglib/classes/pdftotext.php');
require_once(dirname(__FILE__) . '/../../../../local/changeloglib/classes/diff_detector.php');


$settings->add(new admin_setting_configcheckbox(
    ASSIGNSUBMISSION_CHANGES_NAME . '/allow_changelog',
    new lang_string('allow_changelog', ASSIGNSUBMISSION_CHANGES_NAME),
    new lang_string('allow_changelog_help', ASSIGNSUBMISSION_CHANGES_NAME),
    1));

$settings->add(new admin_setting_configtext(
    ASSIGNSUBMISSION_CHANGES_NAME . '/max_filesize',
    new lang_string('max_filesize', ASSIGNSUBMISSION_CHANGES_NAME),
    new lang_string('max_filesize_help', ASSIGNSUBMISSION_CHANGES_NAME),
    20, PARAM_INT));

$settings->add(new admin_setting_configcheckbox(
    ASSIGNSUBMISSION_CHANGES_NAME . '/default',
    new lang_string('default', ASSIGNSUBMISSION_CHANGES_NAME),
    new lang_string('default_help', ASSIGNSUBMISSION_CHANGES_NAME),
    0));

$settings->add(new admin_setting_configcheckbox_assignsubmission_changes_diff(
    ASSIGNSUBMISSION_CHANGES_NAME . '/diff',
    new lang_string('admin_diff', ASSIGNSUBMISSION_CHANGES_NAME),
    new lang_string('admin_diff_help', ASSIGNSUBMISSION_CHANGES_NAME),
    0));

if (!local_changeloglib_pdftotext::is_installed()) {
    $settings->add(new admin_setting_heading(
        ASSIGNSUBMISSION_CHANGES_NAME . '/pdftotext_not_available',
        new lang_string('warning', ASSIGNSUBMISSION_CHANGES_NAME),
        new lang_string('pdftotext_not_available', ASSIGNSUBMISSION_CHANGES_NAME)));
}

if (!local_changeloglib_diff_detector::is_command_line_diff_installed()) {
    $settings->add(new admin_setting_heading(
        ASSIGNSUBMISSION_CHANGES_NAME . '/diff_not_available',
        new lang_string('warning', ASSIGNSUBMISSION_CHANGES_NAME),
        new lang_string('diff_not_available', ASSIGNSUBMISSION_CHANGES_NAME)));
}
