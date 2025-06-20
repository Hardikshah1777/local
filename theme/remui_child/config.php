<?php


defined('MOODLE_INTERNAL') || die();


$THEME->doctype = 'html5';

$THEME->name = 'remui_child';

//$THEME->sheets = array('style','font-iomad','iomad');
$THEME->sheets = array('custom');

// $THEME->editor_sheets = [];

$THEME->parents = ['remui'];

$THEME->enable_dock = false;

$THEME->yuicssmodules = array();

$THEME->javascripts = array('remui_child');

$THEME->rendererfactory = 'theme_overridden_renderer_factory';

$THEME->csspostprocess = 'theme_remui_process_css';

$THEME->requiredblocks = '';

$THEME->addblockposition = BLOCK_ADDBLOCK_POSITION_FLATNAV;

// $THEME->iconsystem = \core\output\icon_system::FONTAWESOME;

$THEME->javascripts_footer = array('ebscohostsearch2');
