<?php
/**
 * This file is part of eAbyas
 *
 * Copyright eAbyas Info Solutons Pvt Ltd, India
 *
 * This courselister is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This courselister is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this courselister.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Course lister block.
 *
 * @author eabyas  <info@eabyas.in>

 * @package Bizlms
 * @subpackage block_courselister
 */

use block_courselister\plugin;

defined('MOODLE_INTERNAL') || die();

/**
 * Class block_courselister_edit_form
 *
 * @author eabyas  <info@eabyas.in>

 * @package Bizlms
 * @subpackage block_courselister
 */
class block_courselister_edit_form extends block_edit_form {

    /**
     * Returns list of available course categories
     * @return array<string, string>
     * @throws coding_exception
     */
    protected function coursecatoptions() {
        global $CFG;
        require_once($CFG->dirroot.'/course/lib.php');
        $categories = [0 => get_string('allcategories')];
        $choices = make_categories_options();
        foreach ($choices as $id => $category) {
            $categories[$id] = html_entity_decode($category, null, 'UTF-8');
        }
        return $categories;
    }

    protected function courselist() {
        global $CFG,$DB;
        $clist = [0 => get_string('allcourses', 'block_courselister')];
        $courses = $DB->get_records_menu('course', null, '', 'id, fullname');

        foreach ($courses as $id => $course) {

             $clist[$id] = html_entity_decode($course, null, 'UTF-8');
         }
      
        return $clist;
    }

    /**
     * Course type list of options
     * @return string[]
     * @throws coding_exception
     * @throws ddl_exception
     */
    protected function coursetypeoptions() {
        $result = [
            0 => get_string('none'),
            plugin::SELECTEDCOURSES => get_string('selectedcourse', 'block_courselister'),
        ];


        return $result;
    }

}
