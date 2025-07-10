<?php

namespace local_policies;

use context_system;
use moodle_url;
use single_button;
use table_sql;

global $CFG;

require_once($CFG->libdir . '/tablelib.php');

class policies_table extends table_sql
{
    public $perpage = 10;

    public $baseurl;

    public $catid;

    public function showdata()
    {

        $col = [
            'name' => get_string('name', 'local_policies'),
            'category' => get_string('categoryy', 'local_policies'),
            'file' => get_string('document', 'local_policies'),
            'timemodified' => get_string('modified', 'local_policies'),
            'action' => get_string('action', 'local_policies'),
        ];

        $this->define_columns(array_keys($col));
        $this->define_headers(array_values($col));
        $this->sortable(false);
        $this->collapsible(false);
        $where = 'lp.id <> 0';
        if ($this->catid) {
            $where .= " AND lp.categoryid = {$this->catid}";
        }

        $this->set_sql('lp.*,lc.id as catid,lc.id as cid,lc.name as category', '{local_policies} lp JOIN {local_policycategories_table} lc on lp.categoryid = lc.id', $where);
        $this->out($this->perpage, false);
    }

    public function col_file($col)
    {

        global $OUTPUT;
        $context = context_system::instance();
        $files = explode(PHP_EOL, $col->files);
        foreach ($files as $file) {
            $iconfile = $OUTPUT->pix_icon(file_file_icon(['filename' => $file]), get_mimetype_description(['filename' => $file]));
            $urlmake = moodle_url::make_pluginfile_url($context->id, 'local_policies', 'overviewfiles', $col->id, '/', $file);
            $links[] = "<a href='" . $urlmake . "' target='_blank'>" . $iconfile . " " . $file . "</a>";
        }
        return join('<br>', $links);
    }

    public function col_timemodified($col)
    {

        if (!empty($col->timemodified)) {
            $timemodified = userdate($col->timemodified, get_string('strftimedatetime'));
        } else {
            $timemodified = '-';
        }
        return $timemodified;
    }

    public function col_action($col)
    {
        global $OUTPUT;
        $editurl = new moodle_url('/local/policies/add.php', ['id' => $col->id]);
        $edit = new single_button($editurl, get_string('editpolicy', 'local_policies'));
        return $OUTPUT->render($edit);
    }

}