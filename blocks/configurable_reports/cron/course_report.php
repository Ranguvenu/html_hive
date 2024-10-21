<?php

echo "Good Point";

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
 * Configurable Reports
 * A Moodle block for creating Configurable Reports
 * @package blocks
 * @author: Juan leyva <http://www.twitter.com/jleyvadelgado>
 * @date: 2009
 */

ini_set("memory_limit", "-1");
 ini_set('max_execution_time', "-1");
//define('CLI_SCRIPT', true);
use \block_configurable_reports\Spout\Writer\WriterFactory;
use \block_configurable_reports\Spout\Common\Type;
require_once("../../../config.php");
require_once($CFG->dirroot . "/blocks/configurable_reports/locallib.php");

if (!$report = $DB->get_record('block_configurable_reports', array('type' => 'course_completion')))
    print_error('reportdoesnotexists', 'configurable_reports');

$components = cr_unserialize($report->components);
$columns = (isset($components['columns']['elements'])) ? $components['columns']['elements'] : array();
$context = context_system::instance();
require_once($CFG->dirroot . '/blocks/configurable_reports/report.class.php');
require_once($CFG->dirroot . '/blocks/configurable_reports/reports/' . $report->type . '/report.class.php');
$reportclassname = 'report_' . $report->type;
$reportclass = new $reportclassname($report);


$writer = WriterFactory::create(Type::CSV); 
$filename = 'completion_report.csv';
unlink($CFG->dirroot.'/blocks/configurable_reports/files/'.$filename);   
$writer->openToFile($CFG->dirroot.'/blocks/configurable_reports/files/'.$filename);
$head = array();

$tablehead=array();
$pluginscache = array();

if (!empty($columns)) {
    foreach ($columns as $c) {
        require_once($CFG->dirroot . '/blocks/configurable_reports/components/columns/' . $c['pluginname'] . '/plugin.class.php');
        $classname = 'plugin_' . $c['pluginname'];
        if (!isset($pluginscache[$classname])) {
            $class_cl = new $classname($report, $c);
            $pluginscache[$classname] = $class_cl;
        } else {
            $class_cl = $pluginscache[$classname];
        }
            $tablehead[] = $c['formdata']->column;
             $head[]=str_replace("\n", ' ', htmlspecialchars_decode(strip_tags(nl2br($c['formdata']->columname))));
    }
    $writer->addRow($head);
}

$data= array();
$cron=$USER->id;
$finalelements = array(1);
$sqlorder = '';
$filters_empty='';
$resultdata = $reportclass->get_rows($finalelements, $sqlorder, true,$filters_empty,0,100,'','');
$rows = $resultdata['data'];
print_object($rows);
foreach ($rows as $key => $value) {
    $result=array_intersect_key((array)$value, 
                    array_flip($tablehead));
    $customerSorted = array_values(array_replace(array_flip($tablehead),$result));
    $data[] = array_map(function ($v) {
            return trim(strip_tags($v));
        }, $customerSorted);
}
$writer->addRows($data); // add a row at a time
$writer->close();
