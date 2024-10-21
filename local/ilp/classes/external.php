<?php
defined('MOODLE_INTERNAL') || die;
require_once("$CFG->libdir/externallib.php");
class local_ilp_external extends external_api {
	public static function submit_ilp_parameters() {
        return new external_function_parameters(
            array(
                'id' => new external_value(PARAM_INT, 'planid', 0),
                'contextid' => new external_value(PARAM_INT, 'The context id for the evaluation', false),
                'jsonformdata' => new external_value(PARAM_RAW, 'The data from the create group form, encoded as a json array', false),
                'form_status' => new external_value(PARAM_INT, 'Form position', 0)
            )
        );
    }



    public function submit_ilp($id, $contextid, $jsonformdata, $form_status){
    	// $context = context::instance_by_id($contextid, MUST_EXIST);
		$context = context_system::instance();
        // We always must call validate_context in a webservice.
		self::validate_context($context);
		$serialiseddata = json_decode($jsonformdata);

		$data = array();
        parse_str($serialiseddata, $data);
        $mform = new local_ilp\forms\ilp(null, array('id' => $data['id'],'costcenterid' => $data['costcenter']), 'post', '', null, true, $data);
		
        $validateddata = $mform->get_data();

        $leplib = new local_ilp\lib\lib();
        if($validateddata){
            if($validateddata->id > 0){
                // $data['id'] = $data->id;
                $lepid = $leplib->update_ilp($validateddata);
            } else{
				$lepid = $leplib->create_ilp($validateddata);
			}
		} else {
			// Generate a warning.
            throw new moodle_exception('Error in creation');
		}
		$return = array(
            'id' => $lepid,
            'form_status' => $form_status);
        return $return;
    }


    public static function submit_ilp_returns() {
        return new external_single_structure(array(
            // 'error' => new external_value(PARAM_BOOL, 'error'),
            'id' => new external_value(PARAM_INT, 'ilp id'),
            'form_status' => new external_value(PARAM_INT, 'form_status'),
        ));
    }


    public static function delete_ilp_parameters() {
        return new external_function_parameters(
            array(
                'action' => new external_value(PARAM_ACTION, 'Action of the event', false),
                'id' => new external_value(PARAM_INT, 'ID of the record', 0),
                'confirm' => new external_value(PARAM_BOOL, 'Confirm', false),
                'name' => new external_value(PARAM_RAW, 'name', false),
            )
        );
    }

    public static function delete_ilp($action, $id, $confirm, $name) {
        global $DB;
        try {
            if ($confirm) {
                // $DB->delete_records('local_ilp_sessions', array('id' => $id));
                $ilplib = new local_ilp\lib\lib();
                $ilplib->delete_ilp($id);
                $return = true;
            } else {
                $return = false;
            }
        } catch (dml_exception $ex) {
            print_error('deleteerror', 'local_ilp');
            $return = false;
        }
        return $return;
    }

    public static function delete_ilp_returns() {
        return new external_value(PARAM_BOOL, 'return');
    }
    public static function toggle_ilp_parameters() {
        return new external_function_parameters(
            array(
                'action' => new external_value(PARAM_ACTION, 'Action of the event', false),
                'id' => new external_value(PARAM_INT, 'ID of the record', 0),
                'visible' => new external_value(PARAM_TEXT,'Visible or hidden text',false),
                'confirm' => new external_value(PARAM_BOOL, 'Confirm', false),
                'name' => new external_value(PARAM_RAW, 'name', false),
            )
        );
    }
    public static function toggle_ilp($action, $id, $visible, $confirm, $name) {
        try {
            if ($confirm) {
                $ilplib = new local_ilp\lib\lib();
                $ilplib->toggleilp($id);
                $return = true;
            } else {
                $return = false;
            }
        } catch (dml_exception $ex) {
            print_error('toggleerror', 'local_ilp');
            $return = false;
        }
        return $return;
    }
    public static function toggle_ilp_returns() {
        return new external_value(PARAM_BOOL, 'return');
    }

    public static function lpcourse_enrol_form_parameters() {
        return new external_function_parameters(
            array(
                'planid' => new external_value(PARAM_INT, 'planid', 0),
                'contextid' => new external_value(PARAM_INT, 'The context id for the evaluation', false),
                'jsonformdata' => new external_value(PARAM_RAW, 'The data from the courseenrolform, encoded as a json array', false)
            )
        );
    }
    public static function lpcourse_enrol_form($planid,$contextid, $jsonformdata) {
        global $DB;
        $context = context_system::instance();
        // We always must call validate_context in a webservice.
        self::validate_context($context);
        $serialiseddata = json_decode($jsonformdata);

        $data = array();
        parse_str($serialiseddata, $data);

        $mform = new local_ilp\forms\courseenrolform(null,array('planid' => $planid, 'condition' => 'manage'), 'post', '', null, true, $data);
        $validateddata = $mform->get_data();
        if($validateddata){
            $courses = array();
            foreach($data['ilp_courses'] as $datas){
                foreach($datas as $key => $value){
                    $courses[] = $value;
                }
            }
            $lib = new local_ilp\lib\lib();
            $return = $lib->modal_lpcourse_enrol($courses,$planid);

            
            // $DB->insert_record($table,  $dataobject,  $returnid=true,  $bulk=false)
        }
        else{
            // Generate a warning.
            throw new moodle_exception('Error in creation');
        }
        return $return;
    }
    public static function lpcourse_enrol_form_returns() {
        return new external_value(PARAM_BOOL, 'return');
    }

    public static function lpcourse_unassign_course_parameters(){
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'ID of the course to be unassigned', 0),
                'planid' => new external_value(PARAM_INT, 'ID of the ilp', 0),
            )
        );
    }
    public static function lpcourse_unassign_course($courseid,$planid){
        if($courseid>0 && $planid >0){
            $ilplib = new local_ilp\lib\lib();
            $ilplib->unassign_delete_courses_to_ilps($courseid,$planid);
            return true;
        }else{
            throw new moodle_exception('Error in unassigning of course');
            return false;
        }

    }
    public static function lpcourse_unassign_course_returns(){
        return new external_value(PARAM_BOOL, 'return');
    }
	public static function lpcourse_unassign_user_parameters(){
        return new external_function_parameters(
            array(
                'userid' => new external_value(PARAM_INT, 'ID of the user to be unassigned', 0),
                'planid' => new external_value(PARAM_INT, 'ID of the ilp', 0),
            )
        );
    }
    public static function lpcourse_unassign_user($userid,$planid){
        if($userid>0 && $planid >0){
            $ilplib = new local_ilp\lib\lib();
            $ilplib->unassign_delete_users_to_ilps($userid,$planid);
            return true;
        }else{
            throw new moodle_exception('Error in unassigning of course');
            return false;
        }

    }
    public static function lpcourse_unassign_user_returns(){
        return new external_value(PARAM_BOOL, 'return');
    }
}