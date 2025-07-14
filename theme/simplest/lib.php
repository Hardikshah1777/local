<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Theme functions.
 *
 * @package   theme_simplest
 * @copyright 2024, LMSwithAI <contact@lmswithai.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Get config theme quotes items
 *
 * @return array
 */
function theme_simplest_quotes_items() {
    $theme = theme_config::load('simplest');

    $insspquo = $theme->settings->inspirationalquotes;
    $quoterender = [];
    if (empty($insspquo)) {
        return;
    }

    $lines = explode("\n", $insspquo);
    if ( !empty($lines) ) {
        foreach ($lines as $line) {

            if (strlen(trim($line)) > 1) {

                $quote = theme_simplest_quotes_build_line($line);
                if (array_key_exists('lang', $quote)) {

                    $lang = current_language();

                    if ($lang == $quote['lang']) {
                        array_push($quoterender, theme_simplest_quotes_build_line($line));
                    }

                } else {
                    array_push($quoterender, theme_simplest_quotes_build_line($line));
                }

            }

        }
    }

    return  $quoterender;
}


/**
 * Build one quote line.
 *
 * @param  string $line one quote line.
 * @return array $quote object.
 */
function theme_simplest_quotes_build_line($line) {

    $quote = [];
    $parm = explode("|", $line);

    if (!empty($parm[0])) {
        $quote['quote'] = trim($parm[0]);
    }

    if (!empty($parm[1])) {
        $langs = get_string_manager()->get_list_of_translations();

        if (array_key_exists($parm[1], $langs)) {
            $quote['lang'] = trim($parm[1]);
        }
    }

    if (!empty($parm[2])) {
        $quote['author'] = trim($parm[2]);
    }

    return $quote;
}

/**
 * Inject additional SCSS.
 *
 * @return string new scss stylesheet inspirationalquotes.scss.
 */
function theme_simplest_generate_slider_css() {
    global $CFG;

    $theme = theme_config::load('simplest');
    $textduration = $theme->settings->inspirationalquotesduration;

    $insspquo = theme_simplest_quotes_items();

    if (empty($insspquo)) {
        return;
    }

    if (empty($textduration) || $textduration == 0) {
        $textduration = 2;
    }

    $i = 1;
    $scsssclier = file_get_contents($CFG->dirroot . '/theme/simplest/scss/simplest/inspirationalquotes.scss');
    $linescount = count($insspquo);

    $calculation = theme_simplest_animation_calculate($textduration, $linescount);

    $scsssclier = theme_simplest_set_keyframe($scsssclier,
                                            $calculation->visibleprechide,
                                            $calculation->visibleprecend,
                                            $calculation->visibleprecstart);

    $scsssclier = theme_simplest_set_maxduration($scsssclier, $calculation->animtime);

    $timequote = $calculation->blockanimtime;

    foreach ($insspquo as $num => $line) {
            $num++;
            $i++;
            $scsssclier .= "
.insquotes ul li:nth-child(" . $i . ") {
    -webkit-animation-delay: " . $num * $timequote . "s;
    -moz-animation-delay: " . $num * $timequote . "s;
}";

    }

    return $scsssclier;
}

/**
 * Calculate css animation time and keyframe.
 *
 * @param  int $textduration time set in settings.
 * @param  int $linesnum count line quotes.
 * @return object
 */
function theme_simplest_animation_calculate($textduration, $linesnum) {

    // Quote animation time.
    $blockanimtime = $textduration;
    // All animation time.
    $animtime = $blockanimtime * $linesnum;

    // Keyframe percent.
    $visibleprechide = round(($blockanimtime * 100) / $animtime, 2);
    $visibleprecend = $visibleprechide - 1;
    $visibleprecstart = 1;

    $calculation = new stdClass();
    $calculation->blockanimtime = $blockanimtime;
    $calculation->animtime = $animtime;
    $calculation->visibleprechide = $visibleprechide;
    $calculation->visibleprecend = $visibleprecend;
    $calculation->visibleprecstart = $visibleprecstart;

    return $calculation;
}

/**
 * Set keyframe in style scss.
 *
 * @param  string $scss stylesheet inspirationalquotes.scss.
 * @param  int $visibleprechide keyframe precent hide.
 * @param  int $visibleprecend keyframe precent to start hide.
 * @param  int $visibleprecstart keyframe precent to start visible.
 * @return string new scss stylesheet inspirationalquotes.scss.
 */
