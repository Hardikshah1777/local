<?php

namespace theme_remui_child\output;

use theme_remui\toolbox;

abstract class _core_renderer extends \theme_remui\output\core_renderer {
    const themename = '';

    public $remui = true;

    public function __construct(\moodle_page $page, $target) {
        parent::__construct($page, $target);
        $this->themeconfig[] = \theme_config::load(static::themename);
    }

    public function should_display_logo() {
        $context = parent::should_display_logo();

        $logo = toolbox::setting_file_url('logo', 'logo');
        $logomini = toolbox::setting_file_url('logomini', 'logomini');

        if (!empty($logo)) {
            $context['islogo'] = true;
            $context['logourl'] = $logo;
            $context['logominiurl'] = $logomini;
            $context['logolink'] = new \moodle_url('/');
            $context['isiconsitename'] = false;
        }

        return $context;
    }

    public static function override_config($config) {
        $config->logoorsitename = 'logo';
        $config->sitecolorhex = $config->brandcolor;
        $config->{'footer-background-color'} = $config->brandcolor;
        //$config->{'header-menu-background-color'} = $config->brandcolor;
    }
}