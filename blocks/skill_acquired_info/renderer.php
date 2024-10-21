<?php

global $CFG;
require_once($CFG->dirroot . '/blocks/learning_plan/renderer.php');

class block_skill_acquired_info_renderer extends plugin_renderer_base {   
	
	 
	 
	function display_skill_acquired_info($userid){
        global $DB;
        $sql = "SELECT c.id, c.fullname, lcd.grade, lcd.proficiencylevel, ls.name, cc.timecompleted  FROM
              {course_completions} cc
              JOIN {course} c
              ON cc.course = c.id
              JOIN {local_coursedetails} lcd
              ON cc.course = lcd.courseid
              JOIN {local_skill} ls
              ON lcd.skill = ls.id
              WHERE cc.userid = $userid AND cc.timecompleted IS NOT NULL";
        $skill_acquireds = $DB->get_records_sql($sql);
        if($skill_acquireds){
            $data = array();
            foreach($skill_acquireds as $skill_acquired){
                $row = array();
                $row[] = $skill_acquired->name;
				$row[] = $skill_acquired->fullname;
                if($skill_acquired->proficiencylevel == 1){
                    $proficiency_level = get_string('beginners', 'block_skill_acquired_info');
                }elseif($skill_acquired->proficiencylevel == 2){
                    $proficiency_level = get_string('intermediate', 'block_skill_acquired_info');
                }else{
                    $proficiency_level = get_string('advanced', 'block_skill_acquired_info');
                }
                $row[] = $proficiency_level;
                $date = date('d M Y', $skill_acquired->timecompleted);
                $row[] = $date;
                $data[] = $row;
            }
            $table = new html_table();
            $table->id = 'skill_acquired_info';
            $table->head = array(get_string('skill', 'block_skill_acquired_info'),
								 get_string('course'),
								 get_string('proficiency_level', 'block_skill_acquired_info'),
								 get_string('date_acquired', 'block_skill_acquired_info'));
            $table->align = array('left', 'center', 'center', 'center');
            $table->data = $data;
            $mytable = html_writer::table($table);
          
        }else{
            $mytable = html_writer::tag('div', get_string('norecords','block_skill_acquired_info'), array('class'=>'emptymsg'));
        }
		return $mytable;
		  
	}

}
