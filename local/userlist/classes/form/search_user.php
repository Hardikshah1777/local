<?php

namespace local_userlist\form;

require_once($CFG->libdir . '/formslib.php');

use context;
use core_form\dynamic_form;
use moodle_url;

class search_user extends dynamic_form
{
    /**
     * @inheritDoc
     */
    protected function get_context_for_dynamic_submission(): context
    {
        // TODO: Implement get_context_for_dynamic_submission() method.
    }

    /**
     * @inheritDoc
     */
    protected function check_access_for_dynamic_submission(): void
    {
        // TODO: Implement check_access_for_dynamic_submission() method.
    }

    /**
     * @inheritDoc
     */
    public function process_dynamic_submission()
    {
        // TODO: Implement process_dynamic_submission() method.
    }

    /**
     * @inheritDoc
     */
    public function set_data_for_dynamic_submission(): void
    {
        // TODO: Implement set_data_for_dynamic_submission() method.
    }

    /**
     * @inheritDoc
     */
    protected function get_page_url_for_dynamic_submission(): moodle_url
    {
        $search = $this->optional_param('search', '', PARAM_TEXT);
        return new moodle_url('/local/userlist/index.php',
            ['search' => $search]);
    }

    /**
     * @inheritDoc
     */
    protected function definition()
    {
        $mform = $this->_form;

        $mform->addElement('text', 'search', get_string('search'));
        $mform->setType('search',PARAM_TEXT);

        $this->add_action_buttons(false, get_string( 'submit'));
    }
}