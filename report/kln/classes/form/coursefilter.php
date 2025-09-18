<?php

namespace report_kln\form;

use report_kln\util;

class coursefilter extends userfilter {

    protected function definition() {
        $mform = $this->_form;

        $courseoptions = [0 => get_string('choose')] + util::get_courses();
        $mform->addElement('autocomplete', 'courseid', get_string('coursefilter:course', util::COMPONENT), $courseoptions);

        $this->render_common_filters($mform);
    }

}