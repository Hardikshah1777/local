<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * html data format writer
 *
 * @package    dataformat_html
 * @copyright  2016 Brendan Heywood (brendan@catalyst-au.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace dataformat_html;

defined('MOODLE_INTERNAL') || die();

/**
 * html data format writer
 *
 * @package    dataformat_html
 * @copyright  2016 Brendan Heywood (brendan@catalyst-au.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class writer extends \core\dataformat\base {

    /** @var $mimetype */
    public $mimetype = "text/html";

    /** @var $extension */
    public $extension = ".html";

    /**
     * Write the start of the output
     */
    public function start_output() {
        global $CFG, $SCRIPT;
        echo "<!DOCTYPE html><br><br><html><head>";
        echo \html_writer::empty_tag('meta', ['charset' => 'UTF-8']);
        echo \html_writer::tag('title', $this->filename);
        $background = '#eee';
        $img1 = $img2 = '';
        if ($SCRIPT === '/local/test1/maillog.php') {
            $img1 = $CFG->wwwroot."/local/test1/pix/test1.jpg";
            $img2 = $CFG->wwwroot."/local/test1/pix/test2.jpg";
            $background = '#dae6f2';
        }

        echo "<style>
html, body {
    margin: 0;
    padding: 0;
    font-family: sans-serif;
    font-size: 13px;
    background: $background;
    background-image: url($img2);
    background-repeat: no-repeat;
    background-size: cover; 
}
th {
    border: solid 1px #999;
    background: #eee;
}
td {
    border: solid 1px #999;
    background: #fff;
}
tr:hover td {
    background: #eef;
}
table {
    border-collapse: collapse;
    width: 90%;
    margin: auto;
    background: $background;
    background-image: url($img1);    
    background-position: center; 
    background-repeat: no-repeat;
}

th, td {
  border: 1px solid #999;
  padding: 0px;
  background-color: rgba(255, 255, 255, 0.5);
}
</style>
</head>
<body>";
    }

    /**
     * Write the start of the sheet we will be adding data to.
     *
     * @param array $columns
     */
    public function start_sheet($columns) {
        echo "<table>";
        echo \html_writer::start_tag('tr');
        foreach ($columns as $k => $v) {
            echo \html_writer::tag('th', $v, ['style' => 'font-size: 1.171875rem;']);
        }
        echo \html_writer::end_tag('tr');
    }

    /**
     * Method to define whether the dataformat supports export of HTML
     *
     * @return bool
     */
    public function supports_html(): bool {
        return true;
    }

    /**
     * Write a single record
     *
     * @param array $record
     * @param int $rownum
     */
    public function write_record($record, $rownum) {
        $record = $this->format_record($record);

        echo \html_writer::start_tag('tr', ['style' => 'color:#0f1266;']);
        foreach ($record as $cell) {
            echo \html_writer::tag('td', $cell);
        }
        echo \html_writer::end_tag('tr');
    }

    /**
     * Write the end of the sheet containing the data.
     *
     * @param array $columns
     */
    public function close_sheet($columns) {
        echo "</table>";
    }

    /**
     * Write the end of the sheet containing the data.
     */
    public function close_output() {
        echo "</body></html>";
    }
}
