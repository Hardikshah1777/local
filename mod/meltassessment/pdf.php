<?php


require ('../../config.php');
require ($CFG->libdir .'/tcpdf/tcpdf.php');
require_once ($CFG->dirroot.'/mod/meltassessment/locallib.php');

$id = optional_param('id','0',PARAM_INT);
$meltassessmentuser = $DB->get_record('meltassessment_user',['id' => $id]);

$userid = $meltassessmentuser->userid;
$meltassessmentid = $meltassessmentuser->meltassessmentid;
$user = core_user::get_user($userid);
$meltassessment = $DB->get_record('meltassessment', array('id'=>$meltassessmentid), '*', MUST_EXIST);
$meltassessmentname = $meltassessment->name;

for ($i = 1; $i <= $meltassessmentuser->nolesson; $i++){
    $avragefinal = $DB->get_field('meltassessment_user_lesson','average',
            ['meltassessmentuserid' => $id, 'lesson' => $i]);
    if($avragefinal != '0.0'){
        $lastavg = $avragefinal;
    }
}
global $meltassessmentname,$lastavg;
class MYPDF extends TCPDF {

    public function Header() {
        global $CFG,$meltassessmentname,$lastavg;
        $image_file = $CFG->dirroot.'/mod/meltassessment/pix/logo.jpg';
        //$this->Image($image_file, 160, 2, 35, '', 'JPG', '', 'T', false, 300, '', false, false, 0, false, false, false);
        $this->Image($image_file, 0, 3, 35, '', 'JPG', '', '', false, 0, 'L', false, false, 0, false, false, false);
        $this->SetFont('helvetica', 'B', 15);
        $this->Cell(0, 10, $meltassessmentname, '', true, 'R', '', '', 0, false, 'T', 'M');
        $this->Cell(0, 0, 'Final Mark : '.$lastavg, 0, true, 'R', '', '', 0, false, 'T', 'M');
    }
}

$pdf = new MYPDF();
$pdf->SetTitle($meltassessment->name);
$pdf->SetHeaderData(PDF_HEADER_LOGO, 60, $meltassessment->name, '');
//$pdf->SetHeaderData('', 60, $meltassessment->name, '');

$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

$pdf->AddPage();

$html = '
    <table border="1">
        ';
        $meltassessmentuserne = $DB->get_records('meltassessment_field',['meltassessmentid' => $meltassessmentid]);
        foreach ($meltassessmentuserne as $users){
            if(is_number($users->fieldvalue)){
                $namedata = profile_data($users->fieldvalue,$userid);
            }else if(!is_number($users->fieldvalue)){
                $namedata = userfield_data($users->fieldvalue,$userid,$id,$meltassessmentuser->attempt,$users->id);
            }

           $html .='
            <tr border="1">
                <td colspan="4" border="1"  style="text-align: left; height: 18px; font-size: 12px; font-weight: 500; text-indent: 4px;"> <b>'.$users->field.':</b> </td>
                <td colspan="8" border="1"  style="text-align: left; height: 18px; font-size: 12px; font-weight: 500; text-indent: 6px;"> '.$namedata.'</td>
            </tr> 
        ';
        }
        $html .='
    </table>
';
$pdf->writeHTML($html, true, false, true, false, '');

$html = '
        <h3><b> Marking Criteria</b></h3>
        <table border="1">
        ';
        $meltassessmentuserne = $DB->get_records('meltassessment_market',['meltassessmentid' => $meltassessmentid]);
        $i = 1;
        foreach ($meltassessmentuserne as $users){
           $html .='
            <tr border="1">
                <td colspan="12" border="1"  style="text-align: left; height: 18px; font-size: 12px; font-weight: 500; text-indent: 4px;"> <b>'.$i.'.  '.$users->name.'</b> </td>
            </tr> 
        ';
           $i++;
        }
        $html .='
    </table>
';
$pdf->writeHTML($html, true, false, true, false, '');

$html = '
        <h3><b> Lessons Date</b></h3>
        <table border="1">
        ';
        $meltassessmentuserne = $DB->get_records('meltassessment_user_lesson',['meltassessmentuserid' => $id]);
        $i = 1;
        $j = 1;
        $html .='
            <tr border="1" >
        ';
            foreach ($meltassessmentuserne as $users) {
                    $html .='
                        <td colspan="12"  border="1"  style="text-align: center; height: 12px; font-size: 9px; "> <b> '.$j.'</b> </td>
                    ';
                $j++;
            }
            $html .='
            </tr> ';

        $html .='
            <tr border="1" >
        ';
        foreach ($meltassessmentuserne as $users){
            $dated = gmdate('d-m-y',$users->time);
           $html .='
            
            <td colspan="12"  border="1"  style="text-align: left; height: 18px; font-size: 9px; "> <b> '.$dated.'</b> </td>
            
        ';
           $i++;
        }
        $html .='
            </tr> 
    </table>
