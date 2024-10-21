<?php
define('AJAX_SCRIPT',true);
require_once(dirname(__FILE__) . '/../../config.php');
use local_classroom\classroom as classroom;
use local_catalog\output\cataloglib;

//require_once('../../config.php');
global $CFG,$DB,$USER,$PAGE;
$PAGE->set_context(context_system::instance());
$PAGE->set_url('/local/catalog/courseajax.php');

require_login();

$tab = optional_param('tab',0,PARAM_INT);
$page= optional_param('page',0, PARAM_INT);
$search= optional_param('search','', PARAM_RAW);
$category = optional_param('category',0,PARAM_INT);
$enrolltype = optional_param('enrolltype',0,PARAM_INT);
$sortid = optional_param('sortid',0, PARAM_RAW);


define('ELE',1);
define('ILT',2);
define('LP',3);
define('PROGRAM',6);
define('CERTIFICATION',4);
define('LEARNINGPATH',5);
define('PERPAGE',8);



if($page>=1)
$page = $page-1;
if(file_exists($CFG->dirroot . '/local/includes.php')){
    require_once($CFG->dirroot . '/local/includes.php');
    $includes = new user_course_details();
}
//$tabcontent = new managecourse_available_list($includes, $page, $search, $category, $enrolltype);
/*cataloglib::$data = array('page'=>$page,
	                       'includes'=>$includes,
	                       'search'=>$search,
	                       'category'=>$category,
	                       'enrolltype'=>$enrolltype

	                   ); */
cataloglib::$page = $page;
cataloglib::$includesobj = $includes;
cataloglib::$search = $search;
cataloglib::$category = $category;
cataloglib::$enrolltype = $enrolltype;
cataloglib::$sortid = $sortid;


$startlimit= $page*PERPAGE;

switch($tab ){    

    case 1: $pages = new \local_catalog\output\elearning();
            echo json_encode($pages->export_for_template(PERPAGE,$startlimit, 'elearning'));
            break;

    case 2: //$pages = new \local_catalog\output\classroom();
            $pages = new \local_catalog\output\iltcourses();
            echo json_encode($pages->export_for_template(PERPAGE,$startlimit));
            break;

    case 3: $pages = new \local_catalog\output\program();
            echo json_encode($pages->export_for_template(PERPAGE,$startlimit));
            break;

    case 4: $pages = new \local_catalog\output\certification();
            echo json_encode($pages->export_for_template(PERPAGE,$startlimit));
            break;
            
    case 5: $pages = new \local_catalog\output\learningplan();
            echo json_encode($pages->export_for_template(PERPAGE,$startlimit));
            break;

    case 6: $pages = new \local_catalog\output\allcourses();
            echo json_encode($pages->main_toget_catalogtypes(PERPAGE));    	    
            break;

    case 7: $categorylist = block_manage_get_elearning_courses_dropdown1();  
            echo json_encode($categorylist);
            break;

    case 8: $pages = new \local_catalog\output\elearning();
            echo json_encode($pages->export_for_template(PERPAGE,$startlimit, 'mooc'));
            break; 	    
	        
} // end of switch statement 


function block_manage_get_elearning_courses_dropdown1(){
    global $DB, $CFG, $USER;   
    $categorylist = array();
    
    $sql="SELECT cc.id,cc.name 
            FROM {course_categories} cc
            WHERE cc.visible = 1
            ORDER BY cc.sortorder ASC";

 //    if (is_siteadmin()) {
 //     $sql="select cc.id,cc.name from {course_categories} cc JOIN {local_costcenter} lc on lc.category=cc.id order by name ASC";
 //    }
	// else{
 //     $id=$DB->get_field('local_costcenter','category',array('id'=>$USER->open_costcenterid));
 //     $sql="select id,name from {course_categories} where (path like '/$id/%' or path like '%/$id' or path like '%/$id/%') AND visible=1";
 //    }
 //    $categorylist = $DB->get_records_sql($sql);

    $categorylist = $DB->get_records_sql($sql);


    return $categorylist;
    
}  // end of block_manage_get_elearning_courses_dropdown1


 