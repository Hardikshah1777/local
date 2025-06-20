<?php

namespace local_test2\form;
use context;
use context_system;
use moodle_url;
use moodleform;

require_once($CFG->libdir . '/formslib.php');

class search_form extends moodleform {

    public function definition()
    {
        $mform = $this->_form;

        $test = ['0' => 'Choose','1' => 'test1','2' => 'test2','3' => 'test3',];

        $mform->addElement( 'select', 'search', 'Search', $test,
            ['data-absselect' => 1, 'multiple' => false,]);
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