<?php

namespace mod_meltassessment\output;

use confirm_action;
use moodle_url;
use renderable;
use renderer_base;
use stdClass;
use templatable;

class meltassessment_form implements renderable, templatable{

    /**
     * @var market[]
     */
    private $markets;
    /**
     * @var section[]
     */
    private $sections;
    /**
     * @var skill[]
     */

    private $skills;

    /**
     * @var field[]
     */
    private $field;

    public $meltassessmentid;

    public $meltassessmentname;

    public $meltassessmenturl;

    public $lesson;

    public $userid;

    private $meltassessmentdata;

    public  $save;

    public $meltassessmentuserid;

    public $notes;

    public $average;

    public $moduleid;

    protected $errors = [];

    public $attempt;

    public $postnote;

    protected $postdata = [];

    public function __construct(array $markets = [],array $sections = [],array $skills = []) {
        $this->markets = $markets;
        $this->sections = $sections;
        $this->skills = $skills;

    }

    public function add_market(market $market) {
        $this->markets[] = $market;
    }

    public function add_section(section $section){
        $this->sections[] = $section;
        $section->set_parent($this);
    }

    public function add_field(field $field){
        $this->field[] = $field;
    }

    public function set_meltassessmentdata(array $meltassessmentdata) {
        $this->meltassessmentdata = $meltassessmentdata;
        return $this;
    }

    public function get_meltassessmentdata(){
        return $this->meltassessmentdata;
    }

    public function get_markets(){
        return $this->markets;
    }

    public function set_errors(array $errors) {
        $this->errors += $errors;
        return $this;
    }

    public function get_errors() {
        return $this->errors;
    }

    /**
     * @return array
     */
    public function getPostdata(): array {
        return $this->postdata;
    }

    /**
     * @param array $postdata
     */
    public function setPostdata(array $postdata): void {
        $this->postdata = $postdata;
    }


    public function export_for_template(renderer_base $output) {
        global $OUTPUT,$DB;
        $result = new stdClass();
        $result->meltassessmentid = $this->meltassessmentid;
        $result->name = $this->meltassessmentname;
        $result->url = $this->meltassessmenturl;
        $result->userid = $this->userid;
        $result->meltassessmentuserid = $this->meltassessmentuserid;
        $result->save = (bool) $this->save;
        $result->attempt = (bool) $this->attempt;
        $result->lesson = [];

        $result->field = [];


        foreach ($this->field as $field){
            $result->field[] = $field->export_for_template($output);
        }

        if($this->lesson < 4) {
            if ($this->lesson == 1) {
                $result->marketweight = 'w-100';
            } else if ($this->lesson == 2) {
                $result->marketweight = 'w-50';
            }else{
                $result->marketweight = 'w-25';
            }
        }else{
            $result->marketweight = 'w-25';
        }

        foreach ($this->markets as $market){
            $result->market[] = $market->export_for_template($output);
        }
        $lessondates = $DB->get_records('meltassessment_user_lesson',['meltassessmentuserid' => $this->meltassessmentuserid]);
        $userdatas = $this->get_meltassessmentdata();
        foreach ($this->sections as $section){
            $sectioninfo = $section->export_for_template($output);
            foreach ($sectioninfo->skills as $skill) {
                $skill->lesson = [];
                for ($i = 1; $i <= $this->lesson; $i++){
                    $lesson = [
                            'no'=>$i,
                            'market' => [],
                    ];
                    foreach ($this->markets as $market){
                        $_market = clone $market;
                        if ($this->postdata) {
                            $_market->selected = isset($this->postdata[$skill->id]) && $this->postdata[$skill->id][$i] == $market->id;
                        } else {
                            foreach ($userdatas as $userdata) {
                                $_market->selected = $skill->id == $userdata->skillid
                                        && $i == $userdata->lessonnumber && $market->id == $userdata->marketid;
                                if ($_market->selected) {
                                    break;
                                }
                            }
                        }
                        $lesson['market'][] = $_market;
                    }
                    $skill->lesson[] = $lesson;
                }
            }
            $result->sections[] = $sectioninfo;
        }

        for ($i = 1; $i <= $this->lesson; $i++){
            $lesson = [
                    'no'=>$i,
                    'market' => [],
                    'lessondate' => '',
            ];
            foreach ($this->markets as $key => $market){
                $_market = clone $market;
                $lesson['market'][] = $_market;
            }
            foreach ($lessondates as $lessondate){
                if($i == $lessondate->lesson){
                    $date = gmdate('d M Y',$lessondate->time);
                    $lesson['lessondate'] = $date;
                }
            }
            $result->lesson[] = $lesson;
        }

        $i = 0;
        foreach ($this->average as $average){
            $result->lesson[$i]['average'] = $average->average;
            if($average->average != '0.0'){
                $lastavg = $average->average;
            }
            $i++;
        }

        $result->finalmark = $lastavg;
        $result->nolesson = $this->lesson;

        if(!empty($this->postnote)){
            $result->postcomments = $this->postnote;
        }else{
            $result->notes = $this->notes;
        }

        return $result;
    }



}