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
 * Contains class used to prepare a verification result for display.
 *
 * @package   mod_customcert
 * @copyright 2017 Mark Nelson <markn@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_customcert\output;

defined('MOODLE_INTERNAL') || die();

use renderable;
use templatable;

define('BLOCK_DEDICATION_IGNORE_SESSION_TIME',1800);
define('BLOCK_DEDICATION_DEFAULT_SESSION_LIMIT',1800);
/**
 * Generate dedication reports based in passed params.
 */
class block_dedication_manager {

    protected $course;
    protected $mintime;
    protected $maxtime;
    protected $limit;

    public function __construct($course, $mintime, $maxtime, $limit) {
        $this->course = $course;
        $this->mintime = $mintime;
        $this->maxtime = $maxtime;
        $this->limit = $limit;
    }

    public function get_students_dedication($students) {
        global $DB;

        $rows = array();

        $where = 'courseid = :courseid AND userid = :userid AND timecreated >= :mintime AND timecreated <= :maxtime';
        $params = array(
                'courseid' => $this->course->id,
                'userid' => 0,
                'mintime' => $this->mintime,
                'maxtime' => $this->maxtime
        );

        $perioddays = ($this->maxtime - $this->mintime) / DAYSECS;

        foreach ($students as $user) {
            $daysconnected = array();
            $params['userid'] = $user->id;
            $logs = block_dedication_utils::get_events_select($where, $params);

            if ($logs) {
                $previouslog = array_shift($logs);
                $previouslogtime = $previouslog->time;
                $sessionstart = $previouslog->time;
                $dedication = 0;
                $daysconnected[date('Y-m-d', $previouslog->time)] = 1;

                foreach ($logs as $log) {
                    if (($log->time - $previouslogtime) > $this->limit) {
                        $dedication += $previouslogtime - $sessionstart;
                        $sessionstart = $log->time;
                    }
                    $previouslogtime = $log->time;
                    $daysconnected[date('Y-m-d', $log->time)] = 1;
                }
                $dedication += $previouslogtime - $sessionstart;
            } else {
                $dedication = 0;
            }
            $groups = groups_get_user_groups($this->course->id, $user->id);
            $group = !empty($groups) && !empty($groups[0]) ? $groups[0][0] : 0;
            $rows[] = (object) array(
                    'user' => $user,
                    'groupid' => $group,
                    'dedicationtime' => $dedication,
                    'connectionratio' => round(count($daysconnected) / $perioddays, 2),
            );
        }

        return $rows;
    }



    public function get_user_dedication($user, $simple = false) {
        $where = 'courseid = :courseid AND userid = :userid AND timecreated >= :mintime AND timecreated <= :maxtime';
        $params = array(
                'courseid' => $this->course->id,
                'userid' => $user->id,
                'mintime' => $this->mintime,
                'maxtime' => $this->maxtime
        );
        $logs = block_dedication_utils::get_events_select($where, $params);

        if ($simple) {
            // Return total dedication time in seconds.
            $total = 0;

            if ($logs) {
                $previouslog = array_shift($logs);
                $previouslogtime = $previouslog->time;
                $sessionstart = $previouslogtime;

                foreach ($logs as $log) {
                    if (($log->time - $previouslogtime) > $this->limit) {
                        $dedication = $previouslogtime - $sessionstart;
                        $total += $dedication;
                        $sessionstart = $log->time;
                    }
                    $previouslogtime = $log->time;
                }
                $dedication = $previouslogtime - $sessionstart;
                $total += $dedication;
            }

            return $total;

        } else {
            // Return user sessions with details.
            $rows = array();

            if ($logs) {
                $previouslog = array_shift($logs);
                $previouslogtime = $previouslog->time;
                $sessionstart = $previouslogtime;
                $ips = array($previouslog->ip => true);

                foreach ($logs as $log) {
                    if (($log->time - $previouslogtime) > $this->limit) {
                        $dedication = $previouslogtime - $sessionstart;

                        // Ignore sessions with a really short duration.
                        if ($dedication > BLOCK_DEDICATION_IGNORE_SESSION_TIME) {
                            $rows[] = (object) array('start_date' => $sessionstart, 'dedicationtime' => $dedication, 'ips' => array_keys($ips));
                            $ips = array();
                        }
                        $sessionstart = $log->time;
                    }
                    $previouslogtime = $log->time;
                    $ips[$log->ip] = true;
                }

                $dedication = $previouslogtime - $sessionstart;

                // Ignore sessions with a really short duration.
                if ($dedication > BLOCK_DEDICATION_IGNORE_SESSION_TIME) {
                    $rows[] = (object) array('start_date' => $sessionstart, 'dedicationtime' => $dedication, 'ips' => array_keys($ips));
                }
            }
            return $rows;
        }
    }

