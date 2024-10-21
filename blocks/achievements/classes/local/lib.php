<?php
defined('MOODLE_INTERNAL') || die;

    //////For display on index page using service.php way//////////
function point_details($tablelimits, $filtervalues){
  
        global $DB, $CFG, $USER,$PAGE;

        $countsql = "SELECT count(distinct c.id) 
                      FROM {user_enrolments} AS m
                      INNER JOIN {enrol} AS e ON  m.enrolid=e.id
                      INNER JOIN {course} AS c ON e.courseid=c.id
                      INNER JOIN {course_completions} AS cc ON  c.id=cc.course
                      WHERE cc.userid = :cuserid AND m.userid = :muserid
                      AND cc.timecompleted IS NOT NULL 
                      AND ( CONCAT(',',c.open_identifiedas, ',') LIKE CONCAT('%,',3,',%') OR CONCAT(',',c.open_identifiedas, ',') LIKE CONCAT('%,',1,',%') ) ";

        $selectsql = "SELECT distinct(c.id),c.fullname,c.open_points
                      FROM {user_enrolments} AS m
                      INNER JOIN {enrol} AS e ON  m.enrolid=e.id
                      INNER JOIN {course} AS c ON e.courseid=c.id
                      INNER JOIN {course_completions} AS cc ON  c.id=cc.course
                      WHERE cc.userid = :cuserid AND m.userid = :muserid
                      AND cc.timecompleted IS NOT NULL AND (CONCAT(',',c.open_identifiedas, ',') LIKE CONCAT('%,',3,',%') OR CONCAT(',',c.open_identifiedas, ',') LIKE CONCAT('%,',1,',%') ) ";

        $queryparam = array();

        
        $queryparam['cuserid'] = $USER->id;
        $queryparam['muserid'] = $USER->id;
    
        $count = $DB->count_records_sql($countsql,$queryparam);
        
        $pointsrecived = $DB->get_records_sql($selectsql, $queryparam, $tablelimits->start, $tablelimits->length);

        $list=array();
        if ($pointsrecived) {
            $data = array();
            foreach ($pointsrecived as $points) { 
                
                //print_object($skill);
              $list['points_title'] = $points->fullname;
              $list['points_credit']=$points->open_points;

               $data[] = $list;
            }
        }
		
      
        return array('count' => $count, 'data' => $data);

        
}



function badges_details($tablelimits, $filtervalues){
 
      global $DB, $CFG, $USER,$PAGE;
 
       $countsql = "SELECT count(bi.uniquehash) FROM  {badge} b ,{badge_issued} bi,{user} u
             
              WHERE bi.userid = {$USER->id} 
              AND b.id = bi.badgeid 
              AND u.id = bi.userid";

        $selectsql = "SELECT bi.uniquehash,
                bi.dateissued,
                bi.dateexpire,
                bi.id as issuedid,
                bi.visible,
                u.email,
                b.*  
                FROM  {badge} b ,{badge_issued} bi,{user} u
             
              WHERE bi.userid = {$USER->id} 
              AND b.id = bi.badgeid 
              AND u.id = bi.userid ";

        $queryparam = array();
    
        $count = $DB->count_records_sql($countsql);
        //print_object($tablelimits);
        $concatsql.=" ORDER BY bi.dateissued DESC";
        $badgesrecived = $DB->get_records_sql($selectsql.$concatsql, $queryparam, $tablelimits->start, $tablelimits->length);

        $list=array();
        if ($badgesrecived) {
            $data = array();
            foreach ($badgesrecived as $badge) { 
              $context = ($badge->type == 1) ? context_system::instance() : context_course::instance($badge->courseid);
              $badgeurl = moodle_url::make_pluginfile_url($context->id, 'badges', 'badgeimage', $badge->id, '/', 'f1', false);
              $list['imageurl'] = $badgeurl->out();
              $list['badge_name'] = strlen($badge->name) > 14 ? substr($badge->name, 0, 14).'...' : $badge->name;
              $list['badge_name_str'] = $badge->name;
              $list['uniquehash'] = $badge->uniquehash;
              $list['badge_id'] = $badge->id;
              $issued_on=date('d-M-Y', $badge->dateissued);
              $list['issued_on']=$issued_on;
              $list['issued_by']=$badge->issuername;

               $data[] = $list;
            }
        }
        return array('count' => $count, 'data' => $data); 
    
}


