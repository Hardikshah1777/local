<?php

defined('MOODLE_INTERNAL') || die;

$ADMIN->add('reports', new admin_externalpage('report_temco_completion', get_string('pluginname', 'report_temco_completion'),
    $CFG->wwwroot."/report/temco_completion/index.php", 'report/temco_completion:view'));

