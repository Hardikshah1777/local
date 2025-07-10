<?php

namespace mod_meltassessment\output;

use confirm_action;
use moodle_url;
use renderer_base;
use stdClass;

class skill {
    use child;
    public $id;

    public $name;

    protected $validation;

    public $moduleid;

    public $nolesson;

    public $meltassessmentuserid;

    public function __construct(int $id, string $name) {
        $this->id = $id;
        $this->name = $name;
    }

    public function validation($validation){
        $this->validation = $validation;
        return $this;
    }

    public function export_for_template(renderer_base $output) {
        global $OUTPUT, $DB;
        $skill = new stdClass();
        $skill->id = $this->id;
        $skill->skillid = $this->id;
        $skill->name = $this->name;

        $editurl = new moodle_url('/mod/meltassessment/editskill.php', ['id' => $this->moduleid, 'skillid' => $this->id]);

        $action = new confirm_action(get_string('skillmessage', 'mod_meltassessment'));
        $deleteurl = new moodle_url('/mod/meltassessment/manage.php', ['id' => $this->moduleid, 'skillid' => $skill->id]);
        $actionlink = $OUTPUT->action_link($deleteurl, '', $action, null, new \pix_icon('t/delete',
                get_string('delete', 'mod_meltassessment')));
        $skill->editurl = $editurl;
        $skill->deleteurl = $actionlink;
        $skill->validation = (bool) $this->validation;
        $meltassessmentform = $this->get_parent()->get_parent();
        if(method_exists($meltassessmentform,'get_errors')){
            $errors = $meltassessmentform->get_errors();
            $markings = $meltassessmentform->get_markets();
            $skill->haserror = !empty($errors[$skill->id]);
            $meltassessmentdata = $meltassessmentform->get_meltassessmentdata();
            //print_object($meltassessmentdata);
            //for ($i = 1; $i <= $this->nolesson; $i++){
            //    foreach ($markings as $key => $marking){
            //        $marking->selected = isset($meltassessmentdata[$skill->id]) && $meltassessmentdata[$skill->id][$i] == $marking->id;
            //        $skill->market[$i][] = (array) $marking;
            //    }
            //}

        }
        return $skill;
    }

}