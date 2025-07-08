<?php

defined('MOODLE_INTERNAL') || die;

require_once("$CFG->libdir/externallib.php");
require_once(dirname(__FILE__).'/classes/autoattendmod_handler.php');

/**
 * Class mod_wsattendance_external
 * @copyright  2015 Caio Bressan Doneda
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_autoattendmod_external extends external_api 
{

    /**
     * Get parameter list.
     * @return external_function_parameters
     */
    public static function get_attendances() {
        return "HELL World!!";
    }

    /**
     * Get list of courses with active sessions for today.
     * @param int $userid
     * @return array
     */
    public static function get_attendances_handler($userid) {
        return autoattendmod_handler::get_attendances_handler($userid);
    }

}
