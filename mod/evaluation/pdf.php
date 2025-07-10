<?php


require ('../../config.php');
require ($CFG->libdir .'/tcpdf/tcpdf.php');
require_once ($CFG->dirroot.'/mod/evaluation/locallib.php');

$id = optional_param('id','0',PARAM_INT);
$evaluationuser = $DB->get_record('evaluation_user',['id' => $id]);

$userid = $evaluationuser->userid;
$evaluationid = $evaluationuser->evaluationid;
$user = core_user::get_user($userid);
$evaluation = $DB->get_record('evaluation', array('id'=>$evaluationid), '*', MUST_EXIST);
global $evaluationname;
$evaluationname = $evaluation->name;
class MYPDF extends TCPDF {

    public function Header() {
        global $CFG,$evaluationname;
        $image_file = $CFG->dirroot.'/mod/evaluation/pix/logo.jpg';
        //$this->Image($image_file, 160, 2, 35, '', 'JPG', '', 'T', false, 300, '', false, false, 0, false, false, false);
        $this->Image($image_file, 15, 2, 35, '', 'JPG', '', 'T', false, 300, '', false, false, 0, false, false, false);

        $this->Cell(0, 15, $evaluationname, '', false, 'R', '', '', 0, false, 'T', 'M');

    }
}

$pdf = new MYPDF();
$pdf->SetTitle($evaluation->name);
$pdf->SetHeaderData(PDF_HEADER_LOGO, 60, $evaluation->name, '');
//$pdf->SetHeaderData('', 60, $evaluation->name, '');

$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

$pdf->AddPage();

$html = '
    <table border="1">
        ';
        $evaluationuserne = $DB->get_records('evaluation_userinfo',['evaluationid' => $evaluationid]);
        foreach ($evaluationuserne as $users){
            if(is_number($users->infovalue)){
                $namedata = profile_data($users->infovalue,$userid);
            }else if(!is_number($users->infovalue)){
                $namedata = userfield_data($users->infovalue,$userid,$id,$evaluationuser->attempt,$users->id);
            }

           $html .='
            <tr border="1">
                <td colspan="4" border="1"  style="text-align: left; height: 18px; font-size: 12px; font-weight: 500; text-indent: 4px;"> <b>'.$users->infofiled.':</b> </td>
                <td colspan="8" border="1"  style="text-align: left; height: 18px; font-size: 12px; font-weight: 500; text-indent: 6px;"> '.$namedata.'</td>
            </tr> 
        ';
        }
        $html .='
    </table>
';
$pdf->writeHTML($html, true, false, true, false, '');

$sections = get_evaluation_sections($evaluation->id);
$evaluationuser = $DB->get_record('evaluation_user',['id' => $id]);

$html = '';
$html .= '
    <table border="1">
    ';
foreach ($sections as $section) {
    $deletecheck = delete_check_section($section->id,$id);
    if(!empty($deletecheck)) {
        if ($section->saferskill == 0) {
            $html .= '
        <tr style="text-indent: 10px;">
            <th colspan="12" style="text-align: left; height: 18px; font-size: 14px; font-weight: bold; border-top: 3px solid black; border-bottom: 3px solid black;">' .
                    $section->name . ' </th>
        </tr>';
            $skillrecords = get_evaluation_sections_skill($section->id);
            foreach ($skillrecords as $skillrecord) {
                $deleteuser = $DB->get_field('evaluation_user_skill_level', 'id',
                        ['skillid' => $skillrecord->id, 'evaluationuserid' => $id]);

                $comment = $DB->get_field('evaluation_user_skill_level','comment',['skillid' => $skillrecord->id,'evaluationuserid' => $id]);

                if (!empty($deleteuser)) {
                    $skillnameheight = $pdf->getStringHeight(10, $skillrecord->name, '', true, '', '');
                    if ($skillnameheight > 60) {
                        $height = "30px";
                    } else {
                        $height = "14px";
                    }
                    $html .= '
        <tr>
            <td colspan="5" style="text-align: left; height: 14px; text-indent: 10px;">' . $skillrecord->name . '</td>
            <td colspan="7" style="text-align: center; height: 14px; text-indent: -6px;">
                ';
                    $levelsnew = get_user_skill_level($evaluationuser->id, $skillrecord->id);
                    $levelrecord = get_evaluation_level($evaluationid);
                    $html .= '<table border="1"><tr>';
                    foreach ($levelrecord as $levelrec) {
                        if (!empty($levelsnew->levelid) && $levelsnew->levelid == $levelrec->id) {
                            $svg = new moodle_url('/mod/evaluation/check.svg');
                            $checked = '<b> <img src="' . $svg . '" height="9px"> ' . $levelrec->name . '</b>';
                        } else {
                            $checked = $levelrec->name;
                        }
                        $html .= '<td style="height: ' . $height . '">' . $checked . '</td>';
                    }
                    $html .= '</tr></table>';
                    $html .= '
            </td>
        </tr>
        ';
         if(!empty($comment)){ $html .='
             <tr>
            <td colspan="5" style="text-align: left; font-weight: bold; height: 14px; text-indent: 10px;">comment</td>
            <td colspan="7" style="height: 14px; text-indent: 1px;">
                     '.$comment.'
            </td>
        </tr> ';
         }

                }//delete skill check
            }//skill end
        }//saferskill end
    }//DELETE SECTION CHECK
}//Section End
$html .='
    </table>
