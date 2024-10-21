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
 * Plugin version and other meta-data are defined here.
 *
 * @package     local_prisemforce
 * @copyright   2024 Moodle India Information Solutions Pvt Ltd
 * @author      2024 Shamala <shamala.kandula@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once("$CFG->libdir/externallib.php");
require_once($CFG->dirroot.'/local/courses/lib.php');
require_once($CFG->dirroot . '/local/prisemforce/lib.php');

class local_prisemforce_external extends external_api {
    /**
     * Describes the parameters for submit_create_course_form webservice.
     * @return external_function_parameters
     */
    public static function skillmaster_parameters() {
        $skillfields = array(
            'skillCategory' => new external_value(PARAM_RAW, 'Skill Category name', VALUE_OPTIONAL),
            'skillMasterId' => new external_value(PARAM_INT, 'Skill master id', VALUE_OPTIONAL),
            'createdByEmpId' => new external_value(PARAM_TEXT, 'Created by employee id', VALUE_OPTIONAL),
            'updatedByEmpId' => new external_value(PARAM_TEXT, 'Updated by employee id', VALUE_OPTIONAL),
            'skillCategoryId' => new external_value(PARAM_INT, 'Skill Category id'),
            'skillMasterName' => new external_value(PARAM_RAW, 'Skill Name'),
            'enabled' => new external_value(PARAM_INT, 'Status of the Skill'),
            'createdAt' => new external_value(PARAM_RAW, 'Created time', VALUE_OPTIONAL),
            'hideSkill' => new external_value(PARAM_RAW, 'Additional Parameter', VALUE_OPTIONAL),
            'updatedAt' => new external_value(PARAM_RAW, 'Updated time', VALUE_OPTIONAL),
            'skillGroups' => new external_value(PARAM_TEXT, 'Array of levels', VALUE_OPTIONAL),
            'extId' => new external_value(PARAM_RAW, 'Unique ID for L4 skill', VALUE_OPTIONAL),
            'practiceId' => new external_value(PARAM_RAW, 'System generated ID for the practice of the skill', VALUE_OPTIONAL),
            'leafSkillExtTag' => new external_value(PARAM_RAW, 'ID in client system', VALUE_OPTIONAL),           
            'adjacentSkillIs' => new external_value(PARAM_TEXT, 'List of adjacent skill', VALUE_OPTIONAL),
            'priority' => new external_value(PARAM_TEXT, 'Is the skill is priority', VALUE_OPTIONAL),
            'adjacentSkillItemIds' => new external_value(PARAM_TEXT, 'comma separated skillids', VALUE_OPTIONAL),
            
        );
        return new external_function_parameters([
            'skilldata' => new external_value(PARAM_RAW,'',json_encode($skillfields))
        ]);
    }
    public static function skillmaster($skilldata) {   
        global $DB, $CFG, $USER;
        // We always must pass webservice params through validate_parameters.
        $params = self::validate_parameters(
            self::skillmaster_parameters(),['skilldata' => $skilldata]);
        $status = '';
        $errormsg = '';
        $skillrecord = json_decode($skilldata);
        $skillobject = new stdClass();
        //$repositoryinsert  = new local_skillrepository\event\insertrepository();
        if ($skillrecord) {
            $orgid = $DB->get_field('local_costcenter','id', array('shortname'=>'Fractal'));
            $skillobject->costcenterid = $orgid;
            $categoryrecord = $DB->get_record('local_skill_categories',['id' => $skillrecord->skillCategoryId]);
            
            if ($categoryrecord) {
                $masterrecord = $DB->get_record('local_skill',['shortname' => $skillrecord->skillMasterId]);
                if ($masterrecord) {
                    $empidcheck = $DB->get_record('user',['open_employeeid' => $skillrecord->updatedByEmpId]);
                    if ($empidcheck) {
                        $skillobject->id = $masterrecord->id;
                        $skillobject->category = $skillrecord->skillCategoryId;
                        $skillobject->name = $skillrecord->skillMasterName;
                        $skillobject->shortname = $skillrecord->skillMasterId;//str_replace(' ','',$skillrecord->skillMasterName);
                        $skillobject->usermodified = $empidcheck->id;
                        $skillobject->timemodified = time();
                        $DB->update_record('local_skill', $skillobject);
                        $id = $masterrecord->id; //$skillrecord->skillMasterId;
                        $status = 'Success';
                    } else {
                        $status = 'Failed';
                        $errormsg = 'updatedByEmpId doesnot exists.';
                    }                   
                } else {
                    $empidcheck = $DB->get_record('user',['open_employeeid' => $skillrecord->createdByEmpId]);
                    if ($empidcheck) {
                        $skillobject->category = $skillrecord->skillCategoryId;
                        $skillobject->name = $skillrecord->skillMasterName;
                        $skillobject->shortname = $skillrecord->skillMasterId;//str_replace(' ','',$skillrecord->skillMasterName);
                        $skillobject->usercreated = $empidcheck->id;
                        $skillobject->timecreated = time();
                        $id = $DB->insert_record('local_skill', $skillobject);
                        $status = 'Success';

                    } else {
                        $status = 'Failed';
                        $errormsg = 'createdByEmpId doesnot exists.';
                    }
                   
                }

            } else {
                $status = 'Failed';
                $errormsg = 'Skill Category id doesnot exists.';

            }
        }        
        $result = [
            'interface' => 'SkillMaster',
            'pfTransactionId' => $id,
            'employeeid' => $skillrecord->createdByEmpId,
            'status' => $status,
            'errorMessage' => $errormsg

        ]; 
      
        return $result;
    } 
    public static function skillmaster_returns() {
        return new external_single_structure(
            array(
                'interface' =>new external_value(PARAM_TEXT, 'Interface'),
                'pfTransactionId' => new external_value(PARAM_TEXT, 'Transaction id'),
                'status' => new external_value(PARAM_TEXT, 'Status'),
                'errorMessage' => new external_value(PARAM_TEXT, 'Error Message', VALUE_OPTIONAL),                
            )
        );
    }
     /**
     * Describes the parameters for submit_create_course_form webservice.
     * @return external_function_parameters
     */
    public static function get_courses_parameters(){
        return new external_function_parameters(
            array()
        );
    }
    public static function get_courses(){
        global $DB;
        $selectsql = "SELECT c.id, c.fullname, c.shortname, c.category,c.open_skill,cc.name categoryname FROM {course} c
        JOIN {course_categories} cc ON cc.id = c.category
        WHERE c.visible = 1";
        $courses = $DB->get_records_sql($selectsql,array());               
        if(count($courses)>0){
            foreach($courses as $course){
                $courseid = strip_tags(trim($course->id));
                $coursename = strip_tags(trim($course->fullname));
                $courseshortname = strip_tags(trim($course->shortname));
                $categoryname = strip_tags(trim($course->categoryname));
                $sql = "SELECT id,name FROM {local_skill} WHERE FIND_IN_SET(id, '".$course->open_skill."')";
                $skills = $DB->get_records_sql($sql);
                $skillsdata = '';
                foreach ($skills as $skill) {
                    if ($skillsdata == '') {
                        $skillsdata = $skill->name;
                    } else {
                        $skillsdata .= ','.$skill->name;
                    }
                }
                $image = course_thumbimage($course);
                $courseslist[] = ['courseid' =>$courseid, 
                'coursename' => $coursename,
                'courseshortname'=>$courseshortname,
                'categoryname' => $categoryname,
                'skills' => $skillsdata,
                'courseimage' => $image
               ]; 
            }          
        }
        $result = [
            'result' => $courseslist
        ];  
      
        return $result;
    }
    public static function get_courses_returns()
    {
        return new external_single_structure(
            array(
               'result' => new external_multiple_structure(
                 new external_single_structure(
                     array(
                        'courseid' =>new external_value(PARAM_INT, 'Course id',VALUE_REQUIRED),
                        'coursename' => new external_value(PARAM_RAW, 'Course Name'),
                        'courseshortname' => new external_value(PARAM_RAW, 'Course short name',VALUE_REQUIRED),
                        'categoryname' => new external_value(PARAM_RAW, 'Category Name'),
                        'skills' => new external_value(PARAM_RAW, 'Skills Name'),
                        'courseimage' => new external_value(PARAM_URL, 'Course image'),
                     )
                 )
             )
          )
       );
    }
    /**
     * Describes the parameters for submit_create_course_form webservice.
     * @return external_function_parameters
     */
    public static function skillgroups_parameters() {
        $skillfields = array(
            'enabled' => new external_value(PARAM_INT, 'Skill Category name', VALUE_OPTIONAL),
            'createdAt' => new external_value(PARAM_RAW, 'Created time', VALUE_OPTIONAL),
            'updatedAt' => new external_value(PARAM_RAW, 'Updated time', VALUE_OPTIONAL),
            'skillGroupId' => new external_value(PARAM_RAW, 'Skill master id', VALUE_OPTIONAL),
            'hierarchyName' => new external_value(PARAM_RAW, 'Skill Name'),
            'createdByEmpId' => new external_value(PARAM_RAW, 'Created by employee id', VALUE_OPTIONAL),
            'hierarchyLevel' => new external_value(PARAM_INT, 'Status of the Skill', VALUE_OPTIONAL),
            'skillGroupName' => new external_value(PARAM_RAW, 'Skill Name'),
            'updatedByEmpId' => new external_value(PARAM_RAW, 'Updated by employee id', VALUE_OPTIONAL),
        );
        
        return new external_function_parameters([
            'skillGroup' => new external_value(PARAM_RAW,'',json_encode($skillfields))
        ]);
    }
    public static function skillgroups($skillGroup) {
        global $DB, $CFG, $USER;
        // We always must pass webservice params through validate_parameters.
        $params = self::validate_parameters(
            self::skillgroups_parameters(),['skillGroup' => $skillGroup]);
        $status = '';
        $errormsg = '';
        $skillGrouprecord = json_decode($skillGroup);
        $skillgroupobject = new stdClass();
        if ($skillGrouprecord) {
            $grouprecords = $DB->get_record('local_skill_categories',['shortname' => $skillGrouprecord->skillGroupId]);
            
            if ($grouprecords) {
                $empidcheck = $DB->get_record('user',['open_employeeid' => $skillGrouprecord->createdByEmpId]);
                if ($empidcheck) {
                    $orgid = $DB->get_field('local_costcenter','id', array('shortname'=>'Fractal'));
                    $skillgroupobject->id = $grouprecords->id;
                    $skillgroupobject->costcenterid = $orgid;
                    $skillgroupobject->name = $skillGrouprecord->skillGroupName;
                    $skillgroupobject->shortname = $skillGrouprecord->skillGroupId;
                    $skillgroupobject->parentid = 0;
                    $skillgroupobject->sortorder = 0;
                    $skillgroupobject->depth = 1;
                    $skillgroupobject->path = $orgid;
                    $skillgroupobject->usercreated = $empidcheck->id;
                    $skillgroupobject->timecreated = time();
                    $DB->update_record('local_skill_categories', $skillgroupobject);
                    $id = $grouprecords->id; 
                    $status = 'Success';
                } else {
                    $status = 'Failed';
                    $errormsg = 'updatedByEmpId doesnot exists.';
                }                
            } else {
                $empidcheck = $DB->get_record('user',['open_employeeid' => $skillGrouprecord->createdByEmpId]);
                if ($empidcheck) {
                    $orgid = $DB->get_field('local_costcenter','id', array('shortname'=>'Fractal'));
                    $skillgroupobject->costcenterid = $orgid;
                    $skillgroupobject->name = $skillGrouprecord->skillGroupName;
                    $skillgroupobject->shortname = $skillGrouprecord->skillGroupId;
                    $skillgroupobject->parentid = 0;
                    $skillgroupobject->sortorder = 0;
                    $skillgroupobject->depth = 1;
                    $skillgroupobject->path = $orgid;
                    $skillgroupobject->usercreated = $empidcheck->id;
                    $skillgroupobject->timecreated = time();
                    $id = $DB->insert_record('local_skill_categories', $skillgroupobject);

                    // Update path (only possible after we know the category id.
                    $pathupdate = new stdClass();
                    $pathupdate->id = $id;
                    if(!empty($skillgroupobject->parentid)){
                        $pathupdate->path = '/'.$skillgroupobject->parentid . '/' . $pathupdate->id;
                    }else{
                        $pathupdate->path = '/' . $pathupdate->id;
                    }
                    $DB->update_record('local_skill_categories', $pathupdate);                    
                    $status = 'Success';
                } else {
                    $status = 'Failed';
                    $errormsg = 'createdByEmpId doesnot exists.';
                }                
            }
        }         
        $result = [
            'interface' => 'SkillGroups',
            'pfTransactionId' => $id,
            'employeeid' => $skillGrouprecord->createdByEmpId,
            'status' => $status,
            'errorMessage' => $errormsg

        ]; 
      
        return $result;
    }
    public static function skillgroups_returns() {
        return new external_single_structure(
            array(
                'interface' =>new external_value(PARAM_TEXT, 'Interface'),
                'pfTransactionId' => new external_value(PARAM_TEXT, 'Transaction id'),
                'status' => new external_value(PARAM_TEXT, 'Status'),
                'errorMessage' => new external_value(PARAM_TEXT, 'Error Message', VALUE_OPTIONAL),                
            )
        );
    }
    /**
     * Describes the parameters for submit_create_course_form webservice.
     * @return external_function_parameters
     */
    public static function insertlog_parameters() {               
        return new external_function_parameters([           
            'logdata' => new external_value(PARAM_RAW, 'parameter what sending'),            
        ]);
    }
    public static function insertlog($logdata) {
        global $DB, $CFG, $USER;
        // We always must pass webservice params through validate_parameters.
        $params = self::validate_parameters(
            self::insertlog_parameters(),['logdata' => $logdata]);
        $status = '';
        $errormsg = '';
        $transid = '';
        if ($logdata) {
            $decodelog = json_decode($logdata);            
            $transid = $decodelog->pfTransactionId;
            custom_log_saving('', 0, $decodelog->pfTransactionId, '', $logdata);                
            $status = 'Success';
        } else {
            $status = 'Fail';
            $errormsg = 'Please send the logdata.';

        }
        $result = [            
            'pfTransactionId' => $transid,
            'status' => $status,
            'errorMessage' => $errormsg
        ]; 
      
        return $result;
    }
    public static function insertlog_returns() {
        return new external_single_structure(
            array(                
                'pfTransactionId' => new external_value(PARAM_TEXT, 'Transaction id'),
                'status' => new external_value(PARAM_TEXT, 'Status'),
                'errorMessage' => new external_value(PARAM_TEXT, 'Error Message', VALUE_OPTIONAL),                
            )
        );
    }


  
}

