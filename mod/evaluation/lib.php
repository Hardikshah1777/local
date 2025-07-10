<?php


defined('MOODLE_INTERNAL') || die();


function evaluation_add_instance($data) {
    global $DB;

    $data->timemodified = time();
    $add = $DB->insert_record('evaluation',$data);
    if($add){
        foreach ($data->levelgroup as $key => $value){
            if(!empty($value['levelpoint'])){
                $datalevel = ['evaluationid' => $add, 'name' => $value['levelpoint'],'status'=>$value['visiblestatus'],'grade'=>$value['grade'], 'timemodified' => $data->timemodified];
                $insert = $DB->insert_record('evaluation_level',$datalevel);
            }
        }

        foreach ($data->infogroup as $key => $value){
            if(!empty($value['infopoint'])){
                if($value['deletedinfo'] == 0){
                    $datainfo = ['evaluationid' => $add, 'infofiled' => $value['infopoint'],'infovalue'=>$value['userfiled'],'timemodified' => $data->timemodified];
                    $insert = $DB->insert_record('evaluation_userinfo',$datainfo);
                }
            }
        }
    }
    return $add;
}

function evaluation_update_instance($data){
    global $DB;

    $updatedata = new stdClass();
    $updatedata->id = $data->instance;
    $updatedata->name = $data->name;
    $updatedata->intro = $data->intro;
    $update = $DB->update_record('evaluation',$updatedata);

    if($update) {
        foreach ($data->levelgroup as $key => $value) {
            if(!empty($value['levelpoint'])) {
                if (isset($value['levelid']) && !empty($value['levelid'])) {
                    $datalevel = new stdClass();
                    $datalevel->id = $value['levelid'];
                    $datalevel->evaluationid = $value['evaluationid'];
                    $datalevel->name = $value['levelpoint'];
                    $datalevel->grade = $value['grade'];
                    $datalevel->status = $value['visiblestatus'];
                    $datalevel->timemodified = time();
                    $update = $DB->update_record('evaluation_level', $datalevel);
                } else {
                    $data->timemodified = time();
                    $datalevel =
                            ['evaluationid' => $data->instance, 'name' => $value['levelpoint'], 'status' => $value['visiblestatus'],
                                    'grade' => $value['grade'], 'timemodified' => $data->timemodified];
                    $update = $DB->insert_record('evaluation_level', $datalevel);
                }
            }
        }

        foreach ($data->infogroup as $fieldkey => $fieldvalue) {
            if(!empty($fieldvalue['infopoint'])) {
                if ($fieldvalue['deletedinfo'] == 0) {
                    if (isset($fieldvalue['infoid']) && !empty($fieldvalue['infoid'])) {
                        $datafield = new stdClass();
                        $datafield->id = $fieldvalue['infoid'];
                        $datafield->infofiled = $fieldvalue['infopoint'];
                        $datafield->infovalue = $fieldvalue['userfiled'];
                        $datafield->timemodified = time();
                        $update = $DB->update_record('evaluation_userinfo', $datafield);
                    } else {
                        $datauserfield =
                                ['evaluationid' => $data->instance, 'infofiled' => $fieldvalue['infopoint'], 'infovalue' => $fieldvalue['userfiled'], 'timemodified' => time()];
                        $update = $DB->insert_record('evaluation_userinfo', $datauserfield);
                    }
                }else{
                    if ($fieldvalue['deletedinfo'] == 1) {
                        $update = $DB->delete_records('evaluation_userinfo',['id'=>$fieldvalue['infoid']]);
                    }
                }
            }
        }
    }

    return $update;
}

function evaluation_delete_instance($id){
    global $DB;
    $sqlparams['id'] = $id;
    $DB->delete_records_select('evaluation_user_skill_level','evaluationuserid IN (SELECT id FROM {evaluation_user} WHERE evaluationid = :id)',$sqlparams);
    $DB->delete_records_select('evaluation_userfields_info','evaluationuserid IN (SELECT id FROM {evaluation_user} WHERE evaluationid = :id)',$sqlparams);
    $DB->delete_records_select('evaluation_user','evaluationid = :id',$sqlparams);
    $DB->delete_records_select('evaluation_skill','sectionid IN (SELECT id FROM {evaluation_section} WHERE evaluationid = :id)',$sqlparams);
    $DB->delete_records_select('evaluation_section','evaluationid = :id',$sqlparams);
    $DB->delete_records_select('evaluation_level','evaluationid = :id',$sqlparams);
    $DB->delete_records_select('evaluation_userinfo','evaluationid = :id',$sqlparams);
    $DB->delete_records('evaluation',['id' => $id]);
    return true;
}

function evaluation_extend_settings_navigation(settings_navigation $settings, navigation_node $evaluationnode) {
    global $DB, $PAGE,$USER;

    $keys = $evaluationnode->get_children_key_list();
    $beforekey = null;
    $i = array_search('modedit', $keys);
    if ($i === false and array_key_exists(0, $keys)) {
        $beforekey = $keys[0];
    } else if (array_key_exists($i + 1, $keys)) {
        $beforekey = $keys[$i + 1];
    }
    if(is_siteadmin($USER->id)) {


        $node = navigation_node::create(get_string('manage', 'mod_evaluation'),
                new moodle_url('/mod/evaluation/manage.php', array('id' => $PAGE->cm->id)),
                navigation_node::TYPE_SETTING, null, 'mod_evaluation_manage',
                new pix_icon('t/edit', ''));
        $evaluationnode->add_node($node, $beforekey);
    }

    return $evaluationnode->trim_if_empty();
}

function evaluation_supports($feature){
    switch($feature) {
        case FEATURE_BACKUP_MOODLE2: return true;
        default: return null;
    }
}


