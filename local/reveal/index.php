<?php
require('../../config.php');
require_login();

$PAGE->set_url(new moodle_url('/local/reveal/index.php'));
$PAGE->set_title('test');
$PAGE->set_heading('test');
$PAGE->requires->css('/local/reveal/revealjs/reveal.css');
$PAGE->requires->css('/local/reveal/revealjs/theme/black.css');
$PAGE->requires->js('/local/reveal/revealjs/reveal.js', true);
$PAGE->requires->js_call_amd('local_reveal/init', 'init');

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_reveal/slides', []);
echo $OUTPUT->footer();