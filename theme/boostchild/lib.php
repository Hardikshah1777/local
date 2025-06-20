<?php

function theme_boostchild_before_footer() {
    global $PAGE;
    if ($PAGE->pagelayout != 'course') {
        return;
    }
    $completion = new completion_info($PAGE->course);
    if (!$completion->is_enabled()) {
        return;
    }
    echo html_writer::start_tag('form', array('action'=>'.', 'method'=>'get'));
    echo html_writer::start_tag('div');
    echo html_writer::empty_tag('input', array('type'=>'hidden', 'id'=>'completion_dynamic_change', 'name'=>'completion_dynamic_change', 'value'=>'0'));
    echo html_writer::end_tag('div');
    echo html_writer::end_tag('form');
    $PAGE->requires->string_for_js('completion-alt-manual-y', 'completion');
    $PAGE->requires->string_for_js('completion-alt-manual-n', 'completion');
    $PAGE->requires->yui_module(['moodle-theme_boostchild-completion'], 'M.theme_boostchild.completion.init');

}