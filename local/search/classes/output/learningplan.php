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
 * @package    local_search
 * @copyright  2018 hemalathacarun <hemalatha@eabyas.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_search\output;
defined('MOODLE_INTERNAL') || die();

use renderable;
use templatable;
use renderer_base;
use stdClass;
use moodle_url;
use context_system;
use context_course;
use context_user;
use html_writer;
use local_search\output\cataloglib;
use local_classroom\classroom as clroom;
use local_learningplan\lib\lib as lpn;
use local_request\api\requestapi;

/**
 * Class containing data for course competencies page
 *
 * @copyright  2019 eabyas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class learningplan implements renderable{
	public function get_learningpathlist_query($perpage,$startlimit,$return_noofrecords=false, $returnobjectlist=false,$tagitems = array(), $selectedvendors = array()){
		global $DB, $USER, $CFG;
		
		$search = cataloglib::$search;
		//------main queries written here to fetch Classrooms or  session based on condition
	    $selectsql = "SELECT llp.*,llp.startdate as trainingstartdate, llp.enddate as trainingenddate ";
        $fromsql = " from {local_learningplan} llp  ";

        $leftjoinsql = '';
        
        // added condition for not displaying retired learningplans.
        $wheresql = " where llp.id > 0 and llp.visible=1  "; //AND llp.open_status <> 4

		//------if not site admin sessions list will be filter by location or bands
		if(cataloglib::$search && cataloglib::$search!='null'){
			$searchsql = " AND llp.name LIKE '%$search%'";
		} 
		 
		$usercontext = context_user::instance($USER->id);
		if(!is_siteadmin()){
		    $params = array();
            $group_list = $DB->get_records_sql_menu("SELECT cm.id,cm.cohortid as groupid from {cohort_members} cm where cm.userid IN ({$USER->id})");
             
            $groups_members = implode(',', $group_list);
        	$grouquery = array(" llp.open_group IS NULL ", " llp.open_group = -1 ");
            if(!empty($group_list)){
                foreach ($group_list as $key => $group) {
                    $grouquery[] = " concat(',',llp.open_group,',') LIKE concat('%,', $group, ',%') ";
            	}
         	}
            $groupqueeryparams =implode('OR',$grouquery);
            $params[]= '('.$groupqueeryparams.')';
            
            if(!empty($USER->open_departmentid) && $USER->open_departmentid != ""){
            	$departmentlike = "'%,$USER->open_departmentid,%'";
            }else{
				$departmentlike = "''";
            }
	        $params[]= " 1 = CASE WHEN llp.department!='-1'
				THEN 
					CASE WHEN CONCAT(',',llp.department,',') LIKE {$departmentlike} 
						THEN 1
						ELSE 0 END
				ELSE 1 END ";

			if(!empty($USER->open_subdepartment) && $USER->open_subdepartment != ""){
            	$subdepartmentlike = "'%,$USER->open_subdepartment,%'";
            }else{
				$subdepartmentlike = "''";
            }
	        $params[]= " 1 = CASE WHEN llp.subdepartment!='-1'
				THEN 
					CASE WHEN CONCAT(',',llp.subdepartment,',') LIKE {$subdepartmentlike} 
						THEN 1
						ELSE 0 END
				ELSE 1 END ";
		    
	    	if(!empty($USER->open_hrmsrole) && $USER->open_hrmsrole != ""){
		    	$hrmsrolelike = "'%,$USER->open_hrmsrole,%'";
			}else{
				$hrmsrolelike = "''";
			}
            $params[]= " 1 = CASE WHEN llp.open_hrmsrole IS NOT NULL
				THEN 
					CASE WHEN CONCAT(',',llp.open_hrmsrole,',') LIKE {$hrmsrolelike}
					THEN 1
					ELSE 0 END 
				ELSE 1 END ";
		    
	    	if(!empty($USER->open_designation) && $USER->open_designation != ""){
		    	$designationlike = "'%,$USER->open_designation,%'";
			}else{
				$designationlike = "''";
			}
            $params[]= " 1 = CASE WHEN llp.open_designation IS NOT NULL
				THEN 
					CASE WHEN CONCAT(',',llp.open_designation,',') LIKE {$designationlike}
						THEN 1
						ELSE 0 END 
				ELSE 1 END ";
           
			if(!empty($USER->open_location) && $USER->open_location != ""){
				$citylike = "'%,$USER->open_location,%'";
			}else{
				$citylike = "''";
			}
	        $params[]= " 1 = CASE WHEN llp.open_location IS NOT NULL
				THEN 
					CASE WHEN CONCAT(',',llp.open_location,',') LIKE {$citylike}
						THEN 1
						ELSE 0 END 
				ELSE 1 END ";

			if(!empty($USER->open_grade) && $USER->open_grade != ""){
				$gradelike = "'%,$USER->open_grade,%'";
			}else{
				$gradelike = "''";
			}
	        $params[]= " 1 = CASE WHEN llp.open_grade IS NOT NULL
				THEN 
					CASE WHEN CONCAT(',',llp.open_grade,',') LIKE {$gradelike}
						THEN 1
						ELSE 0 END 
				ELSE 1 END ";


			if(!is_siteadmin()){
            $params[]= " 1 = CASE 
                                WHEN llp.learning_type = 1
                                    THEN 
                                        CASE 
                                            WHEN ((llp.costcenter = $USER->open_costcenterid) AND (llp.department IS NULL OR llp.department = 0 OR (llp.department = $USER->open_departmentid))) 
                                            THEN 1
                                            ELSE 0 
                                        END                                         
                                ELSE 1 END ";
        	}          
		    
		    if(!empty($params)){
		    	$finalparams=implode('AND',$params);
		    }else{
		    	$finalparams= '1=1' ;
		    }
		    $wheresql .= " AND ($finalparams OR (llp.open_hrmsrole IS NULL AND llp.open_designation IS NULL AND llp.open_location IS NULL AND llp.open_grade IS NULL AND llp.open_group IS NULL AND llp.department='-1' ) )";

	      
			if(cataloglib::$enrolltype && cataloglib::$enrolltype>0 ){					
				if(cataloglib::$enrolltype==1){
					$wheresql .= " AND llp.id in (select distinct planid from {local_learningplan_user} where userid=$USER->id)";				
				}else{
					$wheresql .= " AND llp.id not in (select distinct planid from {local_learningplan_user} where userid=$USER->id)";
				}		
		    }
		}

        $countsql = "SELECT llp.id ";
        $countquery = $countsql.$fromsql.$leftjoinsql.$wheresql.$searchsql;
        $countquery .= " GROUP BY llp.id";
        $numberofrecords = sizeof($DB->get_records_sql($countquery));

		$finalsql = $selectsql.$fromsql.$leftjoinsql.$wheresql.$searchsql;
		$finalsql .= " GROUP BY llp.id ORDER by llp.id DESC ";
		 
        $learningplanlist = $DB->get_records_sql($finalsql, array(), $startlimit, $perpage);

		if($return_noofrecords && !$returnobjectlist){
			return  array('numberofrecords'=>$numberofrecords);
		}			
	    else if($returnobjectlist && !$return_noofrecords){
			return  array('list'=>$learningplanlist);
		}
		else{
			if($return_noofrecords && $returnobjectlist){
				return  array('numberofrecords'=>$numberofrecords,'list'=>$learningplanlist);	
			}	
		}
	}

	public function export_for_template($perpage,$startlimit,$tagitems, $selectedvendors){
		global $DB, $USER, $CFG, $PAGE,$OUTPUT;
		$context = context_system::instance();
		$certificationlist_ar =$this->get_learningpathlist_query($perpage, $startlimit, true, true,$tagitems, $selectedvendors);
		$certificationlist= $certificationlist_ar['list'];
        foreach($certificationlist as $list){
		  $iltlocation=$DB->get_field('local_location_institutes','fullname',array('id'=>$list->instituteid));
		  if($iltlocation){
			$list->iltlocation=$iltlocation;
		   }
		    $course=$DB->get_record('course', array('id'=>$list->course));
			$name="categoryname";
			$coursefileurl = (new lpn)->get_learningplansummaryfile($list->id);
			$list->categoryname = $name;
			$list->formattedcategoryname = cataloglib::format_thestring($name);
			$list->iltfullformatname = cataloglib::format_thestring($list->name); 
			$iltname = cataloglib::format_thestring($list->name);
			if (strlen($iltname)>60){
                $iltname = substr($iltname, 0, 60)."...";
                $list->iltformatname = cataloglib::format_thestring($iltname) ;
            }else {
            	$list->iltformatname = cataloglib::format_thestring($list->name); 
            }
		
            $list->fileurl =   $coursefileurl;
			$list->intro=cataloglib::format_thesummary($list->description);
			//------------------Date-----------------------
			$startdate = cataloglib::get_thedateformat($list->startdate);
			$enddate = cataloglib::get_thedateformat($list->enddate);	
			$list->date = $startdate.' - '.$enddate;
			$list->start_date = date("j M 'y", $list->startdate);

			$lpcoursecount = $this->getcoursecount($list->id);
			$list->coursecount = $lpcoursecount ? $lpcoursecount : 'N/A';

			//-------bands----------------------------
			$list->bands = cataloglib::trim_theband($list->bands); 
			$list->type = LEARNINGPATH;
			$list->enroll = $this->get_enrollflag($list->id);
		    $userenrolstatus = $DB->record_exists('local_learningplan_user', array('planid' => $list->id, 'userid' => $USER->id));
		    $return=false;

	        $list->enrollmentbtn = $this->get_enrollbtn($list);

	        if(class_exists('local_ratings\output\renderer')){
	            $rating_render = $PAGE->get_renderer('local_ratings');
	            $list->rating_element = $rating_render->render_ratings_data('local_learningplan', $list->id ,null, 14);
			}else{
				$list->rating_element = '';
		    }
		    if($list->enroll == 1){
		    	$list->redirect='<a href ="'.$CFG->wwwroot.'/local/learningplan/view.php?id='.$list->id.'" ><button class="cat_btn viewmore_btn">'.get_string('gotolpath','local_search').'</button></a>';
		    }else{
		    	$list->redirect='<span data-action="learningplan'.$list->id.'" class="learningplaninfo d-block" onclick ="(function(e){ require(\'local_search/courseinfo\').learningplaninfo({selector:\'learningplan'.$list->id.'\', learningplanid:'.$list->id.'}) })(event)"><span>'.get_string('viewmore','local_search').'</span></span>';	
		    }
		    $list->copylink = '';
		    if(is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations', $context) || has_capability('local/costcenter:manage_ownorganization', $context) || has_capability('local/costcenter:manage_owndepartments', $context)){
	        	$list->copylink = '<a data-action="courseinfo'.$course->id.'" onclick ="(function(e){ require(\'local_search/courseinfo\').copy_url({module:\'learningplan\', moduleid:'.$list->id.'}) })(event)"><button class="cat_btn viewmore_btn">'.get_string('copyurl', 'local_search').'</button></a>';
		    }
	        $enrolled = $DB->get_field('local_learningplan_user','id', array('planid' => $list->id, 'userid' => $USER->id));
	        $list->selfenroll = $enrolled ? 2 : 1 ;
			$list->learningplanlink= $CFG->wwwroot.'/local/learningplan/view.php?id='.$list->id;
			
            $finallist[]= $list;	
		} // end of foreach		
		
		$finallist['numberofrecords']=$certificationlist_ar['numberofrecords'];
	 
		return $finallist;
	}

	private function get_enrollflag($certificationid){
        global $USER, $DB;

        $enrolled =$DB->record_exists('local_learningplan_user',array('planid'=>$certificationid,'userid'=>$USER->id));
        if($enrolled){
            $flag=1;
        }else{
            $flag=0;
        }
        return $flag;
    } // end of get_enrollflag
    public function get_enrollbtn($planinfo){
     global $DB,$USER;
        $planid = $planinfo->id;
        $planname =  $planinfo->name; 
      
	    if(!is_siteadmin()){
	            if($planinfo->approvalreqd==1){
	                $componentid =$planid;
	                $component = 'learningplan';
	                $sql = "SELECT status FROM {local_request_records} WHERE componentid=:componentid AND compname LIKE :compname AND createdbyid = :createdbyid ORDER BY id desc ";
	                $request = $DB->get_field_sql($sql,array('componentid' => $planid,'compname' => $component,'createdbyid'=>$USER->id));
					if($request=='PENDING'){
					$enrollmentbtn = '<button class="cat_btn btn-primary catbtn_process viewmore_btn">Processing</button>';
					}else{
					$enrollmentbtn =requestapi::get_requestbutton($componentid, $component, $planname);
					}
	            }
	            else{
	   				$enrollmentbtn = '<a href="javascript:void(0);" class="cat_btn viewmore_btn fakebtn btn-primary" alt = ' . get_string('enroll','local_search'). ' title = ' .get_string('enroll','local_search'). ' onclick="(function(e){ require(\'local_learningplan/courseenrol\').enrolUser({planid:'.$planid.', userid:'.$USER->id.', planname:\''.$planname.'\' }) })(event)" ><button class="cat_btn btn-primary catbtn_request viewmore_btn">'.get_string('enroll','local_classroom').'</button></a>';
	   			}  
	  	}
  		return $enrollmentbtn;
    } // end of get_enrollbtn
    private function getcoursecount($planid){
    	global $DB;
    	$coursecount = $DB->count_records('local_learningplan_courses',array('planid'=>$planid));
    	return $coursecount;

    }
 
 }
