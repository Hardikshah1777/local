<?php
$userid = optional_param('uid', '', PARAM_INT);

class block_useraccess extends block_base
{
    public function init()
    {
        $this->title = get_string('useraccess', 'block_useraccess');
    }

    public function get_content()
    {
        global $CFG, $DB, $userid;
        if ($this->content !== null) {
            return $this->content;
        }

        $date = (time() - 3600);
        $users = $DB->get_records_sql('SELECT * FROM {user} WHERE id > 2 AND lastaccess > :mindate AND lastaccess < :maxdate ORDER BY lastaccess DESC', ['mindate' => $date, 'maxdate' => time()]);

        $this->content = new stdClass;
        $this->content->text = 'The content is show the last 1 hour logged in users';
        $html = html_writer::start_tag('table', ['class' => 'table table-striped']);
        $html .= html_writer::start_tag('tbody');
        $html .= html_writer::start_tag('tr');
        $html .= html_writer::tag('th', 'User');
        $html .= html_writer::tag('th', 'Last Access');
        $html .= html_writer::tag('th', 'Action');
        $html .= html_writer::end_tag('tr');
        foreach ($users as $user) {
            $html .= html_writer::start_tag('tr');
            $html .= html_writer::tag('td', $user->username);
            $html .= html_writer::tag('td', format_time(time() - $user->lastaccess));
            $html .= html_writer::tag('td', '<form method="post">
                                    <input type="hidden" name="uid" value=' . $user->id . '>
                                    <input type="submit" name="submit" value="Enrol" class="btn btn-primary">
                                </form>');
            $html .= html_writer::end_tag('tr');
        }
        $html .= html_writer::end_tag('tbody');
        $html .= html_writer::end_tag('table');

        if (isset($_POST['submit'])) {
            require_once($CFG->dirroot . '/blocks/useraccess/lib.php');
            $userid = $_POST['uid'];
            if (!$DB->record_exists_sql('SELECT e.courseid,ue.userid FROM {enrol} e JOIN {user_enrolments} ue  ON ue.enrolid = e.id WHERE courseid = :courseid AND ue.userid = :userid', ['courseid' => 4, 'userid' => $userid])) {
                enroluserincourse($userid);
            }else{
                redirect(new moodle_url('/my'),get_string('alreadyenrol','block_useraccess'),3,'warrning');
            }
        }

        $this->content->text .= $html;
        return $this->content;
    }
}