';
$pdf->writeHTML($html, true, false, true, false, '');


$html = '
    <table  border="1">
        <tr>
            <td colspan="12" style="text-align: center; font-size: 15px; font-weight: bold;">SAFER Skill</td>
        </tr>
    ';
foreach ($sections as $section) {
    $deletecheck = delete_check_section($section->id,$id);
    if(!empty($deletecheck)) {
        if ($section->saferskill == 1) {
            $html .= '
        <tr style="text-indent: 10px;">
            <th colspan="12" style="text-align: left; height: 18px; font-size: 14px; font-weight: bold;border-top: 3px solid black; border-bottom: 3px solid black;">' .
                    $section->name . ' </th>   
        </tr>';
            $skillrecords = get_evaluation_sections_skill($section->id);
            foreach ($skillrecords as $skillrecord) {
                $deleteuser = $DB->get_field('evaluation_user_skill_level', 'id',
                        ['skillid' => $skillrecord->id, 'evaluationuserid' => $id]);
                $comment = $DB->get_field('evaluation_user_skill_level','comment',['skillid' => $skillrecord->id,'evaluationuserid' => $id]);
                if (!empty($deleteuser)) {
                    $skillnameheight = $pdf->getStringHeight(10, $skillrecord->name, '', true, '', '');
                    if ($skillnameheight > 60) {
                        $height = "30px";
                    } else {
                        $height = "14px";
                    }
                    $html .= '
        <tr style="text-indent: 10px;">
            <td colspan="5" style="text-align: left; height: 14px; text-indent: 10px;">' . $skillrecord->name . '</td>
            <td colspan="7" style="text-align: center; height: 14px; text-indent: -6px;">
                ';
                    $levelsnew = get_user_skill_level($evaluationuser->id, $skillrecord->id);
                    $levelrecord = get_evaluation_level($evaluationid);
                    $html .= '<table border="1"><tr>';
                    foreach ($levelrecord as $levelrec) {
                        if (!empty($levelsnew->levelid) && $levelsnew->levelid == $levelrec->id) {
                            $svg = new moodle_url('/mod/evaluation/check.svg');
                            $checked = '<b> <img src="' . $svg . '" height="9px"> ' . $levelrec->name . '</b>';
                        } else {
                            $checked = $levelrec->name;
                        }
                        $html .= '<td style="height: ' . $height . ';">' . $checked . ' </td>';
                    }
                    $html .= '</tr></table>';
                    $html .= '
            </td>
        </tr>
        ';
         if(!empty($comment)){ $html .='
             <tr>
            <td colspan="5" style="text-align: left; font-weight: bold; height: 14px; text-indent: 10px;">comment</td>
            <td colspan="7" style="height: 14px; text-indent: 1px;">
                     '.$comment.'
            </td>
        </tr> ';
         }
                }//delete user check
            }//skill end
        }//saferskill end
    } //DELETE SECTION CHECK
}//Section End
$html .='
    </table>
';

$pdf->writeHTML($html, true, false, true, false, '');

