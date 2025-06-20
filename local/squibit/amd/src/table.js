/* eslint-disable consistent-return*/
import $ from 'jquery';
import Ajax from 'core/ajax';
import {addIconToContainer} from 'core/loadingicon';
import Log from 'core/log';
import * as DynamicTable from 'core_table/dynamic';
import {get_string as getString} from 'core/str';
// eslint-disable-next-line babel/no-unused-expressions
import('theme_boost/index');

const COMPONENT = 'local_squibit';

const SELECTORS = {
    syncuser: '[data-action="syncuser"]',
    syncalluser: '[data-action="syncalluser"]',
    synccourse: '[data-action="synccourse"]',
    syncallcourse: '[data-action="syncallcourse"]',
};

const POLLINTVAL = 5 * 1000;

const ACTIONS = ['status', 'new', 'default'];

const cache = {};

const showToolip = (component) => {
    if (!cache.count) {
        return;
    }
    const type = component.getAttribute('data-type');
    return getString(`${type}remaining`, COMPONENT, cache).then(str => {
        $(component).tooltip('hide')
            .attr('data-original-title', str)
            .tooltip('show');
        setTimeout(() => $(component).tooltip('hide'), 1000);
    });
};

const updateSyncStatus = (component, action = ACTIONS[2], lastloader) => {
    if (!component) {
        return;
    }
    const type = component.getAttribute('data-type');
    const methodname = `${COMPONENT}_sync_all_${type}`;
    return addIconToContainer(component).then(loadingIcon => {
        if (lastloader) {
            lastloader.remove();
        }
        return Ajax.call([{
            methodname,
            args: {
                action
            }
        }])[0].then(({pending, count}) => {
            var actionvalue = ACTIONS[0];
            if (!pending) {
                loadingIcon.remove();
                location.reload();
            }

            if (count > 0) {
                if (cache.count !== count) {
                    cache.count = count;
                    showToolip(component);
                }
                component.setAttribute('disabled', 'disabled');
                if (cache.count === 1) {
                    cache.islast = true;
                }
                setTimeout(() => updateSyncStatus(component, actionvalue, loadingIcon), POLLINTVAL);
            } else {
                component.removeAttribute('disabled');
            }
            return count;
        }).catch(Log.debug);
    });
};

export const tableRegister = (tableUniqId) => {
    const syncalluser = document.querySelector(SELECTORS.syncalluser);
    if (syncalluser) {
        updateSyncStatus(syncalluser);
    }
    document.addEventListener('click', e => {
        const syncuser = e.target.closest(SELECTORS.syncuser);
        if (syncuser) {
            addIconToContainer(syncuser).then(loadingIcon => {
                const userid = syncuser.getAttribute('data-userid');
                const promise = Ajax.call([{
                    methodname: `${COMPONENT}_sync_user`,
                    args: {
                        userid,
                    }
                }])[0];
                promise.then(response => {
                    Log.debug(response);
                    const tableRoot = DynamicTable.getTableFromId(tableUniqId);
                    DynamicTable.refreshTableContent(tableRoot, false);
                    loadingIcon.remove();
                });
            });
        }
        const syncalluser = e.target.closest(SELECTORS.syncalluser);
        if (syncalluser) {
            if (!syncalluser.disabled) {
                updateSyncStatus(syncalluser, ACTIONS[1]);
            } else {
                showToolip(syncalluser);
            }
        }
    });

    const syncallcourse = document.querySelector(SELECTORS.syncallcourse);
    if (syncallcourse) {
        updateSyncStatus(syncallcourse);
    }
    document.addEventListener('click', e => {
        const synccourse = e.target.closest(SELECTORS.synccourse);
        if (synccourse) {
            addIconToContainer(synccourse).then(loadingIcon => {
                const courseid = synccourse.getAttribute('data-courseid');
                const promise = Ajax.call([{
                    methodname: `${COMPONENT}_sync_course`,
                    args: {
                        courseid,
                    }
                }])[0];
                promise.then(response => {
                    Log.debug(response);
                    const tableRoot = DynamicTable.getTableFromId(tableUniqId);
                    DynamicTable.refreshTableContent(tableRoot, false);
                    loadingIcon.remove();
                });
            });
        }
        const syncallcourse = e.target.closest(SELECTORS.syncallcourse);
        if (syncallcourse) {
            if (!syncallcourse.disabled) {
                updateSyncStatus(syncallcourse, ACTIONS[1]);
            } else {
                showToolip(syncallcourse);
            }
        }
    });
};
