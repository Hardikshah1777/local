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
 * postcode profile field definition.
 *
 * @package    profilefield_licensenumber
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Class profile_define_licensenumber
 *
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class profile_define_licensenumber extends profile_define_base {

    /**
     * Add elements for creating/editing a text profile field.
     * @param moodleform $form
     */
    public function define_form_specific($form) {
        // Param 1 for text type is the size of the field.
        $form->addElement('text', 'param1', get_string('box1size', 'profilefield_licensenumber'), 'size="3"');
        $form->setDefault('param1', 3);
        $form->setType('param1', PARAM_SEQUENCE);

        // Param 2 for text type is the maxlength of the field.
        $form->addElement('text', 'param2', get_string('box2size', 'profilefield_licensenumber'), 'size="3"');
        $form->setDefault('param2', 3);
        $form->setType('param2', PARAM_INT);

    }
}
