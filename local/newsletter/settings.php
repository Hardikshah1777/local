<?php


defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {
    global $CFG;
    $ADMIN->add('localplugins', new admin_externalpage('local_newsletter', get_string('title', 'local_newsletter'), "$CFG->wwwroot/local/newsletter/index.php"));
}