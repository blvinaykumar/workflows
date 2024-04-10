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

require_once($CFG->libdir . "/externallib.php");

foreach (glob($CFG->dirroot . "/local/wfglobal/classes/trait/*.php") as $filename) {
    require_once $filename;
}

class local_wfglobal_external extends external_api {

    use get_user_assessment_grade_with_average;
    use get_courses_by_field;
    use get_health_check;
    use get_course_section_structure;
    use get_user_course_activity_completion;
    use get_batch_quiz_scores;
    use get_batch_quiz_score_distribution;
    use get_student_quiz_reports;
    use get_course_quizzes;
    use get_course_batch_quizzes_report;
    use get_user_activity_completion_report_course;
    use get_user_activity_completion_status;
    use get_clear_cookies;


}