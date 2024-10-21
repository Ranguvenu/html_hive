<?php

/**
 * This file is part of eAbyas
 *
 * Copyright eAbyas Info Solutons Pvt Ltd, India
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author eabyas <info@eabyas.in>
 * @subpackage local_skill
 */

namespace local_skill\upload;

require_once($CFG->dirroot . '/course/lib.php');

use html_writer;
use stdClass;

define('MANUAL_ENROLL', 1);
define('LDAP_ENROLL', 2);
define('SAML2', 3);
define('ADwebservice', 4);
define('ONLY_ADD', 1);
define('ONLY_UPDATE', 2);
define('ADD_UPDATE', 3);
class courseskillupload
{
    private $data;
    //-------To hold error messages
    private $errors = array();
    //----To hold error field name
    private $mfields = array(); 

    private $errorcount = 0;   

    private $updatedcount = 0;
   
    //-----It holds the unique username
    private $excel_line_number;
  
    function __construct($data = null)
    {
        $this->data = $data;
    } // end of constructor
    /**BULK UPLOAD FRONTEND METHOD
     * @param $cir [<csv_import_reader Object >]
     * @param $[filecolumns] [<colums fields in csv form>]
     * @param array $[formdata] [<data in the csv>]
     * .
     **/
    public function course_skill_upload($cir, $filecolumns, $formdata)
    {
       
        global $DB, $USER, $CFG;        
        $linenum = 1;
        $mandatory_fields = ['course_code', 'skillcategory', 'skills'];
        while ($line = $cir->next()) {
            $linenum++;
            $user = new \stdClass();
            foreach ($line as $keynum => $value) {
                if (!isset($filecolumns[$keynum])) {
                    // this should not happen
                    continue;
                }
                $key = $filecolumns[$keynum];
                $user->$key = trim($value);
            }
            $this->data[] = $user;
            $this->errors = array();
            $this->warnings = array();
            $this->mfields = array();
            $this->wmfields = array();
            $this->excel_line_number = $linenum;

            foreach ($mandatory_fields as $field) {
               $error = $this->mandatory_field_validation($user, $field);
            }

            // validation for skillcategory
            if (!empty($user->skillcategory)) {
                $error = $this->skillcategory_validation($user);
            }

            // validation for course code
            if (!empty($user->course_code)) {
                $error = $this->course_code_validation($user);
            }

            //skills validation
            if (!empty($user->skills)) {
                $error = $this->skills_validation($user);
            }
            // write_error_db for insertion
            if (empty($error)) {
                $this->upload_course_skills($user);
            } 
        }

        if ($this->data) {
            $upload_info =  '<div class="critera_error1"><h3 style="text-decoration: underline;">' . get_string('empfile_syncstatus', 'local_skill') . '</h3>';

            $upload_info .= '<div class=local_users_sync_success>' . get_string('updatedusers_msg', 'local_skill', $this->updatedcount) . '</div>';
            $upload_info .= '<div class=local_users_sync_error>' . get_string('errorscount_msg', 'local_skill', $this->errorcount) . '</div>
            </div>';
            
            $button = html_writer::tag('button', get_string('button', 'local_users'), array('class' => 'btn btn-primary'));
            $link = html_writer::tag('a', $button, array('href' => $CFG->wwwroot . '/local/courses/courses.php'));
            $upload_info .= '<div class="w-full pull-left text-xs-center">' . $link . '</div>';
            mtrace($upload_info);
           
        } else {
            echo '<div class="critera_error">' . get_string('filenotavailable', 'local_skill') . '</div>';
        }
    }


    //validation for mandatory missing fields
    function mandatory_field_validation($user, $field)
    {
        if (empty(trim($user->$field))) {
            $strings = new stdClass;
            $strings->field = $field;
            $strings->linenumber = $this->excel_line_number;
            echo '<div class=local_users_sync_error>Missing field of "' . $field . '" uploaded excelsheet at line ' . $this->excel_line_number . '</div>';
            $this->errors[] = 'please enter a valid fields in excel sheet at line ' . $this->excel_line_number . '.';
            $this->mfields[] = $field;
            $this->errorcount++;
        }
        return $this->errors;
    }


