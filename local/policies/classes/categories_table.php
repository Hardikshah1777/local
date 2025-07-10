<?php


namespace local_policies;

use moodle_url;
use single_button;
use table_sql;

global $CFG;

require_once($CFG->libdir . '/tablelib.php');

class categories_table extends table_sql
{
    public $perpage = 10;

    public function categorydata(){

        $col = [
            'name' => get_string('catname','local_policies'),
            'timecreated' => get_string('createtime','local_policies'),
            'action' => get_string('action','local_policies'),
        ];

        $this->define_columns(array_keys($col));
        $this->define_headers(array_values($col));
        $this->sortable(false);
        $this->collapsible(false);

        $this->set_sql('*','{local_policycategories_table}', 'id <> 0');
        $this->out($this->perpage,false);
    }
    public function col_action($col){

        global $OUTPUT;
        $editurl = new moodle_url('/local/policies/addcategory.php',['id' => $col->id]);
        $edit = new single_button($editurl, get_string('editcategory','local_policies'));
        return $OUTPUT->render($edit);
    }
    public function col_timecreated($col){
        if($col->timecreated){
            return gmdate('d-m-Y',$col->timecreated);
        }
    }
}