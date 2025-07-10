import $ from 'jquery';
import Ajax from 'core/ajax';
import ModalFactory from 'core/modal_factory';
import ModalEvents from 'core/modal_events';
import * as Str from 'core/str';
import {addIconToContainer} from 'core/loadingicon';
import Log from 'core/log';
import {get_string as getString} from "core/str";
import ModalForm from 'core_form/modalform';
import {add as addToast} from 'core/toast';
import {defaultException} from 'core/notification';

const SELECTORS = {
    resetbtn: '[id="resetbtn"]'
};

const COMPONENT = 'local_squibit';

const TIME = 5 * 1000;

const cache = {};

const showToolip = (btn, value, isshow) => {
    return getString(`resetremainingmsg`, COMPONENT, value).then(str => {
        $(btn).tooltip('hide')
            .attr('data-original-title', str)
            .tooltip('show');
        if (isshow === true) {
            $(btn).tooltip('hide');
        }
    });
};

export const init = () => {
    var button = document.querySelector(SELECTORS.resetbtn);

    button.addEventListener('click', (e) => {
        e.preventDefault();

        custommodal(
            Str.get_string('confirmation', 'admin'),
            Str.get_string('confirmationmsg', COMPONENT),
            Str.get_string('yes', 'moodle')
        ).then(function(confirmationmodal) {

            confirmationmodal.getRoot().on(ModalEvents.save, function() {

                const modalform = new ModalForm({
                    formClass: "local_squibit\\form\\authform",
                    modalConfig: {
                        title: Str.get_string('authenticateuser', COMPONENT),
                        buttons: {
                            save: Str.get_string('save', 'moodle'),
                        },
                        large: false
                    },
                    args: {},
                    returnFocus: button,
                });
                modalform.addEventListener(modalform.events.FORM_SUBMITTED,
                    (responce) => {
                        if (responce.detail) {
                            var argsdata = {
                                action: "delete",
                            };
                            Str.get_string('authenticatesuccess', COMPONENT)
                                .then(addToast).catch(defaultException);
                            callapi(button, argsdata);
                        }
                    }
                );
                modalform.show();
            });
            confirmationmodal.show();
        });
    });
};

export const custommodal = (title, body, savebtn) => {
    return ModalFactory.create({
        type: ModalFactory.types.SAVE_CANCEL,
        title: title,
        body: body,
        buttons: {
            save: savebtn,
        },
    });
};


export const callapi = (button, argsdata, lastloader) => {
    return addIconToContainer(button).then(loadingIcon => {
        if (lastloader) {
            lastloader.remove();
        }
        return Ajax.call([{
            methodname: 'local_squibit_sync_all_reset',
            args: argsdata
        }])[0].then(({result, usercount, coursecount}) => {
            if (usercount === 0 && coursecount === 0 && !result) {
                const btnvalue = {
                    usercount: 0,
                    coursecount: 0
                };
                loadingIcon.remove();
                button.classList.remove('disabled');
                showToolip(button, btnvalue, true);
                location.reload();
                return true;
            }
            if (usercount > 0 || coursecount > 0) {
                if (cache.usercount !== usercount) {
                    cache.usercount = usercount;
                }
                if (cache.coursecount !== coursecount) {
                    cache.coursecount = coursecount;
                }
                button.classList.add('disabled');
                const btnvalue = {
                    usercount: usercount,
                    coursecount: coursecount
                };
                showToolip(button, btnvalue, false);
                argsdata.action = '';
                setTimeout(() => callapi(button, argsdata, loadingIcon), TIME);
            } else {
                button.classList.remove('disabled');
            }

            return ((usercount === '') ? ((coursecount === '') ? false : coursecount) : usercount);
        }).catch(Log.debug);
    });
};
