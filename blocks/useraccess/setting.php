<?php

$settings->add(new admin_setting_heading(
    'headerconfig',
    get_string('headerconfig', 'block_useraccess'),
    get_string('descconfig', 'block_useraccess')
));

$settings->add(new admin_setting_configcheckbox(
    'useraccess/Allow_HTML',
    get_string('labelallowuseraccess', 'block_useraccess'),
    get_string('descallowuseraccess', 'block_useraccess'),
    '0'
));