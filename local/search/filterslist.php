<?php
define('AJAX_SCRIPT',true);
require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/local/catalog/lib.php');
require_once($CFG->dirroot . '/local/search/lib.php');

//require_once('../../config.php');
global $CFG,$DB,$USER,$PAGE;
$PAGE->set_context(context_system::instance());
$PAGE->set_url('/local/search/filterslist.php');
 
//new one
/*define('ELE',1);
define('ICOURSE',2);
define('LP',3);
define('ILT',7);
define('MOOC',4);
define('LEARNINGPATH',5);
define('PERPAGE',6);
define('LPCOURSE',8);
define('BLENDED',9);*/

require_login();

$catid = optional_param('catid', 0, PARAM_TEXT);
$action = optional_param('action', '', PARAM_RAW);

if($catid && $action == 'itemslist'){
    $tagitem_data = get_itemlist($catid, 6, 0);
    $tagitems = $tagitem_data['itemslist'];
    echo json_encode($tagitems);
    exit;
}
$finallist = [];
$categoriesall = [];
$final_array['categoriesall'] = [];
// moduletype filters
//$final_array['categoriesall'][] = get_itemlist('moduletype');

$final_array['categoriesall'][] = get_itemlist('learningtype');
$final_array['categoriesall'][] = get_itemlist('categories');
$final_array['categoriesall'][] = get_itemlist('courseprovider');
$final_array['categoriesall'][] = get_itemlist('level'); 
$final_array['categoriesall'][] = get_itemlist('status');
$final_array['categoriesall'][] = get_itemlist('skillcategory');

$final = array();
$final['finallist'] = $final_array;
echo json_encode($final);

function get_itemlist($catid, $start = 0, $limit = 6){
	global $DB;
	switch($catid){
	 	case 'moduletype':
			$itemslist = [];
			$filterplugins = get_plugins_with_function('search_page_filter_element');
			
			foreach($filterplugins AS $filterelements){
				foreach($filterelements AS $filterelement){
					$filterelement($itemslist);
				}
			}
	
			return ['catcode' => 'moduletype', 'tagcatname' => 'Module Type', 'itemslist' => $itemslist, 'showviewmore' => false];
		break; 
		case 'status':
			$itemslist[] = ['tagitemid' => 'status_notenrolled', 'tagitemname' => 'Not Enrolled', 'tagitemshortname' => 'notenrolled_modules', 'coursecount' => local_search_get_coursecount_for_status(['status_notenrolled'])];
			$itemslist[] = ['tagitemid' => 'status_enrolled', 'tagitemname' => 'Enrolled', 'tagitemshortname' => 'enrolled_modules', 'coursecount' => local_search_get_coursecount_for_status(['status_enrolled'])];
			//$itemslist[] = ['tagitemid' => 'status_completed', 'tagitemname' => 'Completed', 'tagitemshortname' => 'completed_modules', 'coursecount' => local_search_get_coursecount_for_status(['status_completed'])];
            return ['catcode' => 'learningstatus', 'tagcatname' => 'Status', 'itemslist' => $itemslist, 'showviewmore' => false];
        break;
		case 'learningtype':
			$itemslist = [];

			$totalfields = 6;
			if($start == 0){

	            $sql = "SELECT id, course_type, shortname FROM {local_course_types} ";//WHERE active = 1 ";
			 	$ctypes = $DB->get_records_sql($sql, [], 0, $totalfields+1);
				
	        	if(count($ctypes) == 7){
					//array_pop($ctypes);
					$showviewmore = true;
				}else{
					$showviewmore = false;
				}

			} else {
				$sql = "SELECT id,  course_type,shortname FROM {local_course_types} ";//WHERE active = 1 ";
			 	$ctypes = $DB->get_records_sql($sql, [], $totalfields, 0);
			}

			            
            foreach($ctypes AS $customtype){
				if($customtype->shortname == 'learningpath' ){
					$itemslist['custom_'.$customtype->shortname] = ['tagitemid' => 'moduletype_learningplan', 'tagitemname' => $customtype->course_type, 'tagitemshortname' => $customtype->shortname, 'coursecount' => local_search_get_coursecount_for_modules(['moduletype_learningplan'])];
				} else if($customtype->shortname == 'ilt' ){
					$itemslist['custom_'.$customtype->shortname] = ['tagitemid' => 'moduletype_classroom', 'tagitemname' => $customtype->course_type, 'tagitemshortname' => $customtype->shortname, 'coursecount' => local_search_get_coursecount_for_modules(['moduletype_classroom'])];
				}else{
        			$itemslist['custom_'.$customtype->shortname] = ['tagitemid' => 'learningtype_'.$customtype->id, 'tagitemname' => $customtype->course_type, 'tagitemshortname' => $customtype->shortname, 'coursecount' => local_search_get_coursecount_for_modules(['learningtype_'.$customtype->id])];
				}
			}
			
			ksort($itemslist);

            return ['catcode' => 'learningtype', 'tagcatname' => 'Learning Type', 'itemslist' => $itemslist, 'showviewmore' => $showviewmore];
		break;

	   	case 'categories':
			if($start == 0){
				$categorySql = "SELECT id, name FROM {course_categories} WHERE visible = 1 ";
				$categories = $DB->get_records_sql_menu($categorySql, [], 0,7);
				if(count($categories) == 7){
					array_pop($categories);
					$showviewmore = true;
				}else{
					$showviewmore = false;
				}
			}else{
				$categorySql = "SELECT id, name FROM {course_categories} WHERE visible = 1 ";
				$categories = $DB->get_records_sql_menu($categorySql, [], 6,0);
			}
			$itemslist = [];
			foreach($categories AS $catid => $catname){
				$coursecount = local_search\output\allcourses::get_available_catalogtypes(['categories_'.$catid])['sumofallrecords'];
				$itemslist[] = ['tagitemid' => 'categories_'.$catid, 'tagitemname' => $catname, 'tagitemshortname' => $catname, 'coursecount' => $coursecount];
				
			}
			return ['catcode' => 'categories', 'tagcatname' => 'Category ', 'itemslist' => $itemslist, 'showviewmore' => $showviewmore];
		break;

		case 'level':
		  list($itemslist, $showviewmore) = local_search_get_itemlist_level($start, $limit);
		  return ['catcode' => 'level', 'tagcatname' => 'Level', 'itemslist' => $itemslist, 'showviewmore' => $showviewmore];
		  break;
        
        case 'courseprovider':
		  list($itemslist, $showviewmore) = local_search_get_itemlist_courseprovider($start, $limit);
		  return ['catcode' => 'courseprovider', 'tagcatname' => 'Course Provider ', 'itemslist' => $itemslist, 'showviewmore' => $showviewmore];
		  break;

	 
		case 'skillcategory':
		  	list($itemslist, $showviewmore) = local_search_get_itemlist_skill($start, $limit);
		  	return ['catcode' => 'skillcategory', 'tagcatname' => 'Skill Category', 'itemslist' => $itemslist, 'showviewmore' => $showviewmore];
	  	break;
     }
}
