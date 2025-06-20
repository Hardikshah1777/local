<?php

namespace mod_evaluation\output;

use renderable;
use renderer_base;
use stdClass;
use templatable;

class sectionlist implements renderable, templatable {
    /**
     * @var section[]
     */
    protected $sections;

    public function __construct(array $sections = []) {
        $this->sections = $sections;
    }

    public function add_section(section $section){
        $this->sections[] = $section;
        $section->set_parent($this);
    }

    public function export_for_template(renderer_base $output) {
        $export = new stdClass();
        $export->sections = [];
        foreach ($this->sections as $section){
            $export->sections[] = $section->export_for_template($output);
        }

        return $export;
    }
}