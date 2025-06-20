<?php

use report_temco_completion\table\temco_complete;

require_once '../../config.php';

$download = optional_param('download', '', PARAM_ALPHA);
$parpage = optional_param('parpage', 50, PARAM_INT);

$url = new moodle_url( '/report/temco_completion/index.php');
$context = context_system::instance();

$PAGE->set_title(get_string('title', 'report_temco_completion'));
$PAGE->set_heading(get_string('heading', 'report_temco_completion'));
$PAGE->set_url($url);
$PAGE->set_context($context);

require_login();

$temcoreport = new temco_complete('temco');
$temcoreport->define_baseurl($url);
$temcoreport->is_downloadable(true);

if (!empty($download)) {
    $temcoreport->is_downloading($download, 'Temco_completion', 'Temco');
    $temcoreport->render($parpage);
    exit;
}

echo $OUTPUT->header();
$temcoreport->out($parpage, false);
echo $OUTPUT->footer();