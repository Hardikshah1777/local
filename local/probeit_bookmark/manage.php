<?php

use local_probeit_bookmark\probeitbookmark_table;

require_once("../../config.php");

$deleteid = optional_param('deleteid',0,PARAM_INT);
$context = context_system::instance();

$url = new moodle_url('/local/probeit_bookmark/manage.php');
$addurl = new moodle_url('/local/probeit_bookmark/add.php');
$manageurl = new moodle_url('/local/probeit_bookmark/index.php');

$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title(get_string('title', 'local_probeit_bookmark'));
$PAGE->set_heading(get_string('heading', 'local_probeit_bookmark'));
require_login();

if (!empty($deleteid)){
    $DB->delete_records('local_probeit_bookmark', ['id' => $deleteid]);
    redirect($url, get_string('deletemsg', 'local_probeit_bookmark'));
}

$table = new probeitbookmark_table('probeitbookmark');
$table->baseurl = $url;
$addlink = new single_button($addurl, get_string('addlink', 'local_probeit_bookmark'),'',true);
$back = new single_button($manageurl, get_string('back', 'local_probeit_bookmark'),'',true);

echo $OUTPUT->header();

echo html_writer::start_div( 'd-flex justify-content-end mb-2' );
echo $OUTPUT->render( $addlink );
echo $OUTPUT->render( $back );
echo html_writer::end_div();

$table->init();

echo $OUTPUT->footer();