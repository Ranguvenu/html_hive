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
 * Private doselect module utility functions
 *
 * @package mod_doselect
 * @copyright  2019 Anilkumar Cheguri (anil@eabyas.in)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once("$CFG->libdir/filelib.php");
require_once("$CFG->libdir/resourcelib.php");
require_once("$CFG->dirroot/mod/doselect/lib.php");


    /**
     * File browsing support class
     */
    class doselect_content_file_info extends file_info_stored {
        public function get_parent() {
            if ($this->lf->get_filepath() === '/' and $this->lf->get_filename() === '.') {
                return $this->browser->get_file_info($this->context);
            }
            return parent::get_parent();
        }
        public function get_visible_name() {
            if ($this->lf->get_filepath() === '/' and $this->lf->get_filename() === '.') {
                return $this->topvisiblename;
            }
            return parent::get_visible_name();
        }
    }

    function doselect_get_editor_options($context) {
        global $CFG;
        return array('subdirs'=>1, 'maxbytes'=>$CFG->maxbytes, 'maxfiles'=>-1, 'changeformat'=>1, 'context'=>$context, 'noclean'=>1, 'trusttext'=>0);
    }


    /**
     * @return array int => lang string the options for calculating the doselect grade
     *      from the individual attempt grades.
     */
    function doselect_get_grading_options() {
        return array(
            DOSELECT_GRADEHIGHEST => get_string('gradehighest', 'quiz'),
            DOSELECT_GRADEAVERAGE => get_string('gradeaverage', 'quiz'),
            DOSELECT_ATTEMPTFIRST => get_string('attemptfirst', 'quiz'),
            DOSELECT_ATTEMPTLAST  => get_string('attemptlast', 'quiz')
        );
    }

    function doselect_attempts_table($id){
        global $DB,$USER;

        $sql= "SELECT da.* 
                FROM {doselect_attempts} da 
                JOIN {doselect} d ON d.id = da.doselectid AND 
                da.userid = {$USER->id} AND da.doselectid = {$id}
                GROUP BY da.timestart 
                ORDER BY da.timecreated ASC";
        $records = $DB->get_records_sql($sql);
        $table = new html_table();
        $table->width='100%'; 
        $table->head = array('Attempts','Marks','Time start','Time taken');
        $table->align = array('center','center','center','center');
        if($records){
            $attemptno = 1;
            foreach ($records as $record) {
                $attempt = $attemptno;
                $marks = $record->total_score;
                $started_at = explode('T', $record->timestart);
                $started_time = explode('.', $started_at[1]);
                

                $starteddate = $started_at[0];
                
                $starttime = $starteddate.' '.$started_time[0];
                $timetaken = round($record->time_taken/60, 2). " Min";

                $table->data[] = array($attempt,$marks,$starttime,$timetaken);

                $attemptno++;
            }
        }
        echo html_writer::table($table);
    }



