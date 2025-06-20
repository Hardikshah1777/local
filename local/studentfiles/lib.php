<?php

function local_studentfiles_extend_navigation(global_navigation $nav){
    if(isloggedin()) {
        $link = new moodle_url('/local/studentfiles/index.php');
        $title = \local_studentfiles\util::get_string('pluginname');
        $nav->add($title, $link, navigation_node::TYPE_CUSTOM,
                $title,\local_studentfiles\util::component,
                new pix_icon('icon',$title,\local_studentfiles\util::component)
        )->showinflatnavigation = true;
    }
}

function local_studentfiles_get_fontawesome_icon_map(){
    return [
        'local_studentfiles:icon' => 'fa-file-pdf-o',
    ];
}

function local_studentfiles_pluginfile($course,$cm, $context, $filearea, $args, $forcedownload, array $options = array()) {
    global $USER;
    $userid = $USER->id;

    if ($context->contextlevel != CONTEXT_USER) {
        return false;
    }

    if ($filearea != \local_studentfiles\util::filearea) {
        return false;
    }

    require_login($course);

    if($context->instanceid != $userid && !\local_studentfiles\util::user_can_access($userid)) {
        return false;
    }

    $relativepath = implode('/', $args);

    $fullpath = "/{$context->id}/local_studentfiles/$filearea/$relativepath";

    $fs = get_file_storage();
    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        return false;
    }
    send_stored_file($file, 0, 0, $forcedownload, $options);
}

function local_studentfiles_myprofile_navigation(core_user\output\myprofile\tree $tree, $user, $iscurrentuser, $course) {
    if($iscurrentuser || local_studentfiles\util::user_can_access()) {
        $url = new moodle_url('/local/studentfiles/index.php');
        if(!$iscurrentuser) {
            $url->param('userid', $user->id);
        }
        $node = new core_user\output\myprofile\node('miscellaneous', 'studentfiles',
                \local_studentfiles\util::get_string('pluginname'), null, $url);
        $tree->add_node($node);
    }
}
