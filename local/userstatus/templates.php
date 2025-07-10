<?php

use local_userstatus\status;

require_once dirname(__FILE__) . '/../../config.php';
require_once("{$CFG->libdir}/formslib.php");
require_once("{$CFG->libdir}/tablelib.php");

$dbtable = status::templatetable;
$stringcomponent = status::component;
$strings = (array)get_strings([
'templatename',
'messagesubject',
'messagebody',
'placeholdernotice',
'savetemplate',
'managetemplates',
'edittemplate',
'addtemplate',
'templatesaved',
'templatedeleted',
'thname',
'thsubject',
'thaction',
'btnadd',
'btnedit',
'btndelete',
'btndeletemsg',
'templateexist',
'backtoworkflow',
        ],$stringcomponent);
define('ACTION_LIST','list');
define('ACTION_EDIT','edit');
define('ACTION_DELETE','delete');
define('PER_PAGE',30);

class actionform extends moodleform {
    protected function definition() {
        global $strings;
        $mform = $this->_form;

        $mform->addElement('text', 'name', $strings['templatename'], 'size="50"');
        $mform->setType('name', PARAM_TEXT);
        $mform->applyFilter('name','trim');
        $mform->addRule('name',get_string('required'),'required',null,'client');

        //$mform->addElement('text', 'subject', $strings['messagesubject'], 'size="50"');
        //$mform->setType('subject', PARAM_TEXT);
        //$mform->addRule('subject',get_string('required'),'required',null,'client');

        $mform->addElement('textarea', 'message', $strings['messagebody'], array('cols' => 60, 'rows' => 5));
        $mform->setType('message', PARAM_TEXT);
        $mform->addRule('message',get_string('required'),'required',null,'client');

        $mform->addElement('static', null, null, $strings['placeholdernotice']);

        $this->add_action_buttons(true,$strings['savetemplate']);
    }

    public function validation($data, $files) {
        global $DB,$strings,$dbtable;
        $errors = parent::validation($data, $files);
        if($DB->record_exists_select($dbtable,'name = :name AND id <> :id',[
                'name' => $data['name'],
                'id' => $this->_customdata['id'],
        ])) {
            $errors['name'] = $strings['templateexist'];
        }
        return $errors;
    }
}

class actionlist extends table_sql {
    const cols = ['name',/*'subject',*/'action'];
    public $is_collapsible = false;
    public $is_sortable = false;
    public function __construct() {
        parent::__construct('actionlist');
    }

    public function other_cols($column, $row) {
        global $OUTPUT,$strings;
        /* @var $OUTPUT core_renderer */
        $html = NULL;
        switch ($column) {
            case self::cols[1]:
                $editbutton = new single_button(
                        new moodle_url($this->baseurl,['action' => ACTION_EDIT,'id' => $row->id,]),
                        $strings['btnedit']
                );
                $deletebutton = new single_button(
                        new moodle_url($this->baseurl,['action' => ACTION_DELETE,'id' => $row->id,'sesskey' => sesskey()]),
                        $strings['btndelete']
                );
                $deletebutton->add_confirm_action($strings['btndeletemsg']);
                $html = $OUTPUT->render($editbutton) .
                        $OUTPUT->render($deletebutton);
                break;
        }
        return $html;
    }
}

$contextid = required_param('contextid',PARAM_INT);
$action = optional_param('action',ACTION_LIST,PARAM_ALPHA);
$pageurl = new moodle_url('/local/userstatus/templates.php',['action' => $action,'contextid' => $contextid,]);

$context = context::instance_by_id($contextid, MUST_EXIST);
if ($context->contextlevel != CONTEXT_COURSE) {
    print_error('invalidcontext');
}
$course = $DB->get_record('course', array('id' => $context->instanceid), '*', MUST_EXIST);

require_login($course);

$systemcontext = context_system::instance();
$title = $strings['managetemplates'];
$returnurl = new moodle_url('upload.php');

$PAGE->set_context($systemcontext);
$PAGE->set_url($pageurl);
$PAGE->set_title($title);

$canmanage = status::manage_templates($context);
$isadmin = is_siteadmin();

if(!$canmanage) {
    print_error('nopermission',$stringcomponent);
}

switch ($action) {

    case ACTION_EDIT:
        $id = optional_param('id',0,PARAM_INT);
        $returnurl = new moodle_url($pageurl,['action' => ACTION_LIST]);
        $pageurl = new moodle_url($pageurl, ['id' => $id,]);
        $title = $strings[$id > 0 ? 'edittemplate':'addtemplate'];

        $PAGE->set_url($pageurl);
        $PAGE->set_heading($title);
        $PAGE->set_title($title);

        $form = new actionform($pageurl,['id' => $id,]);
        $data = $DB->get_record($dbtable,['id' => $id,],'*',IGNORE_MULTIPLE);
        $form->set_data($data);

        if($postdata = $form->get_data()) {
            $postdata->id = $id;
            $postdata->timemodified = time();
            if($postdata->id > 0) {
                $DB->update_record($dbtable,$postdata);
            } else {
                $postdata->timecreated = $postdata->timemodified;
                $postdata->userid = $USER->id;
                $DB->insert_record($dbtable,$postdata);
            }
            redirect($returnurl,$strings['templatesaved']);
        } elseif ($form->is_cancelled()) {
            redirect($returnurl);
        }

        echo $OUTPUT->header();

        $form->display();

        echo $OUTPUT->footer();
        break;

    case ACTION_LIST:
        $perpage = optional_param('perpage',PER_PAGE,PARAM_INT);

        $table = new actionlist();
        $table->define_baseurl($pageurl);
        $table->define_columns($table::cols);
        $table->define_headers(array_map(function($s) use ($strings){
            return $strings['th'.$s];
        },$table::cols));
        $table->set_sql('*','{'.$dbtable.'}','userid = :userid OR :siteadmin = 1',[
                'userid' => $USER->id, 'siteadmin' => $isadmin,
        ]);

        echo $OUTPUT->header();

        echo $OUTPUT->container_start('text-right mb-2');
        echo $OUTPUT->single_button($pageurl->out(true,['action' => ACTION_EDIT]),$strings['btnadd'],'post',['primary' =>true,]);
        echo $OUTPUT->single_button(new moodle_url('index.php',['contextid' => $context->id,]),$strings['backtoworkflow'],'get',['primary' =>true,]);
        echo $OUTPUT->container_end();

        $table->out($perpage,false);

        echo $OUTPUT->footer();
        break;

    CASE ACTION_DELETE:
        $delete = required_param('id',PARAM_INT);
        $returnurl = new moodle_url($pageurl,['action' => ACTION_LIST]);

        if(confirm_sesskey()) {
            $DB->delete_records($dbtable, [ 'id' => $delete, ]);
            redirect($returnurl,$strings['templatedeleted']);
        } else {
            print_error('invalidaction',$stringcomponent);
        }
        break;

    default:
        print_error('invalidaction',$stringcomponent);

}
