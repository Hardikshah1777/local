<?php

require_once 'config.php';

$context = context_system::instance();
$url = new moodle_url('/test.php');

$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title('Test');
require_login();

echo $OUTPUT->header();

?>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <script src="https://unpkg.com/react@17/umd/react.production.min.js"></script>
        <script src="https://unpkg.com/react-dom@17/umd/react-dom.production.min.js"></script>
        <script src="https://unpkg.com/@babel/standalone/babel.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/vue@2"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
    </head>
<body>
<div class="<!--d-flex--> d-none">
    <div id="level" class="pt-2 pr-2">Level: 1</div>
    <button class="btn btn-primary mb-3" id="addLevelButton" onclick="addLevel()">Add Level</button>
</div>

<script>
    const levelDisplay = document.getElementById('level');
    let level = 1;
    function addLevel() {
        level++;
        levelDisplay.textContent = `Level: ${level}`;
    }
</script>

<div id="root"></div>

<script type="text/babel">
    class GreetingApp extends React.Component {
        constructor(props) {
            super(props);
            this.state = {
                message: 'Hello, ',
                inputText: ''
            };
        }
        handleInputChange = (event) => {
            this.setState({ inputText: event.target.value });
        }
        changeMessage1 = () => {
            if (this.state.inputText.trim() !== '') {
                this.setState({ message: 'Hello, ' + this.state.inputText + '', inputText: '' });
            } else {
                alert('Please enter your name! 111');
            }
        }
        render() {
            return (
                <div className="w-100 mb-4 d-none">
                    <h2>{this.state.message}</h2>
                    <input type="text" value={this.state.inputText} onChange={this.handleInputChange} placeholder="Enter your name" className="float-left form-control w-25 mr-4"/>
                    <button onClick={this.changeMessage1} className="btn btn-primary">Submit 1</button>
                </div>
            );
        }
    }
    ReactDOM.render(<GreetingApp />, document.getElementById('root'));
</script>

<div id="app" class="mb-3 w-100 d-none">
    <h3>{{ message }}</h3>
    <input v-model="inputText" type="text" placeholder="Enter your name" class="form-control w-25 mr-4 float-left">
    <button @click="changeMessage" class="btn btn-primary">Submit 2</button>
</div>

<script>
    var app = new Vue({
        el: '#app',
        data: {
            message: 'Hi, ',
            inputText: ''
        },
        methods: {
            changeMessage: function() {
                if (this.inputText.trim() !== '') {
                    this.message = 'Hi, ' + this.inputText, this.inputText = '';
                } else {
                    alert('Please enter your name! 222');
                }
            }
        }
    });
</script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>

<script>

    async function exportTableToPDF(filename) {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF();

        const table = document.querySelector("table");

        if (!table) {
            Swal.fire({
                icon: 'error',
                title: 'No table found',
                text: 'Please make sure there is a table on the page.',
                confirmButtonText: 'OK',
                allowOutsideClick:false,
                allowEscapeKey:false,
            });
            return;
        }

        doc.autoTable({ html: table });

        // doc.save(filename);

        Swal.fire({
            title: 'Export Successful',
            text: 'The data has been exported as a PDF!',
            icon: 'success',
            confirmButtonText: 'OK',
            //showCancelButton: false,
            showConfirmButton: false,
            confirmButtonColor: '#3085d6',
            cancelButtonColor:'#d33',
            iconColor:'#d33',
            timer: 2000,
            // toast: true,
            timerProgressBar:true,
            allowOutsideClick:true,
            allowEscapeKey:false,
            allowEnterKey:true,
        });
    }

    function handlePDFExport() {
        Swal.fire({
            title: 'Export Data',
            text: 'Are you sure you want to export the data?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Export',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                exportTableToPDF('Export Data.pdf');
            }
        });
    }


    function exportTableToCSV(filename) {
        var csv = [];
        var rows = document.querySelectorAll('table tr');

        rows.forEach(function(row) {
            var rowData = [];
            row.querySelectorAll('th, td').forEach(function(cell) {
                rowData.push(cell.innerText);
            });
            csv.push(rowData.join(','));
        });

        var csvContent = csv.join('\n');
        var blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });

        if (navigator.msSaveBlob) { // IE 10+
            navigator.msSaveBlob(blob, filename);
        } else {
            var link = document.createElement("a");
            if (link.download !== undefined) {
                var url = URL.createObjectURL(blob);
                link.setAttribute("href", url);
                link.setAttribute("download", filename);
                link.style.visibility = 'hidden';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                // Show SweetAlert dialog after export
                Swal.fire({
                    title: 'Export Successful',
                    text: 'The data has been exported successfully!',
                    icon: 'success',
                    confirmButtonText: 'OK',
                    timer: 2000,
                });
            }
        }
    }

    function handleExport() {
        Swal.fire({
            title: 'Export Data',
            text: 'Are you sure you want to export the data?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Export',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                exportTableToCSV('Export Data.csv');
            }
        });
    }

    function sortTableByName() {
        var table, rows, switching, i, x, y, shouldSwitch;
        table = document.querySelector("table");
        switching = true;
        while (switching) {
            switching = false;
            rows = table.rows;
            for (i = 1; i < (rows.length - 1); i++) {
                shouldSwitch = false;
                x = rows[i].getElementsByTagName("TD")[0];
                y = rows[i + 1].getElementsByTagName("TD")[0];
                if (x.innerHTML.toLowerCase() > y.innerHTML.toLowerCase()) {
                    shouldSwitch = true;
                    break;
                }
            }
            if (shouldSwitch) {
                rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
                switching = true;
            }
        }
    }
</script>
    <table border="1" class="flexible table table-striped table-hover generaltable generalbox d-none">
        <tr>
            <th onclick="sortTableByName()">Name</th>
            <th>Age</th>
            <th>Email</th>
        </tr>
        <tr>
            <td>John Doe</td>
            <td>30</td>
            <td>john@example.com</td>
        </tr>
        <tr>
            <td>Jane Smith</td>
            <td>25</td>
            <td>jane@example.com</td>
        </tr>
    </table>
    <button onclick="handleExport()" class="btn btn-primary">Export to CSV</button>
    <button onclick="handlePDFExport()" class="btn btn-info">Export to PDF</button>
</body>
</html>
<?php

echo $OUTPUT->footer();