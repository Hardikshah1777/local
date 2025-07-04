<?php

require_once 'config.php';
require_once $CFG->libdir . '/tablelib.php';

$context = context_system::instance();
$url = new moodle_url('/test.php');

$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title('Test');
require_login();

class getusers extends table_sql{
    public function __construct($uniqueid)
    {
        parent::__construct($uniqueid);
    }

    public function col_timecreated($row) {
        return !empty($row->timecreated) ? userdate($row->timecreated, get_string('strftimedate', 'langconfig')) : '-';
    }

    public function col_lastaccess($row) {
        return !empty($row->lastaccess) ? userdate($row->lastaccess, get_string('strftimedate', 'langconfig')) : '-';
    }
}

//$sql = "CALL GetUsers()";
//$results = $DB->get_records_sql($sql, ['id' => 5]);
//foreach ($results as $row) {
//    echo $row->id.' '.$row->firstname.' '.$row->lastname. "<br>";
//}

$table = new getusers('getusers');
$table->set_sql('*', '{user}','1 = 1');

$col = [
    'fullname' => get_string('fullname'),
    'email' => get_string('email'),
    'department' => get_string('department', ),
    'city' => get_string('city'),
    'country' => get_string('country'),
    'timecreated' => get_string('timecreated'),
    'lastaccess' => get_string('lastaccess'),
    'phone1' => get_string('phone1'),
];

$table->define_columns(array_keys($col));
$table->define_headers(array_values($col));
$table->sortable(false);
$table->collapsible(false);
$table->define_baseurl($url);
$table->set_attribute('class', 'generaltable generalbox');

echo $OUTPUT->header();
//$table->out(30, false);

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

<div class="d-flex ">
    <div id="level" class="pt-2 pr-2">Level: 0</div>
    <button class="btn btn-primary mb-3" id="addLevelButton" onclick="addLevel()">Add Level</button>
</div>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script>
// axios.get('https://jsonplaceholder.typicode.com/posts/1')
// .then(response => { console.log(response.data); })
// .catch(error => { console.error('Error fetching data:', error); });

console.log('----------------------------------------------------------------- 1');
// axios.post('https://jsonplaceholder.typicode.com/posts/3', {
//     title: 'New Post', body: 'Hello, world!', userId:   1 })
// .then(response => { console.log('Created:', response.data); })
// .catch(error => { console.error('Error creating post:', error); });

console.log('-----------------------------------------------------------------  2');

// const response = await axios.get('https://jsonplaceholder.typicode.com/posts/1');
// console.log(response.data);

console.log('-----------------------------------------------------------------   3');

// const res = await axios.get('https://jsonplaceholder.typicode.com/users');
// this.users = res.data;
// function fetchData() {
//     return new Promise((resolve) => {
//         setTimeout(() => resolve("Data received!"), 1000);
//     });
// }

async function getData() {
    console.log("Fetching...");
    const result = await fetchData();
    console.log(result);
}

getData();

</script>
<script>
    const levelDisplay = document.getElementById('level');
    let level = 0;
    function addLevel() {
        level++;
        window.console.log(`Level: ${level}`);
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
                <div className="w-100 mb-4">
                    <h2>{this.state.message}</h2>
                    <input type="text" value={this.state.inputText} onChange={this.handleInputChange} placeholder="Enter your name" className="float-left form-control w-25 mr-4"/>
                    <button onClick={this.changeMessage1} className="btn btn-primary">Submit 1</button>
                </div>
            );
        }
    }
    ReactDOM.render(<GreetingApp />, document.getElementById('root'));
</script>

<div id="app">
    <h3>{{ message }}</h3>
    <input v-model="inputText" placeholder="Enter your name" class="form-control w-25 mr-4 float-left"/>
    <button @click="changeMessage" class="btn btn-info">Submit 2 </button>
</div>

<script>
var app = new Vue({
    el: '#app',
    data: { message: 'Hi,', inputText: '' },
    methods: {
        changeMessage() {
            const name = this.inputText.trim();
            if (name) {
                this.message = `Hi, ${name}`;
                // this.message = this.message.split('').reverse().join('');
                this.inputText = '';
            } else {
                alert('Please enter your name!');
            }
        }
    }
});

</script>


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

        doc.save(filename);

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
<div class="testtable d-none">
        <table border="1" class="flexible table table-striped table-hover generaltable generalbox">
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
    </div>

</body>
</html>
<?php

echo $OUTPUT->footer();