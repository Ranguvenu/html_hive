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
 * @subpackage local_users
 */

class local_users_renderer extends plugin_renderer_base {   
    /**
     * Description: Employees profile view in profile.php
     * @param  [int] $id [user id whose profile is viewed]
     * @return [HTML]     [user profile page content]
     */
    public function employees_profile_view($id) {
        global $CFG, $OUTPUT, $DB, $PAGE, $USER;
        require_once($CFG->dirroot.'/course/renderer.php');
        require_once($CFG->libdir . '/badgeslib.php');

        $corecomponent = new core_component();

        
        $systemcontext = context_system::instance();
        $userrecord = $DB->get_record('user', array('id' => $id));
        /*user image*/
        $user_image = $OUTPUT->user_picture($userrecord, array('size' => 80, 'link' => false));

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
        $userdepartment = $DB->get_field('local_costcenter', 'fullname',  array('id' => $userOrg->open_departmentid));
        $usersubdepartment = !empty($userOrg->open_subdepart) ? $userOrg->open_subdepart : 'NA';
        if(!empty($userrecord->phone1)){
            $contact = $userrecord->phone1;
        }else{
            $contact = 'N/A';
        }
        if(!empty($userOrg->open_supervisorid)){
            $sql = "SELECT u.id, u.firstname, u.lastname, u.open_employeeid 
                    FROM {user} as u 
                    WHERE  u.id= :open_supervisorid";
            $reportinguser = $DB->get_record_sql($sql , array('open_supervisorid' => $userOrg->open_supervisorid));
            $reporting_username = $reportinguser->firstname.' '.$reportinguser->lastname;
        }else{
            $reporting_username = 'N/A';
        }
        if(!empty($userOrg->open_functionalreportingto)){
            $sql = "SELECT u.id, u.firstname, u.lastname
                    FROM {user} as u 
                    WHERE  u.id= :funcreporting";
            $func_reporting = $DB->get_record_sql($sql , array('funcreporting' => $userOrg->open_functionalreportingto));
            $func_reportinguser = $func_reporting->firstname.' '.$func_reporting->lastname;
        }else{
            $func_reportinguser = 'N/A';
        }
        $usercontent = new stdClass();
        $core_component = new core_component();
        $local_pluginlist = $core_component::get_plugin_list('local');
        $existingplugin = array();
        $usercontent = array();
        $navigationdata = '';
        foreach($local_pluginlist AS $pluginname => $pluginurl){
            $userclass = '\local_'.$pluginname.'\local\user';
            if(class_exists($userclass)){
                $plugininfo = array();
                $pluginclass = new $userclass;
                if(method_exists($userclass, 'user_profile_content')){
                    $plugindata = $pluginclass->user_profile_content($id,true);
                    $usercontent[] = $plugindata;
                    $plugininfo['userenrolledcount'] = $plugindata->count;
                    // $plugininfo['string'] = get_string($pluginname, 'local_'.$pluginname);
                    $plugininfo['string'] = $plugindata->string;
                    if($pluginname != 'users'){
                        $existingplugin[$plugindata->sequence] = $plugininfo;
                    }
                    if(isset($plugindata->navdata) && !is_array($plugindata->navdata)){
                        $navigationdata .= $plugindata->navdata;
                    }
                    
                }
            }
        }

        ksort($existingplugin);
        $existingplugin = array_values($existingplugin);
        
        if(is_siteadmin() || has_capability('moodle/user:editownprofile',$systemcontext)){
            $capabilityedit = 1;
        }else{
            $capabilityedit = 0;
        }
        if(has_capability('moodle/user:loginas', $systemcontext)){
            $loginasurl = new moodle_url('/course/loginas.php', array('id'=> 1, 'user' => $userrecord->id, 'sesskey' => sesskey()));
        }else{
            $loginasurl = false;
        }     

        $core_component = new core_component();
        $plugins = $core_component::get_plugin_list('local');
        $pluginarray = array();
        foreach ($plugins as $key => $valuedata) {
            $userclass = '\local_'.$key.'\local\user';
            if(class_exists($userclass)){
                $pluginclass = new $userclass;
                if(method_exists($userclass, 'user_profile_content')){
                    $pluginarray[$key] = true;
                }
            }
        }
        $pluginarray['skills'] = 1;
        $badgecount = $DB->count_records_sql("SELECT count(id) FROM {badge_issued} WHERE userid = :userid", array('userid'=> $userrecord->id));
        $certificatecount = $DB->count_records_sql("SELECT count(id) FROM {certificate_issues} WHERE userid = :userid", array('userid'=> $userrecord->id));
        $options = array('targetID' => 'display_modulesdata');

         if($userrecord->open_country){
            // $countries = get_string_manager()->get_list_of_countries();
            // $empcountry = $countries[$userrecord->country];
             $empcountry = $userrecord->open_country;
        }else{
            $empcountry = 'N/A';
        }

        

        $usersviewContext = [
            "userid" => $userrecord->id,
            "username" => fullname($userrecord),
            "userimage" => $user_image,
            "empid" => $userOrg->open_employeeid != NULL ? $userOrg->open_employeeid : 'N/A',
            "user_email" => $userrecord->email,
            "dob" => ($userrecord->open_dob) ? date('d/m/Y', $userrecord->open_dob) : 'N/A' ,
            "doj" => ($userrecord->open_doj) ? date('d/m/Y', $userrecord->open_doj) : 'N/A' ,
            "gender" => ($userrecord->open_gender) ? $userrecord->open_gender : '--',
            "department" => $userdepartment ? $userdepartment : 'N/A',
            "subdepartment" => $usersubdepartment ? $usersubdepartment : 'N/A',
            "location" => $userrecord->open_location != NULL ? $userrecord->open_location : 'N/A',
            "address" => $userrecord->address != NULL ? $userrecord->address : 'N/A',
            "designation"=>$userrecord->open_designation !=NULL? $userrecord->open_designation:'N/A',
            "grade"=>$userrecord->open_grade !=NULL?$userrecord->open_grade:'N/A',
            "functionalreportingto"=> $func_reportinguser,
            "costcenter"=> $userrecord->open_costcenter != NULL ? $userrecord->open_costcenter : 'N/A',
            "ouname"=> $userrecord->open_ouname != NULL ? $userrecord->open_ouname : 'N/A',
            "careertrack"=> $userrecord->open_careertrack != NULL ? $userrecord->open_careertrack : 'N/A',
            "country"=> $empcountry,
            "phnumber" => $contact,
            "badgesimg" => $OUTPUT->image_url('badgeicon','local_users'),
            "certimg" => $OUTPUT->image_url('certicon','local_users'),
            'navigationdata' => $navigationdata,
            "usercontent" => $usercontent, 
            "existingplugin" => $existingplugin,
            "badgescount" => $badgecount,
            "certificatescount" => $certificatecount,
            "supervisorname" => $reporting_username,
            "capabilityedit" => $capabilityedit,
            "loginasurl" => $loginasurl,
            "options" => $options,
            "pluginslist" => $pluginarray
        ];
        $value = $this->render_from_template('local_users/profile', $usersviewContext);

        return $value;
    }


