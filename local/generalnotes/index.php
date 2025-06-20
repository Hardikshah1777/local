<?php

use local_generalnotes_comment as comment;
use local_generalnotes_usertable as usertable;

require_once '../../config.php';
require_once $CFG->libdir . '/adminlib.php';
require_once $CFG->libdir . '/tablelib.php';
require_once $CFG->dirroot . '/group/lib.php';
require_once $CFG->dirroot . '/cohort/lib.php';

$courseid = optional_param('id',0,PARAM_INT);
$cohortid = optional_param('cohort',0,PARAM_INT);
$groupid = optional_param('group',0,PARAM_INT);
$noncontact = optional_param('noncontact',0,PARAM_INT);
$search = optional_param('search','',PARAM_TEXT);
$perpage = optional_param('perpage',30,PARAM_INT);

require_login();

$PAGE->set_context(context_system::instance());
$courses = get_user_capability_course(comment::cap,null,true,'fullname','fullname');
$results = cohort_get_all_cohorts(0,100000);
$urlparams = [];

if($cohortid > 0 && array_key_exists($cohortid,$results['cohorts'])){
    $urlparams['cohort'] = $cohortid;
    $courseid = $groupid = 0;
}
if($courseid > 0){
    $urlparams['id'] = $courseid;
    require_login($courseid);
    $PAGE->set_course(get_site());
    //require_capability(comment::cap,context_course::instance($courseid));
}
if($groupid > 0){
    $urlparams['group'] = $groupid;
}
if($noncontact > 0){
    $urlparams['noncontact'] = $noncontact;
}
if(trim($search)){
    $urlparams['search'] = trim($search);
}
admin_externalpage_setup('reportgeneralnotes','',$urlparams);

//$url = new moodle_url('/local/generalnotes/index.php');
//$url->params($urlparams);
//$PAGE->set_url($url);
//$PAGE->set_title(get_string('reporttitle',comment::TABLE));
//$PAGE->set_heading(get_string('reporttitle',comment::TABLE));

$table = new usertable($courseid,$cohortid,$groupid,$noncontact,$search);

$formattedcourses = array_column($courses,'fullname','id');
$table->setCourses($formattedcourses ?? []);

if(array_key_exists($courseid,$formattedcourses)){
    $groups = groups_get_all_groups($courseid);
    $formattedgroups = array_column($groups,'name','id');
    $table->setGroups($formattedgroups ?? []);
}

$formattedcohorts = array_column($results['cohorts'],'name','id');
$table->setCohorts($formattedcohorts ?? []);

echo $OUTPUT->header();

$table->define_baseurl($PAGE->url);
$table->out($perpage, false);

echo $OUTPUT->footer();
