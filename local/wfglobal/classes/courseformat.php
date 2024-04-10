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


class local_wfglobal_course_format {

    public $currentpage; ///  type of currentpage course_view, player, mod
    public $course;
    public $last_visited;
    public $modinfo;
    public $section;
    public $redirect = false;


    public function __construct() {
          
        global $USER, $PAGE;

        if(isset($_POST)){
            return;
        }
        if ((isset($USER->editing) and $USER->editing == 1) or isset($_REQUEST['edit']) )  {
            return; 
        }

        // if(theme_wadwanigroup\output\core_renderer::checkIframe()){
        //     return ;
        // }

        if($this->boot()){
            $this->run();    
        }
        
    }

    public function boot(){

        if($this->get_currentpage() === false){
            return false;
        }

        if($this->get_course() === false){
            return false;
        }

        if($this->get_section() === false ){

           $this->section = $this->generate_section();
           return true;
        }

        return true;
    }

    public function get_currentpage(){
        
        global $PAGE;

        if (strpos($PAGE->url, '/course/view.php') == true){
            return $this->currentpage = "course_view";   
        }

        if (strpos($PAGE->url, '/player.php') == true){
            return $this->currentpage = "player";
        }

        if (strpos($PAGE->url, '/mod/') == true and strpos($PAGE->url, '/player.php') == false){
            return $this->currentpage = "mod";
        }

        return $this->currentpage = false;

    }

    public function get_course(){
        
        global $DB;

        if($this->currentpage == "course_view" ){ 
            $this->course = $DB->get_record('course', ['id' => required_param('id',  PARAM_INT) ], '*', MUST_EXIST); 
            $this->modinfo = get_fast_modinfo($this->course);
            return true;
        }

        if($this->currentpage == "mod" or $this->currentpage == "player" ){
            global $PAGE;
            $this->course = $PAGE->course; 
            $this->modinfo = get_fast_modinfo($this->course);
            return true;
        }

        return $this->course = false;
    }


    public function get_last_visited_section(){

        global $USER, $DB;

        $visited = $DB->get_record("last_visited_section", ['courseid' => $this->course->id, 'userid' => $USER->id ], "*",IGNORE_MULTIPLE);

        if(!$visited){
            return false;
        }

        $sectionnumder = $DB->get_field("course_sections", "section", ['id' => $visited->sectionid, "visible" => 1]);

        if(!$sectionnumder){
            return false;
        }

        $section = $this->modinfo->get_section_info($sectionnumder);
        $section->parent_section = $visited->parent_section;

        return $section;
    }

    public function section_validation($section){

        if($section->parent != 0 and $section->visibleold == 1 ){
            return true;
        }
        $this->redirect = true;
        return false;
    }


    public function generate_section(){
        
        $sections = $this->modinfo->get_section_info_all();
        $res = false;
        
        foreach($sections as $blsection){
       
            if($blsection->parent != 0 and $blsection->visibleold == 1){  
                $res = $blsection;
                $res->parent_section = $sections[$blsection->parent]->id;
                break;
            }
        }
        return $res;
    }

    public function get_section(){

        if($this->currentpage == "course_view"):

            if(($this->section = $this->get_section_course_view()) != false){
                return true;
            }

            return false;
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        
        endif; /// course_view 

        if($this->currentpage == "mod"):

            if(($this->section = $this->get_section_mod_view()) != false){
                return true;
            }

            return false;

        endif;


        if($this->currentpage == "player"):

            if(($this->section = $this->get_section_mod_player()) != false){
                return true;
            }

            return false;

        endif;

    } 

    public function get_section_mod_player(){

        global $PAGE, $DB;

        $sectionid = optional_param('sectionid', 0, PARAM_INT);
        $parent_section = optional_param('parent_section', 0, PARAM_INT);

        if($sectionid == 0 or $parent_section == 0){
             $this->redirect = true;
        }

        if($sectionnumder = $DB->get_field("course_sections", "section", ['id' => $PAGE->cm->section])){

            $section = $this->modinfo->get_section_info($sectionnumder);
            $section->parent_section = $this->modinfo->get_section_info($section->parent)->id;
            return $section;
        }

        return false;
    }

    public function get_section_mod_view(){

        global $PAGE, $DB;

       // $id = required_param('id',  PARAM_INT);
        $sectionid = optional_param('sectionid', 0, PARAM_INT);
        $parent_section = optional_param('parent_section', 0, PARAM_INT);

        if($sectionid == 0 or $parent_section == 0){
             $this->redirect = true;
        }

        if($sectionnumder = $DB->get_field("course_sections", "section", ['id' => $PAGE->cm->section])){

            $section = $this->modinfo->get_section_info($sectionnumder);
            $section->parent_section = $this->modinfo->get_section_info($section->parent)->id;
            return $section;
        }

        return false;
    }
    
    public function get_section_course_view(){

        global $DB;

        $sectionid = optional_param('sectionid', 0, PARAM_INT);

        if($sectionid != 0):
    
         
            
            if(!$sectioninfo = $DB->get_record('course_sections', ['id' => $sectionid, 'course' => $this->course->id]) ){
                 return false;
            }
               if(optional_param('parent_section', 0, PARAM_INT) == 0){
                $this->redirect = true;
            }

            $section = $this->modinfo->get_section_info($sectioninfo->section);
            $section->parent_section = $this->modinfo->get_section_info($section->parent)->id;
            return $section;

        endif;
        
        $this->redirect = true;
        return $this->get_last_visited_section();

    }

    public function redirect($section){

        global $PAGE;
        $url = $PAGE->url;
        $params = $_REQUEST;
        $params['sectionid'] = $section->id;
        $params['parent_section'] = $section->parent_section;
        $url->params($params);

        redirect($url);

    }

    public function generate_last_visited($section){

        global $DB, $USER;

        if($visited = $DB->get_record("last_visited_section", ['courseid' => $this->course->id, 'userid' => $USER->id ], "*",IGNORE_MULTIPLE)){

            $visited->sectionid = $section->id;
            $visited->parent_section = $section->parent_section;
            $DB->update_record('last_visited_section', $visited);
            return $section;
        }

        $visited = new stdClass();
        $visited->userid = $USER->id;
        $visited->sectionid = $section->id;
        $visited->parent_section = $section->parent_section;
        $visited->courseid = $this->course->id;
        $DB->insert_record('last_visited_section', $visited);
        return $section;
    }


    public function run(){

        if($this->section != false){
            
            if($this->section_validation($this->section) != false){

                $this->generate_last_visited($this->section);
                
                if($this->redirect == true){
                    $this->redirect($this->section);
                    return;
                }
            }else{

                $this->redirect($this->generate_last_visited($this->generate_section()));
                return;
            }
        }
    }
}
