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
 * TODO describe file index
 *
 * @package    local_learningdashboard
 * @copyright  2024 YOUR NAME <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');

use \local_learningdashboard\output\learningdashboard as learningdashboard;
use local_learningdashboard\api;

require_login();
global $PAGE;
$PAGE->requires->js_call_amd('local_learningdashboard/learningdashboard', 'creditsdata', array());
$PAGE->requires->js_call_amd('local_learningdashboard/learningdashboard', 'load', array());
$PAGE->requires->js_call_amd('local_learningdashboard/graph', 'init', [$sample_data]);
$PAGE->requires->js('/local/learningdashboard/js/highcharts.js', true);


$url = new moodle_url('/local/learningdashboard/index.php', []);
$PAGE->set_url($url);
$PAGE->set_context(context_system::instance());

$PAGE->set_heading(get_string('learninggoalstatus', 'local_learningdashboard'));
echo $OUTPUT->header();
$data['learnerview'] = true;
$supervisor = api::issupervisor();
if ($supervisor) {
    $view = 'manager';
} else {
    $view = 'learner';
}
$data['downloadurl'] = new moodle_url('/local/learningdashboard/csv_export.php', ['view' => $view]);
$data['learningsummaryurl'] = new moodle_url('/local/learningsummary/index.php');
echo $dashbaord = (new learningdashboard())->display_dashboard($data);
echo $OUTPUT->render_from_template('local_learningdashboard/graph', []);
echo $OUTPUT->footer();
