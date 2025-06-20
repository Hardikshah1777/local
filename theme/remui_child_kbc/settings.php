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
 * @package   theme_remui
 * @copyright WisdmLabs
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $settings = new theme_remui_child_admin_settingspage_tabs('themesettingremui_child_kbc', get_string('configtitle_kbc', 'theme_remui_child_kbc'));

    // New Page settings - Begin
    $page = new admin_settingpage('theme_remui_child_kbc_newpagesettings', get_string('newpagesettings', 'theme_remui_child_kbc'));

    // Logo file setting.
    $name = 'theme_remui_child_kbc/logo';
    $title = get_string('logo', 'theme_remui_child_kbc');
    $description = get_string('logodesc', 'theme_remui_child_kbc');
    $setting = new admin_setting_configstoredfile(
        $name,
        $title,
        $description,
        'logo',
        0,
        array( 'subdirs' => 0, 'accepted_types' => 'web_image')
    );
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Logo file setting.
    $name = 'theme_remui_child_kbc/logomini';
    $title = get_string('logomini', 'theme_remui_child_kbc');
    $description = get_string('logominidesc', 'theme_remui_child_kbc');
    $setting = new admin_setting_configstoredfile(
        $name,
        $title,
        $description,
        'logomini',
        0,
        array( 'subdirs' => 0, 'accepted_types' => 'web_image')
    );
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    //Color Setting
    $name = 'theme_remui_child_kbc/brandcolor';
    $title = get_string('brandcolor', 'theme_remui_child_kbc');
    $description = get_string('brandcolor_desc', 'theme_remui_child_kbc');
    $setting = new admin_setting_configcolourpicker($name, $title, $description, '');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    $settings->add($page);

}
