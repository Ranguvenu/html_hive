<?php
require_once($CFG->dirroot . '/blocks/empcredits/lib.php');
function get_totalcredits(){
	global $CFG, $USER, $DB;


		// Training nps score
		$value=ilp_startend_dates();
		$sql="SELECT count(tf.id) promoters FROM {local_trainingfeedback} tf
				JOIN {facetoface} f ON tf.batchid = f.id
				WHERE  tf.score in(9,10) AND f.startdate >= $value->startdate and f.startdate <= $value->enddate ";
		$promoters = $DB->get_field_sql($sql);
		
		$sql = "SELECT count(tf.id) passive FROM {local_trainingfeedback} tf
				JOIN {facetoface} f ON tf.batchid = f.id
				where  tf.score in(7,8) AND f.startdate >= $value->startdate and f.startdate <= $value->enddate ";
		$passive = $DB->get_field_sql($sql);

		$sql = "SELECT count(tf.id) detracto FROM {local_trainingfeedback} tf
				JOIN {facetoface} f ON tf.batchid = f.id
				where score in(0,1,2,3,4,5,6)  AND f.startdate >= $value->startdate and f.startdate <= $value->enddate";
		$detractors = $DB->get_field_sql($sql);
		
		$total = $promoters + $passive + $detractors;
		
		$perpromoters= $promoters/$total;
		$perdetractors= $detractors/$total;

		$nps = (($perpromoters - $perdetractors) * 100);
		
		return $nps;
 }
 
 function training_nps() {
	   global $CFG, $USER, $DB;
	 $sql="SELECT * FROM {course_completions} ft where userid={$USER->id} and timecompleted is not NULL order by userid desc limit 0, 10";
        $courses= $DB->get_records_sql($sql);  
         $user_response=0;
		 $total=0;
		 if(empty($courses)){
			return get_string('nonpsexist','block_nps');
	     }
		 foreach($courses as $course) {   
	   $promoters = $DB->get_field_sql("select avg(ev.value) from {feedback_value} as ev
                                               JOIN {feedback_completed} as ec ON ev.completed=ec.id
                                               JOIN {feedback_item} as ei ON ei.id=ev.item AND ei.typ='multichoicerated'
											   JOIN mdl_feedback f ON ei.feedback=f.id
                                              WHERE f.course={$course->course} and ev.value in(9,10) group by ec.userid ");
	   $passive = $DB->get_field_sql("select avg(ev.value) from {feedback_value} as ev
                                               JOIN {feedback_completed} as ec ON ev.completed=ec.id
                                               JOIN {feedback_item} as ei ON ei.id=ev.item AND ei.typ='multichoicerated'
											  JOIN mdl_feedback f ON ei.feedback=f.id
                                              WHERE f.course={$course->course} and ev.value in(7,8) group by ec.userid") ;
											  
		$passive = $DB->get_field_sql("select avg(ev.value) from {feedback_value} as ev
                                               JOIN {feedback_completed} as ec ON ev.completed=ec.id
                                               JOIN {feedback_item} as ei ON ei.id=ev.item AND ei.typ='multichoicerated'
											   JOIN mdl_feedback f ON ei.feedback=f.id
                                              WHERE f.course={$course->course} and ev.value in(0,1,2,3,4,5,6) group by ec.userid");
											  
			 $sql="SELECT count(id) total FROM {feedback_value} where batchid={$trainerfeedback->batchid} ";
          $total = $DB->get_field_sql("select count(ec.userid) from {feedback_value} as ev
                                               JOIN {feedback_completed} as ec ON ev.completed=ec.id
                                               JOIN {feedback_item} as ei ON ei.id=ev.item AND ei.typ='multichoicerated'
											   JOIN mdl_feedback f ON ei.feedback=f.id
                                              WHERE f.course={$course->course}");
          
            $perpromoters= $promoters/$total;
            $perdetractor= $detractor/$total;
     
            $nps= $perpromoters - $perdetractor;
	
											  
		 }
		 $totaltraining=$user_response+$total;
		 return $totaltraining;
 }
 
function nps_get_data(){
	global $DB, $USER;
	$sql="SELECT n.id, n.userid, n.batchid, n.nps, f.name, c.fullname
		FROM {local_nps} n
		JOIN {facetoface} f ON f.id = n.batchid
		JOIN {local_facetoface_courses} as fc ON fc.batchid = f.id
		JOIN {course} c ON c.id = fc.courseid
		WHERE n.userid=$USER->id";
		
	$nps = $DB->get_records_sql($sql);
	return $nps;
}
