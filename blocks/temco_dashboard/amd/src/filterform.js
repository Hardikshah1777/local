import * as DynamicTable from 'core_table/dynamic';
import * as Notification from 'core/notification';

const SELECTORS = {
    filterform: '#filterform form',
};

export const initTable = () => {
    const form = document.querySelector(SELECTORS.filterform);
    if (!form) {
        return false;
    }
    const submitHandler = e => {
        const {target: triggerelement, submitter} = e;
        e.preventDefault();
        const table = DynamicTable.getTableFromId("temcouser");
        const filterset = DynamicTable.getFilters(table);
        for (let name in filterset.filters) {
            if (name in form.elements || `${name}[]` in form.elements) {
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
                [...formelement.selectedOptions]
                    .map(option => filterset.filters[name].values.push(parseInt(option.value)));
                if (!refreshTable) {
                    refreshTable = formelement === triggerelement;
                }
            } else if (formelement instanceof HTMLInputElement) {
                if (formelement.type === 'checkbox' && !formelement.checked) {
                    return;
                }
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

    form.addEventListener('submit', submitHandler);
    return true;
};