';
$pdf->writeHTML($html, true, false, true, false, '');

$sections = get_meltassessment_section($meltassessment->id);
$meltassessmentuser = $DB->get_record('meltassessment_user',['id' => $id]);

$html = '';
$html .= '
        <table border="1">
        ';
foreach ($sections as $section) {

    $deletecheck = delete_check_sections($section->id,$id);
    if(!empty($deletecheck)) {
//
            $html .= '
        <tr style="text-indent: 10px;">
            <th colspan="5" style="text-align: left; height: 18px; font-size: 14px; font-weight: bold; border: 1px solid red; color: red;">' .
                    $section->name . ' </th>
            <td colspan="7" style="text-align: left; height: 18px; font-size: 13px; font-weight: bold; border: 1px solid red; text-indent: -8px;">
                <table>                
                       <tr >';
                            $nolesson = $meltassessmentuser->nolesson;
                                for ($i = 1; $i <= $nolesson; $i++){
                                    $html .='
                                    <td style="border-right: 1px solid red; color: red" colspan="1">
                                        '.$i.'
                                    </td>
                                   ';
                                }
                            $html .='
                        </tr>
                </table>
             </td>
        </tr>';
            $skillrecords = get_meltassessment_sections_skill($section->id);
            foreach ($skillrecords as $skillrecord) {
                $deleteuser = $DB->get_field('meltassessment_user_skill', 'id',
                        ['skillid' => $skillrecord->id, 'meltassessmentuserid' => $id]);
                if (!empty($deleteuser)) {
                    $skillnameheight = $pdf->getStringHeight(10, $skillrecord->name, '', true, '', '');

                    if ($skillnameheight > 58) {
                        $height = "32px";
                    } else {
                        $height = "17px";
                    }
                    $html .= '
                        <tr>
                            <td colspan="5" style="text-align: left; height: 14px; text-indent: 10px;">' . $skillrecord->name . '</td>
                            <td colspan="7" style="text-align: center; height: 14px; text-indent: -8px;">
                                <table>                
                                   <tr >';
                                        $nolesson = $meltassessmentuser->nolesson;
                                            for ($i = 1; $i <= $nolesson; $i++){
                                                $html .='
                                                <td style="border-right: 1px solid black; text-align: left; text-indent: 2px; height: '.$height.'" colspan="1">
                                                        ';
                                                    $markingdata = $DB->get_field('meltassessment_user_skill','marketid',
                                                            ['meltassessmentuserid' => $id, 'skillid' => $skillrecord->id, 'lessonnumber' => $i]);
                                                    if(!empty($markingdata))
                                                    {
                                                        $marknum = $DB->get_field('meltassessment_market','mnumber',['id' => $markingdata]);
                                                    }else{
                                                        $marknum = '0';
                                                    }

                                                $html .='
                                                        '.$marknum.'
                                                </td>
                                               ';
                                            }
                                        $html .='
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        ';
                }//delete skill check
            }//skill end
    }//DELETE SECTION CHECK
}//Section End
$html .='
            <tr>
                            <td colspan="5" style="text-align: left; color: red; font-weight: bold; font-size: 14px; height: 14px; text-indent: 10px;"> Average</td>
                            <td colspan="7"  style="text-align: left; height: 14px; color: red; text-indent: -8px;"> 
                                <table>                
                                       <tr >';
                                            $nolesson = $meltassessmentuser->nolesson;
                                                for ($i = 1; $i <= $nolesson; $i++){
                                                    $html .='
                                                    <td style="border-right: 1px solid black; font-weight: bold" colspan="1">
                                                        ';
                                                            $avrage = $DB->get_field('meltassessment_user_lesson','average',
                                                                    ['meltassessmentuserid' => $id, 'lesson' => $i]);
                                                        $html .='
                                                                '.$avrage.'
                                                    </td>
                                                   ';
                                                }
                                            $html .='
                                        </tr>
                                </table>
                            </td>
                        </tr>
        ';
$html .='
    </table>
';
$pdf->writeHTML($html, true, false, true, false, '');

$htmlnew = '
   
    <table>
        <tr>
            <td colspan="12" height="25px"  style="font-weight: bold;font-size: 16px;">Notes</td>
        </tr>
        <tr>
            <td colspan="12" style="font-size: 12px; height: 50px; text-align: justify;"><p> '.$meltassessmentuser->notes.'</p></td>
        </tr>
        
    </table>
    ';

$pdf->writeHTML($htmlnew, true, false, true, false, '');

$pdf->lastPage();

$pdf->Output($meltassessment->name.'.pdf', 'D');

