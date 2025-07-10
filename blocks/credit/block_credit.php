<?php

class block_credit extends block_base {

    public function init()
    {
        $this->title = get_string('pluginname', 'block_credit');
    }

    public function get_content()
    {
        global $DB;
        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $text = html_writer::tag('a','Click to view courses', ['href' => new  moodle_url('/blocks/credit/premiumcourses.php')]);
        $this->content->text = $text;

        return $this->content;
    }

}
