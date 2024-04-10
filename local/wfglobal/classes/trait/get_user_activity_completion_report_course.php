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

trait get_user_activity_completion_report_course {

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 3.2
     */
    public static function get_user_activity_completion_report_course_parameters() {

        return new external_function_parameters(['courseid' => new external_value(PARAM_INT, 'course id'),
            'userid' => new external_value(PARAM_SEQUENCE, 'ids: comma separated user ids')]);
    }

    /**
     *  
     * @param  string $courseid field course id
     * @param  string $userid ids: comma separated user ids
     * @return array list of courses and warnings
     * @throws  invalid_parameter_exception
     * @since Moodle 3.2
     */
    public static function get_user_activity_completion_report_course($courseid, $userid) {
        global $DB, $CFG;

        $PARAMS = self::validate_parameters(self::get_user_activity_completion_report_course_parameters(), ['courseid' => $courseid,
                    'userid' => $userid]);

        $course = get_course($PARAMS['courseid']);

        $sql = "SELECT cm.id,
                       cm.instance,
                       cm.completion,
                        m.name
                 FROM {course_modules} cm
            LEFT JOIN {modules} m ON m.id = cm.module
                WHERE deletioninprogress = 0
                  AND  cm.visible = 1 AND cm.course = ?";
        $mods = $DB->get_records_sql($sql, [$PARAMS['courseid']]);

        $sql = "SELECT moduleinstance as id, moduleinstance
                  FROM {course_completion_criteria}    
                 WHERE criteriatype = 4
                   AND course = ?";
        $criteria = $DB->get_records_sql_menu($sql, [$PARAMS['courseid']]);

        list($insql, $inparams) = $DB->get_in_or_equal(array_column($mods, 'id'));
        $sql = "SELECT coursemoduleid, completionstate, timemodified
                  FROM {course_modules_completion}
                 WHERE userid = ?
                   AND coursemoduleid " . $insql;
        $usercompletion = $DB->get_records_sql($sql, array_merge([$PARAMS['userid']], $inparams));

        list($insql, $inparams) = $DB->get_in_or_equal(array_column($mods, 'instance'));

        $sql = "SELECT  gi.iteminstance, gg.rawgrademax, gg.finalgrade, gg.timecreated,  gg.timemodified ,gi.itemmodule
                   FROM {grade_grades} gg
                   JOIN {grade_items} gi ON gi.id = gg.itemid
                  WHERE itemtype = 'mod'
                    AND gg.userid = ?
                    AND gi.courseid = ?
                    AND gi.iteminstance " . $insql;
        $grades = $DB->get_records_sql($sql, array_merge([$PARAMS['userid'], $PARAMS['courseid']], $inparams));

        $response = [];

        foreach ($mods as $mod):

            $d = [];
            $d['id'] = $mod->id;
            $d['requiredForCompletion'] = isset($criteria[$mod->id]) ? 1 : 0;

            if ($mod->completion > 0) {

                $d['status'] = 'notStarted';

                if (isset($usercompletion[$mod->id]->completionstate)) {

                    switch ($usercompletion[$mod->id]->completionstate) {

                        case 0:
                            $d['status'] = 'inProgress';
                            break;
                        case 1:
                            $d['endTime'] = $usercompletion[$mod->id]->timemodified;
                            $d['status'] = 'completed';
                            break;
                        case 2:
                            $d['endTime'] = $usercompletion[$mod->id]->timemodified;
                            $d['status'] = 'completedPass';
                            break;
                        case 3:
                            $d['endTime'] = $usercompletion[$mod->id]->timemodified;
                            $d['status'] = 'completedFail';
                            break;
                    }
                }
            }



            switch ($mod->name) {

                case 'quiz':

                    $tem = self::get_blquiz($mod, $PARAMS);
                    $d['startTime'] = $tem->timestart ?? null;
                    $d['endTime'] = $tem->timefinish ?? null;

                    break;

                case 'scorm':

                    $tem2 = self::get_blscorm($mod, $PARAMS);
                    if (!empty($tem2->timestart)) {
                        $d['startTime'] = $tem2->timestart;
                    }
                    if (!empty($tem2->timefinish)) {
                        $d['endTime'] = $tem2->timefinish;
                    }
                    break;
            }

            if (isset($grades[$mod->instance]) && $grades[$mod->instance]->itemmodule == $mod->name):

                $d['userScore'] = $grades[$mod->instance]->finalgrade;
                $d['totalScore'] = $grades[$mod->instance]->rawgrademax;

            endif;

            $response[] = $d;

        endforeach;
        return $response;
    }

    protected static function get_blquiz($mod, $PARAMS) {

        global $DB;

        $response = [];

        $where = "WHERE userid = :userid  AND quiz = :quiz AND state = 'finished' ORDER BY sumgrades DESC LIMIT 1";

        $sql = "SELECT timestart, timefinish FROM {quiz_attempts} $where";
        $response = $DB->get_record_sql($sql, ['userid' => $PARAMS['userid'], 'quiz' => $mod->instance]);

        return $response;
    }

    protected static function get_blscorm($mod, $PARAMS) {

        global $DB;

        $response = new stdClass();
        $response->timestart = null;
        $response->timefinish = null;

        $records = $DB->get_records('scorm_scoes_track', ['userid' => $PARAMS['userid'], "scormid" => $mod->instance]);

        $tem = [];

        foreach ($records as $record):

            if ($record->element == 'cmi.core.score.raw') {

                $record->value = (int) $record->value;

                if (!isset($tem['attempt'])) {

                    $tem = ['attempt' => $record->attempt, 'score' => $record->value];
                }


                if ($record->value > $tem['score']) {

                    $tem = ['attempt' => $record->attempt, 'score' => $record->value];
                }
            }

        endforeach;

        if (empty($tem)):

            $sql = "SELECT MIN(attempt)
              FROM {scorm_scoes_track}
             WHERE userid = ? AND scormid = ?";

            $lastattempt = $DB->get_field_sql($sql, array($PARAMS['userid'], $mod->instance));
            if (!empty($lastattempt)) {
                $tem = ['attempt' => $lastattempt];
            }

        endif;
        $data = [];
        foreach ($records as $record):

            if ($tem['attempt'] == $record->attempt) {
                $data[$record->element] = $record->value;
            }

        endforeach;

        if ($data['x.start.time']) {

            $response->timestart = $data['x.start.time'];
        }

        if ($data['cmi.core.total_time']) {

            $response->timefinish = $data['x.start.time'] + strtotime($data['cmi.core.total_time']) - strtotime('TODAY');
        }

        return $response;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 3.2
     */
    public static function get_user_activity_completion_report_course_returns() {

        return new external_multiple_structure(
                new external_single_structure(
                        ['id' => new external_value(PARAM_INT, 'email'),
                    'requiredForCompletion' => new external_value(PARAM_RAW, 'requiredForCompletion', false),
                    'status' => new external_value(PARAM_RAW, 'status', false),
                    'startTime' => new external_value(PARAM_INT, 'startTime', false),
                    'endTime' => new external_value(PARAM_INT, 'endTime', false),
                    'userScore' => new external_value(PARAM_RAW, 'userScore', false),
                    'totalScore' => new external_value(PARAM_RAW, 'totalScore', false)])
        );
    }

}
