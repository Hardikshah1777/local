<?php

namespace local_test2\table;
use confirm_action;
use moodle_url;

class test2table extends \table_sql
{
    public function __construct($uniqueid)
    {
        parent::__construct($uniqueid);
    }

    public function init() {
        global $DB;
        $search = optional_param('search','',PARAM_ALPHAEXT);
        $col = [
            'firstname' => 'First name',
            'lastname' => 'Last name',
            'email' => 'Email',
            'city' => 'City',
            'timecreated' => 'Date',
        ];
        if (!$this->is_downloading()) {
            $col['action'] = 'Action';
        }
        $url = new moodle_url('/local/test2/index.php', ['search'=> $search]);
        $this->define_headers(array_values($col));
        $this->define_columns(array_keys($col));
        $this->sortable(true);
        $this->collapsible(false);
        $this->no_sorting('action');
        $this->define_baseurl($url);
        $where = '';
        $params = [];
        if (!empty($search)) {
            $search = trim($search);
            $where .= ' AND (' . $DB->sql_like( 'firstname', ':firstname', false ) .
                ' OR ' . $DB->sql_like( 'lastname', ':lastname', false ) .
                ' OR ' . $DB->sql_like( 'email', ':email', false ) . ')';
            $params['firstname'] = $params['lastname'] = $params['email'] = '%' . $search . '%';
        }

        $this->set_sql( '*', '{local_test2}', 'id > 0' . $where, $params);

        $this->out(30, false);
    }

    public function col_timecreated($row){
        if ($row->timecreated){
            $date = userdate($row->timecreated, '%d/%m/%Y %H:%M');
        }else{
            $date = '-';
        }
         return $date;
    }
    public function col_action($row){
        global $OUTPUT;

        $deleteurl = new moodle_url('/local/test2/index.php',['deleteid' => $row->id]);
        $confirm = new confirm_action(get_string('deleteconfirm', 'local_test2'));
        $deletebutton = $OUTPUT->action_link($deleteurl, '', $confirm, [],new \pix_icon('t/delete','Delete'));

        $editurl = new moodle_url('/local/test2/add.php', ['id' => $row->id]);
        $editbutton = $OUTPUT->action_link($editurl, '', null, [],new \pix_icon('t/edit', 'Edit'));

        return $editbutton . $deletebutton;
    }

    public function exportinzip() {
        global $DB;

        $exportinzip = optional_param('exportinzip', '', PARAM_ALPHA);
        if ($exportinzip == "exportinzip") {
            $zipper = get_file_packer( 'application/zip' );
            $temppath = make_request_directory() . '/zip';
            $zipfiles = [];
            $users = $DB->get_records_sql( 'SELECT * FROM {local_test2}');
            $i = 1;

            foreach ($users as $user) {
                $zipfiles[fullname( $user )] = [
                    "No : ".$i++.
                    "\nFullname: " .fullname($user).
                    "\nEmail: " . $user->email .
                    "\ncity: " . $user->city .
                    "\nCreated Date: " . userdate( $user->timecreated, get_string( 'strftimedatetimeshortaccurate', 'core_langconfig')) ?? '-'];
            }
            if (!empty($zipfiles)) {
                $zipper->archive_to_pathname($zipfiles, $temppath);
                $filename = 'Users_' . date('Ymd_His') . '.zip';
                send_file($temppath, $filename);
                exit;
            } else {
                \core\notification::add( get_string( 'nodatatoexport', 'local_test1' ), \core\notification::WARNING );
            }
        }
    }
}