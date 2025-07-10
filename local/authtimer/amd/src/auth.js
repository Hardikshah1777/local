// eslint-disable
define(['jquery',
    './modal',
    'core/modal_events',
    'core/ajax',
    'core/templates',
    'core/notification',
    'core/event',
    'core/str',
    'theme_boost/form-display-errors',
    './loadingicon',
    'core/config',
    './toast',
    'core_form/events'
], function ($,
                              Modal,
                              ModalEvents,
                              Ajax,
                              Templates,
                              Notification,
                              Event,
                              Str,
                              displayErrors,
                              Loader,
                              config,
                              Toast,
             formEvents) {
    var component = 'local_authtimer';

    var CONSTANTS = {
        modaltemplate: component + '/authmodal',
        mailaction: '[data-action="mail"]',
        close: '[data-action="hide"]',
        closebtn: '[data-action="cancel"]',
        codeinput: '#id_code',
        clicktosend: '#id_clicktosend',
        mailajax: component + '_mail',
        authajax: component + '_authenticate',
        content: '.modal-content',
        authbodyclass: 'authmodeon',
    };

    var sectomilisec = function (sec) {
        return sec * 1000;
    };

    function init(timeInterval) {
        $.when(
            Str.get_strings([
                {key: 'cannotsendmail', component: component},
                {key: 'emailcodeinvalid', component: component},
                {key: 'emailsent', component: component},
            ]),
            Templates.render(CONSTANTS.modaltemplate, {})
        ).then(function (strings, htmljs) {
            Templates.runTemplateJS(htmljs[1]);
            var modal = new Modal.ModalSaveCancel(htmljs[0]);
            var modalRoot = modal.getRoot();
            var codeinput = modalRoot.find(CONSTANTS.codeinput);
            var clicktosend = modalRoot.find(CONSTANTS.clicktosend);
            var errEvent = $.Event(Event.Events.FORM_FIELD_VALIDATION);
            var modalContent = modalRoot.find(CONSTANTS.content);

            modal.getModal().attr('data-modaltheme', 'adaptable');

            function modalSave(e) {
                e.preventDefault();

                formEvents.notifyFieldValidationFailure(codeinput[0], '');

                var codeval = codeinput.val().trim();

                if (codeval.length === 0) {
                    formEvents.notifyFieldValidationFailure(codeinput[0], strings[1]);
                    return false;
                }

                var promises = Ajax.call([{
                    methodname: CONSTANTS.authajax,
                    args: {
                        contextid: config.contextid,
                        authcode: codeval,
                    }
                }]);

                Loader.addIconToContainerRemoveOnCompletion(modalContent, promises[0]);

                promises[0].then(function (response) {
                    if (!response.success) {
                        formEvents.notifyFieldValidationFailure(codeinput[0], strings[1]);
                    } else {
                        modal.hide();
                        codeinput.val('');
                        formEvents.notifyFieldValidationFailure(codeinput[0], '');
                        if (response.nexttick) {
                            // Const tick = setInterval(() => window.console.log(1), sectomilisec(1));
                            setInterval(function () {
                                modal.show();
                                // ClearInterval(tick);
                            }, sectomilisec(response.nexttick));
                        }
                    }
                    return true;
                }).fail(Notification.exception);

                return false;
            }

            modalRoot.on('click', CONSTANTS.mailaction, function (e) {
                e.preventDefault();

                clicktosend.trigger(errEvent, '');
                codeinput.trigger(errEvent, '');

                var promises = Ajax.call([{
                    methodname: CONSTANTS.mailajax,
                    args: {
                        contextid: config.contextid,
                    }
                }]);

                Loader.addIconToContainerRemoveOnCompletion(modalContent, promises[0]);

                promises[0].then(function (response) {
                    if (!response.success) {
                        clicktosend.trigger(errEvent, strings[0]);
                    } else {
                        Toast.add(strings[2]);
                    }
                    return true;
                }).fail(Notification.exception);
            });
            modalRoot.find(CONSTANTS.close).remove();
            modalRoot.find(CONSTANTS.closebtn).remove();
            modalRoot.on(ModalEvents.save, modalSave);
            modalRoot.on('submit', 'form', modalSave);
            modalRoot.on(ModalEvents.shown, function () {
                document.body.classList.add(CONSTANTS.authbodyclass);
            });
            modalRoot.on(ModalEvents.hidden, function () {
                document.body.classList.remove(CONSTANTS.authbodyclass);
            });
            setTimeout(function () {
                modal.show();
                displayErrors.enhance(CONSTANTS.codeinput.replace('#', ''));
                displayErrors.enhance(CONSTANTS.clicktosend.replace('#', ''));
            }, sectomilisec(timeInterval));
            return true;
        }).fail(Notification.exception);
    }

    return {init: init};
});
