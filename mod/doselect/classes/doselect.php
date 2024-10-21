<?php

class doselect {
  

  /**
    * Get list of all the Doselect assessments count
  */
  public function doselect_totalassessmentscount() {
    global $DB;

    $curl = curl_init();

    $apikey = $DB->get_field('config_plugins','value', array('plugin' =>'doselect', 'name'=>'api_key'));
    $api_secret = $DB->get_field('config_plugins','value', array('plugin' =>'doselect', 'name'=>'api_secret'));

    curl_setopt_array($curl, array(
      CURLOPT_URL => "https://api.doselect.com/platform/v1/test?offset=0&limit=5&archived=false&in_learn_feed=true",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "GET",
      CURLOPT_HTTPHEADER => array(
        "doselect-api-key: $apikey",
        "doselect-api-secret: $api_secret"
      ),
    ));
    $response = curl_exec($curl);
    $err = curl_error($curl);

    curl_close($curl);
    $responsedata = json_decode($response);    
    $testscount = $responsedata->meta->total_count;

    return $testscount;
  }


  /**
    * Get list of all the Doselect assessments
  */
  public function doselect_assessmentslist($testlist = false) {
    global $DB;

    $totaltestscount = $this->doselect_totalassessmentscount();
    $curl = curl_init();
        //  CURLOPT_URL => "https://api.doselect.com/platform/v1/test?offset=0&limit=$totaltestscount&archived=false&in_learn_feed=true",


    $apikey = $DB->get_field('config_plugins','value', array('plugin' =>'doselect', 'name'=>'api_key'));
    $api_secret = $DB->get_field('config_plugins','value', array('plugin' =>'doselect', 'name'=>'api_secret'));

    curl_setopt_array($curl, array(
      CURLOPT_URL => "https://api.doselect.com/platform/v1/test?offset=0&archived=false&in_learn_feed=true",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "GET",
      CURLOPT_HTTPHEADER => array(
        "doselect-api-key: $apikey",
        "doselect-api-secret: $api_secret"
      ),
    ));
    $response = curl_exec($curl);
    $err = curl_error($curl);

    curl_close($curl);
    $assessmentslist = array();
    if($response){
      $responsedata = json_decode($response);
      // print_object($responsedata);
      $assessmentslist = array();
      if(!empty($responsedata->objects)){
        if($testlist){
          $cm = optional_param('update',null, PARAM_INT);
          // for get all added tests
          $addedtests = $DB->get_records_select_menu('doselect',array(),null,'','doselect_slug,course');
          $slugs = array_keys($addedtests);
          if($cm){
            $sql = "SELECT d.id,d.doselect_slug 
                    FROM {doselect} d 
                    JOIN {course_modules} cm ON cm.instance = d.id 
                    JOIN {modules} m ON m.id = cm.module 
                    WHERE m.name = 'doselect' AND cm.id = $cm";

            $currenttest = $DB->get_record_sql($sql);
            foreach($responsedata->objects as $assessment){
              if(!in_array($assessment->slug, $slugs) || ($currenttest->doselect_slug == $assessment->slug)){
                $assessmentslist[$assessment->slug] = $assessment->name;
              }
            }
          }else{
            foreach($responsedata->objects as $assessment){
              if(!in_array($assessment->slug, $slugs)){
                $assessmentslist[$assessment->slug] = $assessment->name;
              }
            }
          }                      
        }else{
          foreach($responsedata->objects as $assessment){
            $test = new stdclass();
            $test->slug = $assessment->slug;
            $test->name = $assessment->name;
            $test->duration = $assessment->duration;
            $test->total_test_score = $assessment->total_test_score;
            $test->cutoff = $assessment->cutoff;
            $assessmentslist[$assessment->slug] = $test;
          }
        }
      }
    }
    return $assessmentslist;
  }
  
    
  /*
   * insert a user report
  */
  public function doselect_userreport($testslug,$userid) {
    global $DB, $USER,$PAGE;

    $apikey = $DB->get_field('config_plugins','value', array('plugin' =>'doselect', 'name'=>'api_key'));
    $api_secret = $DB->get_field('config_plugins','value', array('plugin' =>'doselect', 'name'=>'api_secret'));
    $doselectid = $DB->get_field('doselect','id', array('doselect_slug' =>"$testslug"));

    $curl = curl_init();
    $useremail=$DB->get_field('user','email',array('id'=>$userid));
    $useremail = strtolower($useremail);
    curl_setopt_array($curl, array(
      CURLOPT_URL => "https://api.doselect.com/platform/v1/test/$testslug/candidates/$useremail/report",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "GET",
      CURLOPT_HTTPHEADER => array(
        "doselect-api-key: $apikey",
        "doselect-api-secret: $api_secret"
      ),
    ));
    $response = curl_exec($curl);
    $err = curl_error($curl);
          $responsedata1 = json_decode($response);
echo  "https://api.doselect.com/platform/v1/test/$testslug/candidates/$useremail/report";
echo "Key".$apikey;
echo "Secret".$api_secret;

print_object($response);
echo "Gpages";

    curl_close($curl);
    if($response){
      $responsedata = json_decode($response);
      $attemptdata = new stdclass();
      $attemptdata->doselectid = $doselectid;
      $attemptdata->doselect_slug = $testslug;
      $attemptdata->userid = $userid;
      $attemptdata->timestart = $responsedata->started_at;
      $attemptdata->timeend = $responsedata->ended_at;
      $attemptdata->time_taken = $responsedata->time_taken;
      $attemptdata->attempt = $responsedata->attempted;
      $attemptdata->max_score = $responsedata->max_score;
      $attemptdata->total_score = $responsedata->total_score;

      $attemptdata->timecreated = time();
	  
	  print_object($attemptdata);

      $data = $DB->insert_record('doselect_attempts', $attemptdata);

      $doselect= "Select de.id,de.name,doa.doselectid,doa.doselect_slug,doa.userid,
                         doa.timestart,doa.timeend,doa.time_taken,doa.attempt,doa.max_score,
                         doa.timecreated,doa.timemodified,de.course,doa.userid,max(doa.total_score) as total_score
                           from {doselect} de 
                             join {doselect_attempts} doa on doa.doselectid=de.id 
                             where de.id={$doselectid} AND doa.userid={$userid} LIMIT 1";
      $data=$DB->get_record_sql($doselect);
      $this->doselect_grades($data, $userid);

      return $data;
    }else{
      $attemptdata = new stdclass();
      $attemptdata->contextid = $doselectid;
      $attemptdata->contextlevel = 70;
      $attemptdata->contextinstanceid = $doselectid;
      $attemptdata->userid = $userid;
      $attemptdata->relateduserid = $userid;
      $attemptdata->courseid = 0;
      $attemptdata->anonymous = 0;
      $attemptdata->eventname = '\core\event\doselect';
      $attemptdata->objecttable = 'doselect';
      $attemptdata->timecreated =time();
      $attemptdata->origin ='web';
      $attemptdata->action = 'created';
      $attemptdata->target = 'doselect_log';
      $attemptdata->component = 'doselect';
      $attemptdata->crud = 'c';
      $attemptdata->userid = $userid;
      $attemptdata->ip = $PAGE->requestip;
      $attemptdata->realuserid = 1;
      $attemptdata->edulevel = 0;
      $attemptdata->other = $response;
	  
	  print_object($attemptdata);
      $DB->insert_record('logstore_standard_log', $attemptdata);
    }
  }
  /*Function to submit the grades */
 public function doselect_grades($data, $userid=0){
      global $DB, $USER,$CFG;
     require_once($CFG->dirroot.'/mod/doselect/lib.php');
     require_once($CFG->libdir . '/gradelib.php');
      $usergrade=$data->total_score;
     
      if ($usergrade <= 0) {
    
        doselect_grade_item_update($data);

      } else if ($grades = $this->doselect_get_user_grades($data, $userid)) {
          doselect_grade_item_update($data, $grades);
       } else {
          doselect_grade_item_update($data);
       }
      
    
    }

  public function doselect_get_user_grades($doselect, $userid = 0) {
    global $CFG, $DB;

    $sql = "SELECT u.id AS userid, MAX(de.total_score) AS rawgrade, de.max_score
            FROM {user} u
            JOIN {doselect_attempts} de ON de.userid = u.id
            WHERE de.doselectid ={$doselect->id} AND u.id={$userid}            
            GROUP BY u.id, de.doselect_slug";
     
      $usergrade =  $DB->get_record_sql($sql);

      return $usergrade;
   }
   

  
  
}

?>
