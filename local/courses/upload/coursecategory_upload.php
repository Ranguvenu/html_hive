<?php

namespace local_courses;

defined('MOODLE_INTERNAL') || die;

use context_user;
use csv_import_reader;
use core_text;
use lang_string;
use moodle_exception;
use stdClass;
use html_table;
use html_table_cell;
use html_writer;
use html_table_row;
use moodle_url;
class coursecategory_upload  {
    protected $columns;

    protected $columnsmapping = array();
    protected $_error;
    protected $errors = [];
    protected $mfields = [];
    protected $wmfields = [];
    protected $excel_line_number;
    protected $warnings = [];
    protected $data = [];
    protected $errorcount;

    protected $allowedcolumns = array('course_name','course_code','existing_category','new_category'); 
    public function process_upload_file($file, $defaultcontext) {
        global $CFG, $DB, $PAGE,$USER,$OUTPUT;
    
        require_once($CFG->libdir . '/csvlib.class.php');
        $systemcontext = \context_system::instance();
            
        $content = $file->get_content();
        $filename = $file->get_filename();
        /**
        * Extracting account,lob and role information from CSV
        * and removed it from CSV for uploading
        */
        $content = core_text::convert($content, 'utf-8');
        $content = core_text::trim_utf8_bom($content);
        $content = preg_replace('!\r\n?!', "\n", $content);
        $content = trim($content);
        
        $tempfile = tempnam(make_temp_directory('/csvimport'), 'tmp');
        if (!$fp = fopen($tempfile, 'w+b')) {
            $this->_error = get_string('cannotsavedata', 'error');
            @unlink($tempfile);
            return false;
        }
        fwrite($fp, $content);
        fseek($fp, 0);
        $uploadid = csv_import_reader::get_new_iid('coursecategoryupload');
        $cir = new csv_import_reader($uploadid, 'coursecategoryupload');
        /**
         * Actual upload starts from here
         */
        $readcount = $cir->load_csv_content($content, 'utf-8', 'comma');
        unset($content);
        if (!$readcount ) {
            throw new moodle_exception('csvloaderror', 'error',$PAGE->url,  $cir->get_error());
        }
   
       $this->columns = $cir->get_columns();
     
       $column_validation = $this->validate_columns();
     
       if(is_array($column_validation) && count($column_validation) > 0){
           $string = $column_validation[0];
           $return =  '<div class="sync_error">'.get_string('validsheet','local_courses',$string).'</div>'; 
           $return .= html_writer::start_tag('div', array('class'=> 'w-100 mt-5')).$OUTPUT->continue_button(new moodle_url('/local/courses/course_category_upload.php')).html_writer::end_tag('div');
          echo $return;
          return false;
       }
        foreach ($this->columns as $i => $columnname) {
            $columnnamelower = preg_replace('/ /', '', core_text::strtolower($columnname));
            if (in_array($columnnamelower, $this->allowedcolumns)) {
                $this->columnsmapping[$i] = $columnnamelower;
            } else {
                $this->columnsmapping[] = $columnname;
            }
        }
        $cir->init();
        $rownum = 0;
        $progress = 0;
        $data = array();
        $linenum = 1;   
        $successcreatedcount = 0;
        $errorcount= 0;

        if($readcount <= 1){
            echo'<div class = local_users_sync_error>'.get_string('filenotavailable','local_courses').'</div>';
        }

        while ($row = $cir->next()) {
            $linenum++;
            
            $hash = array();
            $masterdata = new stdClass();
            foreach ($row as $i => $value) {
                if (!isset($this->columnsmapping[$i])) {
                    continue;
                }
                $column=$this->columnsmapping[$i];
                $masterdata->$column = $value;
            }
            $masterdata->excel_line_number=$linenum;  

            $this->data[]=$masterdata;  
            $this->errors = array();
            $this->warnings = array();
            $this->mfields = array();
            $this->wmfields = array();
            $this->excel_line_number = $linenum;
            $stringhelpers = new stdClass();
            $stringhelpers->linenumber = $this->excel_line_number; 

            $this->required_fields_validations($masterdata);
            if (count($this->errors) > 0) { 
               $errorcount++;
            }else{
                $masterdata->fullname = $masterdata->course_name;
                $masterdata->shortname = trim($masterdata->course_code);
                $existing_category = trim($masterdata->existing_category);
                $new_category = trim($masterdata->new_category);
                $new_category = $DB->get_field('course_categories','id',['idnumber' => $new_category]);
                $get_record = $DB->get_record_sql(" SELECT c.* FROM {course} c JOIN {course_categories} cc ON cc.id = c.category
                WHERE c.shortname = '$masterdata->shortname'  AND cc.idnumber = '$existing_category' "); 
                if($get_record) {
                    $get_record->category = $new_category;
                    $DB->update_record('course',$get_record);
                    $successcreatedcount++;
                }
                if ($successcreatedcount > 0) {
                    $success = new \stdClass();
                    $success->count = $successcreatedcount;
                    $return = $OUTPUT->notification(get_string('uploadorgsheet', 'local_courses', $success),'info');
                    echo $return;
                } else {
                    $return = $OUTPUT->notification(get_string('notuploadorgsheet', 'local_courses'),'danger');
                    echo $return;
                }
            }
        }
        
    }

    public function validate_columns() {
        foreach ($this->columns as $i => $columnname) {
           
            if (in_array(strtolower($columnname), $this->allowedcolumns)) {
               
                $this->columnsmapping[$i] = strtolower($columnname);
                
            }
        }
      
        if (!in_array('course_name', $this->columnsmapping)) {
            $this->errors[] = get_string('course_name_missing', 'local_courses');
            return  $this->errors;
        }
        if (!in_array('course_code', $this->columnsmapping)) {
            $this->errors[] = get_string('course_code_missing', 'local_courses');
            return  $this->errors;
        }
        if (!in_array('existing_category', $this->columnsmapping)) {
            $this->errors[] = get_string('existing_category_missing', 'local_courses');
            return  $this->errors;
        }

        if (!in_array('new_category', $this->columnsmapping)) {
            $this->errors[] = get_string('new_category_missing', 'local_courses');
            return  $this->errors;
        }
        return  false;
    }
    public function required_fields_validations($excel,$option=0) {
        global $DB;
        $strings = new stdClass;
        $strings->excel_line_number = $this->excel_line_number;
        if (array_key_exists('course_name', (array)$excel) ) {
            if (empty(trim($excel->course_name))) {
                echo '<div class="">'.get_string('course_name_missing','local_courses', $strings).'</div>'; 
                $this->errors[] =  get_string('course_name_missing', 'local_courses',$strings);
                $this->mfields[] = 'course_name';
                $this->errorcount++;
            }
        }
        if (array_key_exists('course_code', (array)$excel) ) {
            if (empty(trim($excel->course_code))) {
                echo '<div class="">'.get_string('course_code_missing','local_courses', $strings).'</div>'; 
                $this->errors[] =  get_string('course_code_missing', 'local_courses',$strings);
                $this->mfields[] = 'course_code';
                $this->errorcount++;
            } else if(!empty(trim($excel->course_code))) {
                $course_code = trim($excel->course_code);
                $record = $DB->record_exists_sql(" SELECT id FROM {course} WHERE shortname = '$course_code' ") ;
                if(!$record) {
                    echo '<div class="sync_error">'.get_string('course_code_notexist','local_courses', $strings).'</div>'; 
                    $this->errors[] =  get_string('course_code_notexist', 'local_courses',$strings);
                    $this->mfields[] = 'course_code';
                    $this->errorcount++;
                }
            }
        }

        if (array_key_exists('existing_category', (array)$excel) ) {
            if (empty(trim($excel->existing_category))) {
                echo '<div class="">'.get_string('existing_category_missing','local_courses', $strings).'</div>'; 
                $this->errors[] =  get_string('existing_category_missing', 'local_courses',$strings);
                $this->mfields[] = 'existing_category';
                $this->errorcount++;
            } else if(!empty(trim($excel->existing_category))) {
                $existing_category = trim($excel->existing_category);
               
                $course_code = trim($excel->course_code);
                $existcategory = $DB->get_field_sql(" SELECT id FROM {course_categories} WHERE idnumber = '$existing_category' ") ;

                if($existcategory) {
                    $record = $DB->record_exists_sql(" SELECT id FROM {course} WHERE shortname = '$course_code' AND category =  $existcategory ");
                    if(!$record) {
                        echo '<div class="sync_error">'.get_string('categorycode_mismatched','local_courses', $strings).'</div>'; 
                        $this->errors[] =  get_string('categorycode_mismatched', 'local_courses',$strings); 
                        $this->mfields[] = 'existing_category';
                        $this->errorcount++;
                    }
                } else {
                    echo '<div class="sync_error">'.get_string('existing_category_notexist','local_courses', $strings).'</div>'; 
                    $this->errors[] =  get_string('existing_category_notexist', 'local_courses',$strings);
                    $this->mfields[] = 'existing_category';
                    $this->errorcount++;
                }
                
            }
        }

        if (array_key_exists('new_category', (array)$excel) ) {
            if (empty(trim($excel->new_category))) {
                echo '<div class="">'.get_string('new_category_missing','local_courses', $strings).'</div>'; 
                $this->errors[] =  get_string('new_category_missing', 'local_courses',$strings);
                $this->mfields[] = 'new_category';
                $this->errorcount++;
            } else if(!empty(trim($excel->new_category))) {
                $new_category = trim($excel->new_category);
                $record = $DB->record_exists_sql(" SELECT id FROM {course_categories} WHERE idnumber = '$new_category' ") ;
                if(!$record) {
                    echo '<div class="sync_error">'.get_string('new_category_notexist','local_courses', $strings).'</div>'; 
                    $this->errors[] =  get_string('new_category_notexist', 'local_courses',$strings);
                    $this->mfields[] = 'new_category';
                    $this->errorcount++;
                }
            }
        }
        


    }

     /**
     * @method get_coursecategory_file
     * @todo Returns the uploaded file if it is present.
     * @param int $draftid
     * @return stored_file|null
     */
    public function get_coursecategory_file($draftid) {
        global $USER;
        // We can not use moodleform::get_file_content() method because we need the content before the form is validated.
        if (!$draftid) {
            return null;
        }
        $fs = get_file_storage();
        $context = context_user::instance($USER->id);
        if (!$files = $fs->get_area_files($context->id, 'user', 'draft', $draftid, 'id DESC', false)) {
            return null;
        }
        $file = reset($files);

        return $file;
    }

}