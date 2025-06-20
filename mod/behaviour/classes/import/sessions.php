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
 * Import behaviour sessions class.
 *
 * @package   mod_behaviour
 * @author Chris Wharton <chriswharton@catalyst.net.nz>
 * @copyright 2017 Catalyst IT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_behaviour\import;

use csv_import_reader;
use mod_behaviour_notifyqueue;
use mod_behaviour_structure;
use stdClass;

/**
 * Import behaviour sessions.
 *
 * @package mod_behaviour
 * @author Chris Wharton <chriswharton@catalyst.net.nz>
 * @copyright 2017 Catalyst IT
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sessions {

    /** @var string $error The errors message from reading the xml */
    protected $error = '';

    /** @var array $sessions The sessions info */
    protected $sessions = array();

    /** @var array $mappings The mappings info */
    protected $mappings = array();

    /** @var int The id of the csv import */
    protected $importid = 0;

    /** @var csv_import_reader|null  $importer */
    protected $importer = null;

    /** @var array $foundheaders */
    protected $foundheaders = array();

    /** @var bool $useprogressbar Control whether importing should use progress bars or not. */
    protected $useprogressbar = false;

    /** @var \core\progress\display_if_slow|null $progress The progress bar instance. */
    protected $progress = null;

    /** @var bool $courseprovided If course has been provided we don't need to map the course field*/
    protected $courseprovided = false;

    /**
     * Store an error message for display later
     *
     * @param string $msg
     */
    public function fail($msg) {
        $this->error = $msg;
        return false;
    }

    /**
     * Get the CSV import id
     *
     * @return string The import id.
     */
    public function get_importid() {
        return $this->importid;
    }

    /**
     * Get the list of headers required for import.
     *
     * @return array The headers (lang strings)
     */
    public function list_required_headers() {
        $headers = [];

        // If we don't have the "courseprovided" then include the courseshortname header.
        if (!$this->courseprovided) {
            $headers[] = get_string('courseshortname', 'behaviour');
        }

        $headers[] = get_string('groups', 'behaviour');
        $headers[] = get_string('sessiondate', 'behaviour');
        $headers[] = get_string('from', 'behaviour');
        $headers[] = get_string('to', 'behaviour');
        $headers[] = get_string('description', 'behaviour');
        $headers[] = get_string('repeaton', 'behaviour');
        $headers[] = get_string('repeatevery', 'behaviour');
        $headers[] = get_string('repeatuntil', 'behaviour');
        $headers[] = get_string('studentscanmark', 'behaviour');
        $headers[] = get_string('passwordgrp', 'behaviour');
        $headers[] = get_string('randompassword', 'behaviour');
        $headers[] = get_string('subnet', 'behaviour');
        $headers[] = get_string('automark', 'behaviour');
        $headers[] = get_string('autoassignstatus', 'behaviour');
        $headers[] = get_string('absenteereport', 'behaviour');
        $headers[] = get_string('preventsharedip', 'behaviour');
        $headers[] = get_string('preventsharediptime', 'behaviour');
        $headers[] = get_string('calendarevent', 'behaviour');
        $headers[] = get_string('includeqrcode', 'behaviour');
        $headers[] = get_string('rotateqrcode', 'behaviour');
        $headers[] = get_string('studentsearlyopentime', 'behaviour');

        return $headers;
    }

    /**
     * Get the list of headers found in the import.
     *
     * @return array The found headers (names from import)
     */
    public function list_found_headers() {
        return $this->foundheaders;
    }

    /**
     * Read the data from the mapping form.
     *
     * @param object $data The mapping data.
     */
    protected function read_mapping_data($data) {

        $headerkeys = [];
        // If we have don't have "courseprovided" then the mapping data is increased by one to include the course field.
        if (!$this->courseprovided) {
            $headerkeys[] = 'course';
        }

        $headerkeys[] = 'groups';
        $headerkeys[] = 'sessiondate';
        $headerkeys[] = 'from';
        $headerkeys[] = 'to';
        $headerkeys[] = 'description';
        $headerkeys[] = 'repeaton';
        $headerkeys[] = 'repeatevery';
        $headerkeys[] = 'repeatuntil';
        $headerkeys[] = 'studentscanmark';
        $headerkeys[] = 'passwordgrp';
        $headerkeys[] = 'randompassword';
        $headerkeys[] = 'subnet';
        $headerkeys[] = 'automark';
        $headerkeys[] = 'autoassignstatus';
        $headerkeys[] = 'absenteereport';
        $headerkeys[] = 'preventsharedip';
        $headerkeys[] = 'preventsharediptime';
        $headerkeys[] = 'calendarevent';
        $headerkeys[] = 'includeqrcode';
        $headerkeys[] = 'rotateqrcode';
        $headerkeys[] = 'studentsearlyopentime';

        // Subtract 1 for 0 indexed arrays.
        $valuecount = count($headerkeys) - 1;
        if ($data) {
            $headervalues = [];
            for ($i = 0; $i <= $valuecount; $i++) {
                $headervalues[] = $data->{"header$i"} ?? null;
            }
        } else {
            $headervalues = range(0, $valuecount);
        }
        return array_combine($headerkeys, $headervalues);
    }

    /**
     * Get the a column from the imported data.
     *
     * @param array $row The imported raw row
     * @param int $index The column index we want
     * @return string The column data.
     */
    protected function get_column_data($row, $index) {
        if ($index < 0) {
            return '';
        }
        return isset($row[$index]) ? $row[$index] : '';
    }

    /**
     * Constructor - parses the raw text for sanity.
     *
     * @param string $text The raw csv text.
     * @param string $encoding The encoding of the csv file.
     * @param string $delimiter The specified delimiter for the file.
     * @param string $importid The id of the csv import.
     * @param array $mappingdata The mapping data from the import form.
     * @param bool $useprogressbar Whether progress bar should be displayed, to avoid html output on CLI.
     * @param bool $courseshortname Course shortname for the course level imports.
     * @param bool $behaviourid ID for the behaviour activity for course level imports.
     */
    public function __construct($text = null, $encoding = null, $delimiter = null, $importid = 0,
                                $mappingdata = null, $useprogressbar = false, $courseshortname = null, $behaviourid = null) {
        global $CFG, $DB;

        require_once($CFG->libdir . '/csvlib.class.php');

        if ($courseshortname) {
            $this->courseprovided = true;
        }

        $pluginconfig = get_config('behaviour');

        $type = 'sessions';

        if (! $importid) {
            if ($text === null) {
                return;
            }
            $this->importid = csv_import_reader::get_new_iid($type);

            $this->importer = new csv_import_reader($this->importid, $type);

            if (! $this->importer->load_csv_content($text, $encoding, $delimiter)) {
                $this->fail(get_string('invalidimportfile', 'behaviour'));
                $this->importer->cleanup();
                return;
            }
        } else {
            $this->importid = $importid;

            $this->importer = new csv_import_reader($this->importid, $type);
        }

        if (! $this->importer->init()) {
            $this->fail(get_string('invalidimportfile', 'behaviour'));
            $this->importer->cleanup();
            return;
        }

        $this->foundheaders = $this->importer->get_columns();
        $this->useprogressbar = $useprogressbar;
        $domainid = 1;

        $sessions = array();

        while ($row = $this->importer->next()) {
            // This structure mimics what the UI form returns.
            $mapping = $this->read_mapping_data($mappingdata);

            $session = new stdClass();
            $session->behaviourid = $behaviourid;
            if ($this->courseprovided) {
                $session->course = $courseshortname;
            } else {
                $session->course = $this->get_column_data($row, $mapping['course']);
            }
            if (empty($session->course)) {
                \mod_behaviour_notifyqueue::notify_problem(get_string('error:sessioncourseinvalid', 'behaviour'));
                continue;
            }

            // Handle multiple group assignments per session. Expect semicolon separated group names.
            $groups = $this->get_column_data($row, $mapping['groups']);
            if (! empty($groups)) {
                $session->groups = explode(';', $groups);
                $session->sessiontype = \mod_behaviour_structure::SESSION_GROUP;
            } else {
                $session->sessiontype = \mod_behaviour_structure::SESSION_COMMON;
            }

            // Expect standardised date format, eg YYYY-MM-DD.
            $sessiondate = strtotime($this->get_column_data($row, $mapping['sessiondate']));
            if ($sessiondate === false) {
                \mod_behaviour_notifyqueue::notify_problem(get_string('error:sessiondateinvalid', 'behaviour'));
                continue;
            }
            $session->sessiondate = $sessiondate;

            // Expect standardised time format, eg HH:MM.
            $from = $this->get_column_data($row, $mapping['from']);
            if (empty($from)) {
                \mod_behaviour_notifyqueue::notify_problem(get_string('error:sessionstartinvalid', 'behaviour'));
                continue;
            }
            $from = explode(':', $from);
            $session->sestime['starthour'] = $from[0];
            $session->sestime['startminute'] = $from[1];

            $to = $this->get_column_data($row, $mapping['to']);
            if (empty($to)) {
                \mod_behaviour_notifyqueue::notify_problem(get_string('error:sessionendinvalid', 'behaviour'));
                continue;
            }
            $to = explode(':', $to);
            $session->sestime['endhour'] = $to[0];
            $session->sestime['endminute'] = $to[1];

            // Wrap the plain text description in html tags.
            $session->sdescription['text'] = '<p>' . $this->get_column_data($row, $mapping['description']) . '</p>';
            $session->sdescription['format'] = FORMAT_HTML;
            $session->sdescription['itemid'] = 0;
            $session->studentpassword = $this->get_column_data($row, $mapping['passwordgrp']);
            $session->subnet = $this->get_column_data($row, $mapping['subnet']);
            // Set session subnet restriction. Use the default activity level subnet if there isn't one set for this session.
            if (empty($session->subnet)) {
                $session->usedefaultsubnet = '1';
            } else {
                $session->usedefaultsubnet = '';
            }

            $studentscanmark = $this->get_column_data($row, $mapping['studentscanmark']);
            if ($studentscanmark == -1) {
                $session->studentscanmark = $pluginconfig->studentscanmark_default;
            } else {
                $session->studentscanmark = $studentscanmark;
            }

            $randompassword = $this->get_column_data($row, $mapping['randompassword']);
            if ($randompassword == -1) {
                $session->randompassword = $pluginconfig->randompassword_default;
            } else {
                $session->randompassword = $randompassword;
            }

            $automark = $this->get_column_data($row, $mapping['automark']);
            if ($automark == -1) {
                $session->automark = $pluginconfig->automark_default;
            } else {
                $session->automark = $automark;
            }

            $autoassignstatus = $this->get_column_data($row, $mapping['autoassignstatus']);
            if ($autoassignstatus == -1) {
                $session->autoassignstatus = $pluginconfig->autoassignstatus;
            } else {
                $session->autoassignstatus = $autoassignstatus;
            }

            $absenteereport = $this->get_column_data($row, $mapping['absenteereport']);
            if ($absenteereport == -1) {
                $session->absenteereport = $pluginconfig->absenteereport_default;
            } else {
                $session->absenteereport = $absenteereport;
            }

            $preventsharedip = $this->get_column_data($row, $mapping['preventsharedip']);
            if ($preventsharedip == -1) {
                $session->preventsharedip = $pluginconfig->preventsharedip;
            } else {
                $session->preventsharedip = $preventsharedip;
            }

            $preventsharediptime = $this->get_column_data($row, $mapping['preventsharediptime']);
            if ($preventsharediptime == -1) {
                $session->preventsharediptime = $pluginconfig->preventsharediptime;
            } else {
                $session->preventsharediptime = $preventsharediptime;
            }

            $calendarevent = $this->get_column_data($row, $mapping['calendarevent']);
            if ($calendarevent == -1) {
                $session->calendarevent = $pluginconfig->calendarevent_default;
            } else {
                $session->calendarevent = $calendarevent;
            }

            $includeqrcode = $this->get_column_data($row, $mapping['includeqrcode']);
            if ($includeqrcode == -1) {
                $session->includeqrcode = $pluginconfig->includeqrcode_default;
            } else {
                $session->includeqrcode = $includeqrcode;

                if ($session->includeqrcode == 1 && $session->studentscanmark != 1) {
                    \mod_behaviour_notifyqueue::notify_problem(get_string('error:qrcode', 'behaviour'));
                    continue;
                }

            }

            $rotateqrcode = $this->get_column_data($row, $mapping['rotateqrcode']);
            if ($rotateqrcode == -1) {
                $session->rotateqrcode = $pluginconfig->rotateqrcode_default;
            } else {
                $session->rotateqrcode = $rotateqrcode;
            }
            if ($session->rotateqrcode) {
                $session->includeqrcode = 0;
            }

            $studentsearlyopentime = $this->get_column_data($row, $mapping['studentsearlyopentime']);
            if ($studentsearlyopentime == -1) {
                $session->studentsearlyopentime = $pluginconfig->studentsearlyopentime;
            } else {
                $session->studentsearlyopentime = $studentsearlyopentime;
            }

            // Reapeating session settings.
            if (empty($mapping['repeaton'])) {
                $session->sdays = [];
            } else {
                $repeaton = $this->get_column_data($row, $mapping['repeaton']);
                $sdays = array_map('trim', explode(',', $repeaton));
                $session->sdays = array_fill_keys($sdays, 1);
            }
            if (empty($mapping['repeatevery'])) {
                $session->period = '';
            } else {
                $session->period = $this->get_column_data($row, $mapping['repeatevery']);
            }
            if (empty($mapping['repeatuntil'])) {
                $session->sessionenddate = null;
            } else {
                $session->sessionenddate = strtotime($this->get_column_data($row, $mapping['repeatuntil']));
            }
            $course = $DB->get_record('course', ['shortname' => $session->course]);
            if ($course) {
                $session->coursestartdate = $course;
            }
            if (!empty($session->sdays) && !empty($session->period) &&
                !empty($session->sessionenddate) && !empty($session->coursestartdate)) {
                $session->addmultiply = 1;
            }

            $session->statusset = 0;

            $sessions[] = $session;
        }
        $this->sessions = $sessions;

        $this->importer->close();
        if ($this->sessions == null) {
            $this->fail(get_string('invalidimportfile', 'behaviour'));
            return;
        } else {
            // We are calling from browser, display progress bar.
            if ($this->useprogressbar === true) {
                $this->progress = new \core\progress\display_if_slow(get_string('processingfile', 'behaviour'));
                $this->progress->start_html();
            } else {
                // Avoid html output on CLI scripts.
                $this->progress = new \core\progress\none();
            }
            $this->progress->start_progress('', count($this->sessions));
            raise_memory_limit(MEMORY_EXTRA);
            $this->progress->end_progress();
        }
    }

    /**
     * Get parse errors.
     *
     * @return array of errors from parsing the xml.
     */
    public function get_error() {
        return $this->error;
    }

    /**
     * Create sessions using the CSV data.
     *
     * @return void
     */
    public function import() {
        global $DB;

        // Count of sessions added.
        $okcount = 0;

        foreach ($this->sessions as $session) {
            $groupids = array();
            // Check course shortname matches.
            if ($DB->record_exists('course', array(
                'shortname' => $session->course
            ))) {
                // Get course.
                $course = $DB->get_record('course', array(
                    'shortname' => $session->course
                ), '*', MUST_EXIST);

                // Check course has activities.
                if ($DB->record_exists('behaviour', array(
                    'course' => $course->id
                ))) {
                    // Translate group names to group IDs. They are unique per course.
                    if ($session->sessiontype === \mod_behaviour_structure::SESSION_GROUP) {
                        foreach ($session->groups as $groupname) {
                            $gid = groups_get_group_by_name($course->id, $groupname);
                            if ($gid === false) {
                                \mod_behaviour_notifyqueue::notify_problem(get_string('sessionunknowngroup',
                                                                            'behaviour', $groupname));
                            } else {
                                $groupids[] = $gid;
                            }
                        }
                        $session->groups = $groupids;
                    }

                    // Get activities in course or specific activity if provided.
                    $params = ['course' => $course->id];
                    if ($session->behaviourid) {
                        $params['id'] = $session->behaviourid;
                    }
                    $activities = $DB->get_recordset('behaviour', $params);

                    foreach ($activities as $activity) {
                        // Build the session data.
                        $cm = get_coursemodule_from_instance('behaviour', $activity->id, $course->id);
                        if (!empty($cm->deletioninprogress)) {
                            // Don't do anything if this behaviour is in recycle bin.
                            continue;
                        }
                        $att = new mod_behaviour_structure($activity, $cm, $course);
                        $sessions = behaviour_construct_sessions_data_for_add($session, $att);

                        foreach ($sessions as $index => $sess) {
                            // Check for duplicate sessions.
                            if ($this->session_exists($sess, $att->id)) {
                                mod_behaviour_notifyqueue::notify_message(get_string('sessionduplicate', 'behaviour', (array(
                                    'course' => $session->course,
                                    'activity' => $cm->name,
                                    'date' => mod_behaviour_construct_session_full_date_time($sess->sessdate, $sess->duration)
                                ))));
                                unset($sessions[$index]);
                            } else {
                                $okcount ++;
                            }
                        }
                        if (! empty($sessions)) {
                            $att->add_sessions($sessions);
                        }
                    }
                    $activities->close();
                } else {
                    mod_behaviour_notifyqueue::notify_problem(get_string('error:coursehasnobehaviour',
                        'behaviour', $session->course));
                }
            } else {
                mod_behaviour_notifyqueue::notify_problem(get_string('error:coursenotfound', 'behaviour', $session->course));
            }
        }

        $message = get_string('sessionsgenerated', 'behaviour', $okcount);
        if ($okcount < 1) {
            mod_behaviour_notifyqueue::notify_message($message);
        } else {
            mod_behaviour_notifyqueue::notify_success($message);
        }

        // Trigger a sessions imported event.
        $event = \mod_behaviour\event\sessions_imported::create(array(
            'objectid' => 0,
            'context' => \context_system::instance(),
            'other' => array(
                'count' => $okcount
            )
        ));

        $event->trigger();
    }

    /**
     * Check if an identical session exists.
     *
     * @param stdClass $session
     * @param int $attid
     * @return boolean
     */
    private function session_exists(stdClass $session, $attid) {
        global $DB;

        $check = ['behaviourid' => $attid,
                  'sessdate' => $session->sessdate,
                  'duration' => $session->duration,
                  'groupid' => $session->groupid];
        if ($DB->record_exists('behaviour_sessions', $check)) {
            return true;
        }
        return false;
    }
}
