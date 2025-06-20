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
 * Menu profile field.
 *
 * @package    profilefield_menu
 * @copyright  2007 onwards Shane Elliot {@link http://pukunui.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Class profile_field_menu
 *
 * @copyright  2007 onwards Shane Elliot {@link http://pukunui.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class profile_field_licensenumber extends profile_field_base {

    protected $box1size;
    protected $box2size;
    const box1key = 'box1';
    const box2key = 'box2';
    const nothingkey = 'nothing';
    const seperator = '-';
    const nothingvalue = '-1';
    const AB = 'Alberta';
    const dependon = 'profile_field_LicenceProvince_';
    const plain = 'plain';
	const box1pattern = "/^[0-9]{6}$/";
    const box2pattern = "/^[0-9]{3}$/";
    const plainpattern = "/^[0-9]*$/";

    public function __construct($fieldid = 0, $userid = 0, $fielddata = null) {
        // First call parent constructor.
        parent::__construct($fieldid, $userid, $fielddata);

        $this->box1size = isset($this->field->param1) ? (string) $this->field->param1 : 3;
        $this->box2size = isset($this->field->param2) ? (int) $this->field->param2 : 3;
        //This is  ugly hack to show data in adaptable theme
        $this->field->datatype = 'text';
    }

    /**
     * @param MoodleQuickForm $mform
     */
    public function edit_field_add($mform) {
        global $PAGE;
		$allowedcharacter = explode(',',$this->box1size);
        $box1maxlength = max($allowedcharacter);
        $box1 = $mform->createElement('text',self::box1key,null, "size='{$box1maxlength}' maxlength ='{$box1maxlength}'");
        $box2 = $mform->createElement('text',self::box2key,null, "size='{$this->box2size}' maxlength ='{$this->box2size}'");
        $input = $mform->createElement('text', self::plain, null);
        $nothing = $mform->createElement('checkbox',self::nothingkey, get_string('nothingcheck', 'profilefield_licensenumber'));

        $mform->addElement('group', $this->inputname, format_string($this->field->name), [$box1,$box2,$input,$nothing]);
        $mform->disabledIf($this->inputname.'['.self::box1key.']', $this->inputname.'['.self::nothingkey.']', 'checked');
        $mform->disabledIf($this->inputname.'['.self::box2key.']', $this->inputname.'['.self::nothingkey.']', 'checked');
        $mform->disabledIf($this->inputname.'['.self::plain.']', $this->inputname.'['.self::nothingkey.']', 'checked');

        $mform->hideIf($this->inputname.'['.self::box1key.']', self::dependon, 'neq', self::AB);
        $mform->hideIf($this->inputname.'['.self::box2key.']', self::dependon, 'neq', self::AB);
        $mform->hideIf($this->inputname.'['.self::plain.']', self::dependon, 'eq', self::AB);

        $mform->setType($this->inputname.'['.self::box1key.']', PARAM_TEXT);
        $mform->setType($this->inputname.'['.self::box2key.']', PARAM_TEXT);
        $mform->setType($this->inputname.'['.self::plain.']', PARAM_TEXT);

        $PAGE->requires->js_call_amd('profilefield_licensenumber/profilefield', 'initialize');
    }

    public function edit_save_data($usernew) {
        if (isset($usernew->{$this->inputname}) && is_array($usernew->{$this->inputname})) {
            if ($usernew->{self::dependon} == self::AB) {
                unset($usernew->{$this->inputname}[self::plain]);
            } else {
                unset($usernew->{$this->inputname}[self::box1key],$usernew->{$this->inputname}[self::box2key]);
            }
        }
        return parent::edit_save_data($usernew);
    }

    public function edit_save_data_preprocess($data, $datarecord) {
        if (isset($data[self::nothingkey])) {
            return self::nothingvalue;
        }
        if (is_array($data)) {
            $data = array_filter($data, 'trim');
        }
        return is_array($data)?join(self::seperator,$data):$data;
    }

    public function edit_validate_field($usernew) {
        $errors = parent::edit_validate_field($usernew);
		if (isset($usernew->{$this->inputname})) {
			if (!isset($usernew->{$this->inputname}[self::nothingkey])) {
				if ($usernew->{self::dependon} != self::AB) {
					if (empty($usernew->{$this->inputname}[self::plain])) {
                        if ($this->is_required()) {
                            $errors[$this->inputname] = get_string('required');
                        }
					}
				} else {
					$allowedcharacter = explode(',',$this->box1size);
				    $lenthinput = strlen($usernew->{$this->inputname}[self::box1key]);
					if (empty($usernew->{$this->inputname}[self::box1key])) {
                        if ($this->is_required()) {
                            $errors[$this->inputname] = get_string('required');
                        }
					} else if (!in_array($lenthinput,$allowedcharacter)) {
						$errors[$this->inputname] = get_string('maxlength', 'profilefield_licensenumber', $this->box1size);
					}
					if (empty($usernew->{$this->inputname}[self::box2key])) {
                        if ($this->is_required()) {
                            $errors[$this->inputname] = get_string('required');
                        }
					} else if (strlen($usernew->{$this->inputname}[self::box2key]) !== $this->box2size) {
						$errors[$this->inputname] = get_string('maxlength', 'profilefield_licensenumber', $this->box2size);
					}
				}
			}
		}
        return $errors;
    }

    public function edit_load_user_data($user) {
        if ($this->field->data) {
            if ($this->field->data == self::nothingvalue) {
                $user->{$this->inputname.'['.self::nothingkey.']'} = 1;
            } else {
                if ($user->{self::dependon} == self::AB) {
                    $dataparts = explode(self::seperator, $this->field->data, 2);
                    $user->{$this->inputname . '[' . self::box1key . ']'} = $dataparts[0];
                    $user->{$this->inputname . '[' . self::box2key . ']'} = $dataparts[1];
                }
                $user->{$this->inputname.'['.self::plain.']'} = $this->field->data;
            }
        }
    }

    public function edit_field_set_default($mform) {
        if(!isset($this->field->defaultdata)){
            return;
        }

        if($this->field->defaultdata == self::nothingvalue) {
            $mform->setDefault($this->inputname.'['.self::nothingkey.']', 1);
            return;
        }

        //$arrayparts = explode(self::seperator,$this->field->defaultdata,2);

        //$mform->setDefault($this->inputname.'['.self::box1key.']', isset($arrayparts[0]) ? $arrayparts[0] :null);
        //$mform->setDefault($this->inputname.'['.self::box2key.']', isset($arrayparts[1]) ? $arrayparts[1] :null);
    }

    public function edit_field_set_locked($mform) {
        if (!$mform->elementExists($this->inputname)) {
            return;
        }
        if ($this->is_locked() and !has_capability('moodle/user:update', context_system::instance())) {
            $mform->hardFreeze($this->inputname);
        }
    }

    public function get_field_properties() {
        return array(PARAM_TEXT, NULL_NOT_ALLOWED);
    }

    public function display_data() {
        if ($this->data == self::nothingvalue) {
            return get_string('nothingcheck', 'profilefield_licensenumber');
        }
        return parent::display_data();
    }
}