<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Adds new instance of enrol_auto to specified course
 * or edits current instance.
 *
 * @package enrol_auto
 * @author Eugene Venter <eugene@catalyst.net.nz>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');
use core_component;
class enrol_auto_edit_form extends moodleform {

    protected function definition() {
        global $DB,$CFG,$USER;

        $mform = $this->_form;

        // Clear the observer cache to ensure observers for any newly-installed plugins are added
        $cache = \cache::make('core', 'observers');
        $cache->delete('all');

        list($instance, $plugin, $context) = $this->_customdata;
        if($instance->department==-1){
                $instance->department=null;
        }
        $instance->open_group =(!empty($instance->open_group)) ? array_diff(explode(',',$instance->open_group), array('')) :array(NULL=>NULL);
        $instance->open_hrmsrole =(!empty($instance->open_hrmsrole)) ? array_diff(explode(',',$instance->open_hrmsrole), array('')) :array(NULL=>NULL);
        $instance->open_designation =(!empty($instance->open_designation)) ? array_diff(explode(',',$instance->open_designation), array('')) :array(NULL=>NULL);
        // $instance->open_location =(!empty($instance->open_location)) ? array_diff(explode(',',$instance->open_location), array('')) :array(NULL=>NULL);
        $instance->open_country =(!empty($instance->open_country)) ? array_diff(explode(',',$instance->open_country), array('')) :array(NULL=>NULL);
        $instance->open_ouname =(!empty($instance->open_ouname)) ? array_diff(explode(',',$instance->open_ouname), array('')) :array(NULL=>NULL);

        //$mform->addElement('header', 'header', get_string('pluginname', 'enrol_auto'));
        //
        //$mform->addElement('text', 'name', get_string('custominstancename', 'enrol'));
        //$mform->setType('name', PARAM_TEXT);

        $options = array(ENROL_INSTANCE_ENABLED  => get_string('yes'),
                         ENROL_INSTANCE_DISABLED => get_string('no'));
        $mform->addElement('select', 'status', get_string('status', 'enrol_auto'), $options);
        $mform->addHelpButton('status', 'status', 'enrol_auto');

        $options = array(ENROL_AUTO_COURSE_VIEWED => get_string('courseview', 'enrol_auto'),
                         ENROL_AUTO_LOGIN => get_string('userlogin', 'enrol_auto'),
                         ENROL_AUTO_MOD_VIEWED    => get_string('modview', 'enrol_auto'));
        $mform->addElement('select', 'customint3', get_string('enrolon', 'enrol_auto'), $options);
        $mform->addHelpButton('customint3', 'enrolon', 'enrol_auto');

        //$mods = \enrol_auto\helper::get_mods_with_viewed_event();
        //$modgroup = array();
        //foreach ($mods as $modname) {
        //    $modgroup[] = $mform->createElement('checkbox', $modname, '', get_string('pluginname', "mod_{$modname}"));
        //}
        //$mform->addGroup($modgroup, 'customtext2', get_string('modviewmods', 'enrol_auto'), '<br>', true);
        //$mform->disabledIf('customtext2', 'customint3', 'neq', ENROL_AUTO_MOD_VIEWED);

        $roles = $this->extend_assignable_roles($context, $instance->roleid);
        $mform->addElement('select', 'roleid', get_string('role', 'enrol_auto'), $roles,array('disabled'=>'disabled'));

        //$mform->addElement('advcheckbox', 'customint2', get_string('sendcoursewelcomemessage', 'enrol_auto'));
        //$mform->addHelpButton('customint2', 'sendcoursewelcomemessage', 'enrol_auto');
        //
        //$mform->addElement('textarea', 'customtext1', get_string('customwelcomemessage', 'enrol_auto'), array('cols' => '60', 'rows' => '8'));
        //$mform->addHelpButton('customtext1', 'customwelcomemessage', 'enrol_auto');
        //$mform->disabledIf('customtext1', 'customint2', 'notchecked');

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);

        // OL-1042 Add Target Audience to courses//
         $mform->addElement('header', 'header',get_string('target_audience', 'local_users'));
          $context = context_system::instance();

            $costcntersql = "SELECT id, fullname
                                FROM {local_costcenter}
                                WHERE visible = 1 AND depth =2 ";                  
             $departments[-1]=get_string('all');
           
         if(is_siteadmin()||has_capability('local/costcenter:manage_multiorganizations',$context)){

                $departments+= $DB->get_records_sql_menu($costcntersql);
                $selectdepartment =$mform->addElement('autocomplete', 'department', get_string('department',
                    'local_classroom'), $departments);
                $mform->setType('department', PARAM_INT);
                //$selectdepartment->setMultiple(true);
                
            }elseif(has_capability('local/costcenter:manage_ownorganization',$context)){
             
                $costcntersql .= " AND parentid =  $USER->open_costcenterid ";
                
                $departments+= $DB->get_records_sql_menu($costcntersql);
                
                $selectdepartment =$mform->addElement('autocomplete', 'department', get_string('department',
                    'local_classroom'), $departments);
                $mform->setType('department', PARAM_INT);
                //$selectdepartment->setMultiple(true);
                
            }elseif(has_capability('local/costcenter:manage_owndepartments',$context)){
                
                $mform->addElement('hidden', 'department', get_string('department',
                    'local_classroom'));
                $mform->setType('department', PARAM_INT);
                $mform->setDefault('department', $USER->open_departmentid);
            }
            $core_component = new core_component();
            $users_plugin_exist = $core_component::get_plugin_directory('local','users');
            if ($users_plugin_exist) {
                require_once($CFG->dirroot . '/local/users/lib.php');
                $functionname ='globaltargetaudience_elementlist';
                 if(function_exists($functionname)) {
                    $functionname($mform,array('group','hrmsrole','designation','country','ouname'));
                }
            }
        // OL-1042 Add Target Audience to courses// 
        $this->add_action_buttons(true, ($instance->id ? null : get_string('addinstance', 'enrol')));

        $instance->customtext2 = array_flip(explode(',', $instance->customtext2));
        $instance->customtext2 = array_map(
            function ($a) {
                return 1;
            },
            $instance->customtext2
        );
        // print_object($instance);
        $this->set_data($instance);
    }

    /**
     * Gets a list of roles that this user can assign for the course as the default for auto-enrolment.
     *
     * @param context $context the context.
     * @param integer $defaultrole the id of the role that is set as the default for auto-enrolment
     * @return array index is the role id, value is the role name
     */
    protected function extend_assignable_roles($context, $defaultrole) {
        global $DB;

        $roles = get_assignable_roles($context, ROLENAME_BOTH);
        if (!isset($roles[$defaultrole])) {
            if ($role = $DB->get_record('role', array('id' => $defaultrole))) {
                $roles[$defaultrole] = role_get_name($role, $context, ROLENAME_BOTH);
            }
        }
        return $roles;
    }
}
