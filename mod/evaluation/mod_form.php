<?php
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once ($CFG->dirroot.'/course/moodleform_mod.php');
define('VISIBLE',0);

class mod_evaluation_mod_form extends moodleform_mod {

    private $deletefieldname = 'deletelevel';

    /**
     * @var array
     */
    protected $levels;

    protected $userfields;

    public function get_user_columns() {
        global $DB;

        return array(
                'username',
                'fullname',
                'email',
                'city',
                'country',
                'idnumber',
                'phone1',
                'phone2',
                'institution',
                'department',
                'address',
                'Instructor Fillable',
                'instructorname',
        );
    }

    function definition() {
        global $CFG, $DB;

        $mform =& $this->_form;

        //-------------------------------------------------------------------------------
        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('evaluationname', 'mod_evaluation'), array('size' => '64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $this->standard_intro_elements(get_string('description', 'mod_evaluation'));

        //-------------------------------------------------------------------------------

        $mform->addElement('header', 'level', get_string('levels', 'mod_evaluation'));
        $mform->setExpanded('level', true);
        if(empty(count($this->get_levels()))){
            $repeatcount = 1;
        }else{
            $repeatcount = count($this->get_levels());
        }

        $repeatarray[] = $mform->createElement('hidden', 'deleted', 0);
        $repeatarray[] = $mform->createElement('hidden', 'evaluationid', 0);
        $repeatarray[] = $mform->createElement('hidden', 'levelid', 0);
        $repeatarray[] = $mform->createElement('text', 'levelpoint', get_string('evaluationlevel', 'mod_evaluation'), 'maxlength="200" size="30" ');
        $statusopt = ['0'=>get_string('visible'),'1'=>get_string('hide')];
        $gradeopt = ['-2'=>-2,'-1'=>-1,'0'=>0,'1'=>1,'2'=>2];
        $repeatarray[] = $mform->createElement('select', 'visiblestatus', get_string('visiblestatus', 'mod_evaluation'), $statusopt, ['multiple' => false,]);
        $repeatarray[] = $mform->createElement('select', 'grade', get_string('grade', 'mod_evaluation'), $gradeopt, ['multiple' => false,]);

        $repeatopts['levelgroup[deleted]']['default'] = 0;
        $repeatopts['levelgroup[grade]']['default'] = 0;
        $repeatopts['levelgroup[deleted]']['type'] = PARAM_INT;
        $repeatopts['levelgroup[evaluationid]']['type'] = PARAM_INT;
        $repeatopts['levelgroup[levelid]']['default'] = 0;
        $repeatopts['levelgroup[levelid]']['type'] = PARAM_INT;
        $repeatopts['levelgroup[levelpoint]']['type'] = PARAM_TEXT;

        $group[] = $mform->createElement('group', 'levelgroup',
                get_string('level', 'mod_evaluation'), $repeatarray, '', true);

        $this->repeat_elements($group, $repeatcount, $repeatopts, 'nolevels', 'leveladd',
                1, get_string('addlevel', 'mod_evaluation'),true);

        $mform->addElement('header', 'information', get_string('informations', 'mod_evaluation'));
        $mform->setExpanded('information', true);

        $chooseopt = ['' => get_string('choose')];
        foreach (self::get_user_columns() as $column) {
            $chooseopt[$column] = $column;
        }
        $customfieldname = $DB->get_records('user_info_field', null, '', '*');
        foreach ($customfieldname as $column) {
            $chooseopt[$column->id] = $column->name."(profilefield)";
        }

        if(empty(count($this->get_userfield()))){
            $repeatcountfiled = 1;
        }else{
            $repeatcountfiled = count($this->get_userfield());
        }
        $filedarray[] = $mform->createElement('hidden', 'deletedinfo', 0);
        $filedarray[] = $mform->createElement('hidden', 'infoid', 0);
        $filedarray[] = $mform->createElement('text', 'infopoint', get_string('evaluationinformation', 'mod_evaluation'), 'maxlength="200" size="30" ');
        $filedarray[] = $mform->createElement('select', 'userfiled', get_string('userfileds', 'mod_evaluation'), $chooseopt, ['multiple' => false,]);

        $repeatfiled['infogroup[deletedinfo]']['default'] = 0;
        $repeatfiled['infogroup[deletedinfo]']['type'] = PARAM_INT;
        $repeatfiled['infogroup[infoid]']['type'] = PARAM_INT;
        $repeatfiled['infogroup[infopoint]']['type'] = PARAM_TEXT;
        $repeatfiled['infogroup[infopoint]']['disabledif'] = ['infogroup[deletedinfo]', 'eq', 1];
        $repeatfiled['infogroup[userfiled]']['disabledif'] = ['infogroup[deletedinfo]', 'eq', 1];


        $groupfiled[] = $mform->createElement('group', 'infogroup',
                get_string('information', 'mod_evaluation'), $filedarray, '', true);
        $groupfiled[]  = $mform->createElement('static', 'deleteitem',
                '<button class="btn btn-primary deletechoicebutton" type="submit" name="removeinformation" value="{no}">'.
                get_string('remove','mod_evaluation').
                '</button>',
                '<strong class="deleteinformationnote" style="display: none">'.
                get_string('removedinformationnote','mod_evaluation').
                '</strong>');

        $mform->registerNoSubmitButton('removeinformation');
        $this->repeat_elements($groupfiled, $repeatcountfiled, $repeatfiled,'noinformation', 'informationadd',
                1, get_string('addinformation', 'mod_evaluation'),true);

        $deleteinformation = $this->optional_param('removeinformation',null,PARAM_INT);

        if (isset($deleteinformation)) {
            $deletedname = 'infogroup[' . ($deleteinformation - 1) . '][deletedinfo]';
            $mform->_constantValues = HTML_QuickForm::arrayMerge($mform->_constantValues, array($deletedname=>1));
            $mform->setConstant('infogroup[' . ($deleteinformation - 1) . ']', ['deletedinfo' => 1]);
        }

        $mform->addElement('html', <<<HTML
        <script>
        document.querySelectorAll('[name*="deletedinfo"][value="1"]').forEach(el => {  
            var index = parseInt(el.name.match(/\d+/)[0]) + 1;
            var button = document.querySelector('[name="removeinformation"][value="'+index+'"]');
            if(button) {
                
                button.disabled = true;
                
                var newindex = index - 1;
                var disableone = document.getElementsByName('infogroup['+newindex+'][infopoint]');
                var disabletwo = document.getElementsByName('infogroup['+newindex+'][userfiled]');
                disableone[0].disabled = true;
                disabletwo[0].disabled = true;
                
                var deletenote = button.closest('.fitem').querySelector('.deleteinformationnote');
                if(deletenote) {
                    deletenote.style.display = 'block';
                }
            }
        })
        </script>
HTML
                    );

        //-------------------------------------------------------------------------------
        $this->standard_coursemodule_elements();
        //-------------------------------------------------------------------------------
        $this->add_action_buttons();
    }

    protected function get_userfield(){
        global $DB;
        if(!isset($this->userfields)){
            $this->userfields = [];
            if(!empty($this->_instance)){
                $this->userfields = $DB->get_records('evaluation_userinfo',['evaluationid' => $this->_instance]);
            }
        }

        return $this->userfields;
    }
    //
    function data_preprocessing(&$default_values) {
        foreach (array_values($this->get_levels()) as $key => $level) {
            $default_values['levelgroup[' . $key . '][evaluationid]'] = $level->evaluationid;
            $default_values['levelgroup[' . $key . '][levelid]'] = $level->id;
            $default_values['levelgroup[' . $key . '][levelpoint]'] = $level->name;
            $default_values['levelgroup[' . $key . '][visiblestatus]'] = $level->status;
            $default_values['levelgroup[' . $key . '][grade]'] = $level->grade;
        }
        foreach (array_values($this->get_userfield()) as $key => $userfields) {
            $default_values['infogroup[' . $key . '][infoid]'] = $userfields->id;
            $default_values['infogroup[' . $key . '][infopoint]'] = $userfields->infofiled;
            $default_values['infogroup[' . $key . '][userfiled]'] = $userfields->infovalue;
        }
    }

    protected function get_levels(){
        global $DB;
        if(!isset($this->levels)){
            $this->levels = [];
            if(!empty($this->_instance)){
                $this->levels = $DB->get_records('evaluation_level',['evaluationid' => $this->_instance]);
            }
        }
        return $this->levels;
    }


    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        $check =  array_column($data['levelgroup'],'levelpoint');
        $datapoint =  array_column($data['infogroup'],'infopoint');
        $datauserfiled =  array_column($data['infogroup'],'userfiled');
        $status = array_column($data['levelgroup'],'visiblestatus');
        $countinformation = count($data['infogroup']);
        $deletecounts =  array_column($data['infogroup'],'deletedinfo');
        $deletecount = count(array_filter($deletecounts));

        if($countinformation == $deletecount){
            $errors['infogroup[0]'] = get_string('selectonefiled', 'mod_evaluation');
        }

        if(!array_filter($check)){
            $errors['levelgroup[0]'] = get_string('selectone', 'mod_evaluation');
        }

        if(!in_array(VISIBLE,$status)){
            $errors['levelgroup[0]'] = get_string('selectonestatus','mod_evaluation');
        }


        if(!array_filter($datauserfiled)){
            $errors['infogroup[0]'] = get_string('selectoneuserfiled', 'mod_evaluation');
        }

        if(!array_filter($datapoint)){
            $errors['infogroup[0]'] = get_string('selectonefiled', 'mod_evaluation');
        }

        $datainfogroups = $data['infogroup'];

        foreach ($datainfogroups  as $key => $datainfogroup){
            if(!empty($datainfogroup['infopoint']) && empty($datainfogroup['userfiled'])){
                $errors['infogroup['.$key.']'] = get_string('selectoneuserfiled', 'mod_evaluation');
            }
        }

        return $errors;
    }

}

