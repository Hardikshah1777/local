<?php

global $CFG;
require_once($CFG->dirroot . '/repository/lib.php');
require_once($CFG->dirroot . '/files/externallib.php');

$this->getDataGenerator()->create_repository($type, $record, $options);













