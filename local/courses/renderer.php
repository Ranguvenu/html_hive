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


defined('MOODLE_INTERNAL') || die;
use core_component;
class local_courses_renderer extends plugin_renderer_base {

     /**
     * [render_classroom description]
     * @method render_classroom
     * @param  \local_classroom\output\classroom $page [description]
     * @return [type]                                  [description]
     */
    public function render_courses(\local_courses\output\courses $page) {
        $data = $page->export_for_template($this);
        return parent::render_from_template('local_courses/courses', $data);
    }
    /**
     * [render_form_status description]
     * @method render_form_status
     * @param  \local_classroom\output\form_status $page [description]
     * @return [type]                                    [description]
     */
    public function render_form_status(\local_courses\output\form_status $page) {
        $data = $page->export_for_template($this);
        return parent::render_from_template('local_courses/form_status', $data);
    }

    /**
     * Display the avialable courses
     *
     * @return string The text to render
     */
    public function get_catalog_courses($filter = false) {
        $systemcontext = context_system::instance();
        $options = array('targetID' => 'manage_courses','perPage' => 10, 'cardClass' => 'col-md-6 col-12', 'viewType' => 'card');
        $options['methodName']='local_courses_courses_view';
        $options['templateName']='local_courses/catalog';
        $options = json_encode($options);
        $filterdata = json_encode(array());
        $dataoptions = json_encode(array('contextid' => $systemcontext->id));
        $context = [
                'targetID' => 'manage_courses',
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
     * Display the avialable categories list
     *
     * @return string The text to render
     */
    public function get_categories_list($filter = false) {
        $id = optional_param('id', 0, PARAM_INT);
        $systemcontext = context_system::instance();
        $options = array('targetID' => 'manage_categories','perPage' => 12, 'cardClass' => 'col-md-3 col-sm-6', 'viewType' => 'card' );
        $options['methodName']='local_courses_categories_view';
        $options['templateName']='local_courses/categorylist';
        $options['parentid'] = $id;
        $options = json_encode($options);
        $filterdata = json_encode(array());
        $dataoptions = json_encode(array('contextid' => $systemcontext->id));
        $context = [
                'targetID' => 'manage_categories',
                'options' => $options,
                'dataoptions' => $dataoptions,
                'filterdata' => $filterdata
        ];
        if($filter){
            return  $context;
        } else {
            return  $this->render_from_template('local_costcenter/cardPaginate', $context);
        }       
    }

    /**
     * Renders html to print list of courses tagged with particular tag
     *
     * @param int $tagid id of the tag
     * @param bool $exclusivemode if set to true it means that no other entities tagged with this tag
     *             are displayed on the page and the per-page limit may be bigger
     * @param int $fromctx context id where the link was displayed, may be used by callbacks
     *            to display items in the same context first
     * @param int $ctx context id where to search for records
     * @param bool $rec search in subcontexts as well
     * @param array $displayoptions
     * @return string empty string if no courses are marked with this tag or rendered list of courses
     */
    public function tagged_courses($tagid, $exclusivemode = true, $ctx = 0, $rec = true, $displayoptions = null, $count = 0, $sort='') {
        global $CFG, $DB,$USER;
        $systemcontext = context_system::instance();
        $userorg = array();
        $userdep = array();
        if ($count > 0)
        $sql =" select count(c.id) from {course} c ";
        else
        $sql =" select c.* from {course} c  ";
        $joinsql = $groupby = $orderby = '';
        if (!empty($sort) AND $count == 0) {
          switch($sort) {
            case 'highrate':
            if ($DB->get_manager()->table_exists('local_rating')) {
              $joinsql .= " LEFT JOIN {local_rating} as r ON r.moduleid = c.id AND r.ratearea = 'local_courses' ";
              $groupby .= " group by c.id ";
              $orderby .= " order by AVG(rating) desc ";
            }
            break;
            case 'lowrate':
            if ($DB->get_manager()->table_exists('local_rating')) {
              $joinsql .= " LEFT JOIN {local_rating} as r ON r.moduleid = c.id AND r.ratearea = 'local_courses' ";
              $groupby .= " group by c.id ";
              $orderby .= " order by AVG(rating) asc ";
            }
            break;
            case 'latest':
            $orderby .= " order by c.timecreated desc ";
            break;
            case 'oldest':
            $orderby .= " order by c.timecreated asc ";
            break;
            default:
            $orderby .= " order by c.timecreated desc ";
            break;
            }
        }

        if(is_siteadmin()){
            $joinsql .= " JOIN {local_costcenter} AS co ON co.id = c.open_costcenterid
                         JOIN {course_categories} AS cc ON cc.id = c.category
                         where 1 = 1 ";
        } elseif(has_capability('local/costcenter:manage_ownorganization',$systemcontext)){
            $joinsql .= " JOIN {local_costcenter} AS co ON co.id = c.open_costcenterid
                       JOIN {course_categories} AS cc ON cc.id = c.category
                       WHERE c.open_costcenterid = :usercostcenter";
        } elseif(has_capability('local/costcenter:manage_owndepartments',$systemcontext)){
            $joinsql .= " JOIN {local_costcenter} AS co ON co.id = c.open_costcenterid
                       JOIN {course_categories} AS cc ON cc.id = c.category
                       WHERE c.open_costcenterid = :usercostcenter 
                       AND c.open_departmentid = :userdepartment";
        } else {
            $joinsql .= " JOIN {local_costcenter} AS co ON co.id = c.open_costcenterid
                       JOIN {course_categories} AS cc ON cc.id = c.category
                       WHERE c.open_costcenterid = :usercostcenter 
                       AND c.open_departmentid = :userdepartment";
        }

        if (!is_siteadmin()) {
            $userorg['usercostcenter'] = $USER->open_costcenterid;
            $userdep['userdepartment'] = $USER->open_departmentid;
        }

        $tagparams = array('tagid' => $tagid, 'itemtype' => 'courses', 'component' => 'local_courses');
        $params = array_merge($userorg, $userdep, $tagparams);

        $where = " AND c.id IN (SELECT t.itemid FROM {tag_instance} t WHERE t.tagid = :tagid AND t.itemtype = :itemtype AND t.component = :component)";

        if ($count > 0) {
            $records = $DB->count_records_sql($sql.$joinsql.$where, $params);
            return $records;
        } else {
            $records = $DB->get_records_sql($sql.$joinsql.$where.$groupby.$orderby, $params);
        }
        
        $tagfeed = new local_tags\output\tagfeed(array(), 'local_courses');
        $img = $this->output->pix_icon('i/course', '');
        foreach ($records as $key => $value) {
          $url = $CFG->wwwroot.'/course/view.php?id='.$value->id.'';
          $imgwithlink = html_writer::link($url, $img);
          $modulename = html_writer::link($url, $value->fullname);
          $coursedetails = get_course_details($value->id);
          $details = $this->render_from_template('local_courses/tagview', $coursedetails);
          $tagfeed->add($imgwithlink, $modulename, $details);
        }
        return $this->output->render_from_template('local_tags/tagfeed', $tagfeed->export_for_template($this->output));

    }
    public function get_parent_category_data($categoryid){
        global $DB;
        $category = $DB->get_record('course_categories', array('id' => $categoryid));
        $data = array();
        $data['category_name'] = strlen($category->name) > 20 ? substr($category->name, 0, 20).'...' : $category->name;
        $data['category_name_title'] = $category->name;
        $data['category_code'] = strlen($category->idnumber) > 20 ? substr($category->idnumber, 0, 20).'...' : $category->idnumber;
        $data['category_code_title'] = $category->idnumber;
        $categorycontext = \context_coursecat::instance($category->id);
        $data['courses'] = html_writer::link('javascript:void(0)', $category->coursecount, array('title' => '', 'alt' => '', 'class'=>'createcoursemodal', 'onclick' =>'(function(e){ require("local_courses/newcategory").courselist({contextid:'.$categorycontext->id.', categoryname: "'.$category->name.'", categoryid: "' . $category->id . '" }) })(event)'));
        $data['subcategory_count'] = $DB->count_records('course_categories', array('parent' => $categoryid)); 
        // $actions = False;
        // $systemcontext = \context_system::instance();
        // if(has_capability('moodle/category:manage', $systemcontext)){
        //     $actions = True;
        //     if(!empty($category->visible)){
        //         $visible_value = 0;
        //         $show = True;
        //     }else{
        //         $visible_value = 1;
        //         $show = False;
        //     }
        // }
        return $this->render_from_template('local_courses/parent_template', $data); 
    }

  function display_course_enrolledusers($courseid){
    global $DB;
    $certificate_plugin_exist = \core_component::get_plugin_directory('tool', 'certificate');
    
    $info = array();
    if($certificate_plugin_exist){
      $certificate = $DB->get_field('course', 'open_certificateid', array('id'=>$courseid));
      if($certificate){
        $info['added_certificate'] = true;
      }else{
        $info['added_certificate'] = false;
      }
    }    
    
    return $this->render_from_template('local_courses/courseusersview', $info);
  }

  function get_course_enrolledusers($dataobj){
    global $DB, $USER, $OUTPUT, $CFG;

    $countsql = "SELECT COUNT(ue.id) ";

    $selectsql = "SELECT ue.id, u.id as userid, u.firstname, u.lastname, u.email, u.open_employeeid, 
            cc.timecompleted ";

    $sql = " FROM {course} c
            JOIN {course_categories} cat ON cat.id = c.category
            JOIN {enrol} e ON e.courseid = c.id AND 
                        (e.enrol IN ('manual', 'self' ,'auto')) 
            JOIN {user_enrolments} ue ON ue.enrolid = e.id
            JOIN {user} u ON u.id = ue.userid AND u.deleted = 0
            JOIN {local_costcenter} lc ON lc.id = u.open_costcenterid
            JOIN {role_assignments} as ra ON ra.userid = u.id
            JOIN {context} AS cxt ON cxt.id=ra.contextid AND cxt.contextlevel = 50 AND cxt.instanceid=c.id
            JOIN {role} as r ON r.id = ra.roleid AND r.shortname = 'employee'
            LEFT JOIN {course_completions} as cc ON cc.course = c.id AND u.id = cc.userid 
            WHERE c.id = :courseid ";

    $params = array();
    $params['courseid'] = $dataobj->courseid;

    $systemcontext = \context_system::instance();

    if(!is_siteadmin() && has_capability('local/costcenter:manage_ownorganization', $systemcontext)){
      $sql .= " AND c.open_costcenterid = :costcenterid ";
      $params['costcenterid'] = $USER->open_costcenterid;
    }else if(!is_siteadmin() && has_capability('local/costcenter:manage_owndepartments', $systemcontext)){
      $sql .= " AND c.open_costcenterid = :costcenterid AND c.open_departmentid = :departmentid ";
      $params['costcenterid'] = $USER->open_costcenterid;
      $params['departmentid'] = $USER->open_departmentid;
    }

    if (!empty($dataobj->search)) {
      $concatsql = " AND ( CONCAT(u.firstname,' ',u.lastname) LIKE '%".$dataobj->search."%' OR
                          u.open_employeeid LIKE '%".$dataobj->search."%' ) ";
    }else{
      $concatsql = '';
    }

    $courseusers = $DB->get_records_sql($selectsql.$sql.$concatsql , $params, $dataobj->start, $dataobj->length);
    $enrolleduserscount = $DB->count_records_sql($countsql.$sql.$concatsql , $params);
    $userslist = array();
    if($courseusers){
      $userslist = array();

      $enrolledcount = $enrolleduserscount;
      $certificate_plugin_exist = \core_component::get_plugin_directory('tool', 'certificate');
      
      if($certificate_plugin_exist){
        $cert_plugin_exists = true;
        $certificate = $DB->get_field('course', 'open_certificateid', array('id'=>$dataobj->courseid));
        
        if($certificate){
          $icon = '<i class="icon fa fa-download" aria-hidden="true"></i>';
          $certificate_added = true;
          
        }else{
          $certificate_added = false;
        }
      }else{
        $cert_plugin_exists = false;
      }

      foreach ($courseusers as $enroluser) { 
        $userinfo = array();
        $userinfo[] = $enroluser->firstname.' '.$enroluser->lastname;
        $userinfo[] = $enroluser->open_employeeid;
        $userinfo[] = $enroluser->email;
        $fullname = $enroluser->firstname.' '.$enroluser->lastname;
        if($enroluser->timecompleted){
          $userinfo[] = get_string('completed', 'local_courses');
          $userinfo[] = date('d/m/Y h:i a', $enroluser->timecompleted);
        }else{
          $userinfo[] = get_string('notcompleted', 'local_courses');
          $userinfo[] = 'N/A';
        } 
      
        if($cert_plugin_exists && $certificate_added){
          if(!empty($enroluser->timecompleted)){
            //newly added code for certificate download
            $queryparam = array('userid' => $enroluser->userid);       
            $selectsql = "SELECT lci.id, lc.id as moduleid ,lc.name ,lci.code, lci.moduletype, lci.moduleid,
                        (SELECT 
                            CASE 
                            WHEN lci.moduletype LIKE 'course'
                                THEN (SELECT module.fullname FROM {course} AS module WHERE module.id = lci.moduleid )
                            ELSE '' END) AS modulename 
                            FROM {tool_certificate_issues} AS lci
                            JOIN {tool_certificate_templates} AS lc ON lc.id = lci.templateid 
                            WHERE lci.userid = :userid AND lci.templateid = ".$certificate;
                  
            $certirecived = $DB->get_record_sql($selectsql, $queryparam);
            if ($certirecived) {           
                $certificate_code=$certirecived->code;
                $moduleid=$certirecived->moduleid;
                $issue_cert_id=$certirecived->id;
            } 
            //end
            $icon = '<i class="icon fa fa-download" aria-hidden="true"></i>';
            //$array = array('ctid' =>$certificate, 'mtype'=>'course', 'uid'=>$enroluser->userid,'mid'=>$dataobj->courseid);
            //$url = new moodle_url('/local/certificates/view.php', $array);
            $array = array('code' =>$certificate_code);
            $url = new moodle_url('/admin/tool/certificate/view.php', $array);
            $userinfo[] = html_writer::link('javascript:void(0)', 'Re Issue', array('title' => 'reissue', 'alt' => '', 'class'=>'reissue', 'onclick' =>'(function(e){ require("local_courses/courses").reissueCertificate({action:"reissue_certificate",certcode:"'.$certificate_code.'", id: '.$issue_cert_id.',contextid:'.$systemcontext->id.',fullname:"'.$fullname.'",moduleid:'.$dataobj->courseid.', userid: ' . $enroluser->userid . ' }) })(event)'));
            $options = array('title'=>get_string('download_certificate', 'local_courses'));
            $userinfo[] = html_writer::link($url, $icon, $options);
          }else{
            //$icon = '<i class="icon fa fa-download" aria-hidden="true"></i>';
            $userinfo[] = "NA";
            $url = 'javascript: void(0)';
            $userinfo[] = html_writer::tag($url,get_string('notassigned','local_classroom'));
          }
        } 

        $userslist[] = $userinfo;
      }

      $return = array(
          // "recordsTotal" => $enrolleduserscount,
          "recordsFiltered" => $enrolleduserscount,
          "data" => $userslist,
      );
    }else{
      $return = array(
          // "recordsTotal" => $enrolleduserscount,
          "recordsFiltered" => 0,
          "data" => array(),
      );
    }
    return $return;
  }

 /**
   * Display the unenrol course confirm popup
   *
   * @return string The modal to render
   */
  public function unenrol_confirm($course, $instance, $message){
   
      $attributes = [
        'role'=>'promptdialog',
        'aria-labelledby'=>'modal-header',
        'aria-describedby'=>'modal-body',
        'aria-modal'=>'true'
      ];
      $yesurl = new moodle_url('/enrol/self/unenrolself.php', array('enrolid'=>$instance->id, 'confirm'=>1, 'sesskey'=>sesskey()));
      $nourl = new moodle_url('/course/view.php', array('id'=>$course->id));
      $message = get_string('unenrolselfconfirm', 'enrol_self', format_string($course->fullname));
      $output = $this->box_start('generalbox modal modal-dialog modal-in-page show', 'notice', $attributes);
          
      $output .= $this->box_start('modal-content', 'modal-content');
      $output .= $this->box_start('modal-header p-x-1', 'modal-header');
      $output .= html_writer::tag('h4', get_string('confirm'));
      $output .= $this->box_end();
      $attributes = [
          'role'=>'prompt',
          'data-aria-autofocus'=>'true'
      ];
      $output .= $this->box_start('modal-body', 'modal-body', $attributes);
      $output .= html_writer::tag('p', $message);
      $output .= html_writer::tag('p', get_string('unenrol_reason','local_courses'));
      $output .= html_writer:: tag('textarea','',array('name' => 'reason', 'class'=>' reason form-control ','size'=>'50', 'required'=>true));
      $output .= html_writer::tag('span', 'Please specify the reason</span>', array('class' => 'unenrolerror', 'style' => 'display:none;color:red;'));
      $output .= $this->box_end();
      $output .= $this->box_start('modal-footer', 'modal-footer');
      $output .= html_writer::tag('input', '', array('type' => 'button', 'class' => 'unenrol btn btn-primary' , 'value' => 'Continue', 'onclick' => '(function(e){ require("local_courses/unenrolCourse").unenrolSelf({contextid:1, confirmStatus : 1, component: "local_courses", plugintype:"local", pluginname:"courses", courseid:"' . $course->id . '" , url: "' .$yesurl .'", enrolid :"' . $instance->id. '" }) })(event)', 'style' => 'color:white;'));
      $output .= html_writer::tag('input', '', array('type' => 'button', 'class' => 'unenrol btn btn-primary', 'value' => 'Cancel', 'onclick' => '(function(e){ require("local_courses/unenrolCourse").unenrolSelf({contextid:1,confirmStatus : 0, component: "local_courses", plugintype:"local", pluginname:"courses", courseid:"' . $course->id . '" , url: "' .$nourl .'" , enrolid :"' . $instance->id. '"}) })(event)', 'style' => 'color:white;'));
      $output .= $this->box_end();
      $output .= $this->box_end();
      $output .= $this->box_end();
      return $output;

  }

    public function get_userdashboard_courses($tab, $filter = false, $coursetype = null) {
        $systemcontext = context_system::instance();
        $options = array('targetID' => 'dashboard_courses', 'perPage' => 6, 'cardClass' => 'col-md-6 col-12', 'viewType' => 'card');
        $options['methodName']='local_courses_userdashboard_content_paginated';
        $options['templateName']='local_courses/userdashboard_paginated';
        $options['filter'] = $tab;
        $options['coursetype'] = $coursetype;
        $options = json_encode($options);
        $filterdata = json_encode(array());
        $dataoptions = json_encode(array('contextid' => $systemcontext->id));
        $context = [
                'targetID' => 'dashboard_courses',
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

    public function display_sme_users($courseid){
      global $DB;
    
      $userslist = array();
      
      $sql="SELECT u.*
              FROM {context} as cxt 
              JOIN {role_assignments} as ra on ra.contextid=cxt.id
              JOIN {user} as u ON ra.userid = u.id
              JOIN {role} as r on r.id=ra.roleid
              WHERE cxt.contextlevel=:cxtlevel and r.shortname=:role and cxt.instanceid = :courseid";  
      $users = $DB->get_records_sql($sql,array('cxtlevel' => 50 , 'role' => 'sme', 'courseid' => $courseid));

      if($users){
          $userslist['usersexists'] = true;
      
          foreach ($users as $enroluser) {
              $userinfo = array();
              $userinfo['userid'] = $enroluser->userid;
              $userinfo['employeename'] = $enroluser->firstname.' '.$enroluser->lastname;
              $userinfo['employeeid'] = $enroluser->open_employeeid;
              $userinfo['email'] = $enroluser->email;
            
              $userslist['userdata'][] = $userinfo;
          }
      }else{
          $userslist['usersexists'] = false;
      }
      return $this->render_from_template('local_courses/smecourseusersview', $userslist);
    }
}
