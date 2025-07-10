<?php
namespace mod_meltassessment\output;

use confirm_action;
use mod_meltassessment\output\skill;
use moodle_url;
use renderable;
use stdClass;
use templatable;
use renderer_base;

class section implements renderable, templatable{
    use child;
    public $id;

    public $name;

    public $visiblestatus;

    public $moduleid;

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
        $id = $this->moduleid;
        $editsection = new moodle_url('/mod/meltassessment/editsection.php',['id'=>$id,'sectionid'=>$this->id]);
        $addskill = new moodle_url('/mod/meltassessment/skill.php',['id'=>$id,'sectionid'=>$this->id]);
        $section->editurl = $editsection;

        $deletesec = new confirm_action(get_string('sectionmessage','mod_meltassessment'));
        $deletesecurl = new moodle_url('/mod/meltassessment/manage.php',['id'=>$id,'sectionid'=>$section->id]);
        $deleteseclink = $OUTPUT->action_link($deletesecurl, '', $deletesec, null, new \pix_icon('t/delete',
                get_string('delete', 'mod_meltassessment')));

        $section->sectiondeleteurl = $deleteseclink;
        $section->addskill = $addskill;
        $section->name = $this->name;

        $section->skills = [];
        foreach ($this->skills as $skill){
            $section->skills[] = $skill->export_for_template($output);
        }

        return $section;
    }
}