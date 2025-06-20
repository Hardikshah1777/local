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
 * Theme functions.
 *
 * @package    theme_remui
 * @copyright  WisdmLabs
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
global $PAGE;

/**
 * Serves any files associated with the theme settings.
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options
 * @return bool
 */
function theme_remui_child_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = array()) {
    static $theme;
    $course = $course;
    $cm = $cm;
    if (empty($theme)) {
        $theme = theme_config::load('remui_child');
    }
    if ($context->contextlevel == CONTEXT_SYSTEM) {
        if ($filearea === 'frontpageaboutusimage') {
            return $theme->setting_file_serve('frontpageaboutusimage', $args, $forcedownload, $options);
        } else if ($filearea === 'loginsettingpic') {
            return $theme->setting_file_serve('loginsettingpic', $args, $forcedownload, $options);
        } else if ($filearea === 'logo') {
            return $theme->setting_file_serve('logo', $args, $forcedownload, $options);
        } else if ($filearea === 'logomini') {
            return $theme->setting_file_serve('logomini', $args, $forcedownload, $options);
        } else if (preg_match("/^(slideimage|testimonialimage|frontpageblockimage)[1-5]/", $filearea)) {
            return $theme->setting_file_serve($filearea, $args, $forcedownload, $options);
        } else if ($filearea === 'faviconurl') {
            return $theme->setting_file_serve('faviconurl', $args, $forcedownload, $options);
        } else if ($filearea === 'staticimage') {
            return $theme->setting_file_serve('staticimage', $args, $forcedownload, $options);
        } else if ($filearea === 'layoutimage') {
            return $theme->setting_file_serve('layoutimage', $args, $forcedownload, $options);
        } else {
            send_file_not_found();
        }
    } else {
        send_file_not_found();
    }
}
