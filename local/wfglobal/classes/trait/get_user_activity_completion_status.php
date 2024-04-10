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

trait get_user_activity_completion_status {

    public static function get_user_activity_completion_status_parameters() {

        return new external_function_parameters(['userid' => new external_value(PARAM_INT, 'User id'),
            'cmid' => new external_value(PARAM_INT, 'cmid')]);
    }

    public static function get_user_activity_completion_status(int $userid, int $cmid) {

        $PARAMS = self::validate_parameters(self::get_user_activity_completion_status_parameters(), ['userid' => $userid,
                    'cmid' => $cmid]);
        global $DB;
        $response = [];        
        $cm = $DB->get_record('course_modules',['id'=> $PARAMS['cmid']]);
      
        if($cm && $cm->completion == 0){
            return $response;
        }
        $response['status'] = 'notStarted';
        
        $completion = $DB->get_record("course_modules_completion", ['userid' => $PARAMS['userid'],
            'coursemoduleid' => $PARAMS['cmid']], 'completionstate, timemodified');
               
      

        if ($completion) {
            switch ($completion->completionstate) {
                case 0:
                    $response['status'] = 'inProgress';
                    break;
                case 1:
                    $response['endTime'] = $completion->timemodified;
                    $response['status'] = 'completed';
                    break;
                case 2:
                    $response['endTime'] = $completion->timemodified;
                    $response['status'] = 'completedPass';
                    break;
                case 3:
                    $response['endTime'] = $completion->timemodified;
                    $response['status'] = 'completedFail';
                    break;
            }
        }

        return $response;
    }

    public static function get_user_activity_completion_status_returns() {

        return new external_single_structure(['status' => new external_value(PARAM_TEXT, 'status', null),
            'endTime' => new external_value(PARAM_INT, 'endTime', null)]);
    }

}
