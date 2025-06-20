<?php

namespace report_temco_completion;

class util {

    const COMPONENT = 'report_temco_completion';

    public static function attach_excel($table, $filepath) {

        $columns = array_keys($table->column_class);
        $excel = new temco_workbook($filepath);
        $workbook = $excel->add_worksheet('report');

        $rowindex = 0;
        foreach ($table->headers as $i => $header) {
            $workbook->write_string($rowindex, $i, $header);
        }

        $rowindex++;
        foreach ($table->rawdata as $key => $row) {
            foreach ($columns as $colkey => $colvalue) {
                if (isset($row->$colvalue)) {
                    $workbook->write_string($rowindex, $colkey, $row->$colvalue ?? '-');
                }
            }
            $rowindex++;
        }
        $excel->close();
    }

}