<?php
require_once('../../config.php');
$systemcontext = context_system::instance();
global $DB,$PAGE,$CFG, $OUTPUT, $USER;
$PAGE->set_pagelayout('admin');
/* ---check the context level of the user and check whether the user is login to the system or not--- */
$PAGE->set_context($systemcontext);
require_login();

/* ---second level of checking--- */
$PAGE->set_url('/local/courses/module_settings.php');
/* ---Header and the navigation bar--- */
$PAGE->set_heading($SITE->fullname);
$PAGE->set_title(get_string('modulesettings', 'local_courses'));
$PAGE->navbar->ignore_active();
$PAGE->navbar->add(get_string('modulesettings', 'local_courses'),new moodle_url('/local/courses/module_settings.php'));
echo $OUTPUT->header();
 echo  "<h2 id='local_costcenter_heading'>".get_string('modulesettings', 'local_courses')."</h2>";

    $data_submitted = data_submitted();
    if(!empty($data_submitted)){
        $submitted_datas = $data_submitted->module;
        $selectedmodules = array_keys($submitted_datas);
        $commasep_modules = implode(',', $selectedmodules);

        $sql = "DELETE 
                FROM {local_moduleconfig}
                WHERE id NOT IN ($commasep_modules) ";
        $DB->execute($sql);

        foreach($submitted_datas as $moduleid=>$selectedorgs){
            $insert_record = new stdClass();
            $insert_record->moduleid = $moduleid;
            $insert_record->usermodified = $USER->id;
            $selectedorgs = array_filter($selectedorgs);
            if($selectedorgs){
                foreach($selectedorgs as $selectedorg){
                    $insert_record->costcenters = $selectedorg;
                    $insert_record->timecreated = time();
                    $insert_record->timemodified = time();
                    $exists = $DB->get_record('local_moduleconfig', array('moduleid'=>$moduleid, 'costcenters'=>$selectedorg));
                    if($exists){
                        $insert_record->id = $exists->id;
                        $exists = $DB->update_record('local_moduleconfig', $insert_record);
                    }else{
                        $exists = $DB->insert_record('local_moduleconfig', $insert_record);
                    }
                }
            }
        }
    }

    $sql = "SELECT *
            FROM {modules}
            WHERE name IN ('wiziq', 'zoom')";

    $modules_list = $DB->get_records_sql($sql);
                
    $sql = "SELECT id,fullname 
            FROM {local_costcenter} 
            WHERE parentid = 0 ORDER BY id ASC";
    
    $orgslist = $DB->get_records_sql_menu($sql);
    
    $count = count($orgslist);

    $table = new html_table();

    $data=array();  
    if($modules_list){
        foreach($modules_list as $module_lists){
            $list=array();
            $i = 2;
            $costcenters_list_check=array();
            if($orgslist){
                foreach($orgslist as $orgid => $orgname){
                    $exists = $DB->record_exists('local_moduleconfig', array('moduleid'=>$module_lists->id,'costcenters'=>$orgid));
                    if($exists){
                        $checkedstatus = 'checked';
                    }else{
                        $checkedstatus = '';
                    }
                    $costcenters_list_check[$i] = '<input type="checkbox" name="module['.$module_lists->id.'][]" value="'.$orgid.'" '.$checkedstatus.'>';
                    $i++;
                }
            }
            $list[] = $module_lists->name;
            $data[] = $list + $costcenters_list_check;
        }
    }
    
    $table->head = array('Module Name') + $orgslist;  
    $table->data = $data;
    echo '<form method="post">'.html_writer::table($table).'<input type="submit" value="Submit"></form>';
    
    echo $OUTPUT->footer();