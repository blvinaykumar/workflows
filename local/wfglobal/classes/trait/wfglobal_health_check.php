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

trait wfglobal_health_check {

    public static function wfglobal_health_check_parameters() {

        return new external_function_parameters([]);
    }

    public static function wfglobal_health_check() {

        self::validate_parameters(self::wfglobal_health_check_parameters(), []);
        return ['code' => 200, 'message' => 'LMS is Healthy'];
    }

    public static function wfglobal_health_check_returns() {

        return new external_single_structure(['code' => new external_value(PARAM_INT,'error'),
                                              'message' => new external_value(PARAM_TEXT,'message')]);       
    }
}