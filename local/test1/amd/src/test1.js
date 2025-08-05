/* working */

export const init = () => {
    //alert('Test 1 js');
    window.console.log('Test 1 js');
};
/**
 * Description of showToast.
 * @param {string} message - The message.
 * @param {boolean} isSuccess - Indicates success or failure.
 */
function showToast(message, isSuccess = true) {
    // const toast = document.getElementById('toast');
    // toast.textContent = message;
    // toast.style.backgroundColor = isSuccess ? '#b3ffae' : '#ffe0e0';
    // toast.className = 'show d-flex justify-content-center p-2';
    // setTimeout(() => {
    //     toast.className = 'toast ';
    // }, 2000);

    const title = isSuccess ? 'Send' : 'Not Send';
    const icon = isSuccess ? 'success' : 'error';
    Swal.fire({
        title: 'Mail ' + title,
        text: message,
        icon: icon,
        confirmButtonText: 'OK',
        //showCancelButton: false,
        showConfirmButton: false,
        confirmButtonColor: '#3085d6',
        cancelButtonColor:'#d33',
        iconColor:'#d33',
        timer: 3000,
        // toast: true,
        timerProgressBar:true,
        allowOutsideClick:true,
        allowEscapeKey:false,
        allowEnterKey:true,
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
            body: JSON.stringify({ userid: userid })
        }).then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Email sent successfully to ' + data.username, true);
                    window.console.log('Email sent successfully to ' + data.username);
                } else {
                    showToast('Failed to send mail', false);
                }
            }).catch(error => {
            window.console.log(error);
            showToast('An error occurred while sending the email.', false);
        });
    });
});

document.querySelectorAll('.downloadpdf').forEach(link => {
    link.addEventListener('click', function(event) {
        event.preventDefault();
        const user = JSON.parse(this.getAttribute('data-user'));
        const tr = this.closest('tr');
        if (!tr) {
            window.error("Row not found");
            return;
        }

        const headers = ['', "Name", "Email", "City", "Date"];
        const rowData = Array.from(tr.children).map(td => td.textContent.trim());

        const tempTable = document.createElement('table');
        tempTable.appendChild(tr.cloneNode(true));
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF();

        const title = "User Information.";
        doc.setFontSize(16);
        doc.text(title, 70, 15);

        doc.autoTable({ head: [headers],
            body: [rowData],
            startY: 20,
        });
        let filename = user.firstname + ' ' + user.lastname + '.pdf';
        doc.save(filename);
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

        const headers = ["", "Name", "Email", "City", "Date"];
        const rowData = Array.from(tr.children).map(td => td.textContent.trim());

        const csvContent = [ headers.join(','), rowData.join(',') ].join("\\n");

        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        let filename = user.firstname + ' ' + user.lastname + '.csv';
        a.setAttribute('href', url);
        a.setAttribute('download', filename);
        a.style.display = 'none';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    });
});