<?php

use block_courseslist\table\allcourse;
use block_courseslist\table\allcourse_filterset;

class block_courseslist extends block_base {

    public function init() {
        $this->title = get_string('courselist:title', 'block_courseslist');
    }

    public function get_content() {
        global $PAGE;

        $courseids = allcourse::get_courseids();
        if (empty($courseids) && !is_siteadmin()) {
            return null;
        }

        if ($this->content !== NULL) {
            return $this->content;
        }

        $table = new allcourse('allcourseid');
        $table->define_baseurl($PAGE->url);

        $filters = new allcourse_filterset();
        $table->set_filterset($filters);

        $content = $table->render($table::perpage, false);


        $this->content = new stdClass;
        $this->content->text = $content;

        return $this->content;
    }

    public function applicable_formats() {
        return ['my' => true];
    }
}