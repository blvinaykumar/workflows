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
defined('MOODLE_INTERNAL') || die;

require_once $CFG->dirroot . '/local/wfglobal/lib.php';

$functions = [
    'wfglobal_get_user_assessment_grade_with_average' => [
        'classname' => 'local_wfglobal_external',
        'methodname' => 'get_user_assessment_grade_with_average',
        'description' => 'Get user assessment grade with average',
        'type' => 'read',
        'services' => [WAGHWANIGLOBLE_ADMIN_SERVICE, WAGHWANIGLOBLE_USER_SERVICE]
    ],
    'wfglobal_get_courses_by_field' => [
        'classname' => 'local_wfglobal_external',
        'methodname' => 'get_courses_by_field',
        'description' => 'Get courses matching a specific field (id/s, shortname, idnumber, category)',
        'type' => 'read',
        'services' => [WAGHWANIGLOBLE_ADMIN_SERVICE, WAGHWANIGLOBLE_USER_SERVICE]
    ],
    'wfglobal_get_health_check' => [
        'classname' => 'local_wfglobal_external',
        'methodname' => 'get_health_check',
        'description' => 'health check',
        'type' => 'read',
        'services' => [WAGHWANIGLOBLE_ADMIN_SERVICE, WAGHWANIGLOBLE_USER_SERVICE],
        'ajax' => true,
        'loginrequired' => false
    ],
     'wfglobal_get_course_section_structure' => [
        'classname'     => 'local_wfglobal_external',
        'methodname'    => 'get_course_section_structure',
        'description'   => 'Section description followed by its seb-section description',
        'type'          => 'read',
        'services'      => [WAGHWANIGLOBLE_ADMIN_SERVICE, WAGHWANIGLOBLE_USER_SERVICE]
    ],
    'wfglobal_get_user_course_activity_completion' => [
        'classname' => 'local_wfglobal_external',
        'methodname' => 'get_user_course_activity_completion',
        'description' => 'Get courses activity completion percrntage',
        'type' => 'read',
        'services' => [WAGHWANIGLOBLE_ADMIN_SERVICE, WAGHWANIGLOBLE_USER_SERVICE]
    ],
    'wfglobal_get_batch_quiz_scores' => [
        'classname' => 'local_wfglobal_external',
        'methodname' => 'get_batch_quiz_scores',
        'description' => 'Batch Quiz scores',
        'type' => 'read',
        'services' => [WAGHWANIGLOBLE_ADMIN_SERVICE, WAGHWANIGLOBLE_USER_SERVICE]
    ],
    'wfglobal_get_batch_quiz_score_distribution' => [
        'classname' => 'local_wfglobal_external',
        'methodname' => 'get_batch_quiz_score_distribution',
        'description' => 'Batch Quiz score distribution',
        'type' => 'read',
        'services' => [WAGHWANIGLOBLE_ADMIN_SERVICE, WAGHWANIGLOBLE_USER_SERVICE]
    ],
    'wfglobal_get_student_quiz_reports' => [
        'classname' => 'local_wfglobal_external',
        'methodname' => 'get_student_quiz_reports',
        'description' => 'Student quiz reports',
        'type' => 'read',
        'services' => [WAGHWANIGLOBLE_ADMIN_SERVICE, WAGHWANIGLOBLE_USER_SERVICE]
    ],
    'wfglobal_get_course_quizzes' => [
        'classname' => 'local_wfglobal_external',
        'methodname' => 'get_course_quizzes',
        'description' => 'Get all course  quizzes',
        'type' => 'read',
        'services' => [WAGHWANIGLOBLE_ADMIN_SERVICE, WAGHWANIGLOBLE_USER_SERVICE]
    ],
    'wfglobal_get_course_batch_quizzes_report' => [
        'classname' => 'local_wfglobal_external',
        'methodname' => 'get_course_batch_quizzes_report',
        'description' => 'Get all course  quizzes',
        'type' => 'read',
        'services' => [WAGHWANIGLOBLE_ADMIN_SERVICE, WAGHWANIGLOBLE_USER_SERVICE]
    ],
     'wfglobal_get_user_activity_completion_report_course' => [
        'classname'     => 'local_wfglobal_external',
        'methodname'    => 'get_user_activity_completion_report_course',
        'description'   => 'get user activity completion_report course',
        'type'          => 'read',
        'services'      => [WAGHWANIGLOBLE_ADMIN_SERVICE, WAGHWANIGLOBLE_USER_SERVICE]
    ],
    'wfglobal_get_user_activity_completion_status' => [
        'classname'     => 'local_wfglobal_external',
        'methodname'    => 'get_user_activity_completion_status',
        'description'   => 'get user activity completion',
        'type'          => 'read',
        'services'      => [WAGHWANIGLOBLE_ADMIN_SERVICE, WAGHWANIGLOBLE_USER_SERVICE]
    ],
    'wfglobal_get_clear_cookies' => [
        'classname'     => 'local_wfglobal_external',
        'methodname'    => 'get_clear_cookies',
        'description'   => 'Create cookies',
        'type'          => 'read',
        'services'      => [WAGHWANIGLOBLE_ADMIN_SERVICE, WAGHWANIGLOBLE_USER_SERVICE],
        'ajax'          => true,
        'loginrequired' => false
     ],

];

$services = [
    WAGHWANIGLOBLE_ADMIN_SERVICE => [
        'functions' => [WAGHWANIGLOBLE_ADMIN_SERVICE],
        'restrictedusers' => 0,
        'enabled' => 1,
        'shortname' => WAGHWANIGLOBLE_ADMIN_SERVICE
    ],
    WAGHWANIGLOBLE_USER_SERVICE => [
        'functions' => [WAGHWANIGLOBLE_USER_SERVICE],
        'restrictedusers' => 0,
        'enabled' => 1,
        'shortname' => WAGHWANIGLOBLE_USER_SERVICE
    ],
];
