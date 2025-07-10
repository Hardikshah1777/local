<?php

namespace local_squibit\output;

use local_squibit\utility;
use renderable;
use renderer_base;
use templatable;
use local_squibit\table\courses;

class course_filterform implements renderable, templatable {

    /**
     * @var courses
     */
    private $courses;

    public function __construct(courses $courses) {
        $this->courses = $courses;
    }

    public function export_for_template(renderer_base $output) {
        $filters = $this->courses->get_filters();

        $context = [];
        $context['courseid'] = !empty($filters['courseid']) ? $filters['courseid'] : '';
        $context['fullname'] = !empty($filters['fullname']) ? $filters['fullname'] : '';
        $context['status'] = !empty($filters['status']) ? $filters['status'] : '';
        $context['courseteacher'] = !empty($filters['courseteacher']) ? $filters['courseteacher'] : '';
        $statusoptions = [];
        foreach (utility::STATUSES as $statuskey => $statusvalue) {
            if(!empty($statusvalue)) {
                $statusoptions[] = [ 'value' => $statusvalue, 'key' => get_string($statuskey, 'local_squibit')];
            }
        }
        $context['statusoption'] = $statusoptions;

        $options = [];
        $options[] = ['value' => 1, 'key' => get_string('yes')];
        $options[] = ['value' => 2, 'key' => get_string('no')];
        $context['courseteacheroption'] = $options;

        return $context;
    }
}