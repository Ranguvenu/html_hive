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
defined('MOODLE_INTERNAL') || die;
require_once("$CFG->libdir/externallib.php");
class local_skillrepository_external extends external_api {

	/**
     * Describes the parameters for submit_create_group_form webservice.
     * @return external_function_parameters
     */
    public static function submit_skill_repository_form_form_parameters() {
        return new external_function_parameters(
            array(
                'contextid' => new external_value(PARAM_INT, 'The context id for the evaluation'),
                'jsonformdata' => new external_value(PARAM_RAW, 'The data from the create group form, encoded as a json array'),
           
            )
        );
    }

	public static function submit_skill_repository_form_form($contextid, $jsonformdata){
		global $PAGE, $CFG;

		require_once($CFG->dirroot . '/local/skillrepository/lib.php');
        // We always must pass webservice params through validate_parameters.
		$params = self::validate_parameters(self::submit_skill_repository_form_form_parameters(),
                                    ['contextid' => $contextid, 'jsonformdata' => $jsonformdata]);
		
		$context = context_system::instance();
        // We always must call validate_context in a webservice.
		self::validate_context($context);
		$data = array();       
        parse_str($params['jsonformdata'], $data);
        $warnings = array();
		 $mform = new local_skillrepository\form\skill_repository_form(null, array(), 'post', '', null, true, $data);
		
        $repositoryinsert  = new local_skillrepository\event\insertrepository();        
        $valdata = $mform->get_data(); 
        $valdata->description=$valdata->description['text'];
        $valdata->shortname= str_replace(' ','',$valdata->name);
   
        if($valdata){
            if($valdata->id>0){
                $repositoryinsert->skillrepository_opertaions('local_skill', 'update', $valdata,'','');
            } else{
				$repositoryinsert->skillrepository_opertaions('local_skill','insert', $valdata,'','');
			}
		} else {
			// Generate a warning.
            throw new moodle_exception('Error in creation');
		}
            // die();    
	}


	/**
     * Returns description of method result value.
     *
     * @return external_description
     * @since Moodle 3.0
     */
    public static function submit_skill_repository_form_form_returns() {
        return new external_value(PARAM_INT, 'repository id');
    }

    /**
     * Describes the parameters for submit_create_group_form webservice.
     * @return external_function_parameters
     */
    public static function submit_skill_category_parameters() {
        return new external_function_parameters(
            array(
                'contextid' => new external_value(PARAM_INT, 'The context id for the skill category'),
                'jsonformdata' => new external_value(PARAM_RAW, 'The data from the create group form, encoded as a json array'),
           
            )
        );
    }

    public static function submit_skill_category($contextid, $jsonformdata){
        global $PAGE, $CFG;

        require_once($CFG->dirroot . '/local/skillrepository/lib.php');
        // We always must pass webservice params through validate_parameters.
        $params = self::validate_parameters(self::submit_skill_category_parameters(),
                                    ['contextid' => $contextid, 'jsonformdata' => $jsonformdata]);
        
        $context = context_system::instance();
        // We always must call validate_context in a webservice.
        self::validate_context($context);        
        
        $data = array();
       
        parse_str($params['jsonformdata'], $data);
        $warnings = array();
         $mform = new local_skillrepository\form\skill_category_form(null, array(), 'post', '', null, true, $data);
        
        $repositoryinsert  = new local_skillrepository\event\insertcategory();
        
        $valdata = $mform->get_data();
        
        if($valdata){
            if($valdata->id>0){
                $repositoryinsert->create_skill_category($valdata);
            } else{
                $repositoryinsert->create_skill_category($valdata);
            }
        } else {
            // Generate a warning.
            throw new moodle_exception('Error in creation');
        }
    }


    /**
     * Returns description of method result value.
     *
     * @return external_description
     * @since Moodle 3.0
     */
    public static function submit_skill_category_returns() {
        return new external_value(PARAM_INT, 'category id');
    }

    public static function repository_selector_parameters() {
        $query = new external_value(
            PARAM_RAW,
            'Query string'
        );
        $includes = new external_value(
            PARAM_ALPHA,
            'What other contexts to fetch the frameworks from. (all, parents, self)',
            VALUE_DEFAULT,
            'parents'
        );
        
        return new external_function_parameters(array(
            'query' => $query,
            'context' => self::get_context_parameters(),
            'includes' => $includes,
            
        ));
    }

