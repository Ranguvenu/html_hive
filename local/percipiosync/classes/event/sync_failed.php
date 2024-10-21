<?php
/**
 * This file is part of eAbyas
 *
 * Copyright eAbyas Info Solutons Pvt Ltd, India
 *
 * This percipiosync is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This percipiosync is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this percipiosync.  If not, see <http://www.gnu.org/licenses/>.

/**
 * percipiosync local settings
 * @author eabyas  <info@eabyas.in>
 * @package    eabyas
 * @subpackage local_percipiosync
 */

namespace local_percipiosync\event;

use local_percipiosync\plugin;

defined('MOODLE_INTERNAL') || die();

class sync_failed extends \core\event\base {

    /**
     *
     * {@inheritDoc}
     * @see \core\event\base::init()
     */
    protected function init() {
        $this->context = \context_system::instance();
        $this->data['crud'] = 'u'; // c(reate), r(ead), u(pdate), d(elete)
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * {@inheritDoc}
     * @see \core\event\base::get_name()
     */
    public static function get_name() {
        return get_string('eventsyncfailed',plugin::COMPONENT);
    }

    /**
     *
     * {@inheritDoc}
     * @see \core\event\base::get_description()
     */
    public function get_description() {
        return get_string('eventsyncfaileddesc',plugin::COMPONENT, $this->data['other']['errormsg']);
    }
}