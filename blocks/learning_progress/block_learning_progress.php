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
 * @subpackage block_learning_progress
 */

class block_learning_progress extends block_base {
	public function init() {
		$this->title = get_string('learning_progress', 'block_learning_progress');
	}

	function hide_header() {
		return true;
	}

	function instance_allow_multiple() {
		return false;
	}

	public function get_content() {
		if ($this->content !== null) {
			return $this->content;
		}
         $systemcontext = context_system::instance();
		 $this->content = new stdClass();

        if(!is_siteadmin() || !has_capability('local/costcenter:manage_ownorganization',$systemcontext) || !has_capability('local/costcenter:manage_owndepartments',$systemcontext)){
			$renderer = $this->page->get_renderer('block_learning_progress');
		    $this->content->text = '<div class="">'.$renderer->learning_progress_track_view_learningplan().'</div>';
		    return $this->content;
		}
	 
	}
 
}
