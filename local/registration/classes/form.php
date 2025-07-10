<?php

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/formslib.php');

class registration_form extends \moodleform
{
    protected $course;
    public function definition()
    {
        global $DB;
        $mform = $this->_form;
        $id = $this->_customdata;

        $course=$DB->get_record('local_registration',['id'=>$id], 'courseid', IGNORE_MISSING);
        $courses=$course->courseid;
        $iid = optional_param('course', $courses, PARAM_INT);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('text', 'couponcode', get_string('couponcode', 'local_registration'));
        $mform->addRule('couponcode', get_string('couponcoderequired', 'local_registration'), 'required', null, 'client');
        $mform->setType('couponcode', PARAM_ALPHANUM);

        $choices = [];
        $choices['0'] = get_string('disable');
        $choices['1'] = get_string('enable');
        $mform->addElement('select', 'couponenable', get_string('couponenable', 'local_registration'), $choices);

        $choices = [];
        $choices['1'] = get_string('one','local_registration');
        $choices['2'] = get_string('many','local_registration');
        $mform->addElement('select', 'couponuse', get_string('couponuse', 'local_registration'), $choices);

        $mform->addElement('select', 'course', get_string('selectcourse', 'local_registration'), $this->get_course(), ['multiple' => false, 'onchange' => 'this.form.elements.formupdater.click()']);
        $mform->addRule('course', get_string('courseselect', 'local_registration'), 'required', null, 'client');

        $groups=[];
        $group = $DB->get_records('groups',['courseid'=>$iid],'', '*');
        $groups[0] = get_string('choose');
        foreach($group as $key => $groupname){
            $groups[$groupname->id]=$groupname->name;
        }

        $mform->addElement('select', 'group', get_string('group', 'local_registration'), $groups);

        $mform->registerNoSubmitButton('formupdater');
        $mform->addElement('submit', 'formupdater', 'level', ['class' => 'd-none']);
        $mform->setType('level', PARAM_BOOL);

        $options = array('optional' => false, 'defaultunit' => 86400);
        $mform->addElement('duration', 'duration', get_string('duration', 'local_registration'), $options);
        $mform->addRule('duration', get_string('durationrequired', 'local_registration'), 'required', null, 'client');

        $this->add_action_buttons(true, get_string('submit', 'local_registration'));
    }
    public function get_course() {
        global $DB;
        if (is_null($this->course)) {
            $this->course = [0 => get_string('choose')];
            $this->course += $DB->get_records_menu('course', ['visible'=>1], '', 'id,fullname');
        }
        return $this->course;
    }

    public function validation($data, $files)
    {
        global $DB;
        $errors = parent::validation($data, $files);

        if (empty($data['course'])) {
            $errors['course'] = get_string('courseselect', 'local_registration');
        }

        if (empty($data['duration'])) {
            $errors['duration'] = get_string('durationrequired', 'local_registration');
        }

        if ($data['couponcode']) {
            if (!empty($data['id'])) {
                $couponcode = $DB->get_record('local_registration', ['id' => $data['id']], 'couponcode');
                if ($data['couponcode'] !== $couponcode->couponcode) {
                    if ($DB->record_exists('local_registration', ['couponcode' => $data['couponcode']])) {
                        $errors['couponcode'] = get_string('codeexist', 'local_registration');
                    }
                }
            } else {
                if ($DB->record_exists('local_registration', ['couponcode' => $data['couponcode']])) {
                    $errors['couponcode'] = get_string('codeexist', 'local_registration');
                }
            }

        }
        return $errors;
    }
}