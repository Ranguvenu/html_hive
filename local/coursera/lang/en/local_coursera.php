<?php
/**
 * This file is part of eAbyas
 *
 * Copyright eAbyas Info Solutons Pvt Ltd, India
 *
 * This coursera is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This coursera is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this coursera.  If not, see <http://www.gnu.org/licenses/>.

/**
 * coursera local settings
 * @author eabyas  <info@eabyas.in>
 * @package    eabyas
 * @subpackage local_coursera
 */

$string['pluginname'] = 'Coursera Course Sync';
$string['cfgformheading'] = 'Global Settings';
$string['enabled'] = 'Enable Plugin';
$string['enabled_help'] = 'Click to enable/disable this plugin';
$string['configerror'] = 'Coursera Catalog Sync plugin in not enabled OR has not been configured fully.';
$string['apiurl'] = 'Catalog API URL';
$string['apiurl_help'] = 'The URL to Coursera API';
$string['catlanguage'] = 'Catalog Language';
$string['catlanguage_help'] = 'Select Catalog language to use.';
$string['clientid'] = 'API Client ID';
$string['clientid_help'] = 'Enter a API Client ID - default is hostname';
$string['ccategoriesoption'] = " -- Select A Category -- ";
$string['ccategories'] = "Category for sync'd courses";
$string['ccategories_help'] = "The course category where the sync'd courses are to be managed - defaults to 'Miscellaneous'";
$string['savesuccess'] = "Settings have been saved.";
$string['taskname'] = "eabyas Coursera Course Sync";
$string['cfgformcrseheading'] = 'Course Field Mappings';
$string['cfhformnoteselected'] = 'Not Mapped';
$string['cfhformduplicaterr'] = 'Duplicate field mapping - please select a different value.';
$string['eventsyncfailed'] = 'Catalog syncronisation task failed.';
$string['eventsyncfaileddesc'] = 'Syncronisation task failed with "{$a}"';
$string['eventsyncerror'] = 'Catalog syncronisation error.';
$string['eventsyncerrordesc'] = 'Syncronisation task had errors: "{$a}"';
$string['eventsynccomplete'] = 'Catalog syncronisation task complete.';
$string['eventsynccompletedesc'] = 'Syncronisation task completed:  Stats: "{$a}"';
$string['eventtotalmodules'] = 'Total Courses';
$string['eventcoursescreated'] = 'Courses created';
$string['eventcoursescreatederrors'] = 'Course creation errors';
$string['eventcoursesupdated'] = 'Courses updated';
$string['eventcoursesupdatederrors'] = 'Course update errors';
$string['errorjsonparse'] = 'Could not parse response: JSON parsing error: "{$a}"';
$string['errorapicall'] = 'Coursera API Call failed:"{$a}"';
$string['errorcourseupdate'] = 'Course Update Error: "{$a}"';
$string['errorcoursecreate'] = 'Course Creation Error : "{$a}"';
$string['errorduplicateidnumber'] = 'The idnumber "{$a}" appears to be duplicated.';
$string['errorfailedsetadd'] = 'Failed to add {$a} courses to the set';
$string['errorfailedsetand'] = 'and';
$string['errorfailedsetdel'] = 'Failed to delete {$a} courses from the set';
$string['successcourseupdate'] = 'Course Update Success: "{$a}"';
$string['successcoursecreate'] = 'Course Creation Success : "{$a}"';
$string['sync_history'] = 'Coursera History';
$string['sync_execution'] = 'Run Coursera Course Sync';
$string['synchistory_stats'] = 'Coursera History Statistics';
$string['courseradatetime'] = 'Excuted Date And Time';
$string['coursecustomfiles'] = '<b>Course custom files</b>';
$string['coursecustomfilesenabled'] = 'Enabled';
$string['coursecustomfilesenabled_help'] = "The sync'd courses are to be managed - defaults to course custom files ";
$string['secretkey'] = 'Secret Key';
$string['clientid'] = 'Client ID';
$string['programlist'] = 'Program List';