<?php

namespace report_behaviour;

use MoodleExcelWorkbook;
use stdClass;

require_once($CFG->libdir.'/excellib.class.php');

class util {

    const COMPONENT = 'report_behaviour';

    const PERPAGE = 30;

    public static function export_excel($table, $filename) {
        $excel = new MoodleExcelWorkbook($filename);
        $excelwriter = $excel->add_worksheet();

        $boldstyle = ['bold' => 1];
        $alignformt = ['align' => 'center'];
        $valignformt = ['v_align' => 'center'];
        $borderstyle = ['border' => 1];
        $wraptext = ['text_wrap' => true];
        $basicformat = array_merge($boldstyle, $alignformt, $valignformt,$borderstyle, $wraptext);
        $headstyle1 = array_merge($basicformat, ['color' => 'white', 'bg_color' => '#3c7f21']);
        $headstyle2 = array_merge($basicformat, ['color' => 'black', 'bg_color' => '#93cb82']);
        $headstyle3 = array_merge($basicformat, ['color' => 'black', 'bg_color' => '#ffffcc']);

        $row = $firstrow = 1;
        $col = 0;
        $sesscolumns = [];

        $studentheader = strtoupper(get_string('students', util::COMPONENT));
        if (!empty($table->isweekend)) {
            if (!empty($table->sessioncolumns)) {
                $nextcol = count($table->defaultcolumns) - 1 ?? 2;
                $excelwriter->merge_cells($row, $col, $row, $nextcol);
                $excelwriter->write_string($row, $col, $studentheader, self::excel_set_styles($excel, $headstyle1));
                $table->totalcol['default'] = $nextcol;
                $nextcol++;

                foreach ($table->sessioncolumns as $sesscolkey => $sesscolvalue) {
                    $date = userdate($sesscolkey, $table->timeformat);
                    $colspan = count($sesscolvalue) - 1;
                    $sesscolumns[] = count($sesscolvalue);
                    $lastcol = $nextcol + $colspan;
                    if (!empty($colspan) && $lastcol > $nextcol) {
                        $excelwriter->merge_cells($row, $nextcol, $row, $lastcol);
                        $excelwriter->write_string($row, $nextcol, $date, self::excel_set_styles($excel, $headstyle2));
                        $nextcol+= $colspan;
                    } else {
                        $excelwriter->write_string($row, $nextcol, $date, self::excel_set_styles($excel, $headstyle2));
                    }
                    $nextcol++;
                }
                $table->totalcol['session'] = $nextcol;

                $nextrow = $row+1;
                foreach ($table->statuscolumns as $statuscolkey => $statuscolvalue) {
                    $excelwriter->merge_cells($row, $nextcol, $nextrow, $nextcol);
                    $excelwriter->write_string($row, $nextcol, $statuscolvalue, self::excel_set_styles($excel, $headstyle3));
                    $nextcol++;
                }
                $table->totalcol['status'] = $nextcol;

                $excelwriter->merge_cells($row, $nextcol, $nextrow, $nextcol);
                $excelwriter->write_string($row, $nextcol, $table->headers[array_key_last($table->headers)], self::excel_set_styles($excel, $headstyle2));
                $table->totalcol['lastrow'] = $nextcol;
            }
        } else {
            if (!empty($table->sessioncolumns)) {
                $nextcol = count($table->defaultcolumns) - 1 ?? 2;
                $excelwriter->merge_cells($row, $col, $row, $nextcol);
                $excelwriter->write_string($row, $col, $studentheader, self::excel_set_styles($excel, $headstyle1));
                $table->totalcol['default'] = $nextcol;
                $nextcol++;

                foreach ($table->sessioncolumns as $sesscolkey => $sesscolvalue) {
                    $date = userdate($sesscolkey, $table->timeformat);
                    $colspan = count($sesscolvalue) - 1;
                    $sesscolumns[] = count($sesscolvalue);
                    $lastcol = $nextcol + $colspan;
                    $excelwriter->merge_cells($row, $nextcol, $row, $lastcol);
                    $excelwriter->write_string($row, $nextcol, $date, self::excel_set_styles($excel, $headstyle2));
                    $nextcol += $colspan;
                }
                $table->totalcol['session'] = $nextcol;

                $nextcol++;
                $nextrow = $row+1;
                foreach ($table->statuscolumns as $statuscolkey => $statuscolvalue) {
                    $excelwriter->merge_cells($row, $nextcol, $nextrow, $nextcol);
                    $excelwriter->write_string($row, $nextcol, $statuscolvalue, self::excel_set_styles($excel, $headstyle3));
                    $nextcol++;
                }
                $table->totalcol['status'] = $nextcol;

                $excelwriter->merge_cells($row, $nextcol, $nextrow, $nextcol);
                $excelwriter->write_string($row, $nextcol, $table->headers[array_key_last($table->headers)], self::excel_set_styles($excel, $headstyle2));

                $table->totalcol['lastrow'] = $nextcol;
            }
        }

        $row++;
        foreach (array_values($table->headers) as $value) {
            $format = !in_array($value, $table->defaultcolumns) ? self::excel_set_styles($excel, $headstyle3) : self::excel_set_styles($excel, $headstyle1);
            $excelwriter->write_string($row, $col, $value, $format);
            $col++;
        }

        $row++;
        foreach ($table->rawdata as $rowkey => $rowvalue) {
            $col = 0;
            foreach (array_keys($table->columns) as $key => $val) {
                $format = !array_key_exists($val, $table->defaultcolumns) ? self::excel_set_styles($excel, $alignformt) : self::excel_set_styles($excel, $boldstyle);
                $excelwriter->write_string($row, $col, $rowvalue->$val, $format);
                $col++;
            }
            $row++;
        }

        $fontsize = ['size' => 8];
        $inclue = !$table->isweekend ? 1 : 0;
        $table->totalcol['default'] += 1;
        $excelwriter->set_column(0, $table->totalcol['default'], 8, $fontsize);
        $excelwriter->set_column($table->totalcol['default'], $table->totalcol['session'], 5, $fontsize);
        $excelwriter->set_column($table->totalcol['session'] + $inclue, $table->totalcol['status'], 2, $fontsize);
        $excelwriter->set_column($table->totalcol['status'], $table->totalcol['lastrow'], 8, $fontsize);

        if (!empty($sesscolumns) && in_array($firstrow, $sesscolumns)) {
            $excelwriter->set_row($firstrow , 30);
        }
        $excel->close();
        exit();
    }

    public static function excel_set_styles($excel, $styles) {
        return $excel->add_format($styles);
    }

}