    /**
     * Downloads user dedication with passed data.
     * @param $user
     * @return MoodleExcelWorkbook
     */


}

/**
 * Utils functions used by block dedication.
 */
class block_dedication_utils {

    public static $logstores = array('logstore_standard', 'logstore_legacy');

    /**
     * Return formatted events from logstores.
     * @param string $selectwhere
     * @param array $params
     * @return array
     */
    public static function get_events_select($selectwhere, array $params) {
        $return = array();

        static $allreaders = null;

        if (is_null($allreaders)) {
            $allreaders = get_log_manager()->get_readers();
        }

        $processedreaders = 0;

        foreach (self::$logstores as $name) {
            if (isset($allreaders[$name])) {
                $reader = $allreaders[$name];
                $events = $reader->get_events_select($selectwhere, $params, 'timecreated ASC', 0, 0);
                foreach ($events as $event) {
                    // Note: see \core\event\base to view base class of event.
                    $obj = new \stdClass();
                    $obj->time = $event->timecreated;
                    $obj->ip = $event->get_logextra()['ip'];
                    $return[] = $obj;
                }
                if (!empty($events)) {
                    $processedreaders++;
                }
            }
        }

        // Sort mixed array by time ascending again only when more of a reader has added events to return array.
        if ($processedreaders > 1) {
            usort($return, function($a, $b) {
                return $a->time > $b->time;
            });
        }

        return $return;
    }

    /**
     * Formats time based in Moodle function format_time($totalsecs).
     * @param int $totalsecs
     * @return string
     */
    public static function format_dedication($totalsecs) {
        $totalsecs = abs($totalsecs);

        $str = new \stdClass();
        $str->hour = get_string('hour');
        $str->hours = get_string('hours');
        $str->min = get_string('min');
        $str->mins = get_string('mins');
        $str->sec = get_string('sec');
        $str->secs = get_string('secs');

        $hours = floor($totalsecs / HOURSECS);
        $remainder = $totalsecs - ($hours * HOURSECS);
        $mins = floor($remainder / MINSECS);
        $secs = round($remainder - ($mins * MINSECS), 2);
        $ss = ($secs == 1) ? $str->sec : $str->secs;
        $sm = ($mins == 1) ? $str->min : $str->mins;
        $sh = ($hours == 1) ? $str->hour : $str->hours;

        $ohours = '';
        $omins = '';
        $osecs = '';

        if ($hours) {
            $ohours = $hours . ' ' . $sh;
        }
        if ($mins) {
            $omins = $mins . ' ' . $sm;
        }
        if ($secs) {
            $osecs = $secs . ' ' . $ss;
        }

        if ($hours) {
            return trim($ohours . ' ' . $omins);
        }
        if ($mins) {
            return trim($omins . ' ' . $osecs);
        }
        if ($secs) {
            return $osecs;
        }
        return get_string('none');
    }

    /**
     * @param string[] $ips
     * @return string
     */
    public static function format_ips($ips) {
        return implode(', ', array_map('block_dedication_utils::link_ip', $ips));
    }

    /**
     * Generates a linkable ip.
     * @param string $ip
     * @return string
     */
    public static function link_ip($ip) {
        return html_writer::link("http://en.utrace.de/?query=$ip", $ip, array('target' => '_blank'));
    }

