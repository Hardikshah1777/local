<?php


function theme_remui_child_nedbank_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = array()) {
    if ($context->contextlevel != CONTEXT_SYSTEM) {
        send_file_not_found();
    }
    // By default, theme files must be cache-able by both browsers and proxies.
    $settings = [
        'logo',
        'logomini',
    ];
    if (in_array($filearea, $settings)) {
        $theme = theme_config::load('remui_child_nedbank');
        // By default, theme files must be cache-able by both browsers and proxies.
        if (!array_key_exists('cacheability', $options)) {
            $options['cacheability'] = 'public';
        }
        return $theme->setting_file_serve($filearea, $args, $forcedownload, $options);
    } else {
        $itemid = (int)array_shift($args);
        $relativepath = implode('/', $args);
        $fullpath = "/{$context->id}/theme_remui_child_nedbank/$filearea/$itemid/$relativepath";
        $fs = get_file_storage();
        if (!($file = $fs->get_file_by_hash(sha1($fullpath)))) {
            return false;
        }
        // Download MUST be forced - security!
        send_stored_file($file, 0, 0, $forcedownload, $options);
    }
    return false;
}

function theme_remui_child_nedbank_process_css($css, $theme) {
    global $PAGE;
    $outputus = $PAGE->get_renderer('theme_remui_child_nedbank', 'core');
    \theme_remui\toolbox::set_core_renderer($outputus);

    return theme_remui_process_css($css, $theme);
}