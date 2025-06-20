import * as Toast from 'core/toast';

const Selectors = {
    alertButtons: "[data-action='sendmail']",
};

export const handletoast = () => {
    document.addEventListener('click', e => {
        var alertButton = e.target.closest(Selectors.alertButtons);
        if (!alertButton){
            return false;
        }
        if (alertButton) {
            Toast.add('Mail sent', {
                type: 'success',
                closeButton: false,
                autohide: true,
            });
        }
    });
};
