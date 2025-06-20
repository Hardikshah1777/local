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
define(['jquery', 'core/sortable_list', 'core/ajax', 'core/notification'], function($, SortableList, Ajax, Notification) {
    return {
        init: function() {
            // Initialise sortable for the given list.
            var sort = new SortableList('.skillsectionwrapper', {targetListSelector: '.skillsectionwrapper'});
            sort.getElementName = function(element) {
                return $.Deferred().resolve(element.attr('data-name'));
            };
            $('.skillsectionwrapper .sectionwrapper').on(SortableList.EVENTS.DRAGSTART, function(_, info) {
                setTimeout(function() {
                    $('.sortable-list-is-dragged').width(info.element.width());
                }, 501);
            }).on(SortableList.EVENTS.DROP, function(_, info) {
                var listsection = info.targetList[0].children;
                var data = [];
                    for (let i = 0; i < listsection.length - 1; i++) {
                        data.push({sectionid: listsection[i].getAttribute('data-id'), position: i});
                    }
                if (info.positionChanged) {
                    var request = {
                        methodname: 'mod_progressreport_invoke_move_action',
                        args: {
                            action: 'move_updown_field',
                            data: data,
                        }
                    };
                    Ajax.call([request])[0].fail(Notification.exception);
                }
            });
        }
    };
});
