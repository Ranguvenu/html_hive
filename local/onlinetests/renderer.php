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
 * @subpackage local_onlinetests
 */

class local_onlinetests_renderer extends plugin_renderer_base  {
    /**
     * Display the avialable onlinetests
     *
     * @return string The template to render
     */
    public function get_onlinetests($filter = false) {
        $systemcontext = context_system::instance();
        // change the display according to moodle 3.6
        $stable = new stdClass();
        $stable->thead = true;
        $stable->start = 0;
        $stable->length = -1;
        $stable->search = '';
        $stable->pagetype ='page';

        $options = array('targetID' => 'manage_onlinetests','perPage' => 12, 'cardClass' => 'col-md-4 col-sm-6 col-12', 'viewType' => 'card');
        $options['methodName']='local_onlinetests_tests_view';
        $options['templateName']='local_onlinetests/onlinetests';
        $options = json_encode($options);
        $filterdata = json_encode(array());
        $dataoptions = json_encode(array('contextid' => $systemcontext->id));
        $context = [
            'targetID' => 'manage_onlinetests',
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
     * Renders html to print list of tests tagged with particular tag
     *
     * @param int $tagid id of the tag
     * @param bool $exclusivemode if set to true it means that no other entities tagged with this tag
     *             are displayed on the page and the per-page limit may be bigger
     * @param int $fromctx context id where the link was displayed, may be used by callbacks
     *            to display items in the same context first
     * @param int $ctx context id where to search for records
     * @param bool $rec search in subcontexts as well
     * @param array $displayoptions
     * @return string empty string if no courses are marked with this tag or rendered list of courses
     */
    public function tagged_onlinetests($tagid, $exclusivemode = true, $ctx = 0, $rec = true, $displayoptions = null, $count = 0, $sort='') {
        global $CFG, $DB, $USER;
        $systemcontext = context_system::instance();
        if ($count > 0)
        $sql =" select count(q.id) from {quiz} q JOIN {local_onlinetests} o ON o.quizid = q.id ";
        else
        $sql =" select q.*, o.id as oid from {quiz} q JOIN {local_onlinetests} o ON o.quizid = q.id ";

        $where = " where q.id IN (SELECT t.itemid FROM {tag_instance} t WHERE t.tagid = :tagid AND t.itemtype = :itemtype AND t.component = :component)";
        if (is_siteadmin())
            $where .= " AND 1=1 ";
        elseif (has_capability('local/onlinetests:manage',$systemcontext))
        $where .= department_sql($systemcontext); // get records departmentwise
        else
        $where .= " AND o.id IN (select onlinetestid from {local_onlinetest_users} where userid = $USER->id)";

        $joinsql = $groupby = $orderby = '';
        if (!empty($sort)) {
          switch($sort) {
            case 'highrate':
            if ($DB->get_manager()->table_exists('local_rating')) {
              $joinsql .= " LEFT JOIN {local_rating} as r ON r.itemid = o.id AND r.ratearea = 'local_onlinetests' ";
              $groupby .= " group by o.id ";
              $orderby .= " order by AVG(rating) desc ";
            }        
            break;
            case 'lowrate':  
            if ($DB->get_manager()->table_exists('local_rating')) {  
              $joinsql .= " LEFT JOIN {local_rating} as r ON r.itemid = o.id AND r.ratearea = 'local_onlinetests' ";
              $groupby .= " group by o.id ";
              $orderby .= " order by AVG(rating) asc ";
            }
            break;
            case 'latest':
            $orderby .= " order by o.timemodified desc ";
            break;
            case 'oldest':
            $orderby .= " order by o.timemodified asc ";
            break;
            default:
            $orderby .= " order by o.timemodified desc ";
            break;
            }
        }
    
        $params = array('tagid' => $tagid, 'itemtype' => 'onlinetests', 'component' => 'local_onlinetests');

        if ($count > 0) {
            $records = $DB->count_records_sql($sql.$where, $params);
            return $records;
        } else {
            $records = $DB->get_records_sql($sql.$joinsql.$where.$groupby.$orderby, $params);
        }
        
        $tagfeed = new local_tags\output\tagfeed(array(), 'onlinetests');
        $img = $this->output->pix_icon('i/course', '');
        foreach ($records as $key => $value) {
            $quizid = $DB->get_field_sql('select cm.id from {course_modules} cm JOIN {modules} m where m.name="quiz" AND m.id = cm.module AND cm.instance = ? ', [$value->id]);
          $url = $CFG->wwwroot.'/mod/quiz/view.php?id='.$quizid.'';
          $imgwithlink = html_writer::link($url, $img);
          $modulename = html_writer::link($url, $value->name);
          $testdetails = get_test_details($value->oid); // test id not quizid
          $details = $this->render_from_template('local_onlinetests/tagview', $testdetails);
          $tagfeed->add($imgwithlink, $modulename, $details);
        }

        return $this->output->render_from_template('local_tags/tagfeed', $tagfeed->export_for_template($this->output));

    }
    public function get_userdashboard_onlinetests($tab, $filter = false){
        $systemcontext = context_system::instance();

        $options = array('targetID' => 'dashboard_onlinetests', 'perPage' => 6, 'cardClass' => 'col-md-6 col-12', 'viewType' => 'card');
        $options['methodName'] = 'local_onlinetests_userdashboard_content_paginated';
        $options['templateName'] = 'local_onlinetests/userdashboard_paginated';
        $options['filter'] = $tab;
        $options = json_encode($options);
        $filterdata = json_encode(array());
        $dataoptions = json_encode(array('contextid' => $systemcontext->id));
        $context = [
                'targetID' => 'dashboard_onlinetests',
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

