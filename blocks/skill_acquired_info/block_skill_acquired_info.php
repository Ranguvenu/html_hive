<?php

//
//
// This software is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This Moodle block is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 *
 * @since 2.0
 * @package blocks
 * @copyright 2012 Georg Maißer und David Bogner http://www.edulabs.org
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 *
 * Used to produce  Manage block
 *
 * @package blocks
 * @copyright 2016 Anilkumar <anil@eabyas.in>
* @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/
class block_skill_acquired_info extends block_base {
	/**
	* block initializations
	*/
        
    public function init() {
      $this->title = get_string('pluginname', 'block_skill_acquired_info');
    }
    function get_required_javascript() {
	  global $PAGE;
	  $this->page->requires->jquery();
	  $PAGE->requires->js('/blocks/learning_plan/js/jquery.dataTables.js',true);
	  $PAGE->requires->css('/blocks/learning_plan/css/jquery.dataTables.css');
	  $this->page->requires->js('/blocks/skill_acquired_info/js/script.js');
    }
	
    public function get_content() {
      global $DB;
      if ($this->content !== null) {
        return $this->content;
      }
        global $CFG, $USER, $PAGE;
        $this->content = new stdClass();
		require_once($CFG->dirroot.'/blocks/skill_acquired_info/renderer.php');
		$renderer = $PAGE->get_renderer('block_skill_acquired_info');
		
		$blockdata = $renderer->display_skill_acquired_info($USER->id);
        
		if(is_siteadmin()){
		  $this->content->text = '';
		}else{
		  $this->content->text = $blockdata;
		}
    
        $this->content->footer = '';
        return $this->content;
    }

}
