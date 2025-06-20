<?php
// This file is part of AssignSubmission_Changes plugin for Moodle - http://moodle.org/
//
// AssignSubmission_Changes is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// AssignSubmission_Changes is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with AssignSubmission_Changes.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Assign Submission Changes
 *
 * @package   assignsubmission_changes
 * @author    Hendrik Wuerz <hendrikmartin.wuerz@stud.tu-darmstadt.de>
 * @copyright (c) 2017 Hendrik Wuerz
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;
global $CFG;

require_once($CFG->libdir.'/formslib.php');
require_once(dirname(__FILE__) . '/../definitions.php');

/**
 * Edit form to change the auto generated change message.
 * @copyright (c) 2017 Hendrik Wuerz
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assign_submission_changes_edit_form extends moodleform {

    /**
     * The prefix for all used form elements and strings.
     */
    const STRING_PREFIX = 'edit_form_';

    /**
     * Define the form.
     */
    public function definition() {
        $mform = $this->_form;

        // Inject the change ID to parse the submitted data easily.
        $mform->addElement('hidden', 'id', '');
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id', $this->_customdata['change']->id);

        // Headline.
        $assignment = $this->_customdata['assignment'];
        $cm = $this->_customdata['cm'];
        $assignment_link = new moodle_url('/mod/assign/view.php', array('id' => $cm->id));
        $mform->addElement('html',
            '<h3>'
            . '<a href="'.$assignment_link->out().'">' . $assignment->name . '</a>' // Link to the submission.
            . '</h3>');
        $mform->addElement('html',
            '<p>'
            . get_string(self::STRING_PREFIX . 'headline',
                ASSIGNSUBMISSION_CHANGES_NAME,
                userdate($this->_customdata['change']->timestamp))
            . '</p>');

        // Detected Changes.
        $mform->addElement('editor', 'changes',
            get_string(self::STRING_PREFIX . 'changes', ASSIGNSUBMISSION_CHANGES_NAME),
            array('enable_filemanagement' => false)
        )->setValue(array('text' => $this->_customdata['change']->changes)); // Function setDefault does not work for editor.
        $mform->setType('changes', PARAM_RAW);
        $mform->addHelpButton('changes', self::STRING_PREFIX . 'changes', ASSIGNSUBMISSION_CHANGES_NAME);

        $this->add_action_buttons();
    }

    /**
     * Validate submitted form data
     * @param array $data The data fields submitted from the form.
     * @param array $files Files submitted from the form.
     * @return array List of errors to be displayed on the form if validation fails.
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // No input.
        if (empty($data['changes'])) {
            $error = get_string(self::STRING_PREFIX . 'error_empty', ASSIGNSUBMISSION_CHANGES_NAME);
            $errors['changes'] = $error;
        }

        // Too many chars in the input.
        $max_chars = 700;
        $used_chars = strlen($data['changes']['text']);
        if ($used_chars > $max_chars) {
            $param = (object) array(
                'max' => $max_chars,
                'used' => $used_chars
            );
            $error = get_string(self::STRING_PREFIX . 'error_too_long', ASSIGNSUBMISSION_CHANGES_NAME, $param);
            $errors['changes'] = $error;
        }

        return $errors;
    }
}
