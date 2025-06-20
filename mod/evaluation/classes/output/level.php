<?php

namespace mod_evaluation\output;

use renderer_base;
use stdClass;

class level {

    public $id;

    public $name;

    public $visiblestatus;

    public $grade;

    public function __construct(int $id, string $name,int $visiblestatus,int $grade) {
        $this->id = $id;
        $this->name = $name;
        $this->visiblestatus = $visiblestatus;
        $this->grade = $grade;
    }

    public function export_for_template(renderer_base $output) {
        $level = new stdClass();
        $level->id = $this->id;
        $level->name = $this->name;
        $level->visiblestatus = $this->visiblestatus;
        $level->grade = $this->grade;
        return $level;
    }
}