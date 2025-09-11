<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Prints an instance of mod_test.
 *
 * @package     mod_test
 * @copyright   admin@demo.com
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');

use mod_test\event\course_module_viewed;
use mod_test\table\alltestmod;

// Course module id.
$id = optional_param('id', 0, PARAM_INT);

// Activity instance id.
$t = optional_param('t', 0, PARAM_INT);

if ($id) {
    $cm = get_coursemodule_from_id('test', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $moduleinstance = $DB->get_record('test', array('id' => $cm->instance), '*', MUST_EXIST);
} else {
    $moduleinstance = $DB->get_record('test', array('id' => $t), '*', MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $moduleinstance->course), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('test', $moduleinstance->id, $course->id, false, MUST_EXIST);
}

require_login($course, true, $cm);

$modulecontext = context_module::instance($cm->id);

$event = \mod_test\event\course_module_viewed::create( ['objectid' => $moduleinstance->id, 'context' => $modulecontext] );
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('test', $moduleinstance);
$event->trigger();
$url = new moodle_url('/mod/test/view.php', array('id' => $cm->id));
$PAGE->set_url($url);
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);
$col = [
    'shortname' => get_string('course'),
    'modname' => get_string('module', 'test'),
    'timecreated' => get_string('createdate', 'test'),
];

$table = new alltestmod('alltestmod');
$table->set_sql('t.id, t.name as modname, t.timecreated, c.id as courseid, c.shortname',
                '{test} t LEFT JOIN {course} c ON c.id = t.course',
                '1');
$table->define_headers(array_values($col));
$table->define_columns(array_keys($col));
$table->sortable(false);
$table->collapsible(false);
$table->define_baseurl($url);

echo $OUTPUT->header();
$table->out(30, false);
echo $OUTPUT->footer();
