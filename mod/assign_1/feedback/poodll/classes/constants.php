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
 * @package   assignfeedback_poodll
 * @copyright 2018 Justin Hunt {@link http://www.poodll.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


namespace assignfeedback_poodll;

defined('MOODLE_INTERNAL') || die();

class constants
{

const M_FILEAREA = 'poodll_files';
const M_COMPONENT='assignfeedback_poodll';
const M_TABLE='assignfeedback_poodll';
const M_SUBPLUGIN='poodll';

const M_REPLYMP3VOICE=0;
const M_REPLYVOICE=1;
const M_REPLYVIDEO=2;
const M_REPLYWHITEBOARD=3;
const M_REPLYSNAPSHOT=4;
const M_FILENAMECONTROL='poodllfeedback';
}