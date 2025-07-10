<?php

namespace local_test2\form;

use context;
use context_system;
use core\form\bsselect;
use core_form\dynamic_form;
use moodle_url;

require_once($CFG->libdir . '/formslib.php');

bsselect::register();

class test_form extends dynamic_form {

    public function definition()
    {
        global $DB;
        $mform = $this->_form;
        $test = ['0' => 'Choose','1' => 'test1','2' => 'test2','3' => 'test3',];
        $mform->addElement( 'bsselect', 'search', 'Search', $test, []);
        $mform->setType('search', PARAM_ALPHAEXT);

        $this->add_action_buttons(false, get_string( 'submit'));
    }

    /**
     * @inheritDoc
     */
    protected function get_context_for_dynamic_submission(): context {
        return context_system::instance();
    }

    /**
     * @inheritDoc
     */
    protected function check_access_for_dynamic_submission(): void {
    }

    /**
     * @inheritDoc
     */
    public function process_dynamic_submission() {
    }

    /**
     * @inheritDoc
     */
    public function set_data_for_dynamic_submission(): void {
    }

    /**
     * @inheritDoc
     */
    protected function get_page_url_for_dynamic_submission(): moodle_url {
        return new moodle_url('/local/test2/index.php');
    }
}