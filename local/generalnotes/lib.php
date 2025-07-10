<?php

function local_generalnotes_profile_tabs($userid) {
    if(isloggedin()){
        $args = new stdClass;
        $args->context   = context_user::instance($userid);
        $args->linktext  = get_string('showcomments');
        $commentbox = new local_generalnotes_comment($args);
        if($commentbox->can_add()){
            return [
                'id' => 'local_generalnotes',
                'name' => get_string('tabname','local_generalnotes'),
                'html' => $commentbox->output(true),
            ];
        }
        return [];
    }
}

/**
 * Add general notes to myprofile page.
 *
 * @param \core_user\output\myprofile\tree $tree Tree object
 * @param stdClass $user user object
 * @param bool $iscurrentuser
 * @param stdClass $course Course object
 * @return bool
 */
function local_generalnotes_myprofile_navigation($tree, $user, $iscurrentuser, $course) {
    if(!$iscurrentuser &&
            has_capability(local_generalnotes_comment::cap,
                    context_system::instance())) {
        $url = new moodle_url('/local/generalnotes/notes.php', array('id' => $user->id));
        $node = new core_user\output\myprofile\node('miscellaneous', 'generalnotes',
                get_string('tabname', local_generalnotes_comment::TABLE),
                null, $url);
        $tree->add_node($node);
    }
    return true;
}
