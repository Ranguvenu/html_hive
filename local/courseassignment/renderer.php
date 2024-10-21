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
 * @subpackage local_courses
 */


defined('MOODLE_INTERNAL') || die;

class local_courseassignment_renderer extends plugin_renderer_base {
  

    /**
     * Display the avialable courses
     *
     * @return string The text to render
     */
    public function get_courses($filter = false) {
        $systemcontext = context_system::instance();
        $id = optional_param('id', 0, PARAM_INT);
        $options = array('targetID' => 'courses_assignments','perPage' => 10, 'cardClass' => 'tableformat', 'viewType' => 'table');
        $options['methodName']='local_courses_assignments';
        $options['templateName']='local_courseassignment/assignmentreport';
        $options['courseid'] = $id;
        $options = json_encode($options);

        $filterdata = json_encode(array());
        $dataoptions = json_encode(array('contextid' => $systemcontext->id));
        $context = [
                'targetID' => 'courses_assignments',
                'options' => $options,
                'dataoptions' => $dataoptions,
                'filterdata' => $filterdata
        ];
        if($filter){
            return  $context;
        }else{
            return  $this->render_from_template('local_costcenter/cardPaginate', $context);
        }
    }


    public function get_sme_courses($filter = false) {
        $systemcontext = context_system::instance();
        $options = array('targetID' => 'manage_smecourses','perPage' => 10, 'cardClass' => 'col-md-6 col-12', 'viewType' => 'card');
        $options['methodName']='local_smecourses_view';
        $options['templateName']='local_courses/catalog';
        $options = json_encode($options);
        $filterdata = json_encode(array());
        $dataoptions = json_encode(array('contextid' => $systemcontext->id));
        $context = [
                'targetID' => 'manage_smecourses',
                'options' => $options,
                'dataoptions' => $dataoptions,
                'filterdata' => $filterdata
        ];
        if($filter){
            return  $context;
        }else{
            return  $this->render_from_template('local_costcenter/cardPaginate', $context);
        }
    }

      /**
     * Display the avialable courses
     *
     * @return string The text to render
     */
    public function get_courseassignments($filter = false) {
        $systemcontext = context_system::instance();
        $id = optional_param('id', 0, PARAM_INT);
        $options = array('targetID' => 'courses_assignments','perPage' => 10, 'cardClass' => 'tableformat', 'viewType' => 'table');
        $options['methodName']='local_courses_assignmentslist';
        $options['templateName']='local_courseassignment/courseassignmentreport';
        $options['courseid'] = $id;
        $options = json_encode($options);

        $filterdata = json_encode(array());
        $dataoptions = json_encode(array('contextid' => $systemcontext->id));
        $context = [
                'targetID' => 'courses_assignments',
                'options' => $options,
                'dataoptions' => $dataoptions,
                'filterdata' => $filterdata
        ];
        if($filter){
            return  $context;
        }else{
            return  $this->render_from_template('local_costcenter/cardPaginate', $context);
        }
    }
}