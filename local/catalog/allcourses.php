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
 * @subpackage local_catalog
 */

require_once(dirname(__FILE__) . '/../../config.php');
global $DB, $USER, $CFG, $PAGE, $OUTPUT;
require_once($CFG->dirroot . '/local/catalog/renderer.php');
//require_once($CFG->dirroot . '/local/includes.php');

$systemcontext = context_system::instance();
$PAGE->set_context($systemcontext);
require_login();
if(!has_capability('local/catalog:viewcatalog', $systemcontext)){
    print_error('No permissions');
}

$PAGE->set_url('/local/catalog/allcourses.php');
$PAGE->set_title(get_string('e_learning_courses','local_catalog'));
$PAGE->set_heading(get_string('leftmenu_catalog', 'local_catalog'));
$PAGE->set_pagelayout('context_image');
$PAGE->navbar->add(get_string('e_learning_courses','local_catalog'));

$category = optional_param('category', -1, PARAM_INT);
$type = optional_param('type', 0, PARAM_INT);
$global_search = optional_param('g_search', 0, PARAM_RAW);
$tab = optional_param('tab', null, PARAM_TEXT);

$PAGE->requires->jquery();

$PAGE->requires->js('/local/catalog/js/angular.min.js');
$PAGE->requires->js('/local/catalog/js/custom.js');
$PAGE->requires->js('/local/catalog/js/dirPagination.js');

/*$ratings_exist = \core_component::get_plugin_directory('local', 'ratings');
if($ratings_exist){
    $PAGE->requires->js('/local/ratings/js/jquery.rateyo.js');
    $PAGE->requires->js('/local/ratings/js/ratings.js');
}*/

$PAGE->requires->js_call_amd('local_classroom/classroom','load', array());
$PAGE->requires->js_call_amd('local_program/program','load', array());
$PAGE->requires->js_call_amd('local_certification/certification','load', array());
$PAGE->requires->js_call_amd('local_learningplan/courseenrol','load');
$PAGE->requires->js_call_amd('local_catalog/courseinfo', 'load', array());
$PAGE->requires->js_call_amd('local_request/requestconfirm', 'load', array());
$renderer = $PAGE->get_renderer('local_catalog');

//$includes = new user_course_details();

use local_catalog\output\cataloglib;
define('ELE',1);
define('ILT',2);
define('LP',3);
define('PROGRAM',6);
define('CERTIFICATION',4);
define('LEARNINGPATH',5);
define('PERPAGE',8);

echo $OUTPUT->header();

$return = array();
$return["loader"] = $CFG->wwwroot.'/local/ajax-loader.svg';

$activetab = cataloglib::get_activetab($tab);

$res= cataloglib::check_catalogpluginexists_ornot();
$return['elearningexists']=$res['elearningexists'];
$return['classroomexists']=$res['classroomexists'];
$return['lpexists'] = $res['lpexists'];
$return['programexists']=$res['programexists'];
$return['certificateexists']=$res['certificateexists'];
$return['classroomimg'] = $OUTPUT->image_url('cricon_blue', 'theme_epsilon');

// echo $renderer->render_from_template('local_catalog/catalog_main', $return);

echo "<div ng-app = 'catalog' >
    <div ng-controller = 'courseController'>".
        // {{> local_catalog/catalog_tab }}
        $OUTPUT->render_from_template('local_catalog/catalog_tab', $return)
   ."
        <div class=list box text-shadow>
            <div id=demo class='box jplist'>
                <div   class=list ng-init='init(".$activetab.")'>".
                    // {{> local_catalog/catalog_selectbox }}
                    $OUTPUT->render_from_template('local_catalog/catalog_selectbox', $return)
