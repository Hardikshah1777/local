<?php  // Moodle configuration file

unset($CFG);
global $CFG;
$CFG = new stdClass();

$CFG->dbtype    = 'mysqli';
$CFG->dblibrary = 'native';
$CFG->dbhost    = 'localhost';
$CFG->dbname    = 'moodle4';
$CFG->dbuser    = 'root';
$CFG->dbpass    = 'Admin@123';
$CFG->prefix    = 'mdl_';
$CFG->dboptions = array (
  'dbpersist' => 0,
  'dbport' => '',
  'dbsocket' => '',
  'dbcollation' => 'utf8mb4_0900_ai_ci',
);

//$CFG->smtphosts = '192.168.88.250:1025';
$CFG->smtphosts = '192.168.88.96:1025';
$CFG->smtpsecure = '';
$CFG->smtpuser = '';
$CFG->smtppass = '';

$CFG->wwwroot   = 'http://localhost/moodle4';
$CFG->dataroot  = '/var/www/Datafolder/moodle4';
$CFG->admin     = 'admin';

$CFG->directorypermissions = 0777;
//@error_reporting(E_ALL | E_STRICT); // NOT FOR PRODUCTION SERVERS!
 //@ini_set('display_errors', '1');    // NOT FOR PRODUCTION SERVERS!
//$CFG->debug = (E_ALL | E_STRICT);   // === DEBUG_DEVELOPER - NOT FOR PRODUCTION SERVERS!
//$CFG->debugdisplay = 1;

require_once(__DIR__ . '/lib/setup.php');

// There is no php closing tag in this file,
// it is intentional because it prevents trailing whitespace problems!
