import Ajax from 'core/ajax';
import DynamicForm from 'core_form/dynamicform';
import * as DynamicTable from 'core_table/dynamic';
import * as Notification from 'core/notification';
import Fragment from 'core/fragment';

export const initTimer = (userId, courseId, intervalSeconds) => {
    const intervalMs = intervalSeconds * 1000;
    let timerId = null;

    const sendTime = (seconds) => {
        return Ajax.call([{
            methodname: 'report_kln_user_timetrack',
            args: {
                userid: parseInt(userId),
                courseid: parseInt(courseId),
                timespent: parseInt(seconds),
            }
        }]);
    };

    const updateTime = () => {
        if (document.hidden) {
            return;
        }
        sendTime(intervalSeconds);
    };

    const startTimer = () => {
        if (!timerId) {
            timerId = setInterval(updateTime, intervalMs);
        }
    };

    const stopTimer = () => {
        if (timerId) {
            clearInterval(timerId);
            timerId = null;
        }
    };

    if (!document.hidden) {
        startTimer();
    }

    document.addEventListener("visibilitychange", () => {
        if (document.hidden) {
            stopTimer();
        } else {
            startTimer();
        }
    });
};

export const setFilters = (formdata) => {
    const formwrapper = document.querySelector('[data-region="dynamicform"]');
    if (!formwrapper) {
        return;
    }

    var table = DynamicTable.getTableFromId(formwrapper.dataset.tableuniqueid);
    var filterset = DynamicTable.getFilters(table);

    for (const name of Object.keys(filterset.filters)) {
        filterset.filters[name].values = [];
        if (formdata[name] !== undefined) {
            if (name === 'userid' || name === 'courseid') {
                filterset.filters[name].values.push(parseInt(formdata[name]));
            } else {
                filterset.filters[name].values.push(formdata[name]);
            }
        }
    }

    DynamicTable.setFilters(table, filterset).catch(Notification.exception);
    return;
};

export const registerDynamicform = () => {
    const formContainer = document.querySelector('[data-region="dynamicform"]');
    const dynamicForm = new DynamicForm(formContainer, formContainer.dataset.formClass);

    dynamicForm.addEventListener(dynamicForm.events.FORM_SUBMITTED, (e) => {
        e.preventDefault();
        const response = e.detail;

        setFilters(response.formdata);
        dynamicForm.updateForm({...response, js: Fragment.processCollectedJavascript(response.js)});
    });
    dynamicForm.addEventListener(dynamicForm.events.CANCEL_BUTTON_PRESSED, (e) => {
        e.preventDefault();
    });
};
