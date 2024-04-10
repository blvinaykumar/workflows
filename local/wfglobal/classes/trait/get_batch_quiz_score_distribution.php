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

trait get_batch_quiz_score_distribution {

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 3.2
     */
    public static function get_batch_quiz_score_distribution_parameters() {
        
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
    public static function get_batch_quiz_score_distribution($courseid, $userids) {
        global $DB;
      

        $PARAMS = self::validate_parameters(self::get_batch_quiz_score_distribution_parameters(),['courseid' => $courseid, 
                                                                                                  'userids' => $userids ]);

        list($insql, $inparams) = $DB->get_in_or_equal(explode(",", $PARAMS['userids']));

        $sql = "SELECT qg.id, q.id quizid, q.name, q.grade grademax, qg.grade
                  FROM {quiz} q
                  JOIN {quiz_grades} qg on qg.quiz = q.id
                 WHERE q.name NOT LIKE ('%Pre%') AND q.course = ? AND qg.userid ".$insql; 

        $records = $DB->get_records_sql($sql, array_merge([$PARAMS['courseid']], $inparams));
                                      
        $response = [];

        foreach($records as $record):

            if(!isset($response[$record->quizid])):
                $response[$record->quizid] = ["quizId" => $record->quizid,
                                              "quizName" => $record->name,  
                                              "<40Count" => 0, 
                                              "40-70Count" => 0, 
                                              "70-80Count" => 0, 
                                              ">80Count" => 0,
                                              "averageScore" => 0,
                                              "totalScore" => 0,
                                              "totaluser" => 0 ];
            endif;


            $response[$record->quizid]['totalScore'] += (float) $record->grade;

            $response[$record->quizid]['averageScore'] = floor(($response[$record->quizid]['totalScore'] / ( ++$response[$record->quizid]['totaluser'])) /  $record->grademax * 100);

            $avg = ( $record->grade / $record->grademax * 100 );
         
            if($avg < 40){
                 $response[$record->quizid]["<40Count"]++;
            } elseif ($avg >= 40 AND $avg < 70) {
                  $response[$record->quizid]["40-70Count"]++; 
            } elseif ($avg >= 70 AND $avg <= 80) {
                $response[$record->quizid]["70-80Count"]++;  
            } elseif($avg > 80){
                $response[$record->quizid][">80Count"]++; 
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
    public static function get_batch_quiz_score_distribution_returns() {

        return  new external_multiple_structure(
            new external_single_structure([
                'quizId' => new external_value(PARAM_INT, 'quizId', VALUE_DEFAULT, 0 ),
                'quizName' => new external_value(PARAM_RAW, 'quizName', VALUE_DEFAULT, 0 ),
                '<40Count' => new external_value(PARAM_INT, '40Count', VALUE_DEFAULT, 0 ),
                '40-70Count' => new external_value(PARAM_INT, '40-70Count', VALUE_DEFAULT, 0 ),
                '70-80Count' => new external_value(PARAM_INT, '70-80Count', VALUE_DEFAULT, 0 ),
                '>80Count' => new external_value(PARAM_INT, '>80Count', VALUE_DEFAULT, 0 ),
                'averageScore' => new external_value(PARAM_RAW, 'averageScore', VALUE_DEFAULT, 0 )] 
            ) 
        );
    }
}