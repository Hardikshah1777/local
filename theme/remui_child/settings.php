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

$ADMIN->add('root', new admin_category('relatedcoursecat', 'Related Course Setting'));

if ($ADMIN->fulltree) {
    $settings = new theme_remui_child_admin_settingspage_tabs('themesettingremui_child', get_string('configtitle', 'theme_remui_child'));

    // New Page settings - Begin
    $page = new admin_settingpage('theme_remui_child_login', get_string('newpagesettings', 'theme_remui_child'));

    // Login page video
    $name = 'theme_remui_child/newpagesetting';
    $title = 'New Page Setting';
    $description = 'New Page Setting Description';
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'newpagesetting');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    $settings->add($page);
    // Login Page settings - End
}
