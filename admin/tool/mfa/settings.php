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
 * Settings
 *
 * @package     tool_mfa
 * @author      Mikhail Golenkov <golenkovm@gmail.com>
 * @copyright   Catalyst IT
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {

    $ADMIN->add('tools', new admin_category('toolmfafolder', new lang_string('pluginname', 'tool_mfa'), false));

    $settings = new admin_settingpage('managemfa', new lang_string('mfasettings', 'tool_mfa'));
    $settings->add(new \tool_mfa\local\admin_setting_managemfa());

    $ADMIN->add('toolmfafolder', new admin_externalpage('confirmationcode', get_string('confirmationcode', 'tool_mfa'),
            new moodle_url('/admin/tool/mfa/authcode.php')));

    $heading = new lang_string('settings:general', 'tool_mfa');
    $settings->add(new admin_setting_heading('tool_mfa/settings', $heading, ''));

    $name = new lang_string('settings:enabled', 'tool_mfa');
    $description = new lang_string('settings:enabled_help', 'tool_mfa');
    $settings->add(new admin_setting_configcheckbox('tool_mfa/enabled', $name, '', false));

    $name = new lang_string('settings:lockout', 'tool_mfa');
    $description = new lang_string('settings:lockout_help', 'tool_mfa');
    $settings->add(new admin_setting_configtext('tool_mfa/lockout', $name, $description, 10, PARAM_INT));

    $name = new lang_string('settings:debugmode', 'tool_mfa');
    $description = new lang_string('settings:debugmode_help', 'tool_mfa');
    $settings->add(new admin_setting_configcheckbox('tool_mfa/debugmode', $name, $description, false));

    $name = new lang_string('setting:course','tool_mfa');
    $description = new lang_string('settings:course_help', 'tool_mfa');
    $courses = get_courses();
    foreach ($courses as $course){
            if($course->id != '1'){
                $choice[$course->id] = $course->fullname;
            }
    }
    $settings->add(new admin_setting_configmultiselect('tool_mfa/course', $name, $description, [],$choice));

    $ADMIN->add('toolmfafolder', $settings);

    $ADMIN->add('toolmfafolder', new admin_externalpage('invalidtryusers', get_string('invalidtryusers', 'tool_mfa'),
        new moodle_url('/admin/tool/mfa/invalidtry.php')));

    $ADMIN->add('toolmfafolder', new admin_externalpage('skipuserauth', get_string('skipuserauth', 'tool_mfa'),
        new moodle_url('/admin/tool/mfa/skipusers.php')));

    foreach (core_plugin_manager::instance()->get_plugins_of_type('factor') as $plugin) {
        $plugin->load_settings($ADMIN, 'toolmfafolder', $hassiteconfig);
    }

    $ADMIN->add('reports', new admin_category('toolmfareports', get_string('mfareports', 'tool_mfa')));
    $ADMIN->add('toolmfareports',
        new admin_externalpage('factorreport', get_string('factorreport', 'tool_mfa'),
        new moodle_url('/admin/tool/mfa/factor_report.php')));
}
