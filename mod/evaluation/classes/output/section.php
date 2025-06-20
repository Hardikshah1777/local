<?php

namespace mod_evaluation\output;
use moodle_url;
use renderable;
use renderer_base;
use stdClass;
use templatable;

class section implements renderable, templatable {
    use child;
    public $id;

    public $name;

    public $moduleid;

    public $visiblestatus;

    public $saferskill;

    /**
    * @var skill[]
     */
    protected $skills = [];

    public function __construct(int $id, string $name) {
        $this->id = $id;
        $this->name = $name;
    }

    public function add_skill(skill $skill){
        $this->skills[] = $skill;
        $skill->set_parent($this);
    }

    public function export_for_template(renderer_base $output) {
        global $OUTPUT;

        $section = new stdClass();
        $section->id = $this->id;
        $editors = new moodle_url('/mod/evaluation/editsection.php',['sectionid'=>$this->id,'id'=>$this->moduleid]);
        $sectional = new moodle_url('/mod/evaluation/skill.php',['id'=>$this->moduleid,'sectionid'=>$this->id]);

        $deletesec = new \confirm_action(get_string('sectionmessage','mod_evaluation'));
        $deletesecurl = new moodle_url('/mod/evaluation/manage.php',['id'=>$this->moduleid,'sectionid'=>$section->id]);
        $deleteseclink = $OUTPUT->action_link($deletesecurl, '', $deletesec, null, new \pix_icon('t/delete',
                get_string('delete', 'mod_evaluation')));

        $section->editurl = $editors;
        $section->sectiondeleteurl = $deleteseclink;
        $section->addskill = $sectional;
        $section->hide = $this->visiblestatus === 1;
        $section->name = $this->name;
        $section->skills = [];
        $section->saferskill = (bool) $this->saferskill;
        foreach ($this->skills as $skill){
            $section->skills[] = $skill->export_for_template($output);
        }
        return $section;
    }
}