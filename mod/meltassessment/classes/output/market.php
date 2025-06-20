<?php

namespace mod_meltassessment\output;

use renderer_base;
use stdClass;

class market {
    public $id;

    public $name;

    public $number;

    public function __construct(int $id, string $name) {
        $this->id = $id;
        $this->name = $name;
    }

    public function export_for_template(renderer_base $output) {
        $market = new stdClass();
        $market->id = $this->id;
        $market->name = $this->name;
        $market->number = $this->number;
        return $market;
    }
}