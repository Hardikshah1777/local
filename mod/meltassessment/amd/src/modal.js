define(['jquery', 'core/notification', 'core/custom_interaction_events', 'core/modal', 'core/modal_registry', 'core/modal_events'],
    function($, Notification, CustomEvents, Modal, ModalRegistry, ModalEvents) {
        var registered = false;
        var SELECTORS = {
            CONFIRM_BUTTON: '[data-action="confirmsave"]',
            CANCEL_BUTTON: '[data-action="cancelsave"]',
        };

        /**
         * Constructor for the Modal.
         *
         * @param {object} root The root jQuery element for the modal
         */
        var ModalLogin = function(root) {
            Modal.call(this, root);

            if (!this.getFooter().find(SELECTORS.CONFIRM_BUTTON).length) {
                Notification.exception({message: 'No login button found'});
            }

            if (!this.getFooter().find(SELECTORS.CANCEL_BUTTON).length) {
                Notification.exception({message: 'No cancel button found'});
            }
        };
        ModalLogin.type = 'mod_meltassessment-confirm';
        ModalLogin.prototype = Object.create(Modal.prototype);
        ModalLogin.prototype.constructor = ModalLogin;

        /**
         * Set up all of the event handling for the modal.
         *
         * @method registerEventListeners
         */
        ModalLogin.prototype.registerEventListeners = function() {
            // Apply parent event listeners.
            Modal.prototype.registerEventListeners.call(this);

            this.getModal().on(CustomEvents.events.activate, SELECTORS.CONFIRM_BUTTON, function(e, data) {
                // Add your logic for when the login button is clicked. This could include the form validation,
                // loading animations, error handling etc.
                var saveEvent = $.Event(ModalEvents.save);
                this.getRoot().trigger(saveEvent, this);
                if (!saveEvent.isDefaultPrevented()) {
                    var savebtn = document.getElementById('save');
                    savebtn.click();
                    this.hide();
                    data.originalEvent.preventDefault();
                }
            }.bind(this));

            this.getModal().on(CustomEvents.events.activate, SELECTORS.CANCEL_BUTTON, function(e, data) {
                var cancelEvent = $.Event(ModalEvents.cancel);
                this.getRoot().trigger(cancelEvent, this);

                if (!cancelEvent.isDefaultPrevented()) {
                    this.hide();
                    data.originalEvent.preventDefault();
                }
            }.bind(this));
        };

        if (!registered) {
            ModalRegistry.register(ModalLogin.type, ModalLogin, 'mod_meltassessment/modal');
            registered = true;
        }
        return ModalLogin;
    });