    public static function repository_selector($query, $context, $includes = 'parents' /*, $limitfrom = 0, $limitnum = 25*/) {
        global $CFG, $DB;
        $params = self::validate_parameters(self::repository_selector_parameters(), array(
            'query' => $query,
            'context' => $context,
            'includes' => $includes,
        ));
        $query = $params['query'];
        $includes = $params['includes'];
        $context = self::get_context_from_params($params['context']);
        self::validate_context($context);
        $repos = array();
        if ($query) {
            $repositorysql = "SELECT id, name
                        FROM {local_skill_categories}
                        WHERE name LIKE '%$query%' AND id <> 1";
            $repos = $DB->get_records_sql($repositorysql);
        }
        return array('repos' => $repos);
    }
    
    public static function repository_selector_returns() {
        return new external_single_structure(array(
            'repos' => new external_multiple_structure(
                new external_single_structure(array(
                    'id' => new external_value(PARAM_INT, 'ID of the repository'),
                    'name' => new external_value(PARAM_RAW, 'repository name'),
                ))
            ),
        ));
    }

    //Levels related functions

    /**
     * Describes the parameters for submit_create_group_form webservice.
     * @return external_function_parameters
     */
    public static function submit_level_form_parameters() {
        return new external_function_parameters(
            array(
                'contextid' => new external_value(PARAM_INT, 'The context id for the evaluation'),
                'jsonformdata' => new external_value(PARAM_RAW, 'The data from the create group form, encoded as a json array'),
           
            )
        );
    }


    public static function submit_level_form($contextid, $jsonformdata){
        global $PAGE, $CFG;

        // require_once($CFG->dirroot . '/local/skillrepository/lib.php');
        // We always must pass webservice params through validate_parameters.
        $params = self::validate_parameters(self::submit_level_form_parameters(),
                                    ['contextid' => $contextid, 'jsonformdata' => $jsonformdata]);
        
        $context = context_system::instance();
        // We always must call validate_context in a webservice.
        self::validate_context($context);
        
        
        $data = array();
       
        parse_str($params['jsonformdata'], $data);
        $warnings = array();
         $mform = new \local_skillrepository\form\levelsform(null, array(), 'post', '', null, true, $data);
        
        $querylib  = new \local_skillrepository\local\querylib();
        
        $valdata = $mform->get_data();
        if($valdata){
            $levelid = $querylib->insert_update_level($valdata);
        } else {
            // Generate a warning.
            throw new moodle_exception('Error in creation');
        }
        return $levelid;
    }


    /**
     * Returns description of method result value.
     *
     * @return external_description
     * @since Moodle 3.0
     */
    public static function submit_level_form_returns() {
        return new external_value(PARAM_INT, 'level id');
    }

    public static function delete_skill_parameters(){
        return new external_function_parameters(
            array(
                'id' => new external_value(PARAM_INT, 'userid', 0),
                'contextid' => new external_value(PARAM_INT, 'The context id for the evaluation', false)
            )
        );
    }
    public static function delete_skill($id,$contextid){
        global $DB;

        $return = $DB->delete_records('local_skill',  array('id' => $id));
     
        return $return;
    }
    public static function delete_skill_returns(){
        return new external_value(PARAM_BOOL, 'return');
    }


    //////For displaying on index page//////////
      public static function manageskillsview_parameters() {
        return new external_function_parameters([
                'options' => new external_value(PARAM_RAW, 'The paging data for the service'),
                'dataoptions' => new external_value(PARAM_RAW, 'The data for the service'),
                'offset' => new external_value(PARAM_INT, 'Number of items to skip from the begging of the result set',
                    VALUE_DEFAULT, 0),
                'limit' => new external_value(PARAM_INT, 'Maximum number of results to return',
                    VALUE_DEFAULT, 0),
                'contextid' => new external_value(PARAM_INT, 'contextid'),
                'filterdata' => new external_value(PARAM_RAW, 'The data for the service'),
            ]);
    }

