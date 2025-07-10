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
 * This file defines the admin settings for this plugin
 *
 * @package   assignsubmission_onlinepoodll
 * @copyright 2012 Justin Hunt {@link http://www.poodll.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use assignsubmission_onlinepoodll\constants;
use assignsubmission_onlinepoodll\utils;

	//enable by default
	$settings->add(new admin_setting_configcheckbox(constants::M_COMPONENT . '/default',
                   new lang_string('default', constants::M_COMPONENT),
                   new lang_string('default_help', constants::M_COMPONENT), 0));
                   

	//Recorders
   $rec_options = utils::fetch_options_recorders();
	$rec_defaults = array(constants::M_REPLYMP3VOICE  => 1, constants::M_REPLYVIDEO => 1 , constants::M_REPLYVOICE => 1,constants::M_REPLYWHITEBOARD => 1,constants::M_REPLYSNAPSHOT => 1);
	$settings->add(new admin_setting_configmulticheckbox(constants::M_COMPONENT . '/allowedrecorders',
						   get_string('allowedrecorders', constants::M_COMPONENT),
						   get_string('allowedrecordersdetails', constants::M_COMPONENT), $rec_defaults,$rec_options));
						   
	//show current submission on submission form
	$cs_options = utils::fetch_options_currentsubmission();
	$settings->add(new admin_setting_configselect(constants::M_COMPONENT . '/showcurrentsubmission',
					new lang_string('showcurrentsubmission', constants::M_COMPONENT),
					new lang_string('showcurrentsubmissiondetails', constants::M_COMPONENT),
            constants::M_CURRENTSUBMISSION_SHOWMINIMIZED, $cs_options));

	//allow user to set a custom name for the plugin as displayed to users
    $settings->add(new admin_setting_configtext(constants::M_COMPONENT . '/customname',
            new lang_string('customname', constants::M_COMPONENT),
            new lang_string('customnamedetails', constants::M_COMPONENT),
            '', PARAM_TEXT));

    //Settings for audio recordings
    $settings->add(new admin_setting_heading(constants::M_COMPONENT . '/audio_heading',
    get_string('setting_audio_heading', constants::M_COMPONENT),
    get_string('setting_audio_heading_details', constants::M_COMPONENT)));

    $yesno_options=utils::fetch_options_yesno();
    $settings->add(new admin_setting_configselect(constants::M_COMPONENT . '/displayaudioplayer_single',
    new lang_string('displayaudioplayersingle', constants::M_COMPONENT),
    '', '1', $yesno_options));

    $settings->add(new admin_setting_configselect(constants::M_COMPONENT . '/displayaudioplayer_list',
    new lang_string('displayaudioplayerlist', constants::M_COMPONENT),
    '', '1', $yesno_options));



//Settings for video recordings
    $settings->add(new admin_setting_heading('assignsubmission_onlinepoodll/video_heading',
        get_string('setting_video_heading', 'assignsubmission_onlinepoodll'),
        get_string('setting_video_heading_details', 'assignsubmission_onlinepoodll')));


    //The size of the video player on the various screens
	$size_options = array('0' => new lang_string('shownovideo', 'assignsubmission_onlinepoodll'),
					'480' => '480x360');
				
	$settings->add(new admin_setting_configselect('assignsubmission_onlinepoodll/displaysize_single', 
						new lang_string('displaysizesingle', 'assignsubmission_onlinepoodll'), 
						new lang_string('displaysizesingledetails', 'assignsubmission_onlinepoodll'), '480', $size_options));

	$settings->add(new admin_setting_configselect('assignsubmission_onlinepoodll/displaysize_list', 
						new lang_string('displaysizelist', 'assignsubmission_onlinepoodll'), 
						new lang_string('displaysizelistdetails', 'assignsubmission_onlinepoodll'), '480', $size_options));

    //The width of an image
    $width_options = array('0' => new lang_string('shownoimage', 'assignsubmission_onlinepoodll'),
        '160' => '160', '320' => '320','480' => '480',
        '640' => '640','800'=>'800','1024'=>'1024');

    //Settings for snapshots
    $settings->add(new admin_setting_heading('assignsubmission_onlinepoodll/snapshot_heading',
        get_string('setting_snapshot_heading', 'assignsubmission_onlinepoodll'),
        get_string('setting_snapshot_heading_details', 'assignsubmission_onlinepoodll')));

    //snapshot
    $settings->add(new admin_setting_configselect('assignsubmission_onlinepoodll/snapshot_displaysize_single',
        new lang_string('imagedisplaysizesingle', 'assignsubmission_onlinepoodll'),
        new lang_string('imagedisplaysizesingledetails', 'assignsubmission_onlinepoodll'), '320', $width_options));

    $settings->add(new admin_setting_configselect('assignsubmission_onlinepoodll/snapshot_displaysize_list',
        new lang_string('imagedisplaysizelist', 'assignsubmission_onlinepoodll'),
        new lang_string('imagedisplaysizelistdetails', 'assignsubmission_onlinepoodll'), '160', $width_options));

    //Settings for whiteboard images
    $settings->add(new admin_setting_heading('assignsubmission_onlinepoodll/whiteboard_heading',
        get_string('setting_whiteboard_heading', 'assignsubmission_onlinepoodll'),
        get_string('setting_whiteboard_heading_details', 'assignsubmission_onlinepoodll')));

    //whiteboard
    $settings->add(new admin_setting_configselect('assignsubmission_onlinepoodll/whiteboard_displaysize_single',
        new lang_string('imagedisplaysizesingle', 'assignsubmission_onlinepoodll'),
        new lang_string('imagedisplaysizesingledetails', 'assignsubmission_onlinepoodll'), '320', $width_options));

    $settings->add(new admin_setting_configselect('assignsubmission_onlinepoodll/whiteboard_displaysize_list',
        new lang_string('imagedisplaysizelist', 'assignsubmission_onlinepoodll'),
        new lang_string('imagedisplaysizelistdetails', 'assignsubmission_onlinepoodll'), '160', $width_options));