    /**
     * [user_page_top_action_buttons description]
     * @return [html] [top action buttons content]
     */
	public function user_page_top_action_buttons(){
		global $CFG;
		$systemcontext = context_system::instance();
        return $this->render_from_template('local_users/usertopactions', array('contextid' => $systemcontext->id));
	}
    /**
     * [render_form_status description]
     * @method render_form_status
     * @param  \local_users\output\form_status $page [description]
     * @return [type]                                    [description]
     */
    public function render_form_status(\local_users\output\form_status $page) {
        $data = $page->export_for_template($this);
        return parent::render_from_template('local_users/form_status', $data);
    }

    /**
     * [display_users description]
     * @method manageusers_content
     * @param  $filter default false
     * @author  sarath
     */
    public function manageusers_content($filter = false){
        global $USER;

        $systemcontext = context_system::instance();

        $options = array('targetID' => 'manage_users1','perPage' => 10, 'cardClass' => 'col-md-6 col-12', 'viewType' => 'table');
        
        $options['methodName']='local_users_manageusers_view';
        $options['templateName']='local_users/users_view'; 
        $options = json_encode($options);

        $dataoptions = json_encode(array('userid' =>$USER->id,'contextid' => $systemcontext->id));
        $filterdata = json_encode(array());
        // echo "<pre>";print_r($dataoptions);
        // exit;
        $context = [
                'targetID' => 'manage_users1',
                'options' => $options,
                'dataoptions' => $dataoptions,
                'filterdata' => $filterdata
        ];

        if($filter){
            return  $context;
        }else{
            return  $this->render_from_template('local_costcenter/cardPaginate', $context);
        }
    }

    /**
     * [display_sync errors description]
     * @method display_sync errors
     * @param  $filter default false
     * @author  sarath
     */
    public function display_sync_errors($filter = false){
        global $USER;

        $systemcontext = context_system::instance();

        $options = array('targetID' => 'display_sync','perPage' => 10, 'cardClass' => 'tableformat', 'viewType' => 'table');
        
        $options['methodName']='local_users_syncerrors_view';
        $options['templateName']='local_users/syncerrors'; 
        $options = json_encode($options);

        $dataoptions = json_encode(array('userid' =>$USER->id,'contextid' => $systemcontext->id));
        $filterdata = json_encode(array());

        $context = [
                'targetID' => 'display_sync',
                'options' => $options,
                'dataoptions' => $dataoptions,
                'filterdata' => $filterdata
        ];

        if($filter){
            return  $context;
        }else{
            return  $this->render_from_template('local_costcenter/cardPaginate', $context);
        }
    }

