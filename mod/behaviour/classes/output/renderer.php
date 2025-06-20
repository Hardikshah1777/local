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
 * Behaviour module renderering methods
 *
 * @package    mod_behaviour
 * @copyright  2011 Artem Andreev <andreev.artem@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_behaviour\output;

use plugin_renderer_base;
use mod_behaviour_view_page_params;
use mod_behaviour_take_page_params;
use mod_behaviour_page_with_filter_controls;
use mod_behaviour_preferences_page_params;
use mod_behaviour_structure;
use mod_behaviour_sessions_page_params;
use behaviour_user_sessions_cells_html_generator;
use html_table;
use html_table_row;
use html_table_cell;
use html_writer;
use single_select;
use stdClass;
use pix_icon;
use moodle_url;
use context_module;
use tabobject;
use js_writer;

/**
 * Behaviour module renderer class
 *
 * @copyright  2011 Artem Andreev <andreev.artem@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends plugin_renderer_base {
    // External API - methods to render behaviour renderable components.

    /**
     * Renders filter controls for behaviour
     *
     * @param mod_behaviour\output\filter_controls $fcontrols - filter controls data to display
     * @return string html code
     */
    protected function render_filter_controls(filter_controls $fcontrols) {

        $context = new stdClass();

        if (property_exists($fcontrols->pageparams, 'mode') &&
            $fcontrols->pageparams->mode === mod_behaviour_view_page_params::MODE_ALL_SESSIONS) {
            $context->modeallsessions = true;
            $context->groupingcontrols = $this->render_grouping_controls($fcontrols);
            $context->coursecontrols = $this->render_course_controls($fcontrols);
        }

        $context->sessgroupselector = $this->render_sess_group_selector($fcontrols);

        if (empty($fcontrols->pageparams->studentid) && // Don't show add session button on user specific reports.
            has_capability('mod/behaviour:managebehaviours', $fcontrols->att->context) && !$fcontrols->reportcontrol) {
            $url = $fcontrols->att->url_sessions()->out(true, ['action' => mod_behaviour_sessions_page_params::ACTION_ADD]);
            $context->addsession = $this->output->single_button($url, get_string('addsession', 'behaviour'), 'post',
             ['class' => 'addsession', 'primary' => true]);
        }

        $context->curdatecontrols = $this->render_curdate_controls($fcontrols);
        $context->pagingcontrols = $this->render_paging_controls($fcontrols);
        $context->viewcontrols = $this->render_view_controls($fcontrols);

        return $this->render_from_template('behaviour/filter_controls', $context);
    }

    /**
     * Render group selector
     *
     * @param filter_controls $fcontrols
     * @return mixed|string
     */
    protected function render_sess_group_selector(filter_controls $fcontrols) {
        switch ($fcontrols->pageparams->selectortype) {
            case mod_behaviour_page_with_filter_controls::SELECTOR_SESS_TYPE:
                $sessgroups = $fcontrols->get_sess_groups_list();
                if ($sessgroups) {
                    $select = new single_select($fcontrols->url(), 'group', $sessgroups,
                                                $fcontrols->get_current_sesstype(), null, 'selectgroup');
                    $select->label = get_string('sessions', 'behaviour');
                    $output = $this->output->render($select);

                    return html_writer::tag('div', $output, array('class' => 'groupselector m-0'));
                }
                break;
            case mod_behaviour_page_with_filter_controls::SELECTOR_GROUP:
                return groups_print_activity_menu($fcontrols->cm, $fcontrols->url(), true);
        }

        return '';
    }

    /**
     * Render paging controls.
     *
     * @param filter_controls $fcontrols
     * @return string
     */
    protected function render_paging_controls(filter_controls $fcontrols) {
        $pagingcontrols = '';

        $group = 0;
        if (!empty($fcontrols->pageparams->group)) {
            $group = $fcontrols->pageparams->group;
        }

        $totalusers = count_enrolled_users(context_module::instance($fcontrols->cm->id), 'mod/behaviour:canbelisted', $group);

        if (empty($fcontrols->pageparams->page) || !$fcontrols->pageparams->page || !$totalusers ||
            empty($fcontrols->pageparams->perpage)) {

            return $pagingcontrols;
        }

        $numberofpages = ceil($totalusers / $fcontrols->pageparams->perpage);

        if ($fcontrols->pageparams->page > 1) {
            $pagingcontrols .= html_writer::link($fcontrols->url(array('curdate' => $fcontrols->curdate,
                                                                       'page' => $fcontrols->pageparams->page - 1)),
                                                                 $this->output->larrow());
        }
        $a = new stdClass();
        $a->page = $fcontrols->pageparams->page;
        $a->numpages = $numberofpages;
        $text = get_string('pageof', 'behaviour', $a);
        $pagingcontrols .= html_writer::tag('span', $text,
                                            array('class' => 'attbtn'));
        if ($fcontrols->pageparams->page < $numberofpages) {
            $pagingcontrols .= html_writer::link($fcontrols->url(array('curdate' => $fcontrols->curdate,
                                                                       'page' => $fcontrols->pageparams->page + 1)),
                                                                 $this->output->rarrow());
        }

        return $pagingcontrols;
    }

    /**
     * Render date controls.
     *
     * @param filter_controls $fcontrols
     * @return string
     */
    protected function render_curdate_controls(filter_controls $fcontrols) {
        global $CFG;

        $curdatecontrols = '';
        if ($fcontrols->curdatetxt) {
            $this->page->requires->strings_for_js(array('calclose'), 'behaviour');
            $jsvals = array(
                    'cal_months'    => explode(',', get_string('calmonths', 'behaviour')),
                    'cal_week_days' => explode(',', get_string('calweekdays', 'behaviour')),
                    'cal_start_weekday' => $CFG->calendar_startwday,
                    'cal_cur_date'  => $fcontrols->curdate);
            $curdatecontrols = html_writer::script(js_writer::set_variable('M.behaviour', $jsvals));

            $this->page->requires->js('/mod/behaviour/calendar.js');

            $curdatecontrols .= html_writer::link($fcontrols->url(array('curdate' => $fcontrols->prevcur)),
                                                                         $this->output->larrow());
            $params = array(
                    'title' => get_string('calshow', 'behaviour'),
                    'id'    => 'show',
                    'class' => 'btn btn-secondary',
                    'type'  => 'button');
            $buttonform = html_writer::tag('button', $fcontrols->curdatetxt, $params);
            foreach ($fcontrols->url_params(array('curdate' => '')) as $name => $value) {
                $params = array(
                        'type'  => 'hidden',
                        'id'    => $name,
                        'name'  => $name,
                        'value' => $value);
                $buttonform .= html_writer::empty_tag('input', $params);
            }
            $params = array(
                    'id'        => 'currentdate',
                    'action'    => $fcontrols->url_path(),
                    'method'    => 'post'
            );

            $buttonform = html_writer::tag('form', $buttonform, $params);
            $curdatecontrols .= $buttonform;

            $curdatecontrols .= html_writer::link($fcontrols->url(array('curdate' => $fcontrols->nextcur)),
                                                                         $this->output->rarrow());
        }

        return $curdatecontrols;
    }

    /**
     * Render grouping controls (for all sessions report).
     *
     * @param filter_controls $fcontrols
     * @return string
     */
    protected function render_grouping_controls(filter_controls $fcontrols) {
        if ($fcontrols->pageparams->mode === mod_behaviour_view_page_params::MODE_ALL_SESSIONS) {
            $groupoptions = array(
                'date' => get_string('sessionsbydate', 'behaviour'),
                'activity' => get_string('sessionsbyactivity', 'behaviour'),
                'course' => get_string('sessionsbycourse', 'behaviour')
            );
            $groupcontrols = get_string('groupsessionsby', 'behaviour') . ":";
            foreach ($groupoptions as $key => $opttext) {
                if ($key != $fcontrols->pageparams->groupby) {
                    $link = html_writer::link($fcontrols->url(array('groupby' => $key)), $opttext);
                    $groupcontrols .= html_writer::tag('span', $link, array('class' => 'attbtn'));
                } else {
                    $groupcontrols .= html_writer::tag('span', $opttext, array('class' => 'attcurbtn'));
                }
            }
            return html_writer::tag('div', $groupcontrols);
        }
        return "";
    }

    /**
     * Render course controls (for all sessions report).
     *
     * @param filter_controls $fcontrols
     * @return string
     */
    protected function render_course_controls(filter_controls $fcontrols) {
        if ($fcontrols->pageparams->mode === mod_behaviour_view_page_params::MODE_ALL_SESSIONS) {
            $courseoptions = array(
                'all' => get_string('sessionsallcourses', 'behaviour'),
                'current' => get_string('sessionscurrentcourses', 'behaviour')
            );
            $coursecontrols = "";
            foreach ($courseoptions as $key => $opttext) {
                if ($key != $fcontrols->pageparams->sesscourses) {
                    $link = html_writer::link($fcontrols->url(array('sesscourses' => $key)), $opttext);
                    $coursecontrols .= html_writer::tag('span', $link, array('class' => 'attbtn'));
                } else {
                    $coursecontrols .= html_writer::tag('span', $opttext, array('class' => 'attcurbtn'));
                }
            }
            return html_writer::tag('div', $coursecontrols);
        }
        return "";
    }

    /**
     * Render view controls.
     *
     * @param filter_controls $fcontrols
     * @return string
     */
    protected function render_view_controls(filter_controls $fcontrols) {
        $views[ATT_VIEW_ALL] = get_string('all', 'behaviour');
        $views[ATT_VIEW_ALLPAST] = get_string('allpast', 'behaviour');
        $views[ATT_VIEW_MONTHS] = get_string('months', 'behaviour');
        $views[ATT_VIEW_WEEKS] = get_string('weeks', 'behaviour');
        $views[ATT_VIEW_DAYS] = get_string('days', 'behaviour');
        if ($fcontrols->reportcontrol  && $fcontrols->att->grade > 0) {
            $a = $fcontrols->att->get_lowgrade_threshold() * 100;
            $views[ATT_VIEW_NOTPRESENT] = get_string('below', 'behaviour', $a);
        }
        if ($fcontrols->reportcontrol) {
            $views[ATT_VIEW_SUMMARY] = get_string('summary', 'behaviour');
        }
        $viewcontrols = '';
        foreach ($views as $key => $sview) {
            if ($key != $fcontrols->pageparams->view) {
                $link = html_writer::link($fcontrols->url(array('view' => $key)), $sview);
                $viewcontrols .= html_writer::tag('span', $link, array('class' => 'attbtn'));
            } else {
                $viewcontrols .= html_writer::tag('span', $sview, array('class' => 'attcurbtn'));
            }
        }

        return html_writer::tag('div', $viewcontrols);
    }

    /**
     * Renders behaviour sessions managing table
     *
     * @param manage_data $sessdata to display
     * @return string html code
     */
    protected function render_manage_data(manage_data $sessdata) {
        $o = $this->render_sess_manage_table($sessdata) . $this->render_sess_manage_control($sessdata);
        $o = html_writer::tag('form', $o, array('method' => 'post', 'action' => $sessdata->url_sessions()->out()));
        $o = $this->output->container($o, 'generalbox attwidth');
        $o = $this->output->container($o, 'attsessions_manage_table');

        return $o;
    }

    /**
     * Render session manage table.
     *
     * @param manage_data $sessdata
     * @return string
     */
    protected function render_sess_manage_table(manage_data $sessdata) {
        $this->page->requires->js_init_call('M.mod_behaviour.init_manage');

        $table = new html_table();
        $table->head = [
                html_writer::checkbox('cb_selector', 0, false, '', array('id' => 'cb_selector')),
                get_string('date', 'behaviour'),
                get_string('time', 'behaviour'),
                get_string('sessiontypeshort', 'behaviour'),
                get_string('description', 'behaviour')
        ];
        $table->align = ['center', 'right', '', '', 'left'];
        $table->size = ['1px', '1px', '1px', '', '*'];

        // Add custom fields.
        $customfields = [];
        if (!empty($sessdata->sessions)) {
            $handler = \mod_behaviour\customfield\session_handler::create();
            $customfields = $handler->get_fields_for_display(reset($sessdata->sessions)->id); // Pass first sessionid.
            $customfieldsdata = $handler->get_instances_data(array_keys($sessdata->sessions));
        }
        foreach ($customfields as $field) {
            $table->head[] = $field->get_formatted_name();
            $table->align[] = '';
            $table->size[] = '';
        }
        // Add final fields.
        $table->head[] = get_string('actions');
        $table->align[] = 'right';
        $table->size[] = '120px';

        $i = 0;
        foreach ($sessdata->sessions as $key => $sess) {
            $i++;

            $dta = $this->construct_date_time_actions($sessdata, $sess);
            $table->data[$sess->id][] = html_writer::checkbox('sessid[]', $sess->id, false, '',
                                                              array('class' => 'behavioursesscheckbox'));
            $table->data[$sess->id][] = $dta['date'];
            $table->data[$sess->id][] = $dta['time'];
            if ($sess->groupid) {
                if (empty($sessdata->groups[$sess->groupid])) {
                    $table->data[$sess->id][] = get_string('deletedgroup', 'behaviour');
                    // Remove actions and links on date/time.
                    $dta['actions'] = '';
                    $dta['date'] = userdate($sess->sessdate, get_string('strftimedmyw', 'behaviour'));
                    $dta['time'] = $this->construct_time($sess->sessdate, $sess->duration);
                } else {
                    $table->data[$sess->id][] = get_string('group') . ': ' . $sessdata->groups[$sess->groupid]->name;
                }
            } else {
                $table->data[$sess->id][] = get_string('commonsession', 'behaviour');
            }
            $table->data[$sess->id][] = format_text($sess->description);
            foreach ($customfields as $field) {
                if (isset($customfieldsdata[$sess->id][$field->get('id')])) {
                    $table->data[$sess->id][] = $customfieldsdata[$sess->id][$field->get('id')]->get('value');
                } else {
                    $table->data[$sess->id][] = '';
                }
            }
            $table->data[$sess->id][] = $dta['actions'];

        }

        return html_writer::table($table);
    }

    /**
     * Implementation of user image rendering.
     *
     * @param password_icon $helpicon A help icon instance
     * @return string HTML fragment
     */
    protected function render_password_icon(password_icon $helpicon) {
        return $this->render_from_template('behaviour/behaviour_password_icon', $helpicon->export_for_template($this));
    }
    /**
     * Construct date time actions.
     *
     * @param manage_data $sessdata
     * @param stdClass $sess
     * @return array
     */
    private function construct_date_time_actions(manage_data $sessdata, $sess) {
        $actions = '';
        if ((!empty($sess->studentpassword) || ($sess->includeqrcode == 1)) &&
            (has_capability('mod/behaviour:managebehaviours', $sessdata->att->context) ||
            has_capability('mod/behaviour:takebehaviours', $sessdata->att->context) ||
            has_capability('mod/behaviour:changebehaviours', $sessdata->att->context))) {

            $icon = new password_icon($sess->studentpassword, $sess->id);

            if ($sess->includeqrcode == 1||$sess->rotateqrcode == 1) {
                $icon->includeqrcode = 1;
            } else {
                $icon->includeqrcode = 0;
            }

            $actions .= $this->render($icon);
        }

        $date = userdate($sess->sessdate, get_string('strftimedmyw', 'behaviour'));
        $time = $this->construct_time($sess->sessdate, $sess->duration);
        if ($sess->lasttaken > 0) {
            if (has_capability('mod/behaviour:changebehaviours', $sessdata->att->context)) {
                $url = $sessdata->url_take($sess->id, $sess->groupid);
                $title = get_string('changebehaviour', 'behaviour');

                $date = html_writer::link($url, $date, array('title' => $title));
                $time = html_writer::link($url, $time, array('title' => $title));

                $actions .= $this->output->action_icon($url, new pix_icon('redo', $title, 'behaviour'));
            } else {
                $date = '<i>' . $date . '</i>';
                $time = '<i>' . $time . '</i>';
            }
        } else {
            if (has_capability('mod/behaviour:takebehaviours', $sessdata->att->context)) {
                $url = $sessdata->url_take($sess->id, $sess->groupid);
                $title = get_string('takebehaviour', 'behaviour');
                $actions .= $this->output->action_icon($url, new pix_icon('t/go', $title));
            }
        }

        if (has_capability('mod/behaviour:managebehaviours', $sessdata->att->context)) {
            $url = $sessdata->url_sessions($sess->id, mod_behaviour_sessions_page_params::ACTION_UPDATE);
            $title = get_string('editsession', 'behaviour');
            $actions .= $this->output->action_icon($url, new pix_icon('t/edit', $title));

            $url = $sessdata->url_sessions($sess->id, mod_behaviour_sessions_page_params::ACTION_DELETE);
            $title = get_string('deletesession', 'behaviour');
            $actions .= $this->output->action_icon($url, new pix_icon('t/delete', $title));
        }

        return array('date' => $date, 'time' => $time, 'actions' => $actions);
    }

    /**
     * Render session manage control.
     *
     * @param manage_data $sessdata
     * @return string
     */
    protected function render_sess_manage_control(manage_data $sessdata) {
        $table = new html_table();
        $table->attributes['class'] = ' ';
        $table->width = '100%';
        $table->align = array('left', 'right');

        $table->data[0][] = $this->output->help_icon('hiddensessions', 'behaviour',
                get_string('hiddensessions', 'behaviour').': '.$sessdata->hiddensessionscount);

        if (has_capability('mod/behaviour:managebehaviours', $sessdata->att->context)) {
            if ($sessdata->hiddensessionscount > 0) {
                $attributes = array(
                        'type'  => 'submit',
                        'name'  => 'deletehiddensessions',
                        'class' => 'btn btn-secondary',
                        'value' => get_string('deletehiddensessions', 'behaviour'));
                $table->data[1][] = html_writer::empty_tag('input', $attributes);
            }

            $options = array(mod_behaviour_sessions_page_params::ACTION_DELETE_SELECTED => get_string('delete'),
                mod_behaviour_sessions_page_params::ACTION_CHANGE_DURATION => get_string('changeduration', 'behaviour'));

            $controls = html_writer::select($options, 'action');
            $attributes = array(
                    'type'  => 'submit',
                    'name'  => 'ok',
                    'value' => get_string('ok'),
                    'class' => 'btn btn-secondary');
            $controls .= html_writer::empty_tag('input', $attributes);
        } else {
            $controls = get_string('youcantdo', 'behaviour'); // You can't do anything.
        }
        $table->data[0][] = $controls;

        return html_writer::table($table);
    }

    /**
     * Render take data.
     *
     * @param take_data $takedata
     * @return string
     */
    protected function render_take_data(take_data $takedata) {
        user_preference_allow_ajax_update('mod_behaviour_statusdropdown', PARAM_TEXT);

        $controls = $this->render_behaviour_take_controls($takedata);
        $table = html_writer::start_div('no-overflow');
        if ($takedata->pageparams->viewmode == mod_behaviour_take_page_params::SORTED_LIST) {
            $table .= $this->render_behaviour_take_list($takedata);
        } else {
            $table .= $this->render_behaviour_take_grid($takedata);
        }
        $table .= html_writer::input_hidden_params($takedata->url(array('sesskey' => sesskey(),
                                                                        'page' => $takedata->pageparams->page,
                                                                        'perpage' => $takedata->pageparams->perpage)));
        $table .= html_writer::end_div();
        $params = array(
                'type'  => 'submit',
                'class' => 'btn btn-primary',
                'value' => get_string('saveandshownext', 'behaviour'));
        $table .= html_writer::tag('center', html_writer::empty_tag('input', $params));
        $table = html_writer::tag('form', $table, array('method' => 'post', 'action' => $takedata->url_path(),
                                                        'id' => 'behaviourtakeform'));

        foreach ($takedata->statuses as $status) {
            $sessionstats[$status->id] = 0;
        }
        // Calculate the sum of statuses for each user.
        $sessionstats[] = array();
        foreach ($takedata->sessionlog as $userlog) {
            foreach ($takedata->statuses as $status) {
                if (in_array($status->id, explode(',',$userlog->statusid)) && in_array($userlog->studentid, array_keys($takedata->users))) {
                    $sessionstats[$status->id]++;
                }
            }
        }

        $statsoutput = '<br/>';
        foreach ($takedata->statuses as $status) {
            $statsoutput .= "$status->description = ".$sessionstats[$status->id]." <br/>";
        }

        return $controls.$table.$statsoutput;
    }

    /**
     * Render take controls.
     *
     * @param take_data $takedata
     * @return string
     */
    protected function render_behaviour_take_controls(take_data $takedata) {

        $urlparams = array('id' => $takedata->cm->id,
            'sessionid' => $takedata->pageparams->sessionid,
            'grouptype' => $takedata->pageparams->grouptype);
        $url = new moodle_url('/mod/behaviour/import/marksessions.php', $urlparams);
//        $return = $this->output->single_button($url, get_string('uploadbehaviour', 'behaviour'));
//        if (!empty($takedata->sessioninfo->automark) &&
//             has_capability('mod/behaviour:manualautomark', context_module::instance($takedata->cm->id)) &&
//                 ($takedata->sessioninfo->automark == BEHAVIOUR_AUTOMARK_ALL ||
//                  $takedata->sessioninfo->automark == BEHAVIOUR_AUTOMARK_ACTIVITYCOMPLETION ||
//                  ($takedata->sessioninfo->automark == BEHAVIOUR_AUTOMARK_CLOSE &&
//                   ($takedata->sessioninfo->sessdate + $takedata->sessioninfo->duration) < time()))) {
//            $urlparams = ['id' => $takedata->cm->id,
//                          'sessionid' => $takedata->pageparams->sessionid,
//                          'grouptype' => $takedata->pageparams->grouptype];
//            $url = new moodle_url('/mod/behaviour/automark.php', $urlparams);
//            $return .= $this->output->single_button($url, get_string('manualtriggerauto', 'behaviour'));
//        }

        $table = new html_table();
        $table->attributes['class'] = ' ';

        $table->data[0][] = $this->construct_take_session_info($takedata);
        $table->data[0][] = $this->construct_take_controls($takedata);

        $return .= $this->output->container(html_writer::table($table), 'generalbox takecontrols');
        return $return;
    }

    /**
     * Construct take session info.
     *
     * @param take_data $takedata
     * @return string
     */
    private function construct_take_session_info(take_data $takedata) {
        $sess = $takedata->sessioninfo;
        $date = userdate($sess->sessdate, get_string('strftimedate'));
        $starttime = behaviour_strftimehm($sess->sessdate);
        $endtime = behaviour_strftimehm($sess->sessdate + $sess->duration);
        $time = html_writer::tag('nobr', $starttime . ($sess->duration > 0 ? ' - ' . $endtime : ''));
        $sessinfo = $date.' '.$time;
        $sessinfo .= html_writer::empty_tag('br');
        $sessinfo .= html_writer::empty_tag('br');
        $sessinfo .= format_text($sess->description);

        return $sessinfo;
    }

    /**
     * Construct take controls.
     *
     * @param take_data $takedata
     * @return string
     */
    private function construct_take_controls(take_data $takedata) {

        $controls = '';
        $context = context_module::instance($takedata->cm->id);
        $group = 0;
        if ($takedata->pageparams->grouptype != mod_behaviour_structure::SESSION_COMMON) {
            $group = $takedata->pageparams->grouptype;
        } else {
            if ($takedata->pageparams->group) {
                $group = $takedata->pageparams->group;
            }
        }

        if (!empty($takedata->cm->groupingid)) {
            if ($group == 0) {
                $groups = array_keys(groups_get_all_groups($takedata->cm->course, 0, $takedata->cm->groupingid, 'g.id'));
            } else {
                $groups = $group;
            }
            $users = get_users_by_capability($context, 'mod/behaviour:canbelisted',
                            'u.id, u.firstname, u.lastname, u.email',
                            '', '', '', $groups,
                            '', false, true);
            $totalusers = count($users);
        } else {
            $totalusers = count_enrolled_users($context, 'mod/behaviour:canbelisted', $group);
        }
        $usersperpage = $takedata->pageparams->perpage;
        if (!empty($takedata->pageparams->page) && $takedata->pageparams->page && $totalusers && $usersperpage) {
            $controls .= html_writer::empty_tag('br');
            $numberofpages = ceil($totalusers / $usersperpage);

            if ($takedata->pageparams->page > 1) {
                $controls .= html_writer::link($takedata->url(array('page' => $takedata->pageparams->page - 1)),
                                                              $this->output->larrow());
            }
            $a = new stdClass();
            $a->page = $takedata->pageparams->page;
            $a->numpages = $numberofpages;
            $text = get_string('pageof', 'behaviour', $a);
            $controls .= html_writer::tag('span', $text,
                                          array('class' => 'attbtn'));
            if ($takedata->pageparams->page < $numberofpages) {
                $controls .= html_writer::link($takedata->url(array('page' => $takedata->pageparams->page + 1,
                            'perpage' => $takedata->pageparams->perpage)), $this->output->rarrow());
            }
        }

        if ($takedata->pageparams->grouptype == mod_behaviour_structure::SESSION_COMMON &&
                ($takedata->groupmode == VISIBLEGROUPS ||
                ($takedata->groupmode && has_capability('moodle/site:accessallgroups', $context)))) {
            $controls .= groups_print_activity_menu($takedata->cm, $takedata->url(), true);
        }

        $controls .= html_writer::empty_tag('br');

        $options = array(
            mod_behaviour_take_page_params::SORTED_LIST   => get_string('sortedlist', 'behaviour'),
            mod_behaviour_take_page_params::SORTED_GRID   => get_string('sortedgrid', 'behaviour'));
        $select = new single_select($takedata->url(), 'viewmode', $options, $takedata->pageparams->viewmode, null);
        $select->set_label(get_string('viewmode', 'behaviour'));
        $select->class = 'singleselect inline';
        $controls .= $this->output->render($select);

        if ($takedata->pageparams->viewmode == mod_behaviour_take_page_params::SORTED_LIST) {
            $options = array(
                    0 => get_string('donotusepaging', 'behaviour'),
                   get_config('behaviour', 'resultsperpage') => get_config('behaviour', 'resultsperpage'));
            $select = new single_select($takedata->url(), 'perpage', $options, $takedata->pageparams->perpage, null);
            $select->class = 'singleselect inline';
            $controls .= $this->output->render($select);
        }

        if ($takedata->pageparams->viewmode == mod_behaviour_take_page_params::SORTED_GRID) {
            $options = array (1 => '1 '.get_string('column', 'behaviour'), '2 '.get_string('columns', 'behaviour'),
                                   '3 '.get_string('columns', 'behaviour'), '4 '.get_string('columns', 'behaviour'),
                                   '5 '.get_string('columns', 'behaviour'), '6 '.get_string('columns', 'behaviour'),
                                   '7 '.get_string('columns', 'behaviour'), '8 '.get_string('columns', 'behaviour'),
                                   '9 '.get_string('columns', 'behaviour'), '10 '.get_string('columns', 'behaviour'));
            $select = new single_select($takedata->url(), 'gridcols', $options, $takedata->pageparams->gridcols, null);
            $select->class = 'singleselect inline';
            $controls .= $this->output->render($select);
        }

        if (isset($takedata->sessions4copy) && count($takedata->sessions4copy) > 0) {
            $controls .= html_writer::empty_tag('br');
            $controls .= html_writer::empty_tag('br');

            $options = array();
            foreach ($takedata->sessions4copy as $sess) {
                $start = behaviour_strftimehm($sess->sessdate);
                $end = $sess->duration ? ' - '.behaviour_strftimehm($sess->sessdate + $sess->duration) : '';
                $options[$sess->id] = $start . $end;
            }
            $select = new single_select($takedata->url(array(), array('copyfrom')), 'copyfrom', $options);
            $select->set_label(get_string('copyfrom', 'behaviour'));
            $select->class = 'singleselect inline';
            $controls .= $this->output->render($select);
        }

        return $controls;
    }

    /**
     * get statusdropdown
     *
     * @return \single_select
     */
    private function statusdropdown() {
        $pref = get_user_preferences('mod_behaviour_statusdropdown');
        if (empty($pref)) {
            $pref = 'unselected';
        }
        $options = array('all' => get_string('statusall', 'behaviour'),
            'unselected' => get_string('statusunselected', 'behaviour'));

        $select = new \single_select(new \moodle_url('/'), 'setallstatus-select', $options,
            $pref, null, 'setallstatus-select');
        $select->label = get_string('setallstatuses', 'behaviour');

        return $select;
    }

    /**
     * Render take list.
     *
     * @param take_data $takedata
     * @return string
     */
    protected function render_behaviour_take_list(take_data $takedata) {
        global $CFG;
        $table = new html_table();
        $table->head = array(
                $this->construct_fullname_head($takedata)
            );
        $table->align = array('left');
        $table->size = array('');
        $table->wrap[0] = 'nowrap';
        // Check if extra useridentity fields need to be added.
        $extrasearchfields = array();
        if (!empty($CFG->showuseridentity) && has_capability('moodle/site:viewuseridentity', $takedata->att->context)) {
            $extrasearchfields = explode(',', $CFG->showuseridentity);
        }
        foreach ($extrasearchfields as $field) {
            $table->head[] = \core_user\fields::get_display_name($field);
            $table->align[] = 'left';
        }
        foreach ($takedata->statuses as $st) {
            $table->head[] = html_writer::link("#", $st->description, array('id' => 'checkstatus'.$st->id,
                'title' => get_string('setallstatusesto', 'behaviour', $st->description)));
            $table->align[] = 'center';
            $table->size[] = '20px';
            // JS to select all radios of this status and prevent default behaviour of # link.
            $this->page->requires->js_amd_inline("
                require(['jquery'], function($) {
                    $('#checkstatus".$st->id."').click(function(e) {
                     if ($('select[name=\"setallstatus-select\"] option:selected').val() == 'all') {
                            $('#behaviourtakeform').find('.st".$st->id."').prop('checkedz', true);
                            M.util.set_user_preference('mod_behaviour_statusdropdown','all');
                        }
                        else {
                            $('#behaviourtakeform').find('input:indeterminate.st".$st->id."').prop('checked', true);
                            M.util.set_user_preference('mod_behaviour_statusdropdown','unselected');
                        }
                        e.preventDefault();
                    });
                });");

        }
//
//        $table->head[] = get_string('remarks', 'behaviour');
        $table->align[] = 'center';
        $table->size[] = '20px';
        $table->attributes['class'] = 'generaltable takelist';

        // Show a 'select all' row of radio buttons.
        $row = new html_table_row();
        $row->attributes['class'] = 'setallstatusesrow';
        foreach ($extrasearchfields as $field) {
            $row->cells[] = '';
        }

        $cell = new html_table_cell(html_writer::div($this->output->render($this->statusdropdown()), 'setallstatuses'));
        $row->cells[] = $cell;
        foreach ($takedata->statuses as $st) {
            $attribs = array(
                'id' => 'radiocheckstatus'.$st->id,
                'type' => 'radio',
                'title' => get_string('setallstatusesto', 'behaviour', $st->description),
                'name' => 'setallstatuses',
                'class' => "st{$st->id}",
            );
            $row->cells[] = html_writer::empty_tag('input', $attribs);
            // Select all radio buttons of the same status.
            $this->page->requires->js_amd_inline("
                require(['jquery'], function($) {
                    $('#radiocheckstatus".$st->id."').click(function(e) {
                        if ($('select[name=\"setallstatus-select\"] option:selected').val() == 'all') {
                            $('#behaviourtakeform').find('.st".$st->id."').prop('checked', true);
                            M.util.set_user_preference('mod_behaviour_statusdropdown','all');
                        }
                        else {
                            $('#behaviourtakeform').find('input:indeterminate.st".$st->id."').prop('checked', true);
                            M.util.set_user_preference('mod_behaviour_statusdropdown','unselected');
                        }
                    });
                });");
        }
//        $row->cells[] = '';
        $table->data[] = $row;

        $i = 0;
        foreach ($takedata->users as $user) {
            $i++;
            $row = new html_table_row();
            $fullname = html_writer::link($takedata->url_view(array('studentid' => $user->id)), fullname($user));
            $fullname = $this->user_picture($user).$fullname; // Show different picture if it is a temporary user.

            $ucdata = $this->construct_take_user_controls($takedata, $user);
            if (array_key_exists('warning', $ucdata)) {
                $fullname .= html_writer::empty_tag('br');
                $fullname .= $ucdata['warning'];
            }
            $row->cells[] = $fullname;
            foreach ($extrasearchfields as $field) {
                $row->cells[] = $user->$field;
            }

            if (array_key_exists('colspan', $ucdata)) {
                $cell = new html_table_cell($ucdata['text']);
                $cell->colspan = $ucdata['colspan'];
                $row->cells[] = $cell;
            } else {
                $row->cells = array_merge($row->cells, $ucdata['text']);
            }

            if (array_key_exists('class', $ucdata)) {
                $row->attributes['class'] = $ucdata['class'];
            }

            $table->data[] = $row;
        }

        return html_writer::table($table);
    }

    /**
     * Render take grid.
     *
     * @param take_data $takedata
     * @return string
     */
    protected function render_behaviour_take_grid(take_data $takedata) {
        $table = new html_table();
        for ($i = 0; $i < $takedata->pageparams->gridcols; $i++) {
            $table->align[] = 'center';
            $table->size[] = '110px';
        }
        $table->attributes['class'] = 'generaltable takegrid';
        $table->headspan = $takedata->pageparams->gridcols;

        $head = array();
        $head[] = html_writer::div($this->output->render($this->statusdropdown()), 'setallstatuses');
        foreach ($takedata->statuses as $st) {
            $head[] = html_writer::link("#", $st->description, array('id' => 'checkstatus'.$st->id,
                                              'title' => get_string('setallstatusesto', 'behaviour', $st->description)));
            // JS to select all radios of this status and prevent default behaviour of # link.
            $this->page->requires->js_amd_inline("
                 require(['jquery'], function($) {
                     $('#checkstatus".$st->id."').click(function(e) {
                         if ($('select[name=\"setallstatus-select\"] option:selected').val() == 'unselected') {
                             $('#behaviourtakeform').find('input:indeterminate.st".$st->id."').prop('checked', true);
                             M.util.set_user_preference('mod_behaviour_statusdropdown','unselected');
                         }
                         else {
                             $('#behaviourtakeform').find('.st".$st->id."').prop('checked', true);
                             M.util.set_user_preference('mod_behaviour_statusdropdown','all');
                         }
                         e.preventDefault();
                     });
                 });");
        }
        $table->head[] = implode('&nbsp;&nbsp;', $head);

        $i = 0;
        $row = new html_table_row();
        foreach ($takedata->users as $user) {
            $celltext = $this->user_picture($user, array('size' => 100));  // Show different picture if it is a temporary user.
            $celltext .= html_writer::empty_tag('br');
            $fullname = html_writer::link($takedata->url_view(array('studentid' => $user->id)), fullname($user));
            $celltext .= html_writer::tag('span', $fullname, array('class' => 'fullname'));
            $celltext .= html_writer::empty_tag('br');
            $ucdata = $this->construct_take_user_controls($takedata, $user);
            $celltext .= is_array($ucdata['text']) ? implode('', $ucdata['text']) : $ucdata['text'];
            if (array_key_exists('warning', $ucdata)) {
                $celltext .= html_writer::empty_tag('br');
                $celltext .= $ucdata['warning'];
            }

            $cell = new html_table_cell($celltext);
            if (array_key_exists('class', $ucdata)) {
                $cell->attributes['class'] = $ucdata['class'];
            }
            $row->cells[] = $cell;

            $i++;
            if ($i % $takedata->pageparams->gridcols == 0) {
                $table->data[] = $row;
                $row = new html_table_row();
            }
        }
        if ($i % $takedata->pageparams->gridcols > 0) {
            $table->data[] = $row;
        }

        return html_writer::table($table);
    }

    /**
     * Construct full name.
     *
     * @param stdClass $data
     * @return string
     */
    private function construct_fullname_head($data) {
        global $CFG;

        $url = $data->url();
        if ($data->pageparams->sort == ATT_SORT_LASTNAME) {
            $url->param('sort', ATT_SORT_FIRSTNAME);
            $firstname = html_writer::link($url, get_string('firstname'));
            $lastname = get_string('lastname');
        } else if ($data->pageparams->sort == ATT_SORT_FIRSTNAME) {
            $firstname = get_string('firstname');
            $url->param('sort', ATT_SORT_LASTNAME);
            $lastname = html_writer::link($url, get_string('lastname'));
        } else {
            $firstname = html_writer::link($data->url(array('sort' => ATT_SORT_FIRSTNAME)), get_string('firstname'));
            $lastname = html_writer::link($data->url(array('sort' => ATT_SORT_LASTNAME)), get_string('lastname'));
        }

        if ($CFG->fullnamedisplay == 'lastname firstname') {
            $fullnamehead = "$lastname / $firstname";
        } else {
            $fullnamehead = "$firstname / $lastname ";
        }

        return $fullnamehead;
    }

    /**
     * Construct take user controls.
     *
     * @param take_data $takedata
     * @param stdClass $user
     * @return array
     */
    private function construct_take_user_controls(take_data $takedata, $user) {
        $celldata = array();
        if ($user->enrolmentend && $user->enrolmentend < $takedata->sessioninfo->sessdate) {
            $celldata['text'] = get_string('enrolmentend', 'behaviour', userdate($user->enrolmentend, '%d.%m.%Y'));
            $celldata['colspan'] = count($takedata->statuses) + 1;
            $celldata['class'] = 'userwithoutenrol';
        } else if (!$user->enrolmentend && $user->enrolmentstatus == ENROL_USER_SUSPENDED) {
            // No enrolmentend and ENROL_USER_SUSPENDED.
            $celldata['text'] = get_string('enrolmentsuspended', 'behaviour');
            $celldata['colspan'] = count($takedata->statuses) + 1;
            $celldata['class'] = 'userwithoutenrol';
        } else {
            if ($takedata->updatemode && !array_key_exists($user->id, $takedata->sessionlog)) {
                $celldata['class'] = 'userwithoutdata';
            }

            $celldata['text'] = array();
            foreach ($takedata->statuses as $st) {
                $params = array(
                        'type'  => 'checkbox',
                        'name'  => 'user'.$user->id.'[]',
                        'class' => 'st'.$st->id,
                        'value' => $st->id);

                $userstatusid = in_array($st->id, explode(',', $takedata->sessionlog[$user->id]->statusid));

                if (array_key_exists($user->id, $takedata->sessionlog) && !empty($userstatusid)) {
                    $params['checked'] = '';
                }

                $input = html_writer::empty_tag('input', $params);

                if ($takedata->pageparams->viewmode == mod_behaviour_take_page_params::SORTED_GRID) {
                    $input = html_writer::tag('nobr', $input . $st->acronym);
                }

                $celldata['text'][] = $input;
            }

//            $params = array(
//                    'type'  => 'text',
//                    'name'  => 'remarks'.$user->id,
//                    'maxlength' => 255);
//            if (array_key_exists($user->id, $takedata->sessionlog)) {
//                $params['value'] = $takedata->sessionlog[$user->id]->remarks;
//            }
//            $celldata['text'][] = html_writer::empty_tag('input', $params);

            if ($user->enrolmentstart > $takedata->sessioninfo->sessdate + $takedata->sessioninfo->duration) {
                $celldata['warning'] = get_string('enrolmentstart', 'behaviour',
                                                  userdate($user->enrolmentstart, '%H:%M %d.%m.%Y'));
                $celldata['class'] = 'userwithoutenrol';
            }
        }

        return $celldata;
    }

    /**
     * Construct take session controls.
     *
     * @param take_data $takedata
     * @param stdClass $user
     * @return array
     */
    private function construct_take_session_controls(take_data $takedata, $user) {
        $celldata = array();
        $celldata['remarks'] = '';
        if ($user->enrolmentend && $user->enrolmentend < $takedata->sessioninfo->sessdate) {
            $celldata['text'] = get_string('enrolmentend', 'behaviour', userdate($user->enrolmentend, '%d.%m.%Y'));
            $celldata['colspan'] = count($takedata->statuses) + 1;
            $celldata['class'] = 'userwithoutenrol';
        } else if (!$user->enrolmentend && $user->enrolmentstatus == ENROL_USER_SUSPENDED) {
            // No enrolmentend and ENROL_USER_SUSPENDED.
            $celldata['text'] = get_string('enrolmentsuspended', 'behaviour');
            $celldata['colspan'] = count($takedata->statuses) + 1;
            $celldata['class'] = 'userwithoutenrol';
        } else {
            if ($takedata->updatemode && !array_key_exists($user->id, $takedata->sessionlog)) {
                $celldata['class'] = 'userwithoutdata';
            }

            $celldata['text'] = array();
            foreach ($takedata->statuses as $st) {
                $params = array(
                        'type'  => 'radio',
                        'name'  => 'user'.$user->id.'sess'.$takedata->sessioninfo->id,
                        'class' => 'st'.$st->id,
                        'value' => $st->id);
                if (array_key_exists($user->id, $takedata->sessionlog) && $st->id == $takedata->sessionlog[$user->id]->statusid) {
                    $params['checked'] = '';
                }

                $input = html_writer::empty_tag('input', $params);

                if ($takedata->pageparams->viewmode == mod_behaviour_take_page_params::SORTED_GRID) {
                    $input = html_writer::tag('nobr', $input . $st->acronym);
                }

                $celldata['text'][] = $input;
            }
            $params = array(
                    'type'  => 'text',
                    'name'  => 'remarks'.$user->id.'sess'.$takedata->sessioninfo->id,
                    'maxlength' => 255);
            if (array_key_exists($user->id, $takedata->sessionlog)) {
                $params['value'] = $takedata->sessionlog[$user->id]->remarks;
            }
            $input = html_writer::empty_tag('input', $params);
            if ($takedata->pageparams->viewmode == mod_behaviour_take_page_params::SORTED_GRID) {
                $input = html_writer::empty_tag('br').$input;
            }
            $celldata['remarks'] = $input;

            if ($user->enrolmentstart > $takedata->sessioninfo->sessdate + $takedata->sessioninfo->duration) {
                $celldata['warning'] = get_string('enrolmentstart', 'behaviour',
                                                  userdate($user->enrolmentstart, '%H:%M %d.%m.%Y'));
                $celldata['class'] = 'userwithoutenrol';
            }
        }

        return $celldata;
    }

    /**
     * Render user data.
     *
     * @param user_data $userdata
     * @return string
     */
    protected function render_user_data(user_data $userdata) {
        global $USER;

        $o = $this->render_user_report_tabs($userdata);

        if ($USER->id == $userdata->user->id ||
            $userdata->pageparams->mode === mod_behaviour_view_page_params::MODE_ALL_SESSIONS) {

            $o .= $this->construct_user_data($userdata);

        } else {

            $table = new html_table();

            $table->attributes['class'] = 'userinfobox';
            $table->colclasses = array('left side', '');
            // Show different picture if it is a temporary user.
            $table->data[0][] = $this->user_picture($userdata->user, array('size' => 100));
            $table->data[0][] = $this->construct_user_data($userdata);

            $o .= html_writer::table($table);
        }

        return $o;
    }

    /**
     * Render user report tabs.
     *
     * @param user_data $userdata
     * @return string
     */
    protected function render_user_report_tabs(user_data $userdata) {
        $tabs = array();

        $tabs[] = new tabobject(mod_behaviour_view_page_params::MODE_THIS_COURSE,
                        $userdata->url()->out(true, array('mode' => mod_behaviour_view_page_params::MODE_THIS_COURSE)),
                        get_string('thiscourse', 'behaviour'));

        // Skip the 'all courses' and 'all sessions' tabs for 'temporary' users.
        if ($userdata->user->type == 'standard') {
            $tabs[] = new tabobject(mod_behaviour_view_page_params::MODE_ALL_COURSES,
                            $userdata->url()->out(true, array('mode' => mod_behaviour_view_page_params::MODE_ALL_COURSES)),
                            get_string('allcourses', 'behaviour'));
            $tabs[] = new tabobject(mod_behaviour_view_page_params::MODE_ALL_SESSIONS,
                            $userdata->url()->out(true, array('mode' => mod_behaviour_view_page_params::MODE_ALL_SESSIONS)),
                            get_string('allsessions', 'behaviour'));
        }

        return print_tabs(array($tabs), $userdata->pageparams->mode, null, null, true);
    }

    /**
     * Construct user data.
     *
     * @param user_data $userdata
     * @return string
     */
    private function construct_user_data(user_data $userdata) {
        global $USER;
        $o = '';
        if ($USER->id <> $userdata->user->id) {
            $o = html_writer::tag('h2', fullname($userdata->user));
        }

        if ($userdata->pageparams->mode == mod_behaviour_view_page_params::MODE_THIS_COURSE) {
            $o .= $this->render_filter_controls($userdata->filtercontrols);
            $o .= $this->construct_user_sessions_log($userdata);
            $o .= html_writer::empty_tag('hr');
            $o .= behaviour_construct_user_data_stat($userdata->summary->get_all_sessions_summary_for($userdata->user->id),
                $userdata->pageparams->view);
        } else if ($userdata->pageparams->mode == mod_behaviour_view_page_params::MODE_ALL_SESSIONS) {
            $allsessions = $this->construct_user_allsessions_log($userdata);
            $o .= html_writer::start_div('allsessionssummary row');
            $o .= html_writer::start_div('userinfo col-auto mr-xl-auto');
            $o .= html_writer::start_div('float-left');
            $o .= $this->user_picture($userdata->user, array('size' => 100, 'class' => 'userpicture float-left'));
            $o .= html_writer::end_div();
            $o .= html_writer::start_div('float-right');
            $o .= $allsessions->summary;
            $o .= html_writer::end_div();
            $o .= html_writer::end_div();
            $o .= html_writer::start_div('attfiltercontrols-wrap col-12 col-xl-auto');
            $o .= $this->render_filter_controls($userdata->filtercontrols);
            $o .= html_writer::end_div();
            $o .= html_writer::end_div();
            $o .= $allsessions->detail;
        } else {
            $table = new html_table();
            $table->head  = array(get_string('course'),
                get_string('pluginname', 'mod_behaviour'),
                get_string('sessionscompleted', 'behaviour'),
                get_string('pointssessionscompleted', 'behaviour'),
                get_string('percentagesessionscompleted', 'behaviour'));
            $table->align = array('left', 'left', 'center', 'center', 'center');
            $table->colclasses = array('colcourse', 'colatt', 'colsessionscompleted',
                                       'colpointssessionscompleted', 'colpercentagesessionscompleted');

            $table2 = clone($table); // Duplicate table for ungraded sessions.
            $totalbehaviour = 0;
            $totalpercentage = 0;
            foreach ($userdata->coursesatts as $ca) {
                $row = new html_table_row();
                $courseurl = new moodle_url('/course/view.php', array('id' => $ca->courseid));
                $row->cells[] = html_writer::link($courseurl, $ca->coursefullname);
                $behavioururl = new moodle_url('/mod/behaviour/view.php', array('id' => $ca->cmid,
                                                                                      'studentid' => $userdata->user->id,
                                                                                      'view' => ATT_VIEW_ALL));
                $row->cells[] = html_writer::link($behavioururl, $ca->attname);
                $usersummary = new stdClass();
                if (isset($userdata->summary[$ca->attid])) {
                    $usersummary = $userdata->summary[$ca->attid]->get_all_sessions_summary_for($userdata->user->id);

                    $row->cells[] = $usersummary->numtakensessions;
                    $row->cells[] = $usersummary->pointssessionscompleted;
                    if (empty($usersummary->numtakensessions)) {
                        $row->cells[] = '-';
                    } else {
                        $row->cells[] = $usersummary->percentagesessionscompleted;
                    }

                }
                if (empty($ca->attgrade)) {
                    $table2->data[] = $row;
                } else {
                    $table->data[] = $row;
                    if ($usersummary->numtakensessions > 0) {
                        $totalbehaviour++;
                        $totalpercentage = $totalpercentage + ($usersummary->takensessionspercentage * 100);
                    }
                }
            }
            $row = new html_table_row();
            if (empty($totalbehaviour)) {
                $average = '-';
            } else {
                $average = format_float($totalpercentage / $totalbehaviour).'%';
            }

            $col = new html_table_cell(get_string('averagebehaviourgraded', 'mod_behaviour'));
            $col->attributes['class'] = 'averagebehaviour';
            $col->colspan = 4;

            $col2 = new html_table_cell($average);
            $col2->style = 'text-align: center';
            $row->cells = array($col, $col2);
            $table->data[] = $row;

            if (!empty($table2->data) && !empty($table->data)) {
                // Print graded header if both tables are being shown.
                $o .= html_writer::div("<h3>".get_string('graded', 'mod_behaviour')."</h3>");
            }
            if (!empty($table->data)) {
                // Don't bother printing the table if no sessions are being shown.
                $o .= html_writer::table($table);
            }

            if (!empty($table2->data)) {
                // Don't print this if it doesn't contain any data.
                $o .= html_writer::div("<h3>".get_string('ungraded', 'mod_behaviour')."</h3>");
                $o .= html_writer::table($table2);
            }
        }

        return $o;
    }

    /**
     * Construct user sessions log.
     *
     * @param user_data $userdata
     * @return string
     */
    private function construct_user_sessions_log(user_data $userdata) {
        global $USER;
        $context = context_module::instance($userdata->filtercontrols->cm->id);

        $shortform = false;
        if ($USER->id == $userdata->user->id) {
            // This is a user viewing their own stuff - hide non-relevant columns.
            $shortform = true;
        }

        $table = new html_table();
        $table->attributes['class'] = 'generaltable attwidth boxaligncenter';
        $table->head = array();
        $table->align = array();
        $table->size = array();
        $table->colclasses = array();
        if (!$shortform) {
            $table->head[] = get_string('sessiontypeshort', 'behaviour');
            $table->align[] = '';
            $table->size[] = '1px';
            $table->colclasses[] = '';
        }
        $table->head[] = get_string('date');
        $table->head[] = get_string('description', 'behaviour');

        $table->align = array_merge($table->align, array('', 'left'));
        $table->colclasses = array_merge($table->colclasses, array('datecol', 'desccol'));
        $table->size = array_merge($table->size, array('1px', '*'));

        // Add custom fields.
        $customfields = [];
        if (!empty($userdata->sessionslog)) {
            $sessionids = [];
            foreach ($userdata->sessionslog as $s) {
                $sessionids[] = $s->id;
            }
            $handler = \mod_behaviour\customfield\session_handler::create();
            $customfields = $handler->get_fields_for_display(reset($sessionids)); // Pass first sessionid.
            $customfieldsdata = $handler->get_instances_data($sessionids);
        }
        foreach ($customfields as $field) {
            $table->head[] = $field->get_formatted_name();
            $table->align[] = '';
            $table->size[] = '';
            $table->colclasses[] = 'customfield';
        }
        $table->head[] = get_string('status', 'behaviour');
        $table->head[] = get_string('points', 'behaviour');
        $table->head[] = get_string('remarks', 'behaviour');

        $table->align = array_merge($table->align, array('center', 'center', 'center'));
        $table->colclasses = array_merge($table->colclasses, array('statuscol', 'pointscol', 'remarkscol'));
        $table->size = array_merge($table->size, array('*', '1px', '*'));

        if (has_capability('mod/behaviour:takebehaviours', $context)) {
            $table->head[] = get_string('action');
            $table->align[] = '';
            $table->size[] = '';
        }

        $statussetmaxpoints = behaviour_get_statusset_maxpoints($userdata->statuses);

        $i = 0;
        foreach ($userdata->sessionslog as $sess) {
            $i++;

            $row = new html_table_row();
            if (!$shortform) {
                if ($sess->groupid) {
                    $sessiontypeshort = get_string('group') . ': ' . $userdata->groups[$sess->groupid]->name;
                } else {
                    $sessiontypeshort = get_string('commonsession', 'behaviour');
                }

                $row->cells[] = html_writer::tag('nobr', $sessiontypeshort);
            }
            $row->cells[] = userdate($sess->sessdate, get_string('strftimedmyw', 'behaviour')) .
             " ". $this->construct_time($sess->sessdate, $sess->duration);
            $row->cells[] = format_text($sess->description);
            foreach ($customfields as $field) {
                if (isset($customfieldsdata[$sess->id][$field->get('id')])) {
                    $row->cells[] = $customfieldsdata[$sess->id][$field->get('id')]->get('value');
                } else {
                    $row->cells[] = '';
                }
            }

            if (!empty($sess->statusid)) {
                $status = $userdata->statuses[$sess->statusid];
                $row->cells[] = $status->description;
                $row->cells[] = format_float($status->grade, 1, true, true) . ' / ' .
                                    format_float($statussetmaxpoints[$status->setnumber], 1, true, true);
                $row->cells[] = $sess->remarks;
            } else if (($sess->sessdate + $sess->duration) < $userdata->user->enrolmentstart) {
                $cell = new html_table_cell(get_string('enrolmentstart', 'behaviour',
                                            userdate($userdata->user->enrolmentstart, '%d.%m.%Y')));
                $cell->colspan = 3;
                $row->cells[] = $cell;
            } else if ($userdata->user->enrolmentend && $sess->sessdate > $userdata->user->enrolmentend) {
                $cell = new html_table_cell(get_string('enrolmentend', 'behaviour',
                                            userdate($userdata->user->enrolmentend, '%d.%m.%Y')));
                $cell->colspan = 3;
                $row->cells[] = $cell;
            } else {
                list($canmark, $reason) = behaviour_can_student_mark($sess, false);
                if ($canmark) {
                    if ($sess->rotateqrcode == 1) {
                        $url = new moodle_url('/mod/behaviour/behaviour.php');
                        $output = html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'sessid',
                                'value' => $sess->id));
                        $output .= html_writer::empty_tag('input', array('type' => 'text', 'name' => 'qrpass',
                                'placeholder' => "Enter password"));
                        $output .= html_writer::empty_tag('input', array('type' => 'submit',
                                'value' => get_string('submit'),
                                'class' => 'btn btn-secondary'));
                        $cell = new html_table_cell(html_writer::tag('form', $output,
                            array('action' => $url->out(), 'method' => 'get')));
                    } else {
                        // Student can mark their own behaviour.
                        // URL to the page that lets the student modify their behaviour.
                        $url = new moodle_url('/mod/behaviour/behaviour.php',
                                array('sessid' => $sess->id, 'sesskey' => sesskey()));
                        $cell = new html_table_cell(html_writer::link($url, get_string('submitbehaviour', 'behaviour')));
                    }
                    $cell->colspan = 3;
                    $row->cells[] = $cell;
                } else { // Student cannot mark their own attendace.
                    $row->cells[] = '?';
                    $row->cells[] = '? / ' . format_float($statussetmaxpoints[$sess->statusset], 1, true, true);
                    $row->cells[] = '';
                }
            }

            if (has_capability('mod/behaviour:takebehaviours', $context)) {
                $params = array('id' => $userdata->filtercontrols->cm->id,
                    'sessionid' => $sess->id,
                    'grouptype' => $sess->groupid);
                $url = new moodle_url('/mod/behaviour/take.php', $params);
                $icon = $this->output->pix_icon('redo', get_string('changebehaviour', 'behaviour'), 'behaviour');
                $row->cells[] = html_writer::link($url, $icon);
            }

            $table->data[] = $row;
        }

        return html_writer::table($table);
    }

    /**
     * Construct table showing all sessions, not limited to current course.
     *
     * @param user_data $userdata
     * @return string
     */
    private function construct_user_allsessions_log(user_data $userdata) {
        global $USER;

        $allsessions = new stdClass();

        $shortform = false;
        if ($USER->id == $userdata->user->id) {
            // This is a user viewing their own stuff - hide non-relevant columns.
            $shortform = true;
        }

        $groupby = $userdata->pageparams->groupby;

        $table = new html_table();
        $table->attributes['class'] = 'generaltable attwidth boxaligncenter allsessions';
        $table->head = array();
        $table->align = array();
        $table->size = array();
        $table->colclasses = array();
        $colcount = 0;
        $summarywidth = 0;

        // If grouping by date, we need some form of date up front.
        // Only need course column if we are not using course to group
        // (currently date is only option which does not use course).
        if ($groupby === 'date') {
            $table->head[] = '';
            $table->align[] = 'left';
            $table->colclasses[] = 'grouper';
            $table->size[] = '1px';

            $table->head[] = get_string('date');
            $table->align[] = 'left';
            $table->colclasses[] = 'datecol';
            $table->size[] = '1px';
            $colcount++;

            $table->head[] = get_string('course');
            $table->align[] = 'left';
            $table->colclasses[] = 'colcourse';
            $colcount++;
        } else {
            $table->head[] = '';
            $table->align[] = 'left';
            $table->colclasses[] = 'grouper';
            $table->size[] = '1px';
            if ($groupby === 'activity') {
                $table->head[] = '';
                $table->align[] = 'left';
                $table->colclasses[] = 'grouper';
                $table->size[] = '1px';
            }
        }

        // Need activity column unless we are using activity to group.
        if ($groupby !== 'activity') {
            $table->head[] = get_string('pluginname', 'mod_behaviour');
            $table->align[] = 'left';
            $table->colclasses[] = 'colcourse';
            $table->size[] = '*';
            $colcount++;
        }

        // If grouping by date, it belongs up front rather than here.
        if ($groupby !== 'date') {
            $table->head[] = get_string('date');
            $table->align[] = 'left';
            $table->colclasses[] = 'datecol';
            $table->size[] = '1px';
            $colcount++;
        }

        // Use "session" instead of "description".
        $table->head[] = get_string('session', 'behaviour');
        $table->align[] = 'left';
        $table->colclasses[] = 'desccol';
        $table->size[] = '*';
        $colcount++;

        if (!$shortform) {
            $table->head[] = get_string('sessiontypeshort', 'behaviour');
            $table->align[] = '';
            $table->size[] = '*';
            $table->colclasses[] = '';
            $colcount++;
        }

        if (!empty($USER->behaviourediting)) {
            $table->head[] = get_string('status', 'behaviour');
            $table->align[] = 'center';
            $table->colclasses[] = 'statuscol';
            $table->size[] = '*';
            $colcount++;
            $summarywidth++;

            $table->head[] = get_string('remarks', 'behaviour');
            $table->align[] = 'center';
            $table->colclasses[] = 'remarkscol';
            $table->size[] = '*';
            $colcount++;
            $summarywidth++;
        } else {
            $table->head[] = get_string('status', 'behaviour');
            $table->align[] = 'center';
            $table->colclasses[] = 'statuscol';
            $table->size[] = '*';
            $colcount++;
            $summarywidth++;

            $table->head[] = get_string('points', 'behaviour');
            $table->align[] = 'center';
            $table->colclasses[] = 'pointscol';
            $table->size[] = '1px';
            $colcount++;
            $summarywidth++;

            $table->head[] = get_string('remarks', 'behaviour');
            $table->align[] = 'center';
            $table->colclasses[] = 'remarkscol';
            $table->size[] = '*';
            $colcount++;
            $summarywidth++;
        }

        $statusmaxpoints = array();
        foreach ($userdata->statuses as $attid => $attstatuses) {
            $statusmaxpoints[$attid] = behaviour_get_statusset_maxpoints($attstatuses);
        }

        $lastgroup = array(null, null);
        $groups = array();
        $stats = array(
            'course' => array(),
            'activity' => array(),
            'date' => array(),
            'overall' => array(
                'points' => 0,
                'maxpointstodate' => 0,
                'maxpoints' => 0,
                'pcpointstodate' => null,
                'pcpoints' => null,
                'statuses' => array()
            )
        );
        $group = null;
        if ($userdata->sessionslog) {
            foreach ($userdata->sessionslog as $sess) {
                if ($groupby === 'date') {
                    $weekformat = date("YW", $sess->sessdate);
                    if ($weekformat != $lastgroup[0]) {
                        if ($group !== null) {
                            array_push($groups, $group);
                        }
                        $group = array();
                        $lastgroup[0] = $weekformat;
                    }
                    if (!array_key_exists($weekformat, $stats['date'])) {
                        $stats['date'][$weekformat] = array(
                            'points' => 0,
                            'maxpointstodate' => 0,
                            'maxpoints' => 0,
                            'pcpointstodate' => null,
                            'pcpoints' => null,
                            'statuses' => array()
                        );
                    }
                    $statussetmaxpoints = $statusmaxpoints[$sess->behaviourid];
                    // Ensure all possible acronyms for current sess's statusset are available as
                    // keys in status array for period.
                    //
                    // A bit yucky because we can't tell whether we've seen statusset before, and
                    // we usually will have, so much wasted spinning.
                    foreach ($userdata->statuses[$sess->behaviourid] as $attstatus) {
                        if ($attstatus->setnumber === $sess->statusset) {
                            if (!array_key_exists($attstatus->acronym, $stats['date'][$weekformat]['statuses'])) {
                                $stats['date'][$weekformat]['statuses'][$attstatus->acronym] =
                                    array('count' => 0, 'description' => $attstatus->description);
                            }
                            if (!array_key_exists($attstatus->acronym, $stats['overall']['statuses'])) {
                                $stats['overall']['statuses'][$attstatus->acronym] =
                                    array('count' => 0, 'description' => $attstatus->description);
                            }
                        }
                    }
                    // The array_key_exists check is for hidden statuses.
                    if (isset($sess->statusid) && array_key_exists($sess->statusid, $userdata->statuses[$sess->behaviourid])) {
                        $status = $userdata->statuses[$sess->behaviourid][$sess->statusid];
                        $stats['date'][$weekformat]['statuses'][$status->acronym]['count']++;
                        $stats['date'][$weekformat]['points'] += $status->grade;
                        $stats['date'][$weekformat]['maxpointstodate'] += $statussetmaxpoints[$sess->statusset];
                        $stats['overall']['statuses'][$status->acronym]['count']++;
                        $stats['overall']['points'] += $status->grade;
                        $stats['overall']['maxpointstodate'] += $statussetmaxpoints[$sess->statusset];
                    }
                    $stats['date'][$weekformat]['maxpoints'] += $statussetmaxpoints[$sess->statusset];
                    $stats['overall']['maxpoints'] += $statussetmaxpoints[$sess->statusset];
                } else {
                    // By course and perhaps activity.
                    if (
                        ($sess->courseid != $lastgroup[0]) ||
                        ($groupby === 'activity' && $sess->cmid != $lastgroup[1])
                    ) {
                        if ($group !== null) {
                            array_push($groups, $group);
                        }
                        $group = array();
                        $lastgroup[0] = $sess->courseid;
                        $lastgroup[1] = $sess->cmid;
                    }
                    if (!array_key_exists($sess->courseid, $stats['course'])) {
                        $stats['course'][$sess->courseid] = array(
                            'points' => 0,
                            'maxpointstodate' => 0,
                            'maxpoints' => 0,
                            'pcpointstodate' => null,
                            'pcpoints' => null,
                            'statuses' => array()
                        );
                    }
                    $statussetmaxpoints = $statusmaxpoints[$sess->behaviourid];
                    // Ensure all possible acronyms for current sess's statusset are available as
                    // keys in status array for course
                    //
                    // A bit yucky because we can't tell whether we've seen statusset before, and
                    // we usually will have, so much wasted spinning.
                    foreach ($userdata->statuses[$sess->behaviourid] as $attstatus) {
                        if ($attstatus->setnumber === $sess->statusset) {
                            if (!array_key_exists($attstatus->acronym, $stats['course'][$sess->courseid]['statuses'])) {
                                $stats['course'][$sess->courseid]['statuses'][$attstatus->acronym] =
                                    array('count' => 0, 'description' => $attstatus->description);
                            }
                            if (!array_key_exists($attstatus->acronym, $stats['overall']['statuses'])) {
                                $stats['overall']['statuses'][$attstatus->acronym] =
                                    array('count' => 0, 'description' => $attstatus->description);
                            }
                        }
                    }
                    // The array_key_exists check is for hidden statuses.
                    if (isset($sess->statusid) && array_key_exists($sess->statusid, $userdata->statuses[$sess->behaviourid])) {
                        $status = $userdata->statuses[$sess->behaviourid][$sess->statusid];
                        $stats['course'][$sess->courseid]['statuses'][$status->acronym]['count']++;
                        $stats['course'][$sess->courseid]['points'] += $status->grade;
                        $stats['course'][$sess->courseid]['maxpointstodate'] += $statussetmaxpoints[$sess->statusset];
                        $stats['overall']['statuses'][$status->acronym]['count']++;
                        $stats['overall']['points'] += $status->grade;
                        $stats['overall']['maxpointstodate'] += $statussetmaxpoints[$sess->statusset];
                    }
                    $stats['course'][$sess->courseid]['maxpoints'] += $statussetmaxpoints[$sess->statusset];
                    $stats['overall']['maxpoints'] += $statussetmaxpoints[$sess->statusset];

                    if (!array_key_exists($sess->cmid, $stats['activity'])) {
                        $stats['activity'][$sess->cmid] = array(
                            'points' => 0,
                            'maxpointstodate' => 0,
                            'maxpoints' => 0,
                            'pcpointstodate' => null,
                            'pcpoints' => null,
                            'statuses' => array()
                        );
                    }
                    $statussetmaxpoints = $statusmaxpoints[$sess->behaviourid];
                    // Ensure all possible acronyms for current sess's statusset are available as
                    // keys in status array for period
                    //
                    // A bit yucky because we can't tell whether we've seen statusset before, and
                    // we usually will have, so much wasted spinning.
                    foreach ($userdata->statuses[$sess->behaviourid] as $attstatus) {
                        if ($attstatus->setnumber === $sess->statusset) {
                            if (!array_key_exists($attstatus->acronym, $stats['activity'][$sess->cmid]['statuses'])) {
                                $stats['activity'][$sess->cmid]['statuses'][$attstatus->acronym] =
                                    array('count' => 0, 'description' => $attstatus->description);
                            }
                            if (!array_key_exists($attstatus->acronym, $stats['overall']['statuses'])) {
                                $stats['overall']['statuses'][$attstatus->acronym] =
                                    array('count' => 0, 'description' => $attstatus->description);
                            }
                        }
                    }
                    // The array_key_exists check is for hidden statuses.
                    if (isset($sess->statusid) && array_key_exists($sess->statusid, $userdata->statuses[$sess->behaviourid])) {
                        $status = $userdata->statuses[$sess->behaviourid][$sess->statusid];
                        $stats['activity'][$sess->cmid]['statuses'][$status->acronym]['count']++;
                        $stats['activity'][$sess->cmid]['points'] += $status->grade;
                        $stats['activity'][$sess->cmid]['maxpointstodate'] += $statussetmaxpoints[$sess->statusset];
                    }
                    $stats['activity'][$sess->cmid]['maxpoints'] += $statussetmaxpoints[$sess->statusset];
                    $stats['overall']['maxpoints'] += $statussetmaxpoints[$sess->statusset];
                }
                array_push($group, $sess);
            }
            array_push($groups, $group);
        }

        $points = $stats['overall']['points'];
        $maxpoints = $stats['overall']['maxpointstodate'];
        $summarytable = new html_table();
        $summarytable->attributes['class'] = 'generaltable table-bordered table-condensed';
        $row = new html_table_row();
        $cell = new html_table_cell(get_string('allsessionstotals', 'behaviour'));
        $cell->colspan = 2;
        $cell->header = true;
        $row->cells[] = $cell;
        $summarytable->data[] = $row;
        foreach ($stats['overall']['statuses'] as $acronym => $status) {
            $row = new html_table_row();
            $row->cells[] = $status['description'] . ":";
            $row->cells[] = $status['count'];
            $summarytable->data[] = $row;
        }

        $row = new html_table_row();
        if ($maxpoints !== 0) {
            $pctodate = format_float( $points * 100 / $maxpoints);
            $pointsinfo  = get_string('points', 'behaviour') . ": " . $points . "/" . $maxpoints;
            $pointsinfo .= " (" . $pctodate . "%)";
        } else {
            $pointsinfo  = get_string('points', 'behaviour') . ": " . $points . "/" . $maxpoints;
        }
        $pointsinfo .= " " . get_string('todate', 'behaviour');
        $cell = new html_table_cell($pointsinfo);
        $cell->colspan = 2;
        $row->cells[] = $cell;
        $summarytable->data[] = $row;
        $allsessions->summary = html_writer::table($summarytable);

        $lastgroup = array(null, null);
        foreach ($groups as $group) {

            $statussetmaxpoints = $statusmaxpoints[$sess->behaviourid];

            // For use in headings etc.
            $sess = $group[0];

            if ($groupby === 'date') {
                $row = new html_table_row();
                $row->attributes['class'] = 'grouper';
                $cell = new html_table_cell();
                $cell->rowspan = count($group) + 2;
                $row->cells[] = $cell;
                $week = date("W", $sess->sessdate);
                $year = date("Y", $sess->sessdate);
                // ISO week starts on day 1, Monday.
                $weekstart = date_timestamp_get(date_isodate_set(date_create(), $year, $week, 1));
                $dmywformat = get_string('strftimedmyw', 'behaviour');
                $cell = new html_table_cell(get_string('weekcommencing', 'behaviour') . ": " . userdate($weekstart, $dmywformat));
                $cell->colspan = $colcount - $summarywidth;
                $cell->rowspan = 2;
                $cell->attributes['class'] = 'groupheading';
                $row->cells[] = $cell;
                $weekformat = date("YW", $sess->sessdate);
                $points = $stats['date'][$weekformat]['points'];
                $maxpoints = $stats['date'][$weekformat]['maxpointstodate'];
                if ($maxpoints !== 0) {
                    $pctodate = format_float( $points * 100 / $maxpoints);
                    $summary  = get_string('points', 'behaviour') . ": " . $points . "/" . $maxpoints;
                    $summary .= " (" . $pctodate . "%)";
                } else {
                    $summary  = get_string('points', 'behaviour') . ": " . $points . "/" . $maxpoints;
                }
                $summary .= " " . get_string('todate', 'behaviour');
                $cell = new html_table_cell($summary);
                $cell->colspan = $summarywidth;
                $row->cells[] = $cell;
                $table->data[] = $row;
                $row = new html_table_row();
                $row->attributes['class'] = 'grouper';
                $summary = array();
                foreach ($stats['date'][$weekformat]['statuses'] as $acronym => $status) {
                    array_push($summary, html_writer::tag('b', $acronym) . $status['count']);
                }
                $cell = new html_table_cell(implode(" ", $summary));
                $cell->colspan = $summarywidth;
                $row->cells[] = $cell;
                $table->data[] = $row;
                $lastgroup[0] = date("YW", $weekstart);
            } else {
                if ($groupby === 'course' || $sess->courseid !== $lastgroup[0]) {
                    $row = new html_table_row();
                    $row->attributes['class'] = 'grouper';
                    $cell = new html_table_cell();
                    $cell->rowspan = count($group) + 2;
                    if ($groupby === 'activity') {
                        $headcell = $cell; // Keep ref to be able to adjust rowspan later.
                        $cell->rowspan += 2;
                        $row->cells[] = $cell;
                        $cell = new html_table_cell();
                        $cell->rowspan = 2;
                    }
                    $row->cells[] = $cell;
                    $courseurl = new moodle_url('/course/view.php', array('id' => $sess->courseid));
                    $cell = new html_table_cell(get_string('course', 'behaviour') . ": " .
                        html_writer::link($courseurl, $sess->cname));
                    $cell->colspan = $colcount - $summarywidth;
                    $cell->rowspan = 2;
                    $cell->attributes['class'] = 'groupheading';
                    $row->cells[] = $cell;
                    $points = $stats['course'][$sess->courseid]['points'];
                    $maxpoints = $stats['course'][$sess->courseid]['maxpointstodate'];
                    if ($maxpoints !== 0) {
                        $pctodate = format_float( $points * 100 / $maxpoints);
                        $summary  = get_string('points', 'behaviour') . ": " . $points . "/" . $maxpoints;
                        $summary .= " (" . $pctodate . "%)";
                    } else {
                        $summary  = get_string('points', 'behaviour') . ": " . $points . "/" . $maxpoints;
                    }
                    $summary .= " " . get_string('todate', 'behaviour');
                    $cell = new html_table_cell($summary);
                    $cell->colspan = $summarywidth;
                    $row->cells[] = $cell;
                    $table->data[] = $row;
                    $row = new html_table_row();
                    $row->attributes['class'] = 'grouper';
                    $summary = array();
                    foreach ($stats['course'][$sess->courseid]['statuses'] as $acronym => $status) {
                        array_push($summary, html_writer::tag('b', $acronym) . $status['count']);
                    }
                    $cell = new html_table_cell(implode(" ", $summary));
                    $cell->colspan = $summarywidth;
                    $row->cells[] = $cell;
                    $table->data[] = $row;
                }
                if ($groupby === 'activity') {
                    if ($sess->courseid === $lastgroup[0]) {
                        $headcell->rowspan += count($group) + 2;
                    }
                    $row = new html_table_row();
                    $row->attributes['class'] = 'grouper';
                    $cell = new html_table_cell();
                    $cell->rowspan = count($group) + 2;
                    $row->cells[] = $cell;
                    $behavioururl = new moodle_url('/mod/behaviour/view.php', array('id' => $sess->cmid,
                                                                                      'studentid' => $userdata->user->id,
                                                                                      'view' => ATT_VIEW_ALL));
                    $cell = new html_table_cell(get_string('pluginname', 'mod_behaviour') .
                        ": " . html_writer::link($behavioururl, $sess->attname));
                    $cell->colspan = $colcount - $summarywidth;
                    $cell->rowspan = 2;
                    $cell->attributes['class'] = 'groupheading';
                    $row->cells[] = $cell;
                    $points = $stats['activity'][$sess->cmid]['points'];
                    $maxpoints = $stats['activity'][$sess->cmid]['maxpointstodate'];
                    if ($maxpoints !== 0) {
                        $pctodate = format_float( $points * 100 / $maxpoints);
                        $summary  = get_string('points', 'behaviour') . ": " . $points . "/" . $maxpoints;
                        $summary .= " (" . $pctodate . "%)";
                    } else {
                        $summary  = get_string('points', 'behaviour') . ": " . $points . "/" . $maxpoints;
                    }
                    $summary .= " " . get_string('todate', 'behaviour');
                    $cell = new html_table_cell($summary);
                    $cell->colspan = $summarywidth;
                    $row->cells[] = $cell;
                    $table->data[] = $row;
                    $row = new html_table_row();
                    $row->attributes['class'] = 'grouper';
                    $summary = array();
                    foreach ($stats['activity'][$sess->cmid]['statuses'] as $acronym => $status) {
                        array_push($summary, html_writer::tag('b', $acronym) . $status['count']);
                    }
                    $cell = new html_table_cell(implode(" ", $summary));
                    $cell->colspan = $summarywidth;
                    $row->cells[] = $cell;
                    $table->data[] = $row;
                }
                $lastgroup[0] = $sess->courseid;
                $lastgroup[1] = $sess->cmid;
            }

            // Now iterate over sessions in group...

            foreach ($group as $sess) {
                $row = new html_table_row();

                // If grouping by date, we need some form of date up front.
                // Only need course column if we are not using course to group
                // (currently date is only option which does not use course).
                if ($groupby === 'date') {
                    // What part of date do we want if grouped by it already?
                    $row->cells[] = userdate($sess->sessdate, get_string('strftimedmw', 'behaviour')) .
                        " ". $this->construct_time($sess->sessdate, $sess->duration);

                    $courseurl = new moodle_url('/course/view.php', array('id' => $sess->courseid));
                    $row->cells[] = html_writer::link($courseurl, $sess->cname);
                }

                // Need activity column unless we are using activity to group.
                if ($groupby !== 'activity') {
                    $behavioururl = new moodle_url('/mod/behaviour/view.php', array('id' => $sess->cmid,
                                                                                      'studentid' => $userdata->user->id,
                                                                                      'view' => ATT_VIEW_ALL));
                    $row->cells[] = html_writer::link($behavioururl, $sess->attname);
                }

                // If grouping by date, it belongs up front rather than here.
                if ($groupby !== 'date') {
                    $row->cells[] = userdate($sess->sessdate, get_string('strftimedmyw', 'behaviour')) .
                        " ". $this->construct_time($sess->sessdate, $sess->duration);
                }

                $sesscontext = context_module::instance($sess->cmid);
                if (has_capability('mod/behaviour:takebehaviours', $sesscontext)) {
                    $sessionurl = new moodle_url('/mod/behaviour/take.php', array('id' => $sess->cmid,
                                                                                   'sessionid' => $sess->id,
                                                                                   'grouptype' => $sess->groupid));
                    $description = html_writer::link($sessionurl, $sess->description);
                } else {
                    $description = $sess->description;
                }
                $row->cells[] = $description;

                if (!$shortform) {
                    if ($sess->groupid) {
                        $sessiontypeshort = get_string('group') . ': ' . $userdata->groups[$sess->courseid][$sess->groupid]->name;
                    } else {
                        $sessiontypeshort = get_string('commonsession', 'behaviour');
                    }
                    $row->cells[] = html_writer::tag('nobr', $sessiontypeshort);
                }

                if (!empty($USER->behaviourediting)) {
                    $context = context_module::instance($sess->cmid);
                    if (has_capability('mod/behaviour:takebehaviours', $context)) {
                        // Takedata needs:
                        // sessioninfo->sessdate
                        // sessioninfo->duration
                        // statuses
                        // updatemode
                        // sessionlog[userid]->statusid
                        // sessionlog[userid]->remarks
                        // pageparams->viewmode == mod_behaviour_take_page_params::SORTED_GRID
                        // and urlparams to be able to use url method later.
                        //
                        // user needs:
                        // enrolmentstart
                        // enrolmentend
                        // enrolmentstatus
                        // id.

                        $nastyhack = new \ReflectionClass('mod_behaviour\output\take_data');
                        $takedata = $nastyhack->newInstanceWithoutConstructor();
                        $takedata->sessioninfo = $sess;
                        $takedata->statuses = array_filter($userdata->statuses[$sess->behaviourid], function($x) use ($sess) {
                            return ($x->setnumber == $sess->statusset);
                        });
                        $takedata->updatemode = true;
                        $takedata->sessionlog = array($userdata->user->id => $sess);
                        $takedata->pageparams = new stdClass();
                        $takedata->pageparams->viewmode = mod_behaviour_take_page_params::SORTED_GRID;
                        $ucdata = $this->construct_take_session_controls($takedata, $userdata->user);

                        $celltext = join($ucdata['text']);

                        if (array_key_exists('warning', $ucdata)) {
                            $celltext .= html_writer::empty_tag('br');
                            $celltext .= $ucdata['warning'];
                        }
                        if (array_key_exists('class', $ucdata)) {
                            $row->attributes['class'] = $ucdata['class'];
                        }

                        $cell = new html_table_cell($celltext);
                        $row->cells[] = $cell;

                        $celltext = empty($ucdata['remarks']) ? '' : $ucdata['remarks'];
                        $cell = new html_table_cell($celltext);
                        $row->cells[] = $cell;

                    } else {
                        if (!empty($sess->statusid)) {
                            $status = $userdata->statuses[$sess->behaviourid][$sess->statusid];
                            $row->cells[] = $status->description;
                            $row->cells[] = $sess->remarks;
                        }
                    }

                } else {
                    if (!empty($sess->statusid)) {
                        $status = $userdata->statuses[$sess->behaviourid][$sess->statusid];
                        $row->cells[] = $status->description;
                        $row->cells[] = format_float($status->grade, 1, true, true) . ' / ' .
                            format_float($statussetmaxpoints[$status->setnumber], 1, true, true);
                        $row->cells[] = $sess->remarks;
                    } else if (($sess->sessdate + $sess->duration) < $userdata->user->enrolmentstart) {
                        $cell = new html_table_cell(get_string('enrolmentstart', 'behaviour',
                        userdate($userdata->user->enrolmentstart, '%d.%m.%Y')));
                        $cell->colspan = 3;
                        $row->cells[] = $cell;
                    } else if ($userdata->user->enrolmentend && $sess->sessdate > $userdata->user->enrolmentend) {
                        $cell = new html_table_cell(get_string('enrolmentend', 'behaviour',
                        userdate($userdata->user->enrolmentend, '%d.%m.%Y')));
                        $cell->colspan = 3;
                        $row->cells[] = $cell;
                    } else {
                        list($canmark, $reason) = behaviour_can_student_mark($sess, false);
                        if ($canmark) {
                            // Student can mark their own behaviour.
                            // URL to the page that lets the student modify their behaviour.

                            $url = new moodle_url('/mod/behaviour/behaviour.php',
                            array('sessid' => $sess->id, 'sesskey' => sesskey()));
                            $cell = new html_table_cell(html_writer::link($url, get_string('submitbehaviour', 'behaviour')));
                            $cell->colspan = 3;
                            $row->cells[] = $cell;
                        } else { // Student cannot mark their own attendace.
                            $row->cells[] = '?';
                            $row->cells[] = '? / ' . format_float($statussetmaxpoints[$sess->statusset], 1, true, true);
                            $row->cells[] = '';
                        }
                    }
                }

                $table->data[] = $row;
            }
        }

        if (!empty($USER->behaviourediting)) {
            $row = new html_table_row();
            $params = array(
                'type'  => 'submit',
                'class' => 'btn btn-primary',
                'value' => get_string('save', 'behaviour'));
            $cell = new html_table_cell(html_writer::tag('center', html_writer::empty_tag('input', $params)));
            $cell->colspan = $colcount + (($groupby == 'activity') ? 2 : 1);
            $row->cells[] = $cell;
            $table->data[] = $row;
        }

        $logtext = html_writer::table($table);

        if (!empty($USER->behaviourediting)) {
            $formtext = html_writer::start_div('no-overflow');
            $formtext .= $logtext;
            $formtext .= html_writer::input_hidden_params($userdata->url(array('sesskey' => sesskey())));
            $formtext .= html_writer::end_div();
            // Could use userdata->urlpath if not private or userdata->url_path() if existed, but '' turns
            // out to DTRT.
            $logtext = html_writer::tag('form', $formtext, array('method' => 'post', 'action' => '',
                                                                 'id' => 'behaviourtakeform'));
        }
        $allsessions->detail = $logtext;
        return $allsessions;
    }

    /**
     * Construct time for display.
     *
     * @param int $datetime
     * @param int $duration
     * @return string
     */
    private function construct_time($datetime, $duration) {
        $time = html_writer::tag('nobr', behaviour_construct_session_time($datetime, $duration));

        return $time;
    }

    /**
     * Render report data.
     *
     * @param report_data $reportdata
     * @return string
     */
    protected function render_report_data(report_data $reportdata) {
        global $COURSE;

        // Initilise Javascript used to (un)check all checkboxes.
        $this->page->requires->js_init_call('M.mod_behaviour.init_manage');

        $table = new html_table();
        $table->attributes['class'] = 'generaltable attwidth attreport';

        $userrows = $this->get_user_rows($reportdata);

        if ($reportdata->pageparams->view == ATT_VIEW_SUMMARY) {
            $sessionrows = array();
        } else {
            $sessionrows = $this->get_session_rows($reportdata);
        }

        $setnumber = -1;
        $statusetcount = 0;
        foreach ($reportdata->statuses as $sts) {
            if ($sts->setnumber != $setnumber) {
                $statusetcount++;
                $setnumber = $sts->setnumber;
            }
        }

        $acronymrows = $this->get_acronym_rows($reportdata, true);
        $startwithcontrast = $statusetcount % 2 == 0;
        $summaryrows = $this->get_summary_rows($reportdata, $startwithcontrast);

        // Check if the user should be able to bulk send messages to other users on the course.
        $bulkmessagecapability = has_capability('moodle/course:bulkmessaging', $this->page->context);

        // Extract rows from each part and collate them into one row each.
        $sessiondetailsleft = $reportdata->pageparams->sessiondetailspos == 'left';
        foreach ($userrows as $index => $row) {
            $summaryrow = isset($summaryrows[$index]->cells) ? $summaryrows[$index]->cells : array();
            $sessionrow = isset($sessionrows[$index]->cells) ? $sessionrows[$index]->cells : array();
            if ($sessiondetailsleft) {
                $row->cells = array_merge($row->cells, $sessionrow, $acronymrows[$index]->cells, $summaryrow);
            } else {
                $row->cells = array_merge($row->cells, $acronymrows[$index]->cells, $summaryrow, $sessionrow);
            }
            $table->data[] = $row;
        }

        if ($bulkmessagecapability) { // Require that the user can bulk message users.
            // Display check boxes that will allow the user to send a message to the students that have been checked.
            $output = html_writer::empty_tag('input', array('name' => 'sesskey', 'type' => 'hidden', 'value' => sesskey()));
            $output .= html_writer::empty_tag('input', array('name' => 'id', 'type' => 'hidden', 'value' => $COURSE->id));
            $output .= html_writer::empty_tag('input', array('name' => 'returnto', 'type' => 'hidden', 'value' => s(me())));
            $output .= html_writer::start_div('behaviourreporttable');
            $output .= html_writer::table($table).html_writer::tag('div', get_string('users').': '.count($reportdata->users));
            $output .= html_writer::end_div();
            $output .= html_writer::tag('div',
                    html_writer::empty_tag('input', array('type' => 'submit',
                                                                   'value' => get_string('messageselectadd'),
                                                                   'class' => 'btn btn-secondary')),
                    array('class' => 'buttons'));
            $url = new moodle_url('/mod/behaviour/messageselect.php');
            return html_writer::tag('form', $output, array('action' => $url->out(), 'method' => 'post'));
        } else {
            return html_writer::table($table).html_writer::tag('div', get_string('users').': '.count($reportdata->users));
        }
    }

    /**
     * Build and return the rows that will make up the left part of the behaviour report.
     * This consists of student names, as well as header cells for these columns.
     *
     * @param report_data $reportdata the report data
     * @return array Array of html_table_row objects
     */
    protected function get_user_rows(report_data $reportdata) {
        $rows = array();

        $bulkmessagecapability = has_capability('moodle/course:bulkmessaging', $this->page->context);
        $extrafields = \core_user\fields::for_identity($reportdata->att->context, true)->get_required_fields();
        $showextrauserdetails = $reportdata->pageparams->showextrauserdetails;
        $params = $reportdata->pageparams->get_significant_params();
        $text = get_string('users');
        if ($extrafields) {
            if ($showextrauserdetails) {
                $params['showextrauserdetails'] = 0;
                $url = $reportdata->att->url_report($params);
                $text .= $this->output->action_icon($url, new pix_icon('t/switch_minus',
                            get_string('hideextrauserdetails', 'behaviour')), null, null);
            } else {
                $params['showextrauserdetails'] = 1;
                $url = $reportdata->att->url_report($params);
                $text .= $this->output->action_icon($url, new pix_icon('t/switch_plus',
                            get_string('showextrauserdetails', 'behaviour')), null, null);
                $extrafields = array();
            }
        }
        $usercolspan = count($extrafields);

        $row = new html_table_row();
        $cell = $this->build_header_cell($text, false, false);
        $cell->attributes['class'] = $cell->attributes['class'] . ' headcol';
        $row->cells[] = $cell;
        if (!empty($usercolspan)) {
            $row->cells[] = $this->build_header_cell('', false, false, $usercolspan);
        }
        $rows[] = $row;

        $row = new html_table_row();
        $text = '';
        if ($bulkmessagecapability) {
            $text .= html_writer::checkbox('cb_selector', 0, false, '', array('id' => 'cb_selector'));
        }
        $text .= $this->construct_fullname_head($reportdata);
        $cell = $this->build_header_cell($text, false, false);
        $cell->attributes['class'] = $cell->attributes['class'] . ' headcol';
        $row->cells[] = $cell;

        foreach ($extrafields as $field) {
            $row->cells[] = $this->build_header_cell(\core_user\fields::get_display_name($field), false, false);
        }

        $rows[] = $row;

        foreach ($reportdata->users as $user) {
            $row = new html_table_row();
            $text = '';
            if ($bulkmessagecapability) {
                $text .= html_writer::checkbox('user'.$user->id, 'on', false, '', array('class' => 'behavioursesscheckbox'));
            }
            $text .= html_writer::link($reportdata->url_view(array('studentid' => $user->id)), fullname($user));
            $cell = $this->build_data_cell($text, false, false, null, null, false);
            $cell->attributes['class'] = $cell->attributes['class'] . ' headcol';
            $row->cells[] = $cell;

            foreach ($extrafields as $field) {
                $row->cells[] = $this->build_data_cell($user->$field, false, false);
            }
            $rows[] = $row;
        }

        $row = new html_table_row();
        $text = ($reportdata->pageparams->view == ATT_VIEW_SUMMARY) ? '' : get_string('summary');
        $cell = $this->build_data_cell($text, false, true);
        $cell->attributes['class'] = $cell->attributes['class'] . ' headcol';
        $row->cells[] = $cell;
        if (!empty($usercolspan)) {
            $row->cells[] = $this->build_header_cell('', false, false, $usercolspan);
        }
        $rows[] = $row;

        return $rows;
    }

    /**
     * Build and return the rows that will make up the summary part of the behaviour report.
     * This consists of countings for each status set acronyms, as well as header cells for these columns.
     *
     * @param report_data $reportdata the report data
     * @param boolean $startwithcontrast true if the first column must start with contrast (bgcolor)
     * @return array Array of html_table_row objects
     */
    protected function get_acronym_rows(report_data $reportdata, $startwithcontrast=false) {
        $rows = array();

        $summarycells = array();

        $row1 = new html_table_row();
        $row2 = new html_table_row();

        $setnumber = -1;
        $contrast = !$startwithcontrast;
        foreach ($reportdata->statuses as $sts) {
            if ($sts->setnumber != $setnumber) {
                $contrast = !$contrast;
                $setnumber = $sts->setnumber;
                $text = behaviour_get_setname($reportdata->att->id, $setnumber, false);
                $cell = $this->build_header_cell($text, $contrast);
                $row1->cells[] = $cell;
            }
            $cell->colspan++;
            $sts->contrast = $contrast;
            $row2->cells[] = $this->build_header_cell($sts->acronym, $contrast);
            $summarycells[] = $this->build_data_cell('', $contrast);
        }

        $rows[] = $row1;
        $rows[] = $row2;

        foreach ($reportdata->users as $user) {
            if ($reportdata->pageparams->view == ATT_VIEW_SUMMARY) {
                $usersummary = $reportdata->summary->get_all_sessions_summary_for($user->id);
            } else {
                $usersummary = $reportdata->summary->get_taken_sessions_summary_for($user->id);
            }

            $row = new html_table_row();
            foreach ($reportdata->statuses as $sts) {
                if (isset($usersummary->userstakensessionsbyacronym[$sts->setnumber][$sts->acronym])) {
                    $text = $usersummary->userstakensessionsbyacronym[$sts->setnumber][$sts->acronym];
                } else {
                    $text = 0;
                }
                $row->cells[] = $this->build_data_cell($text, $sts->contrast);
            }

            $rows[] = $row;
        }

        $rows[] = new html_table_row($summarycells);

        return $rows;
    }

    /**
     * Build and return the rows that will make up the summary part of the behaviour report.
     * This consists of counts and percentages for taken sessions (all sessions for summary report),
     * as well as header cells for these columns.
     *
     * @param report_data $reportdata the report data
     * @param boolean $startwithcontrast true if the first column must start with contrast (bgcolor)
     * @return array Array of html_table_row objects
     */
    protected function get_summary_rows(report_data $reportdata, $startwithcontrast=false) {
        $rows = array();

        $contrast = $startwithcontrast;
        $summarycells = array();

        $row1 = new html_table_row();
        $helpicon = $this->output->help_icon('oversessionstaken', 'behaviour');
        $row1->cells[] = $this->build_header_cell(get_string('oversessionstaken', 'behaviour') . $helpicon, $contrast, true, 3);

        $row2 = new html_table_row();
        $row2->cells[] = $this->build_header_cell(get_string('sessions', 'behaviour'), $contrast);
        $row2->cells[] = $this->build_header_cell(get_string('points', 'behaviour'), $contrast);
        $row2->cells[] = $this->build_header_cell(get_string('percentage', 'behaviour'), $contrast);
        $summarycells[] = $this->build_data_cell('', $contrast);
        $summarycells[] = $this->build_data_cell('', $contrast);
        $summarycells[] = $this->build_data_cell('', $contrast);

        if ($reportdata->pageparams->view == ATT_VIEW_SUMMARY) {
            $contrast = !$contrast;

            $helpicon = $this->output->help_icon('overallsessions', 'behaviour');
            $row1->cells[] = $this->build_header_cell(get_string('overallsessions', 'behaviour') . $helpicon, $contrast, true, 3);

            $row2->cells[] = $this->build_header_cell(get_string('sessions', 'behaviour'), $contrast);
            $row2->cells[] = $this->build_header_cell(get_string('points', 'behaviour'), $contrast);
            $row2->cells[] = $this->build_header_cell(get_string('percentage', 'behaviour'), $contrast);
            $summarycells[] = $this->build_data_cell('', $contrast);
            $summarycells[] = $this->build_data_cell('', $contrast);
            $summarycells[] = $this->build_data_cell('', $contrast);

            $contrast = !$contrast;
            $helpicon = $this->output->help_icon('maxpossible', 'behaviour');
            $row1->cells[] = $this->build_header_cell(get_string('maxpossible', 'behaviour') . $helpicon, $contrast, true, 2);

            $row2->cells[] = $this->build_header_cell(get_string('points', 'behaviour'), $contrast);
            $row2->cells[] = $this->build_header_cell(get_string('percentage', 'behaviour'), $contrast);
            $summarycells[] = $this->build_data_cell('', $contrast);
            $summarycells[] = $this->build_data_cell('', $contrast);
        }

        $rows[] = $row1;
        $rows[] = $row2;

        foreach ($reportdata->users as $user) {
            if ($reportdata->pageparams->view == ATT_VIEW_SUMMARY) {
                $usersummary = $reportdata->summary->get_all_sessions_summary_for($user->id);
            } else {
                $usersummary = $reportdata->summary->get_taken_sessions_summary_for($user->id);
            }

            $contrast = $startwithcontrast;
            $row = new html_table_row();
            $row->cells[] = $this->build_data_cell($usersummary->numtakensessions, $contrast);
            $row->cells[] = $this->build_data_cell($usersummary->pointssessionscompleted, $contrast);
            $row->cells[] = $this->build_data_cell(format_float($usersummary->takensessionspercentage * 100) . '%', $contrast);

            if ($reportdata->pageparams->view == ATT_VIEW_SUMMARY) {
                $contrast = !$contrast;
                $row->cells[] = $this->build_data_cell($usersummary->numallsessions, $contrast);
                $text = $usersummary->pointsallsessions;
                $row->cells[] = $this->build_data_cell($text, $contrast);
                $row->cells[] = $this->build_data_cell($usersummary->allsessionspercentage, $contrast);

                $contrast = !$contrast;
                $text = $usersummary->maxpossiblepoints;
                $row->cells[] = $this->build_data_cell($text, $contrast);
                $row->cells[] = $this->build_data_cell($usersummary->maxpossiblepercentage, $contrast);
            }

            $rows[] = $row;
        }

        $rows[] = new html_table_row($summarycells);

        return $rows;
    }

    /**
     * Build and return the rows that will make up the behaviour report.
     * This consists of details for each selected session, as well as header and summary cells for these columns.
     *
     * @param report_data $reportdata the report data
     * @param boolean $startwithcontrast true if the first column must start with contrast (bgcolor)
     * @return array Array of html_table_row objects
     */
    protected function get_session_rows(report_data $reportdata, $startwithcontrast=false) {

        $rows = array();

        $row = new html_table_row();

        $showsessiondetails = $reportdata->pageparams->showsessiondetails;
        $text = get_string('sessions', 'behaviour');
        $params = $reportdata->pageparams->get_significant_params();
        if (count($reportdata->sessions) > 1) {
            if ($showsessiondetails) {
                $params['showsessiondetails'] = 0;
                $url = $reportdata->att->url_report($params);
                $text .= $this->output->action_icon($url, new pix_icon('t/switch_minus',
                            get_string('hidensessiondetails', 'behaviour')), null, null);
                $colspan = count($reportdata->sessions);
            } else {
                $params['showsessiondetails'] = 1;
                $url = $reportdata->att->url_report($params);
                $text .= $this->output->action_icon($url, new pix_icon('t/switch_plus',
                            get_string('showsessiondetails', 'behaviour')), null, null);
                $colspan = 1;
            }
        } else {
            $colspan = 1;
        }

        $params = $reportdata->pageparams->get_significant_params();
        if ($reportdata->pageparams->sessiondetailspos == 'left') {
            $params['sessiondetailspos'] = 'right';
            $url = $reportdata->att->url_report($params);
            $text .= $this->output->action_icon($url, new pix_icon('t/right', get_string('moveright', 'behaviour')),
                null, null);
        } else {
            $params['sessiondetailspos'] = 'left';
            $url = $reportdata->att->url_report($params);
            $text = $this->output->action_icon($url, new pix_icon('t/left', get_string('moveleft', 'behaviour')),
                    null, null) . $text;
        }

        $row->cells[] = $this->build_header_cell($text, '', true, $colspan);
        $rows[] = $row;

        $row = new html_table_row();
        if ($showsessiondetails && !empty($reportdata->sessions)) {
            foreach ($reportdata->sessions as $sess) {
                $sesstext = userdate($sess->sessdate, get_string('strftimedm', 'behaviour'));
                $sesstext .= html_writer::empty_tag('br');
                $sesstext .= behaviour_strftimehm($sess->sessdate);
                $capabilities = array(
                    'mod/behaviour:takebehaviours',
                    'mod/behaviour:changebehaviours'
                );
                if (is_null($sess->lasttaken) && has_any_capability($capabilities, $reportdata->att->context)) {
                    $sesstext = html_writer::link($reportdata->url_take($sess->id, $sess->groupid), $sesstext,
                        array('class' => 'behaviourreporttakelink'));
                }
                $sesstext .= html_writer::empty_tag('br', array('class' => 'behaviourreportseparator'));
                if (!empty($sess->description) &&
                    !empty(get_config('behaviour', 'showsessiondescriptiononreport'))) {
                    $sesstext .= html_writer::tag('small', format_text($sess->description),
                        array('class' => 'behaviourreportcommon'));
                }
                if ($sess->groupid) {
                    if (empty($reportdata->groups[$sess->groupid])) {
                        $sesstext .= html_writer::tag('small', get_string('deletedgroup', 'behaviour'),
                            array('class' => 'behaviourreportgroup'));
                    } else {
                        $sesstext .= html_writer::tag('small', $reportdata->groups[$sess->groupid]->name,
                            array('class' => 'behaviourreportgroup'));
                    }

                } else {
                    $sesstext .= html_writer::tag('small', get_string('commonsession', 'behaviour'),
                        array('class' => 'behaviourreportcommon'));
                }

                $row->cells[] = $this->build_header_cell($sesstext, false, true, null, null, false);
            }
        } else {
            $row->cells[] = $this->build_header_cell('');
        }
        $rows[] = $row;

        foreach ($reportdata->users as $user) {
            $row = new html_table_row();
            if ($showsessiondetails && !empty($reportdata->sessions)) {
                $cellsgenerator = new behaviour_user_sessions_cells_html_generator($reportdata, $user);
                foreach ($cellsgenerator->get_cells(true) as $cell) {
                    if ($cell instanceof html_table_cell) {
                        $cell->attributes['class'] .= ' center';
                        $row->cells[] = $cell;
                    } else {
                        $row->cells[] = $this->build_data_cell($cell);
                    }
                }
            } else {
                $row->cells[] = $this->build_data_cell('');
            }
            $rows[] = $row;
        }

        $row = new html_table_row();
        if ($showsessiondetails && !empty($reportdata->sessions)) {
            foreach ($reportdata->sessions as $sess) {
                $sessionstats = array();
                foreach ($reportdata->statuses as $status) {
                    if ($status->setnumber == $sess->statusset) {
                        $status->count = 0;
                        $sessionstats[$status->id] = $status;
                    }
                }

                foreach ($reportdata->users as $user) {
                    if (!empty($reportdata->sessionslog[$user->id][$sess->id])) {
                        $statusid = $reportdata->sessionslog[$user->id][$sess->id]->statusid;
                        if (isset($sessionstats[$statusid]->count)) {
                            $sessionstats[$statusid]->count++;
                        }
                    }
                }

                $statsoutput = '';
                foreach ($sessionstats as $status) {
                    $statsoutput .= "$status->description: {$status->count}<br/>";
                }
                $row->cells[] = $this->build_data_cell($statsoutput);
            }
        } else {
            $row->cells[] = $this->build_header_cell('');
        }
        $rows[] = $row;

        return $rows;
    }

    /**
     * Build and return a html_table_cell for header rows
     *
     * @param html_table_cell|string $cell the cell or a label for a cell
     * @param boolean $contrast true menans the cell must be shown with bgcolor contrast
     * @param boolean $center true means the cell text should be centered. Othersiwe it should be left-aligned.
     * @param int $colspan how many columns should cell spans
     * @param int $rowspan how many rows should cell spans
     * @param boolean $nowrap true means the cell text must be shown with nowrap option
     * @return html_table_cell a html table cell
     */
    protected function build_header_cell($cell, $contrast=false, $center=true, $colspan=null, $rowspan=null, $nowrap=true) {
        $classes = array('header', 'bottom');
        if ($center) {
            $classes[] = 'center';
            $classes[] = 'narrow';
        } else {
            $classes[] = 'left';
        }
        if ($contrast) {
            $classes[] = 'contrast';
        }
        if ($nowrap) {
            $classes[] = 'nowrap';
        }
        return $this->build_cell($cell, $classes, $colspan, $rowspan, true);
    }

    /**
     * Build and return a html_table_cell for data rows
     *
     * @param html_table_cell|string $cell the cell or a label for a cell
     * @param boolean $contrast true menans the cell must be shown with bgcolor contrast
     * @param boolean $center true means the cell text should be centered. Othersiwe it should be left-aligned.
     * @param int $colspan how many columns should cell spans
     * @param int $rowspan how many rows should cell spans
     * @param boolean $nowrap true means the cell text must be shown with nowrap option
     * @return html_table_cell a html table cell
     */
    protected function build_data_cell($cell, $contrast=false, $center=true, $colspan=null, $rowspan=null, $nowrap=true) {
        $classes = array();
        if ($center) {
            $classes[] = 'center';
            $classes[] = 'narrow';
        } else {
            $classes[] = 'left';
        }
        if ($nowrap) {
            $classes[] = 'nowrap';
        }
        if ($contrast) {
            $classes[] = 'contrast';
        }
        return $this->build_cell($cell, $classes, $colspan, $rowspan, false);
    }

    /**
     * Build and return a html_table_cell for header or data rows
     *
     * @param html_table_cell|string $cell the cell or a label for a cell
     * @param Array $classes a list of css classes
     * @param int $colspan how many columns should cell spans
     * @param int $rowspan how many rows should cell spans
     * @param boolean $header true if this should be a header cell
     * @return html_table_cell a html table cell
     */
    protected function build_cell($cell, $classes, $colspan=null, $rowspan=null, $header=false) {
        if (!($cell instanceof html_table_cell)) {
            $cell = new html_table_cell($cell);
        }
        $cell->header = $header;
        $cell->scope = 'col';

        if (!empty($colspan) && $colspan > 1) {
            $cell->colspan = $colspan;
        }

        if (!empty($rowspan) && $rowspan > 1) {
            $cell->rowspan = $rowspan;
        }

        if (!empty($classes)) {
            $classes = implode(' ', $classes);
            if (empty($cell->attributes['class'])) {
                $cell->attributes['class'] = $classes;
            } else {
                $cell->attributes['class'] .= ' ' . $classes;
            }
        }

        return $cell;
    }

    /**
     * Output the status set selector.
     *
     * @param set_selector $sel
     * @return string
     */
    protected function render_set_selector(set_selector $sel) {
        $current = $sel->get_current_statusset();
        $selected = null;
        $opts = array();
        for ($i = 0; $i <= $sel->maxstatusset; $i++) {
            $url = $sel->url($i);
            $display = $sel->get_status_name($i);
            $opts[$url->out(false)] = $display;
            if ($i == $current) {
                $selected = $url->out(false);
            }
        }
        $newurl = $sel->url($sel->maxstatusset + 1);
        $opts[$newurl->out(false)] = get_string('newstatusset', 'mod_behaviour');
        if ($current == $sel->maxstatusset + 1) {
            $selected = $newurl->out(false);
        }

        return $this->output->url_select($opts, $selected, null);
    }

    /**
     * Render preferences data.
     *
     * @param stdClass $prefdata
     * @return string
     */
    protected function render_preferences_data($prefdata) {
        $this->page->requires->js('/mod/behaviour/module.js');

        $table = new html_table();
        $table->width = '100%';
        $table->head = array('#',
                             get_string('acronym', 'behaviour'),
                             get_string('description'),
                             get_string('points', 'behaviour'));
        $table->align = array('center', 'center', 'center', 'center', 'center', 'center');

        $table->head[] = get_string('studentavailability', 'behaviour').
            $this->output->help_icon('studentavailability', 'behaviour');
        $table->align[] = 'center';

        $table->head[] = get_string('setunmarked', 'behaviour').
            $this->output->help_icon('setunmarked', 'behaviour');
        $table->align[] = 'center';

        $table->head[] = get_string('action');

        $i = 1;
        foreach ($prefdata->statuses as $st) {
            $emptyacronym = '';
            $emptydescription = '';
            if (isset($prefdata->errors[$st->id]) && !empty(($prefdata->errors[$st->id]))) {
                if (empty($prefdata->errors[$st->id]['acronym'])) {
                    $emptyacronym = $this->construct_notice(get_string('emptyacronym', 'mod_behaviour'), 'notifyproblem');
                }
                if (empty($prefdata->errors[$st->id]['description'])) {
                    $emptydescription = $this->construct_notice(get_string('emptydescription', 'mod_behaviour') , 'notifyproblem');
                }
            }
            $cells = array();
            $cells[] = $i;
            $cells[] = $this->construct_text_input('acronym['.$st->id.']', 2, 2, $st->acronym) . $emptyacronym;
            $cells[] = $this->construct_text_input('description['.$st->id.']', 30, 30, $st->description) .
                                 $emptydescription;
            $cells[] = $this->construct_text_input('grade['.$st->id.']', 4, 4, $st->grade);
            $checked = '';
            if ($st->setunmarked) {
                $checked = ' checked ';
            }
            $cells[] = $this->construct_text_input('studentavailability['.$st->id.']', 4, 5, $st->studentavailability);
            $cells[] = '<input type="radio" name="setunmarked" value="'.$st->id.'"'.$checked.'>';

            $cells[] = $this->construct_preferences_actions_icons($st, $prefdata);

            $table->data[$i] = new html_table_row($cells);
            $table->data[$i]->id = "statusrow".$i;
            $i++;
        }
        $cells = [];
        $cells[] = '*';
        $cells[] = $this->construct_text_input('newacronym', 2, 2);
        $cells[] = $this->construct_text_input('newdescription', 30, 30);
        $cells[] = $this->construct_text_input('newgrade', 4, 4);
        $cells[] = $this->construct_text_input('newstudentavailability', 4, 5);

        $cells[] = $this->construct_preferences_button(get_string('add', 'behaviour'),
            mod_behaviour_preferences_page_params::ACTION_ADD);

        $table->data[$i] = new html_table_row($cells);
        $table->data[$i]->id = "statuslastrow";

        $o = html_writer::table($table);
        $o .= html_writer::input_hidden_params($prefdata->url(array(), false));
        // We should probably rewrite this to use mforms but for now add sesskey.
        $o .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()))."\n";

        $o .= $this->construct_preferences_button(get_string('update', 'behaviour'),
                                                  mod_behaviour_preferences_page_params::ACTION_SAVE);
        $o = html_writer::tag('form', $o, array('id' => 'preferencesform', 'method' => 'post',
                                                'action' => $prefdata->url(array(), false)->out_omit_querystring()));
        $o = $this->output->container($o, 'generalbox attwidth');

        return $o;
    }

    /**
     * Render default statusset.
     *
     * @param default_statusset $prefdata
     * @return string
     */
    protected function render_default_statusset(default_statusset $prefdata) {
        return $this->render_preferences_data($prefdata);
    }

    /**
     * Render preferences data.
     *
     * @param stdClass $prefdata
     * @return string
     */
    protected function render_behaviour_pref($prefdata) {

    }

    /**
     * Construct text input.
     *
     * @param string $name
     * @param integer $size
     * @param integer $maxlength
     * @param string $value
     * @return string
     */
    private function construct_text_input($name, $size, $maxlength, $value='') {
        $attributes = array(
                'type'      => 'text',
                'name'      => $name,
                'size'      => $size,
                'maxlength' => $maxlength,
                'value'     => $value,
                'class' => 'form-control');
        return html_writer::empty_tag('input', $attributes);
    }

    /**
     * Construct action icons.
     *
     * @param stdClass $st
     * @param stdClass $prefdata
     * @return string
     */
    private function construct_preferences_actions_icons($st, $prefdata) {
        $params = array('sesskey' => sesskey(),
                        'statusid' => $st->id);
        if ($st->visible) {
            $params['action'] = mod_behaviour_preferences_page_params::ACTION_HIDE;
            $showhideicon = $this->output->action_icon(
                    $prefdata->url($params),
                    new pix_icon("t/hide", get_string('hide')));
        } else {
            $params['action'] = mod_behaviour_preferences_page_params::ACTION_SHOW;
            $showhideicon = $this->output->action_icon(
                    $prefdata->url($params),
                    new pix_icon("t/show", get_string('show')));
        }
        if (empty($st->haslogs)) {
            $params['action'] = mod_behaviour_preferences_page_params::ACTION_DELETE;
            $deleteicon = $this->output->action_icon(
                    $prefdata->url($params),
                    new pix_icon("t/delete", get_string('delete')));
        } else {
            $deleteicon = '';
        }

        return $showhideicon . $deleteicon;
    }

    /**
     * Construct preferences button.
     *
     * @param string $text
     * @param string $action
     * @return string
     */
    private function construct_preferences_button($text, $action) {
        $attributes = array(
                'type'      => 'submit',
                'value'     => $text,
                'class'     => 'btn btn-secondary',
                'onclick'   => 'M.mod_behaviour.set_preferences_action('.$action.')');
        return html_writer::empty_tag('input', $attributes);
    }

    /**
     * Construct a notice message
     *
     * @param string $text
     * @param string $class
     * @return string
     */
    private function construct_notice($text, $class = 'notifymessage') {
        $attributes = array('class' => $class);
        return html_writer::tag('p', $text, $attributes);
    }

    /**
     * Show different picture if it is a temporary user.
     *
     * @param stdClass $user
     * @param array $opts
     * @return string
     */
    protected function user_picture($user, array $opts = null) {
        if ($user->type == 'temporary') {
            $attrib = array(
                'width' => '35',
                'height' => '35',
                'class' => 'userpicture defaultuserpic',
            );
            if (isset($opts['size'])) {
                $attrib['width'] = $attrib['height'] = $opts['size'];
            }
            return $this->output->pix_icon('ghost', '', 'mod_behaviour', $attrib);
        }

        return $this->output->user_picture($user, $opts);
    }
}
