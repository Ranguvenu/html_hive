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
 * Class containing data for course competencies page
 *
 * @package    local_search
 * @copyright  2018 hemalathacarun <hemalatha@eabyas.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_search\output;

defined('MOODLE_INTERNAL') || die();

use renderable;
use templatable;
use renderer_base;
use stdClass;
use moodle_url;
use context_system;
use context_course;
use core_component;

/**
 * Class containing data for course competencies page
 *
 * @copyright  2018 hemalatha c arun <hemalatha@eabyas.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
    class cataloglib {

   static public $includesobj;

    /* To hold page number */
   static public $page;

    /* To hold search text */
   static public $search;

    /* To hold category/department id */
   static public $category;

    /* To hold enrolltype */
    static  public  $enrolltype;

    /* To hold enrolltype */
    static  public  $sortid;


    public function __set($variable, $value){
        // self::$data[$variable] = $value;
         self::$variable = $value;

    } // end of set function 


    public static function convert_urlobject_intoplainurl($course){
        $coursefileurl = self::$includesobj->course_summary_files($course);
        if(is_object($coursefileurl)){
            $coursefileurl=$coursefileurl->out();
        }
        return $coursefileurl;
    }  // end of convert_urlobject_intoplainurl function   
    

    public static function format_thestring($stringcontent){
        $stringcontent_len = strlen(strip_tags(html_entity_decode(clean_text($stringcontent)),array('overflowdiv' => false, 'noclean' => false, 'para' => false)));
            // if($stringcontent_len >= 20){
            //     $trimedcontent = substr($stringcontent, 0, 20).'...';
            // }else{
                $trimedcontent = clean_text($stringcontent);
            // }
        return $trimedcontent;
    } // end of formatthestring


    public static function format_thesummary($summary){        

        if(!empty($summary)){
           // $summary =  self::to_display_description($summary);
            //$string = strip_tags(html_entity_decode($summary),array('overflowdiv' => false, 'noclean' => false, 'para' => false));
            $string =  $summary;
            if (strlen($summary) > 85) {
                //truncate string
                $stringCut = substr($summary, 0, 85);
                $string = $stringCut.'...'; 
            }
            $trimmedsummary =  strip_tags(html_entity_decode(clean_text($string)));
                     
        }else{
            $trimmedsummary = '<p class="alert alert-info">'.get_string('descriptionisnotavailable','local_search').'</p>';
        }

        return $trimmedsummary;
    } // end of format_thesummary


    public static function get_thedateformat($date){

        if($date){
                $formatted_date = date('d M', $date);
            }
            else{
                $formatted_date ='N/A';          
        }         

        return $formatted_date;

    } // end of get_thedateformat   


    public static function trim_theband($bands){

        if(empty($bands)){                
            $trimmedbands="N/A";
        }
        elseif($bands!='-1'){                
            $bands = strip_tags(clean_text($bands));                
            if (strlen($bands) > 15) { 
                $trimmedbands = substr($bands, 0, 15).'...';
                    
            }  
        }else{                
            $trimmedbands= get_string('all','local_search');            
        }  

        return $trimmedbands;     
    } // trim_theband


    public static function to_display_description($description){
        
        if(empty($description)){            
            $description= '<span class="alert alert-info">'.get_string('descriptionisnotavailable','local_search').'</span>';          
        }       
        return strip_tags(html_entity_decode(clean_text($description)),array('overflowdiv' => false, 'noclean' => false, 'para' => false));        
    } // end of to_display_description function

  public static function check_catalogpluginexists_ornot(){

    $standard_catalogtypes = array(ELE, LP, ILT);
    
    foreach($standard_catalogtypes as $key => $type){
        switch($type){ 
          
            case ELE : $elearningexists =0;
                       $plugin_exists = core_component::get_plugin_directory('local', 'courses'); 
                        if(!empty($plugin_exists)){
                          $elearningexists =1;
                        }                 
                      break;

            case LP :  $lpexists =0;
                     $plugin_exists = core_component::get_plugin_directory('local', 'learningplan'); 
                      if(!empty($plugin_exists)){
                       $lpexists =1;
                      } // end of if condition
                      break;

            case ILT : $classroomexists =0;
                       $plugin_exists = core_component::get_plugin_directory('local', 'classroom'); 
                        if(!empty($plugin_exists)){
                          $classroomexists =1;
                        }                 
                    break;

        }// end of switch case
    } // end of foreach

        $res = array('elearningexists' => true,
                  'lpexists' => $lpexists,
                   'classroomexists' => $classroomexists
               );

        return $res;
 
    }  // end of  function

} // end of class






