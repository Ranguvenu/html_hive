<?php

/**
 * script for downloading courses
 */
require_once(dirname(__FILE__) . '/../../../config.php');
global $DB, $CFG, $OUTPUT;
// require_once($CFG->libdir . '/adminlib.php');
$format = optional_param('format', 'csv', PARAM_ALPHA);
$classroomid = required_param('id', PARAM_INT);
$PAGE->set_title('Download');
$systemcontext = context_system::instance();
$PAGE->set_context($systemcontext);
require_login();
$PAGE->set_heading('Download');
$PAGE->set_url('/local/classrooms/export/csvfiledata.php', array('id'=>$classroomid));
    if ($format) {
      $fields = array(
                  'Emp id' => 'Emp id',
                  'Emp name' => 'Emp name',
                  'Emp Location ' => 'Emp Location ',
                  'E-mail id ' => 'E-mail id ',
                  'Designation ' => 'Designation ',
                  'Course name ' => 'Course name ',
                  // 'Facilitators ' => 'Facilitators ',
                  'ILT Name' => 'ILT Name',
                  'ILT Start date and time' => 'ILT Start date and time',
                  'ILT End date and time' => 'ILT End date and time',
                  'Trainers ' => 'Trainers'
                );
      switch ($format) {
        case 'csv' : user_download_csv($fields, $classroomid);
          break;
      }
    }


function user_download_csv($fields, $classroomid) {

    global $CFG, $DB;

    $classroom = $DB->get_record('local_classroom',array('id'=>$classroomid));
    require_once($CFG->libdir . '/csvlib.class.php');
    $filename = clean_filename(''.$classroom->name.'');
    $csvexport = new csv_export_writer();
    $csvexport->set_filename($filename);
    $csvexport->add_data($fields);
    
    if($classroomid){
      $sql = "SELECT lcu.id,u.firstname, u.lastname, u.email, u.open_employeeid, u.open_location, 
              u.open_designation 
              FROM {local_classroom_users} as lcu
              JOIN {user} as u ON u.id = lcu.userid AND lcu.classroomid = $classroom->id
              WHERE u.deleted = 0 ";

      $classroomusers = $DB->get_records_sql($sql);

      $sql = "SELECT lcc.id, lcc.courseid, c.fullname
              FROM {local_classroom_courses} lcc
              JOIN {course} c ON c.id = lcc.courseid AND lcc.classroomid = $classroom->id 
              WHERE lcc.courseid > 1 ";

      $assigned_course = $DB->get_record_sql($sql);

      if($assigned_course){
        $coursename = $assigned_course->fullname;
      }else{
        $coursename = 'NA';
      }

      $sql = "SELECT lct.id, CONCAT(u.firstname,' ', u.lastname) as trainername
              FROM {local_classroom_trainers} as lct 
              JOIN {user} as u ON u.id = lct.trainerid AND lct.classroomid = $classroom->id
              WHERE u.deleted = 0";
      $classrromtrainers = $DB->get_records_sql_menu($sql);


      if($classrromtrainers){
        $trainers = implode(',', $classrromtrainers);
      }else{
        $trainers = '--';
      }

      // if($assigned_course){
      //   $sql = "SELECT cf.id, CONCAT(u.firstname, u.lastname) as facilitators
      //         FROM {local_course_facilitators} cf
      //         JOIN {user} u ON u.id = cf.userid AND cf.courseid = $assigned_course->courseid
      //         WHERE  u.deleted = 0 AND cf.userid > 0 ";

      //   $facilitators = $DB->get_records_sql_menu($sql);
      // }
      
      // if($facilitators){
      //   $course_facilitators = implode(',', $facilitators);
      // }else{
      //   $course_facilitators = '--';
      // }

      if($classroomusers){
        foreach($classroomusers as $classroomuser){
          $csvrow = array();
          $csvrow[] = $classroomuser->open_employeeid;
          $csvrow[] = $classroomuser->firstname.' '.$classroomuser->lastname;
          $csvrow[] = ($classroomuser->open_location) ? $classroomuser->open_location : 'NA';
          $csvrow[] = $classroomuser->email;
          $csvrow[] = ($classroomuser->open_designation) ? $classroomuser->open_designation : 'NA';
          $csvrow[] = $coursename;
          // $csvrow[] = $course_facilitators;
          $csvrow[] = $classroom->name;
          $csvrow[] = date('d M Y h:i a',$classroom->startdate);
          $csvrow[] = date('d M Y h:i a',$classroom->enddate);
          $csvrow[] = $trainers;
          $csvexport->add_data($csvrow);
        }
      }
      $csvexport->download_file();
    }
  }
