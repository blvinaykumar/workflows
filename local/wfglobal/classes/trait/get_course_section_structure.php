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
 * @file       Course TOC
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

trait get_course_section_structure {

    public static function get_course_section_structure_parameters() {

        return new external_function_parameters(['courseid' => new external_value(PARAM_INT, 'course id')]);
    }

    public static function get_course_section_structure(int $courseid ) {

           global $DB;

            $PARAMS = self::validate_parameters(self::get_course_section_structure_parameters(), ['courseid' => $courseid]);
            $course = get_course($PARAMS['courseid']);
            // if($course->format != 'flexsections'){
            //     throw new moodle_exception("Invalid course format");
            // }

            $modinfo = get_fast_modinfo($course);
            $sections = $modinfo->get_section_info_all();
            $data = [];
            unset($sections[0]);
            foreach($sections as $section):

                if($section->parent != 0){
                    continue;
                }
               
                $subsections = [];
                foreach ($sections as $subsection) {

                    if($subsection->parent != 0 and $subsection->parent == $section->section  ){

                        $activities = [];
                           $sql = "SELECT cm.id, cm.instance, m.name, cm.visible, string_agg(t.rawname,',') as tagsname, cm.availability
                                     FROM {course_modules} cm
                                LEFT JOIN {modules} m ON m.id = cm.module
                                LEFT JOIN {tag_instance} ti ON ti.itemid = cm.id
                                LEFT JOIN {tag} t ON t.id = ti.tagid
                                    WHERE course = $course->id
                                      AND deletioninprogress = 0
                                      AND section = $subsection->id GROUP BY cm.id, cm.instance, m.name, cm.visible";

                        $mods = $DB->get_records_sql($sql);
 
                        if($mods):

                            foreach($mods as $mod):

                                $activity = (array) $DB->get_record($mod->name, ['id'=> $mod->instance],"id, name, intro ") + ["instance" => $mod->id, "modtype" => $mod->name, "visible" =>  $mod->visible, "tagsname" => $mod->tagsname];

                                $activity['intro'] = htmlentities($activity['intro']);
                               
                                $activity['courseCompletionInclude'] = $DB->record_exists("course_completion_criteria", ['moduleinstance'=> $mod->id, 'course'=> $PARAMS['courseid'] ]) ?? 0;

                               if($grademax = $DB->get_field("grade_items", "grademax", [ "itemtype" => "mod",'itemmodule'=> $mod->name, 'iteminstance' => $mod->instance, 'courseid'=> $PARAMS['courseid']])){

                                $activity['maxGrade'] = (float) $grademax;
                                   
                                }else{

                                    $activity['maxGrade'] = null;
                                }

                                if(!empty($mod->availability)):

                                    $availability = json_decode($mod->availability);

                                    foreach($availability->c as $c):

                                        if($c->type == "completion" ){

                                            if($c->e == 0){
                                                $c->text = 'must not be marked complete';
                                            }

                                            if($c->e == 1){
                                                $c->text = 'must be marked complete';
                                            }

                                            if($c->e == 2){
                                                $c->text = 'must be complete with pass grade';
                                            }

                                            if($c->e == 3){
                                                $c->text = 'must be complete with fail grade';
                                            }

                                        }

                                    endforeach;

                                    $activity['availability'] = $availability->c ?? [];

                                endif;

                                $order = $subsection->modinfo->sections[$subsection->section] ?? [];
                                $order = array_flip($order);
                                $activity["order"] = $order[$mod->id] ?? null;
                                $activities[] = $activity;
                            endforeach;


                         array_multisort(array_column($activities, 'order'), SORT_ASC, $activities);
                           
                        endif;

                        $subsections[$subsection->id] = [ "id" => $subsection->id,
                                                          "name" => $subsection->name ?? "Topic $subsection->id",
                                                          "description" => htmlentities($subsection->summary),
                                                          "visible" => $subsection->visibleold,
                                                          "order" => $subsection->section,
                                                          "mods" => $activities ];
                    }
                }

                $data[$section->id] = ["id" => $section->id,
                                       "name" => $section->name ?? "Topic $section->id",
                                       "description" => htmlentities ($section->summary),
                                       "visible" => $section->visibleold,
                                       "order" => $section->section ];
                                       
                $data[$section->id]['subsections'] = $subsections;  

            endforeach;

            array_multisort(array_column($data, 'order'), SORT_ASC, $data);

            $v = 0;

            foreach($data as $key => $value):

                $data[$key]['order'] = $v++;

                array_multisort(array_column($value['subsections'], 'order'), SORT_ASC, $value['subsections']);

                $s = 0;

                foreach($value['subsections'] as $subkey => $subvalue ):

                    $data[$key]['subsections'][$subvalue['id']]['order'] = $s++;

                endforeach;
            endforeach;
            return $data;
    }

    public static function get_course_section_structure_returns() {

        return new external_multiple_structure(
            new external_single_structure(
                ["id" => new external_value(PARAM_RAW, 'id of section'),
                 "name" => new external_value(PARAM_RAW, 'name of section'),
                 "description" => new external_value(PARAM_RAW, 'description of section'),
                 "visible" => new external_value(PARAM_RAW, 'visible of section'),
                 "order" => new external_value(PARAM_INT, 'order'),
                 "subsections" => new external_multiple_structure(
                                  new external_single_structure(["id" => new external_value(PARAM_RAW, 'id of section'),
                                                                 "name" => new external_value(PARAM_RAW, 'name of section'),
                                                                 "description" => new external_value(PARAM_RAW, 'description of section'),
                                                                 "visible" => new external_value(PARAM_RAW, 'visible of section'),
                                                                 "order" => new external_value(PARAM_INT, 'order'),
                                                                 "mods" => new external_multiple_structure(new external_single_structure([

                                                                     "id" => new external_value(PARAM_RAW, 'id of mod'),
                                                                     "instance" => new external_value(PARAM_RAW, 'instance of mod'),
                                                                     "modtype" => new external_value(PARAM_RAW, 'modtype of mod'),
                                                                     "name" => new external_value(PARAM_RAW, 'name of mod'),
                                                                     "intro" => new external_value(PARAM_RAW, 'description of mod'),
                                                                     "tagsname" => new external_value(PARAM_RAW, 'tagname of mod'),
                                                                     "visible" => new external_value(PARAM_RAW, 'description of visible'),
                                                                     "courseCompletionInclude" => new external_value(PARAM_BOOL, 'course completion'),
                                                                     "maxGrade" => new external_value(PARAM_RAW, 'maxGrade'),
                                                                     "order" => new external_value(PARAM_INT, 'order'),
                                                                     "availability" => new external_multiple_structure(new external_single_structure([
                                                                        "type" => new external_value(PARAM_RAW, 'type'),
                                                                        "cm" => new external_value(PARAM_RAW, 'cm'),
                                                                        "e" => new external_value(PARAM_RAW, 'e'),
                                                                        "text" => new external_value(PARAM_RAW, 'text'),
                                                                        ]),'', VALUE_OPTIONAL)
                                                                 ]),'', VALUE_OPTIONAL)]
                                 ),'', VALUE_OPTIONAL)
            ])
        );
    }

  

}