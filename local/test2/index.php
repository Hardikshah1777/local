<?php

require_once '../../config.php';
require_once($CFG->libdir . '/tablelib.php');

use local_test2\form\test_form;
use local_test2\form\search_form;
use local_test2\table\test2table;

$deleteid = optional_param('deleteid',0,PARAM_INT);
$download = optional_param('download', '', PARAM_ALPHANUM);
$exportinzip = optional_param('exportinzip', '', PARAM_ALPHA);
$search = optional_param('search', '', PARAM_TEXT);

$context = context_system::instance();
$url = new moodle_url('/local/test2/index.php', ['search' => $search]);

$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_heading('');
$PAGE->set_title('Test 2 Heading');
require_login();

if (!empty($deleteid)) {
    if ($DB->delete_records( 'local_test2', ['id' => $deleteid])){
        redirect($url,get_string('deletedmsg','local_test2'));
    }
}

$table = new test2table('test2tbl');
$searchform = new test_form($url, null,'post',/*'', ['class' => 'd-flex']*/);
//$searchform = new search_form($url, null,'post',/*'', ['class' => 'd-flex']*/);

$table->show_download_buttons_at([TABLE_P_BOTTOM]);
$table->is_downloadable(false);
if ($table->is_downloading($download, 'Users', 'Users')) {
    $table->init();
}

if(!empty($exportinzip)) {
    $table->exportinzip();
}

$js = <<<js
require(['jquery'], function($) {
    var exportinzip = $('#exportinzip');
    if (exportinzip.length > 0) {
        exportinzip.on('click', function() {
            $.ajax({
                url: M.cfg.wwwroot+'/local/test2/index.php',                
                type: 'POST',
                dataType: 'html',
                data: { action: 'exportinzip' },
                success: function(response) {
                    if (response.status === 'success') {
                        alert(response.message);
                    } else {
                        alert('Something went wrong: ' + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    alert('An error occurred: ' + error);
                }
            });
        });
    }
});
//
// require(['jquery'], function($) {
//     var exportinzip = $('#exportinzip');
//     if (exportinzip.length > 0) {
//         exportinzip.on('click', function() {
//             alert();
//         });
//     }
// });
js;

echo $OUTPUT->header();
$PAGE->requires->js_amd_inline($js);
echo html_writer::start_div('row d-flex');
    echo html_writer::start_div('col-md-6');
        $searchform->display();
    echo html_writer::end_div();
    echo html_writer::start_div('col-md-6');
        $addurl = new moodle_url('/local/test2/add.php');
        echo $OUTPUT->action_link($addurl, '', null, ['class' => 'float-right'], new pix_icon('t/add','Add'));
    echo html_writer::end_div();
echo html_writer::end_div();
$table->init();
$btndownloadzip = $OUTPUT->single_button(new moodle_url("/local/test2/index.php",['id' => 'exportinzip','exportinzip' => 'exportinzip', 'search' => $search]),"Users");
$btndownloadzip = $OUTPUT->action_link('#','Users',null, ['class' => 'btn btn-secondary', 'id' => 'exportinzip']);
echo html_writer::tag('div', $btndownloadzip);
echo $OUTPUT->footer();