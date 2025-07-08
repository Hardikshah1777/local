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
 *
 * @package    mod_autoattendmod
 * @copyright  Fumi.Iseki
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;


require_once ($CFG->dirroot.'/course/moodleform_mod.php');


class mod_autoattendmod_mod_form extends moodleform_mod
{
    function definition()
    {
        $mform = $this->_form;

        //-------------------------------------------------------------------------------
        $mform->addElement('header', 'general', get_string('general', 'form'));
        //
        $mform->addElement('text', 'name', get_string('name', 'autoattendmod'), array('size'=>'64'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        if (method_exists($this, 'standard_intro_elements')) {
            $this->standard_intro_elements(get_string('description', 'autoattendmod'));
        }
        else {
            $this->add_intro_editor(true, get_string('description', 'autoattendmod'));
        }

        //-------------------------------------------------------------------------------
        $mform->addElement('header', 'autoattendmodhdr', get_string('autoattendmod_options', 'autoattendmod'));
        //
        $choices['fullname']  = get_string('use_item', 'autoattendmod', get_string('fullnameuser'));
        $choices['firstname'] = get_string('use_item', 'autoattendmod', get_string('firstname'));
        $choices['lastname']  = get_string('use_item', 'autoattendmod', get_string('lastname'));
        $mform->addElement('select', 'namepattern', get_string('username_manage', 'autoattendmod'), $choices);
        $mform->addHelpButton('namepattern', 'username_manage', 'autoattendmod');
        $mform->setDefault('namepattern', 'fullname');
        $mform->setType('namepattern', PARAM_TEXT);

        $mform->addElement('checkbox', 'emailkey',    get_string('email_key_title', 'autoattendmod'),    get_string('email_key', 'autoattendmod'));
        $mform->setDefault('emailkey', false);
        $mform->addHelpButton('emailkey', 'email_key', 'autoattendmod');
        $mform->setType('emailkey', PARAM_INT);

        $mform->addElement('checkbox', 'emailenable', get_string('email_enable_title', 'autoattendmod'), get_string('email_enable', 'autoattendmod'));
        $mform->setDefault('emailenable', false);
        $mform->addHelpButton('emailenable', 'email_enable', 'autoattendmod');
        $mform->setType('emailenable', PARAM_INT);

        $mform->addElement('checkbox', 'emailuser',   get_string('email_user_title', 'autoattendmod'), get_string('email_user', 'autoattendmod'));
        $mform->addHelpButton('emailuser', 'email_user', 'autoattendmod');
        $mform->setDefault('emailuser', false);
        $mform->setType('emailuser', PARAM_INT);

        /*
        $mform->addElement('checkbox', 'allreports',  get_string('email_allrep_title', 'autoattendmod'), get_string('email_allrep', 'autoattendmod'));
        $mform->setDefault('allreports', false);
        $mform->addHelpButton('allreports', 'email_allrep', 'autoattendmod');
        $mform->setType('allreport', PARAM_INT);
        */

        $mform->addElement('selectyesno', 'dispinfo', get_string('disp_info_title', 'autoattendmod'), get_string('disp_info', 'autoattendmod'));
        $mform->addHelpButton('dispinfo', 'disp_info', 'autoattendmod');
        $mform->setDefault('dispinfo', 1);
        $mform->setType('dispinfo', PARAM_INT);

        $pretime['-10']= -10;
        $pretime['-5'] = -5;
        $pretime['0']  = 0;
        $pretime['3']  = 3;
        $pretime['5']  = 5;
        $pretime['10'] = 10;
        $pretime['20'] = 20;
        $pretime['30'] = 30;
        $pretime['45'] = 45;
        $pretime['60'] = 60;
        $mform->addElement('select', 'predisptime', get_string('pre_disptime_title', 'autoattendmod'), $pretime);
        $mform->addHelpButton('predisptime', 'pre_disptime', 'autoattendmod');
        $mform->setDefault('predisptime', '5');
        $mform->setType('predisptime', PARAM_INT);
        $mform->hideIf('predisptime', 'dispinfo', 'eq', 0);

        $mform->addElement('checkbox', 'homeroom', get_string('permit_homeroom_title', 'autoattendmod'), get_string('permit_homeroom', 'autoattendmod'));
        $mform->addHelpButton('homeroom', 'permit_homeroom', 'autoattendmod');
        $mform->setDefault('homeroom', true);
        $mform->setType('homeroom', PARAM_INT);

        $mform->addElement('checkbox', 'localhostname', get_string('disp_localhostname_title', 'autoattendmod'), get_string('disp_localhostname', 'autoattendmod'));
        $mform->addHelpButton('localhostname', 'disp_localhostname', 'autoattendmod');
        $mform->setDefault('localhostname', false);
        $mform->setType('localhostname', PARAM_INT);

        $mform->addElement('checkbox', 'summertime', get_string('summertime_title', 'autoattendmod'), get_string('summertime_disp', 'autoattendmod'));
        $mform->addHelpButton('summertime', 'summertime_disp', 'autoattendmod');
        $mform->setDefault('summertime', false);
        $mform->setType('summertime', PARAM_INT);

        $mform->addElement('checkbox', 'excelver', get_string('excelver_title', 'autoattendmod'), get_string('excelver_disp', 'autoattendmod'));
        $mform->addHelpButton('excelver', 'excelver_disp', 'autoattendmod');
        $mform->setDefault('excelver', false);
        $mform->setType('excelver', PARAM_INT);

        $mform->addElement('checkbox', 'backupblock', get_string('backup_block_title', 'autoattendmod'), get_string('backup_block', 'autoattendmod'));
        $mform->addHelpButton('backupblock', 'backup_block', 'autoattendmod');
        $mform->setDefault('backupblock', false);
        $mform->setType('backupblock', PARAM_INT);

        $mform->addElement('checkbox', 'feedback', get_string('feedback_title', 'autoattendmod'), get_string('feedback_disp', 'autoattendmod'));
        $mform->addHelpButton('feedback', 'feedback_disp', 'autoattendmod');
        $mform->setDefault('feedback', true);
        $mform->setType('feedback', PARAM_INT);

        //-------------------------------------------------------------------------------
        $this->standard_coursemodule_elements();

        //-------------------------------------------------------------------------------
        $this->add_action_buttons(true, false, null);
    }
}

