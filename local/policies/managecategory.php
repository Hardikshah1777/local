<?php

use local_policies\categories_table;

require_once("../../config.php");

$url = new moodle_url('/local/policies/managecategory.php');
$addcategoryurl = new moodle_url('/local/policies/addcategory.php');
$backurl = new moodle_url('/local/policies/index.php');
$context = context_system::instance();

$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title(get_string('cattitle', 'local_policies'));
$PAGE->set_heading(get_string('catheading', 'local_policies'));

require_admin();

$addcategory = new single_button($addcategoryurl, get_string('addcategory', 'local_policies'), 'post');
$addcategory->class = "text-right mb-3 mr-2";
$table = new categories_table('id');
$table->baseurl = $url;
$backbtn = new single_button($backurl, get_string('backtopolicy','local_policies'));

echo $OUTPUT->header();
echo html_writer::start_div('d-flex justify-content-end');
echo $OUTPUT->render($addcategory);
echo $OUTPUT->render($backbtn);
echo html_writer::end_div();
$table->categorydata();

echo $OUTPUT->footer();