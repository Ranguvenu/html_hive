<?php
/**
 * This file is part of eAbyas
 *
 * Copyright eAbyas Info Solutons Pvt Ltd, India
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author eabyas  <info@eabyas.in>
 * @package BizLMS
 * @subpackage blocks_announcement
 */
use \blocks_announcement\form\announcement_form as announcement_form;
// function create($data, $editoroptions = null){
// 	 global $DB, $CFG, $USER;
// 		$data = (object)$data;
//         $record = new stdClass();
//         $record->name = $data->name;
//         $record->startdate = $data->startdate;
//         $record->enddate = $data->enddate;
//         $record->timemodified = time();
//         $record->usermodified = $USER->id;
//         $record->timecreated = time();
//         $record->courseid = 1;
//         $record->visible = 1;
//         $record->costcenterid = $data->costcenterid;
//         $record->departmentid = $data->departmentid;
//         $data->description['text']=str_replace("/draftfile.php/5/user/draft/","/pluginfile.php/1/block_announcement/announcement/",$data->description['text']);
//         $record->description = $data->description['text'];
//         $record->id = $DB->insert_record('block_announcement', $record);

// }

// function update($data, $editoroptions = null){
// 	 global $DB, $CFG, $USER;
// 		$data = (object)$data;
//         $record = new stdClass();
//         $record->name = $data->name;
//         $record->startdate = $data->startdate;
//         $record->enddate = $data->enddate;
//         $record->timemodified = time();
//         $record->usermodified = $USER->id;
//         $record->timecreated = time();
//         $record->courseid = 1;
//         $record->visible = 1;
//         $record->costcenterid = $data->costcenterid;
//         $record->departmentid = $data->departmentid;
//         $data->description['text']=str_replace("/draftfile.php/5/user/draft/","/pluginfile.php/1/block_announcement/announcement/",$data->description['text']);
//         $record->description = $data->description['text'];
//         $record->id = $data->id;
        
//         $DB->update_record('block_announcement', $record);
// }

// function announcements($courseid, $limit = 0, $future = false){
//     global $DB, $USER;
    
//     $limit = empty($limit) ? '' : ' LIMIT '.$limit;
     
//     $where = '';
    
//     $params = array('courseid' => 1);
//     $systemcontext = context_system::instance();
   
//    if($future){
// 	$params = array();
//    }
//     $announcements_sql = "SELECT id,courseid,name,description,startdate,enddate,
// 						  attachment,visible,usermodified,timecreated,timemodified
// 						  FROM {block_announcement}
//                            WHERE courseid = 1";
   
//     // $announcements_sql .= " AND usermodified = 2 ORDER BY id DESC";
   
//     $announcements = $DB->get_records_sql($announcements_sql.$limit, $params);

//     return $announcements;
// }

function block_announcement_output_fragment_announcement_form($args){
 global $DB,$CFG,$PAGE;
    
    $args = (object) $args;
    $context = $args->context;
    $id = $args->id;
    
    $o = '';
   
    $formdata = [];
    if (!empty($args->jsonformdata)) {
        $serialiseddata = json_decode($args->jsonformdata);
        parse_str($serialiseddata, $formdata);
    }
 
    $context = context_system::instance();
	$itemid = 0;
    
    if ($id > 0) {
        $heading = 'Update Announcement';
        $collapse = false;
        $data = $DB->get_record('block_announcement', array('id'=>$id));
        $formdata = new stdClass();
        $formdata->id = $data->id;
        $formdata->name = $data->name;
        $formdata->description['text'] = $data->description;
        $formdata->attachment = $data->attachment;
        $formdata->startdate = $data->startdate;
        $formdata->enddate = $data->enddate;
    }
    
    $editoroptions = [
        'maxfiles' => EDITOR_UNLIMITED_FILES,
        'maxbytes' => $course->maxbytes,
        'trust' => false,
        'context' => $context,
        'noclean' => true,
        'subdirs' => false
    ];
    $params = array(
    'id' => $id,
    'context' => $context,
	'itemid' => $itemid,
    'editoroptions' => $editoroptions,
    'attachment' => $data->attachment,
    );
 
    $mform = new block_announcement\form\announcement_form(null, $params, 'post', '', null, true, $formdata);
    // Used to set the courseid.
    //print_object($formdata);
    $mform->set_data($formdata);

    if (!empty($args->jsonformdata)) {
        // If we were passed non-empty form data we want the mform to call validation functions and show errors.
        $mform->is_validated();
    }
 
    ob_start();
    $mform->display();
    $o .= ob_get_contents();
    ob_end_clean();
 
    return $o;
}
function block_announcement_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = array()) {
    global $CFG;

    if ($filearea == 'announcement') {
        $itemid = (int) array_shift($args);

        $fs = get_file_storage();
        $filename = array_pop($args);
        if (empty($args)) {
            $filepath = '/';
        } else {
            $filepath = '/' . implode('/', $args) . '/';
        }

        $file = $fs->get_file($context->id, 'block_announcement', $filearea, $itemid, $filepath, $filename);

        if (!$file) {
            return false;
        }
        $filedata = $file->resize_image(200, 200);
        \core\session\manager::write_close();
        send_stored_file($file, null, 0, 1);
    }

    send_file_not_found();
}
// function announcement_icon($itemid, $blockinstanceid) {
//     global $DB, $CFG, $USER, $OUTPUT;
//     $file = $DB->get_record('files', array('itemid' => $itemid,'filearea'=>'announcement'));
//     if (empty($file)) {
//         $defaultlogo = $OUTPUT->image_url('sample_announcement', 'block_announcement');
//         $logo = $defaultlogo;
//     } else {
//         $context = context_system::instance();
//         $fs = get_file_storage();
//         $files = $fs->get_area_files($context->id, 'block_announcement', 'announcement', $file->itemid, 'filename', false);
//         $url = array();
//     if(!empty($files)){
//         foreach ($files as $file) {
//             $isimage = $file->is_valid_image();
//             $filename = $file->get_filename();
//             $ctxid = $file->get_contextid();
//             $component = $file->get_component();
//             $itemid = $file->get_itemid();
//             if ($isimage) {
//                 $url[] = $CFG->wwwroot."/pluginfile.php/$ctxid/block_announcement/announcement/$itemid/$filename";
//             }
//         }
//         if(!empty($url[0])){
//             $logo = $url[0];
//         }else{
//             $defaultlogo = $OUTPUT->image_url('sample_announcement', 'block_announcement');
//             $logo = $defaultlogo;
//         }
//     } else{
//         return $OUTPUT->image_url('sample_announcement', 'block_announcement');
//     }
// }
// return $logo;
// }













