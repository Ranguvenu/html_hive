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

namespace local_learningdashboard\persistent;

use core\persistent;

/**
 * Class learningdashboard
 *
 * @package    local_learningdashboard
 * @copyright  2024 YOUR NAME <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class learningdashboard extends persistent {
    /** Database table pokcertificate. */
    public const TABLE = 'local_learningdashboard_master';

    /**
     * Return the definition of the properties of this model.
     *
     * @return array
     */
    protected static function define_properties(): array {

        return [
            'id' => [
                'type' => PARAM_INT,
                'optional' => false,
            ],
            'creditstype' => [
                'type' => PARAM_TEXT,
                'optional' => false,
            ],
            'months' => [
                'type' => PARAM_INT,
                'optional' => false,
            ],
            'credits' => [
                'type' => PARAM_INT,
                'optional' => false,
            ],
            'usercreated' => [
                'type' => PARAM_INT,
                'optional' => false,
            ],
            'usermodified' => [
                'type' => PARAM_INT,
                'optional' => false,
            ],
            'timecreated' => [
                'type' => PARAM_INT,
                'optional' => false,
            ],
            'timemodified' => [
                'type' => PARAM_INT,
                'optional' => false,
            ],
        ];
    }
}