."
                    <div class='w-100 pull-left course_view_list_container'>
                        <div class='col-12 pull-left pl-15'>
                            <div ng-show='showLoader' class='loader_container'>
                                <img src= ".$return['loader']." />
                            </div>
                    
                            <div ng-if=\"numberofrecords > 0\">
                    
                                <div dir-paginate='record in courseinfo | itemsPerPage: 8' total-items=numberofrecords class='list-item col-xl-3 col-lg-4 col-sm-6 col-12 pull-left course_view_list' >
                                    <div ng-if=\"record.id >=1\">
                                        <div class='course-body'>
                                            <div ng-if=\" tab == 1\">".
                                               
                                               // {{> local_catalog/elearning }}
                                               $OUTPUT->render_from_template('local_catalog/elearning', $return)
                                               ."
                                            </div>

                                            <div ng-if=\" tab == 2\">
                                                <div class=\"w-full pull-left cr_courses\">".
                                                // {{> local_catalog/classroom }}
                                                $OUTPUT->render_from_template('local_catalog/iltcourses', $return)
                                                ."</div>
                                            </div>

                                            <div ng-if=\" tab == 3\">
                                                <div class=\"w-full pull-left cr_courses\">".
                                                // {{> local_catalog/program }}
                                                $OUTPUT->render_from_template('local_catalog/program', $return)
                                                ."</div>
                                            </div>

                                            <div ng-if=\" tab == 4\">
                                                <div class=\"w-full pull-left cr_courses\">".
                                                // {{> local_catalog/certification }}
                                                $OUTPUT->render_from_template('local_catalog/certification', $return)
                                                ."</div>
                                            </div>

                                            <div ng-if=\" tab == 5\">
                                                <div class=\"w-full pull-left cr_courses\">".
                                                    // {{> local_catalog/learningplan }}
                                                    $OUTPUT->render_from_template('local_catalog/learningplan', $return)
                                                ."</div>
                                            </div>

                                            <div ng-if=\" tab == 6\">
                                                <div ng-if='record.type == 1'>".
                                                    // {{> local_catalog/elearning }}  
                                                    $OUTPUT->render_from_template('local_catalog/elearning', $return)
                                                ."</div>
                                                <div ng-if='record.type == 2'>
                                                    <div class=\"w-full pull-left cr_courses\">".
                                                    // {{> local_catalog/classroom }}
                                                    $OUTPUT->render_from_template('local_catalog/classroom', $return)
                                                    ."</div>
                                                </div>

                                                <div ng-if='record.type == 6'>
                                                    <div class=\"w-full pull-left cr_courses\">".
                                                    // {{> local_catalog/program }}
                                                    $OUTPUT->render_from_template('local_catalog/program', $return)
                                                    ."</div>
                                                </div>  

                                                <div ng-if='record.type == 4'>
                                                    <div class=\"w-full pull-left cr_courses\">".
                                                     // {{> local_catalog/certification }}
                                                    $OUTPUT->render_from_template('local_catalog/certification', $return)
                                                    ."</div>
                                                </div>

                                                <div ng-if='record.type == 5'>
                                                    <div class=\"w-full pull-left cr_courses\">".
                                                    // {{> local_catalog/learningplan }}
                                                    $OUTPUT->render_from_template('local_catalog/learningplan', $return)
                                                    ."</div>
                                                </div>
                                            </div>
                                            
                                            <div ng-if=\" tab == 8\">
                                            <div class=\"w-full pull-left cr_courses\">".
                                                // Mooc Courses tab
                                                $OUTPUT->render_from_template('local_catalog/elearning', $return)
                                            ."</div>
                                            </div>

                                        </div>
                                        <div class='section-shadow'></div>
                                    </div>
                                    </div>
                                    
                                </div>
                            </div>
                            
                        </div>".
                            // {{> local_catalog/recordnotfound }}
                            $OUTPUT->render_from_template('local_catalog/recordnotfound', $return)
                    ."</div>
                </div>
            </div>

    <div ng-if=\"numberofrecords > 0\" class='row'>
        <div class='col-12'>
            <dir-pagination-controls class='d-flex align-items-center justify-content-center' boundary-links='true' on-page-change='pageChangeHandler(newPageNumber, tab)' template-url='dirPagination.tpl.html'>
            </dir-pagination-controls>
        </div>
    </div>

     </div>
    </div>
    
    </div>
</div>";

echo $OUTPUT->footer();