    //Upload skill to courses - insertion
    function upload_course_skills($excel)
    {
        
        global $DB, $USER;
        $mandatory_fields = ['course_code', 'skillcategory', 'skills'];
        $excel->course_code = trim($excel->course_code);
        $data = new \stdclass();
        $coursesql = "SELECT * FROM {course} WHERE shortname='$excel->course_code'";
        $sql = $DB->get_record_sql($coursesql);
        
        $data->id = $sql->id;
        $data->course_code = $sql->shortname;
        $skil = explode(',', $excel->skills);
        $skillid = array();
        foreach ($skil as $skillvalue) {
            $skillid[] = $DB->get_field('local_skill', 'id', array('name' => trim($skillvalue)));
        }
        $skillids = implode(',', array_filter($skillid));
        //$skillids = implode(',', $skillid);
        $data->open_skill = $skillids;
        $skillcategory = $DB->get_field('local_skill_categories', 'id', array('name' => trim($excel->skillcategory)));
        $data->open_skillcategory = $skillcategory;
        $data->mandatory_fields = $mandatory_fields;
       
        $DB->update_record('course', $data);
        echo "<div class='alert alert-success'>Skills for course " . $sql->fullname . " succcessfully updated in system</div>";
        $this->updatedcount++;       
    }

    //validation for skill category
    function skillcategory_validation($excel)
    {
        global $DB, $USER;
        $excel->skillcategory = trim($excel->skillcategory);
        //$skillcat = $DB->get_record('local_skill_categories', array('name' => $excel->skillcategory));
        $sql = "SELECT * FROM {local_skill_categories}
                    WHERE " . $DB->sql_equal('name', ':name', false, true) . "";
        $params = array(
            'name' => $excel->skillcategory,
        );
        $skillcat = $DB->get_record_sql($sql, $params, IGNORE_MULTIPLE);
        if (strtolower($skillcat->name) != strtolower($excel->skillcategory)) {
            echo '<div class=local_users_sync_error>This data is not existed "' . $excel->skillcategory . '" of uploaded excelsheet at line ' . $this->excel_line_number . '</div>';
            $this->errors[] = 'skillcategory' . $excel->skillcategory . ' in excel sheet at line ' . $this->excel_line_number . '.';
            $this->mfields[] = 'skillcategory';
            $this->errorcount++;
        }
        return $this->errors;
    } 


    // course_code  validation
    function course_code_validation($excel)
    {
        global $DB, $USER;
        $excel->course_code = trim($excel->course_code);
        $courses = $DB->get_record('course', array('shortname' => $excel->course_code));
        
        if ($excel->course_code != $courses->shortname) {
            echo '<div class=local_users_sync_error>This course_code is not exist "' . $excel->course_code  . '" of uploaded excelsheet at line ' . $this->excel_line_number . '</div>';
            $this->errors[] = 'Invalid course_code' . $excel->course_code  . ' in excel sheet at line ' . $this->excel_line_number . '.';
            $this->mfields[] = 'course_code';
            $this->errorcount++;
        }
        return $this->errors;
    } 


    //skills validation
    function skills_validation($excel)
    {
        global $DB, $USER;
        $skills = explode(',', $excel->skills);
        foreach ($skills as $skill) {
            $skill = trim($skill);
           // $validskills = $DB->get_record('local_skill', array('name' => $skill));
           /*  $sql = "SELECT * FROM {local_skill}
                    WHERE " . $DB->sql_equal('name', ':name', false, true) . "";
            $params = array(
                'name' => $skill,
            );
            $validskills = $DB->get_record_sql($sql, $params, IGNORE_MULTIPLE); */

            $validskills = $DB->get_record_sql("SELECT * FROM {local_skill} WHERE " . $DB->sql_equal('name', ':name', false, true) . "", array('name' => $skill));
            
            if (strtolower($skill) != strtolower($validskills->name)) {
                echo '<div class=local_users_sync_error>Invalid skill "' . $skill . '" in "' . $excel->skills. '" of uploaded excelsheet at line ' . $this->excel_line_number . '</div>';
                $this->errors[] = 'Invalid skill ' . $skill. ' in skills ' . $excel->skills . ' in excel sheet at line ' . $this->excel_line_number . '.';
                $this->mfields[] = 'skills';
                $this->errorcount++;
            }
            //$skillcatid = $DB->get_field('local_skill_categories', 'id', array('name' => trim($excel->skillcategory)));
            
            $skillcatid = $DB->get_field_sql("SELECT id FROM {local_skill_categories} WHERE " . $DB->sql_equal('name', ':name', false, true) ."" , array('name' => trim($excel->skillcategory)));
            
            $validskill = $DB->get_record_sql("SELECT id FROM {local_skill} WHERE category=:category and " .$DB->sql_equal('name', ':name', false, true) ."", array('category' => $skillcatid, 'name' => $skill));
            if (!$validskill) {  
                echo '<div class=local_users_sync_error>Invalid skills "' . $skill . '"  under skillcategory "' . $excel->skillcategory . '" of uploaded excelsheet at line ' . $this->excel_line_number . '</div>';
                $this->errors[] = 'Invalid skills' . $skill  . ' under skillcategory "' . $excel->skillcategory . '" in excel sheet at line ' . $this->excel_line_number . '.';
                $this->mfields[] = 'skills';
                $this->errorcount++;
            }           
        }
        return $this->errors;
    } 
}
