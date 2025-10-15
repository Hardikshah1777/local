import * as DynamicTable from 'core_table/dynamic';

export const init = () => {
    window.console.log('--------------- Test 1 js working ---------------');
};
/**
 * Description of showToast.
 * @param {string} message - The message for show in swal.
 * @param {boolean} isSuccess - Indicates success or failure user email.
 * @param {string} toasttype - Indicates pdf send in user email.
 */
function showToast(message, isSuccess, toasttype) {
    // const toast = document.getElementById('toast');
    // toast.textContent = message;
    // toast.style.backgroundColor = isSuccess ? '#b3ffae' : '#ffe0e0';
    // toast.className = 'show d-flex justify-content-center p-2';
    // setTimeout(() => {
    //     toast.className = 'toast ';
    // }, 2000);
    let title = null;
    let toast = false;
    let icon = isSuccess ? 'success' : 'error';

    if (toasttype.includes('isEmail')) {
        title = isSuccess ? 'Mail Send' : 'Mail Not Send';
    }
    if (toasttype.includes('ispdf')) {
        title = isSuccess ? 'Attachment Send' : 'Attachment Not Send';
    }
    if (toasttype.includes('resendmail')) {
        title = isSuccess ? 'Mail Re-Send' : 'Mail Not Send';
        toast = true;
        icon = '';
    }

    if (message.includes('CSV')) {
        toast = true;
        icon = '';
    }

    Swal.fire({
        title: title,
        text: message,
        icon: icon,
        showConfirmButton: false,
        confirmButtonText: 'OK',
        showCancelButton: false,
        cancelButtonText: 'Cancle',
        confirmButtonColor: '#3085d6',
        cancelButtonColor:'#d33',
        iconColor:'#d33',
        timer: 3000,
        toast: toast,
        timerProgressBar:true,
        //allowOutsideClick:true,
        allowEscapeKey:false,
        //allowEnterKey:true,
    });
}

document.querySelectorAll('.maillink').forEach(link => {
    link.addEventListener('click', function(event) {
        event.preventDefault();
        const userid = this.getAttribute('data-uid');
        fetch(M.cfg.wwwroot +'/local/test1/testmail.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ uid: userid })
        }).then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Email successfully sent to ' + data.username, data.success, 'isEmail');
                } else {
                    showToast('Failed to send Email', data.success, 'isEmail');
                }
            }).catch(error => {
            window.console.log(error);
                showToast('An error occurred while sending the email.', false, 'isEmail');
        });
    });
});

document.querySelectorAll('.downloadpdf').forEach(link => {
    link.addEventListener('click', function(event) {
        event.preventDefault();
        const user = JSON.parse(this.getAttribute('data-user'));
        const tr = this.closest('tr');
        if (!tr) {
            window.console.error("Row not found");
            return;
        }

        const headers = ["Name", "Email", "City", "Date"];
        const rowData = Array.from(tr.children).map(td => td.textContent.trim()).slice(1);

        const { jsPDF } = window.jspdf;
        const doc = new jsPDF();

        const title = "User Information.";
        doc.setFontSize(16);
        doc.text(title, 80, 15);

        doc.autoTable({ head: [headers], body: [rowData], startY: 20 });

        const filename = user.firstname + ' ' + user.lastname + '.pdf';

        const blob = doc.output('blob'); // sync
        const formData = new FormData();
        formData.append('pdf', blob, filename);
        formData.append('userid', user.id);

        fetch(M.cfg.wwwroot +'/local/test1/testmail.php', {
            method: 'POST',
            body: formData
        }).then(response => response.json())
            .then(result => {
                if (result.success) {
                    showToast('PDF sent to ' + result.username, result.success, 'ispdf');
                    doc.save(filename);
                } else {
                    showToast('Failed to send PDF ', result.success, 'ispdf');
                }
            }).catch(error => {
                window.console.log(error);
                showToast('Error sending PDF in send mail', false, 'ispdf');
        });
    });
});

document.querySelectorAll('.downloadcsv').forEach(link => {
    link.addEventListener('click', function(event) {
        event.preventDefault();
        const user = JSON.parse(this.getAttribute('data-user'));
        const tr = this.closest('tr');
        if (!tr) {
            window.error("Row not found");
            return;
        }

        const headers = ["Name", "Email", "City", "Date"];
        const rowData = Array.from(tr.children).map(td => td.textContent.trim()).slice(1);

        const csvContent = [ headers.join(','), rowData.join(',') ].join("\n");

        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        let filename = user.firstname + ' ' + user.lastname + '.csv';
        a.setAttribute('href', url);
        a.setAttribute('download', filename);
        a.style.display = 'none';
        document.body.appendChild(a);
        a.click();
        showToast('CSV Exported successfully...',true, 'iscsv');
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    });
});

require(['core/modal_factory', 'jquery', 'jqueryui'], function(ModalFactory, $) {
    document.querySelectorAll('.viewmail').forEach(function(link) {
        link.addEventListener('click', function(event) {
            event.preventDefault();

            const user = JSON.parse(this.getAttribute('data-user'));
            let type = user?.type || ' General ';
            let title = user?.subject || 'No Subject';
            let body = user?.body || 'Nothing to display';
            title = 'Subject : ' + title;
            if (type) {
                type = '<p><b>Type : ' + type + ' </b></p><hr>';
            }
            ModalFactory.create({
                title: title,
                body: type + ' ' + body,
                large: true,
                closeButton: false,
                footer: '<button type="submit" class="btn btn-primary cancel-btn">Cancel</button>',
            }).then(function(modal) {
                const $modal = modal.getModal();
                $modal.addClass('custom-viewmail-modal');
                $modal.find('.modal-content').hide().fadeIn(500);
                modal.show().then(function () {
                    const $backdrop = $('.modal-backdrop');
                    $backdrop.css('opacity', '0');
                    setTimeout(() => {
                        $backdrop.addClass('show');
                        $backdrop.css('opacity', '0.5');
                    }, 10);
                });
                $modal.draggable({ handle: ".modal-header" });
                $modal.find('.modal-content').css({
                    resize: 'both',
                });
                $modal.on('click', '.cancel-btn', function (e) {
                    e.preventDefault();
                    const $backdrop = $('.modal-backdrop');
                    $modal.find('.modal-content').animate({
                        opacity: 0
                    }, 500, function () {
                    $backdrop.css('opacity', '0');
                    setTimeout(() => {
                        if (typeof modal.hide === 'function') {
                            modal.hide();
                        } else if (typeof modal.close === 'function') {
                            modal.close();
                        } else {
                            $modal.hide();
                        }
                    },500);
                    });
                });
            });
        });
    });
});

document.querySelectorAll('.resendmail').forEach(link => {
    link.addEventListener('click', function(event) {
        event.preventDefault();
        const user = JSON.parse(this.getAttribute('data-user'));
        let logid1 = user.id;

        fetch(M.cfg.wwwroot +'/local/test1/testmail.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ logid: logid1 }),
        }).then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Mail Re-send to ' + data.fullname, data.success, 'resendmail');
                    let dt = document.querySelector('.table-dynamic');
                    DynamicTable.refreshTableContent(DynamicTable.getTableFromId(dt.dataset.tableUniqueid));
                } else {
                    showToast('Mail Failed to Re-send', data.success, 'resendmail');
                }
            }).catch(error => {
                    window.console.log(error);
                    showToast('An error occurred while Re-sending the mail',false, 'resendmail');
        });
    });
});