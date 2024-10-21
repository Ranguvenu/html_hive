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
use core_component;
class block_user_bookmarks_renderer extends plugin_renderer_base {

    public function get_usersbookmarks() {
      
        $options  = array('targetID' => 'mybookmarked_courses','perPage' => 3, 'cardClass' => 'pl-0 pr-4 col-md-4 col-sm-6', 'viewType' => 'card');
       
        $dataoptions['search_query'] = '' ;
        $options['methodName']='block_user_bookmarks_getcontent';
        $options['templateName']='block_user_bookmarks/user_bookmarks';

        $carddataoptions = json_encode($dataoptions);
        $cardoptions = json_encode($options);
        $filterdata                 = json_encode(array());
        $context = array(
            'targetID' => 'mybookmarked_courses',
            'options' => $cardoptions,
            'dataoptions' => $carddataoptions,
            'filterdata' => $filterdata, 
        );   
        return  $context;
/* 
         if($filter){
             return  $context;
         }else{
            return  $this->render_from_template('local_costcenter/cardPaginate', $context);
         } */
    }
    
}