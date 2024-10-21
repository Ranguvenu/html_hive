<?php
namespace local_users\local;    
use html_writer;
class user{
	public function user_profile_content($id,$return = false,$start =0,$limit=5){
        global $OUTPUT,$PAGE,$CFG,$DB;
        require_once($CFG->dirroot.'/course/renderer.php');
        require_once($CFG->libdir . '/badgeslib.php');

        $returnobj = new \stdClass();
        $returnobj->divid = 'user_profile';
        $returnobj->string = get_string('profile', 'local_users');
        $returnobj->moduletype = 'users';
        $returnobj->targetID = 'display_users';
        $returnobj->userid = $id;
        $returnobj->count = 1;
        $returnobj->usersexist = 1;
        
        $systemcontext = \context_system::instance();
        $userrecord = $DB->get_record('user', array('id' => $id));
        /*user image*/
        //$user_image = $OUTPUT->user_picture($userrecord, array('size' => 120, 'link' => false));

        /*user roles*/
        $userroles = get_user_roles($systemcontext, $id);
        if(!empty($userroles)){
                $rolename  = array();
                foreach($userroles as $roles) {
                    $rolename[] = ucfirst($roles->name);
                }
                $roleinfo = implode(", ",$rolename);
        } else {
            $roleinfo = "Employee";
        }
        $sql3 = "SELECT cc.fullname, u.open_employeeid,u.open_costcenterid,
                    u.open_designation, u.open_location,
                    u.open_supervisorid, u.open_group,
                    u.department, u.open_subdepart ,u.open_departmentid                         
                    FROM {local_costcenter} cc, {user} u
                    WHERE u.id=:id AND u.open_costcenterid=cc.id";
        $userOrg = $DB->get_record_sql($sql3, array('id' => $id));
        $usercostcenter = $DB->get_field('local_costcenter', 'fullname',  array('id' => $userOrg->open_costcenterid));
        $userdepartment = $userOrg->open_subdepart;
        if(!empty($userrecord->phone1)){
                $contact = $userrecord->phone1;
        }else{
                $contact = 'N/A';
        }
        if(!empty($userOrg->open_supervisorid)){
            $sql = "SELECT u.id, u.firstname, u.lastname, u.open_employeeid 
                    FROM {user} as u 
                    WHERE  u.id= :open_supervisorid";
            $reportinguser = $DB->get_record_sql($sql, array('open_supervisorid' => $userOrg->open_supervisorid));
            $reporting_username = $reportinguser->firstname.' '.$reportinguser->lastname;
        }else{
            $reporting_username = 'N/A';
        }

        $badgeimage = $OUTPUT->image_url('badgeicon','local_users');
        $badgimg = $badgeimage->out_as_local_url(); 

        $certiconimage = $OUTPUT->image_url('certicon','local_users');
        $certimg = $certiconimage->out_as_local_url(); 
        $usersviewContext = [
            "userid" => $userrecord->id,
            "username" => fullname($userrecord),
            "rolename" => $roleinfo,
            "empid" => $userOrg->open_employeeid != NULL ? $userOrg->open_employeeid : 'N/A',
            "user_email" => $userrecord->email,
            "organisation" => $usercostcenter ? $usercostcenter : 'N/A',
            "department" => $userdepartment ? $userdepartment : 'N/A',
            "location" => $userrecord->city != NULL ? $userrecord->city : 'N/A',
            "address" => $userrecord->address != NULL ? $userrecord->address : 'N/A',
            "phnumber" => $contact,
            "badgesimg" => $badgimg,
            "certimg" => $certimg,
            "supervisorname" => $reporting_username,
        ];
        
        $data = array();
        $data[] = $usersviewContext;
        $returnobj->navdata = $data;
        
        return $returnobj;
	}

