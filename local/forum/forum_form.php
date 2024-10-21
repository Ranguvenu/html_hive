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
 * @subpackage local_forum
 */


if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}
if(file_exists($CFG->dirroot . '/local/costcenter/lib.php')){
    require_once($CFG->dirroot . '/local/costcenter/lib.php');
}
require_once($CFG->libdir . '/formslib.php');
use local_users\functions\userlibfunctions as userlib;
class forum_form extends moodleform {

    function definition() {
        global $CFG, $COURSE, $USER, $DB;
        $mform    =& $this->_form;
        $id = $this->_customdata['id'];
        $form_status = $this->_customdata['form_status'];
//-------------------------------------------------------------------------------
        if($form_status == 0) {
            $mform->addElement('text', 'name', get_string('forumname', 'forum'), array('size'=>'64'));
            if (!empty($CFG->formatstringstriptags)) {
                $mform->setType('name', PARAM_TEXT);
            } else {
                $mform->setType('name', PARAM_CLEANHTML);
            }
            $mform->addRule('name', get_string('forumnamee', 'local_forum'), 'required', null, 'client');
            //$mform->addRule('name', null, 'required', null, 'client');
            $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
            
            $mform->addElement('editor', 'introeditor', get_string('moduleintro'), array('rows' => 10), array('maxfiles' => EDITOR_UNLIMITED_FILES,
                'noclean' => true,  'subdirs' => true,'autosave' => false));
            $mform->setType('introeditor', PARAM_RAW);
    
            $forumtypes = local_forum_get_forum_types();
            core_collator::asort($forumtypes, core_collator::SORT_STRING);
            $mform->addElement('select', 'type', get_string('forumtype', 'forum'), $forumtypes);
            $mform->addHelpButton('type', 'forumtype', 'forum');
            $mform->setDefault('type', 'general');
            $context = context_system::instance();
            $departmentslist = array(get_string('all'));
            if (is_siteadmin($USER->id) || has_capability('local/costcenter:manage_multiorganizations',$context)) {
                $sql="select id,fullname from {local_costcenter} where visible =1 AND parentid = 0";
                $costcenters = $DB->get_records_sql($sql);
            
                $organizationlist=array(null=>get_string('select_organization', 'local_forum'));
                foreach ($costcenters as $scl) {
                    $organizationlist[$scl->id]=$scl->fullname;
                }
                $mform->addElement('autocomplete', 'costcenterid', get_string('organization', 'local_users'), $organizationlist);
                $mform->addRule('costcenterid', null, 'required', null, 'client');
                $mform->setType('costcenterid', PARAM_RAW);
            } elseif (has_capability('local/costcenter:manage_ownorganization',$context)){
                $user_dept = $DB->get_field('user','open_costcenterid', array('id'=>$USER->id));
                $mform->addElement('hidden', 'costcenterid', $user_dept, array('id' => 'id_costcenterid'));
                $mform->setType('costcenterid', PARAM_RAW);
                $mform->setConstant('costcenterid', $user_dept);
                $sql="select id,fullname from {local_costcenter} where visible =1 AND parentid = $user_dept";
                $departmentslists = $DB->get_records_sql_menu($sql);
                if(isset($departmentslists)&&!empty($departmentslists))
                $departmentslist = $departmentslist+$departmentslists;
            } else {
                $user_dept = $DB->get_field('user','open_costcenterid', array('id'=>$USER->id));
                $mform->addElement('hidden', 'costcenterid', null);
                $mform->setType('costcenterid', PARAM_RAW);
                $mform->setConstant('costcenterid', $user_dept);
                
                $mform->addElement('hidden', 'departmentid');
                $mform->setType('departmentid', PARAM_INT);
                $mform->setConstant('departmentid', $USER->open_departmentid);
                
            }

            if (is_siteadmin($USER->id) || has_capability('local/courses:manage', $context)) {
                $sql="select id,fullname from {course} where visible =1 ";
                $courses = $DB->get_records_sql($sql);
            
                $courseslist=array(null=>get_string('selectcourse', 'local_courses'));
                foreach ($courses as $course) {
                    $courseslist[$course->id]=$course->fullname;
                }
                $courseselect = $mform->addElement('autocomplete', 'courseid', get_string('courses', 'local_courses'), $courseslist);
                //$mform->addRule('courseid', null, 'required', null, 'client');
                $courseselect->setMultiple(true);
                $mform->setType('courseid', PARAM_RAW);
            } 
            
            if (is_siteadmin($USER->id) || has_capability('local/costcenter:manage_multiorganizations',$context) ||
                has_capability('local/costcenter:manage_ownorganization',$context)) {
                if($id > 0) {
                    $open_costcenterid = $DB->get_field('local_forum','costcenterid',array('id'=>$id));
                } else {
                    $open_costcenterid = $this->_ajaxformdata['costcenterid'];
                }
    
                if(!empty($open_costcenterid)) {
                    $departments = userlib::find_departments_list($open_costcenterid);
                    foreach($departments as $depart){
                        $departmentslist[$depart->id]=$depart->fullname;
                    }
                }
                $mform->addElement('autocomplete', 'departmentid', get_string('department','local_evaluation'),$departmentslist);
                $mform->setType('departmentid', PARAM_RAW);
            }
            
            // tags
            //$mform->addElement('tags', 'tags', get_string('tags'), array('itemtype' => 'forum', 'component' => 'local_forum'));
            // group selection
            if ($id <= 0)
			$costcenterid = $this->_ajaxformdata['costcenterid'];
			$select_list = array(get_string('all'));
         /*    if (empty($id)) {
                if(is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations',$context) ||
                has_capability('local/costcenter:manage_ownorganization',$context)){
    				$open_departmentid = $this->_ajaxformdata['departmentid'];    				
    				if ( $open_departmentid ) {
    					$groupslist = $DB->get_records_sql_menu("select lg.id, c.name from {cohort} c, {local_groups} lg where lg.cohortid = c.id AND lg.departmentid = ?", array($open_departmentid));
    				} else {
    					if ($costcenterid) {
							$groupslist = $DB->get_records_sql_menu("select lg.id, c.name from {cohort} c, {local_groups} lg where lg.cohortid = c.id AND lg.costcenterid = ?", array($costcenterid));
						} elseif ((!is_siteadmin()) AND (!has_capability('local/costcenter:manage_multiorganizations',$context)) AND has_capability('local/costcenter:manage_ownorganization',$context)){
							$groupslist = $DB->get_records_sql_menu("select lg.id, c.name from {cohort} c, {local_groups} lg where lg.cohortid = c.id AND lg.costcenterid = ?", array($USER->open_costcenterid));
						}
    				}
                } else {
                    $groupslist = $DB->get_records_sql_menu("select lg.id, c.name from {cohort} c, {local_groups} lg where lg.cohortid = c.id AND lg.departmentid = ?", array($USER->open_departmentid));
                }
                if(isset($groupslist)&&!empty($groupslist)) {
                    $grouplist = $select_list + $groupslist;
                } else {
                    $grouplist = $select_list;
                }
				$groupselect = $mform->addElement('autocomplete', 'local_group', get_string('cohort','local_groups'), $grouplist);
                $groupselect->setMultiple(true);
                $mform->setType('local_group', PARAM_RAW);
            } else {
                $open_departmentid = $this->_ajaxformdata['departmentid'];
                if(!empty($open_departmentid)) {
                    $groupslist = $DB->get_records_sql_menu("select lg.id, c.name from {cohort} c, {local_groups} lg where lg.cohortid = c.id AND lg.departmentid = ?", array($open_departmentid));
                } elseif($open_costcenterid) {
                    if(is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations',$context) ||
                    has_capability('local/costcenter:manage_ownorganization',$context))
                    $groupslist = $DB->get_records_sql_menu("select lg.id, c.name from {cohort} c, {local_groups} lg where lg.cohortid = c.id AND lg.costcenterid = ?", array($open_costcenterid));
                    else
                    $groupslist = $DB->get_records_sql_menu("select lg.id, c.name from {cohort} c, {local_groups} lg where lg.cohortid = c.id AND lg.departmentid = ?", array($USER->open_departmentid));
                } else {
                    if(is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations',$context) ||
                    has_capability('local/costcenter:manage_ownorganization',$context))
                    $groupslist = $DB->get_records_sql_menu("select lg.id, c.name from {cohort} c, {local_groups} lg where lg.cohortid = c.id AND lg.costcenterid = ?", array($USER->open_costcenterid));
                    else
                    $groupslist = $DB->get_records_sql_menu("select lg.id, c.name from {cohort} c, {local_groups} lg where lg.cohortid = c.id AND lg.departmentid = ?", array($USER->open_departmentid));
                }
                if(isset($groupslist)&&!empty($groupslist)) {
                    $grouplist = $select_list + $groupslist;
                } else {
                    $grouplist = $select_list;
                }
                $groupselect = $mform->addElement('autocomplete', 'local_group', get_string('cohort','local_groups'), $grouplist);
                // $groupselect->setMultiple(true);
                $mform->setType('local_group', PARAM_RAW);
            } */
            
        } else if ($form_status == 1) {
            // Attachments and word count.
            $mform->addElement('header', 'attachmentswordcounthdr', get_string('attachmentswordcount', 'forum'));
    
            $choices = get_max_upload_sizes($CFG->maxbytes, $COURSE->maxbytes, 0, $CFG->local_forum_maxbytes);
            $choices[1] = get_string('uploadnotallowed');
            $mform->addElement('select', 'maxbytes', get_string('maxattachmentsize', 'forum'), $choices);
            $mform->addHelpButton('maxbytes', 'maxattachmentsize', 'forum');
            $mform->setDefault('maxbytes', $CFG->local_forum_maxbytes);
    
            $choices = array(
                0 => 0,
                1 => 1,
                2 => 2,
                3 => 3,
                4 => 4,
                5 => 5,
                6 => 6,
                7 => 7,
                8 => 8,
                9 => 9,
                10 => 10,
                20 => 20,
                50 => 50,
                100 => 100
            );
            $mform->addElement('select', 'maxattachments', get_string('maxattachments', 'forum'), $choices);
            $mform->addHelpButton('maxattachments', 'maxattachments', 'forum');
            $mform->setDefault('maxattachments', $CFG->local_forum_maxattachments);
    
            $mform->addElement('selectyesno', 'displaywordcount', get_string('displaywordcount', 'forum'));
            $mform->addHelpButton('displaywordcount', 'displaywordcount', 'forum');
            $mform->setDefault('displaywordcount', 0);
    
            // Subscription and tracking.
            $mform->addElement('header', 'subscriptionandtrackinghdr', get_string('subscriptionandtracking', 'forum'));
    
            $options = local_forum_get_subscriptionmode_options();
            $mform->addElement('select', 'forcesubscribe', get_string('subscriptionmode', 'forum'), $options);
            $mform->addHelpButton('forcesubscribe', 'subscriptionmode', 'forum');
            if (isset($CFG->local_forum_subscription)) {
                $defaultforumsubscription = $CFG->local_forum_subscription;
            } else {
                $defaultforumsubscription = LOCAL_FORUM_CHOOSESUBSCRIBE;
            }
            $mform->setDefault('forcesubscribe', $defaultforumsubscription);
    
            $options = array();
            $options[LOCAL_FORUM_TRACKING_OPTIONAL] = get_string('trackingoptional', 'forum');
            $options[LOCAL_FORUM_TRACKING_OFF] = get_string('trackingoff', 'forum');
            if ($CFG->local_forum_allowforcedreadtracking) {
                $options[LOCAL_FORUM_TRACKING_FORCED] = get_string('trackingon', 'forum');
            }
            $mform->addElement('select', 'trackingtype', get_string('trackingtype', 'forum'), $options);
            $mform->addHelpButton('trackingtype', 'trackingtype', 'forum');
            $default = $CFG->local_forum_trackingtype;
            if ((!$CFG->local_forum_allowforcedreadtracking) && ($default == LOCAL_FORUM_TRACKING_FORCED)) {
                $default = LOCAL_FORUM_TRACKING_OPTIONAL;
            }
            $mform->setDefault('trackingtype', $default);
    
            $mform->addElement('header', 'discussionlocking', get_string('discussionlockingheader', 'forum'));
            $options = [
                0               => get_string('discussionlockingdisabled', 'forum'),
                1   * DAYSECS   => get_string('numday', 'core', 1),
                1   * WEEKSECS  => get_string('numweek', 'core', 1),
                2   * WEEKSECS  => get_string('numweeks', 'core', 2),
                30  * DAYSECS   => get_string('nummonth', 'core', 1),
                60  * DAYSECS   => get_string('nummonths', 'core', 2),
                90  * DAYSECS   => get_string('nummonths', 'core', 3),
                180 * DAYSECS   => get_string('nummonths', 'core', 6),
                1   * YEARSECS  => get_string('numyear', 'core', 1),
            ];
            $mform->addElement('select', 'lockdiscussionafter', get_string('lockdiscussionafter', 'forum'), $options);
            $mform->addHelpButton('lockdiscussionafter', 'lockdiscussionafter', 'forum');
            $mform->disabledIf('lockdiscussionafter', 'type', 'eq', 'single');
    
            //-------------------------------------------------------------------------------
            $mform->addElement('header', 'blockafterheader', get_string('blockafter', 'forum'));
            $options = array();
            $options[0] = get_string('blockperioddisabled','forum');
            $options[60*60*24]   = '1 '.get_string('day');
            $options[60*60*24*2] = '2 '.get_string('days');
            $options[60*60*24*3] = '3 '.get_string('days');
            $options[60*60*24*4] = '4 '.get_string('days');
            $options[60*60*24*5] = '5 '.get_string('days');
            $options[60*60*24*6] = '6 '.get_string('days');
            $options[60*60*24*7] = '1 '.get_string('week');
            $mform->addElement('select', 'blockperiod', get_string('blockperiod', 'forum'), $options);
            $mform->addHelpButton('blockperiod', 'blockperiod', 'forum');
    
            $mform->addElement('text', 'blockafter', get_string('blockafter', 'forum'));
            $mform->setType('blockafter', PARAM_INT);
            $mform->setDefault('blockafter', '0');
            $mform->addRule('blockafter', null, 'numeric', null, 'client');
            $mform->addHelpButton('blockafter', 'blockafter', 'forum');
            $mform->disabledIf('blockafter', 'blockperiod', 'eq', 0);
    
            $mform->addElement('text', 'warnafter', get_string('warnafter', 'forum'));
            $mform->setType('warnafter', PARAM_INT);
            $mform->setDefault('warnafter', '0');
            $mform->addRule('warnafter', null, 'numeric', null, 'client');
            $mform->addHelpButton('warnafter', 'warnafter', 'forum');
            $mform->disabledIf('warnafter', 'blockperiod', 'eq', 0);        
        }
        
        $mform->addElement('hidden', 'id', $id);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'form_status');
        $mform->setType('form_status', PARAM_INT);
        $mform->setDefault('form_status',  $form_status);
        $mform->disable_form_change_checker();
    }

    function definition_after_data() {
        parent::definition_after_data();
        $form_status = $this->_customdata['form_status'];
        if ($form_status == 0) {
            $mform     =& $this->_form;
            $type      =& $mform->getElement('type');
            $typevalue = $mform->getElementValue('type');
            
            //we don't want to have these appear as possible selections in the form but
            //we want the form to display them if they are set.
            if ($typevalue[0]=='news') {
                $type->addOption(get_string('namenews', 'forum'), 'news');
                $mform->addHelpButton('type', 'namenews', 'forum');
                $type->freeze();
                $type->setPersistantFreeze(true);
            }
            if ($typevalue[0]=='social') {
                $type->addOption(get_string('namesocial', 'forum'), 'social');
                $type->freeze();
                $type->setPersistantFreeze(true);
            }
        }
        
    }

    function data_preprocessing(&$default_values) {
        //parent::data_preprocessing($default_values);
        $context = context_system::instance();
        if ($default_values['id']) {
            // setting for intro field
            $draftitemid = file_get_submitted_draft_itemid('intro');
            $default_values['introeditor']['text'] = file_prepare_draft_area($draftitemid, $context->id, 'local_forum', 'intro', false,
                                    $editoroptions, $default_values['intro']);

            $default_values['introeditor']['format'] = $default_values['introformat'];
            $default_values['introeditor']['itemid'] = $draftitemid;
            
        } else {
            // adding a new forum instance
            $draftitemid = file_get_submitted_draft_itemid('introeditor');
            // no context yet, itemid not used
            file_prepare_draft_area($draftitemid, null, 'local_forum', 'intro', false);
            $default_values['introeditor']['text'] = '';
            $default_values['introeditor']['format'] = 1;
            $default_values['introeditor']['itemid'] = $draftitemid;
        }
    }

    /**
     * Allows module to modify the data returned by form get_data().
     * This method is also called in the bulk activity completion form.
     * @param stdClass $data the form data to be modified.
     */
    public function data_postprocessing($data) {
       if (isset($data->introeditor)) {
            $data->introformat = $data->introeditor['format'];
            $data->intro = $data->introeditor['text'];
        } 
    }
}

