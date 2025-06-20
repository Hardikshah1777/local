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
    $pluginname = get_string('pluginname', 'local_authtimer');

    $settings = new admin_settingpage('local_authtimer_settings', $pluginname);
    $ADMIN->add('localplugins', $settings);

    $configs = array();

    $configs[] = new admin_setting_heading('local_authtimer', get_string('settings:general', 'local_authtimer'), '');

    $configs[] = new local_authtimer_settings_course_select('local_authtimer/courses',
            get_string('courses', 'local_authtimer'),
            '', null);

    // Put all settings into the settings page.
    foreach ($configs as $config) {
        $config->plugin = 'local_authtimer';
        $settings->add($config);
    }
}
