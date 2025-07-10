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
 * Prints behaviour info for particular user
 *
 * @package    mod_behaviour
 * @copyright  2011 Artem Andreev <andreev.artem@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once(dirname(__FILE__).'/../../config.php');
require_once(dirname(__FILE__).'/locallib.php');

$pageparams = new mod_behaviour_view_page_params();

$id                     = required_param('id', PARAM_INT);
$edit                   = optional_param('edit', -1, PARAM_BOOL);
$pageparams->studentid  = optional_param('studentid', null, PARAM_INT);
$pageparams->mode       = optional_param('mode', mod_behaviour_view_page_params::MODE_THIS_COURSE, PARAM_INT);
$pageparams->view       = optional_param('view', null, PARAM_INT);
$pageparams->curdate    = optional_param('curdate', null, PARAM_INT);
$pageparams->groupby    = optional_param('groupby', 'course', PARAM_ALPHA);
$pageparams->sesscourses = optional_param('sesscourses', 'current', PARAM_ALPHA);

$cm             = get_coursemodule_from_id('behaviour', $id, 0, false, MUST_EXIST);
$course         = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$behaviour    = $DB->get_record('behaviour', array('id' => $cm->instance), '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/behaviour:view', $context);

$pageparams->init($cm);
$att = new mod_behaviour_structure($behaviour, $cm, $course, $context, $pageparams);

// Not specified studentid for displaying behaviour?
// Redirect to appropriate page if can.
if (!$pageparams->studentid) {
    $capabilities = array(
        'mod/behaviour:managebehaviours',
        'mod/behaviour:takebehaviours',
        'mod/behaviour:changebehaviours'
    );
    if (has_any_capability($capabilities, $context)) {
        redirect($att->url_manage());
    } else if (has_capability('mod/behaviour:viewreports', $context)) {
        redirect($att->url_report());
    }
}

if (isset($pageparams->studentid) && $USER->id != $pageparams->studentid) {
    // Only users with proper permissions should be able to see any user's individual report.
    require_capability('mod/behaviour:viewreports', $context);
    $userid = $pageparams->studentid;
} else {
    // A valid request to see another users report has not been sent, show the user's own.
    $userid = $USER->id;
}

$url = $att->url_view($pageparams->get_significant_params());
$PAGE->set_url($url);

$buttons = '';
$capabilities = array('mod/behaviour:takebehaviours', 'mod/behaviour:changebehaviours');
if (has_any_capability($capabilities, $context) &&
    $pageparams->mode == mod_behaviour_view_page_params::MODE_ALL_SESSIONS) {

    if (!isset($USER->behaviourediting)) {
        $USER->behaviourediting = false;
    }

    if (($edit == 1) && confirm_sesskey()) {
        $USER->behaviourediting = true;
    } else if ($edit == 0 && confirm_sesskey()) {
        $USER->behaviourediting = false;
    }

    if ($USER->behaviourediting) {
        $options['edit'] = 0;
        $string = get_string('turneditingoff');
    } else {
        $options['edit'] = 1;
        $string = get_string('turneditingon');
    }
    $options['sesskey'] = sesskey();
    $button = new single_button(new moodle_url($PAGE->url, $options), $string, 'post');
    $PAGE->set_button($OUTPUT->render($button));
}

$userdata = new mod_behaviour\output\user_data($att, $userid);

// Create url for link in log screen.
$filterparams = array(
    'view' => $userdata->pageparams->view,
    'curdate' => $userdata->pageparams->curdate,
    'startdate' => $userdata->pageparams->startdate,
    'enddate' => $userdata->pageparams->enddate
);
$params = array_merge($userdata->pageparams->get_significant_params(), $filterparams);


if (empty($userdata->pageparams->studentid)) {
    $relateduserid = $USER->id;
} else {
    $relateduserid = $userdata->pageparams->studentid;
}
// We check if formdata includes sesskey first because the javascript calendar does a post to the page on change.
if (($formdata = data_submitted()) && !empty($formdata->sesskey) && confirm_sesskey() && $edit == -1) {
    $userdata->take_sessions_from_form_data($formdata);

    // Trigger updated event.
    $event = \mod_behaviour\event\session_report_updated::create(array(
        'relateduserid' => $relateduserid,
        'context' => $context,
        'other' => $params));
    $event->add_record_snapshot('course_modules', $cm);
    $event->trigger();

    redirect($url, get_string('behavioursuccess', 'behaviour'));
} else {
    // Trigger viewed event.
    $event = \mod_behaviour\event\session_report_viewed::create(array(
        'relateduserid' => $relateduserid,
        'context' => $context,
        'other' => $params));
    $event->add_record_snapshot('course_modules', $cm);
    $event->trigger();
}

$PAGE->set_title($course->shortname. ": ".$att->name);
$PAGE->set_heading($course->fullname);
$PAGE->set_cacheable(true);
$PAGE->navbar->add(get_string('behaviourreport', 'behaviour'));

$output = $PAGE->get_renderer('mod_behaviour');

echo $output->header();
echo $output->render($userdata);
echo $output->footer();
