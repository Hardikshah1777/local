<?php


defined('MOODLE_INTERNAL') || die();


function progressreport_add_instance($data) {
    global $DB;

    $data->timemodified = time();
    $progressreportid = $DB->insert_record('progressreport',$data);

    if($progressreportid){
        foreach ($data->infogroup as $key => $value){
            if(!empty($value['infopoint'])){
                if($value['deletedinfo'] == 0) {
                    $datafield = new stdClass();
                    $datafield->progressreportid = $progressreportid;
                    $datafield->field = $value['infopoint'];
                    $datafield->fieldvalue = $value['userfiled'];
                    $datafield->timemodified = time();
                    $insert = $DB->insert_record('progressreport_field', $datafield);
                }
            }
        }
        $i = 1;
        foreach ($data->marketgroup as $key => $market){
            if(!empty($market['marketpoint'])){
                $datamarket = new stdClass();
                $datamarket->progressreportid = $progressreportid;
                $datamarket->name = $market['marketpoint'];
                $datamarket->mnumber = $i;
                $datamarket->timemodified = time();
                $insert = $DB->insert_record('progressreport_market',$datamarket);
                $i++;
            }
        }


    }
    return $progressreportid;
}

function progressreport_update_instance($data){
    global $DB;

    $updatedata = new stdClass();
    $updatedata->id = $data->instance;
    $updatedata->name = $data->name;
    $updatedata->intro = $data->intro;
    $updatedata->nolesson = $data->nolesson;
    $updatedata->introformat = $data->introformat;
    $update = $DB->update_record('progressreport',$updatedata);
    $sql = "SELECT MAX(mnumber) as numbers FROM {progressreport_market} WHERE progressreportid = :progresssreportid";
    $existmarkingrec = $DB->get_record_sql($sql,array('progresssreportid' => $data->instance));
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
                        $update = $DB->update_record('progressreport_field', $datafield);
                    } else {
                        $datanewfield = new stdClass();
                        $datanewfield->progressreportid = $data->instance;
                        $datanewfield->field = $fieldvalue['infopoint'];
                        $datanewfield->fieldvalue = $fieldvalue['userfiled'];
                        $datanewfield->timemodified = time();
                        $update = $DB->insert_record('progressreport_field', $datanewfield);
                    }
                }else{
                    if ($fieldvalue['deletedinfo'] == 1) {
                        $update = $DB->delete_records('progressreport_field',['id'=>$fieldvalue['infoid']]);
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
                    $update = $DB->update_record('progressreport_market', $datamarket);
                }else {
                    $i++;
                    $datamarketnew = new stdClass();
                    $datamarketnew->progressreportid = $data->instance;
                    $datamarketnew->name = $market['marketpoint'];
                    $datamarketnew->mnumber = $i;
                    $datamarketnew->timemodified = time();
                    $update = $DB->insert_record('progressreport_market', $datamarketnew);
                }
            }
        }
    }

    return $update;
}

function progressreport_delete_instance($id){
    global $DB;
    $sqlparams['id'] = $id;
    $DB->delete_records_select('progressreport_user_skill','progressreportuserid IN (SELECT id FROM {progressreport_user} WHERE progressreportid = :id)',$sqlparams);
    $DB->delete_records_select('progressreport_field_info','progressreportuserid IN (SELECT id FROM {progressreport_user} WHERE progressreportid = :id)',$sqlparams);
    $DB->delete_records_select('progressreport_user','progressreportid = :id',$sqlparams);
    $DB->delete_records_select('progressreport_skill','sectionid IN (SELECT id FROM {progressreport_section} WHERE progressreportid = :id)',$sqlparams);
    $DB->delete_records_select('progressreport_section','progressreportid = :id',$sqlparams);
    $DB->delete_records_select('progressreport_market','progressreportid = :id',$sqlparams);
    $DB->delete_records_select('progressreport_field','progressreportid = :id',$sqlparams);
    $DB->delete_records('progressreport',['id' => $id]);
    return true;
}

function progressreport_extend_settings_navigation(settings_navigation $settings, navigation_node $progressreportnode) {
    global $DB, $PAGE,$USER;

    $keys = $progressreportnode->get_children_key_list();
    $beforekey = null;
    $i = array_search('modedit', $keys);
    if ($i === false and array_key_exists(0, $keys)) {
        $beforekey = $keys[0];
    } else if (array_key_exists($i + 1, $keys)) {
        $beforekey = $keys[$i + 1];
    }
    if(is_siteadmin($USER->id)) {
        $node = navigation_node::create(get_string('manage', 'mod_progressreport'),
                new moodle_url('/mod/progressreport/manage.php', array('id' => $PAGE->cm->id)),
                navigation_node::TYPE_SETTING, null, 'mod_evaluation_manage',
                new pix_icon('t/edit', ''));
        $progressreportnode->add_node($node, $beforekey);
    }

    return $progressreportnode->trim_if_empty();
}

function progressreport_supports($feature){
    switch($feature) {
        case FEATURE_BACKUP_MOODLE2: return true;
        default: return null;
    }
}


