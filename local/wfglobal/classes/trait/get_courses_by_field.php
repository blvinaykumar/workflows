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

trait get_courses_by_field {

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 3.2
     */
    public static function get_courses_by_field_parameters() {
        return new external_function_parameters(
            array(
                'field' => new external_value(PARAM_ALPHA, 'The field to search can be left empty for all courses or:
                    id: course id
                    ids: comma separated course ids
                    shortname: course short name
                    idnumber: course id number
                    category: category id the course belongs to
                ', VALUE_DEFAULT, ''),
                'value' => new external_value(PARAM_RAW, 'The value to match', VALUE_DEFAULT, ''),
                'page' => new external_value(PARAM_INT, 'The value to page', VALUE_DEFAULT, 0),
                'perpage' => new external_value(PARAM_INT, 'The value to per page (DEFAULT 10)', VALUE_DEFAULT, 10)
            )
        );
    }


    /**
     * Get courses matching a specific field (id/s, shortname, idnumber, category)
     *
     * @param  string $field field name to search, or empty for all courses
     * @param  string $value value to search
     * @return array list of courses and warnings
     * @throws  invalid_parameter_exception
     * @since Moodle 3.2
     */
    public static function get_courses_by_field($field = '', $value = '', $page = 0, $perpage = 10) {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/course/lib.php');
        require_once($CFG->libdir . '/filterlib.php');


        $params = self::validate_parameters(self::get_courses_by_field_parameters(),['field' => $field, 
                                                                                     'value' => $value,
                                                                                     'page'  => $page,
                                                                                     'perpage' => $perpage ] );
        $warnings = array();
        $startfrom = $page * $params['perpage'];
        $count = 0;
        
        if (empty($params['field'])) {
           
            $count = $DB->count_records('course');
            $courses = $DB->get_records('course', null, 'id ASC', '*', $startfrom , $params['perpage']);
                   
        } else {
            switch ($params['field']) {
                case 'id':
                case 'category':
                    $value = clean_param($params['value'], PARAM_INT);
                    break;
                case 'ids':
                    $value = clean_param($params['value'], PARAM_SEQUENCE);
                    break;
                case 'shortname':
                    $value = clean_param($params['value'], PARAM_TEXT);
                    break;
                case 'idnumber':
                    $value = clean_param($params['value'], PARAM_RAW);
                    break;
                default:
                    throw new invalid_parameter_exception('Invalid field name');
            }

            if ($params['field'] === 'ids') {
                // Preload categories to avoid loading one at a time.
                $courseids = explode(',', $value);
                list ($listsql, $listparams) = $DB->get_in_or_equal($courseids);
                $categoryids = $DB->get_fieldset_sql("
                        SELECT DISTINCT cc.id
                          FROM {course} c
                          JOIN {course_categories} cc ON cc.id = c.category
                         WHERE c.id $listsql", $listparams);
                core_course_category::get_many($categoryids);

                // Load and validate all courses. This is called because it loads the courses
                // more efficiently.
                list ($courses, $warnings) = external_util::validate_courses($courseids, [],
                        false, true);

            } else {

                $count = $DB->count_records('course', [ $params['field'] => $value]);
                $courses = $DB->get_records('course', [$params['field'] => $value ], 'id ASC', "*",  $startfrom , $params['perpage']);
            }
        }

        $coursesdata = array();
        foreach ($courses as $course) {
            $context = context_course::instance($course->id);
            $canupdatecourse = has_capability('moodle/course:update', $context);
            $canviewhiddencourses = has_capability('moodle/course:viewhiddencourses', $context);

            // Check if the course is visible in the site for the user.
            if (!$course->visible and !$canviewhiddencourses and !$canupdatecourse) {
                continue;
            }
            // Get the public course information, even if we are not enrolled.
            $courseinlist = new core_course_list_element($course);

            // Now, check if we have access to the course, unless it was already checked.
            try {
                if (empty($course->contextvalidated)) {
                    self::validate_context($context);
                }
            } catch (Exception $e) {
                // User can not access the course, check if they can see the public information about the course and return it.
                if (core_course_category::can_view_course_info($course)) {
                    $coursesdata[$course->id] = self::get_course_public_information($courseinlist, $context);
                }
                continue;
            }
            $coursesdata[$course->id] = self::get_course_public_information($courseinlist, $context);
            // Return information for any user that can access the course.
            $coursefields = array('format', 'showgrades', 'newsitems', 'startdate', 'enddate', 'maxbytes', 'showreports', 'visible',
                'groupmode', 'groupmodeforce', 'defaultgroupingid', 'enablecompletion', 'completionnotify', 'lang', 'theme',
                'marker');

            // Course filters.
            $coursesdata[$course->id]['filters'] = filter_get_available_in_context($context);

            // Information for managers only.
            if ($canupdatecourse) {
                $managerfields = array('idnumber', 'legacyfiles', 'calendartype', 'timecreated', 'timemodified', 'requested',
                    'cacherev');
                $coursefields = array_merge($coursefields, $managerfields);
            }

            // Populate fields.
            foreach ($coursefields as $field) {
                $coursesdata[$course->id][$field] = $course->{$field};
            }

            // Clean lang and auth fields for external functions (it may content uninstalled themes or language packs).
            if (isset($coursesdata[$course->id]['theme'])) {
                $coursesdata[$course->id]['theme'] = clean_param($coursesdata[$course->id]['theme'], PARAM_THEME);
            }
            if (isset($coursesdata[$course->id]['lang'])) {
                $coursesdata[$course->id]['lang'] = clean_param($coursesdata[$course->id]['lang'], PARAM_LANG);
            }

            $courseformatoptions = course_get_format($course)->get_config_for_external();
            foreach ($courseformatoptions as $key => $value) {
                $coursesdata[$course->id]['courseformatoptions'][] = array(
                    'name' => $key,
                    'value' => $value
                );
            }
        }

        return array(
            'courses' => $coursesdata,
            'warnings' => $warnings,
            'numberofpage' => $numberofpage = ceil($count / $params['perpage'] )
        );
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 3.2
     */
    public static function get_courses_by_field_returns() {
        // Course structure, including not only public viewable fields.
        return new external_single_structure(
            array(
                'courses' => new external_multiple_structure(self::get_course_structure(false), 'Course'),
                'numberofpage' => new external_value(PARAM_INT, 'Total number of page'),
                'warnings' => new external_warnings()
            )
        );
    }

    /**
     * Returns a course structure definition
     *
     * @param  boolean $onlypublicdata set to true, to retrieve only fields viewable by anyone when the course is visible
     * @return array the course structure
     * @since  Moodle 3.2
     */
    protected static function get_course_structure($onlypublicdata = true) {
        $coursestructure = array(
            'id' => new external_value(PARAM_INT, 'course id'),
            'fullname' => new external_value(PARAM_RAW, 'course full name'),
            'displayname' => new external_value(PARAM_RAW, 'course display name'),
            'shortname' => new external_value(PARAM_RAW, 'course short name'),
            'categoryid' => new external_value(PARAM_INT, 'category id'),
            'categoryname' => new external_value(PARAM_RAW, 'category name'),
            'sortorder' => new external_value(PARAM_INT, 'Sort order in the category', VALUE_OPTIONAL),
            'summary' => new external_value(PARAM_RAW, 'summary'),
            'summaryformat' => new external_format_value('summary'),
            'summaryfiles' => new external_files('summary files in the summary field', VALUE_OPTIONAL),
            'overviewfiles' => new external_files('additional overview files attached to this course'),
            'contacts' => new external_multiple_structure(
                new external_single_structure(
                    array(
                        'id' => new external_value(PARAM_INT, 'contact user id'),
                        'fullname'  => new external_value(PARAM_NOTAGS, 'contact user fullname'),
                    )
                ),
                'contact users'
            ),
            'enrollmentmethods' => new external_multiple_structure(
                new external_value(PARAM_PLUGIN, 'enrollment method'),
                'enrollment methods list'
            ),
            'customfields' => new external_multiple_structure(
                new external_single_structure(
                    array(
                        'name' => new external_value(PARAM_RAW, 'The name of the custom field'),
                        'shortname' => new external_value(PARAM_RAW,
                            'The shortname of the custom field - to be able to build the field class in the code'),
                        'type'  => new external_value(PARAM_ALPHANUMEXT,
                            'The type of the custom field - text field, checkbox...'),
                        'valueraw' => new external_value(PARAM_RAW, 'The raw value of the custom field'),
                        'value' => new external_value(PARAM_RAW, 'The value of the custom field'),
                    )
                ), 'Custom fields', VALUE_OPTIONAL),
        );

        if (!$onlypublicdata) {
            $extra = array(
                'idnumber' => new external_value(PARAM_RAW, 'Id number', VALUE_OPTIONAL),
                'format' => new external_value(PARAM_PLUGIN, 'Course format: weeks, topics, social, site,..', VALUE_OPTIONAL),
                'showgrades' => new external_value(PARAM_INT, '1 if grades are shown, otherwise 0', VALUE_OPTIONAL),
                'newsitems' => new external_value(PARAM_INT, 'Number of recent items appearing on the course page', VALUE_OPTIONAL),
                'startdate' => new external_value(PARAM_INT, 'Timestamp when the course start', VALUE_OPTIONAL),
                'enddate' => new external_value(PARAM_INT, 'Timestamp when the course end', VALUE_OPTIONAL),
                'maxbytes' => new external_value(PARAM_INT, 'Largest size of file that can be uploaded into', VALUE_OPTIONAL),
                'showreports' => new external_value(PARAM_INT, 'Are activity report shown (yes = 1, no =0)', VALUE_OPTIONAL),
                'visible' => new external_value(PARAM_INT, '1: available to student, 0:not available', VALUE_OPTIONAL),
                'groupmode' => new external_value(PARAM_INT, 'no group, separate, visible', VALUE_OPTIONAL),
                'groupmodeforce' => new external_value(PARAM_INT, '1: yes, 0: no', VALUE_OPTIONAL),
                'defaultgroupingid' => new external_value(PARAM_INT, 'default grouping id', VALUE_OPTIONAL),
                'enablecompletion' => new external_value(PARAM_INT, 'Completion enabled? 1: yes 0: no', VALUE_OPTIONAL),
                'completionnotify' => new external_value(PARAM_INT, '1: yes 0: no', VALUE_OPTIONAL),
                'lang' => new external_value(PARAM_SAFEDIR, 'Forced course language', VALUE_OPTIONAL),
                'theme' => new external_value(PARAM_PLUGIN, 'Fame of the forced theme', VALUE_OPTIONAL),
                'marker' => new external_value(PARAM_INT, 'Current course marker', VALUE_OPTIONAL),
                'legacyfiles' => new external_value(PARAM_INT, 'If legacy files are enabled', VALUE_OPTIONAL),
                'calendartype' => new external_value(PARAM_PLUGIN, 'Calendar type', VALUE_OPTIONAL),
                'timecreated' => new external_value(PARAM_INT, 'Time when the course was created', VALUE_OPTIONAL),
                'timemodified' => new external_value(PARAM_INT, 'Last time  the course was updated', VALUE_OPTIONAL),
                'requested' => new external_value(PARAM_INT, 'If is a requested course', VALUE_OPTIONAL),
                'cacherev' => new external_value(PARAM_INT, 'Cache revision number', VALUE_OPTIONAL),
                'filters' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'filter'  => new external_value(PARAM_PLUGIN, 'Filter plugin name'),
                            'localstate' => new external_value(PARAM_INT, 'Filter state: 1 for on, -1 for off, 0 if inherit'),
                            'inheritedstate' => new external_value(PARAM_INT, '1 or 0 to use when localstate is set to inherit'),
                        )
                    ),
                    'Course filters', VALUE_OPTIONAL
                ),
                'courseformatoptions' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'name' => new external_value(PARAM_RAW, 'Course format option name.'),
                            'value' => new external_value(PARAM_RAW, 'Course format option value.'),
                        )
                    ),
                    'Additional options for particular course format.', VALUE_OPTIONAL
                ),
            );
            $coursestructure = array_merge($coursestructure, $extra);
        }
        return new external_single_structure($coursestructure);
    }

    /**
     * Return the course information that is public (visible by every one)
     *
     * @param  core_course_list_element $course        course in list object
     * @param  stdClass       $coursecontext course context object
     * @return array the course information
     * @since  Moodle 3.2
     */
    protected static function get_course_public_information(core_course_list_element $course, $coursecontext) {

        static $categoriescache = array();

        // Category information.
        if (!array_key_exists($course->category, $categoriescache)) {
            $categoriescache[$course->category] = core_course_category::get($course->category, IGNORE_MISSING);
        }
        $category = $categoriescache[$course->category];

        // Retrieve course overview used files.
        $files = array();
        foreach ($course->get_course_overviewfiles() as $file) {
            $fileurl = moodle_url::make_webservice_pluginfile_url($file->get_contextid(), $file->get_component(),
                                                                    $file->get_filearea(), null, $file->get_filepath(),
                                                                    $file->get_filename())->out(false);
            $files[] = array(
                'filename' => $file->get_filename(),
                'fileurl' => $fileurl,
                'filesize' => $file->get_filesize(),
                'filepath' => $file->get_filepath(),
                'mimetype' => $file->get_mimetype(),
                'timemodified' => $file->get_timemodified(),
            );
        }

        // Retrieve the course contacts,
        // we need here the users fullname since if we are not enrolled can be difficult to obtain them via other Web Services.
        $coursecontacts = array();
        foreach ($course->get_course_contacts() as $contact) {
             $coursecontacts[] = array(
                'id' => $contact['user']->id,
                'fullname' => $contact['username'],
                'roles' => array_map(function($role){
                    return array('id' => $role->id, 'name' => $role->displayname);
                }, $contact['roles']),
                'role' => array('id' => $contact['role']->id, 'name' => $contact['role']->displayname),
                'rolename' => $contact['rolename']
             );
        }

        // Allowed enrolment methods (maybe we can self-enrol).
        $enroltypes = array();
        $instances = enrol_get_instances($course->id, true);
        foreach ($instances as $instance) {
            $enroltypes[] = $instance->enrol;
        }

        // Format summary.
        list($summary, $summaryformat) =
            external_format_text($course->summary, $course->summaryformat, $coursecontext->id, 'course', 'summary', null);

        $categoryname = '';
        if (!empty($category)) {
            $categoryname = external_format_string($category->name, $category->get_context());
        }

        $displayname = get_course_display_name_for_list($course);
        $coursereturns = array();
        $coursereturns['id']                = $course->id;
        $coursereturns['fullname']          = external_format_string($course->fullname, $coursecontext->id);
        $coursereturns['displayname']       = external_format_string($displayname, $coursecontext->id);
        $coursereturns['shortname']         = external_format_string($course->shortname, $coursecontext->id);
        $coursereturns['categoryid']        = $course->category;
        $coursereturns['categoryname']      = $categoryname;
        $coursereturns['summary']           = $summary;
        $coursereturns['summaryformat']     = $summaryformat;
        $coursereturns['summaryfiles']      = external_util::get_area_files($coursecontext->id, 'course', 'summary', false, false);
        $coursereturns['overviewfiles']     = $files;
        $coursereturns['contacts']          = $coursecontacts;
        $coursereturns['enrollmentmethods'] = $enroltypes;
        $coursereturns['sortorder']         = $course->sortorder;

        $handler = core_course\customfield\course_handler::create();
        if ($customfields = $handler->export_instance_data($course->id)) {
            $coursereturns['customfields'] = [];
            foreach ($customfields as $data) {
                $coursereturns['customfields'][] = [
                    'type' => $data->get_type(),
                    'value' => $data->get_value(),
                    'valueraw' => $data->get_data_controller()->get_value(),
                    'name' => $data->get_name(),
                    'shortname' => $data->get_shortname()
                ];
            }
        }

        return $coursereturns;
    }
}