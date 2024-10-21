<?php
function get_coursetypes($blocktype)
{
    global $DB;
    $systemcontext = context_system::instance();

    $menulinks = array();
    $i = 2;
    $coursetypes = $DB->get_records('local_course_types', array('active' => 1), 'id asc', 'id,course_type,shortname');
    foreach ($coursetypes as $ctype) {

        $returndata = array();
        $returndata['id'] = $ctype->id;
        $returndata['order'] = $i;
        $returndata['coursetype'] = $ctype->course_type;
        $menulinks[$returndata['order'] - 1] = $returndata;
        $i++;
    }

    $returndata['id'] = 0;
    $returndata['order'] = 1;
    $returndata['coursetype'] = 'ALL';
    $menulinks[$returndata['order'] - 1] = $returndata;

    ksort($menulinks);
    $menulinks = array_values($menulinks);
    return $menulinks;
}

function get_learningsummary_data($filtervalues, $data_object, $stable)
{
    global $DB, $USER;
    $systemcontext = context_system::instance();
    $course_type = $DB->get_field('local_course_types', 'shortname', array('id' => $data_object->id, 'active' => 1));

    if ($course_type == 'ilt') {

        $ilt_params = [];
        $iltsql = ilt_info($data_object, $stable);
        $allcoursecount =  $iltsql['allcoursecount'];
        $allcourses =  $iltsql['allcourses'];
  
    } else if ($course_type == 'learningpath') {

        $lp_params = [];
        $lpsql = lp_info($data_object, $stable);
      
        $allcoursecount =  $lpsql['allcoursecount'];
        $allcourses =  $lpsql['allcourses'];
    } else {
        $coursetypes = getcoursetypes($data_object);

        if ($data_object->id == 0) {
            $allcoursesdata = get_allcoursesdata($filtervalues, $data_object, $stable);
            $allcourses = $allcoursesdata['courses'];
            $allcoursecount = $allcoursesdata['coursecount'];
        } else {
            $allcourrsessql = course_info($data_object, $stable);
            $allcoursecount =  $allcourrsessql['allcoursecount'];
            $allcourses =  $allcourrsessql['allcourses'];
       
        }
    }

    try {
        $allcoursecount = $allcoursecount;
    } catch (dml_exception $ex) {
        $allcoursecount = 0;
    }
    return compact('allcourses', 'allcoursecount');
}

function local_learningsummary_leftmenunode()
{
    $summarynode = '';
    $systemcontext = context_system::instance();
    if (!is_siteadmin() && (has_capability('local/learningsummary:manage', $systemcontext))) {

        $summarynode .= html_writer::start_tag('li', array('id' => 'id_leftmenu_learningsummary', 'class' => 'pull-left user_nav_div local_learningsummary'));
        $summary_url = new moodle_url('/local/learningsummary/index.php');
        $extcert = html_writer::link($summary_url, '<span class="learning_summary_icon left_menu_icons"></span><span class="user_navigation_link_text">' . get_string('pluginname', 'local_learningsummary') . '</span>', array('class' => 'user_navigation_link'));
        $summarynode .= $extcert;
        $summarynode .= html_writer::end_tag('li');
    }
    return array('4' => $summarynode);
}

function getcoursetypes($data_object)
{

    global $DB;
    $coursetype_sql = "SELECT id FROM {local_course_types} WHERE shortname NOT IN ( 'ilt', 'learningpath' ) ";
    if ($data_object->id != 0) {
        $coursetype_sql .= " AND id = :typeid ";
    }
    $coursetype = $DB->get_fieldset_sql($coursetype_sql, array('typeid' => $data_object->id));
    $coursetypes = implode(",", $coursetype);
    return $coursetypes;
}

function lp_info($data_object, $stable)
{
    global $USER, $DB;
    $lp_params = [];

    $countsql =  "SELECT COUNT(llp.id) ";
    $lp_selectsql = "SELECT CONCAT('lp_',llp.id)as lpid,llp.id as id,llp.name as fullname,llp.description as summary,lla.userid as userid, 'learningpath' as open_identifiedas, NULL as expirydate , NULL as classroomlogo ";
    $lp_fromsql = " FROM {local_learningplan} llp 
                   JOIN {local_learningplan_user}  lla ON lla.planid = llp.id 
                   WHERE llp.visible = 1 AND lla.userid = $USER->id ";

    if ($data_object->blocktype == 'inprogress') {
        $lp_fromsql .= " AND lla.completiondate IS NULL AND lla.status IS NULL ";
    }
    if ($data_object->blocktype == 'completed') {
        $lp_fromsql .= " AND lla.completiondate is NOT NULL AND lla.status=1  ";
    }
    $lp_params['userid'] = $USER->id;

    if (!empty($data_object->search_query)) {
        $lp_fromsql .= " AND ( llp.name LIKE '%" .  $data_object->search_query . "%')";
    }
    $allcoursecount = $DB->count_records_sql($countsql . $lp_fromsql, $lp_params);
    $allcourses = $DB->get_records_sql($lp_selectsql . $lp_fromsql, $lp_params, $stable->start, $stable->length);
    return array('allcourses' => $allcourses, 'allcoursecount' => $allcoursecount, 'countsql' => $countsql, 'selectsql' => $lp_selectsql, 'fromsql' => $lp_fromsql);
}

