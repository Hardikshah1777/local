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
 * Special admin setting for theme_simplest that validates date.
 *
 * @package   theme_simplest
 * @copyright 2024, LMSwithAI <contact@lmswithai.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


/**
 * Special admin setting for theme_simplest that validates date.
 *
 * @package   theme_simplest
 * @copyright 2024, LMSwithAI <contact@lmswithai.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class theme_simplest_admin_setting_special_date_configtext extends admin_setting_configtext {

    /**
     * We need to validate date format.
     *
     * @param string $data Form data.
     * @return string Empty when no errors.
     */
    public function validate($data) {

        $format = trim($data);
        if (!preg_match('/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/', $format)) {
            return get_string('errordate', 'theme_simplest');
        }

        $date = explode("-", $format);
        $monthdays = cal_days_in_month(CAL_GREGORIAN, $date[1], $date[0]);
        if ($date[2] > $monthdays) {
            return get_string('errormonth', 'theme_simplest', ['year' => $date[0], 'days' => $monthdays]);
        }

        return parent::validate($data);
    }
}
