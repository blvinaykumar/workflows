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

trait get_course_quizzes {

    public static function get_course_quizzes_parameters() {

        return new external_function_parameters(['courseid' => new external_value(PARAM_INT, 'course id')]);
    }

    public static function get_course_quizzes(int $courseid) {
        global $DB;

        $PARAMS = self::validate_parameters(self::get_course_quizzes_parameters(), ['courseid' => $courseid]);

        $query = 'SELECT id, name FROM mdl_quiz WHERE course =:courseid AND
               name NOT LIKE :name1 AND  name NOT LIKE :name2  ORDER BY id';

        $params = array('courseid' => $PARAMS['courseid'], 'name1' => '%Pre%', 'name2' => '%Post%');

        $results = $DB->get_records_sql($query, $params);
        $response = array();
        foreach ($results as $result) {

            $response[] = ['id' => $result->id, 'name' => $result->name];
        }

        return $response;
    }

    public static function get_course_quizzes_returns() {


        return new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'Quiz ID', true),
                    'name' => new external_value(PARAM_TEXT, 'Quiz Name', true),
        ]));
    }

}