$htmlnew = '
    <table>
        <tr>
            <td colspan="12" height="25px"  style="font-weight: bold;font-size: 16px;">Comments</td>
        </tr>
        <tr>
            <td colspan="12" style="font-size: 12px; height: 50px; text-align: justify;"><p> '.$evaluationuser->comments.'</p></td>
        </tr>
        
        ';
                if($evaluationuser->additionaltraining != 1){
            $htmlnew .='
        <tr>
            <td colspan="12" height="30" style="font-size: 14px;"><b>Result: '.$evaluationuser->pass.'</b>
            </td>
        </tr>
        
        '; }
       else{

            $htmlnew .='
                <tr>
                    <td colspan="12" height="30"  style="font-size: 14px;"><b>Result:';
                       if($evaluationuser->pass == 'Fail'){
                           $htmlnew .='Additional Training Required';
                           if($evaluationuser->grade != 0 && $evaluationuser->grade < 80){

                               $htmlnew .='(Less Than 80%) ';
                           }else{
                               $htmlnew .='(URG)';
                           }
                       }$htmlnew .='</b> 
                    </td>
                </tr>
        '; } $htmlnew .='
        <tr>
            <td colspan="12" height="30" style="font-size: 14px;"><b>Grade: '.$evaluationuser->grade.'</b> 
            </td>
        </tr>
        <tr>
            <td colspan="12" height="20" style="">
                ';
                    if($evaluationuser->agree != 1) {
                        $htmlnew .='
                        <input type="checkbox" id="agree" name="agree"  readonly="true" value="student">Student, please check to indicate that you are aware of and have seen the evaluation
                        ';
                    }else{
                        $svg = new moodle_url('/mod/evaluation/check.svg');
                        $htmlnew .= '<b> <img src="' . $svg . '" height="9px"></b> Student, please check to indicate that you are aware of and have seen the evaluation';
                    }
                    $htmlnew .='
            </td>
        </tr>
        
    </table>
    ';

$pdf->writeHTML($htmlnew, true, false, true, false, '');

$pdf->AddPage();

$pdfhtml = '<table>
    <tr>
        <td colspan="12" style="text-align: left; height: 25px; font-size: 13px;"><b>Note to Coaches:</b></td> 
    </tr>
    <tr>
        <td colspan="12" style="text-align: left; height: 18px; font-size: 12px;" >While you may not be a trained examiner, you have the background and experience to make a good
judgement of what is good driving and what is not.</td>
    </tr><br>
    <tr>
        <td colspan="12" style="text-align: left; height: 18px; font-size: 12px;" >In scoring, everyone starts with a 90% and then you add or subtract percentage points based on the
following four basic categories:</td>
    </tr><br>
     <tr>
        <td colspan="12" style="text-align: left; height: 25px; font-size: 13px;"><b>Scoring:</b></td> 
    </tr>
    <tr >
        <td colspan="1" ><b>EE:</b></td>
        <td colspan="4" >Exceeded Expectations: </td>
        <td colspan="7" style="height: 40px;">Participant has exceeded normal expectations of proficiency in this area. Add +1%.</td>
    </tr>
    <tr>
        <td colspan="1"><b>ME:</b></td>
        <td colspan="4">Met Expectations: </td>
        <td colspan="7" style="height: 40px;">Participant has met normal expectations of proficiency in this area. 0%</td>
    </tr>
    <tr>
        <td colspan="1"><b>NI:</b></td>
        <td colspan="4">Needs Improvement: </td>
        <td colspan="7" style="height: 40px;">Participant needs further practice in this area. Subtract 2% (–2%)</td>
    </tr>
    <tr>
        <td colspan="1"><b>Urg:</b></td>
        <td colspan="4">Urgent: </td>
        <td colspan="7" style="height: 40px;">Highly recommended that the participant receive further training in this area as soon as possible.</td>
    </tr>
    <tr>
        <td colspan="12" style="text-align: left; height: 40px; font-size: 13px;"><b>Pass is 80 % or better to a maximum of 100%.</b></td> 
    </tr>
    <tr>
        <td colspan="12" style="height: 20px;" ><b>Additional Training Required:</b></td>
    </tr>
    <tr>';
        $fill = new moodle_url('/mod/evaluation/fill.png');
        $circle = new moodle_url('/mod/evaluation/circle.png');
        $pdfhtml .='
        <td colspan="11" style="text-indent: 20px; height: 17px;"><img src="'.$fill.'" height="4px"> Less than 80%</td>
    </tr> 
    <tr>
        <td colspan="11" style="height: 17px;"><img src="'.$fill.'" height="4px"> 1 URG, regardless of final percentage score.</td>
    </tr>
    <tr>
        <td colspan="11" style="height: 17px;"><img src="'.$fill.'" height="4px"> Any of the following:</td>
    </tr>
    <tr>
        <td colspan="1"></td>
        <td colspan="10" style="height: 17px;"><img src="'.$circle.'" height="5px"> Not stopping for red light or stop sign</td>
    </tr>
    <tr>
        <td colspan="1"></td>
        <td colspan="12" style="height: 17px;"><img src="'.$circle.'" height="5px"> Speeding by over 9 km/hr</td>
    </tr>
    <tr>
        <td colspan="1"></td>
        <td colspan="12" style="height: 25px;"><img src="'.$circle.'" height="5px"> Any unsafe or dangerous action</td>
    </tr>
    
    <tr>
        <td colspan="12"><b>Instructions:</b></td>    
    </tr>
    <tr>
        <td colspan="12" style="font-size: 12px;">Each participant starts with 90% and gains or loses percentage points based on their scores.</td>    
    </tr>
</table>';
$pdf->writeHTML($pdfhtml, true, false, true, false, '');


$pdf->lastPage();

$pdf->Output($evaluation->name.'.pdf', 'D');

