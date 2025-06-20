<?php
require_once(dirname(__FILE__) . '/../../../config.php');
use block_coupon\helper;
/*SSEE*/if(helper::get_access() < MANAGER_ACCESS) redirect(new moodle_url('/my'),get_string('error:nopermission', 'block_coupon'));
$id = required_param('id', PARAM_INT);
$courseid = optional_param('courseid',0, PARAM_INT);
$groupid = optional_param('groupid',0, PARAM_INT);

$context       = \context_system::instance();

require_login();

$PAGE->navbar->add(get_string('view:group_pricing:title', 'block_coupon'));

$url = new moodle_url($CFG->wwwroot . '/blocks/coupon/view/purchase_history.php',array('id'=>$id));

$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title(get_string('view:group_pricing:title', 'block_coupon'));
$PAGE->set_heading(get_string('view:group_pricing:heading', 'block_coupon'));
$PAGE->set_pagelayout('standard');

// Make sure the moodle editmode is off.
helper::force_no_editing_mode();

$renderer = $PAGE->get_renderer('block_coupon');

echo $renderer->page_purchase_history();