function theme_simplest_set_keyframe($scss, $visibleprechide, $visibleprecend, $visibleprecstart) {

    $tag = '[[setting:visibleprechide]]';
    $replacement = strval($visibleprechide) . '%';
    $scss = str_replace($tag, $replacement, $scss);

    $tag = '[[setting:visibleprecend]]';
    $replacement = strval($visibleprecend) . '%';
    $scss = str_replace($tag, $replacement, $scss);

    $tag = '[[setting:visibleprecstart]]';
    $replacement = strval($visibleprecstart) . '%';
    $scss = str_replace($tag, $replacement, $scss);

    $scss = str_replace($tag, $replacement, $scss);
    return $scss;
}

/**
 * Animation time set in scss.
 *
 * @param  string $scss stylesheet inspirationalquotes.scss.
 * @param  int $maxduration quotes animation time.
 * @return string new scss stylesheet inspirationalquotes.scss.
 */
function theme_simplest_set_maxduration($scss, $maxduration) {
    $tag = '[[setting:maxduration]]';
    $replacement = strval($maxduration) . '.0s';
    $scss = str_replace($tag, $replacement, $scss);
    return $scss;
}


/**
 * Cron task, change theme style.
 *
 * @return boolean
 */
function theme_simplest_change_style() {

    $theme = theme_config::load('simplest');
    $limitedbydate = !empty($theme->settings->limitedbydate) ? $theme->settings->limitedbydate : null;

    if (!$limitedbydate) {
        return false;
    }

    $startdate = strtotime(!empty($theme->settings->startdate) ? $theme->settings->startdate : null);
    $enddate = strtotime(!empty($theme->settings->enddate) ? $theme->settings->enddate : null);
    $currenttime = time();

    if ($startdate < $currenttime) {

        $presettemporary = !empty($theme->settings->presettemporary) ? $theme->settings->presettemporary : null;
        $preset = !empty($theme->settings->preset) ? $theme->settings->preset : null;

        if ($preset != $presettemporary) {
            set_config('preset', $presettemporary, 'theme_simplest');
            theme_reset_all_caches();
            mtrace('Date temporary style start');
            mtrace('Set style: ' . $presettemporary);
        }
    }

    if ($enddate < $currenttime) {

        set_config('preset', 'default.scss', 'theme_simplest');
        set_config('limitedbydate', false, 'theme_simplest');
        theme_reset_all_caches();
        mtrace('Date temporary style end');
        mtrace('Set style: default.scss');
    }

    return true;
}


/**
 * Returns the main SCSS content.
 *
 * @param theme_config $theme The theme config object.
 * @return string
 */
function theme_simplest_get_main_scss_content($theme) {
    global $CFG;

    $scss = '';
    $filename = !empty($theme->settings->preset) ? $theme->settings->preset : null;

    // Main CSS - Get the CSS from theme Classic.
    $scss .= file_get_contents($CFG->dirroot . '/theme/classic/scss/classic/pre.scss');

    if ($filename == 'default.scss') {
        $scss .= file_get_contents($CFG->dirroot . '/theme/classic/scss/preset/default.scss');
    } else if ($filename == 'easter.scss') {
        $scss .= file_get_contents($CFG->dirroot . '/theme/simplest/scss/preset/easter.scss');
    } else if ($filename == 'mourning.scss') {
        $scss .= file_get_contents($CFG->dirroot . '/theme/simplest/scss/preset/mourning.scss');
    } else if ($filename == 'spring.scss') {
        $scss .= file_get_contents($CFG->dirroot . '/theme/simplest/scss/preset/spring.scss');
    } else if ($filename == 'valentinesday.scss') {
        $scss .= file_get_contents($CFG->dirroot . '/theme/simplest/scss/preset/valentinesday.scss');
    } else if ($filename == 'christmas.scss') {
        $scss .= file_get_contents($CFG->dirroot . '/theme/simplest/scss/preset/christmas.scss');
    }

    $scss .= file_get_contents($CFG->dirroot . '/theme/classic/scss/classic/post.scss');

    // Pre CSS - this is loaded AFTER any prescss from the setting but before the main scss.
    $pre = file_get_contents($CFG->dirroot . '/theme/simplest/scss/pre.scss');

    // Post CSS - this is loaded AFTER the main scss but before the extra scss from the setting.
    $post = file_get_contents($CFG->dirroot . '/theme/simplest/scss/post.scss');

    // Quotes slider.
    $slider = '';

    if ($slider = theme_simplest_generate_slider_css()) {
        // Combine them together.
        return $pre . "\n" . $scss . "\n" . $post . "\n" . $slider;
    } else {
        return $pre . "\n" . $scss . "\n" . $post;
    }

}

/**
 * Get SCSS to prepend.
 *
 * @param theme_config $theme The theme config object.
 * @return array
 */
