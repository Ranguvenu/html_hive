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


defined('MOODLE_INTERNAL') || die;
use local_udemysync\plugin;

global $DB;

if (!isset($hassiteconfig)) {
    $hassiteconfig = has_capability('moodle/site:config', context_system::instance());
}
if ($hassiteconfig) {
    $ADMIN->add('courses', new admin_externalpage('local_eabyas_udemysync',
        get_string('pluginname',plugin::COMPONENT),
        new moodle_url('/local/udemysync/configsettings.php')));

}
