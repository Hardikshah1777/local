<?php


defined('MOODLE_INTERNAL') || die();

$THEME->doctype = 'html5';

$THEME->name = 'remui_child_workforcestaffing';

$THEME->sheets = array('style','workforcestaffing');


// $THEME->editor_sheets = [];

$THEME->parents = ['remui'];

$THEME->enable_dock = false;

$THEME->yuicssmodules = array();

$THEME->javascripts = array('remui_child_workforcestaffing');

$THEME->rendererfactory = 'theme_overridden_renderer_factory';

$THEME->csspostprocess = 'theme_remui_child_workforcestaffing_process_css';

$THEME->requiredblocks = '';

$THEME->addblockposition = BLOCK_ADDBLOCK_POSITION_FLATNAV;

// $THEME->iconsystem = \core\output\icon_system::FONTAWESOME;

\theme_remui_child\output\_core_renderer::override_config($THEME->settings);