<?php

require_once($CFG->libdir.'/formslib.php');

class theme_boost_formrenderer extends MoodleQuickForm_Renderer {
    /**
     * Template used when opening a hidden fieldset
     * (i.e. a fieldset that is opened when there is no header element)
     * @var string
     */
    var $_openHiddenFieldsetTemplate = "\n\t<fieldset class=\"hidden\"><div>";

    /** @var string Header Template string */
    var $_headerTemplate = "<li class=\"nav-item\" role=\"listitem\"><a class=\"nav-link {activeclass}\" id=\"{id}\" data-toggle=\"pill\" href=\"#{id}-tab\" role=\"tab\" aria-controls=\"{id}\" >{header}</a></li>";

    /** @var string Template used when opening a fieldset */
    var $_openFieldsetTemplate = "<div class=\"tab-pane fade {classes}\" id=\"{id}-tab\">";

    var $_sidebar = '';

    /**
     * @var MoodleQuickForm
     */
    var $_form = '';

    var $_submitbutton = '';

    var $_js = '';

    public function startForm(&$form) {
        $this->_form = $form;
        $this->_sidebar = '';
        parent::startForm($form);
    }

    /**
     * Create advance group of elements
     *
     * @param MoodleQuickForm_group $group Passed by reference
     * @param bool $required if input is required field
     * @param string $error error message to display
     */
    function startGroup(&$group, $required, $error){
        global $OUTPUT;

        // Make sure the element has an id.
        $group->_generateId();

        // Prepend 'fgroup_' to the ID we generated.
        $groupid = 'fgroup_' . $group->getAttribute('id');

        // Update the ID.
        $group->updateAttributes(array('id' => $groupid));
        $advanced = isset($this->_advancedElements[$group->getName()]);

        $html = $OUTPUT->mform_element($group, $required, $advanced, $error, false);
        $fromtemplate = !empty($html);
        if (!$fromtemplate) {
            if (method_exists($group, 'getElementTemplateType')) {
                $html = $this->_elementTemplates[$group->getElementTemplateType()];
            } else {
                $html = $this->_elementTemplates['default'];
            }

            if (isset($this->_advancedElements[$group->getName()])) {
                $html = str_replace(' {advanced}', ' advanced', $html);
                $html = str_replace('{advancedimg}', $this->_advancedHTML, $html);
            } else {
                $html = str_replace(' {advanced}', '', $html);
                $html = str_replace('{advancedimg}', '', $html);
            }
            if (method_exists($group, 'getHelpButton')) {
                $html = str_replace('{help}', $group->getHelpButton(), $html);
            } else {
                $html = str_replace('{help}', '', $html);
            }
            $html = str_replace('{id}', $group->getAttribute('id'), $html);
            $html = str_replace('{name}', $group->getName(), $html);
            $html = str_replace('{groupname}', 'data-groupname="'.$group->getName().'"', $html);
            $html = str_replace('{typeclass}', 'fgroup', $html);
            $html = str_replace('{type}', 'group', $html);
            $html = str_replace('{class}', $group->getAttribute('class'), $html);
            $emptylabel = '';
            if ($group->getLabel() == '') {
                $emptylabel = 'femptylabel';
            }
            $html = str_replace('{emptylabel}', $emptylabel, $html);
        }
        $this->_templates[$group->getName()] = $html;
        // Fix for bug in tableless quickforms that didn't allow you to stop a
        // fieldset before a group of elements.
        // if the element name indicates the end of a fieldset, close the fieldset
        if (in_array($group->getName(), $this->_stopFieldsetElements) && $this->_fieldsetsOpen > 0) {
            $this->_html .= $this->_closeFieldsetTemplate;
            $this->_fieldsetsOpen--;
        }
        if (!$fromtemplate) {
            parent::startGroup($group, $required, $error);
        } else {
            if ($group->getName() == 'buttonar') {
                $save = get_string('save');
                $buttonhtml = '<div class="text-right pt-1">';
                foreach ($group->_elements as $el) {
                    if ($el->_attributes['name'] != 'cancel') {
                        $buttonhtml .= <<<HTML
                            <button id="{$el->getAttribute('id')}" type="{$el->getAttribute('type')}" class="{$el->getAttribute('class')} resetbtnforicon" name="{$el->getAttribute('type')}"
                            data-toggle="tooltip" data-placement="bottom" title="{$save}" value="{$el->getAttribute('value')}"><i class="lnil lnil-save lnil-lg"></i></button>
                        HTML;
                    } else {
                        $buttonhtml .= <<<HTML
                            <button id="{$el->getAttribute('id')}" type="{$el->getAttribute('type')}" class="{$el->getAttribute('class')} resetbtnforicon ml-1" name="{$el->getAttribute('name')}"
                            data-toggle="tooltip" data-placement="bottom" title="{$el->getAttribute('value')}" value="{$el->getAttribute('value')}"
                            data-cancel="1" data-skip-validation="1" onclick="skipClientValidation = true; return true;"><i class="lnil lnil-cross-circle lnil-lg"></i></button>
                        HTML;
                    }
                }
                $buttonhtml .= '</div>';
                $this->_submitbutton = $buttonhtml;
            } else {
                $this->_html .= $html;
            }
        }
    }

