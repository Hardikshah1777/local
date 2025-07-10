<?php

namespace block_coupon\forms\coupon;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class generator_paypal extends \moodleform {

    public function definition() {
        global $PAGE;
        $mform = & $this->_form;
        $course_options = $this->_customdata['course_options'];
        $hidden_params = array('cmd','business','item_name','item_number','quantity','baseval',
            'userid',#userid
            'groupid',#groupid'
            'blockid',#groupid'
            'currency_code','amount','for_auction','no_note','no_shipping','notify_url','return','custom',
            'cancel_return', 'rm', 'first_name', 'last_name', 'address', 'city', 'country',
        );
        foreach ($hidden_params as $name){
            $mform->addElement('hidden',$name);
            $mform->setType($name,PARAM_RAW_TRIMMED);
        }

        $select = &$mform->addElement('select','courseid',get_string('form:generator_paypal:courselabel','block_coupon'),$course_options);
        if(!empty($course_options)) $select->setSelected(reset(array_keys($course_options)));
        $mform->setType('courseid',PARAM_INT);

        $quantity_options = array_combine(range(1,25),range(1,25));
        $select = &$mform->addElement('select','qauntity',get_string('form:generator_paypal:quantitylabel','block_coupon'),$quantity_options);
        if(!empty($course_options)) $select->setSelected(1);
        $mform->setType('qauntity',PARAM_INT);

        $mform->addElement('html','<div class="form-group row  fitem">
            <div class="col-md-3"></div>
            <div class="col-md-9 form-inline felement" data-fieldtype="html">
              <b id="pricetag"><span id="currencycode">$ </span><span id="price"></span></b>  
            </div>
        </div>','');
        $mform->addElement('submit', 'submitbutton', get_string('button:paypal', 'block_coupon'));

        $mform->disabledIf('submitbutton','courseid','noitemselected');
        $mform->disabledIf('submitbutton','qauntity','noitemselected');

        $js = <<<JS
require(['jquery'],function($){
    var pricetag = $('#pricetag #price'),
                courseselect = $('#id_courseid'),
                qauntityselect = $('#id_qauntity'),
                amount = $('[name=amount]'),
                userid = $('[name=userid]'),
                blockid = $('[name=blockid]'),
                groupid = $('[name=groupid]'),
                custom = $('[name=custom]'),
                itemname = $('[name=item_name]'),
        itemnumber = $('[name=item_number]'),
        courseprices = {$this->_customdata['courseprices']} ,
                pricechanger = function(){
                    var multiplier = qauntityselect.val(),
                selectedcourse = courseprices[courseselect.val()],
                price = selectedcourse.p * multiplier;
            itemnumber.val(courseselect.val());    
            groupid.val(selectedcourse.g);
                    amount.val(price);
                    pricetag.text( price);
                    itemname.val(courseselect.find(':selected').text());
            custom.val([blockid.val(),userid.val(),groupid.val(),courseselect.val(),qauntityselect.val()].join('-'));
                };
    qauntityselect.on('change',pricechanger);
    courseselect.on('change',pricechanger);
            pricechanger();
})
JS;

        $PAGE->requires->js_amd_inline($js);
    }
}