    /**
     * Return table styles based on current theme.
     * @return array
     */
    public static function get_table_styles() {
        global $PAGE;

        // Twitter Bootstrap styling.
        $is_bootstrap_theme = ($PAGE->theme->name === 'boost') || count(array_intersect(array('boost', 'bootstrapbase'), $PAGE->theme->parents)) > 0;
        if ($is_bootstrap_theme) {
            $styles = array(
                    'table_class' => 'table table-bordered table-hover table-sm table-condensed table-dedication',
                    'header_style' => 'background-color: #333; color: #fff;'
            );
        } else {
            $styles = array(
                    'table_class' => 'table-dedication',
                    'header_style' => ''
            );
        }

        return $styles;
    }

    /**
     * Generates generic Excel file for download.
     * @param string $downloadname
     * @param array $rows
     * @return MoodleExcelWorkbook
     * @throws coding_exception
     */
    public static function generate_download($downloadname, $rows) {
        global $CFG;

        require_once($CFG->libdir . '/excellib.class.php');

        $workbook = new MoodleExcelWorkbook(clean_filename($downloadname));

        $myxls = $workbook->add_worksheet(get_string('pluginname', 'block_dedication'));

        $rowcount = 0;
        foreach ($rows as $row) {
            foreach ($row as $index => $content) {
                $myxls->write($rowcount, $index, $content);
            }
            $rowcount++;
        }

        $workbook->close();

        return $workbook;
    }
}

