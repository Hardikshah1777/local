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
 * Update form
 *
 * @package    mod_behaviour
 * @copyright  2011 Artem Andreev <andreev.artem@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_behaviour\form;

/**
 * class for displaying update session form.
 *
 * @copyright  2011 Artem Andreev <andreev.artem@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class updatesession extends \moodleform {

    /**
     * Called to define this moodle form
     *
     * @return void
     */
    public function definition() {

        global $DB, $COURSE;
        $mform    =& $this->_form;

        $modcontext    = $this->_customdata['modcontext'];
        $sessionid     = $this->_customdata['sessionid'];

        if (!$sess = $DB->get_record('behaviour_sessions', array('id' => $sessionid) )) {
            error('No such session in this course');
        }
        $behavioursubnet = $DB->get_field('behaviour', 'subnet', array('id' => $sess->behaviourid));
        $defopts = array('maxfiles' => EDITOR_UNLIMITED_FILES, 'noclean' => true, 'context' => $modcontext);
        $sess = file_prepare_standard_editor($sess, 'description', $defopts, $modcontext, 'mod_behaviour', 'session', $sess->id);

        $starttime = $sess->sessdate - usergetmidnight($sess->sessdate);
        $starthour = floor($starttime / HOURSECS);
        $startminute = floor(($starttime - $starthour * HOURSECS) / MINSECS);

        $enddate = $sess->sessdate + $sess->duration;
        $endtime = $enddate - usergetmidnight($enddate);
        $endhour = floor($endtime / HOURSECS);
        $endminute = floor(($endtime - $endhour * HOURSECS) / MINSECS);

        $data = array(
            'sessiondate' => $sess->sessdate,
            'sestime' => array('starthour' => $starthour, 'startminute' => $startminute,
            'endhour' => $endhour, 'endminute' => $endminute),
            'sdescription' => $sess->description_editor,
            'calendarevent' => $sess->calendarevent,
            'studentscanmark' => $sess->studentscanmark,
            'studentpassword' => $sess->studentpassword,
            'autoassignstatus' => $sess->autoassignstatus,
            'subnet' => $sess->subnet,
            'automark' => $sess->automark,
            'absenteereport' => $sess->absenteereport,
            'automarkcompleted' => 0,
            'preventsharedip' => $sess->preventsharedip,
            'preventsharediptime' => $sess->preventsharediptime,
            'includeqrcode' => $sess->includeqrcode,
            'rotateqrcode' => $sess->rotateqrcode,
            'automarkcmid' => $sess->automarkcmid,
            'studentsearlyopentime' => $sess->studentsearlyopentime
        );
        if ($sess->subnet == $behavioursubnet) {
            $data['usedefaultsubnet'] = 1;
        } else {
            $data['usedefaultsubnet'] = 0;
        }

        $mform->addElement('header', 'general', get_string('changesession', 'behaviour'));

        if ($sess->groupid == 0) {
            $strtype = get_string('commonsession', 'behaviour');
        } else {
            $groupname = $DB->get_field('groups', 'name', array('id' => $sess->groupid));
            $strtype = get_string('group') . ': ' . $groupname;
        }
        $mform->addElement('static', 'sessiontypedescription', get_string('sessiontype', 'behaviour'), $strtype);

        $olddate = mod_behaviour_construct_session_full_date_time($sess->sessdate, $sess->duration);
        $mform->addElement('static', 'olddate', get_string('olddate', 'behaviour'), $olddate);

        behaviour_form_sessiondate_selector($mform);

        // Show which status set is in use.
        $maxstatusset = behaviour_get_max_statusset($this->_customdata['att']->id);
        if ($maxstatusset > 0) {
            $mform->addElement('static', 'statussetstring', get_string('usestatusset', 'mod_behaviour'),
                behaviour_get_setname($this->_customdata['att']->id, $sess->statusset));
        }
        $mform->addElement('hidden', 'statusset', $sess->statusset);
        $mform->setType('statusset', PARAM_INT);

        $mform->addElement('editor', 'sdescription', get_string('description', 'behaviour'),
                           array('rows' => 1, 'columns' => 80), $defopts);
        $mform->setType('sdescription', PARAM_RAW);

        if (!empty(get_config('behaviour', 'enablecalendar'))) {
            $mform->addElement('checkbox', 'calendarevent', '', get_string('calendarevent', 'behaviour'));
            $mform->addHelpButton('calendarevent', 'calendarevent', 'behaviour');
        } else {
            $mform->addElement('hidden', 'calendarevent', 0);
            $mform->setType('calendarevent', PARAM_INT);
        }

        // If warnings allow selector for reporting.
        if (!empty(get_config('behaviour', 'enablewarnings'))) {
            $mform->addElement('checkbox', 'absenteereport', '', get_string('includeabsentee', 'behaviour'));
            $mform->addHelpButton('absenteereport', 'includeabsentee', 'behaviour');
        }

        // Students can mark own behaviour.
        $studentscanmark = get_config('behaviour', 'studentscanmark');

        $mform->addElement('header', 'headerstudentmarking', get_string('studentmarking', 'behaviour'), true);
        $mform->setExpanded('headerstudentmarking');
        if (!empty($studentscanmark)) {
            $mform->addElement('checkbox', 'studentscanmark', '', get_string('studentscanmark', 'behaviour'));
            $mform->addHelpButton('studentscanmark', 'studentscanmark', 'behaviour');

            $mform->addElement('duration', 'studentsearlyopentime', get_string('studentsearlyopentime', 'behaviour'));
            $mform->addHelpButton('studentsearlyopentime', 'studentsearlyopentime', 'behaviour');
            $mform->hideif('studentsearlyopentime', 'studentscanmark', 'notchecked');
        } else {
            $mform->addElement('hidden', 'studentscanmark', '0');
            $mform->settype('studentscanmark', PARAM_INT);
            $mform->addElement('hidden', 'studentsearlyopentime', '0');
            $mform->settype('studentsearlyopentime', PARAM_INT);
        }

        if ($DB->record_exists('behaviour_statuses', ['behaviourid' => $this->_customdata['att']->id, 'setunmarked' => 1])) {
            $options2 = behaviour_get_automarkoptions();

            $mform->addElement('select', 'automark', get_string('automark', 'behaviour'), $options2);
            $mform->setType('automark', PARAM_INT);
            $mform->addHelpButton('automark', 'automark', 'behaviour');

            $automarkcmoptions2 = behaviour_get_coursemodulenames($COURSE->id);

            $mform->addElement('select', 'automarkcmid', get_string('selectactivity', 'behaviour'), $automarkcmoptions2);
            $mform->setType('automarkcmid', PARAM_INT);
            $mform->hideif('automarkcmid', 'automark', 'neq', '3');
            if (!empty($sess->automarkcompleted)) {
                $mform->hardFreeze('automarkcmid,automark,studentscanmark');
            }
        }
        if (!empty($studentscanmark)) {
            $mform->addElement('text', 'studentpassword', get_string('studentpassword', 'behaviour'));
            $mform->setType('studentpassword', PARAM_TEXT);
            $mform->addHelpButton('studentpassword', 'passwordgrp', 'behaviour');
            $mform->disabledif('studentpassword', 'rotateqrcode', 'checked');
            $mform->hideif('studentpassword', 'studentscanmark', 'notchecked');
            $mform->hideif('studentpassword', 'automark', 'eq', BEHAVIOUR_AUTOMARK_ALL);
            $mform->hideif('randompassword', 'automark', 'eq', BEHAVIOUR_AUTOMARK_ALL);
            $mform->addElement('checkbox', 'includeqrcode', '', get_string('includeqrcode', 'behaviour'));
            $mform->hideif('includeqrcode', 'studentscanmark', 'notchecked');
            $mform->disabledif('includeqrcode', 'rotateqrcode', 'checked');
            $mform->addElement('checkbox', 'rotateqrcode', '', get_string('rotateqrcode', 'behaviour'));
            $mform->hideif('rotateqrcode', 'studentscanmark', 'notchecked');
            $mform->addElement('checkbox', 'autoassignstatus', '', get_string('autoassignstatus', 'behaviour'));
            $mform->addHelpButton('autoassignstatus', 'autoassignstatus', 'behaviour');
            $mform->hideif('autoassignstatus', 'studentscanmark', 'notchecked');
        }

        $mgroup = array();
        $mgroup[] = & $mform->createElement('text', 'subnet', get_string('requiresubnet', 'behaviour'));
        $mform->setDefault('subnet', $this->_customdata['att']->subnet);
        $mgroup[] = & $mform->createElement('checkbox', 'usedefaultsubnet', get_string('usedefaultsubnet', 'behaviour'));
        $mform->setDefault('usedefaultsubnet', 1);
        $mform->setType('subnet', PARAM_TEXT);

        $mform->addGroup($mgroup, 'subnetgrp', get_string('requiresubnet', 'behaviour'), array(' '), false);
        $mform->setAdvanced('subnetgrp');
        $mform->addHelpButton('subnetgrp', 'requiresubnet', 'behaviour');
        $mform->hideif('subnet', 'usedefaultsubnet', 'checked');

        $mform->addElement('hidden', 'automarkcompleted', '0');
        $mform->settype('automarkcompleted', PARAM_INT);

        $mgroup3 = array();
        $options = behaviour_get_sharedipoptions();
        $mgroup3[] = & $mform->createElement('select', 'preventsharedip',
            get_string('preventsharedip', 'behaviour'), $options);
        $mgroup3[] = & $mform->createElement('text', 'preventsharediptime',
            get_string('preventsharediptime', 'behaviour'), '', 'test');
        $mform->addGroup($mgroup3, 'preventsharedgroup',
            get_string('preventsharedip', 'behaviour'), array(' '), false);
        $mform->addHelpButton('preventsharedgroup', 'preventsharedip', 'behaviour');
        $mform->setAdvanced('preventsharedgroup');
        $mform->setType('preventsharediptime', PARAM_INT);

        // Add custom field data to form.
        $handler = \mod_behaviour\customfield\session_handler::create();
        $handler->instance_form_definition($mform, $sess->id);
        $data['id'] = $sess->id;
        $data = $handler->instance_form_before_set_data_array($data);

        $mform->setDefaults($data);
        $this->add_action_buttons(true);
    }

    /**
     * Perform minimal validation on the settings form
     * @param array $data
     * @param array $files
     */
    public function validation($data, $files) {
        global $DB;
        $errors = parent::validation($data, $files);

        $sesstarttime = $data['sestime']['starthour'] * HOURSECS + $data['sestime']['startminute'] * MINSECS;
        $sesendtime = $data['sestime']['endhour'] * HOURSECS + $data['sestime']['endminute'] * MINSECS;
        if ($sesendtime < $sesstarttime) {
            $errors['sestime'] = get_string('invalidsessionendtime', 'behaviour');
        }

        if (!empty($data['studentscanmark']) && isset($data['automark'])
            && $data['automark'] == BEHAVIOUR_AUTOMARK_CLOSE) {

            $cm            = $this->_customdata['cm'];
            // Check that the selected statusset has a status to use when unmarked.
            $sql = 'SELECT id
            FROM {behaviour_statuses}
            WHERE deleted = 0 AND (behaviourid = 0 or behaviourid = ?)
            AND setnumber = ? AND setunmarked = 1';
            $params = array($cm->instance, $data['statusset']);
            if (!$DB->record_exists_sql($sql, $params)) {
                $errors['automark'] = get_string('noabsentstatusset', 'behaviour');
            }
        }

        if (!empty($data['studentscanmark']) && !empty($data['preventsharedip']) &&
                empty($data['preventsharediptime'])) {
            $errors['preventsharedgroup'] = get_string('iptimemissing', 'behaviour');

        }
        return $errors;
    }
}
