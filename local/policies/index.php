<?php

require_once("../../config.php");

use local_policies\policies_table;

$id = optional_param('id', 0, PARAM_INT);
$context = context_system::instance();
$category = $DB->get_record('local_policycategories_table', ['id' => $id]);
$url = new moodle_url('/local/policies/index.php', ['id' => $id]);
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title(get_string('title', 'local_policies'));
$heading = get_string('heading', 'local_policies');
if ($id) {
    $heading = $category->name;
}
$PAGE->set_heading($heading);

require_admin();

$addurl = new moodle_url('/local/policies/add.php');
$categoryurl = new moodle_url('/local/policies/managecategory.php');
$table = new policies_table('id');
if ($id) {
    $table->catid = $id;
}
$table->baseurl = $url;
$addpolicy = new single_button($addurl, get_string('addpolicy', 'local_policies'), 'post');
$managecategory = new single_button($categoryurl, get_string('managecategory', 'local_policies'), 'post');
$addpolicy->class = $managecategory->class = "text-right mb-3 mr-2";


echo $OUTPUT->header();
echo html_writer::start_div('d-flex justify-content-end');
echo $OUTPUT->render($addpolicy);
echo $OUTPUT->render($managecategory);
echo html_writer::end_div();
$table->showdata();
echo $OUTPUT->footer();