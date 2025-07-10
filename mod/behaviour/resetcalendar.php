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
 * Reset Calendar events.
 *
 * @package    mod_behaviour
 * @copyright  2017 onwards Dan Marsden http://danmarsden.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/mod/behaviour/lib.php');
require_once($CFG->dirroot.'/mod/behaviour/locallib.php');

$action = optional_param('action', '', PARAM_ALPHA);

admin_externalpage_setup('managemodules');
$context = context_system::instance();

// Check permissions.
require_capability('mod/behaviour:viewreports', $context);

$exportfilename = 'behaviour-absentee.csv';

$PAGE->set_url('/mod/behaviour/resetcalendar.php');

$PAGE->set_heading($SITE->fullname);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('resetcalendar', 'mod_behaviour'));
$tabmenu = behaviour_print_settings_tabs('resetcalendar');
echo $tabmenu;

if (get_config('behaviour', 'enablecalendar')) {
    // Check to see if all sessions that need them have calendar events.
    if ($action == 'create' && confirm_sesskey()) {
        $sessions = $DB->get_recordset('behaviour_sessions',  array('caleventid' => 0, 'calendarevent' => 1));
        foreach ($sessions as $session) {
            behaviour_create_calendar_event($session);
            if ($session->caleventid) {
                $DB->update_record('behaviour_sessions', $session);
            }
        }
        $sessions->close();
        echo $OUTPUT->notification(get_string('eventscreated', 'mod_behaviour'), 'notifysuccess');
    } else {
        if ($DB->record_exists('behaviour_sessions', array('caleventid' => 0, 'calendarevent' => 1))) {
            $createurl = new moodle_url('/mod/behaviour/resetcalendar.php', array('action' => 'create'));
            $returnurl = new moodle_url("/{$CFG->admin}/settings.php", array('section' => 'modsettingbehaviour'));

            echo $OUTPUT->confirm(get_string('resetcaledarcreate', 'mod_behaviour'), $createurl, $returnurl);
        } else {
            echo $OUTPUT->box(get_string("noeventstoreset", "mod_behaviour"));
        }
    }
} else {
    if ($action == 'delete' && confirm_sesskey()) {
        // behaviour isn't using Calendar - delete anything that was created.
        $DB->delete_records('event', ['modulename' => 'behaviour', 'eventtype' => 'behaviour']);
        echo $OUTPUT->notification(get_string('eventsdeleted', 'mod_behaviour'), 'notifysuccess');
    } else {
        // Check to see if there are any events that need to be deleted.
        if ($DB->record_exists_select('behaviour_sessions', 'caleventid > 0')) {
            $deleteurl = new moodle_url('/mod/behaviour/resetcalendar.php', array('action' => 'delete'));
            $returnurl = new moodle_url("/{$CFG->admin}/settings.php", array('section' => 'modsettingbehaviour'));

            echo $OUTPUT->confirm(get_string('resetcaledardelete', 'mod_behaviour'), $deleteurl, $returnurl);
        } else {
            echo $OUTPUT->box(get_string("noeventstoreset", "mod_behaviour"));
        }
    }

}

echo $OUTPUT->footer();
