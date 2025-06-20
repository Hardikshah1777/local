<?php

require_once '../../config.php';

$url = new moodle_url( '/courseslist.php');
$context = context_system::instance();

$PAGE->set_title(get_string('title', 'local_test3'));
$PAGE->set_heading(get_string('heading', 'local_test3'));
$PAGE->set_url($url);
$PAGE->set_context($context);
require_admin();

echo $OUTPUT->header();
$cats = $DB->get_records('course_categories', ['visible' => 1]);
echo html_writer::start_div('course-main-div');
foreach ($cats as $cat) {
    $courses = $DB->get_records('course', ['category' => $cat->id]);
    echo html_writer::tag('p', $cat->name, ['class' => 'course-container-title-div']);
    echo html_writer::start_div('course-list-container');
    foreach ($courses as $course) {
        echo html_writer::start_div('course-item-container');
        $coursecontext = context_course::instance($course->id);
        $fs = get_file_storage();
        $fileurl = $fs->get_area_files($coursecontext->id,'course','overviewfiles',0);
        foreach ($fileurl as $file) {
            if (!empty($file->get_filesize()) && $file->get_filesize() > 0) {
                $courseimgurl = moodle_url::make_pluginfile_url($coursecontext->id, $file->get_component(), $file->get_filearea(), '', '', $file->get_filename());
                echo html_writer::start_div('course-item-image-container');
                    echo html_writer::img($courseimgurl,'Course image', ['class' => 'course-image-item']);
                echo html_writer::end_div();
            }
        }
                echo html_writer::start_div('course-name-text-container');
                    echo html_writer::tag('span', $course->fullname);
                echo html_writer::end_div();
        echo html_writer::end_div();
    }
    echo html_writer::end_div();
}
echo html_writer::end_div();

echo '<style>
.course-main-div{

}
.course-container-title-div{
    color: #f37021;
    font-size: 1.25rem;
    font-weight: 700;
    margin-bottom: 0;
}
.course-list-container{
    display: flex;
    align-items: center;
    justify-content: start;
    flex-wrap: wrap;
}
.course-item-container{
    width: 25%;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 2rem 2rem 0 2rem;
}
.course-item-image-container{
    border: 0.063rem solid #f37021;
    border-radius: 0.5rem;
    overflow: hidden;
}
.course-image-item{
    height: 10rem;
    width: 14rem;
}
.course-name-text-container{
    padding: 0.5rem 0;
    color: #f37021;
    font-size: 0.85rem;
}
@media screen and (max-width:450px){
    .course-item-container{
        width: 50%;        
        padding: 1rem 0 0 0;
    }
    .course-image-item{
        height: 7rem;
        width: 10rem;
    }
}
@media (min-width: 451px) and (max-width:600px) {
.course-item-container{
        width: 50%;        
        padding: 1rem 0 0 0;
    }
    .course-image-item{
        height: 8rem;
        width: 12rem;
    }
}
@media (min-width: 601px) and (max-width:900px){
    .course-item-container{
        width: 33%;
    }
    .course-image-item{
        height: 8rem;
        width: 12rem;
    }
}   

@media (min-width: 901px) and (max-width:1050px){
    .course-item-container{
        width: 33%;
    }
    .course-image-item{
        height: 9rem;
        width: 13rem;
    }
}
</style>';
echo $OUTPUT->footer();

