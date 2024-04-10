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

define("WAGHWANIGLOBLE_ADMIN_SERVICE", "WAGHWANIGLOBLE_ADMIN_SERVICE");
define("WAGHWANIGLOBLE_USER_SERVICE", "WAGHWANIGLOBLE_USER_SERVICE");
 
function local_wfglobal_before_standard_html_head() {

    global $PAGE, $CFG;
    if (strpos($PAGE->url, '/course/view.php') == true){
        return;
    }
    
   // require_once( $CFG->dirroot . "/local/wfglobal/classes/courseformat.php");
   // $wfglobal = new local_wfglobal_course_format();
}