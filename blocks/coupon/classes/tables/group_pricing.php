<?php
namespace block_coupon\tables;

defined('MOODLE_INTERNAL') || die();

use block_coupon\helper;
require_once($CFG->libdir . '/tablelib.php');

class group_pricing extends \table_sql {

    protected $id;
    protected $courseid;
    protected $groupid;

    public function __construct($id = 0,$courseid = 0,$groupid = 0) {
        parent::__construct(__CLASS__);
        $this->id = (int)$id;
        $this->courseid = (int)$courseid;
        $this->groupid = (int)$groupid;
    }

    public function render($pagesize, $useinitialsbar = true) {

        $columns = array('coursename','name','price','action');
        $this->define_table_columns($columns);
        $this->collapsible(false);
        $this->sortable(false);
        echo $this->table_filtering();
        $this->out($pagesize, $useinitialsbar);
    }

    protected function get_query() {
        $params = array();
        $fields = ' g.*, c.fullname as coursename,gp.price';
        $where = array('1 = 1');
        if(!empty($this->courseid)){
            $where[] = 'g.courseid = :courseid';
            $params['courseid'] = $this->courseid;
        }
        if(!empty($this->groupid)){
            $where[] = 'g.id = :groupid';
            $params['groupid'] = $this->groupid;
        }
        $where = implode(' AND ',$where);
        $from = '{groups} g
                JOIN {course} c ON c.id = g.courseid
                LEFT JOIN {block_coupon_group_pricing} gp ON gp.courseid = g.courseid AND gp.groupid = g.id';
        $query = "SELECT {$fields}
                FROM {$from}
                WHERE {$where} ";
        return array($query, $params);
    }

    public function get_sort_columns() {
        $sort = parent::get_sort_columns();
        $sort['c.fullname'] = SORT_ASC;
        $sort['g.name'] = SORT_ASC;
        return $sort;
    }

    public function table_filtering(){
        global $OUTPUT;
        $out = '';
        $courses = helper::get_visible_courses('id,fullname');
        $courses_options = array_column($courses,'fullname','id');
        $select = new \single_select($this->baseurl,'courseid',$courses_options,$this->courseid);
        $out .= $OUTPUT->render($select);
        return $out;
    }

    public function query_db($pagesize, $useinitialsbar=true) {
        global $DB;

        list($sql, $params) = $this->get_query(false);

        if (!$this->is_downloading()) {
            $total = $DB->count_records_sql('SELECT COUNT(*) FROM ('.$sql.') AS c', $params);
            $this->pagesize($pagesize, $total);
        }

        $sort = $this->get_sql_sort();
        if ($sort) {
            $sort = "ORDER BY $sort";
        }

        $sql .= $sort;

        if (!$this->is_downloading()) {
            $reportdata = $DB->get_records_sql($sql, $params, $this->get_page_start(), $this->get_page_size());
        } else {
            $reportdata = $DB->get_records_sql($sql, $params);
        }

        $this->rawdata = $reportdata;
    }

    public function out($pagesize, $useinitialsbar, $downloadhelpbutton='') {
        $this->setup();
        $this->query_db($pagesize, $useinitialsbar);
        $this->build_table();
        $this->finish_output();
    }

    protected function define_table_columns($columns) {
        $this->define_columns($columns);
        $headers = array();
        foreach ($columns as $name) {
            $headers[] = get_string('report:heading:' . $name, 'block_coupon');
        }
        $this->define_headers($headers);
    }

    public function col_action($row){
        global $OUTPUT;
        $url = new \moodle_url($this->baseurl->out_omit_querystring(),array('id'=>$this->id,'courseid'=>$row->courseid,'groupid'=>$row->id));
        $edit = new \single_button($url,get_string('table:group_pricing:editlabel','block_coupon'));
        return $OUTPUT->render($edit);
    }
    public function col_price($row){
        if($row->price) return '$'.$row->price;
        return null;
    }

}
