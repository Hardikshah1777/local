<?php

namespace local_coursenotify;

use \core\event\course_completed;
use core_user;
use stdClass;

class coursecompletion_mail {
    public static function coursecomp_mailtomanager(course_completed $event){
        global $DB;
        $touser = core_user::get_user($event->relateduserid);
        $from = core_user::get_support_user();

        $course = get_course($event->courseid);

        $sql = 'Select uinfo.id,uinfo.userid,uinfo.data from {user_info_data} uinfo where uinfo.fieldid = 1 AND uinfo.userid = '.$event->relateduserid;
        $manger = $DB->get_records_sql($sql);
        foreach ($manger as $user){
            if($user->data != NUll ){
                    $params = [];
                    $params['courseid'] = $event->courseid;
                    $params['userid'] = $event->relateduserid;
                    $params['course'] = 'course';

                    $usersql =
                            'Select gg.id,gg.*,gi.* from {grade_grades} gg JOIN {grade_items} gi  ON gi.id = gg.itemid  Where gi.courseid = :courseid and gi.itemtype = :course AND gg.userid = :userid ';
                    $userinfo = $DB->get_records_sql($usersql, $params);
                    if ($userinfo) {
                        foreach ($userinfo as $row) {
                            $per = ($row->finalgrade / $row->rawgrademax) * 100;
                        }
                        $a = new stdClass();
                        $a->firstname = $touser->firstname;
                        $a->coursename = $course->fullname;
                        $a->per = round($per,2);
                    }

                $certparam  = [];
                $certparam['userid'] = $event->relateduserid;
                $certparam['courseid'] = $event->courseid;
                $usercertsql = 'SELECT * FROM {customcert_issues} ci 
                                JOIN {customcert} c ON c.id = ci.customcertid
                                JOIN {customcert_templates} ct ON ct.id = c.templateid
                                WHERE ci.userid = :userid AND c.course = :courseid ';
                $cert = $DB->get_record_sql($usercertsql,$certparam);

                $tempfile = '';
                $filename = '';

                if(!empty($cert)){
                    // Now, get the PDF.
                    $template = new \stdClass();
                    $template->id = $cert->templateid;
                    $template->name = $cert->name;
                    $template->contextid = $cert->contextid;
                    $template = new \mod_customcert\template($template);
                    $filecontents = $template->generate_pdf(false, $event->relateduserid, true);

                    // Set the name of the file we are going to send.
                    $filename = "certificate";
                    $filename = \core_text::entities_to_utf8($filename);
                    $filename = strip_tags($filename);
                    $filename = rtrim($filename, '.');
                    $filename = str_replace('&', '_', $filename) . '.pdf';

                    $tempdir = make_temp_directory('certificate/attachment');
                    // Create the file we will be sending.
                    $tempfile = $tempdir . '/' . md5(microtime() . $event->relateduserid) . '.pdf';
                    file_put_contents($tempfile, $filecontents);
                }


                $subject = get_string('ccsubject','local_coursenotify');
                $msg = get_string('ccmsgbody', 'local_coursenotify', $a);

                $users = core_user::get_user($user->userid);
                $users->email = $user->data;
                $users->userid = $user->userid;
                $users->data = $user->data;
//                return email_to_user($users, $from, $subject, $msg,'',$tempfile,$filename);
            }
        }
    }
}