<?php
ini_set('memory_limit', '-1');
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
 * List the tool provided in a course
 *
 * @package    local
 * @subpackage  users
 * @copyright  2016 manikanta <manikantam@eabyas.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// define('NO_MOODLE_COOKIES', true);
//define('NO_MOODLE_COOKIES', true);
// define('AJAX_SCRIPT', true);
// global $DB, $CFG, $USER,$PAGE;
require_once(dirname(__FILE__) . '/../../config.php');
$PAGE->requires->jQuery();
$PAGE->requires->jQuery();
$PAGE->requires->jquery_plugin('ui');
$PAGE->requires->jquery_plugin('ui-css');
$PAGE->requires->js('/blocks/empcredits/js/jquery.dataTables.js',true);
$action = required_param('action', PARAM_TEXT);
$systemcontext = context_system::instance();
$PAGE->set_context($systemcontext);
$renderer = $PAGE->get_renderer('block_empcredits');
$requestDatacount=array();
//var_dump($info);
switch ($action) { 
    case 'lastoneyearccdata':
        $output = $renderer->get_courses_view(true);
        echo $output;
    exit;
    break;
    case 'allccdata':
        $output = $renderer->get_courses_view(false);
        echo $output;
    exit;
    break;
    case 'certdata':
        $output = $renderer->get_certificates_view(false);
        echo $output;
    exit;
    break;
}