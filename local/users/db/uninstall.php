<?php
defined('MOODLE_INTERNAL') || die();
function xmldb_local_users_uninstall(){
	global $DB;
	$dbman = $DB->get_manager();
  $table = new xmldb_table('user');
	if ($dbman->table_exists($table)) {

    $costcenterfield = new xmldb_field('open_costcenterid');
    if($dbman->field_exists($table, $costcenterfield)){
      $dbman->drop_field($table, $costcenterfield); 
    }
      

    $departmentfield = new xmldb_field('open_departmentid');
    if($dbman->field_exists($table, $departmentfield)){
       $dbman->drop_field($table, $departmentfield);
    }

    $supervisorfield = new xmldb_field('open_supervisorid');
    if($dbman->field_exists($table, $supervisorfield)){
      $dbman->drop_field($table, $supervisorfield);
    }

    $employeefield = new xmldb_field('open_employeeid');
    if($dbman->field_exists($table, $employeefield)){
      $dbman->drop_field($table, $employeefield);
    }

    $usermodfield = new xmldb_field('open_usermodified');
    if($dbman->field_exists($table, $usermodfield)){
      $dbman->drop_field($table, $usermodfield);
    }

    $desigfield = new xmldb_field('open_designation');
    if($dbman->field_exists($table, $desigfield)){
      $dbman->drop_field($table, $desigfield); 
    }

    $openlevelfield = new xmldb_field('open_level');
    if($dbman->field_exists($table, $openlevelfield)){
      $dbman->drop_field($table, $openlevelfield); 
    }

    $openstatefield = new xmldb_field('open_state');
    if($dbman->field_exists($table, $openstatefield)){
      $dbman->drop_field($table, $openstatefield); 
    }

    $branchfield = new xmldb_field('open_branch');
    if($dbman->field_exists($table, $branchfield)){
      $dbman->drop_field($table, $branchfield); 
    }

    $jobfnfield = new xmldb_field('open_jobfunction');
    if($dbman->field_exists($table, $jobfnfield)){
      $dbman->drop_field($table, $jobfnfield); 
    }

    $groupfield = new xmldb_field('open_group');
    if($dbman->field_exists($table, $groupfield)){
      $dbman->drop_field($table, $groupfield); 
    }

    $qualifield = new xmldb_field('open_qualification');
    if($dbman->field_exists($table, $qualifield)){
      $dbman->drop_field($table, $qualifield); 
    }

    $subdepfield = new xmldb_field('open_subdepartment');
    if($dbman->field_exists($table, $subdepfield)){
      $dbman->drop_field($table, $subdepfield); 
    }

    $locafield = new xmldb_field('open_location');
    if($dbman->field_exists($table, $locafield)){
      $dbman->drop_field($table, $locafield);
    } 

    $supempidfield = new xmldb_field('open_supervisorempid');
    if($dbman->field_exists($table, $supempidfield)){
      $dbman->drop_field($table, $supempidfield); 
    }

    $openbandfield = new xmldb_field('open_band');
    if($dbman->field_exists($table, $openbandfield)){
      $dbman->drop_field($table, $openbandfield);
    } 


    $openhrmsrolefield = new xmldb_field('open_hrmsrole');
    if($dbman->field_exists($table, $openhrmsrolefield)){
      $dbman->drop_field($table, $openhrmsrolefield); 
    }

    $openzonefield = new xmldb_field('open_zone');
    if($dbman->field_exists($table, $openzonefield)){
      $dbman->drop_field($table, $openzonefield); 
    }

    $openregionfield = new xmldb_field('open_region');
    if($dbman->field_exists($table, $openregionfield)){
      $dbman->drop_field($table, $openregionfield); 
    }

    $opengradefield = new xmldb_field('open_grade');
    if($dbman->field_exists($table, $opengradefield)){
      $dbman->drop_field($table, $opengradefield); 
    }

    $openteamfield = new xmldb_field('open_team');
    if($dbman->field_exists($table, $openteamfield)){
      $dbman->drop_field($table, $openteamfield); 
    }

    $openclientfield = new xmldb_field('open_client');
    if($dbman->field_exists($table, $openclientfield)){
      $dbman->drop_field($table, $openclientfield); 
    }

    //commented by sarath its not working for mssql
  	// $sql = 'ALTER TABLE `mdl_user`
    // 			DROP `open_costcenterid`,DROP `open_departmentid`,DROP `open_supervisorid`,DROP `open_employeeid`,
    // 			DROP `open_usermodified`,DROP `open_designation`,DROP `open_level`,DROP `open_state`,
    // 			DROP `open_branch`,DROP `open_jobfunction`,DROP `open_group`,DROP `open_qualification`,
    // 			DROP `open_subdepartment`,DROP `open_location`,DROP `open_supervisorempid`,
    // 			DROP `open_band`,DROP `open_hrmsrole`,DROP `open_zone`,DROP `open_region`,
    // 			DROP `open_grade`,DROP `open_team`,DROP `open_client` ';
    // 		$DB->execute($sql);
	}
  return true;
}
