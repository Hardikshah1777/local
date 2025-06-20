<?php

require_once('../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/csvlib.class.php');
require_once( 'classes/form1.php');
require_once( 'classes/form2.php');
require_once( 'classes/process.php');
require_once( 'classes/preview.php');

$iid         = optional_param('iid', '', PARAM_INT);
$previewrows = optional_param('previewrows', 10, PARAM_INT);

$myurl = new moodle_url('/local/registration/manage.php');
$url=new moodle_url('/local/registration/bulkcoupon.php');

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout('standard');
$PAGE->set_title($SITE->fullname);


if (empty($iid)) {
    $mform1 = new form1();

    if ($formdata = $mform1->get_data()) {
        $iid = csv_import_reader::get_new_iid('uploadcoupon');
        $cir = new csv_import_reader($iid, 'uploadcoupon');

        $content = $mform1->get_file_content('userfile');

        $readcount = $cir->load_csv_content($content, $formdata->encoding, $formdata->delimiter_name);
        $csvloaderror = $cir->get_error();
        unset($content);

        if (!is_null($csvloaderror)) {
            throw new \moodle_exception('csvloaderror', '', $returnurl, $csvloaderror);
        }

    } else {
        $PAGE->set_heading(get_string('uploadcoupon','local_registration'));

        echo $OUTPUT->header();

        $mform1->display();

        echo $OUTPUT->footer();
        die();
    }
} else {
    $cir = new csv_import_reader($iid, 'uploadcoupon');
}
$process = new process($cir);
$filecolumns = $process->get_file_columns();

$mform2 = new form2(null,
    ['columns' => $filecolumns, 'data' => ['iid' => $iid, 'previewrows' => $previewrows]]);

    if ($formdata = $mform2->is_cancelled()) {

        $cir->cleanup(true);
        redirect($returnurl);
    
    } else if ($formdata = $mform2->get_data()) {
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('uploadcouponresult', 'local_registration'));

        $process->process();
    
        echo $OUTPUT->box_start('boxwidthnarrow boxaligncenter generalbox', 'uploadresults');
        echo html_writer::tag('p', join('<br />', $process->get_stats()));
        echo $OUTPUT->box_end();

        echo $OUTPUT->continue_button($myurl);

        echo $OUTPUT->footer();
        die();
    }


echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('uploadcouponspreview', 'local_registration'));

$table = new \preview($cir, $filecolumns, $previewrows);

echo html_writer::tag('div', html_writer::table($table), ['class' => 'flexible-wrap']);

if ($table->get_no_error()) {
    $mform2->display();
}
echo $OUTPUT->footer();

