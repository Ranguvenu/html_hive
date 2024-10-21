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
 * The survey block
 *
 * @package    block
 * @subpackage  survey
 * @copyright 2017 Shivani M <shivani@eabyas.in>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class block_survey extends block_base {
	public function init() {
		$this->title = get_string('pluginname', 'block_survey');
	}

	public function get_content() {
		if ($this->content !== null) {
			return $this->content;
		}
		global $CFG, $PAGE;
		$this->content = new stdClass;
        if(is_siteadmin()){
            $this->content->text = '';
        }else{
            $renderer = $PAGE->get_renderer('block_survey');
            $this->content->text = $renderer->survey_view();
            //$this->content->text = 'Survey Block';
        }

		return $this->content;
	}

	public function get_required_javascript() {
		$this->page->requires->jquery();
		$this->page->requires->jquery_plugin('ui');
		$this->page->requires->jquery_plugin('ui-css');
		//$this->page->requires->js('/blocks/userdashboard/js/custom.js', true);
		$this->page->requires->js('/blocks/survey/js/jquery.dataTables.min.js',true);//*This js and css files for data grid of batches*//
        $this->page->requires->css('/blocks/survey/css/jquery.dataTables.css');
		
	}

}
