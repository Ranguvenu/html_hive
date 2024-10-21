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
 * TODO describe file managerview
 *
 * @package    local_learningdashboard
 * @copyright  2024 YOUR NAME <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');

require_login();
use \local_learningdashboard\output\learningdashboard as learningdashboard;

$url = new moodle_url('/local/learningdashboard/managerview.php', []);
$PAGE->set_url($url);
$PAGE->set_context(context_system::instance());
$PAGE->requires->js_call_amd('local_learningdashboard/learningdashboard', 'teamsstatus', array());

$PAGE->set_heading(get_string('learningsummary', 'local_learningdashboard'));

echo $OUTPUT->header();
$data['adminview'] = true;
$data['mycompletionstatusurl'] = new moodle_url('/local/learningdashboard/index.php', []);
$data['downloadurl'] = new moodle_url('/local/learningdashboard/csv_export.php', ['view' => 'admin']);
$filterparams = (new learningdashboard())->display_teamcompletionstatus(true);
echo $OUTPUT->render_from_template('local_costcenter/global_filter', $filterparams);
echo $dashbaord = (new learningdashboard())->display_dashboard($data);
echo $dashbaord = (new learningdashboard())->display_teamcompletionstatus();
echo $OUTPUT->footer();
