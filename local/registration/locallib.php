<?php
defined('MOODLE_INTERNAL') || die();

class uu_progress_tracker {
    protected $_row;

    public $columns = [];

    protected $headers = [];

    public function __construct() {
        $this->headers = [

            'status' => get_string('status', 'local_registration'),
            'line' => get_string('uucsvline', 'local_registration'),
            'couponcode'=>get_string('couponcode', 'local_registration'),
            'visible'=>get_string('visible', 'local_registration'),
            'course'=>get_string('course', 'local_registration'),
            'duration'=>get_string('duration', 'local_registration')
        ];
        $this->columns = array_keys($this->headers);
    }
    
    public function start() {
        $ci = 0;
        echo '<table id="uuresults" class="generaltable boxaligncenter flexible-wrap" summary="'.get_string('uploadcouponresult', 'local_registration').'">';
        echo '<tr class="heading r0">';
        foreach ($this->headers as $key => $header) {
            echo '<th class="header c'.$ci++.'" scope="col">'.$header.'</th>';
        }
        echo '</tr>';
        $this->_row = null;
    }
    public function flush() {
        if (empty($this->_row) or empty($this->_row['line']['normal'])) {
            // Nothing to print - each line has to have at least number
            $this->_row = array();
            foreach ($this->columns as $col) {
                $this->_row[$col] = array('normal'=>'', 'info'=>'', 'warning'=>'', 'error'=>'');
            }
            return;
        }
        $ci = 0;
        $ri = 1;
        echo '<tr class="r'.$ri.'">';
        foreach ($this->_row as $key=>$field) {
            foreach ($field as $type=>$content) {
                if ($field[$type] !== '') {
                    $field[$type] = '<span class="uu'.$type.'">'.$field[$type].'</span>';
                } else {
                    unset($field[$type]);
                }
            }
            echo '<td class="cell c'.$ci++.'">';
            if (!empty($field)) {
                echo implode('<br />', $field);
            } else {
                echo '&nbsp;';
            }
            echo '</td>';
        }
        echo '</tr>';
        foreach ($this->columns as $col) {
            $this->_row[$col] = array('normal'=>'', 'info'=>'', 'warning'=>'', 'error'=>'');
        }
    }
    public function track($col, $msg, $level = 'normal', $merge = true) {
        if (empty($this->_row)) {
            $this->flush();
        }
        if (!in_array($col, $this->columns)) {
            debugging('Incorrect column:'.$col);
            return;
        }
        if ($merge) {
            if ($this->_row[$col][$level] != '') {
                $this->_row[$col][$level] .='<br />';
            }
            $this->_row[$col][$level] .= $msg;
        } else {
            $this->_row[$col][$level] = $msg;
        }
    }
    public function close() {
        $this->flush();
        echo '</table>';
    }
}
function uu_validate_coupon_upload_column(csv_import_reader $cir, $stdfields, moodle_url $returnurl) {
    $columns = $cir->get_columns();

    if (empty($columns)) {
        $cir->close();
        $cir->cleanup();
        throw new \moodle_exception('cannotreadtmpfile', 'error', $returnurl);
    }
    if (count($columns) < 2) {
        $cir->close();
        $cir->cleanup();
        throw new \moodle_exception('csvfewcolumns', 'error', $returnurl);
    }
    $processed = array();
    foreach ($columns as $key=>$unused) {
        $field = $columns[$key];
        $field = trim($field);
      
        $lcfield = core_text::strtolower($field);
        if (in_array($field, $stdfields) or in_array($lcfield, $stdfields)) {
            $newfield = $lcfield;
        }else if (in_array($field,$stdfields)) {
            $newfield = $field;

        }else {
            $cir->close();
            $cir->cleanup();
            throw new \moodle_exception('invalidfieldname', 'error', $returnurl, $field);
        }
        if (in_array($newfield, $processed)) {
            $cir->close();
            $cir->cleanup();
            throw new \moodle_exception('duplicatefieldname', 'error', $returnurl, $newfield);
        }
            
        $processed[$key] = $newfield;
    }
    return $processed;
}

function checkvisible($visiblestatus){
    $visible = '';

    if(is_number($visiblestatus)){
        if($visiblestatus == 1){
            $visible =  get_string('enable');
        }elseif($visiblestatus == 0){
            $visible =  get_string('disable');
        }
    }
    return $visible;
}
