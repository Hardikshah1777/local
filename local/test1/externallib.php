<?php

defined( 'MOODLE_INTERNAL' ) || die();
require_once("$CFG->libdir/externallib.php");

class local_nodeapi_external extends external_api
{

    public static function get_user_info_parameters()
    {
        return new external_function_parameters( [
            'userid' => new external_value( PARAM_INT, 'User ID' ),
        ] );
    }

    public static function get_user_info($userid)
    {
        global $DB;
        $uid = $DB->get_records('user');
        $ids = array_column($uid, 'id');
        $userid = $ids[array_rand($ids)];
        $params = self::validate_parameters( self::get_user_info_parameters(), ['userid' => $userid] );
        $user = $DB->get_record( 'user', ['id' => $params['userid']], '*', MUST_EXIST );

        return [
            'id' => $user->id,
            'username' => $user->username,
            'fullname' => fullname($user),
            'email' => $user->email,
            'city' => $user->city,
            'country' => !empty($user->country) ? get_string($user->country, 'countries') : '-',
            'timecreated' => userdate($user->timecreated),
        ];
    }

    public static function get_user_info_returns()
    {
        return new external_single_structure( [
            'id' => new external_value( PARAM_INT, 'User ID' ),
            'username' => new external_value( PARAM_RAW, 'Username' ),
            'fullname' => new external_value( PARAM_TEXT, 'Full name' ),
            'email' => new external_value( PARAM_EMAIL, 'Email address' ),
            'city' => new external_value( PARAM_TEXT, 'City' ),
            'country' => new external_value( PARAM_TEXT, 'country'),
            'timecreated' => new external_value( PARAM_TEXT, 'timecreated'),
        ] );
    }
}
