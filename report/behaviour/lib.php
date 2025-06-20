<?php

use report_behaviour\util;

function report_behaviour_extend_navigation_course($navigation, $course, $context) {

    if (has_capability('report/behaviour:view', $context)) {
        $url = new moodle_url('/report/behaviour/index.php', array('id' => $course->id));
        $navigation->add(get_string('indexheader',util::COMPONENT), $url, navigation_node::TYPE_SETTING, null, null, new pix_icon('i/report', ''));

        $url = new moodle_url('/report/behaviour/weekly.php', array('id' => $course->id));
        $navigation->add(get_string('weeklyheader',util::COMPONENT), $url, navigation_node::TYPE_SETTING, null, null, new pix_icon('i/report', ''));
    }

}