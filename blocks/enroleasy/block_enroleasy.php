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
 * Enroleasy block isntance.
 *
 * @package    block_enroleasy
 * @copyright  2021 Lukas Celinak <lukascelinak@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_enroleasy extends block_base {

    /**
     * Initializes class member variables.
     */
    public function init() {
        // Needed by Moodle to differentiate between blocks.
        $this->title = get_string('pluginname', 'block_enroleasy');
        $this->hidetitle = 0;
    }

    /**
     * Returns the block contents.
     *
     * @return stdClass The block contents.
     */
    public function get_content() {

        if ($this->content !== null) {
            return $this->content;
        }

        if (empty($this->instance)) {
            $this->content = '';
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->items = array();
        $this->content->icons = array();
        $this->content->footer = '';

        $text = "";
        $plugin = enrol_get_plugin('easy');
        if ($plugin && !isguestuser()) {
            $text = $plugin->get_form();
        }
        $this->content->text = $text;
        return $this->content;
    }

    /**
     * Defines configuration data.
     *
     * The function is called immediatly after init().
     */
    public function specialization() {
        // Load user defined title and make sure it's never empty.
        if (empty($this->config->title)) {
            $this->title = get_string('title', 'block_enroleasy');
        } else {
            $this->title = $this->config->title;
        }

        // Load title hide config and make sure it has default value.
        if (empty($this->config->hidetitle)) {
            $this->hidetitle = 0;
        } else {
            $this->hidetitle = $this->config->hidetitle;
        }
    }

    /**
     * Core function, specifies where the block can be used.
     * @return array
     */
    public function applicable_formats() {
        return array('all' => true);
    }

    /**
     * All multiple instances of this block
     * @return bool Returns false
     */
    public function instance_allow_multiple() {
        return false;
    }

    /**
     * Has config
     * @return boolean
     */
    public function has_config() {
        return false;
    }

    /**
     * Hide header based on block config
     *
     * @return boolean
     */
    public function hide_header() {
        if ($this->hidetitle != 1) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Tests if this block has been implemented correctly.
     * Also, $errors isn't used right now
     *
     * @return boolean
     */
    public function _self_test() {
        return true;
    }

}
