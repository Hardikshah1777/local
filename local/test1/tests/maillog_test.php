<?php

namespace local_test1;

use advanced_testcase;

class maillog_test extends advanced_testcase
{
    public function test_adding() {
        global $DB;

        $this->resetAfterTest();

        $this->assertEquals(2, 1+2);

        $user1 = $this->getDataGenerator()->create_user([
            'email' => 'user1@example.com',
            'username' => 'user1',
        ]);
        $this->setUser($user1);

        $category = $this->getDataGenerator()->create_category([
            'name' => 'Some subcategory',
            'parent' => 1,
        ]);

        $course = $this->getDataGenerator()->create_course([
            'name' => 'Some course',
            'category' => $category->id,
        ]);

        $page = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);

        $roleid = $DB->get_field('role', 'id', array('shortname' => 'student'), MUST_EXIST);
        $this->getDataGenerator()->enrol_user(
                $user1->id,
                $course->id,
                $roleid,
                'manual',
            );

        $groupid = $this->getDataGenerator()->create_group([
            'courseid' => $course->id,
            'name' => 'Some course group - 1',
            'description' => 'Some course group - 1',
            'descriptionformat' => 'Some course group - 1',
        ]);

        $this->getDataGenerator()->create_group_member([
            'userid' => $user1->id,
            'groupid' => $groupid,
            'component' => 'Some course group - 1',
            'itemid' => $user1->id,
        ]);

        $this->getDataGenerator()->create_grade_category([
            'courseid' => $course->id,
            'fullname' => $user1->username,
        ]);

        $this->getDataGenerator()->create_grade_item(['fullname' => $user1->username]);

        $sink = $this->redirectEmails();
        $messages = $sink->get_messages();
        $this->assertEquals(1, count($messages));

    }
}