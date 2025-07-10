define([
        'jquery',
        'core/custom_interaction_events',
        'core/modal',
        'core/modal_save_cancel',
        'core/modal_events',
        'core/key_codes'
    ],
    function ($,
              CustomEvents,
              VanillaModal,
              VanillaModalSaveCancel,
              ModalEvents,
              KeyCodes
    ) {

        var SELECTORS = {
            SAVE_BUTTON: '[data-action="save"]',
            CANCEL_BUTTON: '[data-action="cancel"]',
            HIDE: '[data-action="hide"]',
        };

        var Modal = function (root) {
            VanillaModal.call(this, root);
        };

        Modal.prototype = Object.create(VanillaModal.prototype);
        Modal.prototype.constructor = Modal;

        Modal.prototype.registerEventListeners = function () {
            this.getRoot().on('keydown', function (e) {
                if (!this.isVisible()) {
                    return;
                }

                if (e.keyCode == KeyCodes.tab) {
                    this.handleTabLock(e);
                } else if (e.keyCode == KeyCodes.escape) {
                    // This.hide();
                }
            }.bind(this));

            CustomEvents.define(this.getModal(), [CustomEvents.events.activate]);
            this.getModal().on(CustomEvents.events.activate, SELECTORS.HIDE, function (e, data) {
                this.hide();
                data.originalEvent.preventDefault();
            }.bind(this));
        };

        class ModalSaveCancel extends VanillaModalSaveCancel {
            registerEventListeners () {
                Modal.prototype.registerEventListeners.call(this);

                this.getModal().on(CustomEvents.events.activate, SELECTORS.SAVE_BUTTON, function (e, data) {
                    var saveEvent = $.Event(ModalEvents.save);
                    this.getRoot().trigger(saveEvent, this);

                    if (!saveEvent.isDefaultPrevented()) {
                        this.hide();
                        data.originalEvent.preventDefault();
                    }
                }.bind(this));

                this.getModal().on(CustomEvents.events.activate, SELECTORS.CANCEL_BUTTON, function (e, data) {
                    var cancelEvent = $.Event(ModalEvents.cancel);
                    this.getRoot().trigger(cancelEvent, this);

                    if (!cancelEvent.isDefaultPrevented()) {
                        this.hide();
                        data.originalEvent.preventDefault();
                    }
                }.bind(this));
            }
        }

        return {
            Modal: Modal,
            ModalSaveCancel: ModalSaveCancel
        };
    });
