<?php

use core\dml\sql_join;
use core_course\customfield\course_handler;
use core_customfield\category_controller;
use local_syllabus_util AS syllabushelper;

defined('MOODLE_INTERNAL') || die();

global $CFG;
$CFG->nofixday = true;
require_once("$CFG->libdir/externallib.php");

class local_coact_external extends external_api {



}