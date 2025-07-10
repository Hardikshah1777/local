<?php

/**
 * Install script for local_squibit
 *
 * File         install.php
 * Encoding     UTF-8
 *
 * @package     local_squibit
 *
 */

/**
 * Install
 */
function xmldb_local_squibit_install() {
    global $CFG, $DB;

    require_once($CFG->dirroot.'/user/profile/definelib.php');
    require_once($CFG->dirroot.'/user/profile/field/menu/define.class.php');

    $formfield = new profile_define_menu();

    $data = new \stdClass();
    $data->datatype = "menu";
    $data->shortname = "squibit_role";
    $data->name = "Squibit Role";
    $data->descriptionformat = 1;
    $data->description = '';
    $data->required = 0;
    $data->locked = 0;
    $data->forceunique = 0;
    $data->signup = 0;
    $data->visible = 2;
    $data->categoryid = 1;
    $data->param1 = 'None';
    $data->defaultdata = '';

    $formfield->define_save($data);

    $category = $DB->get_record_sql('SELECT * FROM {customfield_category} WHERE id > 0 LIMIT 1');
    if (!empty($category)) {
        $catid = $category->id;
    } else {
        $name = 'SQUIBIT';
        $data = new \stdClass();
        $data->name = $name;
        $data->component = 'core_course';
        $data->area = 'course';
        $data->contextid = context_system::instance()->id;
        $data->itemid = 0;
        $data->sortorder = 0;
        $data->timecreated = $data->timemodified = time();

        $cat = \core_customfield\category_controller::create(0, $data);
        $cat->save();
        $catid = $DB->get_field('customfield_category', 'id', ['name' => $name]);
    }

    $record = new stdClass();
    $record->name = get_string(\local_squibit\utility::COURSEENABLE,'local_squibit');
    $record->shortname = \local_squibit\utility::COURSEENABLE;
    $record->type = 'checkbox';
    $record->description = '';
    $record->descriptionformat = 1;
    $record->timecreated = time();
    $record->timemodified = time();
    $record->categoryid = $catid;
    $customdata = [
        'required' => 0,
        'uniquevalues' => 0,
        'checkbydefault' => 0,
        'locked' => 0,
        'visibility' => 2,
    ];
    $record->configdata = $customdata;

    $field = \core_customfield\field_controller::create(0, $record);
    \core_customfield\api::save_field_configuration($field, $record);
}
