<?php
namespace block_coupon\tables;

defined('MOODLE_INTERNAL') || die();

use block_coupon\helper;
require_once($CFG->libdir . '/tablelib.php');

class paypal extends \table_sql {
    protected $courses = array();
    protected $count = 0;

    public function __construct() {
        parent::__construct(__CLASS__);
    }

    public function render($pagesize = 30, $useinitialsbar = false) {
        $columns = array('index','coursename','quantity','used','payment_gross','action');
        $this->define_table_columns($columns);
        $this->sortable(false);
        $this->collapsible(false);
        ob_start();
        $this->out($pagesize, $useinitialsbar);
        $out = ob_get_contents();
        ob_end_clean();
        return $out;
    }

    protected function get_query() {
        global $USER;
        $params = array();
        $fields = '*';
        $where = array('1 = 1');
        $where[] = 'userid = :userid';
        $params['userid'] = $USER->id;
        $where[] = 'payment_status = :payment_status';
        $params['payment_status'] = 'Completed';
        $where = implode(' AND ',$where);
        $from = '{block_coupon_purchase_info}';
        $query = "SELECT {$fields}
                FROM {$from}
                WHERE {$where} ";
        return array($query, $params);
    }

    public function get_sort_columns() {
        $sort = parent::get_sort_columns();
        //$sort['used'] = SORT_DESC;
        $sort['timemodified'] = SORT_DESC;
        return $sort;
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
        $this->courses = $DB->get_records_list('course','id',array_column($reportdata,'courseid'),'id ASC','id,fullname');
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
            $headers[] = get_string('paypal:heading:' . $name, 'block_coupon');
        }
        $this->define_headers($headers);
    }

    public function other_cols($column, $row) {
        global $OUTPUT;
        switch($column){
            case 'index':
                return ++$this->count;
                break;
            case 'coursename':
                return $this->courses[$row->courseid]->fullname;
                break;
            case 'payment_gross':
                return '$'.$row->$column;
                break;
            case 'action':
                if(($row->quantity - $row->used) > 0){
                    $params = array('id'=>$this->baseurl->param('id'),'pid'=>$row->id);
                    $editurl = new \moodle_url('/blocks/coupon/view/generate_email.php',$params);
                    $editbtn = new \single_button($editurl,get_string('table:paypal:editlabel','block_coupon'));
                    return $OUTPUT->render($editbtn);
                }
                break;
        }
        return null;
    }
}
