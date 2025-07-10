<?php

namespace local_studentfiles;

require_once ($CFG->libdir . '/tablelib.php');

class table extends \table_sql {
    private $sr = 0;

    public $is_sortable = false;
    public $is_collapsible = false;

    public function __construct() {
        parent::__construct(str_replace('\\','',__CLASS__));
    }

    public function out($pagesize = 30, $useinitialsbar = false, $downloadhelpbutton = '') {
        $cols = [
            'sr' => util::get_string('sr'),
            'user' => util::get_string('user'),
            'file' => util::get_string('file'),
            'timeuploaded' => util::get_string('timeuploaded'),
        ];
        $this->define_headers(array_values($cols));
        $this->define_columns(array_keys($cols));
        $this->set_sql('h.id AS historyid,h.filename AS file,h.timecreated,u.id,u.firstname,u.lastname',
                '{'.util::dbtable.'} h LEFT JOIN {user} u ON u.id = h.userid AND u.suspended = 0 AND u.deleted = 0',
                '1 = 1');
        $this->column_style_all('width','25%');
        parent::out($pagesize, $useinitialsbar, $downloadhelpbutton);
    }

    public function col_sr($row){
        return ++$this->sr;
    }

    public function col_user($row){
        return $row->id ?  \html_writer::link(
                new \moodle_url('/user/profile.php',['id' => $row->id,]),
                fullname($row),['target'=>'_blank']) : null;
    }

    public function col_timeuploaded($row){
        return userdate($row->timecreated);
    }
}
