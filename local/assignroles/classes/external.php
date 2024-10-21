
<?php
defined('MOODLE_INTERNAL') || die;
require_once("$CFG->libdir/externallib.php");
class local_assignroles_external extends external_api {

    /**
     * Describes the parameters for submit_create_group_form webservice.
     * @return external_function_parameters
     */
    public static function submit_assignrole_form_parameters() {
        return new external_function_parameters(
            array(
                'contextid' => new external_value(PARAM_INT, 'The context id for the evaluation'),
                'roleid' => new external_value(PARAM_INT, 'The role id for the evaluation'),
                'jsonformdata' => new external_value(PARAM_RAW, 'The data from the create group form, encoded as a json array'),

            )
        );
    }

    /**
     * form submission of role name and returns instance of this object
     *
     * @param int $contextid
     * @param [string] $jsonformdata
     * @return assignrole form submits
     */
    public static function submit_assignrole_form($contextid, $roleid,$jsonformdata){
        global $PAGE, $CFG;

        require_once($CFG->dirroot . '/local/assignroles/lib.php');
        // We always must pass webservice params through validate_parameters.
        $params = self::validate_parameters(self::submit_assignrole_form_parameters(),
                                    ['contextid' => $contextid, 'roleid'=>$roleid,'jsonformdata' => $jsonformdata]);
        
        $context = context_system::instance();
        // We always must call validate_context in a webservice.
        self::validate_context($context);
        $serialiseddata = json_decode($params['jsonformdata']);
        // throw new moodle_exception('Error in creation');
        // die;
        $data = array();

        parse_str($serialiseddata, $data);
        $warnings = array();
         $mform = new local_assignroles\form\assignrole(null, array('roleid'=>$roleid), 'post', '', null, true, $data);
        $roles  = new local_assignroles\local\assignrole();
        $valdata = $mform->get_data();
        
        if($valdata){
  

                $roles->rolesassign($valdata->users,$valdata->roleid,$valdata->contextid);
            
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
    public static function submit_assignrole_form_returns() {
        return new external_value(PARAM_INT, 'role id');
    }
    /**
     * Describes the parameters for local_unassign_role webservice.
     * @return external_function_parameters
     */
    public static function local_unassign_role_parameters(){
        return new external_function_parameters(
            array(
                'contextid' => new external_value(PARAM_INT, 'The context id for role unassigning'),
                'roleid' => new external_value(PARAM_INT, 'The role id for role unassigning'),
                'userid' => new external_value(PARAM_RAW, 'The user id for unassigning role'),

            )
        );
    }
    /**
     * local_unassign_role for unassigniung user from role
     *
     * @param [int] $contextid
     * @param [int] $roleid
     * @param [int] $userid
     * @return param bool for status
     */
    public static function local_unassign_role($context, $roleid, $userid){
        global $CFG;
        $params = self::validate_parameters(self::local_unassign_role_parameters(),
                                    ['contextid' => $contextid, 'roleid'=>$roleid,'userid' => $userid]);
        require_once($CFG->dirroot . '/lib/accesslib.php');
        $systemcontext = \context_system::instance();
        try{
            role_unassign($roleid, $userid,$systemcontext->id, '');
            return true;
        }catch(Exception $e){
            throw new moodle_exception('Error in unassigning role '. $e);
            return false;
        }


    }
    /**
     * Describes the return for local_unassign_role webservice.
     * @return external_function_return
     */
    public static function local_unassign_role_returns(){
        return new external_value(PARAM_BOOL, 'status');
    }
    /**
     * Describes the parameters for assignrole_form_option_selector webservice.
     * @return external_function_parameters
     */
    public static function assignrole_form_option_selector_parameters(){
        $query = new external_value(PARAM_RAW, 'Query string');
        $action = new external_value(PARAM_RAW, 'Action for the classroom form selector');
        $options = new external_value(PARAM_RAW, 'Action for the classroom form selector');
        $searchanywhere = new external_value(PARAM_BOOL, 'find a match anywhere, or only at the beginning');
        $page = new external_value(PARAM_INT, 'Page number');
        $perpage = new external_value(PARAM_INT, 'Number per page');
        return new external_function_parameters(array(
            'query' => $query,
            'action' => $action,
            'options' => $options,
            'searchanywhere' => $searchanywhere,
            'page' => $page,
            'perpage' => $perpage,

        ));
    }
    /**
     * assignrole_form_option_selector for autocomplete fields
     *
     * @param [char] $query
     * @param [char] $action
     * @param [text] $options(json)
     * @param [bool] $searchanywhere
     * @param [int] $page
     * @param [int] $perpage
     * @return [Json] data for auto complete
     */
    public static function assignrole_form_option_selector($query, $action, $options, $searchanywhere, $page, $perpage){
        $params = self::validate_parameters(self::assignrole_form_option_selector_parameters(), array(
            'query' => $query,
            'action' => $action,
            'options' => $options,
            'searchanywhere' => $searchanywhere,
            'page' => $page,
            'perpage' => $perpage
        ));
        global $DB,$USER;
        $query = $params['query'];
        $action = $params['action'];
        $options = $params['options'];
        $searchanywhere=$params['searchanywhere'];
        $page=$params['page'];
        $perpage=$params['perpage'];
        if (!empty($options)) {
            $formoptions = json_decode($options);
        }
        if ($action) {
            switch($action){
                case 'role_users':
                $systemcontext = \context_system::instance();

                    if(is_siteadmin()){
                      $userssql =  "SELECT u.id, concat(u.firstname,' ',u.lastname) as fullname 
                        FROM {user} AS u 
                        WHERE u.id > 2 AND u.deleted = 0 AND u.suspended = 0 AND u.id <> :loginuser AND u.id NOT IN (SELECT userid FROM {role_assignments} WHERE contextid=:systemcontext AND roleid=:roleid)";  
                    }else{

                        $userssql =  "SELECT u.id, concat(u.firstname,' ',u.lastname) as fullname 
                        FROM {user} AS u 
                        WHERE u.id > 2 AND u.deleted = 0 AND u.suspended = 0  AND u.id NOT IN (SELECT userid FROM {role_assignments} WHERE contextid=:systemcontext AND roleid=:roleid)";  

                    }
                    
                    $params = array('loginuser' =>$USER->id, 'systemcontext' => $systemcontext->id, 'roleid' => $formoptions->roleid);
                    if(!empty($query)){ 
                        if ($searchanywhere) {
                            $userssql .=" AND CONCAT(u.firstname,' ',u.lastname) LIKE :query ";
                            $params['query'] = "%$query%";
                        } else {
                            $userssql .=" AND CONCAT(u.firstname,' ',u.lastname) LIKE :query ";
                            $params['query'] = "$query%";
                        }
                    }
                    if(!(is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations', $systemcontext))){
                        $userssql .= " AND u.open_costcenterid=:logincostcenter";
                        $params['logincostcenter'] = $USER->open_costcenterid;
                    }
                    $userslist = $DB->get_records_sql($userssql, $params, $page, $perpage);
                break;
            }
            return json_encode($userslist);
        }

    }
    /**
     * Describes the return value of assignrole_form_option_selector webservice.
     * @return external_function_returns
     */
    public static function assignrole_form_option_selector_returns(){
        return new external_value(PARAM_RAW, 'data');
    }
    
}
