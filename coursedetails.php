<?php

require_once 'config.php';

$context = context_system::instance();
$cid = optional_param('id',0,PARAM_INT);
$enrolcid = optional_param('enrolcid',0,PARAM_INT);

$PAGE->set_title("Course Details");
$PAGE->set_heading("Course Details");
$PAGE->set_context($context);
require_login();

if (!empty($cid)) {
    $course = $DB->get_record('course', ['id' => $cid]);
}

if (!empty($enrolcid)) {
    $coursecontext = context_course::instance($course->id);
    $url = new moodle_url('/my/courses.php', ['id' => $course->id]);
    $pageurl = new moodle_url('/coursedetails.php', ['id' => $course->id]);
    $enrol = enrol_get_plugin('manual');
    $user = core_user::get_user($USER->id);
    $role = $DB->get_record('role', ['shortname' => 'student']);
    $instance = $DB->get_record('enrol', array('courseid' => $enrolcid, 'enrol' => 'manual'));
    if (is_enrolled($coursecontext,$USER)){
        redirect($pageurl,\core\notification::warning('You are already enrolled in course '.$course->shortname));
    }else{
        $enrol->enrol_user($instance, $user->id, $role->id,);
        redirect($url, \core\notification::success('You are successfully enrol in course '.$course->shortname));
    }
}

$coursecontext = context_course::instance($course->id);
$fs = get_file_storage();
$fileurl = $fs->get_area_files($coursecontext->id,'course','overviewfiles',0);
$backurl = new moodle_url('/courseslist.php');
echo $OUTPUT->header();
foreach ($fileurl as $file) {
    if (!empty($file->get_filesize()) && $file->get_filesize() > 0) {
        $courseimgurl = moodle_url::make_pluginfile_url($coursecontext->id, $file->get_component(), $file->get_filearea(), '', '', $file->get_filename());
        echo html_writer::start_div();
        echo html_writer::tag('h3', $course->fullname,['class'=>'course-detail-container']);
            echo html_writer::start_div('course-detail-item-container');
            echo html_writer::img($courseimgurl,'Course image', ['class' => 'course-detail-item-image']);
            echo html_writer::tag('a', "RETURN TO CATALOGUE", ['href' => $backurl, 'class' => 'catalogue-btn']);
            echo html_writer::end_div();
        echo html_writer::end_div();
    }
}

echo html_writer::start_div();
    echo html_writer::tag('p', $course->summary,['class'=>'course-detail-desc']);
echo html_writer::end_div();
echo html_writer::start_div();
    $enrol = new moodle_url('/coursedetails.php', ['id' => $cid, 'enrolcid' => $course->id]);
    echo html_writer::tag('a', 'Click here to enrol in this course', ['href' => $enrol,'class' => 'enroll-course-btn']);
echo html_writer::end_div();

echo '<style>
.course-detail-container{
    font-size: 1.6rem;
    color: #f37021;
    font-weight: bold;
}
.course-detail-item-container{
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin: 1.25rem 0;
}
.course-detail-item-image{
    height: 10rem;
    width: 12rem;
}
.catalogue-btn{
    font-size: 0.85rem;
    font-weight: bold;
    color: white;
    background-color: #f37021;
    padding: 0.7rem 2rem;
    text-decoration: none;
    cursor: pointer;
}
.catalogue-btn:hover{
    color: white;
    text-decoration: none;
}
.course-detail-desc{
    margin: 1.5rem 0;
    font-size: 0.85rem;
}
.enroll-course-btn{
    color: #f37021;
    text-decoration: none;
    cursor: pointer;
    font-size: 1rem;
    font-weight: bold;
}
.enroll-course-btn:hover{
    color: #f37021;
    text-decoration: none;
    cursor: pointer;
}
@media screen and (max-width:600px){
    .course-detail-item-container{
        flex-direction: column;
        justify-content: center;
        align-items: center;
    }
    .course-detail-item-image{
        height: 12rem;
        width: 14rem;
    }
    .catalogue-btn{
        margin-top: 1rem;
    }
}
</style>';
echo $OUTPUT->footer();