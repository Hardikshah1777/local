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
 *
 *
 * @package   assignfeedback_onlinepoodll
 * @copyright 2018 Justin Hunt {@link http://www.poodll.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


namespace assignfeedback_poodll;


defined('MOODLE_INTERNAL') || die();

class utils
{

    public static function fetch_options_recorders(){
        return  array( constants::M_REPLYMP3VOICE => get_string("replymp3voice", constants::M_COMPONENT),
            constants::M_REPLYVIDEO => get_string("replyvideo", constants::M_COMPONENT),
            constants::M_REPLYWHITEBOARD => get_string("replywhiteboard", constants::M_COMPONENT),
            constants::M_REPLYSNAPSHOT => get_string("replysnapshot", constants::M_COMPONENT));
    }

    public static function fetch_options_yesno(){
        //show current submission on submission form
        return array(0 => get_string("no", constants::M_COMPONENT),
                1=>get_string("yes", constants::M_COMPONENT));
    }

}