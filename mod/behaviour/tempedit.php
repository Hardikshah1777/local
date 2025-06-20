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
 * behaviour tempedit
 *
 * @package    mod_behaviour
 * @copyright  2013 Davo Smith, Synergy Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__).'/../../config.php');
require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot.'/mod/behaviour/locallib.php');

$id = required_param('id', PARAM_INT);
$userid = required_param('userid', PARAM_INT);
$action = optional_param('action', null, PARAM_ALPHA);

$cm = get_coursemodule_from_id('behaviour', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$att = $DB->get_record('behaviour', array('id' => $cm->instance), '*', MUST_EXIST);
$tempuser = $DB->get_record('behaviour_tempusers', array('id' => $userid), '*', MUST_EXIST);

$att = new mod_behaviour_structure($att, $cm, $course);

$params = array('userid' => $tempuser->id);
if ($action) {
    $params['action'] = $action;
}
$PAGE->set_url($att->url_tempedit($params));

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/behaviour:managetemporaryusers', $context);

$PAGE->set_title($course->shortname.": ".$att->name.' - '.get_string('tempusersedit', 'behaviour'));
$PAGE->set_heading($course->fullname);
$PAGE->set_cacheable(true);
$PAGE->navbar->add(get_string('tempusersedit', 'behaviour'));

/** @var mod_behaviour\output\renderer $output */
$output = $PAGE->get_renderer('mod_behaviour');

if ($action == 'delete') {
    if (optional_param('confirm', false, PARAM_BOOL)) {
        require_sesskey();

        // Remove the user from the grades table, the behaviour log and the tempusers table.
        $DB->delete_records('grade_grades', array('userid' => $tempuser->studentid));
        $DB->delete_records('behaviour_log', array('studentid' => $tempuser->studentid));
        $DB->delete_records('behaviour_tempusers', array('id' => $tempuser->id));

        redirect($att->url_managetemp());
    } else {

        $info = (object)array(
            'fullname' => $tempuser->fullname,
            'email' => $tempuser->email,
        );
        $msg = get_string('confirmdeleteuser', 'behaviour', $info);
        $continue = new moodle_url($PAGE->url, array('confirm' => 1, 'sesskey' => sesskey()));

        echo $output->header();
        echo $output->confirm($msg, $continue, $att->url_managetemp());
        echo $output->footer();

        die();
    }
}

$formdata = new stdClass();
$formdata->id = $cm->id;
$formdata->tname = $tempuser->fullname;
$formdata->userid = $tempuser->id;
$formdata->temail = $tempuser->email;

$mform = new \mod_behaviour\form\tempuseredit();
$mform->set_data($formdata);

if ($mform->is_cancelled()) {
    redirect($att->url_managetemp());
} else if ($tempuser = $mform->get_data()) {
    global $DB;
    $updateuser = new stdClass();
    $updateuser->id = $tempuser->userid;
    $updateuser->fullname = $tempuser->tname;
    $updateuser->email = $tempuser->temail;
    $DB->update_record('behaviour_tempusers', $updateuser);
    redirect($att->url_managetemp());
}

echo $output->header();
echo $output->heading(get_string('tempusersedit', 'behaviour').' : '.format_string($course->fullname));
$mform->display();
echo $output->footer($course);

