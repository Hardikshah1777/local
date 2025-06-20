<?php


defined('MOODLE_INTERNAL') || die();


function meltassessment_add_instance($data) {
    global $DB;

    $data->timemodified = time();
    $meltassessmentid = $DB->insert_record('meltassessment',$data);

    if($meltassessmentid){
        foreach ($data->infogroup as $key => $value){
            if(!empty($value['infopoint'])){
                if($value['deletedinfo'] == 0) {
                    $datafield = new stdClass();
                    $datafield->meltassessmentid = $meltassessmentid;
                    $datafield->field = $value['infopoint'];
                    $datafield->fieldvalue = $value['userfiled'];
                    $datafield->timemodified = time();
                    $insert = $DB->insert_record('meltassessment_field', $datafield);
                }
            }
        }
        $i = 1;
        foreach ($data->marketgroup as $key => $market){
            if(!empty($market['marketpoint'])){
                $datamarket = new stdClass();
                $datamarket->meltassessmentid = $meltassessmentid;
                $datamarket->name = $market['marketpoint'];
                $datamarket->mnumber = $i;
                $datamarket->timemodified = time();
                $insert = $DB->insert_record('meltassessment_market',$datamarket);
                $i++;
            }
        }


    }
    return $meltassessmentid;
}

function meltassessment_update_instance($data){
    global $DB;

    $updatedata = new stdClass();
    $updatedata->id = $data->instance;
    $updatedata->name = $data->name;
    $updatedata->intro = $data->intro;
    $updatedata->nolesson = $data->nolesson;
    $updatedata->introformat = $data->introformat;
    $update = $DB->update_record('meltassessment',$updatedata);
    $sql = "SELECT MAX(mnumber) as numbers FROM {meltassessment_market} WHERE meltassessmentid = :meltassessmentid";
    $existmarkingrec = $DB->get_record_sql($sql,array('meltassessmentid' => $data->instance));
    $dataexistmarking = $existmarkingrec;

    if($update) {

        foreach ($data->infogroup as $fieldkey => $fieldvalue) {
            if(!empty($fieldvalue['infopoint'])) {
                if ($fieldvalue['deletedinfo'] == 0) {
                    if (isset($fieldvalue['infoid']) && !empty($fieldvalue['infoid'])) {
                        $datafield = new stdClass();
                        $datafield->id = $fieldvalue['infoid'];
                        $datafield->field = $fieldvalue['infopoint'];
                        $datafield->fieldvalue = $fieldvalue['userfiled'];
                        $datafield->timemodified = time();
                        $update = $DB->update_record('meltassessment_field', $datafield);
                    } else {
                        $datanewfield = new stdClass();
                        $datanewfield->meltassessmentid = $data->instance;
                        $datanewfield->field = $fieldvalue['infopoint'];
                        $datanewfield->fieldvalue = $fieldvalue['userfiled'];
                        $datanewfield->timemodified = time();
                        $update = $DB->insert_record('meltassessment_field', $datanewfield);
                    }
                }else{
                    if ($fieldvalue['deletedinfo'] == 1) {
                        $update = $DB->delete_records('meltassessment_field',['id'=>$fieldvalue['infoid']]);
                    }
                }
            }
        }
        $i = $dataexistmarking->numbers;
        foreach ($data->marketgroup as $key => $market){

            if(!empty($market['marketpoint'])) {
                if (isset($market['marketid']) && !empty($market['marketid'])) {
                    $datamarket = new stdClass();
                    $datamarket->id = $market['marketid'];
                    $datamarket->name = $market['marketpoint'];
                    $datamarket->timemodified = time();
                    $update = $DB->update_record('meltassessment_market', $datamarket);
                }else {
                    $i++;
                    $datamarketnew = new stdClass();
                    $datamarketnew->meltassessmentid = $data->instance;
                    $datamarketnew->name = $market['marketpoint'];
                    $datamarketnew->mnumber = $i;
                    $datamarketnew->timemodified = time();
                    $update = $DB->insert_record('meltassessment_market', $datamarketnew);
                }
            }
        }
    }

    return $update;
}

function meltassessment_delete_instance($id){
    global $DB;
    $sqlparams['id'] = $id;
    $DB->delete_records_select('meltassessment_user_skill','meltassessmentuserid IN (SELECT id FROM {meltassessment_user} WHERE meltassessmentid = :id)',$sqlparams);
    $DB->delete_records_select('meltassessment_field_info','meltassessmentuserid IN (SELECT id FROM {meltassessment_user} WHERE meltassessmentid = :id)',$sqlparams);
    $DB->delete_records_select('meltassessment_user','meltassessmentid = :id',$sqlparams);
    $DB->delete_records_select('meltassessment_skill','sectionid IN (SELECT id FROM {meltassessment_section} WHERE meltassessmentid = :id)',$sqlparams);
    $DB->delete_records_select('meltassessment_section','meltassessmentid = :id',$sqlparams);
    $DB->delete_records_select('meltassessment_market','meltassessmentid = :id',$sqlparams);
    $DB->delete_records_select('meltassessment_field','meltassessmentid = :id',$sqlparams);
    $DB->delete_records('meltassessment',['id' => $id]);
    return true;
}

function meltassessment_extend_settings_navigation(settings_navigation $settings, navigation_node $meltassessmentnode) {
    global $DB, $PAGE,$USER;

    $keys = $meltassessmentnode->get_children_key_list();
    $beforekey = null;
    $i = array_search('modedit', $keys);
    if ($i === false and array_key_exists(0, $keys)) {
        $beforekey = $keys[0];
    } else if (array_key_exists($i + 1, $keys)) {
        $beforekey = $keys[$i + 1];
    }
    if(is_siteadmin($USER->id)) {
        $node = navigation_node::create(get_string('manage', 'mod_meltassessment'),
                new moodle_url('/mod/meltassessment/manage.php', array('id' => $PAGE->cm->id)),
                navigation_node::TYPE_SETTING, null, 'mod_evaluation_manage',
                new pix_icon('t/edit', ''));
        $meltassessmentnode->add_node($node, $beforekey);
    }

    return $meltassessmentnode->trim_if_empty();
}

function meltassessment_supports($feature){
    switch($feature) {
        case FEATURE_BACKUP_MOODLE2: return true;
        default: return null;
    }
}