function theme_simplest_get_pre_scss($theme) {
    $scss = '';
    $configurable = [
        // Config key => [variableName, ...].
        'brandcolor' => ['primary'],
    ];

    $filename = !empty($theme->settings->preset) ? $theme->settings->preset : null;

    // Prepend variables first.
    foreach ($configurable as $configkey => $targets) {
        $value = isset($theme->settings->{$configkey}) ? $theme->settings->{$configkey} : null;

        if ($filename == 'mourning.scss') {
            $value = '#000';
        }

        if ($filename == 'spring.scss') {
            $value = '#8dc63f';
        }

        if ($filename == 'christmas.scss') {
            $value = '#c30010';
        }

        if ($filename == 'valentinesday.scss') {
            $value = '#c30010';
        }

        if (empty($value)) {
            continue;
        }
        array_map(function($target) use (&$scss, $value) {

            $scss .= '$' . $target . ': ' . $value . ";\n";
        }, (array) $targets);
    }

    // Prepend pre-scss.
    if (!empty($theme->settings->scsspre)) {
        $scss .= $theme->settings->scsspre;
    }

    return $scss;
}

/**
 * Inject additional SCSS.
 *
 * @param theme_config $theme The theme config object.
 * @return string
 */
function theme_simplest_get_extra_scss($theme) {
    global $CFG;
    $content = '';

    // Set the page background image.
    $imageurl = $theme->setting_file_url('backgroundimage', 'backgroundimage');
    if (!empty($imageurl)) {
        $content .= '$imageurl: "' . $imageurl . '";';
        $content .= file_get_contents($CFG->dirroot .
            '/theme/simplest/scss/simplest/body-background.scss');
    } else {
        $content .= file_get_contents($CFG->dirroot .
            '/theme/simplest/scss/simplest/default-body-background.scss');
    }

    $filename = !empty($theme->settings->preset) ? $theme->settings->preset : null;

    if ($filename == 'mourning.scss') {
        $content .= file_get_contents($CFG->dirroot .
        '/theme/simplest/scss/simplest/navbar-mourning.scss');
    } else if ($filename == 'spring.scss') {

        $content .= file_get_contents($CFG->dirroot .
        '/theme/simplest/scss/simplest/navbar-spring.scss');
    } else if ($filename == 'valentinesday.scss') {

        $content .= file_get_contents($CFG->dirroot .
        '/theme/simplest/scss/simplest/navbar-valentinesday.scss');
    } else if ($filename == 'christmas.scss') {

        $content .= file_get_contents($CFG->dirroot .
        '/theme/simplest/scss/simplest/navbar-christmas.scss');
    } else {

        if (!empty($theme->settings->navbardark)) {
            $content .= file_get_contents($CFG->dirroot .
                '/theme/simplest/scss/simplest/navbar-dark.scss');
        } else {
            $content .= file_get_contents($CFG->dirroot .
                '/theme/classic/scss/classic/navbar-light.scss');
        }

    }

    if (!empty($theme->settings->scss)) {
        $content .= $theme->settings->scss;
    }
    return $content;
}

/**
 * Get compiled css.
 *
 * @return string compiled css
 */
function theme_simplest_get_precompiled_css() {
    global $CFG;
    return file_get_contents($CFG->dirroot . '/theme/classic/style/moodle.css');
}

/**
 * Serves any files associated with the theme settings.
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options
 * @return bool
 */
function theme_simplest_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    if ($context->contextlevel == CONTEXT_SYSTEM && ($filearea === 'backgroundimage')) {
        $theme = theme_config::load('simplest');
        // By default, theme files must be cache-able by both browsers and proxies.
        if (!array_key_exists('cacheability', $options)) {
            $options['cacheability'] = 'public';
        }
        return $theme->setting_file_serve($filearea, $args, $forcedownload, $options);
    } else {
        send_file_not_found();
    }
}

/**
 * Checks that user should or not be redirected
 *
 * @return bool
 */
function theme_simplest_check_redir() {
    global $PAGE;
    $pagetypes = ['site-index', 'my-index'];
    $systemcontext = context_system::instance();
    if (!has_capability('moodle/site:config', $systemcontext) &&
    isloggedin() && !isguestuser() && in_array($PAGE->pagetype, $pagetypes) && !$PAGE->user_is_editing()) {
        if ($courses = enrol_get_my_courses()) {

            // We redirect only when user is enrolled into one course.
            if (count($courses) == 1) {
                foreach ($courses as $course) {
                    return $course->id;
                }
            }
        }
    }
    return false;
}

