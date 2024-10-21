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
 * @package    local_learningplan
 * @copyright  2018 hemalathacarun <hemalatha@eabyas.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_learningplan\output;
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
use local_search\output\searchlib;
use local_classroom\classroom as clroom;
use local_learningplan\lib\lib as lpn;
use local_request\api\requestapi;

/**
 * Class containing data for course competencies page
 *
 * @copyright  2019 eabyas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class search implements renderable{
	public function get_learningpathlist_query($perpage, $startlimit, $return_noofrecords = false, $returnobjectlist = false, $filters = array()){
		global $DB, $USER, $CFG;
		
		$search = searchlib::$search;
		//------main queries written here to fetch Classrooms or  session based on condition
	    $selectsql = "SELECT llp.*,llp.startdate as trainingstartdate, llp.enddate as trainingenddate ";
        $fromsql = " from {local_learningplan} llp  ";

        $leftjoinsql = '';
        
        // added condition for not displaying retired learningplans.
        $wheresql = " where llp.id > 0 and llp.visible=1  and llp.selfenrol = 1"; //AND llp.open_status <> 4

		//------if not site admin sessions list will be filter by location or bands
		if(searchlib::$search && searchlib::$search!='null'){
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
	        $params[]= " 1 = CASE WHEN llp.open_grade  <> -1
				THEN 
					CASE WHEN CONCAT(',',llp.open_grade,',') LIKE {$gradelike}
						THEN 1
						ELSE 0 END 
				ELSE 1 END ";

 
	/* 		if(!empty($USER->open_ouname) && $USER->open_ouname != ""){
				$open_ounamelike = "'%,$USER->open_ouname,%'";
			}else{
				$open_ounamelike = "''";
			}
			$params[]= " 1 = CASE WHEN llp.open_ouname <> -1
				THEN 
					CASE WHEN CONCAT(',',llp.open_ouname,',') LIKE {$open_ounamelike}
					THEN 1
					ELSE 0 END 
				ELSE 1 END ";
 */
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

		/* 	$wheresql .= " AND ($finalparams OR ((llp.open_hrmsrole IS NULL OR llp.open_hrmsrole = '0' OR llp.open_hrmsrole = '-1') OR 
								( llp.open_designation IS NULL OR  llp.open_designation = '0' OR  llp.open_designation = '-1') OR 
								( llp.open_location IS NULL OR llp.open_location = '0' OR llp.open_location = '-1' ) OR 
								( llp.open_grade IS NULL OR llp.open_grade ='0' OR llp.open_grade = '-1') OR 
								( llp.department IS NULL OR llp.department = '0' OR llp.department='-1' ))) ";  */
        
		  // $wheresql .= " AND ($finalparams OR (llp.open_hrmsrole IS NULL OR llp.open_designation IS NULL OR llp.open_location IS NULL AND llp.open_grade IS NULL AND llp.department='-1' ) )";
		    $wheresql .= " AND ($finalparams ) ";

			if(searchlib::$enrolltype && searchlib::$enrolltype>0 ){					
				if(searchlib::$enrolltype==1){
					$wheresql .= " AND llp.id in (select distinct planid from {local_learningplan_user} where userid=$USER->id)";				
				}else{
					$wheresql .= " AND llp.id not in (select distinct planid from {local_learningplan_user} where userid=$USER->id)";
				}		
		    }
		}

		foreach ($filters as $filtertype => $filtervalues) {
            switch ($filtertype) {
                case 'status':
                    $statussql = [];
                    foreach ($filters['status'] as $statusfilter) {
                        switch ($statusfilter) {
                            case 'notenrolled':
                                $statussql[] = " llp.id  not in (SELECT lpu.planid FROM {local_learningplan_user} lpu where lpu.userid=$USER->id) ";
                                break;
                            case 'enrolled':
                                $statussql[] = " llp.id in (SELECT lpu.planid FROM {local_learningplan_user} lpu where lpu.userid=$USER->id and lpu.status  <>1 ) ";
                                break;                       
                        }
                    }
				
                    if (!empty($statussql)) {
                        $wheresql .= " AND (" . implode('OR', $statussql) . ' ) ';
                    }
                    break;
         /*   
                case 'categories':
                    $categories = is_array($filtervalues) ? $filtervalues : [$filtervalues];
                    list($categoriessql, $categoriesparams) = $DB->get_in_or_equal($categories, SQL_PARAMS_NAMED, 'categories');
                    $wheresql .= " AND c.category $categoriessql ";
                    $params = array_merge($params, $categoriesparams);
                    break;   */             
            }
        }

        $countsql = "SELECT llp.id ";
        $countquery = $countsql.$fromsql.$leftjoinsql.$wheresql.$searchsql;
        $countquery .= " GROUP BY llp.id";
        $numberofrecords = count($DB->get_records_sql($countquery));

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

	public function export_for_template($perpage,$startlimit,$selectedfilter = array()){
		global $DB, $USER, $CFG, $PAGE,$OUTPUT;
		$context = context_system::instance();
		$certificationlist_ar =$this->get_learningpathlist_query($perpage, $startlimit, true, true);
		$certificationlist= $certificationlist_ar['list'];
        foreach($certificationlist as $list){
		 
		    $course=$DB->get_record('course', array('id'=>$list->course));
			$name="categoryname";
			$coursefileurl = (new lpn)->get_learningplansummaryfile($list->id);
			$list->categoryname = $name;
			$list->formattedcategoryname = searchlib::format_thestring($name);
			$list->lpfullformatname = searchlib::format_thestring($list->name); 
			$lpname = searchlib::format_thestring($list->name);
			if (strlen($lpname)>60){
                $lpname = substr($lpname, 0, 60)."...";
                $list->lpformatname = searchlib::format_thestring($lpname) ;
            }else {
            	$list->lpformatname = searchlib::format_thestring($list->name); 
            }
		
            $list->fileurl =   $coursefileurl;
			$list->intro=searchlib::format_thesummary($list->description);
			//------------------Date-----------------------
			$startdate = searchlib::get_thedateformat($list->startdate);
			$enddate = searchlib::get_thedateformat($list->enddate);	
			$list->date = $startdate.' - '.$enddate;
			$list->start_date = date("j M 'y", $list->startdate);

			$lpcoursecount = $this->getcoursecount($list->id);
			$list->coursecount = $lpcoursecount ? $lpcoursecount : 'N/A';

			//-------bands----------------------------
			$list->bands = searchlib::trim_theband($list->bands); 
			$list->type = 4;
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
            	$list->redirect='<a href ="'.$CFG->wwwroot.'/local/learningplan/view.php?id='.$list->id.'" target="_blank" class="cat_btn viewmore_btn">'.get_string('gotolpath','local_search').'</a>';
		    }else{
                $list->redirect='<a href ="'.$CFG->wwwroot.'/local/learningplan/lpathinfo.php?id='.$list->id.'" class="viewmore_btn" target="_blank">'.get_string('view_details','local_search').'</a>';
			}


		    $list->copylink = '';
		    if(is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations', $context) || has_capability('local/costcenter:manage_ownorganization', $context) || has_capability('local/costcenter:manage_owndepartments', $context)){
	        	$list->copylink = '<a data-action="courseinfo'.$course->id.'" onclick ="(function(e){ require(\'local_search/courseinfo\').copy_url({module:\'learningplan\', moduleid:'.$list->id.'}) })(event)"><button class="cat_btn viewmore_btn">'.get_string('copyurl', 'local_search').'</button></a>';
		    }
	        $enrolled = $DB->get_field('local_learningplan_user','id', array('planid' => $list->id, 'userid' => $USER->id));
	        $list->selfenroll = $enrolled ? 2 : 1 ;
			$list->learningplanlink= $CFG->wwwroot.'/local/learningplan/view.php?id='.$list->id;
			$bookmarks = $DB->get_record_sql("SELECT * FROM {block_custom_userbookmark} WHERE userid = $USER->id AND courseid = $list->id");
            $bookmarkurl = $bookmarks->url;
            if($bookmarkurl){
                $bookmarkurl = '<i class="fa fa-bookmark fa-2x pull-right" aria-hidden="true" onclick="deleteBookmark(\''.$CFG->wwwroot.'/local/learningplan/lpathinfo.php?id='.$list->id.'\','.$USER->id.','.$list->id.')"></i>';
            }else{
                $bookmarkurl = '<i class="fa fa-bookmark-o fa-2x pull-right" aria-hidden="true" onclick="addBookmark(\''.$CFG->wwwroot.'/local/learningplan/lpathinfo.php?id='.$list->id.'\', `'.$list->lpformatname .'`, \'LearningPath\', '.$list->id.')"></i>';
            }
            $list->bookmarkurl =  $bookmarkurl;
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
						$enrollmentbtn = '<button class="cat_btn">Processing</button>';
					}else{
						$enrollmentbtn =requestapi::get_requestbutton($componentid, $component, $planname);
					}
	            }
	            else{
	   				$enrollmentbtn = '<a href="javascript:void(0);"  alt = ' . get_string('selfenrol','local_search'). ' title = ' .get_string('selfenrol','local_search'). ' onclick="(function(e){ require(\'local_learningplan/courseenrol\').enrolUser({planid:'.$planid.', userid:'.$USER->id.', planname:\''.$planname.'\' }) })(event)" ><button class="cat_btn btn-primary viewmore_btn">'.get_string('selfenrol','local_search').'</button></a>';
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
