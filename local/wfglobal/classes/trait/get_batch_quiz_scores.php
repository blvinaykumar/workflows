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

trait get_batch_quiz_scores {

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 3.2
     */
    public static function get_batch_quiz_scores_parameters() {
        
         return new external_function_parameters(['courseid' => new external_value(PARAM_INT, 'course id'),
                                                  'userids' => new external_value(PARAM_SEQUENCE, 'ids: comma separated course ids')]);
    }

    /**
     *  
     * @param  string $courseid field course id
     * @param  string $userids ids: comma separated course ids
     * @return array list of courses and warnings
     * @throws  invalid_parameter_exception
     * @since Moodle 3.2
     */
    public static function get_batch_quiz_scores($courseid, $userids) {
        global $DB, $CFG;
      

        $PARAMS = self::validate_parameters(self::get_batch_quiz_scores_parameters(),['courseid' => $courseid, 
                                                                                       'userids' => $userids ]);

        $sql = "SELECT
                
                DISTINCT (qa.userid),
                AVG((qg.grade/q.grade)*100) OVER (PARTITION BY qa.userid)

                 FROM {quiz} q
                 JOIN {quiz_attempts} qa  ON qa.quiz = q.id
                 JOIN {quiz_grades} qg ON qg.quiz = qa.quiz AND qg.userid = qa.userid
                WHERE q.course = :courseid AND
                      qa.userid IN (".$PARAMS['userids'].") AND q.name NOT LIKE ('%Pre%')";

        $records =  $DB->get_records_sql($sql, ['courseid' => $PARAMS['courseid']]);
     
        $response = [];

        foreach($records as $record):

            switch ( $record->avg ) :
                      
                      case $record->avg <= 40:
                             $response["<40Count"]++; 
                             break;
                      case $record->avg > 40 AND $record->avg <= 60:
                             $response["40-60Count"]++; 
                             break;
                      case $record->avg > 60 AND $record->avg <= 75:
                             $response["60-75Count"]++; 
                             break;
                      case $record->avg >75 AND $record->avg <= 80 :
                             $response["75-80Count"]++; 
                             break;
                      case $record->avg > 80:
                             $response[">80Count"]++;
                             break;
                      
            endswitch;

        endforeach;

        return $response;
        
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 3.2
     */
    public static function get_batch_quiz_scores_returns() {
        return new external_single_structure([
             '<40Count' => new external_value(PARAM_INT, '40Count', VALUE_DEFAULT, 0 ),
             '40-60Count' => new external_value(PARAM_INT, '40Count', VALUE_DEFAULT, 0 ),
             '60-75Count' => new external_value(PARAM_INT, '40Count', VALUE_DEFAULT, 0 ),
             '75-80Count' => new external_value(PARAM_INT, '40Count', VALUE_DEFAULT, 0 ),
             '>80Count' => new external_value(PARAM_INT, '40Count', VALUE_DEFAULT, 0 ) ] );
    }

}
