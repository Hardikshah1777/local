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

declare(strict_types=1);

namespace mod_behaviour\local\entities;

use core_reportbuilder\local\filters\date;
use core_reportbuilder\local\filters\duration;
use core_reportbuilder\local\filters\text;
use core_reportbuilder\local\report\column;
use core_reportbuilder\local\report\filter;
use core_reportbuilder\local\entities\base;
use core_user\fields;
use core_reportbuilder\local\helpers\user_profile_fields;
use core_reportbuilder\local\entities\user;
use lang_string;
use stdClass;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/course/lib.php');

/**
 * Behaviour entity class implementation behaviour
 *
 * This entity defines all the behaviour columns and filters to be used in any report.
 *
 * @package     mod_behaviour
 * @copyright   2022 Catalyst IT
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behaviour extends base {

    /**
     * Database tables that this entity uses and their default aliases
     *
     * @return array
     */
    protected function get_default_table_aliases(): array {
        return [
                'user' => 'attu',
                'context' => 'attctx',
                'course' => 'attc',
                'behaviour' => 'att',
                'behaviour_sessions' => 'attsess',
                'behaviour_log' => 'attlog',
                'behaviour_statuses' => 'attstat',
               ];
    }

    /**
     * The default title for this entity
     *
     * @return lang_string
     */
    protected function get_default_entity_title(): lang_string {
        return new lang_string('behaviourreport', 'mod_behaviour');
    }

    /**
     * Initialise the entity, add all user fields and all 'visible' user profile fields
     *
     * @return base
     */
    public function initialise(): base {

        $columns = $this->get_all_columns();
        foreach ($columns as $column) {
            $this->add_column($column);
        }

        $filters = $this->get_all_filters();
        foreach ($filters as $filter) {
            $this->add_filter($filter);
        }

        // TODO: differentiate between filters and conditions (specifically the 'date' type: MDL-72662).
        $conditions = $this->get_all_filters();
        foreach ($conditions as $condition) {
            $this->add_condition($condition);
        }

        return $this;
    }

    /**
     * Returns list of all available columns
     *
     * These are all the columns available to use in any report that uses this entity.
     *
     * @return column[]
     */
    protected function get_all_columns(): array {

        $columns = [];

        $behaviouralias = $this->get_table_alias('behaviour');
        $behavioursessionalias = $this->get_table_alias('behaviour_sessions');
        $behaviourlogalias = $this->get_table_alias('behaviour_log');
        $behaviourstatusalias = $this->get_table_alias('behaviour_statuses');

        $join = $this->behaviourjoin();

        // behaviour name column.
        $columns[] = (new column(
            'name',
            new lang_string('name', 'mod_behaviour'),
            $this->get_entity_name()
        ))
            ->add_join($join)
            ->set_is_sortable(true)
            ->add_field("{$behaviouralias}.name");

        // Now handle session columns.

        // Description column.
        $columns[] = (new column(
            'sessiondescription',
            new lang_string('sessiondescription', 'mod_behaviour'),
            $this->get_entity_name()
        ))
            ->add_join($join)
            ->set_is_sortable(true)
            ->add_field("{$behavioursessionalias}.description");

        // Session date column.
        $columns[] = (new column(
            'sessiondate',
            new lang_string('reportsessiondate', 'mod_behaviour'),
            $this->get_entity_name()
        ))
            ->add_join($join)
            ->set_is_sortable(true)
            ->add_field("{$behavioursessionalias}.sessdate")
            ->add_callback(static function ($value, $row): string {
                return userdate($value);
            });

        // Session duration column.
        $columns[] = (new column(
            'duration',
            new lang_string('reportsessionduration', 'mod_behaviour'),
            $this->get_entity_name()
        ))
            ->add_join($join)
            ->set_is_sortable(true)
            ->add_field("{$behavioursessionalias}.duration");

        // Session last taken column.
        $columns[] = (new column(
            'lasttaken',
            new lang_string('reportsessionlasttaken', 'mod_behaviour'),
            $this->get_entity_name()
        ))
            ->add_join($join)
            ->set_is_sortable(true)
            ->add_field("{$behavioursessionalias}.lasttaken")
            ->add_callback(static function ($value, $row): string {
                return userdate($value);
            });
        // Now add Log columns.

        // Time taken column.
        $columns[] = (new column(
            'timetaken',
            new lang_string('usersessiontaken', 'mod_behaviour'),
            $this->get_entity_name()
        ))
            ->add_join($join)
            ->set_is_sortable(true)
            ->add_field("{$behaviourlogalias}.timetaken")
            ->add_callback(static function ($value, $row): string {
                return userdate($value);
            });

        // Now add Status columns.

        // Status column.
        $columns[] = (new column(
            'status',
            new lang_string('userstatus', 'mod_behaviour'),
            $this->get_entity_name()
        ))
            ->add_join($join)
            ->set_is_sortable(true)
            ->add_field("{$behaviourstatusalias}.acronym");

        // Grade column.
        $columns[] = (new column(
            'grade',
            new lang_string('usersessiongrade', 'mod_behaviour'),
            $this->get_entity_name()
        ))
            ->add_join($join)
            ->set_is_sortable(true)
            ->add_field("{$behaviourstatusalias}.grade");

        // Remarks column.
        $columns[] = (new column(
            'remarks',
            new lang_string('usersessionremarks', 'mod_behaviour'),
            $this->get_entity_name()
        ))
            ->add_join($join)
            ->set_is_sortable(true)
            ->add_field("{$behaviourlogalias}.remarks");

        return $columns;
    }

    /**
     * Return list of all available filters
     *
     * @return filter[]
     */
    protected function get_all_filters(): array {

        $filters = [];
        $behaviouralias = $this->get_table_alias('behaviour');
        $behavioursessionalias = $this->get_table_alias('behaviour_sessions');
        $behaviourlogalias = $this->get_table_alias('behaviour_log');
        $behaviourstatusalias = $this->get_table_alias('behaviour_statuses');

        $join = $this->behaviourjoin();

        // Session name filter.
        $filters[] = (new filter(
            text::class,
            'nameselector',
            new lang_string('name', 'mod_behaviour'),
            $this->get_entity_name(),
            "{$behaviouralias}.name"
        ))
            ->add_join($join);

        // Description filter.
        $filters[] = (new filter(
            text::class,
            'sessiondescription',
            new lang_string('sessiondescription', 'mod_behaviour'),
            $this->get_entity_name(),
            "{$behavioursessionalias}.description"
        ))
            ->add_join($join);

        // Session date filter.
        $filters[] = (new filter(
            date::class,
            'sessiondate',
            new lang_string('reportsessiondate', 'mod_behaviour'),
            $this->get_entity_name(),
            "{$behavioursessionalias}.sessdate"
        ))
            ->add_join($join);

        // Duration filter.
        $filters[] = (new filter(
            duration::class,
            'duration',
            new lang_string('reportsessionduration', 'mod_behaviour'),
            $this->get_entity_name(),
            "{$behavioursessionalias}.duration"
        ))
            ->add_join($join);

        // Last taken filter.
        $filters[] = (new filter(
            date::class,
            'lasttaken',
            new lang_string('reportsessionlasttaken', 'mod_behaviour'),
            $this->get_entity_name(),
            "{$behavioursessionalias}.lasttaken"
        ))
            ->add_join($join);

        // Status filter.
        $filters[] = (new filter(
            text::class,
            'status',
            new lang_string('userstatus', 'mod_behaviour'),
            $this->get_entity_name(),
            "{$behaviourstatusalias}.acronym"
        ))
            ->add_join($join);

        // Time taken filter.
        $filters[] = (new filter(
            date::class,
            'timetaken',
            new lang_string('usersessiontaken', 'mod_behaviour'),
            $this->get_entity_name(),
            "{$behaviourlogalias}.timetaken"
        ))
            ->add_join($join);

        // Remarks filter.
        $filters[] = (new filter(
            text::class,
            'remarks',
            new lang_string('usersessionremarks', 'mod_behaviour'),
            $this->get_entity_name(),
            "{$behaviourlogalias}.remarks"
        ))
            ->add_join($join);

        return $filters;
    }
    /**
     * Helper function to get main join.
     *
     * @return string
     */
    public function behaviourjoin() {
        $behaviouralias = $this->get_table_alias('behaviour');
        $behavioursessionalias = $this->get_table_alias('behaviour_sessions');
        $behaviourlogalias = $this->get_table_alias('behaviour_log');
        $behaviourstatusalias = $this->get_table_alias('behaviour_statuses');

        return "JOIN {behaviour_statuses} {$behaviourstatusalias}
                    ON {$behaviourstatusalias}.id = {$behaviourlogalias}.statusid
                JOIN {behaviour_sessions} {$behavioursessionalias}
                    ON {$behavioursessionalias}.id = {$behaviourlogalias}.sessionid
                JOIN {behaviour} {$behaviouralias}
                    ON {$behaviouralias}.id = {$behavioursessionalias}.behaviourid";
    }
}
