<?php

require_once('config.php');
$PAGE->set_url('/logout.php');

require_logout();
redirect($CFG->wwwroot.'/');