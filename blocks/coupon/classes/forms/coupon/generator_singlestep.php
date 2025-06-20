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
 * Coupon generator form (first step)
 *
 * File         generator.php
 * Encoding     UTF-8
 *
 * @package     block_coupon
 *
 * @copyright   Sebsoft.nl
 * @author      Menno de Ridder <menno@sebsoft.nl>
 * @author      R.J. van Dongen <rogier@sebso
 * ft.nl>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_coupon\forms\coupon;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * block_coupon\forms\generator
 *
 * @package     block_coupon
 *
 * @copyright   Sebsoft.nl
 * @author      Menno de Ridder <menno@sebsoft.nl>
 * @author      R.J. van Dongen <rogier@sebsoft.nl>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class generator_singlestep extends \moodleform {

    /**
     * form definition
     */
    public function definition() {
        global $PAGE,$CFG;
        $mform = & $this->_form;
        $isadmin = $this->_customdata['isadmin'];

        // Email address to mail to.
        $alternativeemail = get_config('block_coupon', 'alternative_email');
        $email = $mform->createElement('text', 'alternative_email', get_string('label:alternative_email', 'block_coupon'));

        // Coupon logo selection.
        if($isadmin) {

            // Courses
            $courses = \block_coupon\helper::get_visible_courses();

            $arrcoursesselect = array();
            foreach ($courses as $course) {
                $arrcoursesselect[$course->id] = $course->fullname;
            }

            $attributes = array('size' => min(20, count($arrcoursesselect)));
            $selectcourse = &$mform->addElement('select', 'coupon_courses',
                get_string('label:coupon_courses', 'block_coupon'), $arrcoursesselect, $attributes);
            //$selectcourse->setMultiple(true);
            $mform->addRule('coupon_courses', get_string('error:required', 'block_coupon'), 'required', null, 'client');
            $mform->addHelpButton('coupon_courses', 'label:coupon_courses', 'block_coupon');

            //Groups
            $groupselement = &$mform->addElement('select', 'coupon_groups', get_string('label:coupon_groups', 'block_coupon'), array(0 => get_string('select')));
            $mform->addHelpButton('coupon_groups', 'label:coupon_groups', 'block_coupon');
            //$groupselement->setMultiple(false);

            //Logo
            $logooptions = \block_coupon\logostorage::get_file_menu();
            $logoselect = $mform->addElement('select', 'logo', get_string('select:logo', 'block_coupon'), $logooptions);
            $logoselect->setSelected(\block_coupon\helper::get_default_logoid());


            // Determine which type of settings we'll use.
            $radioarray = array();
            $radioarray[] = & $mform->createElement('radio', 'showform', '',
                get_string('showform-amount', 'block_coupon'), 'amount');
            $radioarray[] = & $mform->createElement('radio', 'showform', '',
                get_string('showform-csv', 'block_coupon'), 'csv');
            $radioarray[] = & $mform->createElement('radio', 'showform', '',
                get_string('showform-manual', 'block_coupon'), 'manual');
            $mform->addGroup($radioarray, 'radioar', get_string('label:showform', 'block_coupon'), array('<br/>'), false);
            $mform->setDefault('showform', 'amount');

            /*Send coupons based on CSV upload.*/
            $mform->addElement('header', 'csvForm', get_string('heading:csvForm', 'block_coupon'));

            // Filepicker.
            $urldownloadcsv = new \moodle_url($CFG->wwwroot . '/blocks/coupon/sample.csv');
            $mform->addElement('filepicker', 'coupon_recipients',
                get_string('label:coupon_recipients', 'block_coupon'), null, array('accepted_types' => 'csv'));
            $mform->addElement('static', 'coupon_recipients_desc', '', get_string('coupon_recipients_desc', 'block_coupon'));
            $mform->addElement('static', 'sample_csv', '', '<a href="' . $urldownloadcsv . '" target="_blank">' . get_string('download-sample-csv', 'block_coupon') . '</a>');

            // Editable email message.
            $mailcontentdefault = get_string('coupon_mail_csv_content', 'block_coupon');
            $mform->addElement('editor', 'email_body', get_string('label:email_body', 'block_coupon'), array('noclean' => 1));
            $mform->setType('email_body', PARAM_RAW);
            $mform->setDefault('email_body', array('text' => $mailcontentdefault));
            $mform->addRule('email_body', get_string('required'), 'required');

            /*Send coupons based on CSV upload.*/
            $mform->addElement('header', 'manualForm', get_string('heading:manualForm', 'block_coupon'));

            // Textarea recipients.
            $mform->addElement('textarea', 'coupon_recipients_manual',
                get_string("label:coupon_recipients", 'block_coupon'), 'rows="10" cols="100"');
            $mform->addRule('coupon_recipients_manual', get_string('required'), 'required', null, 'client');
            $mform->setDefault('coupon_recipients_manual', 'E-mail');

            $mform->addElement('static', 'coupon_recipients_desc', '', get_string('coupon_recipients_desc', 'block_coupon'));

            // Editable email message.
            $mform->addElement('editor', 'email_body_manual', get_string('label:email_body', 'block_coupon'), array('noclean' => 1));
            $mform->setType('email_body_manual', PARAM_RAW);
            $mform->setDefault('email_body_manual', array('text' => $mailcontentdefault));
            $mform->addRule('email_body_manual', get_string('required'), 'required');

            /*Send coupons based on Amount field.*/
            $mform->addElement('header', 'amountForm', get_string('heading:amountForm', 'block_coupon'));
            $mform->setExpanded('amountForm',true);
            // Email address to mail to.
            $mform->addElement($email);


            //javascript
            $PAGE->requires->js_amd_inline("require(['jquery','core/ajax'], function($,Ajax) {
                var form = $('#".$mform->getAttribute('id')."'),
                courseGroupSelect = form.find('[name=\"coupon_courses\"]'),
                groupSelect = form.find('[name=\"coupon_groups\"]'),
                showform = form.find('input[name$=\"showform\"]:checked'),
                showformOpt = form.find('input[name$=\"showform\"]'),
                courseChange = function() {
                    var courseId = courseGroupSelect.val();
                    Ajax.call([{methodname: 'core_group_get_course_groups',args: {courseid: courseId}}])[0]
                    .then(function(groups) {
                        groupSelect.find('option:not([value = 0])').remove();
                        groupSelect.prop('disabled', groups.length < 1);
                        $(groups).each(function(id, group) {
                            groupSelect.append($('<option></option>').attr('value', group.id).text(group.name));
                        });
                    }).catch(Notification.exception);
                },
                showHide = function(fieldValue) {
                    switch(fieldValue) {
                        case 'csv':
                            $('#id_amountForm').hide();
                            $('#id_manualForm').hide();
                            break;
                        case 'amount':
                            $('#id_csvForm').hide();
                            $('#id_manualForm').hide();
                            break;
                        case 'manual':
                            $('#id_csvForm').hide();
                            $('#id_amountForm').hide();
                            break;
                    }
                    $('#id_' + fieldValue + 'Form').show();
                };
                courseGroupSelect.on('change', courseChange);
                courseChange();
                if(showformOpt){
                    showHide(showform.val());    
                    showformOpt.click(function(){
                        showHide($(this).val());    
                    })
                }
            });");
        }else{
            $mform->addElement($email);
            $mform->addRule('alternative_email', get_string('error:required', 'block_coupon'), 'required', null, 'client');
            $mform->addRule('alternative_email', get_string('error:invalid_email', 'block_coupon'), 'email', null);
            $mform->setDefault('alternative_email', $alternativeemail);
        }
        $mform->setType('alternative_email', PARAM_EMAIL);

        $mform->addHelpButton('alternative_email', 'label:alternative_email', 'block_coupon');

        $this->add_action_buttons(true, get_string('button:save', 'block_coupon'));
    }

    /**
     * Perform validation.
     *
     * @param array $data array of ("fieldname"=>value) of submitted data
     * @param array $files array of uploaded files "element_name"=>tmp_file_path
     * @return array of "element_name"=>"error_description" if there are errors,
     *         or an empty array if everything is OK (true allowed for backwards compatibility too).
     */
    public function validation($data, $files) {
		global $DB,$USER;
        if($this->_customdata['isadmin']) {
            // Set which fields to validate, depending on form used.
            if ($data['showform'] == 'csv' || $data['showform'] == 'manual') {
                $data2validate = array(
                    'email_body' => $data['email_body'],
                );
            } else {
                $data2validate = array(
                    'alternative_email' => $data['alternative_email']
                );
            }

            // Validate.
            $errors = parent::validation($data2validate, $files);

            // Custom validate.
            if ($data['showform'] == 'amount') {
                if(empty($data['alternative_email'])){
                    $errors['alternative_email'] = get_string('error:required', 'block_coupon');
                }else if(!validate_email($data['alternative_email'])){
                    $errors['alternative_email'] = get_string('error:invalid_email', 'block_coupon');
                }
            } else if ($data['showform'] == 'csv') {
                $csvcontent = $this->get_file_content('coupon_recipients');
                if (!$csvcontent || empty($csvcontent)) {
                    $errors['coupon_recipients'] = get_string('required');
                }else if(!empty($csvcontent)){
                    $validationresult = $this->validate_coupon_recipients_csv($csvcontent);
                    if ($validationresult !== true) {
                        $errors['coupon_recipients'] = $validationresult;
                    }
                }
            } else {
                $validationresult = $this->validate_coupon_recipients_manual($data['coupon_recipients_manual']);
                if ($validationresult !== true) {
                    $errors['coupon_recipients_manual'] = $validationresult;
                }
            }
        }else{
            $errors = parent::validation($data,$files);
			if(empty($data['alternative_email'])){
                $errors['alternative_email'] = get_string('error:required', 'block_coupon');
            }else if(!validate_email($data['alternative_email'])){
                $errors['alternative_email'] = get_string('error:invalid_email', 'block_coupon');
            }else if($DB->record_exists('block_coupon',array('ownerid'=>$USER->id,'for_user_email'=>$data['alternative_email']))){
                $errors['alternative_email'] = get_string('error:same_coupon_email_exist', 'block_coupon');
            }
        }

        return $errors;
    }

    /**
     * Get content of uploaded file.
     *
     * @param string $elname name of file upload element
     * @return string|bool false in case of failure, string if ok
     */
    public function get_file_content($elname) {
        global $USER;

        $element = $this->_form->getElement($elname);
        if ($element instanceof \MoodleQuickForm_filepicker || $element instanceof \MoodleQuickForm_filemanager) {
            $values = $this->_form->exportValues($elname);
            if (empty($values[$elname])) {
                return false;
            }
            $draftid = $values[$elname];
            $fs = get_file_storage();
            $context = \context_user::instance($USER->id);
            if (!$files = $fs->get_area_files($context->id, 'user', 'draft', $draftid, 'id DESC', false)) {
                return false;
            }
            $file = reset($files);

            return $file->get_content();
        } else if (isset($_FILES[$elname])) {
            return file_get_contents($_FILES[$elname]['tmp_name']);
        }

        return false;
    }

    public function get_data()
    {
        if($result = parent::get_data()) { // TODO: Change the autogenerated stub
            $result->coupon_groups = optional_param('coupon_groups', null, PARAM_INT);
            return $result;
        }
        return null;
    }

    public function get_recipients_from_csv($recipientsstr) {

        $recipients = array();
        $count = 0;

        // Split up in rows.
        $expectedcolumns = array('e-mail');
        if (!$csvdata = str_getcsv($recipientsstr, "\n")) {
            return false;
        }
        // Split up in columns.
        foreach ($csvdata as &$row) {

            // Get the next row.
            $row = str_getcsv($row, ",");

            // Check if we're looking at the first row.
            if ($count == 0) {

                $expectedrow = array();
                // Set the columns we'll need.
                foreach ($row as $key => &$column) {

                    $column = trim(strtolower($column));
                    if (!in_array($column, $expectedcolumns)) {
                        continue;
                    }

                    $expectedrow[$key] = $column;
                }
                // If we're missing columns.
                if (count($expectedcolumns) != count($expectedrow)) {
                    return false;
                }

                // Now set which columns we'll need to use when extracting the information.
                $emailkey = array_search('e-mail', $expectedrow);

                $count++;
                continue;
            }

            $recipient = new \stdClass();
            $recipient->email = trim($row[$emailkey]);

            $recipients[] = $recipient;
        }

        return $recipients;
    }

    public function get_recipients_from_csvfile($recipientsstr) {

        $recipients = array();
        $count = 0;

        // Split up in rows.
        $expectedcolumns = array('e-mail','name');
        if (!$csvdata = str_getcsv($recipientsstr, "\n")) {
            return false;
        }
        // Split up in columns.
        foreach ($csvdata as &$row) {

            // Get the next row.
            $row = str_getcsv($row, ",");

            // Check if we're looking at the first row.
            if ($count == 0) {

                $expectedrow = array();
                // Set the columns we'll need.
                foreach ($row as $key => &$column) {

                    $column = trim(strtolower($column));
                    if (!in_array($column, $expectedcolumns)) {
                        continue;
                    }

                    $expectedrow[$key] = $column;
                }
                // If we're missing columns.
                if (count($expectedcolumns) != count($expectedrow)) {
                    return false;
                }

                // Now set which columns we'll need to use when extracting the information.
                $emailkey = array_search('e-mail', $expectedrow);
                $namekey = array_search('name', $expectedrow);

                $count++;
                continue;
            }

            $recipient = new \stdClass();
            $recipient->email = trim($row[$emailkey]);
            $recipient->name = trim($row[$namekey]);

            $recipients[] = $recipient;
        }

        return $recipients;
    }

    public function validate_coupon_recipients_csv($csvdata) {

        $error = false;
        $maxcoupons = get_config('block_coupon', 'max_coupons');

        if (!$recipients = $this->get_recipients_from_csvfile($csvdata)) {
            // Required columns aren't found in the csv.
            $error = get_string('error:recipients-columns-missing', 'block_coupon');
        } else {
            // No recipient rows were added to the csv.
            if (empty($recipients)) {
                $error = get_string('error:recipients-empty', 'block_coupon');
                // Check max of the file.
            } else if (count($recipients) > $maxcoupons) {
                $error = get_string('error:recipients-max-exceeded', 'block_coupon');
            } else {
                // Lets run through the file to check on email addresses.
                foreach ($recipients as $recipient) {
                    if (!filter_var($recipient->email, FILTER_VALIDATE_EMAIL)) {
                        $error = get_string('error:recipients-email-invalid', 'block_coupon', $recipient);
                    }
                }
            }
        }

        return ($error === false) ? true : $error;
    }

    public function validate_coupon_recipients_manual($csvdata) {

        $error = false;
        $maxcoupons = get_config('block_coupon', 'max_coupons');

        if (!$recipients = $this->get_recipients_from_csv($csvdata)) {
            // Required columns aren't found in the csv.
            $error = get_string('error:recipients-columns-missing', 'block_coupon');
        } else {
            // No recipient rows were added to the csv.
            if (empty($recipients)) {
                $error = get_string('error:recipients-empty', 'block_coupon');
                // Check max of the file.
            } else if (count($recipients) > $maxcoupons) {
                $error = get_string('error:recipients-max-exceeded', 'block_coupon');
            } else {
                // Lets run through the file to check on email addresses.
                foreach ($recipients as $recipient) {
                    if (!filter_var($recipient->email, FILTER_VALIDATE_EMAIL)) {
                        $error = get_string('error:recipients-email-invalid', 'block_coupon', $recipient);
                    }
                }
            }
        }

        return ($error === false) ? true : $error;
    }

}
