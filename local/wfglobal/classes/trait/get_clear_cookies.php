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

trait get_clear_cookies {

    public static function get_clear_cookies_parameters() {

        return new external_function_parameters([]);
    }

    public static function get_clear_cookies() {
        global $CFG;
        self::validate_parameters(self::get_health_check_parameters(), []);
        $sessionname = 'MoodleSession'.$CFG->sessioncookie;
        header('Access-Control-Allow-Origin: *');
        header("Set-Cookie:$sessionname=123456; expires=Sat, 01-Jan-2023 14:58:07 GMT; path=$CFG->sessioncookiepath");
     
        return ['success' => true];
    }

    public static function get_clear_cookies_returns() {

      return new external_single_structure(['success' => new external_value(PARAM_BOOL, 'Status')]);
    }

}
