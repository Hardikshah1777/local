<?php

use local_policies\categories_form;

require_once("../../config.php");

$id = optional_param('id', 0, PARAM_INT);
$catlisturl = new moodle_url('/local/policies/managecategory.php');
$url = new moodle_url('/local/policies/addcategory.php', ['id' => $id]);
$context = context_system::instance();

$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title(get_string('addcattitle', 'local_policies'));
$PAGE->set_heading(get_string('addcatheading', 'local_policies'));
require_admin();

$addcatform = new categories_form($url);

if ($addcatform->is_cancelled()) {
    redirect($catlisturl);
}

$existcatdata = new stdClass();

if ($id) {
    $existcatdata = $DB->get_record('local_policycategories_table', ['id' => $id]);
    $addcatform->set_data($existcatdata);
}

if ($formdata = $addcatform->get_data()) {

    $catdata = new stdClass();
    $catdata->name = $formdata->name;
    $catdata->timemodified = time();
    if (!empty($id)) {
        $catdata->id = $id;
        $DB->update_record('local_policycategories_table', $catdata);
    } else {
        $catdata->timecreated = time();
        $catdata->id = $DB->insert_record('local_policycategories_table', $catdata);
    }

    redirect($catlisturl, get_string('save', 'local_policies'));
}


echo $OUTPUT->header();
$addcatform->display();
echo $OUTPUT->footer();