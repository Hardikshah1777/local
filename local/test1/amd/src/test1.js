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
    if (toasttype.includes('isEmail')) {
        title = isSuccess ? 'Mail Send' : 'Mail Not Send';
    }
    if (toasttype.includes('ispdf')) {
        title = isSuccess ? 'Attachment Send' : 'Attachment Not Send';
    }
    let icon = isSuccess ? 'success' : 'error';
    let toast = false;
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