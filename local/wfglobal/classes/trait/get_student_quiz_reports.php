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
 * @author     Vinay <vinay@ballisticlearning.com>
 * @package    local_wfglobal
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

trait get_student_quiz_reports {

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 3.2
     */
    public static function get_student_quiz_reports_parameters() {
       return new external_function_parameters(['courseid' => new external_value(PARAM_INT, 'course id'),
                                                'userid' => new external_value(PARAM_INT, 'user id')]);
    }


    /**
     * Get courses matching a specific field (id/s, shortname, idnumber, category)
     *
     * @param  string $field field name to search, or empty for all courses
     * @param  string $value value to search
     * @return array list of courses and warnings
     * @throws  invalid_parameter_exception
     * @since Moodle 3.2
     */
    public static function get_student_quiz_reports($courseid, $userid) {
       
        global $DB, $CFG;

        $PARAMS = self::validate_parameters(self::get_student_quiz_reports_parameters(),['courseid' => $courseid, 
                                                                                         'userid' => $userid ] );

        $sql = "SELECT
                      qa1.quiz as quiz_id, q.name as quiz_name, 
                      qa1.state as state, 
                      qa1.timestart as attempt_start_date, qa1.timefinish as attempt_finish_date,
                      q.grade::int as max_score,
                      qg.grade::int AS user_score,
                      ROUND((qg.grade/q.grade)*100::numeric,2) AS percentage_score
                FROM mdl_quiz_attempts qa1
                JOIN mdl_quiz q on qa1.quiz=q.id
                JOIN mdl_quiz_grades qg ON qg.quiz = qa1.quiz AND qg.userid = qa1.userid
               WHERE qa1.sumgrades = ( SELECT MAX( qa2.sumgrades )
                                       FROM mdl_quiz_attempts qa2
                                       WHERE qa2.userid=qa1.userid AND  qa2.quiz=qa1.quiz)
                 AND qa1.userid = :userid AND q.course = :courseid AND q.name NOT LIKE ('%Pre%')";

       $data = $DB->get_records_sql($sql, ['courseid' => $PARAMS['courseid'], 'userid' => $PARAMS['userid']]);

       return $data;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 3.2
     */
    public static function get_student_quiz_reports_returns() {
        // Course structure, including not only public viewable fields.
        return new external_multiple_structure(
            new external_single_structure(
                    ['quiz_id' => new external_value(PARAM_RAW, 'cmid of course'),
                     'quiz_name' => new external_value(PARAM_RAW, 'grade of user'),
                     'state' => new external_value(PARAM_RAW, 'grade of user'),
                     'attempt_start_date' => new external_value(PARAM_RAW, 'grade of user'),
                     'attempt_finish_date' => new external_value(PARAM_RAW, 'grade of user'),
                     'max_score' => new external_value(PARAM_RAW, 'grade of user'),
                     'user_score' => new external_value(PARAM_RAW, 'grade of user'),
                     'percentage_score' => new external_value(PARAM_RAW, 'grade of user'),
                   //  'quiz_attempts' => new external_value(PARAM_RAW, 'grade of user')
                    ]
            )
        );
    }

    
}