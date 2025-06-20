<?php


namespace report_temco_completion;

require_once $CFG->libdir . '/excellib.class.php';

use core_useragent;
use MoodleExcelWorkbook;
use PhpOffice\PhpSpreadsheet\IOFactory;

class temco_workbook extends MoodleExcelWorkbook {

    public function close() {
        foreach ($this->objspreadsheet->getAllSheets() as $sheet) {
            $sheet->setSelectedCells('A1');
        }
        $this->objspreadsheet->setActiveSheetIndex(0);

        $filename = preg_replace('/\.xlsx?$/i', '', $this->filename);
        $filename = $filename . '.xlsx';

        if (core_useragent::is_ie() || core_useragent::is_edge()) {
            $filename = rawurlencode($filename);
        } else {
            $filename = s($filename);
        }

        $objwriter = IOFactory::createWriter($this->objspreadsheet, $this->type);
        $objwriter->save($filename);
    }
}