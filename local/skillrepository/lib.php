<?php
/**
 * This file is part of eAbyas
 *
 * Copyright eAbyas Info Solutons Pvt Ltd, India
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author eabyas  <info@eabyas.in>
 * @package BizLMS
 * @subpackage local_skillrepository
 */
require_once(dirname(__FILE__) . '/../../config.php');
global $CFG;
require_once($CFG->dirroot . '/lib/moodlelib.php');
/*
 *  @method Create Array Format
 *  @param string $table Database Table Name
 *  @param string $column Database Table Column Name for KEY
 *  @param string $value Database Table Column Name for VALUE
 *  @return array $array contains KEY AND VALUE
 */
function create_array($table, $key, $value) {
    global $DB;
    $data = $DB->get_records('local_skill_' . $table);
    $array[NULL] = '--SELECT--';
    foreach ($data as $d) {
        $array[$d->$key] = $d->$value;
    }
    return $array;
}

/*
 *  @method Database Table Columns List
 *  @param string $table Database Table Name
 *  @return array $columnnames contains KEY AND VALUE
 */
function getTableColumns($table){
	global $DB;

	$tables = $DB->get_tables();
	$currenttable = $tables[$table];

	$columns = $DB->get_columns($tables[$currenttable]);
	    foreach ($columns as $column) {
			$columnnames[$column->name] = $column->name;
		}

	return $columnnames;
}


/*
 *  @method output fragment
 *  @param $args
 *  @return array $args contains KEY AND VALUE
 */
function local_skillrepository_output_fragment_new_skill_repository_form($args){
    global $CFG,$DB;
    $args = (object) $args;
    $context = $args->context;
    $repositoryid = $args->repositoryid;
    $o = '';
    $formdata = [];
    if (!empty($args->jsonformdata)) {
        $serialiseddata = ($args->jsonformdata);
        parse_str($serialiseddata, $formdata);
    }
    if ($args->repositoryid > 0) {
        $heading = 'Update repository';
        $collapse = false;
        $data = $DB->get_record('local_skill', array('id'=>$repositoryid));
        $description=$data->description;
        $data->description=array();
        $data->description['text'] = $description;
    }
    $editoroptions = [
        'maxfiles' => EDITOR_UNLIMITED_FILES,
        'maxbytes' => $course->maxbytes,
        'trust' => false,
        'context' => $context,
        'noclean' => false,
        'subdirs' => false,
        'autosave' => false
    ];
    $group = file_prepare_standard_editor($group, 'description', $editoroptions, $context, 'group', 'description', null);

    $mform = new local_skillrepository\form\skill_repository_form(null, array('id' => $args->repositoryid, 'editoroptions' => $editoroptions), 'post', '', null, true, $formdata);

    //print_object($data);
    $mform->set_data($data);

    if (!empty($args->jsonformdata) && strlen($args->jsonformdata)>2) {
        // If we were passed non-empty form data we want the mform to call validation functions and show errors.
        $mform->is_validated();
    }

    ob_start();
    $mform->display();
    $o .= ob_get_contents();
    ob_end_clean();
    return $o;
}

function local_skillrepository_output_fragment_skill_category_form($args){
    global $CFG,$DB;
    $args = (object) $args;
    $context = $args->context;
    $categoryid = $args->categoryid;
    $o = '';
    $formdata = [];
    if (!empty($args->jsonformdata)) {
        $serialiseddata = ($args->jsonformdata);
        parse_str($serialiseddata, $formdata);
    }
    $mform = new local_skillrepository\form\skill_category_form(null, array('id' => $args->categoryid), 'post', '', null, true, $formdata);
    if ($categoryid > 0) {
        $data = $DB->get_record('local_skill_categories', array('id'=>$categoryid));
        $mform->set_data($data);
    }
    if (!empty($formdata)) {
        // If we were passed non-empty form data we want the mform to call validation functions and show errors.
        $mform->is_validated();
    }

    ob_start();
    $mform->display();
    $o .= ob_get_contents();
    ob_end_clean();
    return $o;
}
/*
* Author Rizwana
* Displays a node in left side menu
* @return  [type] string  link for the leftmenu
*/
function local_skillrepository_leftmenunode(){
    $systemcontext = context_system::instance();
    $skillreponode = '';
    if(has_capability('local/skillrepository:create_skill', $systemcontext) || is_siteadmin()) {
        $skillreponode .= html_writer::start_tag('li', array('id'=> 'id_leftmenu_skills', 'class'=>'pull-left user_nav_div skills'));
            $skills_url = new moodle_url('/local/skillrepository/index.php');
            $skill_icon = '<span class="manage_skill_icon left_menu_icons"></span>';
            $courses = html_writer::link($skills_url, $skill_icon.'<span class="user_navigation_link_text">'.get_string('manage_skills','local_skillrepository').'</span>',array('class'=>'user_navigation_link'));
            $skillreponode .= $courses;
        $skillreponode .= html_writer::end_tag('li');
    }

    return array('18' => $skillreponode);
}

//Level related functions

function local_skillrepository_output_fragment_level_form($args){
    global $CFG,$DB;
    $args = (object) $args;
    $context = $args->context;
    $levelid = $args->levelid;
    $o = '';
    $formdata = [];
    if (!empty($args->jsonformdata)) {
        $serialiseddata = ($args->jsonformdata);
        parse_str($serialiseddata, $formdata);
    }
    $mform = new \local_skillrepository\form\levelsform(null, array('id' => $args->levelid), 'post', '', null, true, $formdata);
    if ($levelid > 0) {
        $data = $DB->get_record('local_course_levels', array('id'=>$levelid));
        $mform->set_data($data);
    }
    if (!empty($formdata)) {
        // If we were passed non-empty form data we want the mform to call validation functions and show errors.
        $mform->is_validated();
    }

    ob_start();
    $mform->display();
    $o .= ob_get_contents();
    ob_end_clean();
    return $o;
}

