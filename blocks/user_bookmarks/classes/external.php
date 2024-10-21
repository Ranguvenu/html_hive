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

 * Learning Summary completed block caps.
 *
 * @author eabyas  <info@eabyas.in>
 * @package    Bizlms
 * @subpackage block_user_bookmarks
 */

defined('MOODLE_INTERNAL') || die;
require_once("$CFG->libdir/externallib.php");
require_once($CFG->libdir.'/enrollib.php');

class block_user_bookmarks_external extends external_api{
  
    public static function get_bookmark_content_parameters() {
        return new external_function_parameters([
             'options' => new external_value(PARAM_RAW, 'The paging data for the service'),
             'dataoptions' => new external_value(PARAM_RAW, 'The data for the service'),
             'offset' => new external_value(PARAM_INT, 'Number of items to skip from the begging of the result set',
                    VALUE_DEFAULT, 0),
             'limit' => new external_value(PARAM_INT, 'Maximum number of results to return',
                    VALUE_DEFAULT, 0),
             'filterdata' => new external_value(PARAM_RAW, 'The data for the service'),
        ]);
    }


    public static function get_bookmark_content($options, $dataoptions, $offset = 0, $limit = 0, $filterdata) {
        global $PAGE, $DB, $USER, $CFG;
        require_once($CFG->dirroot . '/blocks/user_bookmarks/lib.php');
        $sitecontext = context_system::instance();

        require_login();
        $PAGE->set_url('/my/index.php', array());
        $PAGE->set_context($sitecontext);
        // Parameter validation.
        $params = self::validate_parameters(
            self::get_bookmark_content_parameters(),
            [
                'options' => $options,
                'dataoptions' => $dataoptions,
                'offset' => $offset,
                'limit' => $limit,
                'filterdata' => $filterdata,
            ]
        );
        $data_object = (json_decode($dataoptions));
        $offset = $params['offset'];
        $limit = $params['limit']; 
        $filtervalues = json_decode($filterdata);
       
        $stable = new \stdClass();
        $stable->thead = false;
        $stable->search =$data_object->search_query;
        $stable->start = $offset;
        $stable->length = $limit;             

        $data = array();
        $config = $data_object;
        $data = get_listof_usersbookmarks($stable, $filterdata, $data_object);
        $totalcount = $data['bookmarkcount'];

        return [
            'totalcount' => $totalcount,
            'records' =>$data['bookmarkcourses'],
            'options' => $options,
            'dataoptions' => $dataoptions,
            'filterdata' => $filterdata,
            'nodata' => '',
        ];
    }

    /**
     * Returns description of method result value.
     */
    public static function get_bookmark_content_returns() {

        return new external_single_structure([
            'options' => new external_value(PARAM_RAW, 'The paging data for the service'),
            'dataoptions' => new external_value(PARAM_RAW, 'The data for the service'),
            'totalcount' => new external_value(PARAM_INT, 'total number of bookmarked courses in result set'),
            'filterdata' => new external_value(PARAM_RAW, 'The data for the service'),
            'nodata' => new external_value(PARAM_TEXT, 'The data for the service'),
            'records' => new external_multiple_structure(
                new external_single_structure(
                    array(
                        'courseid' => new external_value(PARAM_INT, 'courseid'),
                        'url' => new external_value(PARAM_RAW,'url',VALUE_OPTIONAL),
                        'title' => new external_value(PARAM_RAW, 'title'),
                        'coursename' =>new external_value(PARAM_RAW, 'coursename'), 
                        'description' => new external_value(PARAM_RAW, 'course description', VALUE_OPTIONAL),
                        'learningtype' => new external_value(PARAM_RAW, 'coursetype', VALUE_OPTIONAL),
                        'imageurl' => new external_value(PARAM_RAW, 'imageurl', VALUE_OPTIONAL),
                    )
                ),'records', VALUE_OPTIONAL
            )
        ]);
    }

}
