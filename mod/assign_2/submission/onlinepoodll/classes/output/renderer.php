<?php
/**
 * Created by PhpStorm.
 * User: ishineguy
 * Date: 2018/06/26
 * Time: 13:16
 */

namespace assignsubmission_onlinepoodll\output;

use assignsubmission_onlinepoodll\constants;

defined('MOODLE_INTERNAL') || die();

class renderer extends \plugin_renderer_base {

    public function fetch_delete_submission(){

        $ds= \html_writer::tag('button',
            get_string('deletesubmission',constants::M_COMPONENT),
            array('type'=>'button','id'=>constants::M_COMPONENT .'_deletesubmissionbutton','class'=>constants::M_COMPONENT .'_deletesubmissionbutton btn btn-secondary'));

        return $ds;
    }

    public function prepare_toggle_controls(){
        $toggletext = \html_writer::tag('span',get_string('clicktoshow',constants::M_COMPONENT),array('class'=>'toggletext'));
        $togglebutton = \html_writer::tag('span','',array('class'=>'fa fa-2x fa-toggle-off togglebutton','aria-hidden'=>'true'));
        $toggle =\html_writer::div($togglebutton . $toggletext, constants::M_COMPONENT . '_togglecontainer');
        return $toggle;
    }

    public function prepare_current_submission($responses, $deletesubmission, $showcurrentsubmission){
        switch($showcurrentsubmission){
            case constants::M_CURRENTSUBMISSION_ALWAYS:
                $style = 'display: block;';
                break;
            case constants::M_CURRENTSUBMISSION_SHOWEXPANDED:
            case constants::M_CURRENTSUBMISSION_SHOWMINIMIZED:
                default:
                $style = 'display: none;';
        }
        $cs = \html_writer::div($responses . $deletesubmission, constants::M_COMPONENT . '_currentsubmission',array('style'=>$style));
        return $cs;
    }

    public function prepare_recorder($recorder,$showcurrentsubmission, $toggler){
        if($toggler==='') {
            $style = 'display: block;';
        }else{
            switch($showcurrentsubmission){
                //if we are always shpwing current submission, we are toggling recorder, so we start minimised.
                case constants::M_CURRENTSUBMISSION_ALWAYS:
                    $style = 'display: none;';
                    break;
                case constants::M_CURRENTSUBMISSION_SHOWEXPANDED:
                case constants::M_CURRENTSUBMISSION_SHOWMINIMIZED:
                default:
                    $style = 'display: block;';
        }
        }
        $cs = \html_writer::div($recorder, constants::M_COMPONENT . '_recorder',array('style'=>$style));
        return $cs;
    }
}