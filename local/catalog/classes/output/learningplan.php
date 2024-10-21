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
use html_writer;
use local_catalog\output\cataloglib;
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
	public function get_learningpathlist_query($perpage,$startlimit,$return_noofrecords=false, $returnobjectlist=false){
		global $DB, $USER, $CFG;
		
		$search = cataloglib::$search;
		$sortid = cataloglib::$sortid;
		//------main queries written here to fetch Classrooms or  session based on condition
	    $csql="SELECT llp.*,llp.startdate as trainingstartdate, llp.enddate as trainingenddate ";
          $cfromsql = " from {local_learningplan} llp  ";
        $usql = " UNION ";
        $tsql="SELECT  llp.*,llp.startdate as trainingstartdate, llp.enddate as trainingenddate ";
          $tfromsql = " from {local_learningplan} llp  ";

          $tjoinsql = " JOIN {tag_instance} tgi ON tgi.itemid = llp.id AND tgi.itemtype = 'learningplan' AND tgi.component = 'local_learningplan' JOIN {tag} t ON t.id = tgi.tagid ";

          $leftjoinsql = $groupby = $orderby = $avgsql = '';
        if (!empty($sortid)) {
          switch($sortid) {
            case 'highrate':
            if ($DB->get_manager()->table_exists('local_rating')) {
                $avgsql .= " , AVG(r.rating) as rates ";
                $leftjoinsql .= " LEFT JOIN {local_rating} as r ON r.itemid = llp.id AND r.ratearea = 'local_learningplan' ";
                $groupby .= " group by llp.id ";
                $orderby .= " order by rates desc ";
            }
            break;
            case 'lowrate':  
            if ($DB->get_manager()->table_exists('local_rating')) {  
                $avgsql .= " , AVG(r.rating) as rates  ";
                $leftjoinsql .= " LEFT JOIN {local_rating} as r ON r.itemid = llp.id AND r.ratearea = 'local_learningplan' ";
                $groupby .= " group by llp.id ";
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

        $wheresql = " where llp.id>0 and llp.visible=1 ";
		//------if not site admin sessions list will be filter by location or bands
     	if(is_siteadmin()){
			if(cataloglib::$search && cataloglib::$search!='null'){
				 $cwrsql = " AND llp.name LIKE '%$search%'";
				 $twrsql = " AND t.name LIKE '%$search%'";
			}
	 	} 
		 
		$usercontext = context_user::instance($USER->id);
		if(!is_siteadmin()){
				
				//-----filter by costcenter
				if($USER->open_costcenterid){
					$wheresql .= " AND (( llp.costcenter !=0 AND $USER->open_costcenterid in (llp.costcenter)))";      
				} ;
				//OL-1042 Add Target Audience to Learningplan//
                $params = array();
                
                $group_list = $DB->get_records_sql_menu("SELECT cm.id,cm.cohortid as groupid from {cohort_members} cm where cm.userid IN ({$USER->id})");
                 
                //added by sarath for jira issu 1761
                // if (!empty($group_list)){
                    $groups_members = implode(',', $group_list);
                	$grouquery = array(" llp.open_group IS NULL ", " llp.open_group = -1 ");
                    if(!empty($group_list)){
                        foreach ($group_list as $key => $group) {
                            // $grouquery[] = " FIND_IN_SET($group,llp.open_group) ";
                            $grouquery[] = " concat(',',llp.open_group,',') LIKE concat('%,', $group, ',%') ";
                    	}
                 	}
                    $groupqueeryparams =implode('OR',$grouquery);
                    $params[]= '('.$groupqueeryparams.')';
                // }
                 
               // if(!empty($USER->open_departmentid)){
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
					ELSE 1 END ";//FIND_IN_SET($USER->open_departmentid,llp.department)
			    //}
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
					ELSE 1 END ";//FIND_IN_SET($USER->open_departmentid,llp.department)
			    //}
			    //if(!empty($USER->open_hrmsrole)){
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
					ELSE 1 END ";//FIND_IN_SET('$USER->open_hrmsrole',llp.open_hrmsrole)
			   // }
			    //if(!empty($USER->open_designation)){
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
					ELSE 1 END ";//FIND_IN_SET('$USER->open_designation',llp.open_designation)
			    //}
               // if(!empty($USER->city)){
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
					ELSE 1 END ";//FIND_IN_SET('$USER->city',llp.open_location)
			   // }


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
			    
			    if(!empty($params)){
			    	$finalparams=implode('AND',$params);
			    }else{
			    	$finalparams= '1=1' ;
			    }
			    $wheresql .= " AND ($finalparams OR (llp.open_hrmsrole IS NULL AND llp.open_designation IS NULL AND llp.open_location IS NULL AND llp.open_grade IS NULL AND llp.open_group IS NULL AND llp.department='-1' ) )";
             //OL-1042 Add Target Audience to Learningplan//


			    if(cataloglib::$search && cataloglib::$search!='null'){
			        $cwrsql = " AND llp.name LIKE '%$search%'";
			        $twrsql = " AND t.name LIKE '%$search%'";
		        }
		         $category=cataloglib::$category; 
				if(cataloglib::$category && cataloglib::$category>0){
					
				}	

		      
				if(cataloglib::$enrolltype && cataloglib::$enrolltype>0 ){					
					if(cataloglib::$enrolltype==1){
						if(cataloglib::$category && cataloglib::$category>0){
						
						}	
						$wheresql .= " AND llp.id in (select distinct planid from {local_learningplan_user} where userid=$USER->id)";				
					}else{
						$wheresql .= " AND llp.id not in (select distinct planid from {local_learningplan_user} where userid=$USER->id)";
					}		
			    }
				
		}       
        // $orderby = '  ORDER BY llp.id DESC'; //group by llp.id
      
		

	    $finalsql = "SELECT * from ( ".$csql.$avgsql.$cfromsql.$leftjoinsql.$wheresql.$cwrsql.$groupby.$usql.$tsql.$avgsql.$tfromsql.$tjoinsql.$leftjoinsql.$wheresql.$twrsql.$groupby." ) as a ";

	    $numofilt=$DB->get_records_sql($finalsql);
		$numberofrecords = sizeof($numofilt);

		if (empty($sortid)) {
            $finalsql .= " order by a.id desc ";
        } else {
            $finalsql .= $orderby;
        }
		
        $learningplanlist=$DB->get_records_sql($finalsql, array(), $startlimit, $perpage);
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

	public function export_for_template($perpage,$startlimit){
		global $DB, $USER, $CFG, $PAGE;
		$lpathslist_ar =$this->get_learningpathlist_query($perpage, $startlimit, true, true);
		$lpathslist= $lpathslist_ar['list'];
	    $tagsplugin = \core_component::get_plugin_directory('local', 'tags');
	    if($tagsplugin){
	        $localtags = new \local_tags\tags();
	    }
		foreach($lpathslist as $list){
		
		   $iltlocation=$DB->get_field('local_location_institutes','fullname',array('id'=>$list->instituteid));
		  	if($iltlocation){
				$list->iltlocation=$iltlocation;
		   	}
		    $course=$DB->get_record('course', array('id'=>$list->course));
			$name="categoryname";
			$coursefileurl = (new lpn)->get_learningplansummaryfile($list->id);
			$list->categoryname = $name;
			$list->formattedcategoryname = cataloglib::format_thestring($name);
			$list->iltformatname = cataloglib::format_thestring($list->name); 
			if(is_object($coursefileurl)){
					$coursefileurl=$coursefileurl->out();
			}
            $list->fileurl =   $coursefileurl;
			$list->intro=cataloglib::format_thesummary($list->description);
			//------------------Date-----------------------
			$startdate = cataloglib::get_thedateformat($list->startdate);
			$enddate = cataloglib::get_thedateformat($list->enddate);	
			$list->date = $startdate.' - '.$enddate;
			$list->start_date = date("j M 'y", $list->startdate);
			//-------bands----------------------------
			$list->bands = cataloglib::trim_theband($list->bands); 
			$list->type = LEARNINGPATH;
			$list->enroll = $this->get_enrollflag($list->id);
		    $userenrolstatus = $DB->record_exists('local_learningplan_user', array('planid' => $list->id, 'userid' => $USER->id));
		    $return=false;
		/*	if($list->id > 0 && $list->nomination_startdate!=0 && $list->nomination_enddate!=0){
	            $params1 = array();
	            $params1['certificationid'] = $list->id;
	            $params1['nomination_startdate'] = date('Y-m-d H:i',time());
	            $params1['nomination_enddate'] = date('Y-m-d H:i',time());

	            $sql1="SELECT * FROM {local_certification} where id=:certificationid and (from_unixtime(nomination_startdate,'%Y-%m-%d %H:%i')<=:nomination_startdate and from_unixtime(nomination_enddate,'%Y-%m-%d %H:%i')>=:nomination_enddate)";
	           
	            $return=$DB->record_exists_sql($sql1,$params1); 

	        }elseif($list->id > 0 && $list->nomination_startdate==0 && $list->nomination_enddate==0){
	        	$return=true;
	        }
	        */
	        $list->enrollmentbtn = $this->get_enrollbtn($list);

	        if(class_exists('local_ratings\output\renderer')){
	            $rating_render = $PAGE->get_renderer('local_ratings');
	            $list->rating_element = $rating_render->render_ratings_data('local_learningplan', $list->id ,null, 14);
			}else{
				$list->rating_element = '';
		    }

	        $list->redirect = '<a data-action="learningplan'.$list->id.'" class="learningplaninfo" onclick ="(function(e){ require(\'local_catalog/courseinfo\').learningplaninfo({selector:\'learningplan'.$list->id.'\', learningplanid:'.$list->id.'}) })(event)"><button class="cat_btn viewmore_btn">'.get_string('viewmore','local_catalog').'</button></a>';
	        $enrolled = $DB->get_field('local_learningplan_user','id', array('planid' => $list->id, 'userid' => $USER->id));
	        $list->selfenroll = $enrolled ? 2 : 1 ;
			$list->learningplanlink = $CFG->wwwroot.'/local/learningplan/view.php?id='.$list->id;
			$context = context_system::instance();
			if($tagsplugin){
	            $tags = $localtags->get_item_tags('local_learningplan', 'learningplan', $list->id, $context->id, $arrayflag = 0, $more = 0);
	            $course->tags_title = $tags;
	            $tags = strlen($tags) > 25 ? substr($tags, 0, 25)."..." : $tags;
	            $list->tags = (!empty($tags) ) ? '<span title="Tags"><i class="fa fa-tags" aria-hidden="true"></i></span> '.$tags: '';
	        }
		    $finallist[]= $list;	
		} // end of foreach		
		
		$finallist['numberofrecords'] = $lpathslist_ar['numberofrecords'];
	 
		return $finallist;
	}

	private function get_enrollflag($certificationid){
        global $USER, $DB;

        $enrolled = $DB->record_exists('local_learningplan_user',array('planid'=>$certificationid,'userid'=>$USER->id));
        if($enrolled){
            $flag=1;
        }else{
            $flag=0;
        }
        return $flag;
    } // end of get_enrollflag
    private function get_enrollbtn($planinfo){
     	global $DB,$USER;
        $planid = $planinfo->id;
        $planname =  $planinfo->name; 
      
     	if(!is_siteadmin()){
     		if($planinfo->selfenrol == 1){
	            if($planinfo->approvalreqd == 1){
	                $componentid =$planid;
	                $component = 'learningplan';
	                // $request = $DB->get_field('local_request_records','status',array('componentid' => $planid,'compname' => $component,'createdbyid'=>$USER->id));
	                $sql = "SELECT status FROM {local_request_records} WHERE componentid=:componentid AND compname LIKE :compname AND createdbyid = :createdbyid ORDER BY id desc ";
	                $request = $DB->get_field_sql($sql,array('componentid' => $planid,'compname' => $component,'createdbyid'=>$USER->id));
					if($request=='PENDING'){
						$enrollmentbtn = '<button class="cat_btn btn-primary catbtn_process viewmore_btn">Processing</button>';
					}else{
						$enrollmentbtn =requestapi::get_requestbutton($componentid, $component, $planname);
					}
	            }else{
	   				$enrollmentbtn = '<a href="javascript:void(0);" class="cat_btn viewmore_btn fakebtn btn-primary" alt = ' . get_string('enroll','local_catalog'). ' title = ' .get_string('enroll','local_catalog'). ' onclick="(function(e){ require(\'local_learningplan/courseenrol\').enrolUser({planid:'.$planid.', userid:'.$USER->id.', planname:\''.$planname.'\' }) })(event)" ><button class="cat_btn btn-primary catbtn_request viewmore_btn">'.get_string('enroll','local_classroom').'</button></a>';
	   			}
	   		}
  		}
  		return $enrollmentbtn;
    } // end of get_enrollbtn
}
