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
 * This file contains the form used to upload a csv behaviour file to automatically update behaviour records.
 *
 * @package   mod_behaviour
 * @copyright 2019 Jonathan Chan <jonathan.chan@sta.uwi.edu>
 * @copyright based on work by 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later */
namespace mod_behaviour\form\import;

defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');

use core_text;
use moodleform;
require_once($CFG->libdir.'/formslib.php');

/**
 * Class for displaying the csv upload form.
 *
 * @package   mod_behaviour
 * @copyright 2019 Jonathan Chan <jonathan.chan@sta.uwi.edu>
 * @copyright based on work by 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class marksessions extends moodleform {

    /**
     * Called to define this moodle form
     *
     * @return void
     */
    public function definition() {
        global $COURSE;

        $mform = $this->_form;
        $params = $this->_customdata;

        $mform->addElement('header', 'uploadbehaviour', get_string('uploadbehaviour', 'behaviour'));

        $fileoptions = array('subdirs' => 0,
                                'maxbytes' => $COURSE->maxbytes,
                                'accepted_types' => 'csv',
                                'maxfiles' => 1);

        $mform->addElement('filepicker', 'behaviourfile', get_string('uploadafile'), null, $fileoptions);
        $mform->addRule('behaviourfile', get_string('uploadnofilefound'), 'required', null, 'client');
        $mform->addHelpButton('behaviourfile', 'behaviourfile', 'behaviour');

        $encodings = core_text::get_encodings();
        $mform->addElement('select', 'encoding', get_string('encoding', 'grades'), $encodings);
        $mform->addHelpButton('encoding', 'encoding', 'grades');

        $radio = array();
        $radio[] = $mform->createElement('radio', 'separator', null, get_string('septab', 'grades'), 'tab');
        $radio[] = $mform->createElement('radio', 'separator', null, get_string('sepcomma', 'grades'), 'comma');
        $radio[] = $mform->createElement('radio', 'separator', null, get_string('sepcolon', 'grades'), 'colon');
        $radio[] = $mform->createElement('radio', 'separator', null, get_string('sepsemicolon', 'grades'), 'semicolon');
        $mform->addGroup($radio, 'separator', get_string('separator', 'grades'), ' ', false);
        $mform->addHelpButton('separator', 'separator', 'grades');
        $mform->setDefault('separator', 'comma');

        $mform->addElement('hidden', 'id', $params['id']);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'sessionid', $params['sessionid']);
        $mform->setType('sessionid', PARAM_INT);
        $mform->addElement('hidden', 'grouptype', $params['grouptype']);
        $mform->setType('grouptype', PARAM_INT);
        $mform->addElement('hidden', 'confirm', 0);
        $mform->setType('confirm', PARAM_BOOL);
        $this->add_action_buttons(true, get_string('uploadbehaviour', 'behaviour'));
    }
    /**
     * Display an error on the import form.
     *
     * @param string $msg
     */
    public function set_import_error($msg) {
        $mform = $this->_form;

        $mform->setElementError('behaviourfile', $msg);
    }
}
