<?php

namespace theme_boostchild\output\core;

global $CFG;

use cm_info;
use completion_info;
use core_availability\info;
use html_writer;
use moodle_url;
use pix_icon;
use stdClass;

require_once $CFG->dirroot . '/course/renderer.php';

class course_renderer extends \core_course_renderer {

    /**
     * Renders html for completion box on course page
     *
     * If completion is disabled, returns empty string
     * If completion is automatic, returns an icon of the current completion state
     * If completion is manual, returns a form (with an icon inside) that allows user to
     * toggle completion
     *
     * @param stdClass $course course object
     * @param completion_info $completioninfo completion info for the course, it is recommended
     *     to fetch once for all modules in course/section for performance
     * @param cm_info $mod module to show completion for
     * @param array $displayoptions display options, not used in core
     * @return string
     */
    public function course_section_cm_completion($course, &$completioninfo, cm_info $mod, $displayoptions = array()) {
        global $CFG, $DB, $USER;
        $output = '';

        $istrackeduser = $completioninfo->is_tracked_user($USER->id);
        $isediting = $this->page->user_is_editing();

        if (!empty($displayoptions['hidecompletion']) || !isloggedin() || isguestuser() || !$mod->uservisible) {
            return $output;
        }
        if ($completioninfo === null) {
            $completioninfo = new completion_info($course);
        }
        $completion = $completioninfo->is_enabled($mod);

        if ($completion == COMPLETION_TRACKING_NONE) {
            if ($isediting) {
                $output .= html_writer::span('&nbsp;', 'filler');
            }
            return $output;
        }

        $completionicon = '';

        if ($isediting || !$istrackeduser) {
            switch ($completion) {
                case COMPLETION_TRACKING_MANUAL :
                    $completionicon = 'manual-enabled'; break;
                case COMPLETION_TRACKING_AUTOMATIC :
                    $completionicon = 'auto-enabled'; break;
            }
        } else {
            $completiondata = $completioninfo->get_data($mod, true);
            if ($completion == COMPLETION_TRACKING_MANUAL) {
                switch($completiondata->completionstate) {
                    case COMPLETION_INCOMPLETE:
                        $completionicon = 'manual-n' . ($completiondata->overrideby ? '-override' : '');
                        break;
                    case COMPLETION_COMPLETE:
                        $completionicon = 'manual-y' . ($completiondata->overrideby ? '-override' : '');
                        break;
                }
            } else { // Automatic
                switch($completiondata->completionstate) {
                    case COMPLETION_INCOMPLETE:
                        $completionicon = 'auto-n' . ($completiondata->overrideby ? '-override' : '');
                        break;
                    case COMPLETION_COMPLETE:
                        $completionicon = 'auto-y' . ($completiondata->overrideby ? '-override' : '');
                        break;
                    case COMPLETION_COMPLETE_PASS:
                        $completionicon = 'auto-pass'; break;
                    case COMPLETION_COMPLETE_FAIL:
                        $completionicon = 'auto-fail'; break;
                }
            }
        }
        if ($completionicon) {
            $formattedname = html_entity_decode($mod->get_formatted_name(), ENT_QUOTES, 'UTF-8');
            if (!$isediting && $istrackeduser && $completiondata->overrideby) {
                $args = new stdClass();
                $args->modname = $formattedname;
                $overridebyuser = \core_user::get_user($completiondata->overrideby, '*', MUST_EXIST);
                $args->overrideuser = fullname($overridebyuser);
                $imgalt = get_string('completion-alt-' . $completionicon, 'completion', $args);
            } else {
                $imgalt = get_string('completion-alt-' . $completionicon, 'completion', $formattedname);
            }

            if ($isediting || !$istrackeduser || !has_capability('moodle/course:togglecompletion', $mod->context)) {
                // When editing, the icon is just an image.
                $completionpixicon = new pix_icon('i/completion-'.$completionicon, $imgalt, '',
                        array('title' => $imgalt, 'class' => 'iconsmall'));
                $output .= html_writer::tag('span', $this->output->render($completionpixicon),
                        array('class' => 'autocompletion'));
            } else if ($completion == COMPLETION_TRACKING_MANUAL) {
                $newstate =
                        $completiondata->completionstate == COMPLETION_COMPLETE
                                ? COMPLETION_INCOMPLETE
                                : COMPLETION_COMPLETE;
                // In manual mode the icon is a toggle form...

                // If this completion state is used by the
                // conditional activities system, we need to turn
                // off the JS.
                $extraclass = '';
                if (!empty($CFG->enableavailability) &&
                        info::completion_value_used($course, $mod->id)) {
                    $extraclass = ' preventjs';
                }
                $output .= html_writer::start_tag('form', array('method' => 'post',
                        'action' => new moodle_url('/course/togglecompletion.php'),
                        'class' => 'togglecompletion'. $extraclass));
                $output .= html_writer::start_tag('div');
                $output .= html_writer::empty_tag('input', array(
                        'type' => 'hidden', 'name' => 'id', 'value' => $mod->id));
                $output .= html_writer::empty_tag('input', array(
                        'type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()));
                $output .= html_writer::empty_tag('input', array(
                        'type' => 'hidden', 'name' => 'modulename', 'value' => $formattedname));
                $output .= html_writer::empty_tag('input', array(
                        'type' => 'hidden', 'name' => 'completionstate', 'value' => $newstate));
                $output .= html_writer::tag('button',
                        $this->output->pix_icon('i/completion-' . $completionicon, $imgalt),
                        array('class' => 'btn btn-link', 'aria-live' => 'assertive'));
                $output .= html_writer::end_tag('div');
                $output .= html_writer::end_tag('form');
            } else {
                // In auto mode, the icon is just an image.
                $completionpixicon = new pix_icon('i/completion-'.$completionicon, $imgalt, '',
                        array('title' => $imgalt));
                $output .= html_writer::tag('span', $this->output->render($completionpixicon),
                        array('class' => 'autocompletion'));
            }
        }
        return $output;
    }

    /**
     * Renders HTML to display one course module in a course section
     *
     * This includes link, content, availability, completion info and additional information
     * that module type wants to display (i.e. number of unread forum posts)
     *
     * This function calls:
     * {@link core_course_renderer::course_section_cm_name()}
     * {@link core_course_renderer::course_section_cm_text()}
     * {@link core_course_renderer::course_section_cm_availability()}
     * {@link core_course_renderer::course_section_cm_completion()}
     * {@link course_get_cm_edit_actions()}
     * {@link core_course_renderer::course_section_cm_edit_actions()}
     *
     * @param stdClass $course
     * @param completion_info $completioninfo
     * @param cm_info $mod
     * @param int|null $sectionreturn
     * @param array $displayoptions
     * @return string
     */
    public function course_section_cm($course, &$completioninfo, cm_info $mod, $sectionreturn, $displayoptions = array()) {
        $output = '';
        // We return empty string (because course module will not be displayed at all)
        // if:
        // 1) The activity is not visible to users
        // and
        // 2) The 'availableinfo' is empty, i.e. the activity was
        //     hidden in a way that leaves no info, such as using the
        //     eye icon.
        if (!$mod->is_visible_on_course_page()) {
            return $output;
        }

        $indentclasses = 'mod-indent';
        if (!empty($mod->indent)) {
            $indentclasses .= ' mod-indent-'.$mod->indent;
            if ($mod->indent > 15) {
                $indentclasses .= ' mod-indent-huge';
            }
        }

        $output .= html_writer::start_tag('div');

        if ($this->page->user_is_editing()) {
            $output .= course_get_cm_move($mod, $sectionreturn);
        }

        $output .= html_writer::start_tag('div', array('class' => 'mod-indent-outer w-100'));

        // This div is used to indent the content.
        $output .= html_writer::div('', $indentclasses);

        // Start a wrapper for the actual content to keep the indentation consistent
        $output .= html_writer::start_tag('div');

        // Display the link to the module (or do nothing if module has no url)
        $cmname = $this->course_section_cm_name($mod, $displayoptions);

        if (!empty($cmname)) {
            // Start the div for the activity title, excluding the edit icons.
            $output .= html_writer::start_tag('div', array('class' => 'activityinstance'));
            $output .= $cmname;


            // Module can put text after the link (e.g. forum unread)
            $output .= $mod->afterlink;

            // Closing the tag which contains everything but edit icons. Content part of the module should not be part of this.
            $output .= html_writer::end_tag('div'); // .activityinstance
        }

        // If there is content but NO link (eg label), then display the
        // content here (BEFORE any icons). In this case cons must be
        // displayed after the content so that it makes more sense visually
        // and for accessibility reasons, e.g. if you have a one-line label
        // it should work similarly (at least in terms of ordering) to an
        // activity.
        $contentpart = $this->course_section_cm_text($mod, $displayoptions);
        $url = $mod->url;
        if (empty($url)) {
            $output .= $contentpart;
        }

        $modicons = '';
        if ($this->page->user_is_editing()) {
            $editactions = course_get_cm_edit_actions($mod, $mod->indent, $sectionreturn);
            $modicons .= ' '. $this->course_section_cm_edit_actions($editactions, $mod, $displayoptions);
            $modicons .= $mod->afterediticons;
        }

        $modicons .= $this->course_section_cm_completion($course, $completioninfo, $mod, $displayoptions);

        if (!empty($modicons)) {
            $output .= html_writer::div($modicons, 'actions');
        }

        // Show availability info (if module is not available).
        $output .= $this->course_section_cm_availability($mod, $displayoptions);

        // If there is content AND a link, then display the content here
        // (AFTER any icons). Otherwise it was displayed before
        if (!empty($url)) {
            $output .= $contentpart;
        }

        $output .= html_writer::end_tag('div'); // $indentclasses

        // End of indentation div.
        $output .= html_writer::end_tag('div');

        $output .= html_writer::end_tag('div');
        return $output;
    }
}