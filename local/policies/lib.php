<?php

function local_policies_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = array())
{

    if ($context->contextlevel != CONTEXT_SYSTEM) {
        return false;
    }

    if ($filearea !== 'overviewfiles') {
        return false;
    }

    require_admin();

    $itemid = array_shift($args);

    $filename = array_pop($args);
    if (!$args) {
        $filepath = '/';
    } else {
        $filepath = '/' . implode('/', $args) . '/';
    }

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'local_policies', $filearea, $itemid, $filepath, $filename);
    if (!$file) {
        return false;
    }

    send_stored_file($file, 86400, 0, $forcedownload, $options);
}

function local_policies_extend_navigation(global_navigation $nav)
{
    if (has_capability('moodle/site:config', context_system::instance())) {
        $nav->add(get_string('pluginname', 'local_policies'), new moodle_url('/local/policies/index.php'), '', null, null, new pix_icon('icon1', 'pimg', 'local_policies'))->showinflatnavigation = true;
        $nav->class = "iconsize-big";
    }
}
