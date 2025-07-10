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
 * A system for displaying small snackbar notifications to users which disappear shortly after they are shown.
 *
 * @module     core/toast
 * @package    core
 * @copyright  2019 Andrew Nicols <andrew@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/templates','core/notification','core/pending'], function ($, Templates, Notification, Pending) {
    var Toast = {
        /**
         * Add a new region to place toasts in, taking in a parent element.
         *
         * @param {Element} parent
         */
        addToastRegion: function (parent) {
            var pendingPromise = new Pending('addToastRegion');
            var promise = $.Deferred();

            try {
                Templates.render('local_authtimer/toastwrapper', {}).then(function (html, js) {
                    Templates.prependNodeContents(parent, html, js);
                    promise.resolve();
                });
            } catch (e) {
                Notification.exception(e);
            }

            pendingPromise.resolve();
            return promise.promise();
        },

        /**
         * Add a new toast or snackbar notification to the page.
         *
         * @param {String} message
         * @param {Object} configuration
         * @param {String} [configuration.title]
         * @param {String} [configuration.subtitle]
         * @param {Bool} [configuration.autohide=true]
         * @param {Number} [configuration.delay=4000]
         */
        add: function(message, configuration) {
            var pendingPromise = new Pending('addToastRegion');
            configuration = Object.assign({
                closeButton: false,
                autohide: true,
                delay: 4000,
            }, configuration);

            var templateName = "local_authtimer/toastmessage";
            try {
                Toast.getTargetNode().done(function(targetNode) {
                    return Templates.render(templateName, Object.assign({
                            message: message,
                    }, configuration)).then(function(html, js) {
                        return Templates.prependNodeContents(targetNode, html, js);
                    });
                }).fail(Notification.exception);
            } catch (e) {
                Notification.exception(e);
            }

            pendingPromise.resolve();
        },

        getTargetNode: function() {
            var regions = document.querySelectorAll('.toast-wrapper');

            if (regions.length) {
                var promise = $.Deferred();
                promise.resolve(regions[regions.length - 1]);
                return promise.promise();
            }

            return Toast.addToastRegion(document.body, 'fixed-bottom').then(function() {
                return Toast.getTargetNode();
            });
        }
    };
    return Toast;
});