    /**
     * Gets the list of users based on the login user
     *
     * @param int $options need to give options targetid,viewtype,perpage,cardclass
     * @param int $dataoptions need to give data which you need to get records
     * @param int $limit Maximum number of results to return
     * @param int $offset Number of items to skip from the beginning of the result set.
     * @param int $filterdata need to pass filterdata.
     * @return array The list of users and total users count.
     */
    public static function manageskillsview(
        $options,
        $dataoptions,
        $offset = 0,
        $limit = 0,
        $contextid,
        $filterdata
    ) {
        global $OUTPUT, $CFG, $DB,$USER,$PAGE;
        require_once($CFG->dirroot . '/local/skillrepository/lib.php');
        require_login();
        $PAGE->set_url('/local/skillrepository/index.php', array());
        $PAGE->set_context($contextid);
        // Parameter validation.
        $params = self::validate_parameters(
            self::manageskillsview_parameters(),
            [
                'options' => $options,
                'dataoptions' => $dataoptions,
                'offset' => $offset,
                'limit' => $limit,
                'contextid' => $contextid,
                'filterdata' => $filterdata
            ]
        );
        
        $offset = $params['offset'];
        $limit = $params['limit'];
        $decodedata = json_decode($params['dataoptions']);
        $filtervalues = json_decode($filterdata);

        $stable = new \stdClass();
        $stable->thead = true;
       
        $stable->thead = false;
        $stable->start = $offset;
        $stable->length = $limit;
        $result_skill = skill_details($stable,$filtervalues);
        $totalcount = $result_skill['count'];
        $data=$result_skill['data'];
        

        return [
            'totalcount' => $totalcount,
            'records' =>$data,
            'options' => $options,
            'dataoptions' => $dataoptions,
            'filterdata' => $filterdata,
        ];

    }

    /**
     * Returns description of method result value.
     */ 
    public static function  manageskillsview_returns() {
        return new external_single_structure([
            'options' => new external_value(PARAM_RAW, 'The paging data for the service'),
            'dataoptions' => new external_value(PARAM_RAW, 'The data for the service'),
            'totalcount' => new external_value(PARAM_INT, 'total number of skills in result set'),
            'filterdata' => new external_value(PARAM_RAW, 'The data for the service'),
            'records' => new external_multiple_structure(
                            new external_single_structure(
                                array(
                                    'visible' => new external_value(PARAM_INT, 'visible skill', VALUE_OPTIONAL),
                                    'skill_id' => new external_value(PARAM_RAW, 'id in skill', VALUE_OPTIONAL),
        
                                    'skilname' => new external_value(PARAM_RAW, 'skill', VALUE_OPTIONAL),
                                    'shortname' => new external_value(PARAM_RAW, 'shortname of skill', VALUE_OPTIONAL),
                                    'skill_catname' => new external_value(PARAM_RAW, 'category name in skill', VALUE_OPTIONAL),
                                    'achieved_users' => new external_value(PARAM_RAW, 'achieved users in skill', VALUE_OPTIONAL),
                                )
                            )
                        )
        ]);
    }



    

    /**
     * Describes the parameters for submit_skil interested_form webservice.
    * @return external_function_parameters
    */
    public static function submit_skills_interested_form_parameters() {
        return new external_function_parameters(
            array(
                'contextid' => new external_value(PARAM_INT, 'The context id for the skills interested'),
                'jsonformdata' => new external_value(PARAM_RAW, 'The data from the skills interested form, encoded as a json array')
            )
        );
    }

    /**
     * Submit the Skills Interested form.
    *
    * @param int $contextid The context id .
    * @param string $jsonformdata The data from the form, encoded as a json array.
    * @return int new skill interested id.
    */
    public static function submit_skills_interested_form($contextid, $jsonformdata) {
        global $DB, $USER;
      
        // We always must pass webservice params through validate_parameters.
        $params = self::validate_parameters(self::submit_skills_interested_form_parameters(),
                                            ['contextid' => $contextid, 'jsonformdata' => $jsonformdata]);

        $context = context::instance_by_id($params['contextid'], MUST_EXIST);
        self::validate_context($context);
        $serialiseddata = json_decode($params['jsonformdata']);
       
        $data = array();

        parse_str($serialiseddata, $data);
       
        // The last param is the ajax submitted data.
        $mform = new local_skillrepository\form\skills_interested_form(null, array('contextid'=>$contextid), 'post', '', null, true, $data);
        
        $validateddata = $mform->get_data();
 
        if ($validateddata) {
            $data = new stdClass();
            $data->interested_skill_ids = implode(',',$validateddata->skills);
            $data->id = $validateddata->id;
            $data->open_costcenterid =  $USER->open_costcenterid;
           
            if($validateddata->id>0){
                $data->usermodified = $USER->id;
                $data->timemodified = time();
                $intskillsupdate = $DB->update_record('local_interested_skills',$data);
            } else{
                $data->usercreated = $USER->id;
                $data->timecreated = time();
                $intskillsinsert = $DB->insert_record('local_interested_skills',$data);
                
            }
        } else {
            // Generate a warning.
            throw new moodle_exception('Error in submission');
        }

    }
    /**filter_starttime_assignment
     * Returns description of method result value.
     *
     * @return external_description
     * @since Moodle 3.0
     */
    public static function submit_skills_interested_form_returns() {
        return new external_value(PARAM_INT, 'skills interested id');
    }

}
