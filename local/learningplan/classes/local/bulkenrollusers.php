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
 * Class bulkenrollusers
 *
 * @package    local_learningplan
 * @copyright  2023 Moodle India Information Solutions Pvt Ltd
 * @author     Narendra Patel <narendra.patel@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_learningplan\local;

use csv_import_reader;
use core_text;
use moodle_exception;
use stdClass;
use html_writer;
use moodle_url;
require_once($CFG->libdir . '/enrollib.php');
require_once($CFG->dirroot . '/enrol/locallib.php');
// use masterdata_courseofferings\persistent\settings_courseofferings;
// use local_trainingcourses\persistent\traineestaff;
// use local_trainingcourses\persistent\settings_organization;
// use local_trainingcourses\persistent\settings_organizationtype;
// use local_users\api;
// use local_trainingcourses\api as trainingcourses;
// use local_trainingcourses\persistent\courseorganization;
class bulkenrollusers {

    //protected $offeringid;
    protected $columns;

    protected $columnsmapping = [];

    protected $allowedcolumns = ['employeeid'];

    private $data;

    //-------To hold error messages
    private $errors = [];

    //----To hold error field name
    private $mfields = [];

    //-----To hold warning messages----
    private $warnings = [];

    //-----To hold warning field names-----
    private $wmfields = [];

    //-----To hold error count-----
    private $errorcount = 0;

    //-----To hold warnings count-----
    private $warningscount = 0;

    //-----To hold created count-----
    private $enrolledcount = 0;

    //-----To hold updated count-----
    private $updatedcount = 0;

    private $erroravailableseats = 0;

    private $gendererror = 0;

    private $offeringdateerror = 0;

    private $sessionmapped = 0;
    private $nouser = 0;
    private $roleerror = 0;
    private $errororg = 0;
    private $alreadyenrolledcount = 0;
    private $errornoorg = 0;

    private $excel_line_number;

    /**
     * @method process_upload_file
     * @todo To process the uploaded CSV file and return the data
     * @param stored_file $file
     * @param string $encoding
     * @param string $delimiter
     * @param context $defaultcontext
     * @return array
     */
    public function process_upload_file($file, $defaultcontext,$lpid) {
      
        global $CFG, $DB, $PAGE, $USER, $OUTPUT;
        require_once($CFG->libdir . '/csvlib.class.php');
       
        $content = $file->get_content();
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
            $this->errors = get_string('cannotsavedata', 'error');
            @unlink($tempfile);
            return false;
        }
        fwrite($fp, $content);
        fseek($fp, 0);

        $uploadid = csv_import_reader::get_new_iid('userenroll');
        $cir = new csv_import_reader($uploadid, 'userenroll');

        /**
         * Actual upload starts from here
         */

        $readcount = $cir->load_csv_content($content, 'utf-8', 'comma');
        unset($content);
        if (!$readcount) {
            throw new moodle_exception('csvloaderror', 'error', $PAGE->url,  $cir->get_error());
        }
        $this->columns = $cir->get_columns();
        foreach ($this->columns as $i => $columnname) {
            $columnnamelower = preg_replace('/ /', '', core_text::strtolower($columnname));
            if (in_array($columnnamelower, $this->allowedcolumns)) {
                $this->columnsmapping[$i] = $columnnamelower;
            } else {
                $this->columnsmapping[] = $columnname;
            }
        }
        $cir->init();
        $linenum = 1;
       if($readcount == 1){
            echo '<div class="alert alert-danger">' . get_string(
                'emptymsg',
                'local_trainingcourses',
            ) . '</div>';
       }
        while ($row = $cir->next()) {
            $linenum++;
            $traineedata = new stdClass();
            foreach ($row as $i => $value) {
                if (!isset($this->columnsmapping[$i])) {
                    continue;
                }
                $column = $this->columnsmapping[$i];
                $traineedata->$column = $value;
            }
            $traineedata->excel_line_number = $linenum;
            $this->data[] = $traineedata;
            $this->errors = [];
            $this->warnings = [];
            $this->mfields = [];
            $this->wmfields = [];
            $this->excel_line_number = $linenum;
            $stringhelpers = new stdClass();
            $stringhelpers->linenumber = $this->excel_line_number;
            $this->required_fields_validations($traineedata);
            $employeeid = $traineedata->employeeid;
            $sql = "SELECT * FROM {user} WHERE open_employeeid =  trim('$employeeid') AND deleted = 0 AND suspended = 0 ";
            $userinfo = $DB->get_record_sql($sql);
            $stringsen = new stdClass;
            $stringsen->excel_line_number = $this->excel_line_number;
            $stringsen->employeeid = $userinfo->open_employeeid;
            if(!empty($userinfo)){          
                $userexists = $DB->record_exists_sql("SELECT * FROM {local_learningplan_user} WHERE planid = $lpid AND userid = $userinfo->id");

                if(empty($userexists)){
                    $data = new stdClass;
                    $data->planid = $lpid;
                    $data->userid = $userinfo->id;
                    $data->usercreated = $USER->id;
                    $data->timecreated = time();
                    $data->timemodified = time();
                    $DB->insert_record('local_learningplan_user', $data);
                    $this->enrolledcount++;
                } else {

                    echo '<div class="alert alert-danger">' . get_string('alredyenrolled', 'local_learningplan', $this->excel_line_number) . '</div>';
                    $this->errors[] = get_string('alredyenrolled', 'local_learningplan', $this->excel_line_number);
                    $this->mfields[] = 'employeeid';
                    $this->errorcount++;
                }                            
            }

        }
        $upload_info = '<hr>';
        

