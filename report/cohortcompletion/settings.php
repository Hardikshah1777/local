<?php

defined('MOODLE_INTERNAL') || die;

$ADMIN->add('reports', new admin_externalpage('reportcohortcompletion', get_string('pluginname', 'report_cohortcompletion'),
    "$CFG->wwwroot/report/cohortcompletion/index.php", 'report/cohortcompletion:view'));

// No report settings.
$settings = null;
