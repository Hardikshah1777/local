<?php



$functions = array(
        'local_userstatus_send_instant_messages' => array(
                'classname' => 'local_userstatus\external',
                'methodname' => 'send_instant_messages',
                'description' => 'Send instant messages',
                'type' => 'write',
                'capabilities' => 'moodle/site:sendmessage',
                'ajax' => true,
                'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
        )
);
