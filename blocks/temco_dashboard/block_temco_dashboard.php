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
 *
 * @package   block_temco_dashboard
 * @copyright 1999 onwards Martin Dougiamas (http://dougiamas.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_temco_dashboard\form\temco_filter;
use block_temco_dashboard\output\filterform;
use block_temco_dashboard\table\temco_user;
use block_temco_dashboard\table\temco_user_filterset;
use core_table\local\filter\filter;

class block_temco_dashboard extends block_base {

    function init() {
        $this->title = get_string('pluginname', 'block_temco_dashboard');
    }

    function has_config() {
        return true;
    }

    function get_content() {
        global $PAGE, $OUTPUT;
        $cohortids = optional_param_array('cohort', [], PARAM_INT);
        $idnumber = optional_param_array('idnumber', [], PARAM_TEXT);
        $fullname = optional_param_array('fullname', [], PARAM_TEXT);

        $contextsystem = context_system::instance();

        if (!has_capability('block/temco_dashboard:view', $contextsystem)) {
            return null;
        }

        if ($this->content !== NULL) {
            return $this->content;
        }

        $table = new temco_user('temcouser');
        $table->define_baseurl($PAGE->url);

        $filters = (new temco_user_filterset)
                ->add_filter_from_params('cohortid', filter::JOINTYPE_DEFAULT, $cohortids)
                ->add_filter_from_params('idnumber', filter::JOINTYPE_DEFAULT, $idnumber)
                ->add_filter_from_params('fullname', filter::JOINTYPE_DEFAULT, $fullname);
        $table->set_filterset($filters);

        $this->content = new stdClass;
        $this->content->text = $OUTPUT->render(new filterform($table)) . $table->render($table::perpage, false);

        return $this->content;
    }

    public function get_content_for_output($output) {
        if (!has_capability('block/temco_dashboard:view', context_system::instance())) {
            return null;
        }
        return parent::get_content_for_output($output);
    }
}