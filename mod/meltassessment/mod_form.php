<?php
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once ($CFG->dirroot.'/course/moodleform_mod.php');
define('VISIBLE',0);

class mod_meltassessment_mod_form extends moodleform_mod {

    private $deletefieldname = 'deletelevel';

    /**
     * @var array
     */
    protected $marketcriteria;

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

        $mform->addElement('text', 'name', get_string('meltassessmentname', 'mod_meltassessment'), array('size' => '64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $this->standard_intro_elements(get_string('description', 'mod_meltassessment'));

        //-------------------------------------------------------------------------------

        $mform->addElement('header', 'information', get_string('userfield', 'mod_meltassessment'));
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
        $filedarray[] = $mform->createElement('text', 'infopoint', get_string('meltassessmentfield', 'mod_meltassessment'), 'maxlength="200" size="30" ');
        $filedarray[] = $mform->createElement('select', 'userfiled', get_string('userfileds', 'mod_meltassessment'), $chooseopt, ['multiple' => false,]);

        $repeatfiled['infogroup[deletedinfo]']['default'] = 0;
        $repeatfiled['infogroup[deletedinfo]']['type'] = PARAM_INT;
        $repeatfiled['infogroup[infoid]']['type'] = PARAM_INT;
        $repeatfiled['infogroup[infopoint]']['type'] = PARAM_TEXT;
        $repeatfiled['infogroup[infopoint]']['disabledif'] = ['infogroup[deletedinfo]', 'eq', 1];
        $repeatfiled['infogroup[userfiled]']['disabledif'] = ['infogroup[deletedinfo]', 'eq', 1];


        $groupfiled[] = $mform->createElement('group', 'infogroup',
                get_string('field', 'mod_meltassessment'), $filedarray, '', true);
        $groupfiled[]  = $mform->createElement('static', 'deleteitem',
                '<button class="btn btn-primary deletechoicebutton" type="submit" name="removeinformation" value="{no}">'.
                get_string('remove','mod_meltassessment').
                '</button>',
                '<strong class="deleteinformationnote" style="display: none">'.
                get_string('removedfieldnote','mod_meltassessment').
                '</strong>');

        $mform->registerNoSubmitButton('removeinformation');
        $this->repeat_elements($groupfiled, $repeatcountfiled, $repeatfiled,'noinformation', 'informationadd',
                1, get_string('adduserfield', 'mod_meltassessment'),true);

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


        $mform->addElement('header', 'marketcriteria', get_string('marketcriteria', 'mod_meltassessment'));
        $mform->setExpanded('marketcriteria', true);



        if(empty(count($this->get_market_criteria()))){
            $repeatmarketcount = 1;
        }else{
            $repeatmarketcount = count($this->get_market_criteria());
        }

        $marketarray[] = $mform->createElement('hidden', 'deletedmarket', 0);
        $marketarray[] = $mform->createElement('hidden', 'marketid', 0);
        $marketarray[] = $mform->createElement('text', 'marketpoint', get_string('marketfield', 'mod_meltassessment'), 'maxlength="200" size="30" ');

        $marketfiled['marketgroup[deletedmarket]']['default'] = 0;
        $marketfiled['marketgroup[deletedmarket]']['type'] = PARAM_INT;
        $marketfiled['marketgroup[marketid]']['type'] = PARAM_INT;
        $marketfiled['marketgroup[marketpoint]']['type'] = PARAM_TEXT;

        $groupmarket[] = $mform->createElement('group', 'marketgroup',
                get_string('marketfieldnumber', 'mod_meltassessment'), $marketarray, '', true);

        $this->repeat_elements($groupmarket, $repeatmarketcount, $marketfiled, 'nomarkets', 'marketadd',
                1, get_string('addmarket', 'mod_meltassessment'),true);
        //------------
        $mform->addElement('header', 'lessons', get_string('lessons', 'mod_meltassessment'));
        $mform->setExpanded('lessons', true);

        $lessonopt = ['0'=>get_string('choose'),'1'=>1,'2'=>2,'3'=>3,'4'=>4,'5'=>5,'6'=>6,'7'=>7,'8'=>8,'9'=>9,'10'=>10,'11'=>11,'12'=>12];
        $mform->addElement('select', 'nolesson', get_string('nolesson', 'mod_meltassessment'),$lessonopt, ['multiple' => false,]);

        //-------------------------------------------------------------------
        $this->standard_coursemodule_elements();
        //-------------------------------------------------------------------------------
        $this->add_action_buttons();
    }

    protected function get_userfield(){
        global $DB;
        if(!isset($this->userfields)){
            $this->userfields = [];
            if(!empty($this->_instance)){
                $this->userfields = $DB->get_records('meltassessment_field',['meltassessmentid' => $this->_instance]);
            }
        }

        return $this->userfields;
    }
    //
    function data_preprocessing(&$default_values) {

        foreach (array_values($this->get_userfield()) as $key => $userfields) {
            $default_values['infogroup[' . $key . '][infoid]'] = $userfields->id;
            $default_values['infogroup[' . $key . '][infopoint]'] = $userfields->field;
            $default_values['infogroup[' . $key . '][userfiled]'] = $userfields->fieldvalue;
        }

        foreach (array_values($this->get_market_criteria()) as $key =>$marketcriteria){
            $default_values['marketgroup[' . $key . '][marketid]'] = $marketcriteria->id;
            $default_values['marketgroup[' . $key . '][marketpoint]'] = $marketcriteria->name;
        }
    }

    protected function get_market_criteria(){
        global $DB;
        if(!isset($this->marketcriteria)){
            $this->marketcriteria = [];
            if(!empty($this->_instance)){
                $this->marketcriteria = $DB->get_records('meltassessment_market',['meltassessmentid' => $this->_instance]);
            }
        }
        return $this->marketcriteria;
    }


    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        $datapoint =  array_column($data['infogroup'],'infopoint');
        $nolesson = $data['nolesson'];
        $marketfield = array_column($data['marketgroup'],'marketpoint');
        $datauserfiled =  array_column($data['infogroup'],'userfiled');
        $countinformation = count($data['infogroup']);
        $deletecounts =  array_column($data['infogroup'],'deletedinfo');
        $deletecount = count(array_filter($deletecounts));

        if($countinformation == $deletecount){
            $errors['infogroup[0]'] = get_string('selectonefiled', 'mod_meltassessment');
        }

        if(!array_filter($datauserfiled)){
            $errors['infogroup[0]'] = get_string('selectoneuserfiled', 'mod_meltassessment');
        }

        if(!array_filter($datapoint)){
            $errors['infogroup[0]'] = get_string('selectonefiled', 'mod_meltassessment');
        }

        if(!array_filter($marketfield)){
            $errors['marketgroup[0]'] = get_string('addmarketcriteria', 'mod_meltassessment');
        }

        if(empty($nolesson)){
            $errors['nolesson'] = get_string('lessoner','mod_meltassessment');
        }



        $datainfogroups = $data['infogroup'];

        foreach ($datainfogroups  as $key => $datainfogroup){
            if(!empty($datainfogroup['infopoint']) && empty($datainfogroup['userfiled'])){
                $errors['infogroup['.$key.']'] = get_string('selectoneuserfiled', 'mod_meltassessment');
            }
            if(empty($datainfogroup['infopoint']) && !empty($datainfogroup['userfiled'])){
                $errors['infogroup['.$key.']'] = get_string('userfielder', 'mod_meltassessment');
            }
        }
        return $errors;
    }

}