//////For display on index page//////////
function skill_details($tablelimits, $filtervalues){
        global $DB, $PAGE,$USER,$CFG,$OUTPUT;
        $systemcontext = context_system::instance();
        $concatsql = "";
        $countsql = "SELECT count(sk.id) FROM {local_skill} AS sk WHERE 1=1 ";
        $selectsql = "SELECT * 
            FROM {local_skill} AS sk
            WHERE 1=1 ";
        $queryparam = array();
       if(is_siteadmin()){
            
        }else{
            $costcenterid=$DB->get_field('user','open_costcenterid',array('id'=>$USER->id));
            $concatsql .= " AND sk.costcenterid= :usercostcenter ";
            $queryparam['usercostcenter'] = $costcenterid;
        }
        if($filtervalues->search_query){
            $fields = array(
                0 => 'sk.name',
            );
            $fields = implode(" LIKE '%" . $filtervalues->search_query . "%' OR ", $fields);
            $fields .= " LIKE '%" . $filtervalues->search_query . "%' ";
            $concatsql .= " AND ($fields) ";
        }
        $count = $DB->count_records_sql($countsql.$concatsql, $queryparam);
        //print_object($tablelimits);
        $concatsql.=" order by sk.id desc";
        $records = $DB->get_records_sql($selectsql.$concatsql, $queryparam, $tablelimits->start, $tablelimits->length);


        $list=array();
        $data=array();
        if ($records) {
            foreach ($records as $c) { 
                
                $list=array();
                $id = $c->id;
                $usercountsql = "SELECT count(DISTINCT(u.id)) 
                FROM {course} c
                JOIN {course_completions} cc
                on cc.course = c.id
                JOIN {user} u
                on cc.userid = u.id
                WHERE c.open_skill = {$id} and cc.timecompleted IS NOT NULL
                ";
                $usercount = $DB->count_records_sql($usercountsql);

                $skill_catname = $DB->get_field('local_skill_categories', 'name',array('id'=>$c->category));
                if($skill_catname){
                    $skill_catname = $skill_catname;
                }else{
                    $skill_catname = '---';
                }

                /*$skillurl = new moodle_url('/local/skillrepository/skillinfo.php', array('id'=>$c->id));
                $skilname = html_writer:: link($skillurl, $c->name, array());*/
               $skilname=$c->name;
               $list['skilname'] = $skilname;
               $list['skill_id'] = $c->id;
               $list['achieved_users'] = $usercount;
               $list['shortname']=$c->shortname;
               $list['skill_catname']=$skill_catname;
               $data[] = $list;
            }
        }

        return array('count' => $count, 'data' => $data); 
}

/*
* Author Sarath
* return filterform
*/
function skills_filters_form($filterparams){
    global $CFG;

    require_once($CFG->dirroot . '/local/courses/filters_form.php');
    $obj = json_decode($filterparams['dataoptions']);
   
    $categorycontext = $filterparams['context'];
    $systemcontext = context_system::instance();
    
    $context = is_siteadmin() ? $systemcontext: $categorycontext;
    
    $mform = new filters_form(null, array('filterlist'=>array('name'), 'plugins'=>array('skillrepository'),'filterparams' => $filterparams));
    
    return $mform;
}

/*
* return skills
*/
function local_skillrepository_output_fragment_skills_interested($args){
 
    global $CFG, $DB;
    $args = (object) $args;
    $context = $args->context;
    $intskill_id = $args->id;
    $o = '';
    $formdata = [];

    $o = '';
     if (!empty($args->jsonformdata)) {
        $serialiseddata = json_decode($args->jsonformdata);
        parse_str($serialiseddata, $formdata);
    }

    if (empty($formdata) && $intskill_id > 0) {
        $data = $DB->get_record('local_interested_skills', array('id'=>$intskill_id));
        $formdata = new stdClass();
        $formdata->id = $data->id;
        $formdata->interested_skill_ids = $data->interestes_skill_ids;
        
        $fromsql="SELECT * FROM {local_skill} AS sk WHERE sk.id >0 ";
        $ordersql= " ORDER BY sk.id DESC";     
        if($data->interested_skill_ids){
                $fromsql .=" AND sk.id IN ($data->interested_skill_ids) ";
        }

        $interested_skills_list = $DB->get_records_sql($fromsql .$ordersql);
        foreach($interested_skills_list as $intskills){
            $interested_skills[] =  $intskills->id;
        }
        $formdata->skills = $interested_skills;
    }

    $params = array(
    'intskill_id' => $intskill_id,
    'interested_skill_ids' => $formdata->featured_course_ids,
    'context' => $context
    ); 
   
    $mform = new local_skillrepository\form\skills_interested_form(null, array('contextid'=> $context, 'interested_skills' => $formdata->skills,'intskill_id' => $intskill_id ), 'post', '', null, true, (array)$formdata);
    $mform->set_data($formdata);
    
    if (!empty($formdata)) {
        // If we were passed non-empty form data we want the mform to call validation functions and show errors.
        $mform->is_validated();
    }
    ob_start();
    $mform->display();
    $o .= ob_get_contents();
    ob_end_clean();
    return $o;
 
}


