<?php
// This script should be placed in local/test1/cli/seed_dummy_data.php

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir.'/clilib.php');

global $DB;

cli_heading('Seeding dummy data into courseuserinvoice');

// Check if table exists.
if (!$DB->get_manager()->table_exists('courseuserinvoice')) {
    cli_error("Table 'courseuserinvoice' does not exist.");
}

for ($i = 1; $i <= 10; $i++) {

    $record = new stdClass();
    $record->courseid = rand(2, 62);
    $record->userid = rand(1, 300);
    $record->invoice = rand(1, 500);
    $record->timemodified = time();

    if (!$DB->record_exists('courseuserinvoice',['courseid' => $record->courseid, 'userid' => $record->userid])) {
        $DB->insert_record('courseuserinvoice', $record );
    }else{
        \core\notification::success('Record exist with courseid: '.$record->courseid.' AND userid: '.$record->userid);
    }
}

cli_writeln("✅ Inserted 10 dummy records into courseuserinvoice.");
