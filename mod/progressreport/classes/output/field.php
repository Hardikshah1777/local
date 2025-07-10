<?php

namespace mod_progressreport\output;

use mod_progressreport\constants;
use renderer_base;
use stdClass;

class field {

    public $id;

    public $userid;

    public $field;

    public $fieldval;

    public $attemptuser;

    public $progressreportid;

    public function __construct($field,$fieldval,$userid) {
        $this->field = $field;
        $this->fieldval = $fieldval;
        $this->userid = $userid;
    }


        public function export_for_template(renderer_base $output) {

            $userinfodata = new stdClass();
            $userinfodata->id = $this->id;
            $userinfodata->field = $this->field;
            $userinfodata->userfield = $this->fieldval;
            if(is_number($this->fieldval)){
                $pronamedata = profile_data($this->fieldval,$this->userid);
                $userinfodata->value  =  $pronamedata ;
            }else{
                $namedata = userfield_data($this->fieldval,$this->userid,$this->progressreportid,$this->attemptuser,$this->id);
                $userinfodata->value =  $namedata ;
                if($this->fieldval == constants::GROUPS[constants::FILLABLE]){
                    $userinfodata->instructorname = true;
                }
            }
            return $userinfodata;
    }
}