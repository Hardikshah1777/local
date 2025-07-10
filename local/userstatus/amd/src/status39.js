/**
 * Some UI stuff for participants page.
 * This is also used by the report/participants/index.php because it has the same functionality.
 *
 * @module     local_userstatus/status38
 * @package    local_userstatus
 */
define(['jquery', 'core/str', 'core/modal_factory', 'core/modal_events', 'core/templates', 'core/notification', 'core/config',
'core_table/dynamic'], function($, Str, ModalFactory, ModalEvents, Templates, Notification, config, DynamicTable) {

        var SELECTORS = {
            BULKACTIONSELECT: "#formactionid",
            BULKUSERCHECKBOXES: "input[data-togglegroup='participants-table'][data-toggle='slave']",
            BULKUSERSELECTEDCHECKBOXES: "input[data-togglegroup='participants-table'][data-toggle='slave']:checked",
            BULKACTIONFORM: "#participantsform",
            STATUSCHANGER: "#statuschanger",
        };

        var endpoint = config.wwwroot + '/local/userstatus/ajax.php';
        var component = 'local_userstatus';

        let tableForm = uniqueId => `form[data-table-unique-id="${uniqueId}"]`;

        /**
         * Constructor
         *
         * @param {Object} options Object containing options. Contextid is required.
         * Each call to templates.render gets it's own instance of this class.
         */
        var Participants = function(options) {

            this.courseId = options.courseid;

            this.statuses = options.statuses;

            this.selectbox = null;

            this.attachEventListeners();
        };
        // Class variables and functions.

        /**
         * @var {int} courseId
         * @private
         */
        Participants.prototype.courseId = -1;

        /**
         * @var null selectbox
         * @private
         */
        Participants.prototype.selectbox = null;

        /**
         * Private method
         *
         * @method attachEventListeners
         * @private
         */
        Participants.prototype.attachEventListeners = function() {
            if ($(SELECTORS.BULKACTIONSELECT).length > 0) {
                $(SELECTORS.BULKACTIONFORM).attr('data-enhanced', true);
                var params = {
                    sesskey: config.sesskey,
                    id: this.courseId,
                    userids: [],
                    action: 'get',
                };
                Str.get_strings([
                    {
                        key: 'changestatus',
                        component: component,
                    }, {
                        key: 'choose',
                    },
                ]).then(function(strings) {
                    if (!this.selectbox) {
                        var label = $('<label for="statuschanger" class="col-form-label d-inline ml-2">' + strings[0] + '</label>');
                        label.insertAfter($(SELECTORS.BULKACTIONSELECT));
                        var selectbox = $('<select id="statuschanger" class="custom-select" disabled></select>');
                        selectbox.append('<option value="">' + strings[1] + '</option>');
                        $.each(this.statuses, function(key, value) {
                            selectbox.append('<option value="' + key + '">' + value + '</option>');
                        });
                        selectbox.insertAfter(label);
                        selectbox.on('change', function(e) {
                            params.status = $(e.target).val();
                            params.userids = [];
                            let idrows = {};
                            $(SELECTORS.BULKUSERSELECTEDCHECKBOXES).each(function(index, ele) {
                                var id = ele.name.replace('user', '');
                                params.userids.push(id);
                                idrows[id] = $(ele).parents('tr');
                            });
                            if (params.userids.length === 0 || params.status === '') {
                                return;
                            }
                            $.post(endpoint, params).done(function(response) {
                                if (response.success && response.users) {
                                    $.each(response.users, function(key, value) {
                                        idrows[key].find('[data-statusid]').replaceWith(value);
                                    });
                                }
                                this.selectbox.val('');
                            }.bind(this));
                        }.bind(this));
                        this.selectbox = selectbox;
                    }
                    var idrows = {};
                    $(SELECTORS.BULKUSERCHECKBOXES).each(function(index, ele) {
                        var id = ele.name.replace('user', '');
                        params.userids.push(id);
                        idrows[id] = $(ele).parents('tr');
                    }).on('change', function() {
                        this.selectbox.prop('disabled', $(SELECTORS.BULKUSERSELECTEDCHECKBOXES).length === 0);
                    }.bind(this));
                    if (0 && params.userids.length > 0) {
                        $.post(endpoint, params).done(function(response) {
                            if (response.success && response.users) {
                                $.each(response.users, function(key, value) {
                                    idrows[key].find('.c1').append(value);
                                });
                            }
                            params.action = 'put';
                            params.userids = [];
                        });
                    }
                }.bind(this));
            }
        };

        Participants.prototype.refreshTable = function() {
            this.attachEventListeners();
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
                let dynamictable;
                let obj;
                if (options.uniqueid) {
                    dynamictable = document.querySelector(tableForm(options.uniqueid));
                    SELECTORS.BULKACTIONFORM = dynamictable;
                }
                obj = new Participants(options);
                if (dynamictable) {
                    dynamictable.addEventListener(DynamicTable.Events.tableContentRefreshed, function() {
                        obj.refreshTable();
                    });
                }
                return obj;
            }
        };
    });
