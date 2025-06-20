/**
 * @module report_behaviour/behaviour_sessions_filterform
 */

import * as DynamicTable from 'core_table/dynamic';
import * as Notification from 'core/notification';

var SELECTORS = {
    filterform: '#behaviour_filterform form',
    table: '#region-main table',
    save: '#savefilter',
    cancel: '#cancelfilter',
    tabid: 'behaviour_session',
    exportbtn: '#exportbtn',
};

export const initTable = () => {
    var form = document.querySelector(SELECTORS.filterform);
    if (!form) {
        return false;
    }
    const submitHandler = e => {
        const {target: triggerelement, submitter} = e;

        e.preventDefault();
        var table = DynamicTable.getTableFromId(SELECTORS.tabid);
        var filterset = DynamicTable.getFilters(table);
        for (let name in filterset.filters) {
            if (name in form.elements || (name+'[]') in form.elements) {
                filterset.filters[name].values = [];
            }
        }

        let refreshTable = false;
        [...form.elements].forEach(formelement => {
            const name = formelement.name.replace('[]', '');
            if (!(name in filterset.filters)) {
                return;
            }

            if (formelement instanceof HTMLSelectElement) {
                if (formelement.value === '' || formelement.value === 0 || !formelement.value) {
                    return;
                }

                setbtnfilter(formelement.name, formelement.value);
                [...formelement.selectedOptions]
                    .map(option => filterset.filters[name].values.push(parseInt(option.value)));

                if (!refreshTable) {
                    refreshTable = formelement === triggerelement;
                }
            } else if (formelement instanceof HTMLInputElement) {
                if (formelement.type === 'checkbox' && !formelement.checked) {
                    return;
                }
                if (formelement.value === '') {
                    return;
                }

                setbtnfilter(formelement.name, formelement.value);
                filterset.filters[name].values.push(formelement.value);
                if (!refreshTable) {
                    refreshTable = formelement === triggerelement && formelement.type === 'text';
                }
            }
        });
        if (refreshTable || submitter) {
            DynamicTable.setFilters(table, filterset)
                .then(tableRoot => tableRoot.setAttribute('data-ajaxloaded', 1))
                .catch(Notification.exception);
        }
        return false;
    };

    const cancelHandler = e => {

        e.preventDefault();
        var form = document.querySelector(SELECTORS.filterform);
        var tableRoot = DynamicTable.getTableFromId(SELECTORS.tabid);
        var filterset = DynamicTable.getFilters(tableRoot);

        form.reset();

        [...form.elements].forEach(formelement => {
            const name = formelement.name.replace('[]', '');
            if (!(name in filterset.filters)) {
                return;
            }
            if (formelement instanceof HTMLSelectElement) {
                [...formelement.selectedOptions]
                    .map(() => filterset.filters[name].values.pop());
            } else if (formelement instanceof HTMLInputElement) {
                filterset.filters[name].values.pop();
            }
        });

        DynamicTable.setFilters(tableRoot, filterset)
            .then(tableRoot => tableRoot.setAttribute('data-ajaxloaded', 1))
            .catch(Notification.exception);

        if (tableRoot) {
            DynamicTable.refreshTableContent(tableRoot, false);
        }
    };

    form.addEventListener('submit', submitHandler);
    document.querySelector(SELECTORS.cancel).addEventListener('click', cancelHandler);

    return true;
};

export const setbtnfilter = (name, value) => {
    let btn = document.querySelector(SELECTORS.exportbtn);
    let url = new URL(btn.href);
    url.searchParams.delete(name);
    url.searchParams.set(name, value);
    btn.href = url;
};