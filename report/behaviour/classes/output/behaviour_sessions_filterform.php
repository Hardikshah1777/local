<?php

namespace report_behaviour\output;

use report_behaviour\table\behaviour_sessions;
use renderable;
use renderer_base;
use templatable;

class behaviour_sessions_filterform implements renderable, templatable {

    /**
     * @var behaviour_sessions
     */
    private $sesstable;

    public function __construct(behaviour_sessions $table) {
        $this->sesstable = $table;
    }

    public function export_for_template(renderer_base $output) {
        $filters = $this->sesstable->get_filters();

        $context = [];
        $context['cmid'] = !empty($filters['cmid']) ? $filters['cmid'] : '';

        $modules = $this->sesstable->get_coursemodules();
        $cmoptions = [];
        if (!empty($modules)) {
            foreach ($modules as $module) {
                if ($context['cmid'] == $module->id) {
                    $selected = ['selected' => true];
                }
                $cmoptions[] = array_merge(['value' => $module->id, 'key' => $module->name], $selected ?? []);
            }
        }

        $context['cmoption'] = $cmoptions;
        $context['isweekend'] = !empty($this->sesstable->isweekend);
        $context['step'] = ($context['isweekend'] ? 7 : 1);
        $context['sundaydate'] = userdate(strtotime('next sunday'), '%Y-%m-%d');

        return $context;
    }
}