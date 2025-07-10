<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This file contains the definition for the library class for onlinepoodll submission plugin
 *
 * This class provides all the functionality for the new assign module.
 *
 * @package    assignsubmission_onlinepoodll
 * @copyright 2012 Justin Hunt {@link http://www.poodll.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

use assignsubmission_onlinepoodll\constants;
use assignsubmission_onlinepoodll\utils;


/**
 * library class for onlinepoodll submission plugin extending submission plugin base class
 *
 * @package    assignsubmission_onlinepoodll
 * @copyright 2012 Justin Hunt {@link http://www.poodll.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assign_submission_onlinepoodll extends assign_submission_plugin {
		
  public function is_enabled() {
      return $this->get_config('enabled') && $this->is_configurable();
  }

  public function is_configurable() {
      $context = context_course::instance($this->assignment->get_course()->id);
      if ($this->get_config('enabled')) {
          return true;
      }
      if (!has_capability('assignsubmission/onlinepoodll:use', $context)) {
          return false;
      }
      return parent::is_configurable();
  }
		
    /**
     * Get the name of the online text submission plugin. It could be a custom name
     * @return string
     */
    public function get_name() {
        if(get_config(constants::M_COMPONENT,'customname')){
            return get_config(constants::M_COMPONENT,'customname');
        }else {
            return get_string('onlinepoodll', constants::M_COMPONENT);
        }
    }

	 /**
     * Get the settings for Online PoodLLsubmission plugin form
     *
     * @global stdClass $CFG
     * @global stdClass $COURSE
     * @param MoodleQuickForm $mform The form to add elements to
     * @return void
     */
    public function get_settings(MoodleQuickForm $mform) {
        global $CFG, $COURSE;

        //start out with a divider to make it clear which plugin's settings these are
        $pluginname = get_string('onlinepoodll',constants::M_COMPONENT);
        $customname = get_config(constants::M_COMPONENT, 'customname');
        if(!empty($customname)){
            $args =new stdClass();
            $args->pluginname = $pluginname;
            $args->customname = $customname;
            $divider = get_string('customdivider', constants::M_COMPONENT,$args);
        }else{
            $divider = get_string('divider',constants::M_COMPONENT,$pluginname);
        }

        //If  M3.4 or lower we show a divider to make it easier to figure where poodll ends and starts
        if($CFG->version < 2017111300) {
            $mform->addElement('static', constants::M_COMPONENT . '_divider', '', $divider);
        }

        $recordertype = $this->get_config('recordertype');

        //convert old Red5 refs to audio media type option
        if($recordertype==constants::M_REPLYVOICE){
            $recordertype=constants::M_REPLYMP3VOICE;
        }

		$boardsize = $this->get_config('boardsize');
		$backimage = $this->get_config('backimage');
		$timelimit = $this->get_config('timelimit');
        $safesave= $this->get_config('safesave');
        $active = $this->get_config('active');
        if($active===false){
            $active = 1;
        }
        $currentsubmission = $this->get_config('showcurrentsubmission');
        if($currentsubmission===false){
            $currentsubmission = get_config(constants::M_COMPONENT, 'showcurrentsubmission');
        }
      
       //get allowed recorders from admin settings
		$allowed_recorders = get_config(constants::M_COMPONENT, 'allowedrecorders');
		$allowed_recorders  = explode(',',$allowed_recorders);
		$recorderoptions = array();
		if(array_search(constants::M_REPLYMP3VOICE,$allowed_recorders)!==false || array_search(constants::M_REPLYVOICE,$allowed_recorders)!==false){
			$recorderoptions[constants::M_REPLYMP3VOICE] = get_string("replymp3voice", constants::M_COMPONENT);
		}

		if(array_search(constants::M_REPLYVIDEO ,$allowed_recorders)!==false){
			$recorderoptions[constants::M_REPLYVIDEO ] = get_string("replyvideo", constants::M_COMPONENT);
		}
		if(array_search(constants::M_REPLYWHITEBOARD,$allowed_recorders)!==false){
			$recorderoptions[constants::M_REPLYWHITEBOARD ] = get_string("replywhiteboard", constants::M_COMPONENT);
		}
		if(array_search(constants::M_REPLYSNAPSHOT,$allowed_recorders)!==false){
			$recorderoptions[constants::M_REPLYSNAPSHOT] = get_string("replysnapshot", constants::M_COMPONENT);
		}

        
		$mform->addElement('select', constants::M_COMPONENT . '_recordertype', get_string("recordertype", constants::M_COMPONENT), $recorderoptions);
        //$mform->addHelpButton(constants::M_COMPONENT . '_recordertype', get_string('onlinepoodll', constants::M_COMPONENT), constants::M_COMPONENT);
        $mform->setDefault(constants::M_COMPONENT . '_recordertype', $recordertype);
		$mform->disabledIf(constants::M_COMPONENT . '_recordertype', constants::M_COMPONENT . '_enabled', 'notchecked');


		//Add a place to set a maximum recording time.
	   $mform->addElement('duration', constants::M_COMPONENT . '_timelimit', get_string('timelimit', constants::M_COMPONENT));
       $mform->setDefault(constants::M_COMPONENT . '_timelimit', $timelimit);
		$mform->disabledIf(constants::M_COMPONENT . '_timelimit', constants::M_COMPONENT . '_enabled', 'notchecked');
		$mform->disabledIf(constants::M_COMPONENT . '_timelimit', constants::M_COMPONENT . '_recordertype', 'eq', constants::M_REPLYWHITEBOARD);
		$mform->disabledIf(constants::M_COMPONENT . '_timelimit', constants::M_COMPONENT . '_recordertype', 'eq', constants::M_REPLYSNAPSHOT);


        //safe save settings
        $mform->addElement('advcheckbox', constants::M_COMPONENT . '_safesave', get_string("safesave", constants::M_COMPONENT));
        $mform->setDefault(constants::M_COMPONENT . '_safesave', $safesave);
        $mform->disabledIf(constants::M_COMPONENT . '_safesave', constants::M_COMPONENT . '_enabled', 'notchecked');



        $csoptions = utils::fetch_options_currentsubmission();
        $mform->addElement('select', constants::M_COMPONENT . '_showcurrentsubmission', get_string("currentsubmission", constants::M_COMPONENT), $csoptions);
        //$mform->addHelpButton(constants::M_COMPONENT . '_recordertype', get_string('onlinepoodll', constants::M_COMPONENT), constants::M_COMPONENT);
        $mform->setDefault(constants::M_COMPONENT . '_showcurrentsubmission', $currentsubmission);
        $mform->disabledIf(constants::M_COMPONENT . '_showcurrentsubmission', constants::M_COMPONENT . '_enabled', 'notchecked');


        $yesnooptions = utils::fetch_options_yesno();
        $mform->addElement('select', constants::M_COMPONENT . '_active', get_string("active", constants::M_COMPONENT), $yesnooptions);
        //$mform->addHelpButton(constants::M_COMPONENT . '_recordertype', get_string('onlinepoodll', constants::M_COMPONENT), constants::M_COMPONENT);
        $mform->setDefault(constants::M_COMPONENT . '_active', $active);
        $mform->disabledIf(constants::M_COMPONENT . '_active', constants::M_COMPONENT . '_enabled', 'notchecked');
        $mform->addHelpButton(constants::M_COMPONENT . '_active','active', constants::M_COMPONENT);

        //these are for the whiteboard submission
	  // added Justin 20121216 back image, and boardsizes, part of whiteboard response
		//For the back image, we 
		//(i) first have to load existing back image files into a draft area
		// (ii) add a file manager element
		//(iii) set the draft area info as the "default" value for the file manager
		if(array_search(constants::M_REPLYWHITEBOARD,$allowed_recorders)!==false){
			$itemid = 0;
			$draftitemid = file_get_submitted_draft_itemid(constants::M_WB_FILEAREA);
			$context =  $this->assignment->get_context();
			if($context) {
				$contextid = $context->id;
			}else{
				$contextid = 0;
			}
			file_prepare_draft_area($draftitemid, $contextid, constants::M_CONFIG_COMPONENT, constants::M_WB_FILEAREA,
			$itemid,
			array('subdirs' => 0, 'maxbytes' => 0, 'maxfiles' => 1));
			$mform->addElement('filemanager', 'backimage', get_string('backimage', constants::M_COMPONENT), null,array('subdirs' => 0, 'maxbytes' => 0, 'maxfiles' => 1));
			$mform->setDefault('backimage', $backimage);
			//commented 20130120 bcause was broken with moodle 2.6. Errors saying "must attach no more than one file" when tried to save, empty, disabled
			//$mform->disabledIf('backimage', constants::M_COMPONENT . '_enabled', 'eq', 0);
			//$mform->disabledIf('backimage', constants::M_COMPONENT . '_recordertype', 'ne', constants::M_REPLYWHITEBOARD );
		}else{
			$mform->addElement('hidden', 'backimage',$backimage);
		}
		$mform->setType('backimage', PARAM_INT);

		
		//board sizes
		if(array_search(constants::M_REPLYWHITEBOARD,$allowed_recorders)!==false){
			$boardsizes = array(
				'320x320' => '320x320',
				'400x600' => '400x600',
				'500x500' => '500x500',
				'600x400' => '600x400',
				'600x800' => '600x800',
				'800x600' => '800x600',
				'1000x800' => '1000x800',
				'1024x768' => '1024x768'
				);
			$mform->addElement('select', constants::M_COMPONENT . '_boardsize',
				get_string('boardsize', constants::M_COMPONENT), $boardsizes);
			$mform->setDefault(constants::M_COMPONENT . '_boardsize', $boardsize);
			$mform->disabledIf(constants::M_COMPONENT . '_boardsize', constants::M_COMPONENT . '_enabled', 'notchecked');
			$mform->disabledIf(constants::M_COMPONENT . '_boardsize', constants::M_COMPONENT . '_recordertype', 'ne', constants::M_REPLYWHITEBOARD );
		}else{
			$mform->addElement('hidden', constants::M_COMPONENT . '_boardsize',$boardsize);
		}
		$mform->setType(constants::M_COMPONENT . '_boardsize', PARAM_TEXT);

        //If  M3.4 or lower we show a divider to make it easier to figure where poodll ends and starts
        if($CFG->version < 2017111300) {
            $mform->addElement('static', constants::M_COMPONENT . '_dividerend', '',
                    get_string('divider', constants::M_COMPONENT, ''));
        }

        //If  M3.4 or higher we can hide unneeded elements
        if($CFG->version >= 2017111300) {
            $mform->hideIf(constants::M_COMPONENT . '_recordertype', constants::M_COMPONENT . '_enabled', 'notchecked');
            $mform->hideIf(constants::M_COMPONENT . '_timelimit', constants::M_COMPONENT . '_enabled', 'notchecked');
            $mform->hideIf(constants::M_COMPONENT . '_showcurrentsubmission', constants::M_COMPONENT . '_enabled', 'notchecked');
            $mform->hideIf(constants::M_COMPONENT . '_safesave', constants::M_COMPONENT . '_enabled', 'notchecked');
            $mform->hideIf(constants::M_COMPONENT . '_active', constants::M_COMPONENT . '_enabled', 'notchecked');
            $mform->hideIf('backimage', constants::M_COMPONENT . '_enabled', 'notchecked');
            $mform->hideIf(constants::M_COMPONENT . '_boardsize', constants::M_COMPONENT . '_enabled', 'notchecked');

            //if no audio or video allowed just hide
            if (array_search(constants::M_REPLYMP3VOICE, $allowed_recorders) === false &&
                    array_search(constants::M_REPLYVOICE, $allowed_recorders) === false &&
                    array_search(constants::M_REPLYVIDEO, $allowed_recorders) === false) {
                $mform->hideIf(constants::M_COMPONENT . '_timelimit', constants::M_COMPONENT . '_enabled', 'checked');
            }
            //if no whiteboard allowed just hide
            if (array_search(constants::M_REPLYWHITEBOARD, $allowed_recorders) === false) {
                $mform->hideIf('backimage', constants::M_COMPONENT . '_enabled', 'checked');
                $mform->hideIf(constants::M_COMPONENT . '_boardsize', constants::M_COMPONENT . '_enabled', 'checked');
            }
        }//end of if M3.6


    }
    
    /**
     * Save the settings for file submission plugin
     *
     * @param stdClass $data
     * @return bool 
     */
    public function save_settings(stdClass $data) {
        $this->set_config('recordertype', $data->{constants::M_COMPONENT . '_recordertype'});
		//if we have a board size, set it
		if(isset($data->{constants::M_COMPONENT . '_boardsize'})){
			$this->set_config('boardsize', $data->{constants::M_COMPONENT . '_boardsize'});
		}else{
			$this->set_config('boardsize', '320x320');
		}
		
		//if we have a time limit, set it
		if(isset($data->{constants::M_COMPONENT . '_timelimit'})){
			$this->set_config('timelimit', $data->{constants::M_COMPONENT . '_timelimit'});
		}else{
			$this->set_config('timelimit', 0);
		}

        //safesave
        if(isset($data->{constants::M_COMPONENT . '_safesave'})) {
            $this->set_config('safesave', $data->{constants::M_COMPONENT . '_safesave'});
        }else{
            $this->set_config('safesave', 0);
        }

		//active
        if(isset($data->{constants::M_COMPONENT . '_active'})){
            $this->set_config('active', $data->{constants::M_COMPONENT . '_active'});
        }else{
            $this->set_config('active', 1);
        }
        //current submission
        if(isset($data->{constants::M_COMPONENT . '_showcurrentsubmission'})){
            $this->set_config('showcurrentsubmission', $data->{constants::M_COMPONENT . '_showcurrentsubmission'});
        }else{
            $this->set_config('showcurrentsubmission', 1);
        }
		
		// $this->set_config('backimage', $data->assignsubmission_onlinepoodll_backimage);
		//error_log(print_r($this->assignment,true));
		//error_log(print_r($data,true));
		$itemid = $data->instance;
		//error_log(print_r($this,true));
		//$itemid = $this->id;
		$itemid = 0;
		if(isset($data->backimage)){
			file_save_draft_area_files($data->backimage, 
								$this->assignment->get_context()->id, 
								constants::M_CONFIG_COMPONENT,
								constants::M_WB_FILEAREA, $itemid,
								array('subdirs' => 0, 'maxbytes' => 0, 'maxfiles' => 1));
			$this->set_config('backimage', $data->backimage);
		}else{
			$this->set_config('backimage',null);
		}
	
	/*
		error_log(print_r($data,true));
	$draftitemid = file_get_submitted_draft_itemid(constants::M_COMPONENT . '_backimage');
	file_prepare_draft_area($draftitemid, $this->context->id, constants::M_COMPONENT, constants::M_WB_FILEAREA,
		!empty($data->coursemodule) ? (int) $data->coursemodule : null,
		array('subdirs' => 0, 'maxbytes' => 0, 'maxfiles' => 1));
		*/
	

        return true;
    }

   /**
    * Get onlinepoodll submission information from the database
    *
    * @param  int $submissionid
    * @return mixed
    */
    private function get_onlinepoodll_submission($submissionid) {
        global $DB;

        return $DB->get_record(constants::M_TABLE, array('submission'=>$submissionid));
    }

    /**
     * Add form elements onlinepoodll submissions
     *
     * @param mixed $submission can be null
     * @param MoodleQuickForm $mform
     * @param stdClass $data
     * @return true if elements were added to the form
     */
    public function get_form_elements($submission, MoodleQuickForm $mform, stdClass $data) {
		 global $CFG, $USER, $PAGE;

        //active
        $active = $this->get_config('active');
        if($active===false){
            $active=1;
        }
        //current submission
        $showcurrentsubmission = $this->get_config('showcurrentsubmission');
        if($showcurrentsubmission===false){
            $showcurrentsubmission=get_config(constants::M_COMPONENT, 'showcurrentsubmission');
        }

        //determine if toggling recorder or current submission
        switch($showcurrentsubmission){
            case constants::M_CURRENTSUBMISSION_ALWAYS:
                $togglingthing = 'recorder';
                break;
            case constants::M_CURRENTSUBMISSION_SHOWEXPANDED:
            case constants::M_CURRENTSUBMISSION_SHOWMINIMIZED:
            default:
                $togglingthing = 'currentsubmission';
        }

        //prepare the AMD javascript for deletesubmission  and toggle
        $opts = array(
                "component"=> constants::M_COMPONENT,
                "filecontrolid"=> constants::M_FILENAMECONTROL,
                "expandcurrentsubmission"=>($showcurrentsubmission==constants::M_CURRENTSUBMISSION_SHOWEXPANDED),
                "togglingthing"=> $togglingthing,
                "safesave"=>$this->get_config('safesave')
        );
        $PAGE->requires->js_call_amd(constants::M_COMPONENT . "/submissionhelper", 'init', array($opts));
        $PAGE->requires->strings_for_js(array('reallydeletesubmission','clicktohide','clicktoshow'),constants::M_COMPONENT);

        //Get our renderers
        $renderer = $PAGE->get_renderer(constants::M_COMPONENT);

        $submissionid = $submission ? $submission->id : 0;
        $responses = '';

        if ($submission && $showcurrentsubmission) {

            //show the previous submission in a player or whatever
            $responses = $this->fetchResponses($submission->id, false);
            if ($responses != '') {

                if($active) {
                    $deletesubmission = $renderer->fetch_delete_submission();
                }else{
                    $deletesubmission = '';
                }
                //show current submission
                $currentsubmission = $renderer->prepare_current_submission($responses, $deletesubmission,$showcurrentsubmission);

                //are we toggling current submission
                if($togglingthing == 'currentsubmission'){
                    $currentsubmission = $renderer->prepare_toggle_controls() . $currentsubmission;
                }

                $mform->addElement('static', 'currentsubmission',
                        get_string('currentsubmission', constants::M_COMPONENT),
                        $currentsubmission);

            }
        }
        
         if (!isset($data->vectordata)) {
            $data->vectordata = '';
        }

        if ($submission) {
            $onlinepoodllsubmission = $this->get_onlinepoodll_submission($submission->id);
            if ($onlinepoodllsubmission) {
                $data->vectordata = $onlinepoodllsubmission->vectordata;
            }

        }

		//we need to get a draft item id, but if the form was cancelled or invalid
        //the old draftitemid will get set to the form, and the newly fetched one passed to oru recorders
        //this makes the form submit fail, because the uploaded files can't be found
        //hence we check for a previous submission first. Justin 2017/02/05
        $draftitemid = optional_param('draftitemid',0,PARAM_INT);
        if($draftitemid==0) {
            $draftitemid = file_get_submitted_draft_itemid(constants::M_FILENAMECONTROL);
        }

        //We prepare our form here and fetch/save data in SAVE method
        $usercontextid=context_user::instance($USER->id)->id;
        $contextid=$this->assignment->get_context()->id;
		file_prepare_draft_area($draftitemid, $contextid, constants::M_COMPONENT, constants::M_FILEAREA, $submissionid, null,null);
		$mform->addElement('hidden', 'draftitemid', $draftitemid);
		$mform->addElement('hidden', 'usercontextid', $usercontextid);	
		$mform->addElement('hidden', constants::M_FILENAMECONTROL, '',array('id' => constants::M_FILENAMECONTROL));
		$mform->addElement('hidden', constants::M_VECTORCONTROL, $data->vectordata,array('id' => constants::M_VECTORCONTROL));
		$mform->setType('draftitemid', PARAM_INT);
		$mform->setType('usercontextid', PARAM_INT); 
		$mform->setType(constants::M_FILENAMECONTROL, PARAM_TEXT);
		$mform->setType(constants::M_VECTORCONTROL, PARAM_TEXT);


		//if this is inactive we do not want to show the recorders, so we just return here
		if(!$active){
		    return true;
        }

	
		//get timelimit for recorders if set
		$timelimit = $this->get_config('timelimit');
		$hints = Array();
		$hints['modulecontextid']=$contextid;
		$callbackjs ='prassigsub';

        //are we toggling recorder
        $toggler = '';
        if($togglingthing == 'recorder' && $submission && $responses != ''){
            $toggler = $renderer->prepare_toggle_controls();
        }


        //fetch the required "recorder
		switch($this->get_config('recordertype')){

            case constants::M_REPLYVOICE:
			case constants::M_REPLYMP3VOICE:
				$mediadata= \filter_poodll\poodlltools::fetchMP3RecorderForSubmission(constants::M_FILENAMECONTROL, $usercontextid ,
                    'user','draft',$draftitemid,$timelimit,$callbackjs,$hints);
                $mediadiv = $toggler . $renderer->prepare_recorder($mediadata,$showcurrentsubmission,$toggler);
                $mform->addElement('static', 'description', get_string('replymp3voice',constants::M_COMPONENT),$mediadiv);
				break;
				
			case constants::M_REPLYWHITEBOARD:
				//get board sizes
				switch($this->get_config('boardsize')){
					case "320x320": $width=320;$height=320;break;
					case "400x600": $width=400;$height=600;break;
					case "500x500": $width=500;$height=500;break;
					case "600x400": $width=600;$height=400;break;
					case "600x800": $width=600;$height=800;break;
					case "800x600": $width=800;$height=600;break;
					case "1000x800": $width=1000;$height=800;break;
					case "1024x768": $width=1024;$height=768;break;
				}


				//Get Backimage, if we have one
				// get file system handle for fetching url to submitted media prompt (if there is one) 
				$fs = get_file_storage();
				$itemid=0;
				$files = $fs->get_area_files($contextid, constants::M_CONFIG_COMPONENT,
								constants::M_WB_FILEAREA,
								$itemid);
				$imageurl="";
				if($files && count($files)>0){
					$file = array_pop($files);
					$imageurl = file_rewrite_pluginfile_urls('@@PLUGINFILE@@/' . $file->get_filename(), 
								'pluginfile.php', 
								$file->get_contextid(), 
								$file->get_component(), 
								$file->get_filearea(), 
								$file->get_itemid());
				
				}
				
				$vectorcontrol = constants::M_VECTORCONTROL;
				$vectordata=$data->vectordata;
				$mediadata= \filter_poodll\poodlltools::fetchWhiteboardForSubmission(constants::M_FILENAMECONTROL, $usercontextid ,'user','draft',$draftitemid, $width, $height, $imageurl,'','',$vectorcontrol,$vectordata);
                $mediadiv = $toggler . $renderer->prepare_recorder($mediadata,$showcurrentsubmission,$toggler);
				$mform->addElement('static', 'description', get_string('replywhiteboard',constants::M_COMPONENT),$mediadiv);
				break;
			
			case constants::M_REPLYSNAPSHOT:
				$mediadata= \filter_poodll\poodlltools::fetchHTML5SnapshotCamera(constants::M_FILENAMECONTROL,350,400,$usercontextid,'user','draft',$draftitemid);
                $mediadiv = $toggler . $renderer->prepare_recorder($mediadata,$showcurrentsubmission,$toggler);
				$mform->addElement('static', 'description', get_string('replysnapshot',constants::M_COMPONENT),$mediadiv);
				break;

			case constants::M_REPLYVIDEO:
				$mediadata= \filter_poodll\poodlltools::fetchVideoRecorderForSubmission('swf','onlinepoodll',constants::M_FILENAMECONTROL,
                    $usercontextid ,'user','draft',$draftitemid,$timelimit,$callbackjs,$hints);
                $mediadiv = $toggler . $renderer->prepare_recorder($mediadata,$showcurrentsubmission,$toggler);
				$mform->addElement('static', 'description', get_string('replyvideo',constants::M_COMPONENT),$mediadiv);
									
				break;
					
		}

        // hidden params: Pretty sure we don't need this. Justin 20170523
        //$mform->addElement('hidden', 'id', 0);
        //$mform->setType('id', PARAM_INT);
		return true;
    }
	
	/*
	* Fetch the player to show the submitted recording(s)
	*
	*
	*
	*/
	function fetchResponses($submissionid, $checkfordata=false){
		global $CFG, $OUTPUT;

		$responsestring = "";
		
		//get filename, from the filearea for this submission. 
		//there should be only one.
		$fs = get_file_storage();
		$filename="";
        $files = $fs->get_area_files($this->assignment->get_context()->id, 
			constants::M_COMPONENT,
			constants::M_FILEAREA,
			$submissionid, "id", false);
			
        //if we have files, lets get them ready
		if (!empty($files)) {
			//if the filename property exists, and is filled, use that to fetch the file
			$onlinepoodllsubmission = $this->get_onlinepoodll_submission($submissionid);
			if(isset($onlinepoodllsubmission->filename) && !empty($onlinepoodllsubmission->filename)){
				$filename =  $onlinepoodllsubmission->filename;
				
			//if no filename property just take the first file. That is how we used to do it	
			}else{
				foreach ($files as $file) {
					$filename = $file->get_filename();
					break;
				}
			}
		}

        //size params for our response players/images
        //audio is a simple 1 or 0 for display or not
        $size = $this->fetch_response_size($this->get_config('recordertype'));

		
		//if this is a playback area, for teacher, show a string if no file
		if ($checkfordata  && empty($filename)){ 
					$responsestring .= "No submission found";
					
		//if the student is viewing and there is no file , just show an empty area
		}elseif(empty($filename)){
				$responsestring .= "";
				
		}else{	
			//The path to any media file we should play
			$rawmediapath = $CFG->wwwroot.'/pluginfile.php/'.$this->assignment->get_context()->id.'/'. constants::M_COMPONENT .'/' . constants::M_FILEAREA  . '/'.$submissionid.'/'.$filename;
			$mediapath = urlencode($rawmediapath);

			//prepare our response string, which will parsed and replaced with the necessary player
			switch($this->get_config('recordertype')){

                case constants::M_REPLYVOICE:
				case constants::M_REPLYMP3VOICE:
				    if($size) {
                        $options= new stdClass();
                        $options->mediaurl = $rawmediapath;
                        $options->islist = $this->is_list();
                        $responsestring .=$OUTPUT->render_from_template(constants::M_COMPONENT . '/audioplayerstandard', $options);
                    }else{
                        $responsestring=get_string('audioplaceholder',constants::M_COMPONENT);
                    }
                    break;
					
				case constants::M_REPLYVIDEO:
						if($size->width==0){
							$responsestring=get_string('videoplaceholder',constants::M_COMPONENT);
							break;
						}
					    $options= new stdClass();
					    $options->mediaurl = $rawmediapath;
                        $options->islist = $this->is_list();
                        $responsestring .=$OUTPUT->render_from_template(constants::M_COMPONENT . '/videoplayerstandard', $options);
						break;

				case constants::M_REPLYWHITEBOARD:
                    if($size->width==0){
                        $responsestring=get_string('imageplaceholder',constants::M_COMPONENT);
                        break;
                    }
					$responsestring .= "<img alt='submittedimage' class='assignsubmission_onlinepoodll_whiteboard'" .
                        "src='" . urldecode($mediapath) . "' style='max-width: $size->width" . "px;' />";
					break;
					
				case constants::M_REPLYSNAPSHOT:
                    if($size->width==0){
                        $responsestring=get_string('imageplaceholder',constants::M_COMPONENT);
                        break;
                    }
					$responsestring .= "<img alt='submittedimage' class='assignsubmission_onlinepoodll_snapshot'" .
                            "src='" . urldecode($mediapath) . "' style='max-width: $size->width" . "px;' />";
					break;
					
				default:
					$responsestring .= format_text("<a href='$rawmediapath'>$filename</a>", FORMAT_HTML);
					break;	
				
			}//end of switch
		}//end of if (checkfordata ...)
		
		return $responsestring;
		
	}//end of fetchResponses
	
    public function	fetch_response_size($recordertype){

	        //is this a list view
            $islist = $this->is_list();

            //build our sizes array
            $sizes=array();
            $sizes['0']=new stdClass();$sizes['0']->width=0;$sizes['0']->height=0;
            $sizes['160']=new stdClass();$sizes['160']->width=160;$sizes['160']->height=120;
            $sizes['320']=new stdClass();$sizes['320']->width=320;$sizes['320']->height=240;
            $sizes['480']=new stdClass();$sizes['480']->width=480;$sizes['480']->height=360;
            $sizes['640']=new stdClass();$sizes['640']->width=640;$sizes['640']->height=480;
            $sizes['800']=new stdClass();$sizes['800']->width=800;$sizes['800']->height=600;
            $sizes['1024']=new stdClass();$sizes['1024']->width=1024;$sizes['1024']->height=768;

            $size=$sizes[0];
            $config=get_config(constants::M_COMPONENT);

        //prepare our response string, which will parsed and replaced with the necessary player
        switch($recordertype){
            case constants::M_REPLYVIDEO:
                $size=$islist ? $sizes[$config->displaysize_list] : $sizes[$config->displaysize_single] ;
                break;
            case constants::M_REPLYWHITEBOARD:
                $size=$islist ? $sizes[$config->whiteboard_displaysize_list] : $sizes[$config->whiteboard_displaysize_single] ;
                break;
            case constants::M_REPLYSNAPSHOT:
                $size=$islist ? $sizes[$config->snapshot_displaysize_list] : $sizes[$config->snapshot_displaysize_single] ;
                break;
            case constants::M_REPLYVOICE:
            case constants::M_REPLYMP3VOICE:
                $size=$islist ? $config->displayaudioplayer_list : $config->displayaudioplayer_single ;
                break;
            case constants::M_REPLYTALKBACK:
            default:
                break;

        }//end of switch
        return $size;

    }

    public function is_list (){
        //is this a list view
        return optional_param('action','',PARAM_TEXT)=='grading';
    }
	
     /**
      * Save data to the database
      *
      * @param stdClass $submission
      * @param stdClass $data
      * @return bool
      */
     public function save(stdClass $submission, stdClass $data) {
        global $DB;


		//Move recorded files from draft to the correct area
		//if filename is false, no update. possibly used changed something else on page
		$filename = $this->shift_draft_file($submission);
		if(!$filename){return true;}

        $onlinepoodllsubmission = $this->get_onlinepoodll_submission($submission->id);
        //fetch any vectordata we might have for the whiteboard
        $vectordata = optional_param(constants::M_VECTORCONTROL, '', PARAM_RAW);

        if ($onlinepoodllsubmission) {
			$onlinepoodllsubmission->filename = $filename;
			$onlinepoodllsubmission->vectordata = $vectordata;
			$ret = $DB->update_record(constants::M_TABLE, $onlinepoodllsubmission);
        } else {
            $onlinepoodllsubmission = new stdClass();
            $onlinepoodllsubmission->submission = $submission->id;
            $onlinepoodllsubmission->assignment = $this->assignment->get_instance()->id;
            $onlinepoodllsubmission->recorder = $this->get_config('recordertype');
			$onlinepoodllsubmission->filename = $filename;
			$onlinepoodllsubmission->vectordata = $vectordata;
            $ret = $DB->insert_record(constants::M_TABLE, $onlinepoodllsubmission) > 0;
        }
		 return $ret;

    }
    
    
    function shift_draft_file($submission) {
        global $CFG, $USER, $DB,$COURSE;

		//When we add the recorder via the poodll filter, it adds a hidden form field of the name constants::M_FILENAMECONTROL
		//the recorder updates that field with the filename of the audio/video it recorded. We pick up that filename here.


		$filename = optional_param(constants::M_FILENAMECONTROL, '', PARAM_RAW);
		$draftitemid = optional_param('draftitemid', '', PARAM_RAW);
		$usercontextid = optional_param('usercontextid', '', PARAM_RAW);

		//Don't do anything in the case that the filename is empty
		//possibly the user is just updating something else on the page(eg an online text submission)
		//if we overwrite here, we might trash their existing poodll submission file
		if($filename==''){return false;}
		
		 $fs = get_file_storage();
		 $browser = get_file_browser();
         $fs->delete_area_files($this->assignment->get_context()->id, constants::M_COMPONENT,constants::M_FILEAREA , $submission->id);

        //if filename = -1 we are being told to delete the file
        //so we have done enough
        if($filename==-1){
            return '';
        }

		//fetch the file info object for our original file
		$original_context = context::instance_by_id($usercontextid);
		$draft_fileinfo = $browser->get_file_info($original_context, 'user','draft', $draftitemid, '/', $filename);

		//perform the copy
		if($draft_fileinfo){
			
			//create the file record for our new file
			$file_record = array(
			'userid' => $USER->id,
			'contextid'=>$this->assignment->get_context()->id, 
			'component'=>constants::M_COMPONENT,
			'filearea'=>constants::M_FILEAREA ,
			'itemid'=>$submission->id, 
			'filepath'=>'/', 
			'filename'=>$filename,
			'author'=>'moodle user',
			'license'=>'allrighttsreserved',		
			'timecreated'=>time(), 
			'timemodified'=>time()
			);
			$ret = $draft_fileinfo->copy_to_storage($file_record);
			
		}//end of if $draft_fileinfo

		return $filename;

	}//end of shift_draft_file
    



    /**
     * Display the list of files  in the submission status table
     *
     * @param stdClass $submission
     * @param bool $showviewlink Set this to true if the list of files is long
     * @return string
     */
    public function view_summary(stdClass $submission, & $showviewlink) {
    	$showviewlink = false;


		//our response, this will output a player/image, and optionally a portfolio export link
		return $this->fetchResponses($submission->id,false);// . $this->get_p_links($submission->id) ;
		//rely on get_files from now on to generate portfolio links Justin 19/06/2014

    }

      /**
     * Produce a list of files suitable for export that represent this feedback or submission
     *
     * @param stdClass $submission The submission
     * @return array - return an array of files indexed by filename
     */
    public function get_files(stdClass $submission, stdClass $user=null) {
        $result = array();
        $fs = get_file_storage();

        $files = $fs->get_area_files($this->assignment->get_context()->id, constants::M_COMPONENT, constants::M_FILEAREA, $submission->id, "timemodified", false);

        foreach ($files as $file) {
		
			//let NOT return splash images for videos
			if($this->get_config('recordertype')== constants::M_REPLYVIDEO){
				$fname = $file->get_filename();
				$fext = pathinfo($fname, PATHINFO_EXTENSION);				
				if($fext == 'jpg' || $fext == 'png'){
					continue;
				}
			}
		
            $result[$file->get_filename()] = $file;
        }
        return $result;
    }

    /**
     * Display the saved text content from the editor in the view table
     *
     * @param stdClass $submission
     * @return string
     */
    public function view(stdClass $submission) {
        $result = '';

        $onlinepoodllsubmission = $this->get_onlinepoodll_submission($submission->id);

        if ($onlinepoodllsubmission) {

            // show our responses in a player
			$result = $this->fetchResponses($submission->id,false);
		
			//the default render method. Only shows a link
			// return $this->assignment->render_area_files(constants::M_COMPONENT, ASSIGN_FILEAREA_SUBMISSION_ONLINEPOODLL, $submission->id);

        }

        return $result;
    }
	
	
	    /**
     * Produces a list of portfolio links to the file recorded byuser
     *
     * @param $submissionid this submission's id
     * @return string the portfolio export link
     */
    public function get_p_links($submissionid) {
        global $CFG, $OUTPUT, $DB;

		$output ="";
        $fs = get_file_storage();
        $files = $fs->get_area_files($this->assignment->get_context()->id, 
					constants::M_COMPONENT,
					constants::M_FILEAREA,
					$submissionid, "id", false);
					
        if (!empty($files)) {
        	//this was nec for portfolios prior to M2.7. 
        	if(file_exists($CFG->dirroot . '/mod/assignment/locallib.php')){
            	require_once($CFG->dirroot . '/mod/assignment/locallib.php');
            }
            if ($CFG->enableportfolios) {
                require_once($CFG->libdir.'/portfoliolib.php');
            }
            
			//Add portfolio download links if appropriate
            foreach ($files as $file) {
				
				//in the case of splash images we will have two files.
				//we just want one link, and for the video file
				if($this->get_config('recordertype')== constants::M_REPLYVIDEO){
					$fname = $file->get_filename();
					$fext = pathinfo($fname, PATHINFO_EXTENSION);				
					if($fext == 'jpg' || $fext == 'png'){
						continue;
					}
				}
                
				
				if ($CFG->enableportfolios && has_capability('mod/assign:exportownsubmission', $this->assignment->get_context())){
					require_once($CFG->libdir . '/portfoliolib.php');
					$button = new portfolio_add_button();
                   
				   //API changes. See https://github.com/moodle/moodle/blob/master/portfolio/upgrade.txt#L6
				   if($CFG->version < 2012120300){
						$finalparam='/mod/assign/portfolio_callback.php';
					}else{
						$finalparam='mod_assign';
					}
				   
					$button->set_callback_options('assign_portfolio_caller', 
							array('cmid' => $this->assignment->get_course_module()->id,
											'component' => constants::M_COMPONENT,
											'area'=>constants::M_FILEAREA,
											'sid' => $submissionid),
							$finalparam);
                    $button->set_format_by_file($file);
                    $output .= $button->to_html(PORTFOLIO_ADD_TEXT_LINK);
                }
               
                $output .= '<br />' ;
            }
        }

        $output = '<div class="files" style="float:left;margin-left:5px;">'.$output.'</div><br clear="all" />';
        
        return $output;

    }

     /**
     * Return true if this plugin can upgrade an old Moodle 2.2 assignment of this type and version.
     *
     * @param string $type old assignment subtype
     * @param int $version old assignment version
     * @return bool True if upgrade is possible
     */
    public function can_upgrade($type, $version) {
        if ($type == 'poodllonline' && $version >= 2011112900) {
            return true;
        }
        return false;
    }


    /**
     * Upgrade the settings from the old assignment to the new plugin based one
     *
     * @param context $oldcontext - the database for the old assignment context
     * @param stdClass $oldassignment - the database for the old assignment instance
     * @param string $log record log events here
     * @return bool Was it a success?
     */
    public function upgrade_settings(context $oldcontext, stdClass $oldassignment, & $log) {
		switch($oldassignment->var3){
			case 0:
			case 1:
			case 2:
			case 7:
				$this->set_config('recordertype',  constants::M_REPLYMP3VOICE);
				break;
			case 3:
			case 4:
				$this->set_config('recordertype',  constants::M_REPLYVIDEO);
				break;
			case 5:
				$this->set_config('recordertype',  constants::M_REPLYTALKBACK);
				break;
			case 6:
				$this->set_config('recordertype',  constants::M_REPLYWHITEBOARD);
				break;
		}

        return true;
    }

    /**
     * Upgrade the submission from the old assignment to the new one
     *
     * @param context $oldcontext - the database for the old assignment context
     * @param stdClass $oldassignment The data record for the old assignment
     * @param stdClass $oldsubmission The data record for the old submission
     * @param stdClass $submission The data record for the new submission
     * @param string $log Record upgrade messages in the log
     * @return bool true or false - false will trigger a rollback
     */
    public function upgrade(context $oldcontext, stdClass $oldassignment, stdClass $oldsubmission, stdClass $submission, & $log) {
        global $DB;

        $onlinepoodllsubmission = new stdClass();

        $onlinepoodllsubmission->submission = $submission->id;
        $onlinepoodllsubmission->assignment = $this->assignment->get_instance()->id;

    

        if (!$DB->insert_record(constants::M_TABLE, $onlinepoodllsubmission) > 0) {
            $log .= get_string('couldnotconvertsubmission', 'mod_assign', $submission->userid);
            return false;
        }

        // now copy the area files
        $this->assignment->copy_area_files_for_upgrade($oldcontext->id,
                                                        'mod_assignment',
                                                        'submission',
                                                        $oldsubmission->id,
                                                        // New file area
                                                        $this->assignment->get_context()->id,
                                                        constants::M_COMPONENT,
                                                        constants::M_FILEAREA,
                                                        $submission->id);
        return true;
    }

    /**
     * Return the plugin configs for external functions.
     *
     * @return array the list of settings
     */
    public function get_config_for_external() {
        return (array) $this->get_config();
    }

    /**
     * Copy the student's submission from a previous submission. Used when a student opts to base their resubmission
     * on the last submission.
     * @param stdClass $sourcesubmission
     * @param stdClass $destsubmission
     */
    public function copy_submission(stdClass $sourcesubmission, stdClass $destsubmission) {
        global $DB;

        // Copy the files across
        $contextid = $this->assignment->get_context()->id;
        $fs = get_file_storage();
        $files = $fs->get_area_files($contextid, constants::M_COMPONENT,
                constants::M_FILEAREA, $sourcesubmission->id, 'id', false);
        foreach ($files as $file) {
            $fieldupdates = array('itemid' => $destsubmission->id);
            $fs->create_file_from_storedfile($fieldupdates, $file);
        }

        // Copy the assignsubmission plugin record.
        $thesubmission = $this->get_onlinepoodll_submission($sourcesubmission->id);
        if ($thesubmission) {
            unset($thesubmission->id);
            $thesubmission->submission = $destsubmission->id;
            $DB->insert_record(constants::M_TABLE, $thesubmission);
        }
        return true;
    }

    /**
     * Remove a submission.
     *
     * @param stdClass $submission The submission
     * @return boolean
     */
    public function remove(stdClass $submission) {
        global $DB;
        //delete database record
        $submissionid = $submission ? $submission->id : 0;
        if ($submissionid) {
            $DB->delete_records(constants::M_TABLE, array('submission' => $submissionid));

            //delete recorded files
            $fs = get_file_storage();
            $fs->delete_area_files($this->assignment->get_context()->id,
                    constants::M_COMPONENT,
                    constants::M_FILEAREA,
                    $submission->id);
        }



        return true;
    }


    /**
     * Formatting for log info
     *
     * @param stdClass $submission The new submission
     * @return string
     */
    public function format_for_log(stdClass $submission) {
        // format the info for each submission plugin add_to_log
      //  $onlinepoodllsubmission = $this->get_onlinepoodll_submission($submission->id);
        $onlinepoodllloginfo = '';

        $onlinepoodllloginfo .= "submission id:" . $submission->id . " added.";

        return $onlinepoodllloginfo;
    }

    /**
     * The assignment has been deleted - cleanup
     *
     * @return bool
     */
    public function delete_instance() {
        global $DB;
        // will throw exception on failure
        $DB->delete_records(constants::M_TABLE, array('assignment'=>$this->assignment->get_instance()->id));
        return true;
    }

    /**
     * No text is set for this plugin
     *
     * @param stdClass $submission
     * @return bool
     */
    public function is_empty(stdClass $submission) {
        return $this->view($submission) == '';
    }

    /**
     * Get file areas returns a list of areas this plugin stores files
     * @return array - An array of fileareas (keys) and descriptions (values)
     */
    public function get_file_areas() {
  
        return array(constants::M_FILEAREA=>constants::M_COMPONENT,
        	constants::M_WB_FILEAREA=>constants::M_COMPONENT . " whiteboard backimage");
    }

}


