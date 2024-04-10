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

trait get_health_check {

    public static function get_health_check_parameters() {

        return new external_function_parameters([]);
    }

    public static function get_health_check() {

        self::validate_parameters(self::get_health_check_parameters(), []);

        return [["error" => false, "data" => ["status" => "SUCCESS", "data" => ["moodleStatus" => "connected"]]]];
    }

    public static function get_health_check_returns() {

        return new external_multiple_structure(
                new external_single_structure(
                        ["error" => new external_value(PARAM_BOOL, 'error'),
                    "data" => new external_single_structure([
                        "status" => new external_value(PARAM_ALPHA, 'status'),
                        "data" => new external_single_structure([
                            "moodleStatus" => new external_value(PARAM_ALPHA, 'moodleStatus'),
                                ])
                            ])
                        ])
        );
    }

}
