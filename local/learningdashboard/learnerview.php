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
 * TODO describe file learnerview
 *
 * @package    local_learningdashboard
 * @copyright  2024 YOUR NAME <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require('../../config.php');

require_login();

$url = new moodle_url('/local/learningdashboard/learnerview.php');
$PAGE->set_url($url);
$PAGE->set_context(context_system::instance());
$PAGE->set_heading(get_string('learninggoalstatus', 'local_learningdashboard'));
$PAGE->requires->jquery();

$PAGE->requires->js('/local/learningdashboard/js/highcharts.js', true);

$sample_data = json_encode([
    "data" => [
        "Completed" => 555,
        "Pending" => 155
    ],
    'completion_percentage' => 55
]);

$PAGE->requires->js_call_amd('local_learningdashboard/graph', 'init', [$sample_data]);

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_learningdashboard/graph', []);
echo $OUTPUT->footer();
