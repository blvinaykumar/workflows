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

trait get_user_course_activity_completion {

    public static function get_user_course_activity_completion_parameters() {

        return new external_function_parameters(['userid' => new external_value(PARAM_INT, 'User id'),
            'courseid' => new external_value(PARAM_INT, 'course id')]);
    }

    public static function get_user_course_activity_completion(int $userid, int $courseid) {

        $PARAMS = self::validate_parameters(self::get_user_course_activity_completion_parameters(), [
                    'userid' => $userid,
                    'courseid' => $courseid]);

        global $DB;

        $course = get_course($PARAMS['courseid']);
        $user = $DB->get_record('user', ['suspended' => 0, 'deleted' => 0, 'id' => $PARAMS['userid']], "id", MUST_EXIST);

        $completioncriteria = $DB->get_records_menu('course_completion_criteria', ['course' => $PARAMS['courseid'], "criteriatype" => 4], '', 'id ,moduleinstance');

        if (!$completioncriteria) {

            return ["percentage" => null];
        }

        // Get the number of modules that have been completed.

        list($insql, $inparams) = $DB->get_in_or_equal($completioncriteria);

        $sql = "SELECT COUNT(id)
                  FROM {course_modules_completion}
                 WHERE userid = ?
                   AND completionstate > 0
                   AND coursemoduleid $insql ";


        $completed = $DB->count_records_sql($sql,  array_merge([$PARAMS['userid']], $inparams) );
        return ["percentage" => number_format((($completed / count($completioncriteria) ) * 100), 2, '.', '') ];
       
    }

    public static function get_user_course_activity_completion_returns() {

        return new external_single_structure(['percentage' => new external_value(PARAM_RAW, 'percentage of user activity completion')]);
    }

}
