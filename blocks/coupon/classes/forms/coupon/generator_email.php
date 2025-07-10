<?php

namespace block_coupon\forms\coupon;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class generator_email extends \moodleform {

    public function definition() {
        $mform = &$this->_form;
        $bid = $this->_customdata['id'];
        $pid = $this->_customdata['pid'];
        $quantity = $this->_customdata['quantity'];
        $used = $this->_customdata['used'];
        $remaining = $quantity - $used;

        $mform->addElement('hidden', 'id', $bid);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'pid', $pid);
        $mform->setType('pid', PARAM_INT);

        $mform->addElement('html','<div class="form-group row  fitem"><div class="col-md-3"></div>
            <div class="col-md-9 form-inline felement" data-fieldtype="html">
              <b>'.get_string('form:generator_email:remainingnote','block_coupon',$remaining).'</b>  
            </div>
        </div>','');

        if($remaining):
        $mform->addElement('textarea', 'coupon_recipients_manual',
                get_string("label:coupon_recipients", 'block_coupon'), 'rows="10" cols="100"');
        $mform->addRule('coupon_recipients_manual', get_string('required'), 'required', null, 'client');

        $mform->addElement('static', 'coupon_recipients_desc', '', get_string('form:generator_email:coupon_recipients_desc', 'block_coupon'));

        $this->add_action_buttons(true,get_string('button:generator_email', 'block_coupon'));
        else:
        $mform->addElement('cancel');
        endif;

    }

    public function validation($data, $files) {
        $validationresult = $this->validate_coupon_recipients_manual($data['coupon_recipients_manual']);
        if ($validationresult !== true) {
            $errors['coupon_recipients_manual'] = $validationresult;
        }

        return $errors;
    }

    public function validate_coupon_recipients_manual($csvdata) {

        $error = false;
        $quantity = $this->_customdata['quantity'];
        $used = $this->_customdata['used'];
        $availablecoupons = $quantity - $used;

        $recipients = $this->get_recipients_from_csv($csvdata);
        if (empty($recipients)) {
            $error = get_string('error:generator_email:recipients-empty', 'block_coupon');
        } else if (count($recipients) > $availablecoupons) {
            $error = get_string('error:generator_email:recipients-max-exceeded', 'block_coupon',$availablecoupons);
        } else {
            foreach ($recipients as $recipient) {
                if (!filter_var($recipient->email, FILTER_VALIDATE_EMAIL)) {
                    $error = get_string('error:generator_email:recipients-email-invalid', 'block_coupon', $recipient);
                }
            }
        }

        return ($error === false) ? true : $error;
    }

    public function get_recipients_from_csv($recipientsstr) {

        $recipients = array();

        if (!$csvdata = str_replace("\n","",trim($recipientsstr,','))) {
            return false;
        }

        $row = str_getcsv($csvdata, ",");
        foreach ($row as $email) {
            $recipient = new \stdClass();
            $email = trim(strtolower($email));
            $recipient->email = $email;
            $recipients[] = $recipient;
        }

        return $recipients;
    }


}
