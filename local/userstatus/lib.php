<?php


function local_userstatus_before_footer($addtoenrol = false) {
    global $COURSE,$PAGE,$CFG,$participanttable;
    $isenrolindex = $PAGE->url->compare(new moodle_url('/user/index.php'),URL_MATCH_BASE);
    $participanttable->uniqueid ?? 0;
    if($COURSE->id > 1 && $isenrolindex && $addtoenrol) {
        $options = new stdClass();
        $options->courseid = $COURSE->id;
        $options->statuses = \local_userstatus\status::get_options();
        $options->userindex = true;
        $prefix = $CFG->branch;
        if($prefix > 38){
            if(!($participanttable || $participanttable->uniqueid)) {
                return false;
            }
            $options->uniqueid = $participanttable->uniqueid;
        }
        $PAGE->requires->js_call_amd('local_userstatus/status'.$prefix, 'init', [$options]);
    }
}

function local_userstatus_extend_navigation_course($navigation, $course, $context) {
    global $PAGE;

    // Only add this settings item on non-site course pages.
    if (!$PAGE->course || $PAGE->course->id == SITEID) {
        return null;
    }

    // Check we can view the recycle bin.
    if (!has_any_capability(['moodle/course:update','moodle/course:enrolreview'], $context)) {
        return null;
    }

    $url = new moodle_url('/local/userstatus/index.php', array(
            'id' => $course->id
    ));

    // Add the recyclebin link.
    $pluginname = get_string('workflow', 'local_userstatus');

    $node = navigation_node::create(
            $pluginname,
            $url,
            navigation_node::NODETYPE_LEAF,
            'local_userstatus',
            'local_userstatus',
            new pix_icon('i/users',$pluginname)
    );

    if ($PAGE->url->compare($url, URL_MATCH_BASE)) {
        $node->make_active();
    }

    $navigation->add_node($node);
}
