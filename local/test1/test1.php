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
echo var_dump(qualified_me()).'<br>';
echo var_dump(is_https()).'<br>';
echo var_dump(me()).'<br>';
echo var_dump(get_local_referer()).'<br>';
echo var_dump(p(' test ')).'<br>';
echo var_dump(s(' test ')).'<br>';
echo var_dump(strip_querystring(qualified_me().'?id=1')).'<br>';
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