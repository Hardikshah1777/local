<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * The block_enroleasy for Easy enrollment method.
 *
 * @package     block_enroleasy
 * @copyright   2021 Lukas Celinak <lukascelinak@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Form for editing of Enroleasy block instances.
 *
 * @package    block_enroleasy
 * @copyright  2021 Lukas Celinak <lukascelinak@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_enroleasy_edit_form extends block_edit_form {

    /**
     * The definition of the fields to use.
     *
     * @param MoodleQuickForm $mform
     */
    protected function specific_definition($mform) {
        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

        $mform->addElement('text', 'config_title', get_string('configtitle', 'block_enroleasy'));
        $mform->setType('config_title', PARAM_TEXT);

        $mform->addElement('selectyesno', 'config_hidetitle', get_string('confighidetitle', 'block_enroleasy'));
        $mform->setType('config_hidetitle', PARAM_INT);
    }

    /**
     * The definition of the default config data.
     *
     * @param object $defaults
     */
    public function set_data($defaults) {
        if (!$this->block->user_can_edit() && !empty($this->block->config->title)) {
            // If a title has been set but the user cannot edit it format it nicely.
            $title = $this->block->config->title;
            $hidetitle = $this->block->config->hidetitle;
            $defaults->config_title = format_string($title, true, $this->page->context);
            $defaults->config_hidetitle = $this->block->config->hidetitle;
            // Remove the title from the config so that parent::set_data doesn't set it.
            unset($this->block->config->title);
        }

        parent::set_data($defaults);
        // Restore $text.
        if (!isset($this->block->config)) {
            $this->block->config = new stdClass();
        }
        if (isset($hidetitle)) {
            // Reset the preserved title.
            $this->block->config->hidetitle = $hidetitle;
        }
        if (isset($title)) {
            // Reset the preserved title.
            $this->block->config->title = $title;
        }
    }

}