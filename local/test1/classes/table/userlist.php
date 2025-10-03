<?php

namespace local_test1\table;

use confirm_action;
use html_writer;
use moodle_url;
use pix_icon;
use table_sql;

class userlist extends table_sql {

    public $showpaginationsat = [TABLE_P_BOTTOM];

    public $search;

    public function col_profile($row) {
        global $OUTPUT;
        if ($row->picture) {
            return $OUTPUT->user_picture( $row, ['size' => 40, 'link' => true, 'alttext' => false] );
        }else{
            $imgurl = new moodle_url('/local/test1/pix/useravtar.png');
            $profile = new moodle_url('/user/profile.php', ['id' => $row->id]);
            $img = html_writer::img($imgurl, 'User', ['class' => 'userpicture', 'width' => 40]);
            return html_writer::link($profile, $img);
        }
    }

    public function col_timecreated($row) {
        return userdate( $row->timecreated, get_string( 'strftimedatetime', 'core_langconfig' ) );
    }

    public function col_action($row) {
        global $OUTPUT;
        $edituser1 = new moodle_url( '/user/editadvanced.php', ['id' => $row->id, 'localtest1' => 'localtest1'] );
        $edituser = $OUTPUT->action_link( $edituser1, new pix_icon( 't/edit', get_string( 'edit' ) ) );

        $logurl = new moodle_url( '/local/test1/maillog.php', ['userid' => $row->id]);
        $viewlog = $OUTPUT->action_link($logurl, new pix_icon( 'e/preview', get_string( 'view')));

        //$mail = new moodle_url('/local/test1/testmail.php', ['data-uid' => $row->id, 'search'=> $this->search]);
        $email = $OUTPUT->action_link( '#', new pix_icon( 't/email', get_string( 'email', 'local_test1' ) ), null, ['data-uid' => $row->id, 'class' => 'maillink'] );

        $downloadpdf = $OUTPUT->action_link( '#', new pix_icon( 'f/pdf-128', get_string( 'pdf', 'local_test1' ) ), null, ['data-user' => json_encode( $row ), 'class' => 'downloadpdf'] );

        $downloadcsv = $OUTPUT->action_link( '#', new pix_icon( 'f/calc-128', get_string( 'csv', 'local_test1' ) ), null, ['data-user' => json_encode( $row ), 'class' => 'downloadcsv'] );

        $syncurl = new moodle_url('/local/test1/sync.php', ['userid' => $row->id]);
        $syncbtn = $OUTPUT->action_link( $syncurl, new pix_icon( 't/sync', "sync user in iomad4"), null, ['class' => 'far fa-sync']);

        $edituser2 = new moodle_url( '/local/test1/index.php', ['delete' => $row->id, 'localtest1' => 'localtest1'] );
        $confirm = new confirm_action( get_string( 'confirmuserdelete', 'local_test1', $row ) );
        $deleteuser = $OUTPUT->action_link( $edituser2, new pix_icon( 't/delete', get_string( 'delete' ) ), $confirm );

        return ($edituser . $viewlog . $email . $downloadpdf . $downloadcsv . $syncbtn . $deleteuser);
    }

    function start_html() {
        global $OUTPUT;

        echo $this->get_dynamic_table_html_start();

        echo $this->render_reset_button();

        $this->print_initials_bar();

        if ($this->use_pages && in_array( TABLE_P_TOP, $this->showpaginationsat )) {
            $pagingbar = new paging_bar( $this->totalrows, $this->currpage, $this->pagesize, $this->baseurl );
            $pagingbar->pagevar = $this->request[TABLE_VAR_PAGE];
            echo $OUTPUT->render( $pagingbar );
        }

        if (in_array( TABLE_P_TOP, $this->showdownloadbuttonsat )) {
            echo $this->download_buttons();
        }

        $this->wrap_html_start();

        echo html_writer::start_tag( 'div', array('class' => 'no-overflow') );
        echo html_writer::start_tag( 'table', $this->attributes );
    }
}
