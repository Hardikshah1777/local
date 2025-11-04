<?php
require('../../config.php');
require_login();

$PAGE->set_url(new moodle_url('/local/reveal/index.php'));
$PAGE->set_title('test');
$PAGE->set_heading('test');
$PAGE->requires->css('/local/reveal/revealjs/reveal.css');
$PAGE->requires->css('/local/reveal/revealjs/theme/black.css');
$PAGE->requires->js_call_amd('/local/reveal/revealjs/reveal', 'init');

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_reveal/slides', []);
?>

<link rel="stylesheet" href="/moodle4/local/reveal/revealjs/reveal.css">
<link rel="stylesheet" href="/moodle4/local/reveal/revealjs/theme/black.css">
<script src="/moodle4/local/reveal/revealjs/reveal.js"></script>
<div class="reveal">
    <div class="slides">
        <section>Slide 1</section>
        <section>Slide 2</section>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof Reveal !== 'undefined') {
            Reveal.initialize({
                hash: true,
                slideNumber: true,
                transition: 'fade'
            });
        } else {
            console.error("Reveal.js still not loaded!");
        }
    });
</script>

<?php
echo $OUTPUT->footer();