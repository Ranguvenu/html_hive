<?php
//=======completed courses info==========
function get_courses_info($data){
	global $DB, $USER;
	
	$value = ilp_startend_dates();
	/* LEFT OUTER JOIN {grade_items} gr ON gr.courseid = c.id
	LEFT OUTER JOIN {grade_grades} gd ON gr.id = gd.itemid  */  
	$sql="SELECT c.id,c.fullname,c.open_points,c.open_identifiedas,
		cat.name as category, ct.course_type as moduletype,ct.shortname as moduletypeshort, cc.timecompleted, cp.course_provider ,c.open_grade,
		(SELECT GROUP_CONCAT({local_skill}.name) 
                        FROM mdl_local_skill WHERE instr(concat(' ,',c.open_skill,','),concat(',',{local_skill}.id,',')) > 1
                        ) as skill,gd.finalgrade as score,
		(SELECT name  FROM {local_skill_categories} sc WHERE c.open_skillcategory = sc.id) as skillcategory,
		(SELECT MAX(ue.timecreated)  FROM {user_enrolments} ue LEFT JOIN {enrol} e ON (ue.enrolid = e.id)
		WHERE c.id = e.courseid) AS enroleddate
		FROM {course} AS c
		JOIN {course_categories} AS cat ON cat.id = c.category
		JOIN {course_completions} as cc ON cc.course=c.id
		JOIN {local_course_types} ct ON ct.id = c.open_identifiedas   
		JOIN {local_course_providers} cp ON cp.id = c.open_courseprovider   
		LEFT OUTER JOIN {grade_items} gr ON gr.courseid = c.id
		LEFT OUTER JOIN {grade_grades} gd ON gr.id = gd.itemid              
		WHERE cc.userid = $USER->id AND ct.shortname NOT IN ('ilt','learningpath') ";

	if($data){
		$sql .= "AND cc.timecompleted >= $value->startdate AND cc.timecompleted <= $value->enddate ";
	}
	$sql .= " AND cc.timecompleted IS NOT NULL ORDER BY cc.timecompleted ASC ";
	
	$mycourses = $DB->get_records_sql($sql);

	$sql = "SELECT lp.id, lp.name, lp.open_points, 'Learning Path' as moduletype,'learning_path' as moduletypeshort,null as course_provider,
			lpu.completiondate as timecompleted,lpu.timecreated AS enroleddate
			FROM {local_learningplan_user} lpu
			JOIN {local_learningplan} lp ON lp.id = lpu.planid
			WHERE lpu.userid = $USER->id AND lla.completiondate is NOT NULL ";

	if($data){
		$sql .= " AND lpu.completiondate >= $value->startdate AND lpu.completiondate <= $value->enddate "; 
	}

	$sql .= " AND lpu.status = 1";

    $lpaths = $DB->get_records_sql($sql);

    $results = $mycourses + $lpaths;

	return $results;
}
function get_mycourses_credits_sum($data){
	global $DB, $USER;

    $value = ilp_startend_dates();

	$sql = "SELECT sum(c.open_points) AS total
			FROM {course} AS c
			JOIN {course_completions} as cc ON cc.course=c.id
			WHERE cc.userid = $USER->id AND c.open_identifiedas IN (1,2,3) " ;

	if($data){
		$sql .= "AND cc.timecompleted >= $value->startdate AND cc.timecompleted <= $value->enddate AND cc.timecompleted IS NOT NULL"; 
	}else{
		$sql .= " AND cc.timecompleted IS NOT NULL";
	}
	
	$mycredits = $DB->get_record_sql($sql);

	$sql = "SELECT sum(lp.open_points) AS total
			FROM {local_learningplan_user} lpu
			JOIN {local_learningplan} lp ON lp.id = lpu.planid
			WHERE lpu.userid = $USER->id ";

	if($data){
		$sql .= "AND lpu.completiondate >= $value->startdate AND lpu.completiondate <= $value->enddate"; 
	}

	$sql .= " AND lpu.status = 1 ";

    $lpathcredits = $DB->get_record_sql($sql);
	
	$return = round($mycredits->total + $lpathcredits->total, 2);

	return $return;
}