function course_info($data_object, $stable)
{

    global $USER, $DB;
    $coursetypes = getcoursetypes($data_object);
    $countsql = "SELECT COUNT(course.id)";
    $selectsql = "SELECT CONCAT('course_',course.id)as cid,course.id as id,course.fullname as fullname,course.summary as summary,ue.userid as userid,course.open_identifiedas as open_identifiedas,expirydate,NULL  as classroomlogo";

    $fromsql = " FROM {course} AS course 
                   JOIN {enrol} AS e ON course.id = e.courseid AND e.enrol IN('self','manual','auto')
                   JOIN {user_enrolments} ue ON e.id = ue.enrolid 
                   WHERE ue.userid = $USER->id AND course.visible = 1 AND course.open_identifiedas IN ( $coursetypes ) ";

    if ($data_object->blocktype === 'inprogress') {
        //$fromsql .= " AND course.id IN (SELECT course FROM {course_completions}  WHERE  course.id = course AND userid = {$USER->id} AND timecompleted IS NULL)  ";
        $fromsql .= " AND course.id NOT IN(SELECT course FROM {course_completions} WHERE course = course.id AND userid = {$USER->id} AND timecompleted IS NOT NULL) ";
    }
    if ($data_object->blocktype == 'completed') {
        $fromsql .= " AND course.id  IN (SELECT course FROM {course_completions} WHERE course = course.id AND userid = {$USER->id} AND timecompleted IS NOT NULL)  ";
    }

    if (!empty($data_object->search_query)) {
        $fromsql .= " AND ( course.fullname LIKE '%" . $data_object->search_query . "%')";
    }
    $allcoursecount = $DB->count_records_sql($countsql . $fromsql, array());
    $allcourses = $DB->get_records_sql($selectsql . $fromsql, array(), $stable->start, $stable->length);
    return array('allcourses' => $allcourses, 'allcoursecount' => $allcoursecount, 'countsql' => $countsql, 'selectsql' => $selectsql, 'fromsql' => $fromsql);
}

function ilt_info($data_object,$stable)
{

    global $USER,$DB;
    $ilt_params = array();
    $countsql =  "SELECT COUNT(lc.id) ";
    $ilt_selectsql = "SELECT CONCAT('ilt',lc.id)as lcid,lc.id as id,lc.name AS fullname,lc.description as summary,lcu.userid as userid, 'ilt' as open_identifiedas, NULL as expirydate, lc.classroomlogo as classroomlogo";
    $ilt_fromsql = " FROM {local_classroom} AS lc 
           JOIN {local_classroom_users} AS lcu ON lc.id=lcu.classroomid
           WHERE lcu.userid={$USER->id} and lc.visible=1 ";
    if ($data_object->blocktype == 'inprogress') {
        $ilt_fromsql .= " AND lc.status=:status ";
        $ilt_params['status'] = 1;
    }
    if ($data_object->blocktype == 'completed') {
        $ilt_fromsql .= " AND lc.status=:status ";
        $ilt_params['status'] = 4;
    }
    if (!empty($data_object->search_query)) {
        $ilt_fromsql .= " AND ( lc.name LIKE '%" . $data_object->search_query . "%' ) ";
    }

    $allcoursecount = $DB->count_records_sql($countsql . $ilt_fromsql, $ilt_params);
    $allcourses = $DB->get_records_sql($ilt_selectsql . $ilt_fromsql,  $ilt_params, $stable->start, $stable->length);
    return array('allcourses' => $allcourses, 'allcoursecount' => $allcoursecount, 'countsql' => $countsql, 'selectsql' => $ilt_selectsql, 'fromsql' => $ilt_fromsql);
}


function get_allcoursesdata($filtervalues, $data_object, $stable)
{
    global $DB, $USER;

    $countsql = "SELECT COUNT(*)";

    $unionsql = " UNION ";

    $coursessql = course_info($data_object, $stable);
    $selectsql =  $coursessql['selectsql'];
    $fromsql =  $coursessql['fromsql'];
    $courseparams['userid'] = $USER->id;
    $sql = $selectsql . $fromsql;

    $lpathsql = lp_info($data_object, $stable);
    $lp_selectsql =  $lpathsql['selectsql'];
    $lp_fromsql =  $lpathsql['fromsql'];
    $lpsql = $lp_selectsql . $lp_fromsql;

    $classroomsql = ilt_info($data_object, $stable);
    $ilt_selectsql =  $classroomsql['selectsql'];
    $ilt_fromsql =  $classroomsql['fromsql'];
    if ($data_object->blocktype == 'inprogress') {
        $ilt_params['status'] = 1;
    }
    if ($data_object->blocktype == 'completed') {
        $ilt_params['status'] = 4;
    }
    $iltsql = $ilt_selectsql . $ilt_fromsql;
    $final_countsql = $sql . $unionsql . $lpsql . $unionsql . $iltsql;

    $countsql = $countsql . ' FROM (' . $final_countsql . ') AS tbl ';
    $allcoursecount = $DB->count_records_sql($countsql, $ilt_params);

    $allcourses = $DB->get_records_sql($sql . $unionsql . $lpsql . $unionsql . $iltsql, $ilt_params, $stable->start, $stable->length);
    return array('coursecount' => $allcoursecount, 'courses' => $allcourses);
}
