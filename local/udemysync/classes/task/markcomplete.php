<?php
/**
 * This file is part of eAbyas
 *
 * Copyright eAbyas Info Solutons Pvt Ltd, India
 *
 * This udemysync is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This udemysync is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this udemysync.  If not, see <http://www.gnu.org/licenses/>.

/**
 * udemysync local settings
 * @author eabyas  <info@eabyas.in>
 * @package    eabyas
 * @subpackage local_udemysync
 */

namespace local_udemysync\task;

use local_udemysync\plugin;


class markcomplete extends \core\task\scheduled_task {

    
    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string
     */
    public function get_name() {
        return get_string('taskname',plugin::COMPONENT);
    }

    /**
     * Execute the task.
     *
     * @param int $testing - are we testing/developing - called from index.php.
    * @param text $crud - are we testing/developing - called from index.php.
     */
    public function execute($testing = null,$crud=null) {
        plugin::markcomplete_udemycourse($testing,$crud);
    }
}