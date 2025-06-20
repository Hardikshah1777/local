/**
 * Some UI stuff for participants page.
 * This is also used by the report/participants/index.php because it has the same functionality.
 *
 * @module     local_userstatus/status38
 * @package    local_userstatus
 */
// eslint-disable-next-line max-len
define(['jquery', 'core/str', 'core/modal_factory', 'core/modal_events', 'core/templates', 'core/notification', 'core/config', 'core/yui', 'core/ajax'],
    function($, Str, ModalFactory, ModalEvents, Templates, Notification, config, Y, Ajax) {

        var SELECTORS = {
            BULKACTIONSELECT: "#formactioncustom",
            BULKUSERCHECKBOXES: "input.usercheckbox",
            BULKUSERNOSCHECKBOXES: "input.usercheckbox[value='0']",
            BULKUSERSELECTEDCHECKBOXES: "input.usercheckbox:checked",
            BULKACTIONFORM: "#participantsform",
            CHECKALLBUTTON: "#checkall",
            CHECKALLNOSBUTTON: "#checkallnos",
            STATUSCHANGER: "#statuschanger",
            mailtemplate: '#id_mailtemplate',
            mailbody: '#id_mailbody',
        };

        var endpoint = config.wwwroot + '/local/userstatus/ajax.php';
        var component = 'local_userstatus';

        /**
         * Constructor
         *
         * @param {Object} options Object containing options. Contextid is required.
         * Each call to templates.render gets it's own instance of this class.
         */
        var Participants = function(options) {

            this.courseId = options.courseid;
            this.statuses = options.statuses;
            this.templates = options.templates;
            this.notemplateid = parseInt(options.notemplateid);
            this.userindex = options.userindex || false;
            this.noteStateNames = options.noteStateNames;
            this.stateHelpIcon = options.stateHelpIcon;

            this.attachEventListeners();
        };
        // Class variables and functions.

        /**
         * @var {int} courseId
         * @private
         */
        Participants.prototype.courseId = -1;

        /**
         * Private method
         *
         * @method attachEventListeners
         * @private
         */
        Participants.prototype.attachEventListeners = function() {
            var bulkSelect = $(SELECTORS.BULKACTIONSELECT);
            $(SELECTORS.BULKACTIONSELECT).on('change', function(e) {
                var action = $(e.target).val();
                if (action.indexOf('#') !== -1) {
                    e.preventDefault();

                    var ids = [];
                    $(SELECTORS.BULKUSERSELECTEDCHECKBOXES).each(function(index, ele) {
                        var name = $(ele).attr('name');
                        var id = name.replace('user', '');
                        ids.push(id);
                    });

                    if (action == '#messageselect') {
                        this.showSendMessage(ids).fail(Notification.exception);
                    } else if (action == '#addgroupnote') {
                        this.showAddNote(ids).fail(Notification.exception);
                    }
                    $(SELECTORS.BULKACTIONSELECT + ' option[value=""]').prop('selected', 'selected');
                } else if (action !== '') {
                    if ($(SELECTORS.BULKUSERSELECTEDCHECKBOXES).length > 0) {
                        $(SELECTORS.BULKACTIONFORM).submit();
                    } else {
                        $(SELECTORS.BULKACTIONSELECT + ' option[value=""]').prop('selected', 'selected');
                    }
                }
            }.bind(this));
            if (bulkSelect.length > 0) {
                $(SELECTORS.BULKACTIONFORM).attr('data-enhanced', true);
                var loader = window.M.util.add_lightbox(Y, Y.Node(document.querySelector(SELECTORS.BULKACTIONFORM)));
                var params = {
                    sesskey: config.sesskey,
                    id: this.courseId,
                    userids: [],
                    action: 'get',
                };
                var ref = this;
                Str.get_strings([
                    {
                        key: 'changestatus',
                        component: component,
                    }, {
                        key: 'choose',
                    },
                ]).then(function(strings) {
                    var label = $('<label for="statuschanger" class="col-form-label d-inline ml-2">' + strings[0] + '</label>');
                    label.insertAfter($(SELECTORS.BULKACTIONSELECT));
                    var selectbox = $('<select id="statuschanger" class="custom-select mr-2" disabled></select>');
                    selectbox.append('<option value="">' + strings[1] + '</option>');
                    $.each(this.statuses, function(key, value) {
                        selectbox.append('<option value="' + key + '">' + value + '</option>');
                    });
                    selectbox.insertAfter(label);
                    var idrows = {};
                    $(SELECTORS.BULKUSERCHECKBOXES).each(function(index, ele) {
                        var id = ele.name.replace('user', '');
                        params.userids.push(id);
                        idrows[id] = $(ele).parents('tr');
                    }).on('change', function() {
                        selectbox.prop('disabled', $(SELECTORS.BULKUSERSELECTEDCHECKBOXES).length === 0);
                    });
                    if (0 && params.userids.length > 0) {
                        loader.show();
                        $.post(endpoint, params).done(function(response) {
                            if (response.success && response.users) {
                                $.each(response.users, function(key, value) {
                                    if (ref.userindex) {
                                        idrows[key].find('.c1').append(value);
                                    } else {
                                        idrows[key].find('[data-statusid]').replaceWith(value);
                                    }
                                });
                            }
                            params.action = 'put';
                            params.userids = [];
                            loader.hide();
                        });
                    }
                    selectbox.on('change', function(e) {
                        params.status = $(e.target).val();
                        params.userids = [];
                        $(SELECTORS.BULKUSERSELECTEDCHECKBOXES).each(function(index, ele) {
                            var id = ele.name.replace('user', '');
                            params.userids.push(id);
                        });
                        if (params.userids.length === 0 || params.status === '') {
                            return;
                        }
                        selectbox.prop('disabled', true);
                        loader.show();
                        $.post(endpoint, params).done(function(response) {
                            if (response.success && response.users) {
                                $.each(response.users, function(key, value) {
                                    idrows[key].find('[data-statusid]').replaceWith(value);
                                });
                            }
                            selectbox.val('').prop('disabled', false);
                            setTimeout(() => {
                                loader.hide();
                            }, 500);
                        });
                    });
                }.bind(this)).fail(Notification.exception);
            }
        };

        Participants.prototype.showSendMessage = function(users) {

            if (users.length == 0) {
                // Nothing to do.
                return $.Deferred().resolve().promise();
            }
            var titlePromise = null;
            if (users.length == 1) {
                titlePromise = Str.get_string('sendbulkmessagesingle', 'core_message');
            } else {
                titlePromise = Str.get_string('sendbulkmessage', 'core_message', users.length);
            }

            return $.when(
                ModalFactory.create({
                    type: ModalFactory.types.SAVE_CANCEL,
                    body: Templates.render('local_userstatus/send_bulk_message',
                        {
                            templates: this.templates,
                            notemplateid: this.notemplateid,
                        })
                }),
                titlePromise
            ).then(function(modal, title) {
                // Keep a reference to the modal.
                this.modal = modal;

                this.modal.setTitle(title);
                this.modal.setSaveButtonText(title);

                // We want to focus on the action select when the dialog is closed.
                this.modal.getRoot().on(ModalEvents.hidden, function() {
                    $(SELECTORS.BULKACTIONSELECT).focus();
                    this.modal.getRoot().remove();
                }.bind(this));

                this.modal.getRoot().on(ModalEvents.save, this.submitSendMessage.bind(this, users));

                this.modal.show();

                var templateObj = {};
                this.templates.forEach(template => {
                    templateObj[parseInt(template.id)] = template;
                });
                templateObj[this.notemplateid] = {message: ''};

                this.modal.getRoot().on('change', function (e) {
                    var mailtemplate = document.querySelector(SELECTORS.mailtemplate);
                    var mailbody = document.querySelector(SELECTORS.mailbody);
                    var selectedtemplate = parseInt(mailtemplate.value);
                    if (mailtemplate.contains(e.target)) {
                        mailbody.value = templateObj[selectedtemplate].message;
                        return true;
                    }
                    if (selectedtemplate !== this.notemplateid) {
                        return false;
                    }
                    if (mailbody.contains(e.target)) {
                        templateObj[this.notemplateid].message = mailbody.value;
                    }
                }.bind(this));

                return this.modal;
            }.bind(this));
        };

        Participants.prototype.submitSendMessage = function(users) {

            var messageText = this.modal.getRoot().find('form textarea').val();

            var messages = [],
                i = 0;

            for (i = 0; i < users.length; i++) {
                messages.push({touserid: users[i], text: messageText});
            }

            return Ajax.call([{
                methodname: 'local_userstatus_send_instant_messages',
                args: {messages: messages}
            }])[0].then(function(messageIds) {
                if (messageIds.length == 1) {
                    return Str.get_string('sendbulkmessagesentsingle', 'core_message');
                } else {
                    return Str.get_string('sendbulkmessagesent', 'core_message', messageIds.length);
                }
            }).then(function(msg) {
                Notification.addNotification({
                    message: msg,
                    type: "success"
                });
                return true;
            }).catch(Notification.exception);
        };

        Participants.prototype.showAddNote = function(users) {

            if (users.length == 0) {
                // Nothing to do.
                return $.Deferred().resolve().promise();
            }

            var states = [];
            for (var key in this.noteStateNames) {
                switch (key) {
                    case 'draft':
                        states.push({value: 'personal', label: this.noteStateNames[key]});
                        break;
                    case 'public':
                        states.push({value: 'course', label: this.noteStateNames[key], selected: 1});
                        break;
                    case 'site':
                        states.push({value: key, label: this.noteStateNames[key]});
                        break;
                }
            }

            var context = {stateNames: states, stateHelpIcon: this.stateHelpIcon};
            var titlePromise = null;
            if (users.length == 1) {
                titlePromise = Str.get_string('addbulknotesingle', 'core_notes');
            } else {
                titlePromise = Str.get_string('addbulknote', 'core_notes', users.length);
            }

            return $.when(
                ModalFactory.create({
                    type: ModalFactory.types.SAVE_CANCEL,
                    body: Templates.render('core_user/add_bulk_note', context)
                }),
                titlePromise
            ).then(function(modal, title) {
                // Keep a reference to the modal.
                this.modal = modal;
                this.modal.setTitle(title);
                this.modal.setSaveButtonText(title);

                // We want to focus on the action select when the dialog is closed.
                this.modal.getRoot().on(ModalEvents.hidden, function() {
                    var notification = $('#user-notifications [role=alert]');
                    if (notification.length) {
                        notification.focus();
                    } else {
                        $(SELECTORS.BULKACTIONSELECT).focus();
                    }
                    this.modal.getRoot().remove();
                }.bind(this));

                this.modal.getRoot().on(ModalEvents.save, this.submitAddNote.bind(this, users));

                this.modal.show();

                return this.modal;
            }.bind(this));
        };

        Participants.prototype.submitAddNote = function(users) {
            var noteText = this.modal.getRoot().find('form textarea').val();
            var publishState = this.modal.getRoot().find('form select').val();
            var notes = [],
                i = 0;

            for (i = 0; i < users.length; i++) {
                notes.push({userid: users[i], text: noteText, courseid: this.courseId, publishstate: publishState});
            }

            return Ajax.call([{
                methodname: 'core_notes_create_notes',
                args: {notes: notes}
            }])[0].then(function(noteIds) {
                if (noteIds.length == 1) {
                    return Str.get_string('addbulknotedonesingle', 'core_notes');
                } else {
                    return Str.get_string('addbulknotedone', 'core_notes', noteIds.length);
                }
            }).then(function(msg) {
                Notification.addNotification({
                    message: msg,
                    type: "success"
                });
                return true;
            }).catch(Notification.exception);
        };

        return {
            // Public variables and functions.

            /**
             * Initialise the unified user filter.
             *
             * @method init
             * @param {Object} options - List of options.
             * @return {Participants}
             */
            'init': function(options) {
                return new Participants(options);
            }
        };
    });
