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
 * Logs the user out and sends them to the home page
 *
 * @author     Vinay <vinay@ballisticlearning.com>
 * @package    local_wfglobal
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if (!$hassiteconfig) { return; }

$settings = new admin_settingpage('local_wfglobal', get_string('pluginname', 'local_wfglobal'));

$ADMIN->add('localplugins', $settings);

$settings->add(new admin_setting_configtext('local_wfglobal/portalurl',
        get_string('portelurl', 'local_wfglobal'),
        get_string('portelurl_info', 'local_wfglobal'),
        "https://dev.opportunity.wfglobal.org",
        PARAM_RAW));
 
