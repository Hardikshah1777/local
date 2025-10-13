<?php
require_once '../../config.php';

$url = new moodle_url('/local/test1/test1.php');
$context = context_system::instance();

$PAGE->set_title('test1');
$PAGE->set_heading('test1');
$PAGE->set_url($url);
$PAGE->set_context($context);
require_admin();

echo $OUTPUT->header();
$params = ['columnid' => 10, 'sheetid' => 1];
$fields = ['columnstart', 'columnend'];
foreach ($fields as $field) {
    $DB->set_debug(true);
    $records = $DB->get_records_sql( "SELECT * FROM {local_spreadsheet_cell} WHERE CAST({$field} AS UNSIGNED) > :columnid AND sheetid = :sheetid", $params );
    $DB->set_debug(false);
    foreach ($records as $record) {
        print_object($record);
    }
}

echo $OUTPUT->footer();