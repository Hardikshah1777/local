<?php

use local_policies\policies_form;

require_once("../../config.php");

$id = optional_param('id', 0, PARAM_INT);
$indurl = new moodle_url('/local/policies/index.php');

$url = new moodle_url('/local/policies/add.php', ['id' => $id]);
$context = context_system::instance();

$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title(get_string('title', 'local_policies'));
$PAGE->set_heading(get_string('heading', 'local_policies'));
require_admin();


$mform = new policies_form($url);

if ($mform->is_cancelled()) {
    redirect($indurl);
}

$data = new stdClass();
if ($id) {
    $data = $DB->get_record('local_policies', ['id' => $id]);
    file_prepare_standard_filemanager($data, 'overview', ['subdirs' => 0, 'maxfiles' => 5], $context, 'local_policies', 'overviewfiles', $id);
    $mform->set_data($data);
}

if ($formdata = $mform->get_data()) {

    $files = file_get_drafarea_files($formdata->overview_filemanager);
    foreach ($files->list as $filelist) {
        $filenames[] = $filelist->filename;
    }

    $policiedata = new stdClass();
    $policiedata->name = $formdata->name;
    $policiedata->categoryid = $formdata->categoryid;
    $policiedata->files = join(PHP_EOL, empty($filenames) ? [] : $filenames);
    $policiedata->timemodified = time();
    if (!empty($id)) {
        $policiedata->id = $id;
        $DB->update_record('local_policies', $policiedata);
    } else {
        $policiedata->timecreated = time();
        $policiedata->id = $DB->insert_record('local_policies', $policiedata);
    }

    $option = array('subdirs' => 0, 'maxfiles' => 5,);
    $insertdata = file_postupdate_standard_filemanager($formdata, 'overview', $option, $context, 'local_policies', 'overviewfiles', $policiedata->id);
    redirect($indurl, get_string('save', 'local_policies'));
}


echo $OUTPUT->header();

$mform->display();

echo $OUTPUT->footer();