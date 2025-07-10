<?php

namespace local_squibit\event;

use coding_exception;
use context_system;

class api_failed extends \core\event\base {

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('apifailed', 'local_squibit');
    }

    public function get_description() {
        return var_export($this->data['other'], true);
    }

    protected function init() {
        $this->context = context_system::instance();
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    protected function validate_data() {
        if (!array_key_exists('header', $this->other)) {
            throw new coding_exception("'header' must be set in other");
        }
        if (!array_key_exists('responsecode', $this->other)) {
            throw new coding_exception("'responsecode' must be set in other");
        }
        if (!array_key_exists('rawresponse', $this->other)) {
            throw new coding_exception("'rawresponse' must be set in other");
        }
    }
}
