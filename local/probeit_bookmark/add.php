<?php

use local_probeit_bookmark\probeitbookmark_form;

require_once("../../config.php");

$id = optional_param( 'id', 0, PARAM_INT);
$context = context_system::instance();

$url = new moodle_url( '/local/probeit_bookmark/add.php', ['id' => $id]);
$backurl = new moodle_url( '/local/probeit_bookmark/manage.php' );

$PAGE->set_url( $url );
$PAGE->set_context( $context );
$PAGE->set_title( get_string( 'addtitle', 'local_probeit_bookmark' ) );
$PAGE->set_heading( get_string( 'addheading', 'local_probeit_bookmark' ) );
require_login();

$form = new probeitbookmark_form($url);

if (!empty($id)) {
    $setdata = $DB->get_record('local_probeit_bookmark', ['id' => $id]);
    $form->set_data($setdata);
}

if ($form->is_cancelled()) {
    redirect( $backurl );
} elseif ($formdata = $form->get_data()) {
    $data = new stdClass();
    $data->title = $formdata->title;
    $data->link = $formdata->link;
    $data->description = $formdata->description;
    $data->timemodified = time();
    if (!empty($id)) {
        $data->id = $id;
        $DB->update_record( 'local_probeit_bookmark', $data);
        $msg = get_string('updatemsg', 'local_probeit_bookmark');
    } else {
        $data->timecreated = time();
        $DB->insert_record( 'local_probeit_bookmark', $data );
        $msg = get_string('insertmsg', 'local_probeit_bookmark');
    }
    redirect($backurl, $msg);
}

echo $OUTPUT->header();
$form->display();
echo $OUTPUT->footer();