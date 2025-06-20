<?php


namespace local_coact\external;


use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;
use local_coact_external;

class available_cohort extends local_coact_external
{
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([]);
    }

    public static function execute() : array {
        global $DB;
        $params = self::validate_parameters(self::execute_parameters(), []);

        $cohorts = $DB->get_records('cohort', ['visible' => 1]);

        $cohort = [];
        foreach ($cohorts as $cohortdata) {
            $cohort[] = [
                'id' => $cohortdata->id,
                'name' => $cohortdata->name,
            ];
        }
        return $cohort;
    }

    public static function execute_returns() : external_multiple_structure {
        return new external_multiple_structure(
            new external_single_structure(
                [
                    'id' => new external_value(PARAM_RAW, 'The id of the cohort.'),
                    'name' => new external_value(PARAM_RAW, 'The name of the cohort.'),
                ]
            ),
            'List of cohorts.'
        );
    }
}