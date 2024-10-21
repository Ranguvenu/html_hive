<?php


class block_elearning_renderer extends plugin_renderer_base {

 public function get_elearning_content(){
	  global $CFG, $USER, $DB, $OUTPUT, $PAGE;
       $data = '';
       $data = html_writer:: start_tag('div', array('class'=>'link_container'));
	   $url = new moodle_url('/blocks/manage/allcourses.php');
	   //$data .= html_writer:: link($url,get_string('global_search', 'block_elearning'), array());
				
				$url = new moodle_url('/blocks/manage/allcourses.php');
				$searchimgurl = $OUTPUT->pix_url('i/search');
				$data .= "<div id='courses_searchform'>
														<form action=$url method='get'>
															<input id='global_search_text' type='text' name='g_search' placeholder='".get_string('global_search', 'block_elearning')."'>
															<input type='image' id='global_search' src='".$searchimgurl."' alt='".get_string('search')."'/>
														</form>
													</div>";
       //$data .= html_writer::tag('h4', 'NPS');
       //$creditvalue= get_totalcredits();
	   //$creditvalue= "Calculate here";
       //$data .= html_writer::tag('span', $creditvalue);
       $data .= html_writer:: end_tag('div');
	 return $data;
 }
}
