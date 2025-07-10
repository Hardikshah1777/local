<?php


function local_coursenotify_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, $options){
    global $DB;

    if ($context->contextlevel != CONTEXT_COURSE || $filearea != local_coursenotify\utility::$filearea
        || !$notificationid = (int)array_shift($args)) {
        return false;
    }

    if (!$DB->record_exists('local_coursenotify', array('id'=>$notificationid,'status'=>1))) {
        return false;
    }

    $fullpath = "/{$context->id}/local_coursenotify/{$filearea}/{$notificationid}/".implode('/', $args);

    $fs = get_file_storage();
    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        return false;
    }

    send_stored_file($file, 0, 0, $forcedownload, $options);
}

function local_coursenotify_extend_navigation_course(navigation_node $parentnode, stdClass $course, context_course $context) {
    if (!has_capability('local/coursenotify:editnotification', $context)) {
        return false;
    }
    $action = new moodle_url( '/local/coursenotify/index.php', array('courseid'=>$course->id));
    $icon = new pix_icon('i/folder', '');
    $nodestr = get_string('coursenotification', local_coursenotify\utility::$component);
    $parentnode->add($nodestr, $action, navigation_node::TYPE_CUSTOM, $nodestr, 'local_coursenotify',$icon);
}
