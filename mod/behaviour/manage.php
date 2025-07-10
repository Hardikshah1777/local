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
 * Manage behaviour sessions
 *
 * @package    mod_behaviour
 * @copyright  2011 Artem Andreev <andreev.artem@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__).'/../../config.php');
require_once(dirname(__FILE__).'/locallib.php');

$pageparams = new mod_behaviour_manage_page_params();

$id                         = required_param('id', PARAM_INT);
$from                       = optional_param('from', null, PARAM_ALPHANUMEXT);
$pageparams->view           = optional_param('view', null, PARAM_INT);
$pageparams->curdate        = optional_param('curdate', null, PARAM_INT);
$pageparams->perpage        = get_config('behaviour', 'resultsperpage');

$cm             = get_coursemodule_from_id('behaviour', $id, 0, false, MUST_EXIST);
$course         = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$att            = $DB->get_record('behaviour', array('id' => $cm->instance), '*', MUST_EXIST);

require_login($course, true, $cm);

$context = context_module::instance($cm->id);
$capabilities = array(
    'mod/behaviour:managebehaviours',
    'mod/behaviour:takebehaviours',
    'mod/behaviour:changebehaviours'
);
if (!has_any_capability($capabilities, $context)) {
    $url = new moodle_url('/mod/behaviour/view.php', array('id' => $cm->id));
    redirect($url);
}

$pageparams->init($cm);
$att = new mod_behaviour_structure($att, $cm, $course, $context, $pageparams);

// If teacher is coming from block, then check for a session exists for today.
if ($from === 'block') {
    $sessions = $att->get_today_sessions();
    $size = count($sessions);
    if ($size == 1) {
        $sess = reset($sessions);
        $nottaken = !$sess->lasttaken && has_capability('mod/behaviour:takebehaviours', $context);
        $canchange = $sess->lasttaken && has_capability('mod/behaviour:changebehaviours', $context);
        if ($nottaken || $canchange) {
            redirect($att->url_take(array('sessionid' => $sess->id, 'grouptype' => $sess->groupid)));
        }
    } else if ($size > 1) {
        $att->curdate = $today;
        // Temporarily set $view for single access to page from block.
        $att->view = ATT_VIEW_DAYS;
    }
}

$PAGE->set_url($att->url_manage());
$PAGE->set_title($course->shortname. ": ".$att->name);
$PAGE->set_heading($course->fullname);
$PAGE->set_cacheable(true);
$PAGE->force_settings_menu(true);
$PAGE->navbar->add($att->name);

$output = $PAGE->get_renderer('mod_behaviour');
$filtercontrols = new mod_behaviour\output\filter_controls($att);
$sesstable = new mod_behaviour\output\manage_data($att);

// Output starts here.

echo $output->header();
mod_behaviour_notifyqueue::show();
echo $output->render($filtercontrols);
echo $output->render($sesstable);

echo $output->footer();
