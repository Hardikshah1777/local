<?php

require_once '../../config.php';
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir.'/filelib.php');

$context = context_system::instance();
$url = new moodle_url('/local/test3/test3.php');

$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title('Test 3');
$PAGE->set_heading('Test 3');

require_login();

class test3_form extends moodleform {
    public function definition() {
        $mform = $this->_form;
        $mform->addElement('filemanager', 'filedetail', get_string('file'), null,
            array('subdirs' => 0, 'maxbytes' => 0, 'maxfiles' => 50, 'accepted_types' => ['*']));
        $mform->addRule('filedetail',get_string('required'),'required',null,'client');
        $this->add_action_buttons(true, get_string('submit'));
    }

    public function set_data($data) {
        $context = context_system::instance();
        file_prepare_standard_filemanager($data,'filedetail',['subdirs' => 0, 'maxbytes' => 0, 'maxfiles' => 50, 'accepted_types' => ['*']], $context->id,'local_test3','test3',0);
        $this->set_data($data);
    }

    public function validation($data, $files) {
        return parent::validation( $data, $files );
    }
}

$form = new test3_form($url);

if ($form->is_cancelled()) {
    redirect($PAGE->url);
} else if ($data = $form->get_data()) {
    $draftitemid = file_get_submitted_draft_itemid('filedetail');
    file_prepare_draft_area($draftitemid, $context->id, 'local_test3', 'test3', $entry->id, [ 'subdirs' => 0, 'maxbytes' => $maxbytes,
            'maxfiles' => 50,] );
    file_save_draft_area_files($data->filedetail, $context->id, 'local_test3', 'test3', 0);
}


$js = <<<JS

$('.img').on('click', function (e) {
    e.preventDefault();
    const imgElement = this;
    Swal.fire({
        title: 'Confirmation',
        text: 'Are you sure you want to download?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Export',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            const url = imgElement.src + '?forcedownload=1';
            location.href = url;
        } else if (result.dismiss === Swal.DismissReason.cancel) {
            Swal.fire({
                title: 'Cancelled',
                text: 'The download was cancelled',
                toast: true,
                timer: 3000,                
            });
        }
    });
});


JS;

$PAGE->requires->js_amd_inline($js);
$fs = get_file_storage();
$files = $fs->get_area_files($context->id,'local_test3','test3');
$html = html_writer::start_div('container-fluid');
    $html .= html_writer::start_div('row');
        if ($files = $fs->get_area_files($context->id, 'local_test3', 'test3', 0, 'sortorder', false )) {
            foreach ($files as $file) {
                if ($file->get_filesize() > 0) {
                    $imgurl = moodle_url::make_pluginfile_url( $file->get_contextid(), $file->get_component(), $file->get_filearea(), $file->get_itemid(), $file->get_filepath(), $file->get_filename(), false );
                    $url = $imgurl->out(false );
                    $html .= html_writer::start_div('col-4 text-center pt-2');
                        $html .= html_writer::start_tag('a', ['href' => $url]);
                                $extension = strtolower(pathinfo($url, PATHINFO_EXTENSION));
                                if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'])) {
                                    $html .= html_writer::img($url, $file->get_filename(), ['style' => 'width: 50%; object-fit: cover;', 'class' => 'img', 'id' => 'imgid']);
                                } elseif (in_array($extension, ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'log', 'json', 'txt', 'odt'])) {
                                    $html .= html_writer::tag('iframe', '', [ 'src' => $url, 'width' => '50%', 'style' => 'border: none;',
                                        'class' => 'pdf-frame', 'id' => 'pdfid' ]);
                                } else {
                                    $html .= html_writer::tag('iframe', '', [ 'src' => $url, 'width' => '50%', 'style' => 'border: none;',
                                        'class' => 'pdf-frame', 'id' => 'fileid' ]);
                                }
                            $html .= html_writer::span($file->get_filename(), 'd-flex justify-content-around mt-2');
                        $html .= html_writer::end_tag('a');
                    $html .= html_writer::end_div();
                }
            }
        }
    $html .= html_writer::end_div();
$html .= html_writer::end_div();

echo $OUTPUT->header();
$form->display();
echo $html;
echo $OUTPUT->footer();