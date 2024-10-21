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


require_once(dirname(__FILE__) . '/../../config.php');

global $DB, $USER, $CFG,$PAGE,$OUTPUT;
require_once($CFG->dirroot . '/local/ilp/lib.php');

require_login();

use \local_ilp\render;

$pageurl = new moodle_url('/local/ilp/index.php', []);
$context = context_system::instance();
$pluginname = get_string('pluginname', 'local_ilp');
$PAGE->requires->jquery();
$PAGE->requires->jquery_plugin('ui');
$PAGE->requires->js('/local/ilp/js/jquery.dataTables.min.js',true);
$PAGE->requires->css('/local/ilp/css/jquery.dataTables.css');


$PAGE->requires->js_call_amd('theme_epsilon/quickactions', 'quickactionsCall');
$PAGE->requires->js_call_amd('local_ilp/lpcreate', 'load', array());

$return_url = new moodle_url('/local/ilp/manageilp.php');

$PAGE->set_url($pageurl);
$PAGE->set_title($pluginname);
$PAGE->set_context($context);
// $PAGE->set_heading(get_string('mytrainingplans', 'local_ilp'));

// $PAGE->navbar->add(get_string('pluginname', 'local_ilp'), new moodle_url('/local/ilp/index.php'));
// $PAGE->navbar->add(get_string('add_ilps', 'local_ilp'));


$ilp_renderer = new local_ilp\render\view();

echo $OUTPUT->header();

// if(!is_user()) { //Even for admin, this page is not allowed ;)
//     print_error('nopermissions');
// }

$out = "<ul class='course_extended_menu_list ilp'>";
//An authenticated user will create their own Learning plan
$titlestring = get_string('addnew_ilps','local_ilp');
$out .= "<li>    
            <div class = 'coursebackup course_extended_menu_itemcontainer'>
                <a class='course_extended_menu_itemlink' data-action='createlpmodal' title='$titlestring' onclick ='(function(e){ require(\"local_ilp/lpcreate\").init({selector:\"createlpmodal\", contextid:$context->id, planid:0, form_status:0}) })(event)'><span class='createicon'><span class='custom_ilp_icon icon'><i class='fa fa-plus createiconchild' aria-hidden='true'></i></span></span>
                </a>
            </div>
        </li>";
$out .= "</ul>";
echo $out;

$condition="";

echo $ilp_renderer->ilpBannerContent();
// echo $ilp_renderer->all_ilps($condition,$normal=array(),false);

$plan = new local_ilp\render\plans();
echo $plan->actionplans_view();

echo $OUTPUT->footer();
