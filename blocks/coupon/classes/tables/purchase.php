<?php
namespace block_coupon\tables;

defined('MOODLE_INTERNAL') || die();

use block_coupon\helper;
require_once($CFG->libdir . '/tablelib.php');

class purchase extends \block_coupon\tables\paypal {
    protected $courses = array();
    protected $count = 0;

    public function render($pagesize = 30, $useinitialsbar = false) {
        $columns = array('index','fullname','payer_email','coursename','quantity','used','payment_gross');
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
        $params = array();
        $fields = 'b.*';
        $where = array('1 = 1');
        //$where[] = 'userid = :userid';
        //$params['userid'] = $USER->id;
        $where[] = 'b.payment_status = :payment_status';
        $params['payment_status'] = 'Completed';
        $where = implode(' AND ',$where);
        $from = '{block_coupon_purchase_info} b';
        $query = "SELECT {$fields}
                FROM {$from}
                WHERE {$where} ";
        return array($query, $params);
    }


    protected function define_table_columns($columns) {
        $this->define_columns($columns);
        $headers = array();
        foreach ($columns as $name) {
            $headers[] = get_string('purchase:heading:' . $name, 'block_coupon');
        }
        $this->define_headers($headers);
    }

    public function other_cols($column, $row) {
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
        }
        return null;
    }

    public function col_fullname($row) {
        $user = \core_user::get_user($row->userid);
        return parent::col_fullname($user);
    }
}
