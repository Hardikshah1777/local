<?php

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_squibit', new lang_string('settingname', 'local_squibit'));
    $ADMIN->add('localplugins', $settings);

    if ($ADMIN->fulltree) {
        $options = [
                new lang_string('disabled', 'local_squibit'),
                new lang_string('enabled', 'local_squibit'),
        ];

        $settings->add(
                new admin_setting_configselect(
                        'local_squibit/status',
                        new lang_string('status', 'local_squibit'),
                        new lang_string('status_desc', 'local_squibit'),
                        1, $options
                )
        );

        $settings->add(
                new admin_setting_configtext(
                        'local_squibit/host',
                        new lang_string('host', 'local_squibit'),
                        new lang_string('host_desc', 'local_squibit'),
                        null, PARAM_URL
                )
        );

        $settings->add(
                new admin_setting_configtext(
                        'local_squibit/apikey',
                        new lang_string('apikey', 'local_squibit'),
                        new lang_string('apikey_desc', 'local_squibit'),
                        null
                )
        );

        $settings->add(
                new admin_setting_configtext(
                        'local_squibit/email',
                        new lang_string('email', 'local_squibit'),
                        new lang_string('email_desc', 'local_squibit'),
                        null, PARAM_RAW
                )
        );

        $settings->add(
                new admin_setting_configpasswordunmask(
                        'local_squibit/password',
                        new lang_string('password', 'local_squibit'),
                        new lang_string('password_desc', 'local_squibit'),
                        null
                )
        );

        $settings->add(
                new admin_setting_configcheckbox(
                        'local_squibit/checksendmail',
                        new lang_string('checksendmail', 'local_squibit'),
                        new lang_string('checksendmail_desc', 'local_squibit'),
                        null
                )
        );

        $settings->add(
                new admin_setting_configtext(
                        'local_squibit/senderemail',
                        new lang_string('senderemail', 'local_squibit'),
                        new lang_string('senderemail_desc', 'local_squibit'),
                        null, PARAM_EMAIL
                )
        );

        $settings->add(new local_squibit\admin_setting_link(
                'local_squibit/rolesynclink',
                new lang_string('rolesynclink', 'local_squibit'),
                new lang_string('rolesynclinkdesc', 'local_squibit'),
                new lang_string('clickrolesync', 'local_squibit'),
                new moodle_url('/local/squibit/roles.php'),
                null
        ));

        $teacherrolechoices = ['' => ''];
        $rawrolejson = get_config('local_squibit', 'rolejson');
        if (!empty($rawrolejson)) {
            $teacherrolechoices = $teacherrolechoices + json_decode($rawrolejson, true);
        }

        $settings->add(
                new admin_setting_configselect(
                        'local_squibit/teacherrole',
                        new lang_string('teacherrole', 'local_squibit'),
                        new lang_string('teacherrole_desc', 'local_squibit'),
                        null,
                        $teacherrolechoices
                )
        );
        unset($teacherrolechoices, $rawrolejson);

        $settings->add(
                new admin_setting_configcheckbox(
                        'local_squibit/syncuserid',
                        new lang_string('syncuserid', 'local_squibit'),
                        new lang_string('syncuserid_desc', 'local_squibit'),
                        null
                )
        );

        $settings->add(
                new admin_setting_configcheckbox(
                        'local_squibit/synccourseid',
                        new lang_string('synccourseid', 'local_squibit'),
                        new lang_string('synccourseid_desc', 'local_squibit'),
                        null
                )
        );


        $settings->add(new local_squibit\admin_setting(
                'manageuserssetings',
                new lang_string('userssetings', 'local_squibit'),
                local_squibit\admin_setting::TYPES[0]
        ));

        $settings->add(new local_squibit\admin_setting(
                'managecoursessetings',
                new lang_string('coursessetings', 'local_squibit'),
                local_squibit\admin_setting::TYPES[1]
        ));

        $settings->add(
                new local_squibit\admin_setting(
                        'resetbtn',
                        new lang_string('resetbtn', 'local_squibit'),
                        local_squibit\admin_setting::TYPES[2]
                )
        );

    }

    $ADMIN->add('localplugins', new admin_externalpage(
                'local_squibit_rolesync',
                new lang_string('rolesync', 'local_squibit'),
                new moodle_url('/local/squibit/roles.php'),
                'moodle/site:config', true
        )
    );
}
