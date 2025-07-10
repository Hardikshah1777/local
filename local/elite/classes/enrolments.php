<?php

namespace local_elite;

use moodle_url;
use table_sql;

require_once($CFG->libdir . '/tablelib.php');

class enrolments extends table_sql
{
    public $download;

    public function showdata()
    {
        $col = [
            'profile' => 'Profile',
            'firstname' => 'Firstname',
            'lastname' => 'Lastname',
            'shortname' => 'Course',
            'email' => get_string('email'),
            'method' => 'Enrolment Method',
            'time' => 'Enrolment Time',
        ];

        $url = new moodle_url('/local/elite/enrolments.php');
        $this->define_columns(array_keys($col));
        $this->define_headers(array_values($col));
        $this->sortable(true);
        $this->collapsible(false);
        $this->no_sorting('profile');
        $this->define_baseurl($url);
        $this->set_sql('ue.id, e.courseid, u.id as userid, u.username,u.firstname,u.lastname,u.email,c.shortname,e.enrol as method,ue.timecreated as time',
            '{enrol} e
                       JOIN {user_enrolments} ue on ue.enrolid = e.id
                       JOIN {course} c on c.id = e.courseid
                       JOIN {user} u on u.id = ue.userid',
            'u.id > 2 ');

        $this->is_downloadable(true);
        if ($this->is_downloading($this->download, 'Enrolments', 'Enrolments')) {
            unset($this->headers[0]);
            unset($this->columns['profile']);
            $this->out(15, false);
        }

        $this->out(15, false);
    }

    public function col_time($data)
    {
        if (!empty($data->time)) {
            return userdate($data->time, get_string('strftimedatetime', 'langconfig'));
        } else {
            return '-';
        }
    }

    public function col_profile($data)
    {
        global $OUTPUT;
        $user = \core_user::get_user($data->userid);
        return $OUTPUT->user_picture($user, ['size' => 35, '', 'includefullname' => false]);
    }
}