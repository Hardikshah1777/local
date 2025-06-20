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
 * behaviour plugin settings
 *
 * @package    mod_behaviour
 * @copyright  2013 Netspot, Tim Lock.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    require_once(dirname(__FILE__).'/lib.php');
    require_once(dirname(__FILE__).'/locallib.php');
    require_once($CFG->dirroot . '/user/profile/lib.php');

    $tabmenu = behaviour_print_settings_tabs();

    $settings->add(new admin_setting_heading('behaviour_header', '', $tabmenu));

    $plugininfos = core_plugin_manager::instance()->get_plugins_of_type('local');

    // Paging options.
    $options = array(
          0 => get_string('donotusepaging', 'behaviour'),
         25 => 25,
         50 => 50,
         75 => 75,
         100 => 100,
         250 => 250,
         500 => 500,
         1000 => 1000,
    );

    $settings->add(new admin_setting_configselect('behaviour/resultsperpage',
        get_string('resultsperpage', 'behaviour'), get_string('resultsperpage_desc', 'behaviour'), 25, $options));

    $settings->add(new admin_setting_configcheckbox('behaviour/studentscanmark',
        get_string('studentscanmark', 'behaviour'), get_string('studentscanmark_desc', 'behaviour'), 1));

    $settings->add(new admin_setting_configtext('behaviour/rotateqrcodeinterval',
        get_string('rotateqrcodeinterval', 'behaviour'),
        get_string('rotateqrcodeinterval_desc', 'behaviour'), '15', PARAM_INT));

    $settings->add(new admin_setting_configtext('behaviour/rotateqrcodeexpirymargin',
            get_string('rotateqrcodeexpirymargin', 'behaviour'),
            get_string('rotateqrcodeexpirymargin_desc', 'behaviour'), '2', PARAM_INT));

    $settings->add(new admin_setting_configcheckbox('behaviour/studentscanmarksessiontime',
        get_string('studentscanmarksessiontime', 'behaviour'),
        get_string('studentscanmarksessiontime_desc', 'behaviour'), 1));

    $settings->add(new admin_setting_configtext('behaviour/studentscanmarksessiontimeend',
        get_string('studentscanmarksessiontimeend', 'behaviour'),
        get_string('studentscanmarksessiontimeend_desc', 'behaviour'), '60', PARAM_INT));

    $settings->add(new admin_setting_configcheckbox('behaviour/subnetactivitylevel',
        get_string('subnetactivitylevel', 'behaviour'),
        get_string('subnetactivitylevel_desc', 'behaviour'), 1));

    $options = array(
        ATT_VIEW_ALL => get_string('all', 'behaviour'),
        ATT_VIEW_ALLPAST => get_string('allpast', 'behaviour'),
        ATT_VIEW_NOTPRESENT => get_string('below', 'behaviour', 'X'),
        ATT_VIEW_MONTHS => get_string('months', 'behaviour'),
        ATT_VIEW_WEEKS => get_string('weeks', 'behaviour'),
        ATT_VIEW_DAYS => get_string('days', 'behaviour')
    );

    $settings->add(new admin_setting_configselect('behaviour/defaultview',
        get_string('defaultview', 'behaviour'),
            get_string('defaultview_desc', 'behaviour'), ATT_VIEW_WEEKS, $options));

    $settings->add(new admin_setting_configcheckbox('behaviour/multisessionexpanded',
        get_string('multisessionexpanded', 'behaviour'),
        get_string('multisessionexpanded_desc', 'behaviour'), 0));

    $settings->add(new admin_setting_configcheckbox('behaviour/showsessiondescriptiononreport',
        get_string('showsessiondescriptiononreport', 'behaviour'),
        get_string('showsessiondescriptiononreport_desc', 'behaviour'), 0));

    $settings->add(new admin_setting_configcheckbox('behaviour/studentrecordingexpanded',
        get_string('studentrecordingexpanded', 'behaviour'),
        get_string('studentrecordingexpanded_desc', 'behaviour'), 1));

    $settings->add(new admin_setting_configcheckbox('behaviour/enablecalendar',
        get_string('enablecalendar', 'behaviour'),
        get_string('enablecalendar_desc', 'behaviour'), 1));

    $settings->add(new admin_setting_configcheckbox('behaviour/enablewarnings',
        get_string('enablewarnings', 'behaviour'),
        get_string('enablewarnings_desc', 'behaviour'), 0));

    $settings->add(new admin_setting_configcheckbox('behaviour/automark_useempty',
        get_string('automarkuseempty', 'behaviour'),
        get_string('automarkuseempty_desc', 'behaviour'), 1));

    $fields = array('id' => get_string('studentid', 'behaviour'));
    $customfields = profile_get_custom_fields();
    foreach ($customfields as $field) {
        $fields[$field->shortname] = format_string($field->name);
    }

    $settings->add(new admin_setting_configmultiselect('behaviour/customexportfields',
            new lang_string('customexportfields', 'behaviour'),
            new lang_string('customexportfields_help', 'behaviour'),
            array('id'), $fields)
    );

    $name = new lang_string('mobilesettings', 'mod_behaviour');
    $description = new lang_string('mobilesettings_help', 'mod_behaviour');
    $settings->add(new admin_setting_heading('mobilesettings', $name, $description));

    $settings->add(new admin_setting_configduration('behaviour/mobilesessionfrom',
        get_string('mobilesessionfrom', 'behaviour'), get_string('mobilesessionfrom_help', 'behaviour'),
         6 * HOURSECS, PARAM_RAW));

    $settings->add(new admin_setting_configduration('behaviour/mobilesessionto',
        get_string('mobilesessionto', 'behaviour'), get_string('mobilesessionto_help', 'behaviour'),
        24 * HOURSECS, PARAM_RAW));

    $name = new lang_string('defaultsettings', 'mod_behaviour');
    $description = new lang_string('defaultsettings_help', 'mod_behaviour');
    $settings->add(new admin_setting_heading('defaultsettings', $name, $description));

    $settings->add(new admin_setting_configtext('behaviour/subnet',
        get_string('requiresubnet', 'behaviour'), get_string('requiresubnet_help', 'behaviour'), '', PARAM_RAW));

    $name = new lang_string('defaultsessionsettings', 'mod_behaviour');
    $description = new lang_string('defaultsessionsettings_help', 'mod_behaviour');
    $settings->add(new admin_setting_heading('defaultsessionsettings', $name, $description));

    $settings->add(new admin_setting_configcheckbox('behaviour/calendarevent_default',
        get_string('calendarevent', 'behaviour'), '', 1));

    $settings->add(new admin_setting_configcheckbox('behaviour/absenteereport_default',
        get_string('includeabsentee', 'behaviour'), '', 1));

    $settings->add(new admin_setting_configcheckbox('behaviour/studentscanmark_default',
        get_string('studentscanmark', 'behaviour'), '', 0));

    $options = behaviour_get_automarkoptions();

    $settings->add(new admin_setting_configselect('behaviour/automark_default',
        get_string('automark', 'behaviour'), '', 0, $options));

    $settings->add(new admin_setting_configduration('behaviour/studentsearlyopentime',
        get_string('studentsearlyopentime', 'behaviour'), get_string('studentsearlyopentime_help', 'behaviour'), 0));

    $settings->add(new admin_setting_configcheckbox('behaviour/randompassword_default',
        get_string('randompassword', 'behaviour'), '', 0));

    $settings->add(new admin_setting_configcheckbox('behaviour/includeqrcode_default',
        get_string('includeqrcode', 'behaviour'), '', 0));

    $settings->add(new admin_setting_configcheckbox('behaviour/rotateqrcode_default',
        get_string('rotateqrcode', 'behaviour'), '', 0));

    $settings->add(new admin_setting_configcheckbox('behaviour/autoassignstatus',
        get_string('autoassignstatus', 'behaviour'), '', 0));

    $options = behaviour_get_sharedipoptions();
    $settings->add(new admin_setting_configselect('behaviour/preventsharedip',
        get_string('preventsharedip', 'behaviour'),
        '', BEHAVIOUR_SHAREDIP_DISABLED, $options));

    $settings->add(new admin_setting_configtext('behaviour/preventsharediptime',
        get_string('preventsharediptime', 'behaviour'), get_string('preventsharediptime_help', 'behaviour'), '', PARAM_RAW));

    $name = new lang_string('defaultwarningsettings', 'mod_behaviour');
    $description = new lang_string('defaultwarningsettings_help', 'mod_behaviour');
    $settings->add(new admin_setting_heading('defaultwarningsettings', $name, $description));

    $options = array();
    for ($i = 1; $i <= 100; $i++) {
        $options[$i] = "$i%";
    }
    $settings->add(new admin_setting_configselect('behaviour/warningpercent',
        get_string('warningpercent', 'behaviour'), get_string('warningpercent_help', 'behaviour'), 70, $options));

    $options = array();
    for ($i = 1; $i <= 50; $i++) {
        $options[$i] = "$i";
    }
    $settings->add(new admin_setting_configselect('behaviour/warnafter',
        get_string('warnafter', 'behaviour'), get_string('warnafter_help', 'behaviour'), 5, $options));

    $settings->add(new admin_setting_configselect('behaviour/maxwarn',
        get_string('maxwarn', 'behaviour'), get_string('maxwarn_help', 'behaviour'), 1, $options));

    $settings->add(new admin_setting_configcheckbox('behaviour/emailuser',
        get_string('emailuser', 'behaviour'), get_string('emailuser_help', 'behaviour'), 1));

    $settings->add(new admin_setting_configtext('behaviour/emailsubject',
        get_string('emailsubject', 'behaviour'), get_string('emailsubject_help', 'behaviour'),
        get_string('emailsubject_default', 'behaviour'), PARAM_RAW));


    $settings->add(new admin_setting_configtextarea('behaviour/emailcontent',
        get_string('emailcontent', 'behaviour'), get_string('emailcontent_help', 'behaviour'),
        get_string('emailcontent_default', 'behaviour'), PARAM_RAW));
}
