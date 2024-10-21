<?php

/**
 * This file is part of eAbyas
 *
 * Copyright eAbyas Info Solutons Pvt Ltd, India
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author eabyas  <info@eabyas.in>
 * @package BizLMS
 * @subpackage local_courses
 */


defined('MOODLE_INTERNAL') || die();

function local_externalcertificate_output_fragment_edit($args)
{

    global $CFG, $DB;

    $args = (object) $args;
    $context = $args->context;
    $id = $args->id;
    $o = '';
    $formdata = [];

    $o = '';
    if (!empty($args->jsonformdata)) {
        $serialiseddata = $args->jsonformdata;
        parse_str($serialiseddata, $formdata);
    }

    $mform = new local_externalcertificate\form\edit(null, array(), 'post', '', null, true, (array)$formdata);
   

    $mform->set_data($formdata);

    if (!empty($formdata)) {
        // If we were passed non-empty form data we want the mform to call validation functions and show errors.
        $mform->is_validated();
    }
    ob_start();
    $mform->display();
    $o .= ob_get_contents();
    ob_end_clean();
    return $o;
}




// for display reason form in popup.......
function local_externalcertificate_output_fragment_reason_form($args)
{

    global $CFG, $DB;

    $args = (object) $args;
    $context = $args->context;
    $id = $args->id;
    $status = $args->status;
    $o = '';
    $formdata = [];

    $o = '';
    if (!empty($args->jsonformdata)) {
        $serialiseddata = $args->jsonformdata;
        parse_str($serialiseddata, $formdata);
    }

    $params = array(
        'id' => $id,
        'status'=>$status,
        'contextid' => $context
    );
    $mform = new local_externalcertificate\form\reason_form(null, $params, 'post', '', null, true, (array)$formdata);
    $mform->set_data($formdata);
    // print_r($formdata); die;
    if (!empty($args->jsonformdata)) {
        // If we were passed non-empty form data we want the mform to call validation functions and show errors.
        $mform->is_validated();
    }
    ob_start();
    $mform->display();
    $o .= ob_get_contents();
    ob_end_clean();
    return $o;
}

function local_externalcertificate_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = array())
{

    if ($context->contextlevel != CONTEXT_SYSTEM) {
        // send_file_not_found();
        return false;
    }
    // Make sure the filearea is one of those used by the plugin.
    if ($filearea !== 'certificate') {
        return false;
    }

    $itemid = array_shift($args);
    $filename = array_pop($args);

    if (!$args) {
        $filepath = '/';
    } else {
        $filepath = '/' . implode('/', $args) . '/';
    }

    $filedata = get_file_storage();
    $file = $filedata->get_file($context->id, 'local_externalcertificate', $filearea, $itemid, $filepath, $filename);

    if (!$file) {
        return false;
    }

    send_stored_file($file, 86400, 0, $forcedownload, $options);
}


function img_path2($itemid = 0)
{
    global $DB;
    
    if ($itemid > 0) {
        // code...
        $sql = "SELECT * FROM {files} WHERE itemid = :certificate AND component = 'local_externalcertificate' AND filearea = 'certificate' AND filename != '.' ORDER BY id DESC";
        $imgdata = $DB->get_record_sql($sql, array('certificate' => $itemid), 1);
    }


    if (!empty($imgdata)) {
        // code...
        $imgurl = moodle_url::make_pluginfile_url($imgdata->contextid, $imgdata->component, $imgdata->filearea, $imgdata->itemid, $imgdata->filepath, $imgdata->filename);

        $imgurl = $imgurl->out();
    } else {
        return false;
    }

    return $imgurl;
}

// add form in left menu
function local_externalcertificate_leftmenunode()
{
    $extcertnode = '';
    // if(is_siteadmin() ||(has_capability('local/externalcertificate:manage', $systemcontext)&& has_capability('local/externalcertificate:view', $systemcontext))){
    $extcertnode .= html_writer::start_tag('li', array('id' => 'id_leftmenu_external_certificate', 'class' => 'pull-left user_nav_div external_certificate'));
    $extcert_url = new moodle_url('/local/externalcertificate/index.php');
    $extcert = html_writer::link($extcert_url, '<span class="external_certificates_icon left_menu_icons"></span><span class="user_navigation_link_text">' . get_string('certifications', 'local_externalcertificate') . '</span>', array('class' => 'user_navigation_link'));
    $extcertnode .= $extcert;
    $extcertnode .= html_writer::end_tag('li');
    // }
    return array('9' => $extcertnode);
}

