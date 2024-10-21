<?php

namespace local_courses\completionupload;
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
class coursecompletion_upload  {
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

    protected $allowedcolumns = array('course_code','timecompleted','email'); 
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
        $uploadid = csv_import_reader::get_new_iid('coursecompletionupload');
        $cir = new csv_import_reader($uploadid, 'coursecompletionupload');
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
           $return .= html_writer::start_tag('div', array('class'=> 'w-100 mt-5')).$OUTPUT->continue_button(new moodle_url('/local/courses/completionupload/course_completion_upload.php')).html_writer::end_tag('div');
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
            } else {
                $shortname =  trim($masterdata->course_code);
                $email =  trim($masterdata->email);

                $master = new \stdClass();
                $coursecode = $DB->get_record_sql("SELECT * FROM {course} WHERE shortname = '$shortname' ");
                $userinfo = $DB->get_record_sql("SELECT * FROM {user} WHERE email = '$email' ");
                $master->id = $coursecode->id;
                $master->userid = $userinfo->id;
                $master->timecompleted = trim(strtotime($masterdata->timecompleted));
                $get_record = $DB->get_record_sql("SELECT cc.* 
                    FROM {course_completions} cc
                    WHERE cc.userid = '$master->userid' AND cc.course = '$master->id' ");
                if ($get_record) {
                    $master->id = $get_record->id;
                    $DB->execute("UPDATE {course_completions} SET timecompleted = NULL WHERE id = '$get_record->id'");
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
        if (!in_array('course_code', $this->columnsmapping)) {
            $this->errors[] = get_string('course_code_missing', 'local_courses');
            return  $this->errors;
        }
        if (!in_array('timecompleted', $this->columnsmapping)) {
            $this->errors[] = get_string('timecompleted_missing', 'local_courses');
            return  $this->errors;
        }
        if (!in_array('email', $this->columnsmapping)) {
            $this->errors[] = get_string('email_missing', 'local_courses');
            return  $this->errors;
        }
        return  false;
    }
    public function required_fields_validations($excel,$option=0) {
        global $DB;
        $strings = new stdClass;
        $strings->excel_line_number = $this->excel_line_number;
        if (array_key_exists('course_code', (array)$excel) ) {
            if (empty(trim($excel->course_code))) {
                echo '<div class="">'.get_string('course_code_missing','local_courses', $strings).'</div>'; 
                $this->errors[] =  get_string('course_code_missing', 'local_courses',$strings);
                $this->mfields[] = 'course_code';
                $this->errorcount++;
            } else if(!empty(trim($excel->course_code))) {
                $course = trim($excel->course_code);
                $record = $DB->record_exists_sql("SELECT id FROM {course} WHERE shortname = '$course' ");
                if(!$record) {
                    echo '<div class="sync_error">'.get_string('course_code_notexist','local_courses', $strings).'</div>'; 
                    $this->errors[] =  get_string('course_code_notexist', 'local_courses',$strings);
                    $this->mfields[] = 'shortname';
                    $this->errorcount++;
                }
            }
        }

        if (array_key_exists('email', (array)$excel) ) {
            if (empty(trim($excel->email))) {
                echo '<div class="">'.get_string('email_missing','local_courses', $strings).'</div>'; 
                $this->errors[] =  get_string('email_missing', 'local_courses',$strings);
                $this->mfields[] = 'email';
                $this->errorcount++;
            } else if(!empty(trim($excel->email))) {
                $email = trim($excel->email);
                $record = $DB->record_exists_sql(" SELECT id FROM {user} WHERE email = '$email' ");
                if(!$record) {
                    echo '<div class="sync_error">'.get_string('email_notexist','local_courses', $strings).'</div>'; 
                    $this->errors[] =  get_string('email_notexist', 'local_courses',$strings);
                    $this->mfields[] = 'email';
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
    public function get_coursecompletion_file($draftid) {
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
