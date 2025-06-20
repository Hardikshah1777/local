<?php

defined('MOODLE_INTERNAL') || die();

$capabilities = array(

'local/timetracker:viewreport' => ['captype' => 'write', 'contextlevel' => CONTEXT_SYSTEM,
 'archetypes' => ['manager' => CAP_ALLOW, ], 'clonepermissionsfrom' => 'moodle/site:config', ],

);
