<?php

namespace mod_evaluation\output;

use mod_evaluation\constants;
use renderable;
use renderer_base;
use stdClass;
use templatable;

class userfield  implements renderable, templatable{

    public $id;

    public $userid;

    public $field;

    public $fieldval;

    public $evaluationuserid;

    public $attemptuser;

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
            $namedata = userfield_data($this->fieldval,$this->userid,$this->evaluationuserid,$this->attemptuser,$this->id);
            $userinfodata->value =  $namedata ;
            if($userinfodata->value == constants::GROUPS[constants::FILLABLE]){
                $userinfodata->instructorname = true;
            }
        }
        return $userinfodata;
    }
}