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



defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {
	$ADMIN->add('localplugins', new admin_category(
        'timetracker',
        get_string('pluginname', 'local_timetracker')
    ));
    
	$settings = new admin_settingpage('local_timetracker', get_string('settings'));

    $settings->add(new admin_setting_configtext(
        'local_timetracker/coursebreak',
        get_string('coursebreak', 'local_timetracker'),
        get_string('coursebreak_help', 'local_timetracker'),
        2,
        PARAM_INT
    ));
    
    $settings->add(new admin_setting_configtext(
        'local_timetracker/daybreak',
        get_string('daybreak', 'local_timetracker'),
        get_string('daybreak_help', 'local_timetracker'),
        3,
        PARAM_INT
    ));

    $ADMIN->add('timetracker', $settings);
    
  

    $ADMIN->add('timetracker', new admin_externalpage(
        'local_timetracker_viewreport',
                get_string('report', 'local_timetracker'),
        new moodle_url('/local/timetracker/report.php'),
        'local/timetracker:viewreport'
    ));

}
