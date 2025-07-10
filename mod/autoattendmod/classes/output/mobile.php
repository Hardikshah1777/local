<?php
namespace mod_autoattendmod\output;
 
defined('MOODLE_INTERNAL') || die();
 
use context_module;
 
/**
 * Mobile output class for autoattend
 *
 * @package    mod_autoattendmod
 * @copyright  2019 Fumi Iseki
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mobile
{
    public static function mobile_course_view($args)
    {
        global $OUTPUT, $USER, $DB;
 
        $args = (object) $args;
        $cm = get_coursemodule_from_id('autoattendmod', $args->cmid);
 
        // Capabilities check.
        require_login($args->courseid , false , $cm, true, true);
 
        $context = context_module::instance($cm->id);
 
        require_capability ('mod/autoattendmod:view', $context);
        if ($args->userid != $USER->id) {
            require_capability('mod/autoattendmod:manage', $context);
        }
        $autoattendmod = $DB->get_record('autoattendmod', array('id' => $cm->instance));
 

        $data = array(
            'autoattendmod' => $autoattendmod,
            'cmid'     => $cm->id,
            'courseid' => $args->courseid
        );
 
        return [
            'templates' => [
                [
                    'id'   => 'main',
                    'html' => $OUTPUT->render_from_template('mod_autoattendmod/mobile_course_view', $data),
                ],
            ],
            'javascript' => '',
            'otherdata' => '',
        ];
    }

}
