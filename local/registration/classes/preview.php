<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/csvlib.class.php');
require_once($CFG->dirroot.'/local/registration/locallib.php');

class preview extends \html_table {

    protected $cir;

    protected $filecolumns;

    protected $previewrows;
    protected $noerror = true;


    public function __construct(\csv_import_reader $cir, array $filecolumns, int $previewrows) {
        parent::__construct();
        $this->cir = $cir;
        $this->filecolumns = $filecolumns;
        $this->previewrows = $previewrows;

        $this->id = "uupreview";
        $this->attributes['class'] = 'generaltable';
        $this->head = array();
        $this->data = $this->read_data();

        $this->head[] = get_string('uucsvline', 'local_registration');
        foreach ($filecolumns as $column) {
            $this->head[] = $column;
        }
        $this->head[] = 'status';
        foreach($this->head as $headkey => $headervalue){
            if($headkey > 0){
                $headers[] = get_string($headervalue, 'local_registration');
            }else{
                $headers[] = $headervalue;
            }
        }
        if($headers){
            $this->head = $headers;
        }
    }

    protected function read_data() {
        global $DB, $CFG;

        $data = array();
        $this->cir->init();
        $linenum = 1;
        while ($linenum <= $this->previewrows and $fields = $this->cir->next()) {
            $linenum++;
            $rowcols = array();
            $rowcols['line'] = $linenum;
            foreach ($fields as $key => $field) {
                $rowcols[$this->filecolumns[$key]] = s(trim($field));
            }

            $rowcols['status'] = array();

            if (!empty($rowcols['couponcode'])) {
                $couponcode = $DB->get_field('local_registration','couponcode', ['couponcode' => $rowcols['couponcode']], IGNORE_MULTIPLE);
                if ($rowcols['couponcode'] == $couponcode) {
                    $rowcols['status'][] = get_string('duplicatecouponcode', 'local_registration');
                }
            } else {
                $rowcols['status'][] = get_string('missingcouponcode', 'local_registration');
            }

            if(trim($rowcols['visible']) == ''){
                $rowcols['status'][] = get_string('missingvisible', 'local_registration');
            } elseif (!checkvisible($rowcols['visible'])) {
                $rowcols['status'][] = get_string('invaliddata', 'local_registration', 
                get_string('visible', 'local_registration'));
            }

            if(trim($rowcols['course']) == ''){
                $rowcols['status'][] = get_string('missingcourse', 'local_registration');
            }elseif(!$DB->record_exists('course',['shortname'=>$rowcols['course']])){
                $rowcols['status'][] = get_string('coursenotexist', 'local_registration');
            }
    
            if(trim($rowcols['duration']) == ''){
                $rowcols['status'][] = get_string('missingduration', 'local_registration');
            } else if (!is_numeric($rowcols['duration']) || $rowcols['duration']  <= 0 ) {
                $rowcols['status'][] = get_string('invaliddata', 'local_registration', 
                get_string('duration', 'local_registration'));
            }    
            
            $rowcols['visible'] = checkvisible($rowcols['visible']);
            $rowcols['status'] = implode('<br />', $rowcols['status']);
            $data[] = $rowcols;
        }
        if ($fields = $this->cir->next()) {
            $data[] = array_fill(0, count($fields) + 2, '...');
        }
        $this->cir->close();

        return $data;
    }

    public function get_no_error() {
        return $this->noerror;
    }
}