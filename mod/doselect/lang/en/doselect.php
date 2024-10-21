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
 * Strings for component 'doselect', language 'en'
 *
 * @package   mod_doselect
 * @copyright 2019 Anilkumar Cheguri  {anil@eabyas.in}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['configdisplayoptions'] = 'Select all options that should be available, existing settings are not modified. Hold CTRL key to select multiple fields.';
$string['description'] = 'Doselect description';
$string['contentheader'] = 'Content';
$string['createdoselect'] = 'Create a new Doselect';
$string['displayoptions'] = 'Available display options';
$string['displayselect'] = 'Display';
$string['displayselectexplain'] = 'Select display type.';
$string['legacyfiles'] = 'Migration of old course file';
$string['legacyfilesactive'] = 'Active';
$string['legacyfilesdone'] = 'Finished';
$string['modulename'] = 'Doselect';
$string['modulename_help'] = 'The Doselect module enables a teacher to create a Doselect assessment. A Doselect activity page can embedded assessment.';

$string['modulename_link'] = 'mod/doselect/view';
$string['optionsheader'] = 'Display options';
$string['doselect-mod-doselect-x'] = 'Any Doselect module page';
$string['doselect:addinstance'] = 'Add a new Doselect';
$string['doselect:view'] = 'View Doselect content';
$string['pluginadministration'] = 'Doselect module administration';
$string['pluginname'] = 'Doselect';
$string['printheading'] = 'Display Doselect name';
$string['search:activity'] = 'Doselect';
$string['mingrade'] = 'Minimum grade';
$string['maxgrade'] = 'Maximum grade';
$string['starttime'] = 'Start time';
$string['endtime'] = 'End time';
$string['endbeforstart'] = 'You have specified a end date before the start date.';
$string['gradepass'] = 'Grade to pass';
$string['mingradeerror'] = 'You have specified Max grade is less than the Min grade.';
$string['passgradeerror'] = 'You have specified Pass grade is greater than the Max grade.';
$string['passgrade_notless_mingrade_error'] = 'Pass grade is should be greater than or equals to the Min grade.';

$string['platform_apis'] = 'DoSelect Platform APIs';
$string['api_message'] = 'You can use these credentials to connect to DoSelect Platform APIs.';
$string['api_key'] = 'Doselect user account API Key';
$string['api_secret'] = 'Doselect user account API Secret';
$string['modulenameplural'] = 'Doselect assessments';

$string['completionbody'] = '<table style="border-collapse: collapse;background-color:#266c92;color:#FFF;font-size: 13px;font-family: Helvetica;width:100%;">
            <tr style="background-color:#fff;">
               <th><img src="{$a->picture3}" alt="Fractal" height="62px" width="187px" style="margin: 5px;float:left;"></th>
            </tr>
            <tr>
            <td colspan="2" style="padding: 26px;"><b>Dear {$a->employee},</b></td>
            </tr>
            <tr>
                <td colspan="2"  style="padding: 0px 26px;">
                    <b>You have successfully completed the "{$a->activity}" assessment.</b>
                </td>
            </tr>
            <tr>
                <td colspan="2"  style="padding: 0px 26px;">
                    <b>Please {$a->clickhere} to view the assessment.</b>
                </td>
            </tr>
            <tr>
                <td colspan="2" style="padding: 10px 20px;">
                    <b>Feel free to write back for any queries or suggestions. </b>
                </td>
            </tr>
            <tr>
                <td colspan="2" style="padding: 10px 20px;">
                    <b>Regards,</b>
                </td>
            </tr>
            <tr>
                <td colspan="2" style="padding: 5px 20px 15px 20px;">
                    FAA Team
                </td>
            </tr>
        </table>';
$string['youneedtoenrol'] = 'You need to enrol in this course before you can attempt this doselect';