//=======facilitator info=============
function get_facilitator_info($data){
	global $DB,$USER;
	$value=ilp_startend_dates();
	$sql="SELECT cf.id, c.id AS courseid, c.fullname, cf.percentage, cf.contenttype, c.open_identifiedas, cf.timecreated
			FROM {course} as c
			JOIN {course_facilitator} as cf ON c.id=cf.courseid
			WHERE cf.employeeid = $USER->id " ;
    if($data){
		$sql .= "AND cf.timecreated >= $value->startdate AND cf.timecreated <= $value->enddate ";
	}else{
		//$sql .= "AND  cf.timecreated NOT BETWEEN $value[0] AND $end_date";
		$sql .= " AND cc.timecompleted IS NOT NULL";
		
	}
	$myfacilitators = $DB->get_records_sql($sql);
	return $myfacilitators;
}

function insert_ilp_startend_dates($fromform){
	global $DB;
	$update_data=$DB->get_field('config','name',array('id'=>$fromform->id));
	if($update_data=='ilp'){
		$record = new stdClass();
		$record->id=$fromform->id;
		$record->name="ilp";
		$values=array();
		$values[]=$fromform->ilp_start;
		$values[]=$fromform->ilp_end;
		$record->value=implode(',',$values);
		$update = $DB->update_record('config', $record);	
	}
	return true;
}
function get_ilp_strartend_records(){
        global $DB;
        $rec= $DB->get_records_sql( "select * from {config} where name='ilp' ");
        return $rec;
}

function edit_ilpdates($id){
	global $DB;
		$edit= $DB->get_record_sql( "select * from {config} where name='ilp' and id=$id ");
			$id=$edit->id;
			$value=array();
			$value=explode(',',$edit->value);
			$ilt_start=$value[0];
			$ilt_end=$value[1];
			$edit->ilp_start=$ilt_start;
			$edit->ilp_end=$ilt_end;
		return $edit;
}

function ilp_startend_dates(){
    global $DB;
	$ilp=$DB->get_record_sql("select * from {config} where name='ilp' ");
	$value = explode(',',$ilp->value);
	
	$date = new DateTime;
	$date->setTimestamp($value[1]);
	$date->setTime( 23, 59, 59);
    $end_date = $date->getTimestamp();
	
	$requiredates = new stdClass();
	$requiredates->startdate = $value[0];
	$requiredates->enddate = $end_date;
	return $requiredates;
}

function facilitator_credits_sum($data){
	global $DB,$USER;
	$value = ilp_startend_dates();
	$sql="SELECT sum(percentage) AS total 
		FROM {course_facilitator} 
		WHERE employeeid = $USER->id ";
	if($data){
		 $sql .=  "AND timecreated >= $value->startdate AND timecreated <= $value->enddate " ;
	}else{
		$sql .= " AND cc.timecompleted IS NOT NULL";
	}
	$fc_credits_sum = $DB->get_record_sql($sql);
	
	if($fc_credits_sum->total){
		$fc_total = $fc_credits_sum->total;
	}else{
		$fc_total = 0;
	}
	return $fc_total;
} 

function date_filters_form($filterparams){
    global $CFG;
    require_once($CFG->dirroot . '/blocks/empcredits/filters_form.php');
   
    $thisfilters = array('tab_from_date','tab_to_date');

$filterparams['submitid'] = 'form#externalfilteringform';
$formtype = 'externalfilteringform';
$mform = new filters_form(null, array('filterlist'=> $thisfilters, 'filterparams' => $filterparams ,'submitid' => 'empcredits'));
    return $mform;
}

function tab_from_date_filter($mform)
{
    $mform->addElement('date_selector', 'fromdate', get_string('fromdate', 'block_empcredits'), array('optional' => true));
} 

function tab_to_date_filter($mform)
{
    $mform->addElement('date_selector', 'todate', get_string('todate', 'block_empcredits'), array('optional' => true));
}  
