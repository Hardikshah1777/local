<?php

namespace local_userlist\table;

use html_writer;
use moodle_url;
use paging_bar;

class userlist extends \table_sql
{
    public $showpaginationsat = [TABLE_P_BOTTOM];

    public function __construct($uniqueid)
    {
        parent::__construct($uniqueid);
    }

    public function init()
    {
        global $DB;

        $search = optional_param('search', '', PARAM_TEXT);

        $col = [
            'fullname' => 'First name',
            'username' => 'User name',
            'email' => 'Email',
            'city' => 'City',
            'timecreated' => 'Date',
        ];
        if (!$this->is_downloading()) {
            $col['action'] = 'Action';
        }

        $url = new moodle_url('/local/userlist/index.php', ['search' => $search]);
        $this->define_headers(array_values($col));
        $this->define_columns(array_keys($col));
        $this->sortable(true);
        $this->collapsible(false);
        $this->define_baseurl($url);
        $this->no_sorting('action');
        $where = 'id > 2 AND deleted = 0 AND suspended = 0';
        $params = [];
        if ($search) {
            $where .= ' AND ('.$DB->sql_like('firstname',':firstname').' OR '.
                         $DB->sql_like('lastname',':lastname').' OR '.
                         $DB->sql_like('username',':username').' OR '.
                         $DB->sql_like('email',':email').')';
            $params['firstname'] = $params['lastname'] = $params['username'] = $params['email'] = '%'.$search.'%';
        }
        $this->set_sql('*','{user}', $where, $params);
        $this->out(30, false);
    }

    public function col_timecreated($row){
        if ($row->timecreated){
            $date = userdate($row->timecreated, '%d/%m/%Y');
        }else{
            $date = '-';
        }
        return $date;
    }

    public function col_action($row) {
        global $OUTPUT;
        if (has_capability('moodle/user:delete', $this->get_context())) {
            $deleteurl = new moodle_url( '/admin/user.php', ['delete' => $row->id, 'sesskey' => sesskey()] );
            $deletbtn = $OUTPUT->action_link( $deleteurl, '', null, [], new \pix_icon( 't/delete', 'Delete' ) );
        }
        if (has_capability('moodle/user:editprofile', $this->get_context())) {
            $editurl = new moodle_url('/user/editadvanced.php', ['id' => $row->id]);
            $editbtn = $OUTPUT->action_link($editurl, '', null, [], new \pix_icon( 't/edit', 'Edit' ));
        }
        $mailurl = new moodle_url('/local/userlist/index.php', ['userid' => $row->id]);
        $mailbtn = $OUTPUT->action_link($mailurl, '', null, ['data-action' => 'sendmail', 'class' => 'maillink'],new \pix_icon('t/email', 'E-mail'));

        return $deletbtn . $editbtn . $mailbtn;
    }

    public function define_baseurl($url)
    {
        parent::define_baseurl( $url );
    }

    public function start_html() {
        global $OUTPUT;

        // Render the dynamic table header.
        echo $this->get_dynamic_table_html_start();

        // Render button to allow user to reset table preferences.
        echo $this->render_reset_button();

        // Do we need to print initial bars?
        $this->print_initials_bar();

        // Paging bar
        if ($this->use_pages && in_array(TABLE_P_TOP, $this->showpaginationsat)) {
            $pagingbar = new paging_bar($this->totalrows, $this->currpage, $this->pagesize, $this->baseurl);
            $pagingbar->pagevar = $this->request[TABLE_VAR_PAGE];
            echo $OUTPUT->render($pagingbar);
        }

        if (in_array(TABLE_P_TOP, $this->showdownloadbuttonsat)) {
            echo $this->download_buttons();
        }

        $this->wrap_html_start();
        // Start of main data table

        echo html_writer::start_tag('div', array('class' => 'no-overflow'));
        echo html_writer::start_tag('table', $this->attributes);

    }
}