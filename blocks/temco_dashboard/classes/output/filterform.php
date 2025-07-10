<?php

namespace block_temco_dashboard\output;

use block_temco_dashboard\table\temco_user;
use renderable;
use renderer_base;
use templatable;

class filterform implements renderable, templatable {

    private $temcodashboard;

    public function __construct(temco_user $temcodashboard) {
        $this->temcodashboard = $temcodashboard;
    }

    public function export_for_template(renderer_base $output) {
        $filters = $this->temcodashboard->get_filters();
        $context['teams'] = self::format_options($this->temcodashboard->getcohorts(), $filters['cohortid'] ?? null);
        $context['idnumber'] = $filters['idnumber'];
        $context['fullname'] = $filters['fullname'];
        return $context;
    }

    static function format_options(array $options, $_selected = null) : array {
        $formattedoptions = [];
        foreach ($options as $key => $value) {
            $selected = (is_array($_selected) && in_array($key, $_selected)) || $key == $_selected;
            $formattedoptions[] = compact('key', 'value', 'selected');
        }
        return $formattedoptions;
    }
}