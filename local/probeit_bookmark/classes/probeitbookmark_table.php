<?php

namespace local_probeit_bookmark;

use moodle_url;
use table_sql;

require_once($CFG->libdir . '/tablelib.php');

class probeitbookmark_table extends table_sql
{
    public $perpage = 30;

    public function init()
    {
        $col = [
            'title' => get_string('tabletitle', 'local_probeit_bookmark'),
            'description' => get_string('description', 'local_probeit_bookmark'),
            'timecreated' => get_string('timecreated', 'local_probeit_bookmark'),
            'action' => get_string('action', 'local_probeit_bookmark'),
        ];

        $this->define_columns(array_keys($col));
        $this->define_headers(array_values($col));
        $this->sortable(false);
        $this->collapsible(false);

        $this->set_sql('*','{local_probeit_bookmark}', '1=1');
        $this->out($this->perpage, false);
    }

    public function col_title($col)
    {
        $title = \html_writer::link($col->link, $col->title, ['target' => '_blank']);
        return $title;
    }

    public function col_timecreated($col)
    {
        if (!empty($col->timecreated)) {
            $timecreated = userdate($col->timecreated, get_string('strftimedatetime'));
        } else {
            $timecreated = '-';
        }
        return $timecreated;
    }

    public function col_action($col) {

        global $OUTPUT;

        $editurl = new moodle_url('/local/probeit_bookmark/add.php', ['id' => $col->id]);
        $editlink = $OUTPUT->action_link($editurl,get_string('edit'),null, ['class' => 'btn btn-primary']);

        $deleteurl = new moodle_url('/local/probeit_bookmark/manage.php', ['deleteid' => $col->id]);
        $deletemdg = new \confirm_action(get_string('confirmmsg','local_probeit_bookmark'));
        $deletelink = $OUTPUT->action_link($deleteurl,get_string('delete'),$deletemdg, ['class' => 'btn btn-primary']);

        return $editlink.' '.$deletelink;
    }
}