<?php

namespace report_kln\table;

use coding_exception;
use core_table\dynamic;
use core_table\local\filter\filterset;
use html_writer;
use paging_bar;
use table_sql;

require_once ($CFG->libdir . '/tablelib.php');

class dynamictable extends table_sql implements dynamic {

    public function get_filters() {
        $filters = [];

        if (!$this->filterset instanceof filterset) {
            throw new coding_exception('Unknown filterset class');
        }

        foreach ($this->filterset->get_filters() as $filter) {
            $filters[$filter->get_name()] = !isset($filters[$filter->get_name()]) ?
                $filter->current() :  $filter->get_filter_values();
        }
        return $filters;
    }

    function start_html() {
        global $OUTPUT;

        // Render the dynamic table header.
        echo $this->get_dynamic_table_html_start();

        // Render button to allow user to reset table preferences.
        echo $this->render_reset_button();

        // Do we need to print initial bars?
        $this->print_initials_bar();

        if (in_array(TABLE_P_TOP, $this->showdownloadbuttonsat)) {
            echo $this->download_buttons();
        }

        $this->wrap_html_start();
        // Start of main data table

        echo html_writer::start_tag('div', array('class' => 'no-overflow'));
        echo html_writer::start_tag('table', $this->attributes);

    }

}