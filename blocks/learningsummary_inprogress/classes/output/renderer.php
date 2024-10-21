<?php
/**
 * This file is part of eAbyas
 *
 * Copyright eAbyas Info Solutons Pvt Ltd, India
 *
 * This suggested course is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This suggested course is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this learningsummary inprogress course.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Course lister block.
 *
 * @author eabyas  <info@eabyas.in>

 * @copyright 2020 Fortech inc
 * @subpackage block_learningsummary_inprogress
 */

namespace block_learningsummary_inprogress\output;

use plugin_renderer_base;
use moodle_exception;

defined('MOODLE_INTERNAL') || die;

class renderer extends plugin_renderer_base {

    /**
     * Render the blockview
     * @param  blockview $widget
     * @return bool|string
     * @throws moodle_exception
     */
    protected function render_blockview(blockview $widget) {
        $context = $widget->export_for_template($this);
        return $context;
    }

}
