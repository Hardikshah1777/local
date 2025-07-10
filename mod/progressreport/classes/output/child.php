<?php

namespace mod_progressreport\output;

trait child {
    protected $parent;
    public function set_parent($parent) {
        $this->parent = $parent;
        return $this;
    }
    public function get_parent() {
        return $this->parent;
    }
}