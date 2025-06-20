<?php

namespace block_coupon\forms\coupon;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class group_pricing extends \moodleform {

    public function definition() {
        $mform = $this->_form;
        $id = $this->_customdata['id'];
        $groupid = $this->_customdata['groupid'];
        $courseid = $this->_customdata['courseid'];
        $mform->addElement('hidden','id',$id);
        $mform->addElement('hidden','groupid',$groupid);
        $mform->addElement('hidden','courseid',$courseid);
        $mform->addElement('text','price',get_string('form:group_pricing:price', 'block_coupon'));
        $mform->setType('id',PARAM_INT);
        $mform->setType('groupid',PARAM_INT);
        $mform->setType('courseid',PARAM_INT);
        $mform->setType('price',PARAM_INT);
        $this->add_action_buttons();
    }

}