/**
 * Class to prepare a verification result for display.
 *
 * @package   mod_customcert
 * @copyright 2017 Mark Nelson <markn@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class verify_certificate_result implements templatable, renderable {

    /**
     * @var string The URL to the user's profile.
     */
    public $userprofileurl;

    /**
     * @var string The user's fullname.
     */
    public $userfullname;

    /**
     * @var string The URL to the course page.
     */
    public $courseurl;

    /**
     * @var string The course's fullname.
     */
    public $coursefullname;

    /**
     * @var string The certificate's name.
     */
    public $certificatename;
	public $issuedate;

    /**
     * @var string The course summary .
     */
    public $summary;

    /**
     * @var string This Picture is User Picture .
     */
    public $picture;

    /**
     * @var string course Issuses Code .
     */
    public $code;



    const BARCODETYPE = 'QRCODE';

    /**
     * Constructor.
     *
     * @param \stdClass $result
     */
    public function __construct($result) {
		global $DB;
        $this->cm = $cm = get_coursemodule_from_instance('customcert', $result->certificateid);
        $context = \context_module::instance($cm->id);
		$issue = $DB->get_record('customcert_issues', array('userid' => $result->userid, 'customcertid' => $result->certificateid));
		$this->userprofileurl = new \moodle_url('/user/view.php', array('id' => $result->userid,'course' => $result->courseid));
        $this->userfullname = fullname($result);
        $this->email = $result->email;
        $this->country = $result->country;
        $this->courseurl = new \moodle_url('/course/view.php', array('id' => $result->courseid));
        $this->coursefullname = format_string($result->coursefullname, true, ['context' => $context]);
        $this->summary = format_string($result->summary, true, ['context' => $context]);
        $this->picture = format_string($result->picture, true, ['context' => $context]);
        $this->certificatename = format_string($result->certificatename, true, ['context' => $context]);
        $this->issuedate = userdate($issue->timecreated);
        $this->code = format_string($result->code,true,['context' => $context]);
    }

    /**
     * Function to export the renderer data in a format that is suitable for a mustache template.
     *
     * @param \renderer_base $output Used to do a final render of any components that need to be rendered for export.
     * @return \stdClass|array
     */
    public function export_for_template(\renderer_base $output) {
        global $CFG, $DB;
        require_once($CFG->libdir . '/tcpdf/tcpdf_barcodes_2d.php');
        require_once($CFG->dirroot . '/grade/querylib.php');

        $userid = $this->userprofileurl->param('id');
        $courseid = $this->courseurl->param('id');
        $user = \core_user::get_user($userid);
        $result = new \stdClass();
        $result->userprofileurl = $this->userprofileurl;
        $result->userfullname = $this->userfullname;
        $result->coursefullname = $this->coursefullname;
        $result->summary = $this->summary;
        $result->userpicture = $output->user_picture($user, array('class' => 'userpicture','size' => 175));
        $result->courseurl = $this->courseurl;
        $result->certificatename = $this->certificatename;
		$result->issuedate = $this->issuedate;
		$result->code = $this->code;
		$result->email = $this->email;
		$result->country = get_string_manager()->get_list_of_countries(true)[$this->country];

        $result->downloadlink = \html_writer::tag('div', \html_writer::link(
            new \moodle_url('/mod/customcert/view.php', ['id' => $this->cm->id, 'getissue' => $user->id]),
            get_string('downloadlink', 'customcert')),
            array('class' => 'btn btn-primary downloadbutton'));

        $mod = get_fast_modinfo($courseid);
        $course =  get_course($courseid);

        $cms = $mod->cms;
        $result->modules = [];
		$customcertmodid = $DB->get_record('modules',array('name' => 'customcert'));
        foreach ($cms as $module){
            if($module->visible && !$module->deletioninprogress && $module->module != $customcertmodid->id){
                $result->modules[] = ['index' => count($result->modules) + 1,'name' => $module->name];
            }
        }

        $qrcodeurl = new \moodle_url('/mod/customcert/verify_certificate.php',['code'=>$this->code]);
        $barcode = new \TCPDF2DBarcode($qrcodeurl->out(false), self::BARCODETYPE);
        $result->qrcodehtml = $barcode->getBarcodeHTML(3, 3);

        $gradedata = grade_get_course_grade($userid,$courseid);
        $result->maxgrade  = round($gradedata->item->grademax);
        $grade = $gradedata->grade;
        if(!empty($grade)){
            $result->grade = round($grade,2);
        }else{
            $result->grade = 0;
        }

        $mintime = optional_param('mintime', $course->startdate, PARAM_INT);
        $maxtime = optional_param('maxtime', time(), PARAM_INT);
        $limit = optional_param('limit', BLOCK_DEDICATION_DEFAULT_SESSION_LIMIT, PARAM_INT);

        $dm = new block_dedication_manager($course, $mintime, $maxtime, $limit);
        $user = (object) [ 'id' => $userid];
        $datarow = $dm->get_user_dedication($user, true);

        $value =  block_dedication_utils::format_dedication($datarow);
        $result->duration = $value;

        $ccdate = $DB->get_record('course_completions', ['course' => $courseid, 'userid' => $userid]);
        $enable = $DB->get_field('local_recompletion_config','value', ['name' => 'enable', 'course' => $courseid]);

        if (!empty($ccdate->timestarted)) {
            $result->startdate = userdate($ccdate->timestarted, get_string('strftimedate', 'langconfig'));
        }

        if (!empty($ccdate) && !empty($ccdate->timecompleted)) {
            $result->ccdate = userdate($ccdate->timecompleted, get_string('strftimedate', 'langconfig'));
        }

        if (!empty($enable)) {
            $recompletions = $DB->get_records('local_recompletion_cc',['course' => $courseid, 'userid' => $userid]);
            foreach ($recompletions as $recompletion) {
                if (!empty($recompletion->timecompleted)) {
                    $recompletiondates[] = userdate($recompletion->timecompleted, get_string('strftimedate', 'langconfig'));
                }
            }
            if (!empty($recompletiondates)) {
                $result->olddate = join(', ', $recompletiondates);
            }
        }

        if (!empty($result->ccdate) && !empty($enable)) {
            $recompletionduration = $DB->get_field('local_recompletion_config','value', ['name' => 'recompletionduration', 'course' => $courseid]);
            $result->recompletiondate = userdate($ccdate->timecompleted + $recompletionduration, get_string('strftimedate', 'langconfig'));
        }

        return $result;
    }
}
