<?php

namespace mod_evaluation\output;

use moodle_url;
use stdClass;

class skill {
    use child;
    public $id;

    public $name;

    public $moduleid;

    protected $validation;

    public $evaluationuserid;

    public function __construct(int $id, string $name) {
        $this->id = $id;
        $this->name = $name;
    }

    public function validation($validation){
        $this->validation = $validation;
        return $this;
    }

    public function export_for_template(\renderer_base $output) {
        global $OUTPUT, $DB;
        $skill = new stdClass();
        $skill->name = $this->name;
        $skill->id = $this->id;
        $skill->skillid = $this->id;
        $skill->moduleid = $this->moduleid;
        $editurl = new moodle_url('/mod/evaluation/editskill.php',['id'=>$this->moduleid,'skillid'=>$skill->id]);
        $action = new \confirm_action(get_string('message','mod_evaluation'));
        $deleteurl = new moodle_url('/mod/evaluation/manage.php',['id'=>$this->moduleid,'skillid'=>$skill->id]);
        $actionlink = $OUTPUT->action_link($deleteurl, '', $action, null, new \pix_icon('t/delete',
                get_string('delete', 'mod_evaluation')));
        $skill->skillediturl = $editurl;
        $skill->skilldeleteurl = $actionlink;
        $skill->validation = (bool) $this->validation;
        $comment = $DB->get_field('evaluation_user_skill_level','comment',['evaluationuserid' => $this->evaluationuserid,'skillid' => $this->id]);
        $skill->skillcomment = $comment;
        $evaluationform = $this->get_parent()->get_parent();

        if(method_exists($evaluationform,'get_levels')){
            $levels = $evaluationform->get_levels();
            $errors = $evaluationform->get_errors();
            $skill->haserror = !empty($errors[$skill->id]);
            foreach ($levels as $level) {
                $evaluationdata = $evaluationform->get_evaluationdata();
                $level->checked = isset($evaluationdata[$skill->id]) && $evaluationdata[$skill->id] == $level->id;
                $urg = strtolower($level->name);
                if($urg == 'urg'){
                    $level->urg = 1;
                }else{
                    $level->urg = 0;
                }
                $skill->levels[] = (array) $level;
            }
        }
        return $skill;
    }
}