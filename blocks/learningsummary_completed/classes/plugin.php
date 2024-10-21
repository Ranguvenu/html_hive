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
 * Suggested Courses list block plugin helper
 *
 * @author eabyas  <info@eabyas.in>
 * @package Bizlms
 * @subpackage block_learningsummary_completed
 */

namespace block_learningsummary_completed;

defined('MOODLE_INTERNAL') || die;

/**
 * Class plugin
 * @author eabyas  <info@eabyas.in>
 * @package Bizlms
 * @subpackage block_learningsummary_completed
 */
abstract class plugin
{
    /** @var string */
    const COMPONENT = 'block_learningsummary_completed';

    /** @var int */
    const COMPLETEDCOURSES = 1;

    public static function get_completed_content($stable, $filtervalues, $data_object)
    {
        global $CFG;

        $coursetype = $data_object->coursetype;
        require_once($CFG->dirroot . '/local/courses/lib.php');
        $completeddata = get_learningsummary_content($coursetype, $filtervalues, $data_object, $stable);
       
        return $completeddata;
    }
}
