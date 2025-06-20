<?php


namespace mod_test\event;


class course_module_viewed extends \core\event\course_module_viewed
{
    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'test';
    }

    /**
     * Get URL related to the action
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/mod/test/view.php', array('f' => $this->objectid));
    }

    /**
     * Return the legacy event log data.
     *
     * @return array|null
     */
    protected function get_legacy_logdata() {
        return array($this->courseid, 'test', 'view test', 'view.php?f=' . $this->objectid,
            $this->objectid, $this->contextinstanceid);
    }

    public static function get_objectid_mapping() {
        return array('db' => 'test', 'restore' => 'test');
    }

//    public static function create($data){
//        print_object($data);
//    }
}