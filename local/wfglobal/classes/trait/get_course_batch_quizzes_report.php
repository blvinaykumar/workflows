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

trait get_course_batch_quizzes_report {

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 3.2
     */
    public static function get_course_batch_quizzes_report_parameters() {
        
         return new external_function_parameters(['courseid' => new external_value(PARAM_INT, 'course id'),
                                                  'userids' => new external_value(PARAM_SEQUENCE, 'ids: comma separated user ids')]);
    }

    /**
     *  
     * @param  string $courseid field course id
     * @param  string $userids ids: comma separated user ids
     * @return array list of courses and warnings
     * @throws  invalid_parameter_exception
     * @since Moodle 3.2
     */
    public static function get_course_batch_quizzes_report($courseid, $userids) {
        global $DB, $CFG;
      

        $PARAMS = self::validate_parameters(self::get_course_batch_quizzes_report_parameters(), ['courseid' => $courseid, 
                                                                                       'userids' => $userids ]);

        list($insql, $inparams) = $DB->get_in_or_equal(explode(",", $PARAMS['userids']));

        $sql = "SELECT  qa.id,
                        u.id userid,
                        u.email,
                        CONCAT(u.firstname, ' ', u.lastname ) username,
                        q.name quizname,
                        q.id quizid,
                        q.course courseid,
                        qa.state,
                        qa.timestart,
                        qa.timefinish,
                        q.sumgrades max_score,
                        qa.sumgrades user_score                        
                    FROM {quiz} q
               LEFT JOIN {quiz_attempts} qa ON qa.quiz = q.id
               LEFT JOIN {user} u ON u.id = qa.userid
                   WHERE q.course = ? AND q.name NOT LIKE ('%Pre%') AND u.id ".$insql;

        $records = $DB->get_records_sql($sql, array_merge([$PARAMS['courseid']], $inparams));

        $response = [];

        foreach($records as $record):
 
            $record->percentage_score = ( $record->user_score / $record->max_score * 100 );

            if(!isset($response["$record->quizid,$record->userid"])){
                $response["$record->quizid,$record->userid"] = $record;
            }

            if($response["$record->quizid,$record->userid"]->user_score < $record->user_score){
                $response["$record->quizid,$record->userid"] = $record;
            }

        endforeach;

        return $response;
    }

    
    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 3.2
     */
    public static function get_course_batch_quizzes_report_returns() {

        return  new external_multiple_structure(
            new external_single_structure([
                'email' => new external_value(PARAM_RAW, 'email' ),
                'username' => new external_value(PARAM_RAW, 'username' ),    
                'quizid' => new external_value(PARAM_INT, 'quizid'), 
                'quizname' => new external_value(PARAM_RAW, 'quizname'),  
                'courseid' => new external_value(PARAM_INT, 'courseid'),
                'state' => new external_value(PARAM_RAW, 'state'), 
                'timestart' => new external_value(PARAM_INT, 'timestart'),
                'timefinish' => new external_value(PARAM_INT, 'timefinish'),
                'max_score' => new external_value(PARAM_RAW, 'max_score'),
                'user_score' => new external_value(PARAM_RAW, 'user_score'),
                'percentage_score' => new external_value(PARAM_RAW, 'percentage_score') 
                ] 
            ) 
        );
    }
}