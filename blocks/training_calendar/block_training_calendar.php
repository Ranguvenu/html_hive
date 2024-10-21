<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Training calendar block.
 *
 * @package    block_newblock
 * @copyright  2018 onwards eabyas info solutions 
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * This block needs to be reworked.
 * The new roles system does away with the concepts of rigid student and
 * teacher roles.
 */
 
global $CFG,$PAGE, $USER;
require_once("{$CFG->libdir}/formslib.php");

class block_training_calendar extends block_base {
    public function init() {
        $this->title = get_string('pluginname','block_training_calendar');
    }
	public function get_content() {
        if ($this->content !== null) {
            return $this->content;
        }        
        global $CFG, $USER, $COURSE, $PAGE, $DB;
        $PAGE->requires->css('/blocks/training_calendar/css/fullcalendar.min.css');
        $PAGE->requires->css('/blocks/training_calendar/css/fullcalendar.print.min.css');
		$this->get_required_javascript();
        $this->content = new stdClass;
        $this->content->text ='';

			$this->content->text .= "<div id='calendar'></div>
			<div id='eventContent' style='display:none;' onmouseout='close();'>
			<p id='eventInfo'></p></div>
			<div id='pop_desc'></div>
			";          
        return $this->content;
    }
	public function get_required_javascript() {
		$this->page->requires->js('/blocks/my_event_calendar/js/jquery1.12.js');
		$this->page->requires->js('/blocks/training_calendar/js/moment.min.js', true);
		$this->page->requires->js('/blocks/training_calendar/js/jquery.min.js', true);
		$this->page->requires->jquery();
		$this->page->requires->jquery('ui', true);
		$this->page->requires->js('/blocks/training_calendar/js/fullcalendar.js', true);
		$this->page->requires->js('/blocks/training_calendar/js/custom.js');   
	}
}



