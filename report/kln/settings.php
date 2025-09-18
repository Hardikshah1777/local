<?php

defined('MOODLE_INTERNAL') || die();

$ADMIN->add('reports', new admin_externalpage('klnuserreport', get_string('setting:klnuserreport', 'report_kln'),
     $CFG->wwwroot .'/report/kln/index.php'));

$ADMIN->add('reports', new admin_externalpage('klncoursereport', get_string('setting:klncoursereport', 'report_kln'),
     $CFG->wwwroot .'/report/kln/courses.php'));
