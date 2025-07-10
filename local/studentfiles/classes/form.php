<?php

namespace local_studentfiles;

require_once ($CFG->libdir . '/formslib.php');

use \core\notification;

class form extends \moodleform {
    const field = 'file';
    private $userfield = 'identitynumber';
    private $pattern = '/^.*-([\d]+)\.pdf$/';

    private $fpoptions = [
        'subdirs' => 0,
        'accepted_types' => '.pdf',
    ];

    protected function definition() {
        $mform = $this->_form;

        $mform->addElement('filemanager',static::field,util::get_string('studentfiles'),null,$this->fpoptions);
        $mform->addRule(static::field,get_string('required'),'required',null,'client');

        $templateconfig = array_merge(self::templateconfig(),$this->_customdata['templates']);

        $mform->addElement('select','mailtemplate',util::get_string('mailtemplate'),
                array_column($templateconfig,'name','id'),[
                'data-json' => json_encode([
                        'templates' => $templateconfig,
                        'notemplateid' => util::notemplate,
                ])
        ]);

        $mform->addElement('text', 'mailsubject', util::get_string('mailsubject'), 'size="50"');
        $mform->setType('mailsubject', PARAM_TEXT);
        $mform->addRule('mailsubject',get_string('required'),'required',null,'client');

        $mform->addElement('textarea', 'mailbody', util::get_string('mailbody'), array('cols' => 60, 'rows' => 5));
        $mform->setType('mailbody', PARAM_TEXT);
        $mform->addRule('mailbody',get_string('required'),'required',null,'client');

        $mform->addElement('static', null, null, util::get_string('placeholdernotice'));

        $mform->addElement('advcheckbox', 'saveastemplate', util::get_string('saveastemplate'));
        $mform->addElement('text', 'templatename', util::get_string('templatename'), 'size="50"');
        $mform->setType('templatename', PARAM_TEXT);
        $mform->applyFilter('templatename','trim');

        $mform->hideIf('templatename','saveastemplate','notchecked');

        $mform->disable_form_change_checker();

        $this->add_action_buttons(false,util::get_string('upload',null,'core'));
    }

    public function validation($data, $files) {
        global $DB;
        $errors = parent::validation($data, $files);
        if(!empty($data['saveastemplate'])) {
            if (empty($data['templatename'])) {
                $errors['templatename'] = get_string('required');
            } else if($DB->record_exists(util::templatetable,['name' => $data['templatename']])) {
                $errors['templatename'] = util::get_string('templateexist');
            }
        }
        return $errors;
    }

    public function save_drafts($data){
        global $USER;
        $mform = $this->_form;
        $element = $mform->getElement(static::field);
        $loginuserctx = \context_user::instance($USER->id);
        /* @var $element \MoodleQuickForm_filemanager */
        $draftid = $element->getValue();
        $fs = get_file_storage();
        $files = $fs->get_area_files($loginuserctx->id, 'user', 'draft', $draftid, 'id DESC', false);
        $migrated = 0;
        foreach ($files as $file){
            $filename = $file->get_filename();
            if($user = $this->user_from_filename($filename)){
                $usercontext = \context_user::instance($user->id);
                $file_record = array(
                        'contextid' => $usercontext->id,
                        'component' => util::component,
                        'filearea' => util::filearea,
                        'itemid' => 0
                );
                try {
                    $fs->create_file_from_storedfile($file_record, $file);
                    $fileurl = \moodle_url::make_pluginfile_url(
                            $file_record['contextid'],
                            $file_record['component'],
                            $file_record['filearea'],
                            $file_record['itemid'],
                            '/',
                            $file->get_filename()
                    )->out();
                    util::store($user->id, $filename);
                    util::notify($user,$data->mailsubject,$data->mailbody,$fileurl);
                    notification::add(util::get_string('filemoved',[
                                    'username' => fullname($user),
                                    'filename' => $filename,]
                    ),notification::SUCCESS);
                } catch (\stored_file_creation_exception $e) {
                    notification::add(util::get_string('errorsamefile',[
                            'username' => fullname($user),
                            'filename' => $filename,]
                    ),notification::ERROR);
                    continue;
                } catch (\Exception $e){
                    notification::add($e->getMessage(),notification::ERROR);
                    continue;
                }
                $file->delete();
                $migrated++;
            } else {
                preg_match($this->pattern,$filename,$matches);
                if(isset($matches[1])){
                    $failedmessage = util::get_string('errorinvaliduser', [
                            'filename' => $filename, 'data' => $matches[1]]
                    );
                }else{
                    $failedmessage = util::get_string('errorinvalidfilename', [
                                'filename' => $filename,]
                    );
                }
                notification::add($failedmessage,notification::WARNING);
            }
        }
        return $migrated;
    }

    protected function user_from_filename($filename){
        global $DB;
        preg_match($this->pattern,$filename,$matches);
        $user = $matches[1] ?? null;
        if($user){
            $user = $DB->get_record_sql("SELECT u.* FROM {user_info_data} ud 
                                              JOIN {user_info_field} uf ON uf.id = ud.fieldid 
                                              JOIN {user} u ON ud.userid = u.id 
                                              WHERE uf.shortname = :shortname AND ud.data =:data ",array('shortname'=> $this->userfield, 'data' => $user));
        }
        return $user;
    }

    static function templateconfig() {
        return [
            [
                'id' => util::notemplate,
                'name' => util::get_string('notemplate'),
                'subject' => 'notemplatesubject',
                'message' => 'notemplatemessage',
                'string' => true,
            ],
            [
                'id' => util::feedbacktemplate,
                'name' => util::get_string('feedbacktemplate'),
                'subject' => 'feedbacksubject',
                'message' => 'feedbackmessage',
                'string' => true,
            ],
            [
                'id' => util::certificatetemplate,
                'name' => util::get_string('certificatetemplate'),
                'subject' => 'certificatesubject',
                'message' => 'certificatemessage',
                'string' => true,
            ],
        ];
    }
}
