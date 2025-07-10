<?php

use local_studentfiles\util;
use local_studentfiles\form;
use local_studentfiles\table;

require_once dirname(__FILE__) . '/../../config.php';

$f = optional_param('f',null,PARAM_INT);

$systemcontext = context_system::instance();
$url = new moodle_url('/local/studentfiles/upload.php');
$title = util::get_string('studentfiles');

$PAGE->set_context($systemcontext);
$PAGE->set_url($url);
$PAGE->set_title($title);
$PAGE->navbar->add($title,$url);

require_login();

$PAGE->navigation->find(util::component,navbar::TYPE_CUSTOM)->make_active();

if(!util::user_can_access()) {
    print_error('nopermission',util::component);
}
$isadmin = is_siteadmin();

$templates = $DB->get_records_select(util::templatetable,'1=1',
        ['userid' => $USER->id, 'siteadmin' => $isadmin],'','id,name,subject,message');
$form = new form($url,['templates' => $templates,]);
$table = new table();

if($f){
    $form->set_data([form::field => $f,]);
}

if($formdata = $form->get_data()){
    $count = $form->save_drafts($formdata);
    if($count > 0){
        \core\notification::info(util::get_string('nofilesuploaded',$count));
    }
    if($formdata->saveastemplate) {
        $record = (object)[
                'name' => $formdata->templatename,
                'subject' => $formdata->mailsubject,
                'message' => $formdata->mailbody,
        ];
        $record->userid = $USER->id;
        $record->timecreated = $record->timemodified = time();
        $record->id = $DB->insert_record(util::templatetable,$record);
        \core\notification::info(util::get_string('templatesaved',$count));
    }
    redirect($url->out(true,['f' => $formdata->{form::field}]));
}

$PAGE->requires->js_call_amd('local_studentfiles/form','registerEventListener',[]);
$PAGE->requires->strings_for_js(['notemplatesubject','notemplatemessage','feedbacksubject','feedbackmessage','certificatesubject','certificatemessage',],util::component);

echo $OUTPUT->header();

echo $OUTPUT->container_start('text-right mb-2');
echo $OUTPUT->single_button('templates.php',util::get_string('managetemplates'),'post',['primary' =>true,]);
echo $OUTPUT->container_end();

$form->display();

echo html_writer::empty_tag('hr');

echo $OUTPUT->heading(util::get_string('uploadhistory'));

$table->out();

echo $OUTPUT->footer();
