<?php

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot.'/local/registration/locallib.php');
class process{
    protected $cir;
 
    protected $formdata;

    protected $upt;

    protected $filecolumns = null;
    protected $progresstrackerclass = null;
    protected $profilefields = [];
    protected $standardfields = [];

    protected $couponcreated = 0;
    protected $couponskipped = 0;
    protected $couponerror = 0;
    public function __construct(\csv_import_reader $cir, string $progresstrackerclass = null) {
    $this->cir = $cir;
        if ($progresstrackerclass) {
            if (!class_exists($progresstrackerclass) || !is_subclass_of($progresstrackerclass, \uu_progress_tracker::class)) {
                throw new \coding_exception('Progress tracker class must extend \uu_progress_tracker');
            }
            $this->progresstrackerclass = $progresstrackerclass;
        } else {
            $this->progresstrackerclass = \uu_progress_tracker::class;
        } 
    
        $this->find_fields();
    }
    protected function find_fields(): void {
        $this->standardfields = ['couponcode','visible','course','duration'];
    }
    public function get_file_columns() {
        if ($this->filecolumns === null) {
            $returnurl = new \moodle_url('/local/registration/bulkcoupon.php');
            $this->filecolumns = uu_validate_coupon_upload_column($this->cir,
                $this->standardfields,$returnurl);
        }
       return $this->filecolumns;
    }  
    public function process() {
      
        $this->cir->init();

        $classname = $this->progresstrackerclass;
        $this->upt = new $classname();
        $this->upt->start(); 

        $linenum = 1; 
        while ($line = $this->cir->next()) {
            $this->upt->flush();
            $linenum++;
            $this->upt->track('line', $linenum);
            $this->process_line($line); 
        }

        $this->upt->close(); 
        $this->cir->close();
        $this->cir->cleanup(true);
    }
    public function process_line(array $line) {
        global $DB, $CFG, $SESSION;
        $skip = 0;
        if (!$coupon = $this->prepare_coupon_record($line)) {
            return;
        }

        if (!empty($coupon->couponcode)) {
            $couponcode = $DB->get_field('local_registration','couponcode', ['couponcode' => $coupon->couponcode], IGNORE_MULTIPLE);
            if ($coupon->couponcode == $couponcode) {
                $this->upt->track('status', get_string('couponcodeexist', 'local_registration'), 'warning');
                $skip++;
            }
        } else {
            $this->upt->track('status', get_string('invaliddata', 'local_registration', 'couponcode'), 'error');
            $skip++;
        }

        if(trim($coupon->visible) == ''){
            $this->upt->track('status', get_string('missingvisible', 'local_registration'), 'error');
            $skip++;
        } elseif (!checkvisible($coupon->visible)) {
            $this->upt->track('status', get_string('invaliddata', 'local_registration', 
            get_string('visible', 'local_registration')), 'error');
            $skip++;
        }
        if(trim($coupon->course) == ''){
            $this->upt->track('status', get_string('missingcourse', 'local_registration'), 'error');
            $skip++;
        } elseif(!$data = $DB->get_record('course',['shortname'=>$coupon->course],'id')){
            $this->upt->track('status', get_string('coursenotexist', 'local_registration'), 'error');
            $skip++;
        }

        if(trim($coupon->duration) == ''){
            $this->upt->track('status', get_string('missingduration', 'local_registration'), 'error');
            $skip++;
        } else if (!is_numeric($coupon->duration) || $coupon->duration  <= 0 ) {
            $this->upt->track('status', get_string('invaliddata', 'local_registration', 
            get_string('duration', 'local_registration')), 'error');
            $skip++;
        }

        if ($skip != 0) {
            $this->couponskipped++;
            $this->couponerror++;
            return null;
        }

        $coupon->duration = DAYSECS * $coupon->duration;
        $coupon->courseid = $data->id;
        $DB->insert_record('local_registration',  $coupon);
        $this->upt->track('status', get_string('couponcodcreated', 'local_registration'),'warning');
        $this->couponcreated++;    
    }   

    protected function prepare_coupon_record(array $line): ?\stdClass {
        global $CFG;

        $coupon = new \stdClass();

        foreach ($line as $keynum => $value) {

            if (!isset($this->get_file_columns()[$keynum])) {
                continue;
            }
            $key = $this->get_file_columns()[$keynum];

            if (strpos($key, 'coupon_field_') === 0) {
                
                if (isset($key)) {
                    $coupon->$key = array();
                    $coupon->{$key['text']}   = $value;
                    $coupon->{$key['format']} = FORMAT_MOODLE;

                } else {
                    $coupon->$key = trim($value);
                }
            } else {
                $coupon->$key = trim($value);
            }

            if (in_array($key, $this->upt->columns)) {
                if($key == 'visible'){
                    $this->upt->track($key, checkvisible($value), 'normal');
                }else{
                    $this->upt->track($key, s($value), 'normal');                    
                }         
            }
        }
        return $coupon;
    }

    public function get_stats() {
        $lines = [];

        $lines[] = get_string('couponcodcreated', 'local_registration').' : '.$this->couponcreated;

        $lines[] = get_string('couponcodskipped', 'local_registration').' : '.$this->couponskipped;
        
        $lines[] = get_string('error').' : '.$this->couponerror;

        return $lines;
    }
}
