<?php

namespace mod_evaluation\output;

use moodle_url;
use renderable;
use renderer_base;
use stdClass;
use templatable;

class evaluationform  implements renderable, templatable  {

    /**
     * @var level[]
     */
    private $levels;
    /**
     * @var section[]
     */
    private $sections;
    /**
     * @var skill[]
     */
    private $skills;

    /**
     * @var userfield[]
     */
    private $userfields;


    // user firstname and lastname
    public $username;

    // user id
    public $userid;

    // evaluation id;
    public $evaluationid;

    // evaluation name
    public $evaluationname;

    // submit url
    public $evaluationurl;

    public $city;

    public $id;

    public $comments;

    public $moduleid;

    public $attempt;

    public $agree;

    public $result;

    public $grade;

    public $reson;

    public $student;

    public $additionaltraining;

    protected $evaluationdata = [];

    protected $errors = [];

    const defaultgrade = 90;

    public function __construct(array $levels = [],array $sections = [],array $skills = []) {
        $this->levels = $levels;
        $this->sections = $sections;
        $this->skills = $skills;
    }

    public function add_level(level $level){
        $this->levels[] = $level;
    }

    public function add_section(section $section){
        $this->sections[] = $section;
        $section->set_parent($this);
    }

    public function add_userfield(userfield $userfield){
        $this->userfields[] = $userfield;
    }

    public function get_levels(){
        return $this->levels;
    }

    public function add_skill(skill $skills){
        $this->skills[] = $skills;
    }

    public function set_evaluationdata(array $data){
        $this->evaluationdata = $data;
        return $this;
    }

    public function get_evaluationdata(){
        return $this->evaluationdata;
    }

    public function set_errors(array $errors) {
        $this->errors += $errors;
        return $this;
    }

    public function get_errors() {
        return $this->errors;
    }

    public function export_for_template(renderer_base $output) {
        global $CFG,$DB;
        $result = new stdClass();
        $result->username = $this->username;
        $result->userid = $this->userid;
        $result->evaluationid = $this->evaluationid;
        $result->city = $this->city;
        $result->comments = $this->comments;
        $result->levels = [];
        $result->moduleid = $this->moduleid;
        $result->evaluationname = $this->evaluationname;
        $result->evaluationurl = $this->evaluationurl;
        $result->default = self::defaultgrade;
        $result->sections = [];

        foreach ($this->sections as $section){
            $result->sections[] = $section->export_for_template($output);
        }

        $result->userfield = [];

        foreach ($this->userfields as $userfield){
            $result->userfield[] = $userfield->export_for_template($output);
        }

        if(!empty($this->evaluationdata['comments'])){
            $result->postcomments = $this->evaluationdata['comments'];
        }
        $result->attempt = (bool) $this->attempt;
        $result->actionlink = new moodle_url('/mod/evaluation/userview.php',['id'=>$this->id,'moduleid'=>$this->moduleid,'attempt'=>$this->attempt]);
        $result->agree = (bool) $this->agree;
        $result->student = (bool) $this->student;
        $result->result = $this->result;
        $result->grade = $this->grade;
        $result->reson = $this->reson;
        $result->additionaltraining =  (bool) $this->additionaltraining;
        return $result;
    }

    public function skillids(renderer_base $output) {
        $skillids = [];
        foreach ($this->sections as $section) {
            foreach ($section->export_for_template($output)->skills as $skill) {
                $skillids[$skill->id] = $skill->name;
            }
        }
        return $skillids;
    }
}