function certification_details($tablelimits, $filtervalues){
    global $DB, $CFG, $USER,$PAGE;


/*     $countsql = "SELECT count(distinct certi.id) FROM {user_enrolments} as m
                  JOIN {enrol} as e ON  m.enrolid=e.id
                  JOIN {course} as c ON e.courseid=c.id
                  JOIN {course_completions} as cc ON  c.id=cc.course
                  JOIN {course_modules} as cm ON cm.course=c.id
                  JOIN {certificate} as certi ON certi.id=cm.instance
                  JOIN {certificate_issues} as cissue ON certi.id=cissue.certificateid
                  JOIN {modules} as modu ON modu.id=cm.module
                  WHERE cc.userid=:ccompuserid AND m.userid=:moduuserid
                  AND cc.timecompleted IS NOT NULL AND modu.name='certificate'
                  AND (CONCAT(',',c.open_identifiedas, ',') LIKE CONCAT('%,',3,',%') OR CONCAT(',',c.open_identifiedas, ',') LIKE CONCAT('%,',1,',%')) AND cissue.userid=:ciuserid";

    $selectsql = "SELECT  distinct(certi.id), certi.name,cm.id AS moduleid, certi.course 
                  FROM {user_enrolments} as m
                  JOIN {enrol} as e ON  m.enrolid=e.id
                  JOIN {course} as c ON e.courseid=c.id
                  JOIN {course_completions} as cc ON c.id=cc.course
                  JOIN {course_modules} as cm ON cm.course=c.id
                  JOIN {certificate} as certi ON certi.id=cm.instance
                  JOIN {certificate_issues} as cissue ON certi.id=cissue.certificateid
                  JOIN {modules} as modu ON modu.id=cm.module
                  WHERE cc.userid=:ccompuserid AND m.userid=:moduuserid AND modu.name='certificate' 
                  AND cc.timecompleted IS NOT NULL AND cissue.userid=:ciuserid 
                  AND (concat(',',c.open_identifiedas, ',') LIKE concat('%,',3,',%') OR concat(',',c.open_identifiedas, ',') LIKE concat('%,',1,',%') ) ";

        $queryparam = array();

       
        $queryparam['ccompuserid'] = $USER->id;
        $queryparam['moduuserid'] = $USER->id;
        $queryparam['ciuserid'] = $USER->id;

        $count = $DB->count_records_sql($countsql,$queryparam);

        
        $certirecived = $DB->get_records_sql($selectsql, $queryparam, $tablelimits->start, $tablelimits->length);

         
        $list=array();
        if ($certirecived) {
            $data = array();
            foreach ($certirecived as $certificate) { 
                
              $list['module_id']=$certificate->moduleid;
              
                
              $list['certificate_name']=$certificate->name;
          
               $data[] = $list;
            }
        } */
        $queryparam = array('userid' => $USER->id);
        $countsql = "SELECT count(lci.id) FROM {tool_certificate_issues} AS lci
            JOIN {tool_certificate_templates} AS lc ON lc.id = lci.templateid 
            WHERE lci.userid = :userid ";
        $selectsql = "SELECT lci.id, lc.id as moduleid ,lc.name ,lci.code, lci.moduletype,
        (SELECT 
            CASE 
            WHEN lci.moduletype LIKE 'course'
                THEN (SELECT module.fullname FROM {course} AS module WHERE module.id = lci.moduleid )
            WHEN lci.moduletype LIKE 'classroom'
                THEN (SELECT module.name FROM {local_classroom} AS module WHERE module.id = lci.moduleid) 
            WHEN lci.moduletype LIKE 'learningplan'
                THEN (SELECT module.name FROM {local_learningplan} AS module WHERE module.id = lci.moduleid)
            WHEN lci.moduletype LIKE 'onlinetest'
                THEN (SELECT module.name FROM {local_onlinetests} AS module WHERE module.id = lci.moduleid )
            ELSE '' END) AS modulename 
            FROM {tool_certificate_issues} AS lci
            JOIN {tool_certificate_templates} AS lc ON lc.id = lci.templateid 
            WHERE lci.userid = :userid ";
        $count = $DB->count_records_sql($countsql,$queryparam);
    
        $certirecived = $DB->get_records_sql($selectsql, $queryparam, $tablelimits->start, $tablelimits->length);
        $list=array();
        $data = array();
        if ($certirecived) {
            foreach ($certirecived as $certificate) {
                $list['module_id']=$certificate->moduleid;
                $list['certificate_code']=$certificate->code;
                $list['certificate_name']= "{$certificate->name}(".ucfirst($certificate->moduletype)." - {$certificate->modulename})";
                $data[] = $list;
            }
        }

        return array('count' => $count, 'data' => $data);       
}

  
?>