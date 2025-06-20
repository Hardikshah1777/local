<?php

defined('MOODLE_INTERNAL') || die;

$ADMIN->add('reports', new admin_externalpage('reportgeneralnotes', get_string('reporttitle', 'local_generalnotes'), "$CFG->wwwroot/local/generalnotes/index.php",local_generalnotes_comment::cap));

// no report settings
$settings = null;
