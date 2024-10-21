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
use local_certification\certification as clroom;
use local_certification\certification as crtcatn;
use user_course_details;
use local_request\api\requestapi;

/**
 * Class containing data for course competencies page
 *
 * @copyright  2018 hemalatha c arun <hemalatha@eabyas.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class certification implements renderable{
	public function get_certificationlist_query($perpage,$startlimit,$return_noofrecords=false, $returnobjectlist=false){
		global $DB, $USER, $CFG;
		$search = cataloglib::$search;
		$sortid = cataloglib::$sortid;
		//------main queries written here to fetch Certification or  session based on condition
	    $csql="SELECT  lc.*,lc.startdate as trainingstartdate, lc.enddate as trainingenddate ";
          $cfromsql = "from {local_certification} lc  "; 
          $usql = " UNION ";
          $tsql="SELECT  lc.*,lc.startdate as trainingstartdate, lc.enddate as trainingenddate ";
          $tfromsql = " from {local_certification} lc";
          $tjoinsql = " JOIN {tag_instance} tgi ON tgi.itemid = lc.id AND tgi.itemtype = 'certification' AND tgi.component = 'local_certification' JOIN {tag} t ON t.id = tgi.tagid ";

          $leftjoinsql = $groupby = $orderby = $avgsql = '';
        if (!empty($sortid)) {
          switch($sortid) {
            case 'highrate':
            if ($DB->get_manager()->table_exists('local_rating')) {
                $avgsql .= " , AVG(r.rating) as rates ";
                $leftjoinsql .= " LEFT JOIN {local_rating} as r ON r.itemid = lc.id AND r.ratearea = 'local_certification' ";
                $groupby .= " group by lc.id ";
                $orderby .= " order by rates desc ";
            }
            break;
            case 'lowrate':  
            if ($DB->get_manager()->table_exists('local_rating')) {  
                $avgsql .= " , AVG(r.rating) as rates  ";
                $leftjoinsql .= " LEFT JOIN {local_rating} as r ON r.itemid = lc.id AND r.ratearea = 'local_certification' ";
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

        $wheresql = " where lc.visible=1 ";
			//------if not site admin sessions list will be filter by location or bands
	    if(is_siteadmin()){
			if(cataloglib::$search && cataloglib::$search!='null'){
				 $cwrsql = " AND lc.name LIKE '%$search%'";
                 $twrsql = " AND t.name LIKE '%$search%'";
			}
		}
		 
		$usercontext = context_user::instance($USER->id);
		if(!is_siteadmin()){
				//-----filter by costcenter
				if($USER->open_costcenterid){
					$wheresql .= " AND (( lc.costcenter !=0 AND $USER->open_costcenterid in (lc.costcenter)))";      
				} ;
		
					 //OL-1042 Add Target Audience to Certification//
                $params = array();
                
                $group_list = $DB->get_records_sql_menu("select cm.id,cm.cohortid as groupid from {cohort_members} cm where cm.userid IN ({$USER->id})");
                 
                
                 //added by sarath for jira issu 1761
                if (!empty($group_list)){
                     $groups_members = implode(',', $group_list);
                     if(!empty($group_list)){
                        $grouquery = array();
                        foreach ($group_list as $key => $group) {
                            $grouquery[] = " CONCAT(',',lc.open_group,',') LIKE CONCAT('%,',$group,',%') "; //FIND_IN_SET($group,lc.open_group)
                        }
                        $groupqueeryparams =implode('OR',$grouquery);
                        $params[]= '('.$groupqueeryparams.')';
                     }
                }

                if(!empty($params))
		        	$opengroup=implode('AND',$params);
                else
                	$opengroup = '1 != 1';
                $params = array();
                // if($opengroup){
					$params[]= " 1 = CASE WHEN (lc.open_group!='-1' AND lc.open_group <> '')
                        THEN
                        	CASE WHEN $opengroup
                        		THEN 1
                        		ELSE 0 END 
                        ELSE 1 END ";
                // }
               // if(!empty($USER->open_departmentid)){
                if(!empty($USER->open_departmentid) && $USER->open_departmentid != ""){
                	$departmentlike = "'%,$USER->open_departmentid,%'";
                }else{
					$departmentlike = "''";
                }
		        $params[]= " 1 = CASE WHEN lc.department!='-1'
					THEN 
						CASE WHEN CONCAT(',',lc.department,',') LIKE {$departmentlike}
						THEN 1
						ELSE 0 END
					ELSE 1 END ";//FIND_IN_SET($USER->open_departmentid,lc.department)
			    // }
				if(!empty($USER->open_subdepartment) && $USER->open_subdepartment != ""){
                	$subdepartmentlike = "'%,$USER->open_subdepartment,%'";
                }else{
					$subdepartmentlike = "''";
                }
		        $params[]= " 1 = CASE WHEN lc.subdepartment!='-1'
					THEN 
						CASE WHEN CONCAT(',',lc.subdepartment,',') LIKE {$subdepartmentlike}
						THEN 1
						ELSE 0 END
					ELSE 1 END ";//FIND_IN_SET($USER->open_departmentid,lc.department)
			    // }
			    // if(!empty($USER->open_hrmsrole)){
		    	if(!empty($USER->open_hrmsrole) && $USER->open_hrmsrole != ""){
			    	$hrmsrolelike = "'%,$USER->open_hrmsrole,%'";
				}else{
					$hrmsrolelike = "''";
				}
	          	$params[]= " 1 = CASE WHEN lc.open_hrmsrole IS NOT NULL
					THEN 
						CASE WHEN CONCAT(',',lc.open_hrmsrole,',') LIKE {$hrmsrolelike}
						THEN 1
						ELSE 0 END
					ELSE 1 END ";//FIND_IN_SET('$USER->open_hrmsrole',lc.open_hrmsrole)
			    // }
			    // if(!empty($USER->open_designation)){
				if(!empty($USER->open_designation) && $USER->open_designation != ""){
			    	$designationlike = "'%,$USER->open_designation,%'";
				}else{
					$designationlike = "''";
				}
	          	$params[]= " 1 = CASE WHEN lc.open_designation IS NOT NULL
						THEN 
							CASE WHEN CONCAT(',',lc.open_designation,',') LIKE {$designationlike}
								THEN 1
								ELSE 0 END
						ELSE 1 END  ";//FIND_IN_SET('$USER->open_designation',lc.open_designation) 
			   // }
               // if(!empty($USER->city)){
				if(!empty($USER->open_location) && $USER->open_location != ""){
					$citylike = "'%,$USER->open_location,%'";
				}else{
					$citylike = "''";
				}
		        $params[]= " 1 = CASE WHEN lc.open_location IS NOT NULL
					THEN 
						CASE WHEN CONCAT(',',lc.open_location,',') LIKE {$citylike}
							THEN 1
							ELSE 0 END
					ELSE 1 END  ";//FIND_IN_SET('$USER->city',lc.open_location)
			    // }
			    
			    if(!empty($params)){
			    	$finalcarams=implode('AND',$params);
			    }else{
			    	$finalcarams= '1=1' ;
			    }
			   
             //OL-1042 Add Target Audience to Certification//
                 
			    if(cataloglib::$search && cataloglib::$search!='null'){
			        $cwrsql = " AND lc.name LIKE '%$search%'";
                    $twrsql = " AND t.name LIKE '%$search%'";
		        }
		        $category=cataloglib::$category;
				if(cataloglib::$category && cataloglib::$category>0){
					
				}
				
				$joinsql = " AND ($finalcarams OR (lc.open_hrmsrole IS NULL AND lc.open_designation IS NULL AND lc.open_location IS NULL AND lc.open_group IS NULL AND lc.department='-1' ) )";
				
				if(cataloglib::$enrolltype && cataloglib::$enrolltype>0 ){					
					if(cataloglib::$enrolltype==1){
						if(cataloglib::$category && cataloglib::$category>0){
						
						}	
						$wheresql .= " AND lc.id in (select distinct certificationid from {local_certification_users} where userid=$USER->id) AND lc.status in (1,3,4) "; 			
					}else{
						$finalsql .= " AND lc.id not in (select distinct certificationid from {local_certification_users} where userid=$USER->id) AND lc.status in (1)";
                        $wheresql .= $joinsql;
					}		
			    }else{
                    //$enrolled_certifications=$DB->get_field_sql("select GROUP_CONCAT(certificationid) from {local_certification_users} where userid=$USER->id");
                    //
                    //$yetto_enrolled_certifications=$DB->get_field_sql("select GROUP_CONCAT(lc.id) from {local_certification} AS lc  where  lc.id not in (select distinct certificationid from {local_certification_users} where userid=$USER->id) and lc.status in (1) $joinsql");
               
                   $wheresql .= " AND (lc.id in (select certificationid from {local_certification_users} where userid=$USER->id) or lc.id in (select lc.id from {local_certification} AS lc  where  lc.id not in (select distinct certificationid from {local_certification_users} where userid=$USER->id) and lc.status in (1) $joinsql)) AND lc.status in (1,3,4)";
                }
				//$finalsql .= " AND lc.id not in (select distinct certificationid from {local_certification_trainers} where trainerid=$USER->id)";
		}       
        // $orderby = ' ORDER BY lc.id DESC'; //group by lc.id

	    $finalsql = "select a.* from ( ".$csql.$avgsql.$cfromsql.$leftjoinsql.$wheresql.$cwrsql.$groupby.$usql.$tsql.$avgsql.$tfromsql.$tjoinsql.$leftjoinsql.$wheresql.$twrsql.$groupby." ) as a ";
		$numofcertifications=$DB->get_records_sql($finalsql);
		$numberofrecords = sizeof($numofcertifications);
		if (empty($sortid)) {
            $finalsql .= " order by a.id desc ";
        } else {
            $finalsql .= $orderby;
        }
        $certificationlist=$DB->get_records_sql($finalsql, array(), $startlimit,$perpage);

		if($return_noofrecords && !$returnobjectlist){
			return  array('numberofrecords'=>$numberofrecords);
		}			
	    else if($returnobjectlist && !$return_noofrecords){
			return  array('list'=>$certificationlist);
		}
		else{
			if($return_noofrecords && $returnobjectlist){
				return  array('numberofrecords'=>$numberofrecords,'list'=>$certificationlist);					
			}		
		}		
		
	}//end of get_certificationlist_query.
	public function export_for_template($perpage,$startlimit){
		global $DB, $USER,$CFG, $PAGE;
		$certificationlist_ar =$this->get_certificationlist_query($perpage, $startlimit, true, true);
		$certificationlist= $certificationlist_ar['list'];
	    $localtags = new \local_tags\tags();
		foreach($certificationlist as $list){
			
		   $iltlocation=$DB->get_field('local_location_institutes','fullname',array('id'=>$list->instituteid));
		  if($iltlocation){
			$list->iltlocation=$iltlocation;
		   }
		   
		   $course=$DB->get_record('course', array('id'=>$list->course));
			
			$name="categoryname";
			
			// $coursefileurl = (new crtcatn)->certification_logo($list->certificationlogo);
			if(file_exists($CFG->dirroot.'/local/includes.php')){
				require_once($CFG->dirroot.'/local/includes.php');
	        	$includes = new user_course_details();
	    	}
	        if ($list->certificationlogo > 0){
	            $coursefileurl = (new crtcatn)->certification_logo($list->certificationlogo);
	            if($coursefileurl == false){
	                $coursefileurl = $includes->get_classes_summary_files($list); 
	            }
	        } else {
	            $coursefileurl = $includes->get_classes_summary_files($list);
	        }

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
				
			$list->type = CERTIFICATION;
			$list->enroll = $this->get_enrollflag($list->id);

		    $userenrolstatus = $DB->record_exists('local_certification_users', array('certificationid' => $list->id, 'userid' => $USER->id));
		    $list->userenrolstatus = $userenrolstatus;
		    $return=false;
		    // print_object($list);
			if($list->id > 0 && ($list->nomination_startdate!=0 || $list->nomination_enddate!=0)){
	            $params1 = array();
	            $params1['certificationid'] = $list->id;
	            // $params1['nomination_startdate'] = date('Y-m-d H:i',time());
	            // $params1['nomination_enddate'] = date('Y-m-d H:i',time());
	            $params1['nomination_startdate'] = time();
	            $params1['nomination_enddate'] = time();
	            $sql1="SELECT * FROM {local_certification} where id=:certificationid and 
            		CASE WHEN nomination_startdate > 0
                        THEN CASE WHEN nomination_startdate <= :nomination_startdate
                        	THEN 1
                        	ELSE 0 END
                        ELSE 1 END = 1 and 
                    CASE WHEN nomination_enddate > 0
                    	THEN CASE WHEN nomination_enddate >= :nomination_enddate
                    		THEN 1
                    		ELSE 0 END
                    ELSE 1 END = 1 ";
	           // echo $sql1;
	           // print_object($params1);
	            $return=$DB->record_exists_sql($sql1,$params1); 

	        }elseif($list->id > 0 && $list->nomination_startdate==0 && $list->nomination_enddate==0){
	        	$return=true;
	        }
			$list->selfenroll=1;
            if ($list->status == 1 && !$userenrolstatus && $return) {
				$list->selfenroll=0;
			}
			  $certification_capacity_check=(new crtcatn)->certification_capacity_check($list->id);
			  if($certification_capacity_check&&$list->status == 1 && !$userenrolstatus){
				  $list->selfenroll=2;
			  }
			$list->enrollmentbtn = $this->get_enrollbtn($list);

			if(class_exists('local_ratings\output\renderer')){
				            $rating_render = $PAGE->get_renderer('local_ratings');
				            $list->rating_element = $rating_render->render_ratings_data('local_courses', $list->id ,null, 14);
			}else{
				$list->rating_element = '';
		    } 

			$list->redirect='<a data-action="certification'.$list->id.'" class="certificationinfo" onclick ="(function(e){ require(\'local_catalog/courseinfo\').certificationinfo({selector:\'certification'.$list->id.'\', certificationid:'.$list->id.'}) })(event)"><button class="cat_btn viewmore_btn">'.get_string('viewmore','local_catalog').'</button></a>';
			
			$list->certificationlink= $CFG->wwwroot.'/local/certification/view.php?ctid='.$list->id;
			$context = context_system::instance();
            $tags = $localtags->get_item_tags('local_certification', 'certification', $list->id, $context->id, $arrayflag = 0, $more = 0);
            $course->tags_title = $tags;
            $tags = strlen($tags) > 25 ? substr($tags, 0, 25)."..." : $tags;
            $list->tags = (!empty($tags) ) ? '<span title="Tags"><i class="fa fa-tags" aria-hidden="true"></i></span> '.$tags: '';
		    $finallist[]= $list;	
		} // end of foreach		
		
		$finallist['numberofrecords']=$certificationlist_ar['numberofrecords'];
	 
		return $finallist;
	}//end of get_certificationlist.

	private function get_enrollflag($certificationid){
        global $USER, $DB;

        $enrolled =$DB->record_exists('local_certification_users',array('certificationid'=>$certificationid,'userid'=>$USER->id));
        if($enrolled){
            $flag=1;
        }else{
            $flag=0;
        }
        return $flag;
    } // end of get_enrollflag
	 private function get_enrollbtn($certificationinfo){
	 	global $DB,$USER;
        $certificationid = $certificationinfo->id;
        $certificationname =  $certificationinfo->name; 

    	if(!is_siteadmin()){
    		if($certificationinfo->approvalreqd==1){
    			$componentid =$certificationid;
                $component = 'certification';
    		// $request = $DB->get_field('local_request_records','status',array('componentid' => $certificationid,'compname' => $component,'createdbyid'=>$USER->id));
            $sql = "SELECT status FROM {local_request_records} WHERE componentid=:componentid AND compname LIKE :compname AND createdbyid = :createdbyid ORDER BY id desc ";
    		$request = $DB->get_field_sql($sql, array('componentid' => $certificationid,'compname' => $component,'createdbyid'=>$USER->id));
                if($request=='PENDING'){
                $enrollmentbtn = '<button class="cat_btn btn-primary viewmore_btn">Processing</button>';
            	}else{
             	$enrollmentbtn =requestapi::get_requestbutton($componentid, $component, $certificationname);
            	}
            }
            else{
			$enrollmentbtn = '<a href="javascript:void(0);" class="viewmore_btn" alt = ' . get_string('enroll','local_certification'). ' title = ' .get_string('enroll','local_certification'). ' onclick="(function(e){ require(\'local_certification/certification\').ManagecertificationStatus({action:\'selfenrol\', id: '.$certificationid.', certificationid:'.$certificationid.',actionstatusmsg:\'certification_self_enrolment\',certificationname:\''.$certificationname.'\'}) })(event)" ><button class="cat_btn viewmore_btn">'.get_string('enroll','local_certification').'</button></a>';
			}
		}
		return $enrollmentbtn;
   } // end of get_enrollbtn
}