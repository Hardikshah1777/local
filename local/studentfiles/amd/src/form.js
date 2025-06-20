import {get_strings as getStrings} from "core/str";
import Notification from "core/notification";

const selectors = {
    mailtemplate: '#id_mailtemplate',
    mailsubject: '#id_mailsubject',
    mailbody: '#id_mailbody',
};

const component = 'local_studentfiles';

// eslint-disable-next-line require-jsdoc
export function registerEventListener() {
    let mailtemplate = document.querySelector(selectors.mailtemplate);
    let mailsubject = document.querySelector(selectors.mailsubject);
    let mailbody = document.querySelector(selectors.mailbody);
    let form = mailbody.closest('form');
    let {templates, notemplateid} = JSON.parse(mailtemplate.dataset.json);
    let templateObj = {};
    let stringkeys = [];
    let changeEvent = document.createEvent('HTMLEvents');
    changeEvent.initEvent('change', true, true);
    templates.forEach(template => {
        templateObj[parseInt(template.id)] = template;
        if (template.string) {
            stringkeys.push(template.subject);
            stringkeys.push(template.message);
        }
    });
    getStrings(stringkeys.map(stringkey => {
        return {key: stringkey, component};
    })).then(function(strings) {
        for (let id in templateObj) {
            let template = templateObj[id];
            if (template.string) {
                template.subject = strings[stringkeys.indexOf(template.subject)];
                template.message = strings[stringkeys.indexOf(template.message)];
            }
        }
        if (parseInt(mailtemplate.value) === notemplateid) {
            templateObj[notemplateid].subject = mailsubject.value;
            templateObj[notemplateid].message = mailbody.value;
        }
        form.addEventListener('change', function(e) {
            let selectedtemplate = parseInt(mailtemplate.value);
            if (mailtemplate.contains(e.target)) {
                mailsubject.value = templateObj[selectedtemplate].subject;
                mailbody.value = templateObj[selectedtemplate].message;
                mailsubject.dispatchEvent(changeEvent);
                mailbody.dispatchEvent(changeEvent);
                return true;
            }
            if (selectedtemplate !== notemplateid) {
                return false;
            }
            if (mailsubject.contains(e.target)) {
                templateObj[notemplateid].subject = mailsubject.value;
            }
            if (mailbody.contains(e.target)) {
                templateObj[notemplateid].message = mailbody.value;
            }
            return true;
        });
        return true;
    }).catch(Notification.exception);
}