    /**
     * Renders element
     *
     * @param HTML_QuickForm_element $element element
     * @param bool $required if input is required field
     * @param string $error error message to display
     */
    function renderElement(&$element, $required, $error){
        global $OUTPUT;

        // Make sure the element has an id.
        $element->_generateId();
        $advanced = isset($this->_advancedElements[$element->getName()]);


        if (!$this->_fieldsetsOpen) {
            $id = uniqid('general-');
            $isactive = is_null($this->_form->_currentTab) || strpos($this->_form->_currentTab, 'general-') === 0;
            $header_html = str_replace('{header}', get_string('general'), $this->_headerTemplate);
            $header_html = str_replace('{id}', $id, $header_html);
            $header_html = str_replace('{activeclass}', $isactive ? 'active' : '', $header_html);
            $this->_sidebar .= $header_html;
            $fieldsetclasses = [];
            if ($isactive) {
                $fieldsetclasses[] = 'active show';
            }

            $openFieldsetTemplate = str_replace('{id}', $id, $this->_openFieldsetTemplate);
            $openFieldsetTemplate = str_replace('{classes}', join(' ', $fieldsetclasses), $openFieldsetTemplate);

            $this->_html .= $openFieldsetTemplate;
            $this->_fieldsetsOpen++;
        }
        $headercount = 0;
        foreach ($this->_form->_elements as $elementform) {
            if ($elementform->getType() === 'header') {
                $headercount++;
            }
        }
        if (empty($headercount)) {
            $this->_sidebar = '';
        }

        $html = $OUTPUT->mform_element($element, $required, $advanced, $error, false);
        $fromtemplate = !empty($html);
        if (!$fromtemplate) {
            // Adding stuff to place holders in template
            // check if this is a group element first.
            if (($this->_inGroup) and !empty($this->_groupElementTemplate)) {
                // So it gets substitutions for *each* element.
                $html = $this->_groupElementTemplate;
            } else if (method_exists($element, 'getElementTemplateType')) {
                $html = $this->_elementTemplates[$element->getElementTemplateType()];
            } else {
                $html = $this->_elementTemplates['default'];
            }
            if (isset($this->_advancedElements[$element->getName()])) {
                $html = str_replace(' {advanced}', ' advanced', $html);
                $html = str_replace(' {aria-live}', ' aria-live="polite"', $html);
            } else {
                $html = str_replace(' {advanced}', '', $html);
                $html = str_replace(' {aria-live}', '', $html);
            }
            if (isset($this->_advancedElements[$element->getName()]) || $element->getName() == 'mform_showadvanced') {
                $html = str_replace('{advancedimg}', $this->_advancedHTML, $html);
            } else {
                $html = str_replace('{advancedimg}', '', $html);
            }
            $html = str_replace('{id}', 'fitem_' . $element->getAttribute('id'), $html);
            $html = str_replace('{typeclass}', 'f' . $element->getType(), $html);
            $html = str_replace('{type}', $element->getType(), $html);
            $html = str_replace('{name}', $element->getName(), $html);
            $html = str_replace('{groupname}', '', $html);
            $html = str_replace('{class}', $element->getAttribute('class'), $html);
            $emptylabel = '';
            if ($element->getLabel() == '') {
                $emptylabel = 'femptylabel';
            }
            $html = str_replace('{emptylabel}', $emptylabel, $html);
            if (method_exists($element, 'getHelpButton')) {
                $html = str_replace('{help}', $element->getHelpButton(), $html);
            } else {
                $html = str_replace('{help}', '', $html);
            }
        } else {
            if ($this->_inGroup) {
                $this->_groupElementTemplate = $html;
            }
        }
        if (($this->_inGroup) and !empty($this->_groupElementTemplate)) {
            $this->_groupElementTemplate = $html;
        } else if (!isset($this->_templates[$element->getName()])) {
            $this->_templates[$element->getName()] = $html;
        }

        if (!$fromtemplate) {
            parent::renderElement($element, $required, $error);
        } else {
            if (in_array($element->getName(), $this->_stopFieldsetElements) && $this->_fieldsetsOpen > 0) {
                $this->_html .= $this->_closeFieldsetTemplate;
                $this->_fieldsetsOpen--;
            }
            $this->_html .= $html;
        }

        $this->_js .= <<<JS
require(['jquery', 'core/event'], ($, Event) => {
    $('#{$element->getAttribute("id")}').on(Event.Events.FORM_FIELD_VALIDATION, (event, msg) => {
        if (msg !== '') {
            const tabPane = event.target.closest('.tab-pane');
            if (!tabPane || !tabPane.getAttribute('id')) {
                return;
            }
            const formwrap = tabPane.closest('.mformwrapper');
            const tabToggle = formwrap?.querySelector('[data-toggle="pill"][href="#' + tabPane.getAttribute('id') + '"]');
            if (!formwrap || !tabToggle) {
                return;
            }
            if (!formwrap.querySelector('.has-danger')) {
                $(tabToggle).tab('show');
            } else if (formwrap.querySelector('.has-danger') === event.target.closest('.has-danger')) {
                $(tabToggle).tab('show');
            }
        }
    });
});
JS;
    }

