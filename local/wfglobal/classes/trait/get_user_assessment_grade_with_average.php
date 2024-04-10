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

trait get_user_assessment_grade_with_average {

    public static function get_user_assessment_grade_with_average_parameters() {

        return new external_function_parameters(['userid' => new external_value(PARAM_INT, 'User id'),
                                                 'courseid' => new external_value(PARAM_INT, 'course id'),
                                                 'rawname' => new external_value(PARAM_TEXT, 'rawname tag', false,'type:assessment')
                                                ]);
    }

    public static function get_user_assessment_grade_with_average(int $userid, int $courseid, $rawname ) {

           global $DB;

           $PARAMS = self::validate_parameters(self::get_user_assessment_grade_with_average_parameters(), [
                                    'userid'   => $userid,
                                    'courseid' => $courseid,
                                    'rawname'  => $rawname ]);

           $DB->get_record('user', ['suspended' => 0, 'id' => $PARAMS['userid'] ], "id", MUST_EXIST);

           get_course($PARAMS['courseid']); 

           // Query to get the group name
           $sql =  "SELECT g.name 
                      FROM {groups_members} gm 
                INNER JOIN {groups} g ON gm.groupid = g.id 
                     WHERE gm.userid = ? AND g.courseid = ?";
     
           $group = $DB->get_field_sql($sql, [$PARAMS['userid'], $PARAMS['courseid']], MUST_EXIST);
            
           ///  Query to get the average score of the batch.
           $sql  = "SELECT gi.iteminstance, avg(gg.finalgrade) averagegrade 
                      FROM {grade_grades} gg 
                      JOIN {grade_items} gi on gg.itemid = gi.id AND gi.itemtype ='mod' AND gi.itemmodule='scorm'
                      JOIN {scorm} sm on gi.iteminstance = sm.id 
                      JOIN {course_modules} cm on cm.instance = sm.id 
                      JOIN {tag_instance} ti on ti.itemid = cm.id
                      JOIN {tag} tg on ti.tagid = tg.id 
                     WHERE tg.isstandard = 1 
                           AND tg.rawname = :assessment 
                           AND cm.course = :course 
                           AND gg.userid in 
                                (SELECT userid FROM {groups_members} 
                                  WHERE groupid = (SELECT id 
                                                     FROM {groups} 
                                                    WHERE courseid = :courseid AND name = :group))
                 GROUP BY gi.iteminstance";


           $batchrecords  = $DB->get_records_sql($sql,["assessment"  => $PARAMS['rawname'],
                                                       "courseid"    => $PARAMS['courseid'],
                                                       "course"      => $PARAMS['courseid'],
                                                       "group"       => $group]);


           // Query to get user score.
           $sql  = "SELECT gg.itemid, gg.finalgrade, gi.iteminstance from {grade_grades} gg 
                      JOIN {grade_items} gi on gg.itemid = gi.id AND gi.itemtype='mod' AND gi.itemmodule='scorm'
                      JOIN {scorm} sm on gi.iteminstance = sm.id 
                      JOIN {course_modules} cm on cm.instance = sm.id 
                      JOIN {tag_instance} ti on ti.itemid = cm.id
                      JOIN {tag} tg on ti.tagid = tg.id 
                     WHERE tg.isstandard = 1 
                            AND tg.rawname = :assessment 
                            AND cm.course = :courseid 
                            AND gg.userid = :userid ";

           $records  = $DB->get_records_sql($sql,["assessment" => $PARAMS['rawname'],
                                                 "userid"     => $PARAMS['userid'], 
                                                 "courseid"   => $PARAMS['courseid'] ]);

           $data = [];
           
           foreach($records as $record):

                    $d = [];
                    $d['cmid']  = $record->itemid;
                    $d['grade'] = round($record->finalgrade);
                    $d['groupgrade'] = round(($batchrecords[$record->iteminstance]->averagegrade ?? null ));
                    $data[] = $d;

           endforeach;

           return $data;
    }

    public static function get_user_assessment_grade_with_average_returns() {

        return new external_multiple_structure(
                 new external_single_structure(
                         ['cmid' => new external_value(PARAM_RAW, 'cmid of course'),
                          'grade' => new external_value(PARAM_RAW, 'grade of user'),
                          'groupgrade' => new external_value(PARAM_RAW, 'average grade of group')]
                )
        );
    }

}