function cert_status_filter($mform)
{
    $systemcontext = context_system::instance();
    if(is_siteadmin() ||(has_capability('local/externalcertificate:manage', $systemcontext)&& has_capability('local/externalcertificate:view', $systemcontext))){
        $statusarray = array('select'=>'Select Status', '0'=>'Pending','1' => 'Approved', '2' =>'Declined');
        $select = $mform->addElement('select', 'status', 'Status', $statusarray, array('placeholder' => get_string('status','local_externalcertificate')));
        $mform->setType('status', PARAM_RAW);
        $select->setMultiple(false);
    }
}

function from_date_filter($mform)
{
    $mform->addElement('date_selector', 'fromdate', get_string('fromdate', 'local_externalcertificate'), array('optional' => true));
} 

function to_date_filter($mform)
{
    $mform->addElement('date_selector', 'todate', get_string('todate', 'local_externalcertificate'), array('optional' => true));
}  


function get_listof_external_certificates($stable, $filtervalues)
{
    global  $DB, $USER, $CFG;
    $systemcontext = context_system::instance();
    $data = array();
    $count = 0;

    $sql = "SELECT * FROM {local_external_certificates} ec WHERE 1=1 ";
    $params = array();

    if(!is_siteadmin() && !(has_capability('local/externalcertificate:manage', $systemcontext) && has_capability('local/externalcertificate:view', $systemcontext))){
        $sql .= " AND userid = :userid";
        $params['userid'] = $USER->id;
    }
    
    if(is_siteadmin() || (has_capability('local/externalcertificate:manage', $systemcontext) && has_capability('local/externalcertificate:view', $systemcontext))){
    
        if(isset($filtervalues->search_query) && !empty($filtervalues->search_query) && trim($filtervalues->search_query) != ''){
            $params['search'] = '%' . trim($filtervalues->search_query) . '%';
            $params['search1'] = '%' . trim($filtervalues->search_query) . '%';
            $params['search2'] = '%' . trim($filtervalues->search_query) . '%';
            $sql .= " AND ( coursename LIKE :search OR certificate_issuing_authority LIKE :search1 OR username LIKE :search2)";
        } 
    }else{
        if(isset($filtervalues->search_query) && !empty($filtervalues->search_query) && trim($filtervalues->search_query) != ''){
            $params['search'] = '%' . trim($filtervalues->search_query) . '%';
            $params['search1'] = '%' . trim($filtervalues->search_query) . '%';
            $sql .= " AND ( coursename LIKE :search OR certificate_issuing_authority LIKE :search1 )";
        } 
    }

    if( isset($filtervalues->status) && $filtervalues->status != 'select' ){
        $sql .= " AND ec.status = :certstatus ";       
        $params['certstatus'] =  $filtervalues->status;
    }

    $filterdata = (array) $filtervalues;
  
    if($filterdata['fromdate[year]'] && $filterdata['todate[year]']){
        $from_year=$filterdata['fromdate[year]'];
        $from_month=$filterdata['fromdate[month]'];
        $from_day=$filterdata['fromdate[day]'];

        $filter_fromdate=mktime(0, 0, 0, $from_month, $from_day, $from_year) ;  

        $to_year=$filterdata['todate[year]'];
        $to_month=$filterdata['todate[month]'];
        $to_day=$filterdata['todate[day]'];

        $filter_todate=mktime(0, 0, 0, $to_month, $to_day, $to_year);
        
        $sql .=" AND ec.timecreated BETWEEN :filter_fromdate AND :filter_todate ";
        $params['filter_fromdate'] = $filter_fromdate;
        $params['filter_todate'] = $filter_todate;
    
    }else if($filterdata['fromdate[year]']){
            
        $from_year=$filterdata['fromdate[year]'];
        $from_month=$filterdata['fromdate[month]'];
        $from_day=$filterdata['fromdate[day]'];

        $filter_fromdate=mktime(0, 0, 0, $from_month, $from_day, $from_year) ;        
        $sql .=" AND ec.timecreated >= :filter_fromdate ";
        $params['filter_fromdate'] = $filter_fromdate;
    } 

    $ordersql = " ORDER BY ec.id desc" ;
    $a = array('0' => 'Pending', '1' => 'Approved', '2' => 'Decline', '3' => 'Pending');
 
    $result = $DB->get_records_sql($sql.$ordersql, $params , $stable->start, $stable->length);
    // echo "<pre>";print_r($result);exit;


    $actions = false;
    foreach ($result as $key) {
        $array = array();
        $array['approvestatus'] = false;
        $array['rejectstatus'] = false;
        $array['mergestatus'] = false;
        $array['id'] = $key->id;
        $array['username'] = $key->username;
        $mastercoursename = $DB->get_field('local_external_certificates_courses', 'coursename', array('id' => $key->coursename));
        if($mastercoursename) {
            $array['coursename'] = $mastercoursename;
        }else{
             $array['coursename'] = $key->mastercourse .' ('. $key->coursename .') ';
        }
        $userid = $key->userid;
        $department = $DB->get_record_sql("SELECT lc.fullname
            FROM {user} as u
            JOIN {local_costcenter} as lc ON u.open_departmentid = lc.id
            WHERE u.id = $userid");
        $array['department'] = !empty($department->fullname) ? $department->fullname : 'N/A';
        $empgrade = $DB->get_field('user', 'open_grade', array('id' => $key->userid));
        $array['empgrade'] = !empty($empgrade) ? $empgrade : 'N/A';
        $empid = $DB->get_field('user','open_employeeid',array('id' => $key->userid));
        $array['empid'] = !empty($empid) ? $empid : 'N/A';
        $array['institute_provider'] = $key->institute_provider;
        $array['category'] = $key->category;
        $hours = floor((int)$key->duration/3600);
        $minutes = ((int)$key->duration/60)%60;
        $array['duration'] = $hours.':'. $minutes;
        // FD - 190437 Changes made for credits 
        // if ($hours < 1) {
        //     $credits = '0.5';
        // } elseif (($hours >= 1 && $hours <= 4) || ($hours == 4 && $minutes <= 59)) {
        //     $credits = '1';
        // } elseif (($hours >= 5 && $hours <= 8) || ($hours == 8 && $minutes <= 59)) {
        //     $credits = '2';
        // } elseif (($hours >= 9 && $hours <= 12) || ($hours == 12 && $minutes <= 59)) {
        //     $credits = '3';
        // }else {
        //     $credits = '4';
        // }
        $credits = '2';
        $array['credit'] = $credits;

        // $array['duration'] = gmdate('H:i', $key->duration);
        $array['description'] = \local_costcenter\lib::strip_tags_custom($key->description);
        if($key->certificate_issuing_authority == 'Other'){
            $key->certificate_issuing_authority = $key->authority_type .' ('. $key->certificate_issuing_authority .') ';
        }
        $array['certificate_issuing_authority'] = $key->certificate_issuing_authority;
        $array['allskills'] = $key->skill;
        $array['skill'] =  strlen($array['allskills']) > 20 ? substr($array['allskills'], 0, 30) . "..." : $array['allskills'];   
        $array['issueddate'] =  date('d-m-Y', $key->issueddate);
        $array['validedate'] = ($key->expiry == 1) ? 'No expiry':date('d-m-Y', $key->validedate);
        $array['uploadeddate'] = date('d-m-Y', $key->timecreated);
        $array['approveddate'] = ($key->timemodified) ? date('d-m-Y', $key->timemodified) : 'N/A';
        $array['status'] =  $a[$key->status];       
        $array['imageurl'] = img_path2($key->certificate); 
        $array['compreason'] =  ($key->status == 2) ? $key->reason : 'N/A';  
        $array['reason'] =  ($key->status == 2) ? $key->reason : 'N/A';  
        $array['reason'] = strlen($array['reason']) > 20 ? substr($array['reason'], 0, 50) . "..." : $array['reason'];
        if($key->status == 0 || empty($key->status) || $key->status == NULL){
            $array['approvestatus'] = true;
            $array['rejectstatus'] = true;
            
        }else if($key->status == 1){
            $array['rejectstatus'] = true;
            //$array['mergestatus'] = true;
        }else if($key->status == 2){
            $array['approvestatus'] = true;
           // $array['mergestatus'] = true;
        } else if($key->status == 3) {
            $array['rejectstatus'] = true;
            $array['approvestatus'] = true;
        }
        if($key->coursename == 'Other' ){
            $array['mergestatus'] = true;
        }
        if(is_siteadmin() ||(has_capability('local/externalcertificate:manage', $systemcontext)&& has_capability('local/externalcertificate:view', $systemcontext))){
            $actions = true;
        }
        $array['actions'] = $actions;
        $array['url'] = $key->url;
        $data[] = $array;
       
    }
    $count = count($DB->get_records_sql($sql, $params));


    return array('totalrecords' => $count, 'result' => $data, 'actions' =>$actions);  

}

/* function get_listof_internal_certificates($stable, $filtervalues){

    global $CFG, $USER, $DB;
    $data = array();
    $params = array();
    $count = 0;

                    
    $sql = " SELECT * FROM (
                SELECT concat(l.id, l.code) as uid, l.id, l.code as code,s.timecreated as uploadeddate, cc.timecompleted as approveddate,
                   c.id as courseid,c.open_url as url, c.fullname as coursename, ct.course_type , (SELECT GROUP_CONCAT({local_skill}.name) 
                        FROM mdl_local_skill WHERE instr(concat(' ,',c.open_skill,','),concat(',',{local_skill}.id,',')) > 1
                        ) as skill
                    FROM {tool_certificate_issues} l
                    JOIN {tool_certificate_templates} lc ON lc.id = l.templateid 
                    JOIN {user} u ON l.userid = u.id 
                    JOIN {course} c ON l.moduleid = c.id
                    JOIN {course_modules} cm on c.id = cm.course 
                    LEFT OUTER JOIN {assign_submission} s on s.assignment = cm.instance AND s.status = 'submitted' 
                    JOIN {local_course_types} ct ON ct.id = c.open_identifiedas
                    JOIN {course_completions} cc ON cc.course = c.id AND u.id = cc.userid AND cc.timecompleted IS NOT NULL
                    WHERE l.userid = :inuserid 
                UNION ALL
                SELECT concat(ec.id, ec.certificate) as uid, ec.id, ec.certificate as code ,ec.timecreated as uploadeddate,
                    ec.timemodified as approveddate,NULL as courseid,ec.url as url,
                    ec.coursename as coursename, 'External' as 'course_type' ,ec.skill as skill FROM mdl_local_external_certificates ec
                    WHERE ec.userid = :exuserid and ec.status = :status)
            as nt WHERE  nt.id > 0";
    
    $filterdata = (array) $filtervalues;

    if(isset($filtervalues->search_query) && !empty($filtervalues->search_query)){
        $search = '%' . trim($filtervalues->search_query) . '%';
        $sql .= " AND ( nt.coursename LIKE '". $search."' OR nt.course_type LIKE '". $search."')";
    }   

    if($filterdata['fromdate[year]'] && $filterdata['todate[year]']){ 
        
        $from_year=$filterdata['fromdate[year]'];
        $from_month=$filterdata['fromdate[month]'];
        $from_day=$filterdata['fromdate[day]'];

        $filter_fromdate=mktime(0, 0, 0, $from_month, $from_day, $from_year) ; 

        $to_year=$filterdata['todate[year]'];
        $to_month=$filterdata['todate[month]'];
        $to_day=$filterdata['todate[day]'];

        $filter_todate=mktime(0, 0, 0, $to_month, $to_day, $to_year);
        
        $sql .=" AND nt.uploadeddate BETWEEN :filter_fromdate AND :filter_todate ";
        $params['filter_fromdate'] = $filter_fromdate;
        $params['filter_todate'] = $filter_todate;
    
    }else if($filterdata['fromdate[year]']){        
        $from_year=$filterdata['fromdate[year]'];
        $from_month=$filterdata['fromdate[month]'];
        $from_day=$filterdata['fromdate[day]'];

        $filter_fromdate=mktime(0, 0, 0, $from_month, $from_day, $from_year) ;        
        $sql .=" AND nt.uploadeddate >= :filter_fromdate ";
        $params['filter_fromdate'] = $filter_fromdate;
    } 

    $params['inuserid'] = $USER->id;
    $params['exuserid'] = $USER->id;
    $params['status'] = 1;
   
    if($stable->thead){ 
        $result = $DB->get_records_sql($sql, $params, $stable->start, $stable->length);
    }else {  
        $result = $DB->get_records_sql($sql, $params);
    }
 
    $actions = false;
    foreach ($result as $key) {
        $array = array();
        $array['id'] = $key->id;
        $array['username'] = $key->username;
        $array['coursename'] = $key->coursename;
        $array['learningtype'] = $key->course_type;
        $array['skill'] = !empty($key->skill)? $key->skill : 'N/A';
        $array['uploadeddate'] = !empty($key->uploadeddate)? date('d-m-Y', $key->uploadeddate) : 'N/A';
        $array['approveddate'] = ($key->approveddate) ? date('d-m-Y', $key->approveddate) : 'N/A';
        if($key->course_type == 'External'){ 
            $array['imageurl'] = img_path2($key->code);  
            $array['url'] = $key->url;  
        }else{
            $array['imageurl'] = $CFG->wwwroot . '/admin/tool/certificate/view.php?code='.$key->code;
            $array['url'] = $CFG->wwwroot.'/course/view.php?id='.$key->courseid;        
        }
       $data[] = $array;
       
    }

    $count = count($DB->get_records_sql($sql, $params));

    return array('totalrecords' => $count, 'result' => $data, 'actions' =>$actions); 
} */

function get_listof_internal_certificates($stable, $filtervalues){
	
	global $CFG, $USER, $DB;
	$data = array();
	$params = array();
	$count = 0;

    $countsql = "SELECT COUNT(DISTINCT(nt.cid)) ";
    $sql="SELECT  nt.* ";
	$fromsql = " FROM (
                SELECT concat('InternalCert_',c.id) as cid,MAX( s.id )as aid, c.id as id, NULL as code,MAX(s.timemodified) as uploadeddate, 
                    cc.timecompleted as approveddate,c.open_url as url, c.fullname as coursename, ct.course_type ,c.open_skill as skill
                    FROM {course} c
                    JOIN {course_modules} cm on c.id = cm.course AND cm.module = 1 		
                    JOIN {local_course_types} ct ON ct.id = c.open_identifiedas   
                    JOIN {enrol} e ON e.courseid = c.id 
                    JOIN {user_enrolments} ue ON ue.enrolid = e.id AND ue.status = 0 
                    JOIN {course_completions} cc ON cc.course = c.id AND cc.userid = $USER->id AND cc.timecompleted IS NOT NULL
                    LEFT JOIN {assign_submission} s on s.assignment = cm.instance AND s.status = 'submitted' AND cc.userid = s.userid  
                    WHERE ue.userid = :inuserid AND (c.open_certificateid IS NOT NULL OR c.open_certificateid > 0 OR s.id IS NOT NULL)
                    GROUP BY c.id,cc.timecompleted,c.open_url, c.fullname, ct.course_type ,c.open_skill 
                UNION 
                SELECT concat('ExternalCert_',ec.id) as cid, NULL as aid, ec.id as id , ec.certificate as code ,ec.timecreated as uploadeddate,
                    ec.timemodified as approveddate,ec.url as url, ec.coursename as coursename, 'External' as 'course_type' ,ec.skill as skill 
                    FROM {local_external_certificates} ec
                    WHERE ec.userid = :exuserid and ec.status = :status)
                as nt WHERE  nt.id > 0";
		
		$filterdata = (array) $filtervalues;
		
		if(isset($filtervalues->search_query) && !empty($filtervalues->search_query)){
			$search = '%' . trim($filtervalues->search_query) . '%';
			$fromsql .= " AND ( nt.coursename LIKE '". $search."' OR nt.course_type LIKE '". $search."')";
		} 
		
		if($filterdata['fromdate[year]'] && $filterdata['todate[year]']){ 
			
			$from_year=$filterdata['fromdate[year]'];
			$from_month=$filterdata['fromdate[month]'];
			$from_day=$filterdata['fromdate[day]'];
			
			$filter_fromdate=mktime(0, 0, 0, $from_month, $from_day, $from_year) ; 
			
			$to_year=$filterdata['todate[year]'];
			$to_month=$filterdata['todate[month]'];
			$to_day=$filterdata['todate[day]'];
			
			$filter_todate=mktime(0, 0, 0, $to_month, $to_day, $to_year);
			
			$fromsql .=" AND nt.uploadeddate BETWEEN :filter_fromdate AND :filter_todate ";
			$params['filter_fromdate'] = $filter_fromdate;
			$params['filter_todate'] = $filter_todate;
			
		}else if($filterdata['fromdate[year]']){        
			$from_year=$filterdata['fromdate[year]'];
			$from_month=$filterdata['fromdate[month]'];
			$from_day=$filterdata['fromdate[day]'];
			
			$filter_fromdate=mktime(0, 0, 0, $from_month, $from_day, $from_year) ;        
			$fromsql .=" AND nt.uploadeddate >= :filter_fromdate ";
			$params['filter_fromdate'] = $filter_fromdate;
		} 
		
		$params['inuserid'] = $USER->id;
		$params['exuserid'] = $USER->id;
		$params['status'] = 1;
     
		$result = $DB->get_records_sql($sql.$fromsql, $params, $stable->start, $stable->length);      

	    $count = $DB->count_records_sql($countsql.$fromsql, $params);
     		
       $actions = false;
	
		foreach ($result as $key) { 
			$array = array();
			$array['id'] = $key->id;
			$array['username'] = $key->username;
			$array['coursename'] = $key->coursename;
			$array['learningtype'] = $key->course_type;
			$array['uploadeddate'] = !empty($key->uploadeddate)? date('d-m-Y', $key->uploadeddate) : 'N/A';
			$array['approveddate'] = ($key->approveddate) ? date('d-m-Y', $key->approveddate) : 'N/A';
			if($key->course_type === 'External'){ 
				$array['imageurl'] = img_path2($key->code);  
				$array['url'] = $key->url;  
				$array['skill'] = !empty($key->skill)? $key->skill : 'N/A';
			}else{
				if(!empty($key->skill)){
					$skillsql = "SELECT GROUP_CONCAT(sk.name) FROM {local_skill} sk WHERE sk.id IN ($key->skill) ";
					$array['skill'] = $DB->get_field_sql($skillsql); 				
				}else{
					$array['skill'] = 'N/A';
				}
				if($key->aid != NULL ){
					$filedet =  $DB->get_record_sql("SELECT MAX(f.id) as fileid,f.contextid, f.itemid, f.filename,f.timecreated FROM {files} f JOIN {context} cxt ON cxt.id = f.contextid AND cxt.contextlevel  = 70 JOIN {course_modules} as cm ON  cm.course = $key->id AND cm.id = cxt.instanceid 
													WHERE f.userid = '$USER->id' AND component = 'assignsubmission_file' AND filearea = 'submission_files' AND filename != '.' GROUP BY itemid, contextid,filename");
					if(!empty($filedet)){
						$array['imageurl'] = $CFG->wwwroot.'/pluginfile.php/'.$filedet->contextid.'/assignsubmission_file/submission_files/'.$filedet->itemid.'/'.$filedet->filename.'?forcedownload=1';
                        $array['uploadeddate'] = !empty($filedet->timecreated)? date('d-m-Y', $filedet->timecreated) : 'N/A';
                    }else{
						$certdet =  $DB->get_record_sql("SELECT l.code as code,cc.timecompleted as timecompleted FROM {tool_certificate_issues} l 
						JOIN {tool_certificate_templates} lc ON lc.id = l.templateid 
						JOIN {course_completions} cc ON cc.course = $key->id AND cc.userid = $USER->id AND cc.timecompleted IS NOT NULL                    
						WHERE l.moduleid = $key->id AND l.userid = $USER->id ");
						if(!empty($certdet)){
							$array['imageurl'] = $CFG->wwwroot . '/admin/tool/certificate/view.php?code='.$certdet->code;   
                            $array['uploadeddate'] = !empty($certdet->timecompleted)? date('d-m-Y', $certdet->timecompleted) : 'N/A';				
						}
					}    
					
				}
                $array['url'] = $CFG->wwwroot.'/course/view.php?id='.$key->id;         
				
			}
            $data[] = $array;				
			
		}	

	    return array('totalrecords' => $count, 'result' => $data, 'actions' =>$actions); 
		
}

function send_notification($id,$emailtype){
    global $CFG,$USER,$DB;

  //  require_once($CFG->dirroot.'/local/externalcertificate/classes/notification.php');
    $classname = 'local_externalcertificate\notification';
    if (class_exists($classname)) {
        
        $notification = new \local_externalcertificate\notification();    
        $certdetails = $DB->get_record('local_external_certificates',array('id'=>$id));
        $userinfo = core_user::get_user($certdetails->userid);
        $notification->send_extcertificate_notification((object)$certdetails, $userinfo, $emailtype);
    
    }
    return true;    
}


// For display Merging Course form in popup. <Revathi>
function local_externalcertificate_output_fragment_mastercourse_form($args) {

    global $CFG, $DB;

    $args = (object) $args;
    $context = $args->context;
    $id = $args->id;
    $status = $args->status;
    $o = '';
    $formdata = [];

    $o = '';
    if (!empty($args->jsonformdata)) {
        $serialiseddata = $args->jsonformdata;
        parse_str($serialiseddata, $formdata);
    }

    $params = array(
        'id' => $id,
        'status'=>$status,
        'contextid' => $context
    );
    $mform = new local_externalcertificate\form\mastercourse_form(null, $params, 'post', '', null, true, (array)$formdata);
    $mform->set_data($formdata);
    // print_r($formdata); die;
    if (!empty($args->jsonformdata)) {

        // If we were passed non-empty form data we want the mform to call validation functions and show errors.
        $mform->is_validated();
    }
   
    ob_start();
    $mform->display();
    $o .= ob_get_contents();
    ob_end_clean();
    return $o;
}
// For display Master Course/Certificate  form  <Revathi>
function local_externalcertificate_output_fragment_mastercertificate_form($args) {

    global $CFG, $DB;

    $args = (object) $args;
    $context = $args->context;
    $id = $args->id;
    $status = $args->status;
    $o = '';
    $formdata = [];

   
    if (!empty($args->jsonformdata)) {
        $serialiseddata = $args->jsonformdata;
        parse_str($serialiseddata, $formdata);
    }

    $params = array(
        'id' => $id,
        'status'=>$status,
        'contextid' => $context
    );
    if($id){
        $data = $DB->get_record('local_external_certificates_courses', array('id'=>$id));       
    }
    $mform = new local_externalcertificate\form\mastercertificate_form(null, $params, 'post', '', null, true, (array)$formdata);
    $mform->set_data($data);
    
    if (!empty($args->jsonformdata)) {

        // If we were passed non-empty form data we want the mform to call validation functions and show errors.
        $mform->is_validated();
    }
   
    ob_start();
    $mform->display();
    $o .= ob_get_contents();
    ob_end_clean();
    return $o;
}
//List of Master external certificate data
function get_listof_masterexternal_certificates($stable, $filtervalues) {
    global  $DB, $USER, $CFG;
    $systemcontext = context_system::instance();
    $data = array();
    $count = 0;

    $sql = "SELECT * FROM {local_external_certificates_courses} ec WHERE 1=1 ";
    $params = array();
    if(is_siteadmin() || (has_capability('local/externalcertificate:manage', $systemcontext)&& has_capability('local/externalcertificate:view', $systemcontext))){
    
        if(isset($filtervalues->search_query) && !empty($filtervalues->search_query) && trim($filtervalues->search_query) != ''){
            $params['search'] = '%' . trim($filtervalues->search_query) . '%';
            $sql .= " AND ( coursename LIKE :search)";
        } 
    }else{
        if(isset($filtervalues->search_query) && !empty($filtervalues->search_query) && trim($filtervalues->search_query) != ''){
            $params['search'] = '%' . trim($filtervalues->search_query) . '%';
            $sql .= " AND ( coursename LIKE :search)";
        } 
    } 

    $ordersql = " ORDER BY ec.id desc" ;  
 
    $result = $DB->get_records_sql($sql.$ordersql, $params , $stable->start, $stable->length);

    $actions = false;
    foreach ($result as $key) {
        $array = array();      
        $array['id'] = $key->id;       
        $array['coursename'] =  $key->coursename;
        $array['coursecode'] =  $key->coursecode;        
        $array['uploadeddate'] = date('d-m-Y', $key->timecreated);
        if(is_siteadmin() ||(has_capability('local/externalcertificate:manage', $systemcontext)&& has_capability('local/externalcertificate:view', $systemcontext))){
            $actions = true;
            $editcap = 1;
            $exists = $DB->record_exists('local_external_certificates', array('coursename'=>$key->id));
            if(!$exists){
                $deletecap = 1;
            }else{

             $deletecap = 0;
            }
        }
        $array['actions'] = $actions;
        $array['editcap'] = $editcap;       
        $array['deletecap'] = $deletecap;
        $data[] = $array;
        
       
    }
    $count = count($DB->get_records_sql($sql,$params));

    return array('totalrecords' => $count, 'result' => $data, 'actions' =>$actions);  

}
