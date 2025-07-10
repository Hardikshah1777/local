<?php

require_once("../../config.php");
require_once($CFG->libdir . '/tablelib.php');


$context = context_system::instance();

$url = new moodle_url('/local/probeit_bookmark/index.php');

$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title(get_string('title', 'local_probeit_bookmark'));
$PAGE->set_heading(get_string('heading', 'local_probeit_bookmark'));
require_login();

class probeitbookmark extends table_sql
{
    public function __construct($uniqueid)
    {
        parent::__construct( $uniqueid );
    }

    public function col_title($col)
    {
        $title = \html_writer::link( $col->link, $col->title, ['target' => '_blank'] );
        return $title;
    }
}

$col = [
    'title' => get_string('tabletitle', 'local_probeit_bookmark'),
    'description' => get_string('description', 'local_probeit_bookmark'),
];

$table = new probeitbookmark('probeitbookmark');
$table->define_columns(array_keys($col));
$table->define_headers(array_values($col));
$table->sortable(false);
$table->collapsible(false);
$table->define_baseurl($url);

$table->set_sql('*','{local_probeit_bookmark}', '1=1');
$manageurl = new moodle_url('/local/probeit_bookmark/manage.php');
$managebtn = new single_button($manageurl, get_string('manage', 'local_probeit_bookmark'),'',true);

echo $OUTPUT->header();

echo html_writer::start_div( 'd-flex justify-content-end mb-2' );
echo $OUTPUT->render($managebtn);
echo html_writer::end_div();
$table->out(30, false);
echo $OUTPUT->footer();