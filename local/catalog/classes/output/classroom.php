<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Class containing data for course competencies page
 *
 * @package    local_catalog
 * @copyright  2018 hemalathacarun <hemalatha@eabyas.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_catalog\output;
defined('MOODLE_INTERNAL') || die();

use renderable;
use templatable;
use renderer_base;
use stdClass;
use moodle_url;
use context_system;
use context_course;
use context_user;
use local_catalog\output\cataloglib;
use local_classroom\classroom as clroom;
use user_course_details;
use local_request\api\requestapi;


/**
 * Class containing data for course competencies page
 *
 * @copyright  2018 hemalatha c arun <hemalatha@eabyas.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class classroom implements renderable{

   
    public function get_facetofacelist_query($perpage,$startlimit,$return_noofrecords=false, $returnobjectlist=false){
        global $DB, $USER, $CFG;
        $search = cataloglib::$search;
        $sortid = cataloglib::$sortid;    
        //------main queries written here to fetch Classrooms or  session based on condition
        $csql = "SELECT  lc.*, lc.startdate as trainingstartdate, lc.enddate as trainingenddate ";
        $cfromsql = " from {local_classroom} lc  ";
        $usql = " UNION ";
        $tsql = "SELECT  lc.*, lc.startdate as trainingstartdate, lc.enddate as trainingenddate";
        $tfromsql = " from {local_classroom} lc "; 
        $tjoinsql = " JOIN {tag_instance} tgi ON tgi.itemid = lc.id AND tgi.itemtype = 'classroom' AND tgi.component = 'local_classroom' JOIN {tag} t ON t.id = tgi.tagid ";

        $leftjoinsql = $groupby = $orderby = $avgsql = '';
        if (!empty($sortid)) {
          switch($sortid) {
            case 'highrate':
            if ($DB->get_manager()->table_exists('local_rating')) {
                $avgsql .= " , AVG(r.rating) as rates ";
                $leftjoinsql .= " LEFT JOIN {local_rating} as r ON r.itemid = lc.id AND r.ratearea = 'local_classroom' ";
                $groupby .= " group by lc.id ";
                $orderby .= " order by rates desc ";
            }
            break;
            case 'lowrate':  
            if ($DB->get_manager()->table_exists('local_rating')) {  
                $avgsql .= " , AVG(r.rating) as rates  ";
                $leftjoinsql .= " LEFT JOIN {local_rating} as r ON r.itemid = lc.id AND r.ratearea = 'local_classroom' ";
                $groupby .= " group by lc.id ";
                $orderby .= " order by rates asc ";
            }
            break;
            case 'latest':
            $orderby .= " order by a.timecreated desc ";
            break;
            case 'oldest':
            $orderby .= " order by a.timecreated asc ";
            break;
            default:
            $orderby .= " order by a.timecreated desc ";
            break;
            }
        }

        $wheresql = " WHERE lc.visible=1 ";
        //------if not site admin sessions list will be filter by location or bands     
        if(is_siteadmin()){
            if(cataloglib::$search && cataloglib::$search != 'null'){
                 $cwrsql = " AND lc.name LIKE '%$search%'";
                 $twrsql = " AND t.name LIKE '%$search%'";
            }
        }
        $usercontext = context_user::instance($USER->id);
        $sqlparams = array();
        if(!is_siteadmin()){                
                //-----filter by costcenter
                if($USER->open_costcenterid){
                    $wheresql .= " AND (( lc.costcenter !=0 AND $USER->open_costcenterid in (lc.costcenter)))";      
                }
                 //OL-1042 Add Target Audience to Classrooms//
                $params = array();
                
                $group_list = $DB->get_records_sql_menu("SELECT cm.id,cm.cohortid as groupid from {cohort_members} cm where cm.userid IN ({$USER->id})");
                //added by sarath for  2477
                if (!empty($group_list)){
                     $groups_members = implode(',', $group_list);
                     if(!empty($group_list)){
                        $grouquery = array();
                        foreach ($group_list as $key => $group) {
                            $grouquery[] = " CONCAT(',',lc.open_group,',') LIKE CONCAT('%,',{$group},',%') ";//FIND_IN_SET($group,lc.open_group)
                        }
                        $groupqueeryparams =implode('OR',$grouquery);
                        
                        $params[]= '('.$groupqueeryparams.')';
                     }
                }

                if(count($params) > 0){
                    $opengroup=implode('AND',$params);
                }else{
                    $opengroup =  " 1 != 1 ";
                }
                
                $params = array();
                $params[]= " 1 = CASE WHEN lc.open_group is NOT NULL
                        THEN 
                            CASE 
                                WHEN $opengroup
                                    THEN 1
                                    ELSE 0 
                            END 
                        ELSE 1 END ";
                        
                // if(!empty($USER->open_departmentid)){
                    $params[]= " 1 = CASE WHEN lc.department!='-1'
                        THEN 
                            CASE 
                                WHEN CONCAT(',',lc.department,',') LIKE CONCAT('%,',{$USER->open_departmentid},',%')
                                    THEN 1
                                    ELSE 0 
                            END
                        ELSE 1 END "; //FIND_IN_SET($USER->open_departmentid,lc.department)
                // }
                if(!empty($USER->open_subdepartment) && $USER->open_subdepartment != ""){
                    $sqlparams[] = "%,$USER->open_subdepartment,%";
                }else{
                    $sqlparams[] = "";
                }
                    $params[]= " 1 = CASE WHEN lc.subdepartment != '-1'
                        THEN 
                            CASE 
                                WHEN CONCAT(',',lc.subdepartment,',') LIKE ?
                                    THEN 1
                                    ELSE 0
                            END 
                        ELSE 1 END ";//FIND_IN_SET('$USER->open_hrmsrole',lc.open_hrmsrole)
                // }
                if(!empty($USER->open_hrmsrole) && $USER->open_hrmsrole != ""){
                    $sqlparams[] = "%,$USER->open_hrmsrole,%";
                }else{
                    $sqlparams[] = "";
                }
                      $params[]= " 1 = CASE WHEN lc.open_hrmsrole IS NOT NULL
                        THEN 
                            CASE 
                                WHEN CONCAT(',',lc.open_hrmsrole,',') LIKE ?
                                    THEN 1
                                    ELSE 0
                            END 
                        ELSE 1 END ";//FIND_IN_SET('$USER->open_hrmsrole',lc.open_hrmsrole)
                // }
                if(!empty($USER->open_designation) && $USER->open_designation != ""){
                    $sqlparams[] = "%,$USER->open_designation,%";
                }else{
                    $sqlparams[] = "";
                }
                    $params[]= " 1 = CASE WHEN lc.open_designation IS NOT NULL
                        THEN 
                            CASE 
                                WHEN CONCAT(',',lc.open_designation,',') LIKE ?
                                    THEN 1
                                    ELSE 0
                            END
                        ELSE 1 END ";//FIND_IN_SET('$USER->open_designation',lc.open_designation)
                // }
                if(!empty($USER->open_location) && $USER->open_location != ""){
                    $sqlparams[] = "%,$USER->open_location,%";
                }else{
                    $sqlparams[] = "";
                }
                    $params[]= " 1 = CASE WHEN lc.open_location IS NOT NULL
                        THEN 
                            CASE 
                                WHEN CONCAT(',',lc.open_location,',') LIKE ?
                                    THEN 1
                                    ELSE 0
                            END 
                        ELSE 1 END ";//FIND_IN_SET('$USER->city',lc.open_location)
                // }
                
                if(!empty($USER->open_grade) && $USER->open_grade != ""){
                    $sqlparams[] = "%,$USER->open_grade,%";
                }else{
                    $sqlparams[] = "";
                }
                    $params[]= " 1 = CASE WHEN lc.open_grade IS NOT NULL
                        THEN 
                            CASE 
                                WHEN CONCAT(',',lc.open_grade,',') LIKE ?
                                    THEN 1
                                    ELSE 0
                            END 
                        ELSE 1 END ";
                // }
                

                if(!empty($params)){
                  $finalparams=implode('AND',$params);
                }else{
                  $finalparams= '1=1' ;
                }
               
                   //OL-1042 Add Target Audience to Classrooms//
                if(cataloglib::$search && cataloglib::$search != 'null'){
                    $cwrsql = " AND lc.name LIKE '%$search%'";
                    $twrsql = " AND t.name LIKE '%$search%'";
                }

               /* if(cataloglib::$category && cataloglib::$category>0){
                    
                }  */  
               //$joinsql = " AND ($finalparams) ";// OR (lc.open_hrmsrole IS NULL AND lc.open_designation IS NULL AND lc.open_location IS NULL AND lc.open_group IS NULL AND lc.department='-1' ) 

                $joinsql = " AND ($finalparams OR (lc.open_hrmsrole IS NULL AND lc.open_designation IS NULL AND lc.open_location IS NULL AND lc.open_grade IS NULL AND lc.open_group IS NULL AND lc.department='-1' ))";
               
                
                if(cataloglib::$enrolltype && cataloglib::$enrolltype>0 ){                 
                    if(cataloglib::$enrolltype==1){
                       /* if(cataloglib::$category && cataloglib::$category>0){
                        
                        } */   
                        $wheresql .= " AND lc.id in (select distinct classroomid from {local_classroom_users} where userid=$USER->id) AND lc.status in (1,3,4) ";             
                    }else{
                        $wheresql .= " AND lc.id not in (select distinct classroomid from {local_classroom_users} where userid=$USER->id) AND lc.status in (1)";
                       $wheresql .= $joinsql;
                    }       
                }else{
                    //$enrolled_classrooms=$DB->get_field_sql("select GROUP_CONCAT(classroomid) from {local_classroom_users} where userid=$USER->id");
                    //
                    //$yetto_enrolled_classrooms=$DB->get_field_sql("select GROUP_CONCAT(lc.id) from {local_classroom} AS lc  where  lc.id not in (select distinct classroomid from {local_classroom_users} where userid=$USER->id) and lc.status in (1) $joinsql");
               
                    $wheresql .= " AND (lc.id in (select classroomid from {local_classroom_users} where userid=$USER->id) or lc.id in (select lc.id from {local_classroom} AS lc  where  lc.id not in (select distinct classroomid from {local_classroom_users} where userid=$USER->id) and lc.status in (1) $joinsql)) AND lc.status in (1,3,4)";
                }
                //$finalsql .= " AND lc.id not in (select distinct classroomid from {local_classroom_trainers} where trainerid=$USER->id)";
                
        }       
        // $orderby = '   ORDER BY lc.id DESC '; //group by lc.id
        $csqlparams = $tsqlparams = $sqlparams;
        $mainparams = array_merge($csqlparams, $tsqlparams);
        

        $finalsql = "select a.* from ( ".$csql.$avgsql.$cfromsql.$leftjoinsql.$wheresql.$cwrsql.$groupby.$usql.$tsql.$avgsql.$tfromsql.$tjoinsql.$leftjoinsql.$wheresql.$twrsql.$groupby." ) as a ";
        $numofilt=$DB->get_records_sql($finalsql, $mainparams);
        $numberofrecords = sizeof($numofilt);

        if (empty($sortid)) {
            $finalsql .= " order by a.id desc ";
        } else {
            $finalsql .= $orderby;
        }
        $facetofacelist=$DB->get_records_sql($finalsql, $mainparams, $startlimit,$perpage);       

        if($return_noofrecords && !$returnobjectlist){
            return  array('numberofrecords'=>$numberofrecords);
        }           
        else if($returnobjectlist && !$return_noofrecords){
            return  array('list'=>$facetofacelist);
        }
        else{
            if($return_noofrecords && $returnobjectlist){
                return  array('numberofrecords'=>$numberofrecords,'list'=>$facetofacelist);                 
            }       
        }       
        
    } // end of get_facetofacelist_query


    public function export_for_template($perpage,$startlimit){
        global $DB, $USER, $CFG, $PAGE;
        //$this->setter();

        // $ratings_exist = \core_component::get_plugin_directory('local', 'ratings');

        // if($ratings_exist){
        //     require_once($CFG->dirroot.'/local/ratings/lib.php');
        // }

        $facetofacelist_ar =$this->get_facetofacelist_query($perpage, $startlimit, true, true);
        $facetofacelist= $facetofacelist_ar['list'];
        $tagsplugin = \core_component::get_plugin_directory('local', 'tags');
        if($tagsplugin){
            $localtags = new \local_tags\tags();
        }
        foreach($facetofacelist as $list){
            
           $iltlocation=$DB->get_field('local_location_institutes','fullname',array('id'=>$list->instituteid));
           if($iltlocation){
            $list->iltlocation=$iltlocation;
           }
           
            $course=$DB->get_record('course', array('id'=>$list->course));   
            
            $name="categoryname";            
            //$coursefileurl = get_ilt_attachment($list->id);
            if(file_exists($CFG->dirroot.'/local/includes.php')){
		            require_once($CFG->dirroot.'/local/includes.php');
	              $includes = new user_course_details();
            }
	        if ($list->classroomlogo > 0){
	            $coursefileurl = (new clroom)->classroom_logo($list->classroomlogo);
	            if($coursefileurl == false){
	                $coursefileurl = $includes->get_classes_summary_files($list); 
	            }
	        } else {
	            $coursefileurl = $includes->get_classes_summary_files($list);
	        }
            $list->categoryname = $name;
            $categoryname = $list->categoryname;
            $list->formattedcategoryname=cataloglib::format_thestring($name);
            $list->iltformatname=cataloglib::format_thestring($list->name);         
          
            if(is_object($coursefileurl)){
                    $coursefileurl=$coursefileurl->out();
            }
            $list->fileurl =   $coursefileurl;           
            
            $list->intro=cataloglib::format_thesummary($list->description);

            // if($ratings_exist){
            //     $list->classroom_avgrating = display_averagerating($list->id, 'classroom');
            // }else{
            //     $list->classroom_avgrating = null;
            // }

             //------------------Date-----------------------
            $startdate =cataloglib::get_thedateformat($list->startdate); 
            $enddate= cataloglib::get_thedateformat($list->enddate); 
           
          
            $list->date = $startdate.' - '.$enddate;
            $list->start_date = date("j M 'y", $list->startdate);

            $list->bands=cataloglib::trim_theband($list->bands);      
            $list->type = ILT;

            $list->enroll=$this->get_the_enrollflag($classroomid);
        
           
            $userenrolstatus = $DB->record_exists('local_classroom_users', array('classroomid' => $list->id, 'userid' => $USER->id));
            $list->userenrolstatus = $userenrolstatus;
            $return=false;
            if($list->id > 0 && ($list->nomination_startdate!=0 || $list->nomination_enddate!=0)){
                $params1 = array();
                $params1['classroomid'] = $list->id;
                // $params1['nomination_startdate'] = date('Y-m-d H:i',time());
                // $params1['nomination_enddate'] = date('Y-m-d H:i',time());
                $params1['nomination_startdate'] = time();
                $params1['nomination_enddate'] = time();

                $sql1="SELECT * FROM {local_classroom} WHERE id=:classroomid 
                    AND CASE WHEN nomination_startdate > 0
                        THEN 
                            CASE WHEN nomination_startdate <= :nomination_startdate
                            THEN 1
                            ELSE 0 END
                        ELSE 1  END = 1 AND 
                        CASE WHEN nomination_enddate > 0
                            THEN CASE WHEN nomination_enddate >= :nomination_enddate
                                THEN 1
                                ELSE 0 END
                        ELSE 1 END = 1 ";
                $return=$DB->record_exists_sql($sql1,$params1);

            }elseif($list->id > 0 && $list->nomination_startdate==0 && $list->nomination_enddate==0){
                $return=true;
            }

            $list->selfenroll=1;
            if ($list->status == 1 && !$userenrolstatus && $return) {
                $list->selfenroll=0;
            }
            $classroom_capacity_check=(new clroom)->classroom_capacity_check( $list->id);
            if($classroom_capacity_check&&$list->status == 1 && !$userenrolstatus && $list->allow_waitinglistusers==0){
                  $list->selfenroll=2;
            }
            $list->enrollmentbtn= $this->get_enrollbtn($list);
            if(class_exists('local_ratings\output\renderer')){
                $rating_render = $PAGE->get_renderer('local_ratings');
                $list->rating_element = $rating_render->render_ratings_data('local_courses', $list->id ,null, 14);
            }else{
                $list->rating_element = '';
            }

            $list->redirect='<a data-action="classroom'.$list->id.'" class="classroominfo" onclick ="(function(e){ require(\'local_catalog/courseinfo\').classroominfo({selector:\'classroom'.$list->id.'\', crid:'.$list->id.'}) })(event)"><button class="cat_btn viewmore_btn">'.get_string('viewmore','local_catalog').'</button></a>'; 
            // classroom view link
            $list->classroomlink= $CFG->wwwroot.'/local/classroom/view.php?cid='.$list->id;
            $context = context_system::instance();

            if($tagsplugin){
                $tags = $localtags->get_item_tags('local_classroom', 'classroom', $list->id, $context->id, $arrayflag = 0, $more = 0);
                $course->tags_title = $tags;
                $tags = strlen($tags) > 25 ? substr($tags, 0, 25)."..." : $tags;
                $list->tags = (!empty($tags) ) ? '<span title="Tags"><i class="fa fa-tags" aria-hidden="true"></i></span> '.$tags: '';
            }
            $finallist[]= $list;
        } // end of foreach     
        
        $finallist['numberofrecords']=$facetofacelist_ar['numberofrecords'];
        $finallist['cfgwwwroot']= $CFG->wwwroot;
        
        return $finallist;
        
    } //end of  get_facetofacelist

   


    private function get_the_enrollflag($classroomid){
        global $USER, $DB;

        $enrolled =$DB->record_exists('local_classroom_users',array('classroomid'=>$classroomid,'userid'=>$USER->id));
        if($enrolled){
            $flag=1;
        }else{
            $flag=0;
        }

        return $flag;
    } // end of get_the_enrollflag

	public function get_enrollbtn($classroominfo){
     global $DB,$USER;
        $classroomid = $classroominfo->id;
        $classroomname =  $classroominfo->name; 
      
     if(!is_siteadmin()){
            if($classroominfo->approvalreqd==1){
                   $waitlist = $DB->get_field('local_classroom_waitlist','id',array('classroomid' => $classroomid,'userid'=>$USER->id,'enrolstatus'=>0));
                if($waitlist > 0){
                        $enrollmentbtn = '<button class="cat_btn btn-primary viewmore_btn">Waiting</button>';
                }else{
                    $componentid =$classroomid;
                    $component = 'classroom';
                    // $request = $DB->get_field('local_request_records','status',array('componentid' => $classroomid,'compname' => $component,'createdbyid'=>$USER->id));
                    $sql = "SELECT status FROM {local_request_records} WHERE componentid=:componentid AND compname LIKE :compname AND createdbyid = :createdbyid ORDER BY id desc ";
                    $request = $DB->get_field_sql($sql, array('componentid' => $classroomid,'compname' => $component,'createdbyid'=>$USER->id));
                    if($request=='PENDING'){
                        $enrollmentbtn = '<button class="cat_btn btn-primary viewmore_btn">Processing</button>';
                    }else{
                        $enrollmentbtn =requestapi::get_requestbutton($componentid, $component, $classroomname);
                    }
                }
            }
            else{
                $waitlist = $DB->get_field('local_classroom_waitlist','id',array('classroomid' => $classroomid,'userid'=>$USER->id,'enrolstatus'=>0));
                if($waitlist > 0){
                        $enrollmentbtn = '<button class="cat_btn btn-primary viewmore_btn">Waiting</button>';
                }else{

                    // $prerequisites = $DB->get_record_sql("SELECT lc.open_prerequisites  FROM {local_classroom_courses} lcc JOIN {course} lc ON lc.id=lcc.courseid WHERE lcc.classroomid =:classroomid",array('classroomid'=>$classroomid));
                    $prerequisites = $DB->get_record_sql("SELECT lc.open_prerequisites  FROM {local_classroom} lc WHERE  lc.id =:classroomid",array('classroomid'=>$classroomid));
                    if($prerequisites->open_prerequisites){
                        $user_completedstatus = $this->completed_prereqcourse_ilt_enroluser($prerequisites->open_prerequisites,$USER->id);
                        if($user_completedstatus){
                            $enrollmentbtn = '<a href="javascript:void(0);" class="" alt = ' . get_string('enroll','local_classroom'). ' title = ' .get_string('enroll','local_classroom'). ' onclick="(function(e){ require(\'local_classroom/classroom\').ManageclassroomStatus({action:\'selfenrol\', id: '.$classroomid.', classroomid:'.$classroomid.',actionstatusmsg:\'classroom_self_enrolment\',classroomname:\''.$classroomname.'\'}) })(event)" ><button class="cat_btn viewmore_btn">'.get_string('enroll','local_classroom').'</button></a>';
                        }else{
                            $enrollmentbtn = '<a class="" id="usernotcompleted_sessionprereq" alt = ' . get_string('noenroll','local_classroom'). ' title = ' .get_string('enroll','local_classroom'). ' ><button class="cat_btn viewmore_btn">'.get_string('enroll','local_classroom').'</button></a>';
                        }
                    }else{

                     $enrollmentbtn = '<a href="javascript:void(0);" class="" alt = ' . get_string('enroll','local_classroom'). ' title = ' .get_string('enroll','local_classroom'). ' onclick="(function(e){ require(\'local_classroom/classroom\').ManageclassroomStatus({action:\'selfenrol\', id: '.$classroomid.', classroomid:'.$classroomid.',actionstatusmsg:\'classroom_self_enrolment\',classroomname:\''.$classroomname.'\'}) })(event)" ><button class="cat_btn viewmore_btn">'.get_string('enroll','local_classroom').'</button></a>';
                    }
                }
            }  
  }
  return $enrollmentbtn;
    } // end of get_enrollbtn

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param renderer_base $output Renderer base.
     * @return stdClass
     */
   /* public function export_for_template(renderer_base $output) {
        global $USER, $DB;

        $data = new stdClass();
       
        $data->competencies = array();
        $data->competencyframeworkid =  $this->competencyframeworkid;
        $helper = new performance_helper();
        

        $competency = competencyview::competency_record($this->competencyid);

        $context = $helper->get_context_from_competency($competency[$this->competencyid]);
        $compexporter = new competency_exporter($competency[$this->competencyid], array('context' => $context));

        foreach($this->competencycourselist as $competencycourse){
            
            $coursefullname = $DB->get_field('course','fullname',array('id'=>$competencycourse->courseid));
             $onerow = array(                
                'coursefullname' => $coursefullname,
                'competencycourse' =>$competencycourse->courseid,
                 
                //'comppath' => $pathexporter->export($output)
            );
            array_push($data->competencies, $onerow);  
        } // end of foreach          
       
         //if($data->competencies)
        $data->competency =$compexporter->export($output);

        $data->competencies= json_encode($data->competencies);

        return $data; 

    } // end of  function
  */
function completed_prereqcourse_ilt_enroluser($iltcourseids,$userid){
        global $DB;
        if($iltcourseids){

            $pre_courses = explode(',',$iltcourseids);
            $completed=array();
            foreach($pre_courses as $course){
            $sql="SELECT * from {course_completions} where course=$course and userid= {$userid} and timecompleted is not NULL ";
            $check=$DB->get_record_sql($sql);
                if($check){
                  $completed[]=1;
                }else{
                  $completed[]=0;
                }
            }
            if (in_array("0", $completed)){
                return false;
            }else{
                return true;
            }

        }else{
                return false;

            }
     }

} // end of class






