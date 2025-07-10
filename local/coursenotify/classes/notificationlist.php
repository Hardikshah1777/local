<?php

namespace local_coursenotify;

use moodle_url;

require_once $CFG->libdir . '/tablelib.php';

class notificationlist extends \table_sql
{
    public $is_collapsible = false;
    public $is_sortable = false;
    public $use_pages = false;
    public $showdownloadbuttonsat = array();
    private $courseid;
    public function __construct($courseid = 0)
    {
        parent::__construct('notificationlist');
        $this->courseid = $courseid;
        $this->set();
    }
    public function set()
    {
        $cols = array(
            'title'=> get_string('th:title',utility::$component),
            'threshold'=> get_string('th:threshold',utility::$component),
            'status' => get_string('th:status',utility::$component),
            'action' => get_string('th:action',utility::$component),
        );
        $this->define_headers(array_values($cols));
        $this->define_columns(array_keys($cols));
        $this->set_sql('n.*,c.fullname as coursename','{local_coursenotify} n JOIN {course} c ON c.id = n.courseid','courseid = :courseid',array('courseid' => $this->courseid));
    }
    public function other_cols($column, $row)
    {
        global $OUTPUT;
        switch ($column){
            case 'action':
                $editurl = new moodle_url('/local/coursenotify/edit.php',array('courseid'=>$this->baseurl->param('courseid'),'id'=>$row->id,));
                $edit = $OUTPUT->action_link($editurl,
                    $OUTPUT->pix_icon('t/edit',get_string('editmessage',utility::$component,$row->coursename)));
                $html = $edit;
                break;
            case 'threshold':
                if ($row->beforeafter == LOCAL_COURSENOTIFY_IMMEDIATE)
                    $html = get_string('immediately',utility::$component);
                else {
                    $html = $this->seconds_to_unit($row->threshold);
                    $html .= ' ' . utility::get_beforeafteropt()[$row->beforeafter];
                    $html .= ' ' . utility::get_refdateopt()[$row->refdate];
                }
                $html .= ' (' . utility::get_expirynotifyopt()[$row->expirynotify] . ')';
                break;
            case 'status':
                $html = !empty($row->$column)?get_string('enable'):get_string('disable');
                break;
            default:
                $html = null;
                break;
        }
        return $html;
    }

    public function seconds_to_unit($seconds) {
        $units = array(
            604800 => get_string('weeks'),
            86400 => get_string('days'),
            3600 => get_string('hours'),
            60 => get_string('minutes'),
            1 => get_string('seconds'),
        );
        if ($seconds == 0)
            return  '0 '.$units[60];
        foreach ($units as $unit => $notused) {
            if (fmod($seconds, $unit) == 0)
                return $seconds / $unit .' '. $notused;
        }
        return $seconds .' '. $units[1];
    }
}
