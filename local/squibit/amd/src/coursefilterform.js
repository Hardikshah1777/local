/**
 * @module local_squibit/course_filterform
 */

import * as DynamicTable from 'core_table/dynamic';
import * as Notification from 'core/notification';

var SELECTORS = {
    filterform: '#coursefilterform form',
    table: '#region-main table',
    save: '#coursefilter',
    cancel: '#cancelfilter',
};

export const initTable = () => {
    var form = document.querySelector(SELECTORS.filterform);
    if (!form) {
        return false;
    }
    const submitHandler = e => {
        const {target: triggerelement, submitter} = e;

        e.preventDefault();
        var table = DynamicTable.getTableFromId("courses");
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
                if (formelement.value === '' || formelement.value === 0) {
                    return;
                }
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
                if (formelement.name === 'courseid') {
                    if (isNaN(formelement.value)) {
                        return;
                    }
                    filterset.filters[name].values.push(parseInt(formelement.value));
                } else {
                    filterset.filters[name].values.push(formelement.value);
                }
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
        var tableRoot = DynamicTable.getTableFromId("courses");
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