        if ($this->enrolledcount > 0) {     
            $upload_info .= '<div class="alert alert-info">' . get_string(
                'addedtrainee_msg',
                'local_learningplan',
                $this->enrolledcount
            ) . '</div>';
        }
        
        if ($this->errorcount > 0) {
            $upload_info .= '<div class="alert alert-info">' . get_string(
                'errorscount_msg',
                'local_learningplan',
                $this->errorcount
            ) . '</div>';
        }
        
        $button = html_writer::tag('button', get_string('continue', 'local_learningplan'), array('class' => 'btn btn-primary'));
        $link = html_writer::tag('a', $button, array('href' => $CFG->wwwroot . '/local/learningplan/plan_view.php?id='.$lpid));

        $upload_info .= '<div class="w-full pull-left text-xs-center">' . $link . '</div>';
        mtrace($upload_info);
    }
    
    private function required_fields_validations($excel) {
        global $DB;
        $excel = (array)$excel;
       
        if (array_key_exists('employeeid', $excel)) {
            if (empty($excel['employeeid'])) {
                $strings = new stdClass;
                $strings->excel_line_number = $this->excel_line_number;
                $strings->column = 'employeeid';
                echo '<div class="alert alert-danger">' . get_string('emptymsg', 'local_learningplan', $strings) . '</div>';
                $this->errors[] = get_string('emptymsg', 'local_learningplan', $strings);
                $this->mfields[] = 'employeeid';
                $this->errorcount++;
            } else {
                $userexists = $DB->record_exists('user', ['open_employeeid' => $excel['employeeid']]);
                if (empty($userexists)) {
                    $strings = new stdClass;
                    $strings->excel_line_number = $this->excel_line_number;
                    $strings->column = 'employeeid';
                    echo '<div class="alert alert-danger">' . get_string('invalidid', 'local_learningplan', $this->excel_line_number) . '</div>';
                    $this->errors[] = get_string('invalidid', 'local_learningplan', $this->excel_line_number);
                    $this->mfields[] = 'employeeid';
                    $this->errorcount++;
                }
            }
        } else {
            echo '<div class="alert alert-danger">' . get_string('error_oldidcolumn', 'local_learningplan') . '</div>';
            $this->errors[] = get_string('error_oldidcolumn', 'local_learningplan');
            $this->errorcount++;
        }
    } // end of required_fields_validations function


    /**
     * @method get_organization_file
     * @todo Returns the uploaded file if it is present.
     * @param int $draftid
     * @return stored_file|null
     */
    public function get_users_file($draftid) {
        global $USER;

        if (!$draftid) {
            return null;
        }
        $fs = get_file_storage();
        $context = \context_user::instance($USER->id);
        if (!$files = $fs->get_area_files($context->id, 'user', 'draft', $draftid, 'id DESC', false)) {
            return null;
        }
        $file = reset($files);

        return $file;
    }

}