    /**
     * [display_sync statics description]
     * @method display_sync statics
     * @param  $filter default false
     * @author  sarath
     */
    public function display_sync_statics($filter = false){
        global $USER;

        $systemcontext = context_system::instance();

        $options = array('targetID' => 'display_syncstatics','perPage' => 10, 'cardClass' => 'tableformat', 'viewType' => 'table');
        
        $options['methodName']='local_users_syncstatics_view';
        $options['templateName']='local_users/syncstatistics'; 
        $options = json_encode($options);

        $dataoptions = json_encode(array('userid' =>$USER->id,'contextid' => $systemcontext->id));
        $filterdata = json_encode(array());

        $context = [
                'targetID' => 'display_syncstatics',
                'options' => $options,
                'dataoptions' => $dataoptions,
                'filterdata' => $filterdata
        ];

        if($filter){
            return  $context;
        }else{
            return  $this->render_from_template('local_costcenter/cardPaginate', $context);
        }
    }

     public function employees_skill_profile_view($id) {
        global $CFG, $OUTPUT, $DB, $PAGE, $USER;
        
        $systemcontext = context_system::instance();
        $userrecord = $DB->get_record('user', array('id' => $id));
        // $user_image = $OUTPUT->user_picture($userrecord, array('size' => 80, 'link' => false));
        //added by sowmya for tabs dispalying
        $loginuser_position = $DB->get_record_sql("SELECT p.* FROM {local_positions} as p JOIN {user} as u on p.id=u.open_positionid WHERE p.domain=u.open_domainid and u.id=$userrecord->id");

        $pluginarray = array();
        $comparray = array();
        if($loginuser_position){
            $path = explode('/', $loginuser_position->path);
            // print_object($path);
            $sql = "SELECT id,name FROM {local_positions} where sortorder <= '{$loginuser_position->sortorder}' and domain={$loginuser_position->domain} and path LIKE '%/$path[1]%' order by sortorder desc";//exit;
            $loginuser_next_positions = $DB->get_records_sql($sql, array(),0,3);
            foreach ($loginuser_next_positions as $loginuser_next_position) {
                if($userrecord->open_positionid != $loginuser_next_position->id){
                    $current_position = '';
                } else {
                    $current_position = 'Current Position';

                }
                $positiontabnames = array();
                $positiontabnames['positionname'] = $loginuser_next_position->name;
                $positiontabnames['id'] = $loginuser_next_position->id;
                $positiontabnames['current_position'] = $current_position;
                $pluginarray[] = $positiontabnames;
            }

            $sql = "SELECT sc.*, sm.positionid FROM {local_skillmatrix} as sm JOIN {local_skill_categories} as sc ON sc.id=sm.skill_categoryid where sm.positionid= $loginuser_position->id";
            $compitencies = $DB->get_records_sql($sql, array());
            $count = count($compitencies);
            foreach ($compitencies as $compitency) {
                $domainid = $DB->get_field('local_positions', 'domain', array('id'=>$compitency->positionid));
                $domain = $DB->get_field('local_domains', 'name', array('id'=>$domainid));
                $org = $DB->get_field('local_costcenter', 'fullname', array('id'=>$compitency->costcenterid));
                $compnames = array();
                $compnames['comp_name'] = $compitency->name;
                // $compnames['org_name'] = $org;
                // $compnames['domain_name'] = $domain;
                $sql = "SELECT s.name, s.id FROM {local_skill} as s JOIN {local_skill_categories} as sc ON sc.id=s.category where s.category= $compitency->id";
                $skills = $DB->get_records_sql_menu($sql, array());
                $skillids = implode(',',$skills);
                $sql = "SELECT distinct(cc.id) as completionid,c.id,c.fullname,c.shortname as code,c.summary,ue.timecreated as enrolldate,cc.timecompleted as completedate 
                        FROM {course_completions} AS cc
                        JOIN {course} AS c ON c.id = cc.course 
                        JOIN {enrol} AS e ON c.id = e.courseid AND e.enrol IN('self','manual','auto')
                        JOIN {user_enrolments} AS ue ON e.id = ue.enrolid AND ue.userid = cc.userid
                        WHERE (CONCAT(',',c.open_identifiedas,',') LIKE CONCAT('%,',3,',%') OR CONCAT(',',c.open_identifiedas,',') LIKE CONCAT('%,',1,',%') )
                        AND cc.timecompleted is not NULL AND c.visible=1 AND c.id>1 AND cc.userid = {$userrecord->id} AND open_skill IN ({$skillids})
                        ";
                $completed_skills = $DB->get_records_sql($sql);
                $progress = round((count($completed_skills)/count($skills))*100, 2);
                $compnames['percentage'] = $progress;
                $comparray[] = $compnames;
            }
        }

        $options = array('targetID' => 'display_skilldata');

        $usersviewContext = [
            "userid" => $userrecord->id,
            "username" => fullname($userrecord),
            // "userimage" => $user_image,
            // "user_email" => $userrecord->email,
            "positionslist" => $pluginarray,
            "id" => $loginuser_position->id,
            "contextid" => 1,
            "options"=>$options,
            "records"=>$comparray
        ];
        // $value = $this->render_from_template('local_users/skillinfo', $usersviewContext);

        return $usersviewContext;
    }
}
