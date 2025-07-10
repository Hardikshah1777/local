<?php

require_once '../../config.php';
use local_test2\form\test2_form;

$id = optional_param('id',0,PARAM_INT);

$context = context_system::instance();
$url = new moodle_url('/local/test2/add.php', ['id' => $id]);
$url2 = new moodle_url('/local/test2/index.php');
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_heading('Test 2 Heading');
$PAGE->set_title('Test 2');
require_login();

$test2form = new test2_form($url, ['id' => $id]);
if ($test2form->is_cancelled()){
    redirect($url2);
}

$data = $DB->get_record('local_test2',['id' => $id],'*');
if (!empty($id)){
    $test2form->set_data($data);
}
$data = new stdClass();
if ($formdata = $test2form->get_data()){
    $data->firstname = $formdata->firstname;
    $data->lastname = $formdata->lastname;
    $data->email = $formdata->email;
    $data->city = $formdata->city;
    $data->timeupdated = time();
    if (!empty($formdata->id)) {
        $data->id = $id;
        if ($DB->update_record('local_test2', $data)) {
            redirect($url2, 'Record updated successfully');
        }
    } else {
        $data->timecreated = time();
        if ($DB->insert_record( 'local_test2', $data)) {
            redirect($url2, 'Record insert successfully');
        }
    }
}

echo $OUTPUT->header();
$test2form->display();
echo $OUTPUT->footer();