<?php

namespace local_squibit;

global $CFG;

require_once $CFG->libdir . '/excellib.class.php';

use core_useragent;
use MoodleExcelWorkbook;
use PhpOffice\PhpSpreadsheet\IOFactory;

class syncreport_workbook extends MoodleExcelWorkbook {

    public function close() {
        global $CFG;
        foreach ($this->objspreadsheet->getAllSheets() as $sheet) {
            $sheet->setSelectedCells('A1');
        }
        $this->objspreadsheet->setActiveSheetIndex(0);

        $filename = preg_replace('/\.xlsx?$/i', '', $this->filename);

        $mimetype = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        $filename = $filename . '.xlsx';

        if (core_useragent::is_ie() || core_useragent::is_edge()) {
            $filename = rawurlencode($filename);
        } else {
            $filename = s($filename);
        }

        $tempdir = make_temp_directory('squibitreport');
        $filepath = $tempdir . DIRECTORY_SEPARATOR . $filename;

        $objwriter = IOFactory::createWriter($this->objspreadsheet, $this->type);
        $objwriter->save($filepath);
    }

}