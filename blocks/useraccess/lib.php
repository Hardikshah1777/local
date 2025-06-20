<?php

require_once($CFG->libdir . '/enrollib.php');

function enroluserincourse($userid)
{
    global $DB;
    $enrol = enrol_get_plugin('manual');
    $role = $DB->get_record('role', ['shortname' => 'student']);
    $instance = $DB->get_record('enrol', ['courseid' => 4, 'enrol' => 'manual'] );
    $enrol->enrol_user($instance, $userid, $role->id,);
    redirect(new moodle_url('/my'),get_string('newenrol','block_useraccess'),'','success');
}