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
class skillupload
{
    private $data;
    //-------To hold error messages
    private $errors = array();
    //----To hold error field name
    private $mfields = array();

    private $errorcount=0;

    private $updatedskillcount = 0;

    private $updatedcatgcount  = 0;
  
    //-----It holds the unique username
    private $excel_line_number;

    
    function __construct($data = null)
    {
        $this->data = $data;
    } 
  
    public function bulk_skill_upload($cir, $filecolumns, $formdata)
    {       
        global $DB, $USER, $CFG;
      
        $linenum = 1;
        while ($line = $cir->next()) {
            $linenum++;
            $skill = new \stdClass();
            foreach ($line as $keynum => $value) {
                if (!isset($filecolumns[$keynum])) {
                    // this should not happen
                    continue;
                }
                $key = $filecolumns[$keynum];
                $skill->$key = trim($value);
            }
            $this->data[] = $skill;
            $this->errors = array();
            $this->warnings = array();
            $this->mfields = array();
            $this->wmfields = array();
            $this->excel_line_number = $linenum;
            $mandatory_fields = ['skillcategory', 'skills'];
            foreach ($mandatory_fields as $field) {
                //mandatory field validation               
                $error = $this->mandatory_field_validation($skill, $field);
            }

            if(empty($error)){
                $this->upload_skills($skill);
            }          
        }

        if ($this->data) {
            $upload_info =  '<div class="critera_error1"><h3 style="text-decoration: underline;">' . get_string('empfile_syncstatus', 'local_skill') . '</h3>';
            $upload_info .= '<div class=local_users_sync_success>' . get_string('updatedskillcatg_msg', 'local_skill', $this->updatedcatgcount) . '</div>';
            
            $upload_info .= '<div class=local_users_sync_success>' . get_string('updatedskill_msg', 'local_skill', $this->updatedskillcount) . '</div>';
            $upload_info .= '<div class=local_users_sync_error>' . get_string('errorscount_msg', 'local_skill', $this->errorcount) . '</div></div>';

            mtrace($upload_info);
                     
        } else {
            echo '<div class="critera_error">' . get_string('filenotavailable', 'local_skill') . '</div>';
        }
    }


    //validation for mandatory missing fields
    function mandatory_field_validation($skill, $field)
    {        
        if (empty(trim($skill->$field))) {
            $strings = new stdClass;
            $strings->field = $field;
            $strings->linenumber = $this->excel_line_number;
            echo '<div class=local_users_sync_error>Missing field value for "' . $field . '" at line ' . $this->excel_line_number . '</div>';
            $this->errors[] = 'please enter a valid fields in excel sheet at line ' . $this->excel_line_number . '.';
            $this->mfields[] = $field;
            $this->errorcount++;
        }
        return $this->errors;
    }


    //upload_skills for insertion
    function upload_skills($excel)
    {
        global $DB, $USER;
        $categoryinsert  = new \local_skillrepository\event\insertcategory();
        $catgshortname = str_replace(' ', '',trim($excel->skillcategory)); 
        $catginsert = $skilinsert = false;
        //$skillcategory = $DB->get_record('local_skill_categories', array('name' => trim($excel->skillcategory)),'*');
        $skillcategory = $DB->get_record_sql("SELECT * FROM {local_skill_categories} WHERE " . $DB->sql_equal('name', ':name', false, true) ."" , array('name' => trim($excel->skillcategory)));
            
        $costcenter=$DB->get_field('user','open_costcenterid',array('id'=>$USER->id));

        if (strtolower($skillcategory->name) != strtolower(trim($excel->skillcategory))) {
            $skilcatg = new stdClass;
            $skilcatg->id = 0;
            $skilcatg->costcenterid = $costcenter;
            $skilcatg->parentid = 0;
            $skilcatg->shortname = $catgshortname; 
            $skilcatg->name = trim($excel->skillcategory); 
            $catginsert = $categoryinsert->create_skill_category($skilcatg);
            if($catginsert){
                $this->updatedcatgcount++; 
                //$categoryid = $DB->get_field('local_skill_categories','id',array('name' => $excel->skillcategory));
                $categoryid = $DB->get_field_sql("SELECT id FROM {local_skill_categories} WHERE " . $DB->sql_equal('name', ':name', false, true) ."" , array('name' => trim($excel->skillcategory)));
            
                $skilinsert = $this->insertskills($excel->skills,$categoryid,$costcenter);
            }            
        }else{  

            $skilinsert = $this->insertskills($excel->skills,$skillcategory->id,$costcenter);           
        }     
         
    }

    function insertskills($excelskills,$skillcategoryid,$costcenter)
    {
        $skills = explode(',', $excelskills);
        if(count($skills) > 1){
            foreach ($skills as $skillvalue) {
                $this->skill_insertion($skillvalue,$skillcategoryid,$costcenter);
            }
        }else{
          $this->skill_insertion($excelskills,$skillcategoryid,$costcenter);
        }
       
    }

    function skill_insertion($skillval,$skillcategoryid,$costcenter){
        global $DB;
        $skillinsert  = new \local_skillrepository\event\insertrepository();          
        $skillshortname = str_replace(' ', '', trim($skillval));
        //$skill = $DB->get_record('local_skill', array('name' => trim($skillval),'category' => $skillcategoryid),'*');
        $skill = $DB->get_record_sql("SELECT * FROM {local_skill} WHERE  category=:category and " . $DB->sql_equal('name', ':name', false, true) . "", array('category' => $skillcategoryid , 'name' => trim($skillval)));
       
        if (strtolower(trim($skillval)) != strtolower($skill->name)) { 
            $skil = new stdClass;
            $skil->category = $skillcategoryid;
            $skil->costcenterid = $costcenter;
            $skil->parentid = 0;
            $skil->shortname =  $skillshortname; 
            $skil->name = trim($skillval); 
            $skillinsert->skillrepository_opertaions('local_skill','insert', $skil,'','');
            $this->updatedskillcount++;         
        }
    }
   
}
