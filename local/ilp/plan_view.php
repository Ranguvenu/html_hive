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
 * @subpackage local_ilp
 */

use local_ilp\lib\lib;
use local_ilp\render\view;
require_once(dirname(__FILE__) . '/../../config.php');

global $DB, $USER, $CFG,$PAGE,$OUTPUT;
require_once($CFG->dirroot . '/local/ilp/lib.php');

$id = optional_param('id', 0, PARAM_INT);
$curr_tab = optional_param('tab', 'courses', PARAM_TEXT);
$condition = optional_param('condtion','manage', PARAM_TEXT);

require_login();

if(!$plan_record = get_ilp_by_id($id)){
    print_error('invalidILP');
}
$pageurl = new moodle_url('/local/ilp/plan_view.php', ['id'=>$id]);
$context = context_system::instance();
$pluginname = get_string('pluginname', 'local_ilp');

$PAGE->set_url($pageurl);
$PAGE->set_title($pluginname);
$PAGE->set_heading($plan_record->name);

$PAGE->requires->jquery();
$PAGE->requires->js_call_amd('local_ilp/lpcreate', 'load', array());
$PAGE->requires->js_call_amd('local_ilp/courseenrol', 'tabsFunction', array('id' => $id));
$PAGE->requires->js_call_amd('local_ilp/courseenrol', 'load', array());

//Header and the navigation bar
$PAGE->navbar->ignore_active();
$PAGE->navbar->add($pluginname, new moodle_url('/local/ilp/index.php'));

$ilp_renderer = new view();
$ilpcreateor = $DB->record_exists('local_ilp', array('id' => $id, 'usercreated' => $USER->id));
if(!$ilpcreateor){
	redirect($CFG->wwwroot.'/my');
}
echo $OUTPUT->header();
echo $ilp_renderer->get_editand_publish_icons($id);

$ilp_assigned = $DB->record_exists('local_ilp_user', array('planid' => $id, 'userid' => $USER->id));

/*view of the single plan information in the plan view*/
echo $ilp_renderer->single_plan_view($id);

echo $ilp_renderer->plan_tabview($id, $curr_tab,$condition);


echo $OUTPUT->footer();