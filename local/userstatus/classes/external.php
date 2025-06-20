<?php

namespace local_userstatus;

require_once $CFG->dirroot . '/message/externallib.php';

class external extends \core_message_external {
    const placeholders =[
            'placeholder:firstname',
            'placeholder:lastname',
    ];
    private static $placeholders = [];

    public static function formatmesssage($user,$mailbody) {
        if(!self::$placeholders){
            foreach (self::placeholders as $placeholder){
                self::$placeholders[] = get_string($placeholder,status::component);
            }
        }
        $mailbody = str_replace(
                self::$placeholders,
                [$user->firstname,$user->lastname],
                $mailbody
        );
        return $mailbody;
    }
    public static function send_instant_messages($messages = array()) {
        global $DB;
        $receivers = array_column($messages,'touserid');
        list($sqluserids, $sqlparams) = $DB->get_in_or_equal($receivers);

        $tousers = $DB->get_records_select("user", "id " . $sqluserids . " AND deleted = 0", $sqlparams);

        foreach ($messages as $id => $message) {
            if(array_key_exists($message['touserid'],$tousers)) {
                $messages[$id]['text'] = self::formatmesssage($tousers[$message['touserid']],$message['text']);
            }
        }

        return parent::send_instant_messages($messages);
    }
}
