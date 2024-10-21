<?php
global $CFG,$USER, $DB, $PAGE, $OUTPUT;



?>
<?php
class block_course_info extends block_base {
    public function init() {
        $this->title = get_string('pluginname', 'block_course_info');
    }
    public function get_content() {
    if ($this->content !== null) {
      return $this->content;

    }
        global $DB,$OUTPUT,$COURSE,$CFG;
        $id=$COURSE->id;
		
        $iconurl = $CFG->wwwroot.('/blocks/course_info/pix/fighter-jet.png');
        $mycrsesicon = html_writer::img($iconurl, 'my courses',array('width'=>'10px','height'=>'10px'));
    
        $course = $DB->get_record('course', array('id'=>$id));
        $course_category = $DB->get_field('course_categories', 'name', array('id'=>$course->category));
		$coursedetails = $DB->get_record('local_coursedetails', array('courseid'=>$id));
		$level = $DB->get_field('local_levels', 'name',array('id'=>$coursedetails->level));
        if(empty($level)){
            $level = "NA";
        }
        $credits = $DB->get_field('local_coursedetails','credits', array('courseid'=>$id));
        
		
		
        $course_category_r=html_writer::tag('b' ,$course_category);
       
        $credits_r=html_writer::tag('b',$credits);
        $coursedetails_fes=html_writer::tag('b',$coursedetails->facilitator_credits);
		
        $level_r=html_writer::tag('b',$level);
		
	
	      if(empty($coursedetails->grade)){
			  $coursedetails_r = get_string('all');
			}elseif($coursedetails->grade == -1){
			  $coursedetails_r = get_string('all');
			}else{
			  $coursedetails_r = $coursedetails->grade;
			}
		
        $this->content =  new stdClass;
        $this->content->text=array();
        $this->content->text[]=html_writer::tag('p', $mycrsesicon.' Category: '.$course_category_r);
       
        $this->content->text[]=html_writer::tag('p',$mycrsesicon.' Credits: '.$credits_r);
        $this->content->text[]=html_writer::tag('p',$mycrsesicon.' Facilitator Credits: '.$coursedetails_fes);
        $this->content->text[]=html_writer::tag('p', $mycrsesicon.' Level: '.$level_r);
        $this->content->text[]=html_writer::tag('p',$mycrsesicon.' Grade: '.$coursedetails_r);

	
    
    
    $this->content->text=implode('',$this->content->text);//The implode() function returns a string from the elements of an array.
    return $this->content;

}
}