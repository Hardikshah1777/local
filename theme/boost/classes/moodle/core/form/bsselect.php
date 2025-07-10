<?php

namespace core\form;

use MoodleQuickForm;
use MoodleQuickForm_select;

global $CFG;
require_once $CFG->libdir . '/form/select.php';

class bsselect extends MoodleQuickForm_select {

    public function __construct($elementName = null, $elementLabel = null, $options = null, $attributes = null) {
        parent::__construct($elementName, $elementLabel, $options, $attributes);
        $this->_type = 'bsselect';
        $this->updateAttributes('data-bsselect="1"');
    }

    /**
     * @return self
     */
    public function with_tickicon() : self {
        $this->updateAttributes('data-show-tick="true" data-tick-icon="fa fa-check"');
        return $this;
    }

    /**
     * @return self
     */
    public function with_actionbox() : self {
        if ($this->getMultiple()) {
            $this->updateAttributes('data-actions-box="true"');
        }
        return $this;
    }

    /**
     * @return self
     */
    public function disable_search() : self {
        $this->updateAttributes('data-live-search=""');
        return $this;
    }

    /**
     * @return self
     */
    public function live_search() : self {
        $this->updateAttributes('data-live-search="1"');
        return $this;
    }

    /**
     * @return self
     */
    public function with_ajax() : self {
        $this->updateAttributes('data-live-search="1" data-ajax-search="1"');
        return $this;
    }

    public function exportValue(&$submitValues, $assoc = false) {
        if (!empty($this->getAttribute('data-ajax-search'))) {
            // When this was an ajax request, we do not know the allowed list of values.
            $value = $this->_findValue($submitValues);
            if (null === $value) {
                $value = $this->getValue();
            }
            if ($value === '_qf__force_multiselect_submission' || $value === null) {
                $value = $this->getMultiple() ? [] : '';
            }
            return $this->_prepareValue($value, $assoc);
        }
        return parent::exportValue($submitValues, $assoc);
    }

    public static function register() {
        global $CFG;
        require_once $CFG->libdir . '/formslib.php';
        MoodleQuickForm::registerElementType('bsselect', __FILE__, self::class);
    }
}