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
 * @package   assignsubmission_onlinepoodll
 * @copyright 2018 Justin Hunt {@link http://www.poodll.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


namespace assignsubmission_onlinepoodll;

defined('MOODLE_INTERNAL') || die();

class constants
{

const M_FILEAREA = 'submissions_onlinepoodll';
const M_COMPONENT='assignsubmission_onlinepoodll';
const M_CONFIG_COMPONENT='assignsubmission_onlinepoodll';
const M_TABLE='assignsubmission_onlinepoodl';
const M_WB_FILEAREA = 'onlinepoodll_backimage';

const M_REPLYMP3VOICE=0;
const M_REPLYVOICE=1;
const M_REPLYVIDEO=2;
const M_REPLYWHITEBOARD=3;
const M_REPLYSNAPSHOT=4;
const M_REPLYTALKBACK=5;
const M_FILENAMECONTROL='onlinepoodll';
const M_VECTORCONTROL='onlinepoodll_vector';
const M_CURRENTSUBMISSION_HIDE=0;
const M_CURRENTSUBMISSION_SHOWMINIMIZED=1;
const M_CURRENTSUBMISSION_SHOWEXPANDED=2;
const M_CURRENTSUBMISSION_ALWAYS=3;
}