<?php


require_once($CFG->dirroot . '/blocks/nps/lib.php');
class block_nps_renderer extends plugin_renderer_base {

 public function get_nps_credits(){
			//  global $CFG, $USER, $DB, $OUTPUT, $PAGE;
			$data = '';
			$data = html_writer:: start_tag('div', array('id'=>'nps_count', 'class'=>'nps_count'));
			$data .= html_writer::tag('h4', get_string('pluginname', 'block_nps'));
			$creditvalue= get_totalcredits();
	   
			$credit=round($creditvalue);
			
			//$credits = html_writer::link(new moodle_url('/blocks/nps/nps_info.php'), $credit, array('class' => 'credit'));
			$data .= html_writer::tag('span', $credit,array('id'=>'nps_credits', 'class'=>'nps_credits'));
			$data .= html_writer:: end_tag('div');
			
			return $data;
 }
	
		function nps_view(){
				global $DB;
				$npstable = new html_table();
				$npstable->id='npsinfo';
				$nps= nps_get_data();
				if($nps){
						$npstable->head = array('Course Name ','ILT Name', 'NPS' );
						foreach($nps as $records){
								$id = $records->id;
								$coursename = $records->fullname;
								$iltname = $records->name;
								$nps = $records->nps;
								$npstable->data[] = array($coursename,$iltname,$nps);		
						}
								return html_writer::table($npstable);
				}else{
						return html_writer::tag('div', get_string('emptymsg','block_nps'), array('id'=>'emptymsg'));
				}
		}
}
