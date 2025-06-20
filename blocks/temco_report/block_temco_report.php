<?php


class block_temco_report extends block_base {

    public function init() {
        $this->title = get_string('title', 'block_temco_report');
    }

    function get_content() {

        if ($this->content !== NULL) {
            return $this->content;
        }

        $modulecompletion = new moodle_url( '#' );
        $coursecompletion = new moodle_url( '#' );

        $this->content = new stdClass;
        $content = html_writer::start_div('p-2');
        $content .= html_writer::start_div();
        $content .= html_writer::link( $modulecompletion, get_string( 'modulecompletion', 'block_temco_report' ) );
        $content .= html_writer::end_div();
        $content .= html_writer::start_div('mt-2');
        $content .= html_writer::link( $coursecompletion, get_string( 'coursecompletion', 'block_temco_report' ) );
        $content .= html_writer::end_div();
        $content .= html_writer::end_div();

        $this->content->text = $content;

        return $this->content;
    }

    function has_config() {
        return true;
    }
}