    public function user_profileskill_content($id,$return = false,$start =0,$limit=5,$positionid=false){
        global $OUTPUT,$PAGE,$CFG,$DB;
        require_once($CFG->dirroot.'/course/renderer.php');
        require_once($CFG->libdir . '/badgeslib.php');
        $returnobj = new \stdClass();
        $systemcontext = \context_system::instance();
        $userrecord = $DB->get_record('user', array('id' => $id));
        //added by sowmya for tabs dispalying
        $loginuser_position = $DB->get_record_sql("SELECT p.* FROM {local_positions} as p JOIN {user} as u on p.id=u.open_positionid WHERE p.domain=u.open_domainid and u.id=$id");
        $comparray = array();
        if($loginuser_position) {
            $sql = "SELECT id,name FROM {local_positions} where sortorder <= '{$loginuser_position->sortorder}' order by sortorder desc";
            $loginuser_next_positions = $DB->get_records_sql($sql, array(),0,3);
            if(!empty($positionid)){    
                $positionid=$positionid;    
            } else {    
                $positionid=$userrecord->open_positionid;   
            }
            $sql = "SELECT sc.*, sm.skillid, sm.levelid, sm.positionid FROM {local_skillmatrix} as sm JOIN {local_skill_categories} as sc ON sc.id=sm.skill_categoryid where sm.positionid= $positionid and sm.costcenterid=$loginuser_position->costcenter";
            $compitencies = $DB->get_records_sql($sql, array());
            $count = count($compitencies);
            foreach ($compitencies as $compitency) {
                $compnames = array();
                $skillnames = array();
               $compitenc_name = html_writer::link('javascript:void(0)', $compitency->name, array('title' => '', 'onclick' => '(function(e){ require("local_users/newuser").skillslist({ contextid:'.$systemcontext->id.',costcenterid:'.$loginuser_position->costcenter.',categoryid:'.$compitency->id.',positionid:'.$positionid.', categoryname:"'.$compitency->name.'",userid:'.$id.'}) })(event)'));
                $compnames['comp_name'] = $compitenc_name;//$compitency->name;                
                $compnames['comp_id'] = $compitency->id; 
                $domainid = $DB->get_field('local_positions', 'domain', array('id'=>$compitency->positionid));
                // $domain = $DB->get_field('local_domains', 'name', array('id'=>$domainid));
                // $org = $DB->get_field('local_costcenter', 'fullname', array('id'=>$compitency->costcenterid));  
                // $compnames['org_name'] = $org;
                // $compnames['domain_name'] = $domain;                
               $sql = "SELECT s.id, s.name FROM {local_skill} as s 
                        JOIN {local_skill_categories} as sc ON sc.id=s.category 
                        where s.category= {$compitency->id} and s.costcenterid={$loginuser_position->costcenter}";
                $skills = $DB->get_records_sql_menu($sql, array());
                $skillnames = implode(',', $skills);
                $progress = $this->get_competency_percentage($compitency, $positionid,$userrecord->id);
                // $progress = round(($completedskills/count($skills))*100, 2);
                $compnames['percentage'] = $progress;
                $compnames['skillnames'] = $skillnames;
                $compnames['contextid'] = $systemcontext->id;
                $comparray[] = $compnames;
            }
        }

        $options = array('targetID' => 'display_skilldata');
        $returnobj->userid = $userrecord->id;
        $returnobj->positionid = $positionid;
        $returnobj->count = $count;
        $returnobj->compitencies = $comparray;
        $returnobj->options = $options;
        // print_object($returnobj);        
        return $returnobj;
    }
    public function get_competency_percentage($compitency, $positionid,$userid){
        global $DB;
        $sql = "SELECT s.name, s.id FROM {local_skill} as s 
                        JOIN {local_skill_categories} as sc ON sc.id=s.category 
                        where s.category= $compitency->id";
        $skills = $DB->get_records_sql($sql, array());
        if($skills){
            $completedskills = array();
            foreach ($skills as $skill) {
                $skillcomepleted = array();
                $sql = "SELECT levelid FROM {local_skillmatrix} where costcenterid= {$compitency->costcenterid} AND skillid={$skill->id} AND skill_categoryid={$compitency->id} AND positionid={$positionid}";
                $get_confg_levelid = $DB->get_record_sql($sql);
                if($get_confg_levelid) {
                    $sql = "SELECT distinct(cc.id) as completionid
                        FROM {course_completions} AS cc
                        JOIN {course} AS c ON c.id = cc.course 
                        JOIN {enrol} AS e ON c.id = e.courseid AND e.enrol IN('self','manual','auto')
                        JOIN {user_enrolments} AS ue ON e.id = ue.enrolid AND ue.userid = cc.userid
                        WHERE (CONCAT(',',c.open_identifiedas,',') LIKE CONCAT('%,',3,',%') OR CONCAT(',',c.open_identifiedas,',') LIKE CONCAT('%,',1,',%')) 
                        AND cc.timecompleted is not NULL AND c.visible=1 AND c.id>1 AND cc.userid = {$userid} AND c.open_level={$get_confg_levelid->levelid} AND open_skill={$skill->id} ";
                    $iscompleted=$DB->get_record_sql($sql);

                    if($iscompleted->completionid) {
                        $completedskills[]=$iscompleted->completionid;
                    }
                }
            }
            $progress = round((count($completedskills)/count($skills))*100, 2);
            return $progress;
        } else {
            $progress = 0;
            return $progress;
        }
    }

}