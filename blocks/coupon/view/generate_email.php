<?php

require_once(dirname(__FILE__) . '/../../../config.php');

use block_coupon\helper;

$id = required_param('id', PARAM_INT);
$pid = required_param('pid',PARAM_INT);
$instance = $DB->get_record('block_instances', array('id' => $id), '*', MUST_EXIST);
$record = $DB->get_record('block_coupon_purchase_info',array('id'=>$pid,'payment_status'=>'Completed'),'*',MUST_EXIST);
$redirect_url = new moodle_url('/blocks/coupon/view/generate_coupon.php',array('id'=>$id));

$context       = \context_block::instance($instance->id);
$coursecontext = $context->get_course_context(false);
$course = false;
if ($coursecontext !== false) {
    $course = $DB->get_record("course", array("id" => $coursecontext->instanceid));
}
if ($course === false) {
    $course = get_site();
}

require_login($course, true);

$remaining = $record->quantity - $record->used;
if($record->userid != $USER->id){
    redirect($redirect_url,get_string('view:generate_email:notsameuser','block_coupon'));
}elseif ($remaining < 1){
    redirect($redirect_url,get_string('view:generate_email:noremaining','block_coupon',$remaining));
}

$PAGE->navbar->add(get_string('page:generate_email:title', 'block_coupon'));

$url = new moodle_url($CFG->wwwroot . '/blocks/coupon/view/generate_email.php', array('id' => $id));
$PAGE->set_url($url);

$PAGE->set_title(get_string('view:generate_email:title', 'block_coupon'));
$PAGE->set_heading(get_string('view:generate_email:heading', 'block_coupon'));
$PAGE->set_context($context);
$PAGE->set_course($course);
$PAGE->set_pagelayout('standard');

// Make sure the moodle editmode is off.
helper::force_no_editing_mode();
require_capability('block/coupon:generatecoupons', $context);
$renderer = $PAGE->get_renderer('block_coupon');
echo $renderer->page_coupon_generator_email($record);
