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
 * This file contains the definition for the library class for poodll feedback plugin
 *
 *
 * @package   assignfeedback_poodll
 * @copyright 2013 Justin Hunt {@link http://www.poodll.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use assignfeedback_poodll\constants;
use assignfeedback_poodll\utils;

/**
 * library class for PoodLL feedback plugin extending feedback plugin base class
 *
 * @package   assignfeedback_poodll
 * @copyright 2013 Justin Hunt {@link http://www.poodll.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assign_feedback_poodll extends assign_feedback_plugin {
	
	  public function is_enabled() {
	      return $this->get_config('enabled') && $this->is_configurable();
	  }

	  public function is_configurable() {
	      $context = context_course::instance($this->assignment->get_course()->id);
	      if ($this->get_config('enabled')) {
	          return true;
	      }
	      if (!has_capability('assignfeedback/' . constants::M_SUBPLUGIN . ':use', $context)) {
	          return false;
	      }
	      return parent::is_configurable();
	  }
	
   /**
    * Get the name of the online comment feedback plugin
    * @return string
    */
    public function get_name() {
        return get_string('pluginname', constants::M_COMPONENT);
    }

    /**
     * Get the PoodLL feedback  from the database
     *
     * @param int $gradeid
     * @return stdClass|false The feedback poodll for the given grade if it exists. False if it doesn't.
     */
    public function get_feedback_poodll($gradeid) {
        global $DB;
        return $DB->get_record(constants::M_TABLE, array('grade'=>$gradeid));
    }
    
    	    /**
     * Get the settings for PoodLL Feedback plugin form
     *
     * @global stdClass $CFG
     * @global stdClass $COURSE
     * @param MoodleQuickForm $mform The form to add elements to
     * @return void
     */
    public function get_settings(MoodleQuickForm $mform) {
        global $CFG, $COURSE;

		//get saved values and return them as defaults
        $recordertype = $this->get_config('recordertype');

        //convert old Red5 refs to audio media type option
        if($recordertype==constants::M_REPLYVOICE){
            $recordertype=constants::M_REPLYMP3VOICE;
        }
		$boardsize = $this->get_config('boardsize');
		$downloadsok = $this->get_config('downloadsok');
		
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

		//determine our active or inactive status. Active is a way of allowing old feedbacks to display, but no new ones to appear
        $active = $this->get_config('active');
		//false means that this setting has not been set yet, and in that case it ought to be active
        if($active===false){
            $active = 1;
        }
		
	
	$mform->addElement('select', constants::M_COMPONENT . '_recordertype', get_string("recordertype", constants::M_COMPONENT), $recorderoptions);
        //$mform->addHelpButton(constants::M_COMPONENT . '_recordertype', get_string('onlinepoodll', ASSIGNSUBMISSION_ONLINEPOODLL_COMPONENT), ASSIGNSUBMISSION_ONLINEPOODLL_COMPONENT);
    $mform->setDefault(constants::M_COMPONENT . '_recordertype', $recordertype);
	$mform->disabledIf(constants::M_COMPONENT . '_recordertype', constants::M_COMPONENT . '_enabled', 'notchecked');
	
	//Are students and teachers shown the download link for the feedback recording
	$yesno_options = array( 1 => get_string("yes", constants::M_COMPONENT),
				0 => get_string("no", constants::M_COMPONENT));
	$mform->addElement('select', constants::M_COMPONENT . '_downloadsok', get_string('downloadsok', constants::M_COMPONENT), $yesno_options);
	$mform->setDefault(constants::M_COMPONENT . '_downloadsok', $downloadsok);
    $mform->disabledIf(constants::M_COMPONENT . '_downloadsok', constants::M_COMPONENT . '_enabled', 'notchecked');


        //If whiteboard not allowed, not much point showing boardsizes
		if(array_search(constants::M_REPLYWHITEBOARD,$allowed_recorders)!==false){
				//board sizes for the whiteboard feedback
				$boardsizes = array(
						'320x320' => '320x320',
						'400x600' => '400x600',
						'500x500' => '500x500',
						'600x400' => '600x400',
						'600x800' => '600x800',
						'800x600' => '800x600'
						);
				$mform->addElement('select', constants::M_COMPONENT . '_boardsize',
						get_string('boardsize', constants::M_COMPONENT), $boardsizes);
				$mform->setDefault(constants::M_COMPONENT . '_boardsize', $boardsize);
				$mform->disabledIf(constants::M_COMPONENT . '_boardsize', constants::M_COMPONENT . '_enabled', 'eq', 0);
				$mform->disabledIf(constants::M_COMPONENT . '_boardsize', constants::M_COMPONENT . '_recordertype', 'ne', constants::M_REPLYWHITEBOARD );
		}//end of if whiteboard

        $yesnooptions = utils::fetch_options_yesno();
        $mform->addElement('select', constants::M_COMPONENT . '_active', get_string("active", constants::M_COMPONENT), $yesnooptions);
        $mform->setDefault(constants::M_COMPONENT . '_active', $active);
        $mform->disabledIf(constants::M_COMPONENT . '_active', constants::M_COMPONENT . '_enabled', 'notchecked');
        $mform->addHelpButton(constants::M_COMPONENT . '_active','active', constants::M_COMPONENT);

        //If M3.4 or higher we can hide elements when we need to
        if($CFG->version >= 2017111300) {
            $mform->hideIf(constants::M_COMPONENT . '_recordertype', constants::M_COMPONENT . '_enabled', 'notchecked');
            $mform->hideIf(constants::M_COMPONENT . '_downloadsok', constants::M_COMPONENT . '_enabled', 'notchecked');
            $mform->hideIf(constants::M_COMPONENT . '_boardsize', constants::M_COMPONENT . '_enabled', 'notchecked');
            $mform->hideIf(constants::M_COMPONENT . '_active', constants::M_COMPONENT . '_enabled', 'notchecked');
        }

    }//end of function
    
    
       /**
     * Save the settings for poodll feedback plugin
     *
     * @param stdClass $data
     * @return bool 
     */
    public function save_settings(stdClass $data) {

        $this->set_config('recordertype', $data->{constants::M_COMPONENT . '_recordertype'});
		$this->set_config('downloadsok', $data->{constants::M_COMPONENT . '_downloadsok'});
		
		//if we have a board size, set it
		if(isset($data->{constants::M_COMPONENT . '_boardsize'})){
			$this->set_config('boardsize', $data->{constants::M_COMPONENT . '_boardsize'});
		}else{
			$this->set_config('boardsize', '320x320');
		}

        //active
        if(isset($data->{constants::M_COMPONENT . '_active'})){
            $this->set_config('active', $data->{constants::M_COMPONENT . '_active'});
        }else{
            $this->set_config('active', 1);
        }
		
	
        return true;
    }
    
    function shift_draft_file($grade, $data) {
        global $CFG, $USER, $DB,$COURSE;	
 
	//When we add the recorder via the poodll filter, it adds a hidden form field of the name constants::M_FILENAMECONTROL
	//the recorder updates that field with the filename of the audio/video it recorded. We pick up that filename here.
	$filename ='';     
	$draftitemid = 	0;
	if(property_exists($data,constants::M_FILENAMECONTROL) && !empty($data->{constants::M_FILENAMECONTROL})){
		$filename = $data->{constants::M_FILENAMECONTROL};
		$draftitemid = $data->draftitemid;
	}
	
		//Don't do anything in this case
		//possibly the user is just updating something else on the page(eg grade)
		//if we overwrite here, we might trash their existing poodllfeedback file
		if($filename=='' || $filename==null){return false;}
        
        //if this should fail, we get regular user context, is it the same anyway?
        $usercontextid = optional_param('usercontextid', '', PARAM_RAW);
        if ($usercontextid == ''){
        	$usercontextid = context_user::instance($USER->id)->id;
        }
         
         $fs = get_file_storage();
         $browser = get_file_browser();
         $fs->delete_area_files($this->assignment->get_context()->id, constants::M_COMPONENT,constants::M_FILEAREA , $grade->id);
		
		
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
			'filearea'=>constants::M_FILEAREA,
			'itemid'=>$grade->id, 
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
     * Override to indicate a plugin supports quickgrading
     *
     * @return boolean - True if the plugin supports quickgrading
     */
    public function supports_quickgrading() {
        return false;
    }

     /**
     * Get file areas returns a list of areas this plugin stores files
     * @return array - An array of fileareas (keys) and descriptions (values)
     */
    public function get_file_areas() {
        return array(constants::M_FILEAREA=>$this->get_name());
    }

	/**
     * Get form elements for grading form 
	 * [this is deprecated from 2.3.4 ..but prev moodle versions need it]
     *
     * @param stdClass $grade
     * @param MoodleQuickForm $mform
     * @param stdClass $data
     * @return bool true if elements were added to the form
     */
	public function get_form_elements($grade, MoodleQuickForm $mform, stdClass $data) {
        return $this->get_form_elements_for_user($grade, $mform,$data,0);
    }
	
     /**
     * Get form elements for grading form
     *
     * @param stdClass $grade
     * @param MoodleQuickForm $mform
     * @param stdClass $data
     * @param int $userid The userid we are currently grading
     * @return bool true if elements were added to the form
     */
    public function get_form_elements_for_user($grade, MoodleQuickForm $mform, stdClass $data, $userid) {
        global $USER,$PAGE,$CFG;
        $debug="";
        $PAGE->requires->js(new moodle_url($CFG->httpswwwroot . '/mod/assign/feedback/poodll/module.js'));
        $displayname = $this->get_name(); 
        $gradeid = $grade ? $grade->id : 0;
        
        if ($gradeid > 0 && get_config(constants::M_COMPONENT, 'showcurrentfeedback')) {
           $currentfeedback = $this->fetch_responses($gradeid);
            if($currentfeedback != ''){
				$deletefeedback = "<a href='javascript:void(0);' onclick='M.assignfeedback_poodll.deletefeedback();'>".
            						"<img src='" . $CFG->httpswwwroot . '/mod/assign/feedback/poodll/pix/deletebutton.png' . 
									"' alt='" . get_string('deletefeedback',constants::M_COMPONENT) . "'/>" .
            						"</a>";
            	$currentfeedback .= $deletefeedback;
            }
            $currentcontainer = 'currentfeedbackwrapper';
            $currentfeedback = "<div id='" .$currentcontainer. "'>" . $currentfeedback . "</div>";

             $mform->addElement('static', 'currentfeedback', $displayname,$currentfeedback);
             //reset the display name so it doesn't show with the recorder
             $displayname="";
             
             $opts = array(
				"filecontrolid"=> constants::M_FILENAMECONTROL,
				"reallydeletefeedback"=> get_string('reallydeletefeedback',constants::M_COMPONENT),
				"currentcontainer"=> $currentcontainer
			);
			//$PAGE->requires->js(new moodle_url($CFG->httpswwwroot . '/mod/assign/feedback/poodll/module.js'));
			$PAGE->requires->js_init_call('M.assignfeedback_poodll.init',array($opts),false);
        }

        //active
        $active = $this->get_config('active');
        if($active===false){
            $active=1;
        }

		//We prepare our form here and fetch/save data in SAVE method
		$usercontextid=context_user::instance($USER->id)->id;
		$draftitemid = file_get_submitted_draft_itemid(constants::M_FILENAMECONTROL);
		$contextid=$this->assignment->get_context()->id;
		file_prepare_draft_area($draftitemid, $contextid, constants::M_COMPONENT, constants::M_FILEAREA, $gradeid, null,null);
		$mform->addElement('hidden', 'draftitemid', $draftitemid);
		$mform->addElement('hidden', 'usercontextid', $usercontextid);	
		$mform->addElement('hidden', constants::M_FILENAMECONTROL, '',array('id' => constants::M_FILENAMECONTROL));
		$mform->setType('draftitemid', PARAM_INT);
		$mform->setType('usercontextid', PARAM_INT); 
		$mform->setType(constants::M_FILENAMECONTROL, PARAM_TEXT);


        //if this is inactive we do not want to show the recorders, so we just return here
        if(!$active){

            $mform->addElement('static', 'poodllfeedbackinactive', '',get_string('poodllfeedbackinactive',constants::M_COMPONENT));
            //reset the display name so it doesn't show with the recorder
            $displayname="";
            return true;
        }

        //no timelimit on recordings
        $timelimit=0;

        //get saved values and return them as defaults
        $recordertype = $this->get_config('recordertype');

        //set up current module context id so we can fetch local filter based recorder
        $hints = Array();
        $hints['modulecontextid']=$contextid;
        $callbackjs =false;
		
		//fetch the required "recorder
		switch($recordertype){
				
			case constants::M_REPLYWHITEBOARD:
				//get board sizes
				switch($this->get_config('boardsize')){
					case "320x320": $width=320;$height=320;break;
					case "400x600": $width=400;$height=600;break;
					case "500x500": $width=500;$height=500;break;
					case "600x400": $width=600;$height=400;break;
					case "600x800": $width=600;$height=800;break;
					case "800x600": $width=800;$height=600;break;
				}

				
				$imageurl="";
				$mediadata= \filter_poodll\poodlltools::fetchWhiteboardForSubmission(constants::M_FILENAMECONTROL,
						$usercontextid ,'user','draft',$draftitemid, $width, $height, $imageurl);
				$mform->addElement('static', 'description',$displayname,$mediadata);
				break;
			
			case constants::M_REPLYSNAPSHOT:
                $mediadata= \filter_poodll\poodlltools::fetchHTML5SnapshotCamera(constants::M_FILENAMECONTROL,290,340,$usercontextid,'user','draft',$draftitemid,false);
				$mform->addElement('static', 'description',$displayname,$mediadata);
				break;

			case constants::M_REPLYVIDEO:
				$mediadata= \filter_poodll\poodlltools::fetchVideoRecorderForSubmission('swf','poodllfeedback',constants::M_FILENAMECONTROL,
						$usercontextid ,'user','draft',$draftitemid,$timelimit,$callbackjs,$hints);
				$mform->addElement('static', 'description',$displayname,$mediadata);			
									
				break;

            case constants::M_REPLYVOICE:
            case constants::M_REPLYMP3VOICE:
                $mediadata= \filter_poodll\poodlltools::fetchMP3RecorderForSubmission(constants::M_FILENAMECONTROL, $usercontextid ,'user','draft',$draftitemid,$timelimit, $callbackjs, $hints);
                $mform->addElement('static', 'description',$displayname,$mediadata);
                break;
					
		}

        // hidden params: Pretty sure we don't need this. Justin 20170523
        //$mform->addElement('hidden', 'id', 0);
        //$mform->setType('id', PARAM_INT);
	return true;

    }


    public function is_feedback_modified(stdClass $grade, stdClass $data){

        $thefilename = '';
        /*
        if ($grade) {
            $poodllfeedback = $this->get_feedback_poodll($grade->id);
            if (isset($poodllfeedback->filename) && !empty($poodllfeedback->filename)) {
                $thefilename = $poodllfeedback->filename;
            }
        }
        */
        if($data->{constants::M_FILENAMECONTROL}==$thefilename){
            return false;
        }else{
            return true;
        }
    }


    /**
     * Saving the comment content into dtabase
     *
     * @param stdClass $grade
     * @param stdClass $data
     * @return bool
     */
    public function save(stdClass $grade, stdClass $data) {
        global $DB;
   
        //Move recorded files from draft to the correct area
        //if shift_draft_file is false, no change, so do nothing
        //if it is an empty string, user has deleted file, so we clear it too
		$filename = $this->shift_draft_file($grade, $data);
		if($filename === false){return true;}
        
        $feedbackpoodll = $this->get_feedback_poodll($grade->id);
        if ($feedbackpoodll) {
        	$feedbackpoodll->filename = $filename;
            return $DB->update_record(constants::M_TABLE, $feedbackpoodll);
        } else {
            $feedbackpoodll = new stdClass();
            $feedbackpoodll->grade = $grade->id;
            $feedbackpoodll->filename = $filename;
            $feedbackpoodll->assignment = $this->assignment->get_instance()->id;
            return $DB->insert_record(constants::M_TABLE, $feedbackpoodll) > 0;
        }
    }

    /**
     * display the player in the feedback table
     *
     * @param stdClass $grade
     * @param bool $showviewlink Set to true to show a link to view the full feedback
     * @return string
     */
    public function view_summary(stdClass $grade, & $showviewlink) {
        $showviewlink = false;

        //our response, this will output a player/image
        return $this->fetch_responses($grade->id) ;
    }
    
/*
* Fetch the player to show the submitted recording(s)
*
*
*
*/
function fetch_responses($gradeid){
        global $CFG;
        $responsestring = "";
		
        //get filename, from the filearea for this submission. 
        //there should be only one.
        $fs = get_file_storage();
        $filename="";
        $files = $fs->get_area_files($this->assignment->get_context()->id, 
				constants::M_COMPONENT, constants::M_FILEAREA, $gradeid, "id", false);
        if (!empty($files)) {
			//if the filename property exists, and is filled, use that to fetch the file
			$poodllfeedback= $this->get_feedback_poodll($gradeid);
			if(isset($poodllfeedback->filename) && !empty($poodllfeedback->filename)){
				$filename =  $poodllfeedback->filename;
				
			//if no filename property just take the first file. That is how we used to do it	
			}else{
				foreach ($files as $file) {
					$filename = $file->get_filename();
					break;
				}
			}
	}
	
        //if this is a playback area, for teacher, show a string if no file
        if (empty($filename)){ 
                 $responsestring .= "";
        }else{	
                //The path to any media file we should play
                $rawmediapath = $CFG->wwwroot.'/pluginfile.php/'.$this->assignment->get_context()->id 
						. '/assignfeedback_poodll/' . constants::M_FILEAREA  . '/'.$gradeid.'/'.$filename;

                //prepare our response string, which will parsed and replaced with the necessary player
                switch($this->get_config('recordertype')){


                        case constants::M_REPLYVOICE:
                        case constants::M_REPLYMP3VOICE:

                            $responsestring  = $this->fetch_feedback_player($rawmediapath);
							if($this->get_config('downloadsok')){
								$responsestring .= "<a href='" . $rawmediapath . "' class='nomediaplugin'>"
										. get_string('downloadfile', 'assignfeedback_poodll') 
										."</a>";
							}
							
							break;						

                        case constants::M_REPLYVIDEO:

                            $responsestring  = $this->fetch_feedback_player($rawmediapath);
                            break;

                        case constants::M_REPLYWHITEBOARD:
                                $responsestring .= "<img alt=\"submittedimage\" class=\"assignfeedback_poodll_whiteboardwidth\" src=\"" . $rawmediapath . "\" />";
                                break;

                        case constants::M_REPLYSNAPSHOT:
                                $responsestring .= "<img alt=\"submittedimage\" class=\"assignfeedback_poodll_snapshotwidth\" src=\"" . $rawmediapath . "\" />";
                                break;

                        default:
                                $responsestring .= format_text("<a href=\"$rawmediapath\">$filename</a>", FORMAT_HTML);
					break;
                                break;	

                }//end of switch
        }//end of if (checkfordata ...) 

        return $responsestring;
		
}//end of fetch_responses


    public function fetch_feedback_player($rawmediapath) {
        global $OUTPUT;
        // player template.
        $randomid = html_writer::random_id('poodllfeedback_');

        $playeropts=array(
                'playerid'=> $randomid ,
                'size'=>['width'=>480,'height'=>320],
                'mediaurl'=>$rawmediapath . '?cachekiller=' . $randomid
        );

        // is this a list page?
        $islist = optional_param('action', '', PARAM_TEXT) == 'grading';
        if(!empty($islist)){
            $playeropts['islist']=1;
        }

            // prepare our response string, which will parsed and replaced with the necessary player.
            switch ($this->get_config('recordertype')) {

                case constants::M_REPLYVOICE:
                case constants::M_REPLYMP3VOICE:

                    $playerstring = $OUTPUT->render_from_template(constants::M_COMPONENT . '/audioplayer', $playeropts);
                    break;

                case constants::M_REPLYVIDEO:

                    $playerstring = $OUTPUT->render_from_template(constants::M_COMPONENT . '/videoplayer', $playeropts);
                    break;


                default:
                    $playerstring = format_text("<a href='". $playeropts['mediaurl'] . "'>the_submission</a>", FORMAT_HTML);

            }// end of switch.

        return $playerstring;

    }


    /**
     * display the comment in the feedback table
     *
     * @param stdClass $grade
     * @return string
     */
    public function view(stdClass $grade) {
        return $this->fetch_responses($grade->id) ;
    }

    /**
     * Return true if this plugin can upgrade an old Moodle 2.2 assignment of this type
     * and version.
     *
     * @param string $type old assignment subtype
     * @param int $version old assignment version
     * @return bool True if upgrade is possible
     */
    public function can_upgrade($type, $version) {

        if (($type == 'upload' || $type == 'uploadsingle' ||
             $type == 'online' || $type == 'offline') && $version >= 2011112900) {
            return true;
        }
        return false;
    }

    /**
     * Upgrade the settings from the old assignment to the new plugin based one
     *
     * @param context $oldcontext - the context for the old assignment
     * @param stdClass $oldassignment - the data for the old assignment
     * @param string $log - can be appended to by the upgrade
     * @return bool was it a success? (false will trigger a rollback)
     */
    public function upgrade_settings(context $oldcontext, stdClass $oldassignment, & $log) {
        // first upgrade settings (nothing to do)
        return true;
    }

    /**
     * Upgrade the feedback from the old assignment to the new one
     *
     * @param context $oldcontext - the database for the old assignment context
     * @param stdClass $oldassignment The data record for the old assignment
     * @param stdClass $oldsubmission The data record for the old submission
     * @param stdClass $grade The data record for the new grade
     * @param string $log Record upgrade messages in the log
     * @return bool true or false - false will trigger a rollback
     */
    public function upgrade(context $oldcontext, stdClass $oldassignment, stdClass $oldsubmission, stdClass $grade, & $log) {
        global $DB;

        $feedbackpoodll = new stdClass();
        $feedbackpoodll->commenttext = $oldsubmission->submissioncomment;
        $feedbackpoodll->commentformat = FORMAT_HTML;

        $feedbackpoodll->grade = $grade->id;
        $feedbackpoodll->assignment = $this->assignment->get_instance()->id;
        if (!$DB->insert_record(constants::M_TABLE, $feedbackpoodll) > 0) {
            $log .= get_string('couldnotconvertgrade', 'mod_assign', $grade->userid);
            return false;
        }

        return true;
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
     * Returns true if there are no feedback poodll for the given grade
     *
     * @param stdClass $grade
     * @return bool
     */
    public function is_empty(stdClass $grade) {
        return $this->view($grade) == '';
    }

}