    /**
     * Called when visiting a form, after processing all form elements
     * Adds required note, form attributes, validation javascript and form content.
     *
     * @global moodle_page $PAGE
     * @param moodleform $form Passed by reference
     */
    function finishForm(&$form){
        global $PAGE;
        if ($form->isFrozen()){
            $this->_hiddenHtml = '';
        }
        $html = '';
        if (!empty($this->_submitbutton)) {
            $html .= $this->_submitbutton;
        }
        $html .= '<div class="mformwrapper">';
        if (!empty($this->_sidebar)) {
            $html .= '<ul class="nav nav-pils" role="tablist">' . $this->_sidebar . '</ul>';
        }
        $html .= '<div class="tab-content flex-grow-1">' . $this->_html . '</div>';
        $html .= '</div>';
        $this->_html = $html;
        if ($this->_js) {
            $PAGE->requires->js_amd_inline($this->_js);
        }

        parent::finishForm($form);
        $this->_html = str_replace('{collapsebtns}', $this->_collapseButtons, $this->_html);
        if (!$form->isFrozen()) {
            $args = $form->getLockOptionObject();
            if (count($args[1]) > 0) {
                $PAGE->requires->js_init_call('M.form.initFormDependencies', $args, true, moodleform::get_js_module());
            }
        }

        $PAGE->requires->js_amd_inline(<<<JS
        require(['jquery', 'theme_boost/loader'], ($) => {
            const formid = '{$this->_form->getAttribute("id")}';
            const eve = new CustomEvent('toggleformwrapper', {detail: {formid}})
            document.dispatchEvent(eve);
            const validationError = document.querySelector('.has-danger');
            if (validationError) {
                const tabPane = validationError.closest('.tab-pane');
                if (!tabPane || !tabPane.getAttribute('id')) {
                    return;
                }
                const formwrap = tabPane.closest('.mformwrapper');
                const tabToggle = formwrap?.querySelector('[data-toggle="pill"][href="#' + tabPane.getAttribute('id') + '"]');

                if (formwrap && !tabToggle) {
                    tabPane.classList.add('d-block');
                    tabPane.classList.add('show');
                }
                if (!formwrap || !tabToggle) {
                    return;
                }
                $(tabToggle).tab('show');
            }
            const tabLinks = [...document.querySelectorAll('#' + formid + ' .nav-link')];
            if (!tabLinks.some(tablink => tablink.classList.contains('active'))) {
                $(tabLinks[0]).tab('show');
            }
        })
JS);
    }
   /**
    * Called when visiting a header element
    *
    * @param HTML_QuickForm_header $header An HTML_QuickForm_header element being visited
    * @global moodle_page $PAGE
    */
    function renderHeader(&$header) {
        global $PAGE;

        $header->_generateId();
        $name = $header->getName();
        $isactive = strpos($this->_form->_currentTab, 'id_' . $header->getName()) === 0;
        if (is_null($header->_text)) {
            $header_html = '';
        } elseif (!empty($name) && isset($this->_templates[$name])) {
            $header_html = str_replace('{header}', $header->toHtml(), $this->_templates[$name]);
        } else {
            $header_html = str_replace('{header}', strip_tags($header->_text), $this->_headerTemplate);
            $header_html = str_replace('{id}', $header->getAttribute('id'), $header_html);
            $header_html = str_replace('{activeclass}', $isactive ? 'active' : '', $header_html);
            $this->_sidebar .= $header_html;
        }

        if ($this->_fieldsetsOpen > 0) {
            $this->_html .= $this->_closeFieldsetTemplate;
            $this->_fieldsetsOpen--;
        }

        // Define collapsible classes for fieldsets.
        $fieldsetclasses = [];
        if ($isactive) {
            $fieldsetclasses[] = 'active show';
        }

        if (isset($this->_advancedElements[$name])){
            $fieldsetclasses[] = 'containsadvancedelements';
        }

        $openFieldsetTemplate = str_replace('{id}', $header->getAttribute('id'), $this->_openFieldsetTemplate);
        $openFieldsetTemplate = str_replace('{classes}', join(' ', $fieldsetclasses), $openFieldsetTemplate);

        $this->_html .= $openFieldsetTemplate;
        $this->_fieldsetsOpen++;
    }
}