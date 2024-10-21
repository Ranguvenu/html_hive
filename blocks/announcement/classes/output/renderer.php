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
namespace block_announcement\output;
require_once($CFG->dirroot . '/blocks/announcement/lib.php');
defined('MOODLE_INTERNAL') || die;

use context_system;
use html_table;
use html_table_cell;
use html_writer;
use plugin_renderer_base;
use moodle_url;
use stdClass;
use single_button;

class renderer extends plugin_renderer_base {
    // public function announcements($courseid, $limit = 0){

    //     global $DB,$CFG, $USER, $OUTPUT, $PAGE;
    //     $systemcontext = context_system::instance();
    //     $announcement_lib = new \block_announcement\local\lib();
    //     $announcements = $announcement_lib->announcements($courseid, $limit);
    //     $userorgid = $DB->get_field('user', 'open_costcenterid', array('id'=>$USER->id));
    //     $no_redirect_url = 'javascript:void(0)';
    //     if(!empty($announcements)){
    //         $data = array();
    //         foreach($announcements as $announce){
    //             //print_object($announce);
    //             $row = array();
    //             $neworg = $DB->get_field('user', 'open_costcenterid', array('id'=>$announce->usermodified));
    //             if($neworg){
                    
    //                 if($neworg == $userorgid){
    //                     $course = $DB->get_field('course','id',array('id' => $announce->courseid, 'visible' => 1));
    //                     if(!$course){
    //                         continue;
    //                     }
    //                     $user = $DB->get_field('user','firstname',array('id' => $announce->usermodified, 'confirmed' => 1, 'deleted' => 0, 'suspended' => 0));
    //                     $user1 = $DB->get_field('user','lastname',array('id' => $announce->usermodified, 'confirmed' => 1, 'deleted' => 0, 'suspended' => 0));
                                   
    //                     if(!$user){
    //                         continue;
    //                     }
    //                     if($announce->name > substr(($announce->name),0,35)){
    //                         $link = html_writer::div("<a href='".new moodle_url('/blocks/announcement/news.php',array('id'=>$announce->id))."'>".'...View more'."</a>");
    //                         $row[] = substr($announce->name,0,35).' '.$link;
    //                     }else{
    //                         $row[] = $announce->name;
    //                     }
    //                    if($announce->description > substr(($announce->description),0,50)){
    //                     $link = html_writer::div("<a href='".new moodle_url('/blocks/announcement/news.php',array('id'=>$announce->id))."'>".'...View more'."</a>");
    //                     $row[] = substr(strip_tags($announce->description),0,50).$link;
    //                     }else{
    //                         $row[] = strip_tags($announce->description);
    //                     }
                        
    //                     $row[] = $user.' '.$user1;
                        
    //                     $row[] = ($announce->timemodified) ? date('d/m/Y h:ia', $announce->timemodified) : '-';
    //                     if(isloggedin() && is_siteadmin($USER->id)){
                      
    //                         $buttons = array();
    //                         if(!empty($announce->visible)){
    //                                 $visible_value = 0;
    //                                 $show_hide_iconimg = html_writer::empty_tag('img', array('src' => $OUTPUT->image_url('t/hide'), 'title' => get_string('hide', 'block_announcement'),'alt' => get_string('hide'), 'class'=>'icon'));
    //                         }else{
    //                                 $visible_value = 1;
    //                                 $show_hide_iconimg = html_writer::empty_tag('img', array('src' => $OUTPUT->image_url('t/show'),'title' => get_string('show', 'block_announcement') ,'alt' => get_string('show'), 'class'=>'icon'));
    //                         }
    //                         $buttons[] = html_writer::link(new moodle_url('/blocks/announcement/announcements.php', array('id' =>  $announce->id, 'visible'=>$visible_value, 'sesskey' => sesskey())), $show_hide_iconimg, array());
    //                         // $buttons[] = html_writer::link(new moodle_url('/blocks/announcement/announcements.php', array('delete'=>$announce->id, 'courseid' => $courseid)), html_writer::empty_tag('img', array('src'=>$OUTPUT->pix_url('t/delete'), 'alt'=>'delete', 'class'=>'icon', 'id' => 'deleteannounce_'.$announce->id)), array('title'=>get_string('delete')));
    //                         $buttons[] = html_writer::link($no_redirect_url, html_writer::empty_tag('img', array('src'=>$OUTPUT->image_url('t/delete'), 'alt'=>'delete', 'class'=>'icon', 'id' => 'deleteannounce_'.$announce->id)), array('title'=>get_string('delete'), 'onclick' => '(function(e){ require(\'block_announcement/announcement\').deleteConfirm({selector:\'delete_announcement_modal\', contextid:'.$systemcontext->id.', id:'.$announce->id.'}) })(event)'));
    //                         // $PAGE->requires->event_handler('#deleteannounce_'.$announce->id, 'click', 'M.util.show_confirm_dialog', array('message' => get_string('approveconfirm', 'block_announcement'), 'callbacks' => array('id' => $announce->id)));
                            
    //                        $buttons[] = html_writer::link($no_redirect_url,
    //                                                     html_writer::empty_tag('img', array('src'=>$OUTPUT->image_url('i/edit'), 'title'=>'Edit', 'class'=>'icon',
    //                                                         'onclick'=> '(function(e){ require(\'block_announcement/announcement\').init({selector:\'announcementmodal\', contextid:'.$systemcontext->id.', id:'.$announce->id.'}) })(event)')));
                   
    //                     }elseif(isloggedin() && has_capability('local/costcenter:view', $systemcontext)){
    //                         if($announce->usermodified == 2){
    //                             $row[] = '-';
    //                         }else{
    //                              if($announce->usermodified==$USER->id){
    //                             $buttons = array();
                                
    //                             if(!empty($announce->visible)){
    //                                 $visible_value = 0;
    //                                 $show_hide_iconimg = html_writer::empty_tag('img', array('src' => $OUTPUT->image_url('t/hide'), 'title' => get_string('hide', 'block_announcement'),'alt' => get_string('hide'), 'class'=>'icon'));
    //                             }else{
    //                                 $visible_value = 1;
    //                                 $show_hide_iconimg = html_writer::empty_tag('img', array('src' => $OUTPUT->image_url('t/show'),'title' => get_string('show', 'block_announcement') ,'alt' => get_string('show'), 'class'=>'icon'));
    //                             }
    //                             // $buttons[] = html_writer::link(new moodle_url('/blocks/announcement/announcements.php', array('id' =>  $announce->id, 'visible'=>$visible_value, 'sesskey' => sesskey())), $show_hide_iconimg, array());
    //                             $buttons[] = html_writer::link($no_redirect_url, $show_hide_iconimg, array('onclick' => '(function(e){ require(\'block_announcement/announcement\').statusConfirm({selector:\'change_status_announcement_modal\', contextid:'.$systemcontext->id.', id:'.$announce->id.'}) })(event)'));
    //                             $buttons[] = html_writer::link($no_redirect_url,
    //                                                     html_writer::empty_tag('img', array('src'=>$OUTPUT->image_url('i/edit'), 'title'=>'Edit', 'class'=>'icon',
    //                                                         'onclick'=> '(function(e){ require(\'block_announcement/announcement\').init({selector:\'announcementmodal\', contextid:'.$systemcontext->id.', id:'.$announce->id.'}) })(event)')));
    //                             // $buttons[] = html_writer::link(new moodle_url('/blocks/announcement/announcements.php', array('delete'=>$announce->id, 'courseid' => $courseid)), html_writer::empty_tag('img', array('src'=>$OUTPUT->pix_url('t/delete'), 'alt'=>'delete', 'class'=>'icon', 'id' => 'deleteannounce_'.$announce->id)), array('title'=>get_string('delete')));
    //                             $buttons[] = html_writer::link($no_redirect_url, html_writer::empty_tag('img', array('src'=>$OUTPUT->image_url('t/delete'), 'alt'=>'delete', 'class'=>'icon', 'id' => 'deleteannounce_'.$announce->id)), array('title'=>get_string('delete'), 'onclick' => '(function(e){ require(\'block_announcement/announcement\').deleteConfirm({selector:\'delete_announcement_modal\', contextid:'.$systemcontext->id.', id:'.$announce->id.'}) })(event)'));
    //                             // $PAGE->requires->event_handler('#deleteannounce_'.$announce->id, 'click', 'M.util.show_confirm_dialog', array('message' => get_string('approveconfirm', 'block_announcement'), 'callbacks' => array('id' => $announce->id)));
    //                             $row[] = implode(' ', $buttons);
    //                              }else{
    //                                 $row[]="-";
    //                              }
    //                         }
    //                     }
                    
    //                 else{
    //                     $row[] = '-';
    //                 }
    //                     $data[] = $row;
                        
    //                 }
                    
    //             }else{
    //                 // echo "1";
    //                 $course = $DB->get_field('course','id',array('id' => $announce->courseid, 'visible' => 1));
    //                 if(!$course){
    //                     continue;
    //                 }
    //                 $user = $DB->get_field('user','firstname',array('id' => $announce->usermodified, 'confirmed' => 1, 'deleted' => 0, 'suspended' => 0));
    //                 $user1 = $DB->get_field('user','lastname',array('id' => $announce->usermodified, 'confirmed' => 1, 'deleted' => 0, 'suspended' => 0));
    //                 if(!$user){
    //                     continue;
    //                 }
    //                 if($announce->name > substr(($announce->name),0,35)){
    //                     $link = html_writer::div("<a href='".new moodle_url('/blocks/announcement/news.php',array('id'=>$announce->id))."'>".'...View more'."</a>");
    //                     $row[] = substr($announce->name,0,35).' '.$link;
    //                 }else{
    //                     $row[] = $announce->name;
    //                 }
    //                 if($announce->description > substr(($announce->description),0,50)){
    //                 $link = html_writer::div("<a href='".new moodle_url('/blocks/announcement/news.php',array('id'=>$announce->id))."'>".'...View more'."</a>");
    //                 $row[] = substr(strip_tags($announce->description),0,50).$link;
    //                 }else{
    //                     $row[] = strip_tags($announce->description);
    //                 }
    //                 $row[] = $user.' '.$user1;
                    
    //                 $row[] = ($announce->timemodified) ? date('d/m/Y h:ia', $announce->timemodified) : '-';
                   
                   
    //                     if(isloggedin() && is_siteadmin($USER->id)){
    //                         $buttons = array();
    //                         //$buttons[] = html_writer::link(new moodle_url('/blocks/announcement/announcements.php', array('edit'=>$announce->id, 'courseid' => $courseid)), html_writer::empty_tag('img', array('src'=>$OUTPUT->image_url('t/edit'), 'alt'=>'edit', 'class'=>'iconsmall')), array('title'=>get_string('edit')));
    //                         if(!empty($announce->visible)){
    //                             $visible_value = 0;
    //                             $show_hide_iconimg = html_writer::empty_tag('img', array('src' => $OUTPUT->image_url('t/hide'), 'title' => get_string('hide', 'block_announcement'),'alt' => get_string('hide'), 'class'=>'icon'));
    //                         }else{
    //                             $visible_value = 1;
    //                             $show_hide_iconimg = html_writer::empty_tag('img', array('src' => $OUTPUT->image_url('t/show'),'title' => get_string('show', 'block_announcement') ,'alt' => get_string('show'), 'class'=>'icon'));
    //                         }
    //                         // $buttons[] = html_writer::link(new moodle_url('/blocks/announcement/announcements.php', array('id' =>  $announce->id, 'visible'=>$visible_value, 'sesskey' => sesskey())), $show_hide_iconimg, array());
    //                         $buttons[] = html_writer::link($no_redirect_url, $show_hide_iconimg, array('onclick' => '(function(e){ require(\'block_announcement/announcement\').statusConfirm({selector:\'change_status_announcement_modal\', contextid:'.$systemcontext->id.', id:'.$announce->id.'}) })(event)'));
    //                         $buttons[] = html_writer::link($no_redirect_url,
    //                                                     html_writer::empty_tag('img', array('src'=>$OUTPUT->image_url('i/edit'), 'title'=>'Edit', 'class'=>'icon',
    //                                                         'onclick'=> '(function(e){ require(\'block_announcement/announcement\').init({selector:\'announcementmodal\', contextid:'.$systemcontext->id.', id:'.$announce->id.'}) })(event)')));
    //                         $buttons[] = html_writer::link(new moodle_url('/blocks/announcement/announcements.php', array('delete'=>$announce->id, 'courseid' => $courseid)), html_writer::empty_tag('img', array('src'=>$OUTPUT->image_url('t/delete'), 'alt'=>'delete', 'class'=>'icon', 'id' => 'deleteannounce_'.$announce->id)), array('title'=>get_string('delete')));
    //                         $PAGE->requires->event_handler('#deleteannounce_'.$announce->id, 'click', 'M.util.show_confirm_dialog', array('message' => get_string('approveconfirm', 'block_announcement'), 'callbacks' => array('id' => $announce->id)));
    //                         $row[] = implode(' ', $buttons);
    //                     }elseif(isloggedin() && has_capability('local/costcenter:view', $systemcontext)){
    //                         if($announce->usermodified == 2){
    //                             $row[] = '-';
    //                         }else{
    //                             $buttons = array();
    //                         if(!empty($announce->visible)){
    //                             $visible_value = 0;
    //                             $show_hide_iconimg = html_writer::empty_tag('img', array('src' => $OUTPUT->image_url('t/hide'), 'title' => get_string('hide', 'block_announcement'),'alt' => get_string('hide'), 'class'=>'icon'));
    //                         }else{
    //                             $visible_value = 1;
    //                             $show_hide_iconimg = html_writer::empty_tag('img', array('src' => $OUTPUT->image_url('t/show'),'title' => get_string('show', 'block_announcement') ,'alt' => get_string('show'), 'class'=>'icon'));
    //                         }
    //                         // $buttons[] = html_writer::link(new moodle_url('/blocks/announcement/announcements.php', array('id' =>  $announce->id, 'visible'=>$visible_value, 'sesskey' => sesskey())), $show_hide_iconimg, array());
    //                         $buttons[] = html_writer::link($no_redirect_url, $show_hide_iconimg, array('onclick' => '(function(e){ require(\'block_announcement/announcement\').statusConfirm({selector:\'change_status_announcement_modal\', contextid:'.$systemcontext->id.', id:'.$announce->id.', visible:'.$visible_value.'}) })(event)'));
    //                             $buttons[] = html_writer::link($no_redirect_url,
    //                                                     html_writer::empty_tag('img', array('src'=>$OUTPUT->image_url('i/edit'), 'title'=>'Edit', 'class'=>'icon',
    //                                                         'onclick'=> '(function(e){ require(\'block_announcement/announcement\').init({selector:\'announcementmodal\', contextid:'.$systemcontext->id.', id:'.$announce->id.'}) })(event)')));
                                
    //                            $buttons[] = html_writer::link(new moodle_url('/blocks/announcement/announcements.php', array('delete'=>$announce->id, 'courseid' => $courseid)), html_writer::empty_tag('img', array('src'=>$OUTPUT->image_url('t/delete'), 'alt'=>'delete', 'class'=>'icon', 'id' => 'deleteannounce_'.$announce->id)), array('title'=>get_string('delete')));
    //                             $PAGE->requires->event_handler('#deleteannounce_'.$announce->id, 'click', 'M.util.show_confirm_dialog', array('message' => get_string('approveconfirm', 'block_announcement'), 'callbacks' => array('id' => $announce->id)));
    //                             $row[] = implode(' ', $buttons);
    //                         }
    //                     }
                    
    //                 else{
    //                     $row[] = '-';
    //                 }
                    
    //                 $data[] = $row;
    //             }
                 
    //         }
    //         //print_object($data);
    //         $table = new html_table();
    //         $table->id = 'table_block_announcement';
    //         $table->head = array(get_string('subject', 'block_announcement'),
    //                              get_string('description'),
    //                              get_string('postedby', 'block_announcement'),
    //                              get_string('postedon_head', 'block_announcement'),
    //                              get_string('actions')
    //                              );
    //         $table->data = $data;
    //         $table->size = array('20%', '30%', '10%', '15%', '10%');
    //         $out = html_writer::table($table);

            
    //         return $out;
    //     }else{
    //         return '<div class="w-full pull-left mt-15 alert alert-info text-center">'.get_string('no_announcements', 'block_announcement').'</div>';
    //     }
        
    // }
    
    public function announcements($courseid, $limit = 0){
        global $DB,$CFG, $USER, $OUTPUT, $PAGE;
        $systemcontext = context_system::instance();
        $announcement_lib = new \block_announcement\local\lib();
        /*$announcements = $announcement_lib->announcements($courseid, $limit, false);*/
        $announcements = $announcement_lib->get_announcement_details($courseid, $limit, false);
        $no_redirect_url = 'javascript:void(0)';
        $data = [];
        if(!empty($announcements)){
            $data = array();
            foreach($announcements as $announce){
                // print_object($announce);
                $row = array();
                $buttons = [];
                $user_name = $DB->get_field_sql("SELECT CONCAT(firstname,' ',lastname) as fullname FROM {user} WHERE id=:id AND confirmed=:confirmed AND deleted=:deleted AND suspended=:suspended ", array('id' => $announce->usermodified, 'confirmed' => 1, 'deleted' => 0, 'suspended' => 0));                                     
                if(!$user_name){
                    continue;
                }
                $link = html_writer::div("<a href='".new moodle_url('/blocks/announcement/news.php',array('id'=>$announce->id))."'>".'...View more'."</a>");
                if($announce->name > substr(($announce->name),0,35)){
                    
                    $row[] = substr($announce->name,0,35).' '.$link;
                }else{
                    $row[] = $announce->name;
                }
                if($announce->description > substr(($announce->description),0,50)){
                    $row[] = substr(strip_tags($announce->description),0,50).$link;
                }else{
                    $row[] = strip_tags($announce->description);
                }

                $row[] = $user_name;
                        
                $row[] = ($announce->timemodified) ? date('d/m/Y h:ia', $announce->timemodified) : '-';
                if($announce->attachment){
                    $file =$DB->get_record_sql("SELECT * FROM {files} WHERE itemid = $announce->attachment and filename!='.' and component = 'block_announcement' and filearea = 'announcement'");
                    $filedata = get_file_storage();
                    $files = $filedata->get_area_files($file->contextid, 'block_announcement', 'announcement',$file->itemid, 'id', false);
                    $download_link = '-';
                    if(!empty($files)){
                        $url = array(); 
                        foreach ($files as $file) {            
                            $isimage = $file->is_valid_image();            
                            $url[] = file_encode_url("$CFG->wwwroot/pluginfile.php", '/' . $file->get_contextid() . '/' . 'block_announcement' . '/' . 'announcement' .'/'.$file->get_itemid(). $file->get_filepath() . $file->get_filename(), !$isimage);
                        }
                        // $img= "<img id= '' src = '$url[0]'  height='70' width='70'/><br>";
                        // $download_link = \html_writer::link($url[0],)
                        $download_link = "<a href=".$url[0]." download title='".get_string('attachment','block_announcement')."'><i class='fa fa-download'></i></a>";
                    }
                    $row[] = $download_link;

                }else{
                    $row[] = "<p title='".get_string('noattachement','block_announcement')."'> - </p>";
                }
                if(isloggedin() && is_siteadmin($USER->id)){
                    $display_buttons = true;
                }else if(has_capability('block/announcement:manage_announcements',$systemcontext) && $announce->costcenterid==$USER->open_costcenterid){
                    $display_buttons = true;
                }else{
                    $display_buttons = false;
                }
                if($display_buttons){
                    if(!empty($announce->visible)){
                        $visible_value = 0;
                        $status = 'Hide';                       
                        $show_hide_iconimg = html_writer::empty_tag('img', array('src' => $OUTPUT->image_url('t/hide'), 'title' => get_string('hide', 'block_announcement'),'alt' => get_string('hide'), 'class'=>'icon'));
                    }else{
                        $visible_value = 1;
                        $status = 'Show';
                        $show_hide_iconimg = html_writer::empty_tag('img', array('src' => $OUTPUT->image_url('t/show'),'title' => get_string('show', 'block_announcement') ,'alt' => get_string('show'), 'class'=>'icon'));
                    }
                    $buttons[] = html_writer::link($no_redirect_url, $show_hide_iconimg, array('onclick' => '(function(e){ require(\'block_announcement/announcement\').statusConfirm({selector:\'change_status_announcement_modal\', contextid:'.$systemcontext->id.', id:'.$announce->id.', visible:'.$visible_value.',status:"'.$status.'",name:"'.$announce->name.'"}) })(event)'));
                    $buttons[] = html_writer::link($no_redirect_url,
                        html_writer::empty_tag('img', array('src'=>$OUTPUT->image_url('i/edit'), 'title'=>'Edit', 'class'=>'icon','onclick'=> '(function(e){ require(\'block_announcement/announcement\').init({selector:\'announcementmodal\', contextid:'.$systemcontext->id.', id:'.$announce->id.'}) })(event)')));
                    $buttons[] = html_writer::link($no_redirect_url, html_writer::empty_tag('img', array('src'=>$OUTPUT->image_url('t/delete'), 'alt'=>'delete', 'class'=>'icon', 'id' => 'deleteannounce_'.$announce->id)), array('title'=>get_string('delete'), 'onclick' => '(function(e){ require(\'block_announcement/announcement\').deleteConfirm({selector:\'delete_announcement_modal\', contextid:'.$systemcontext->id.', id:'.$announce->id.', name:"'.$announce->name.'"}) })(event)'));
                    $row[] = implode(' ', $buttons);
                }else{
                    $row[] = '-';
                }

                $data[] = $row;
            }
            $table = new html_table();
            $table->id = 'table_block_announcement';
            $table->head = array(get_string('subject', 'block_announcement'),
                                 get_string('description'),
                                 get_string('postedby', 'block_announcement'),
                                 get_string('postedon_head', 'block_announcement'),
                                 get_string('attachment', 'block_announcement'),
                                 get_string('actions')
                                 );
            $table->data = $data;
            // $table->size = array('20%', '30%', '10%', '15%', '10%', '10%');
            $table->align = array('left', 'left', 'left', 'left', 'center', 'center');
            $out = html_writer::table($table);
            return $out;
        }else{
            return '<div class="w-full pull-left mt-15 alert alert-info text-center">'.get_string('no_announcements', 'block_announcement').'</div>';
        }
    }
    // public function announcements_view($courseid,$limit = 0){
  
    //     global $DB, $COURSE, $USER, $OUTPUT, $PAGE;
    //     $announcement_lib = new \block_announcement\local\lib();
    //     $allannouncements = $announcement_lib->announcements($courseid,$limit,true);
    //     $lib = array();
    //     foreach($allannouncements as $allannounce){
    //         $lib[] = $allannounce->usermodified;
    //     }
    //     $userorgid = $DB->get_field('user', 'open_costcenterid', array('id'=>$USER->id));
    //     $neworg = $DB->get_field('user', 'open_costcenterid', array('id'=>$allannounce->usermodified));
        
    //     if($neworg == $userorgid){ 
    //         $list1 = array();
    //         $list2 = array();
    //         foreach($allannouncements as $ment){
    //             $list1[] = $ment;
    //         }
    //         $finallist = $list1 ;
   
    //     }else{
    //         $list1 = array();
    //         $list2 = array();
    //         foreach($allannouncements as $ment){
    //             //Active Announcements
    //             $list1[] = $ment;
    //         }
    //         $finallist = $list1 ;
            
    //     }
    //     //$finallist = $list1 ;
    //     $announcements = $finallist;
    //     $data = array();
    //     $systemcontext = context_system::instance();
    //     if($announcements){
    //         $content = '';
    //         $new_calc = 1;
    //         $news_icon_class = '';
    //         $table = new html_table();
    //         foreach($announcements as $announce){
    //             if($new_calc == 1){
    //                 $news_icon_class = ' fa-volume-up  ';
    //             }else{
    //                 $news_icon_class = ' fa-picture-o ';
    //             }
    //             $row = array();
    //             $neworg = $DB->get_field('user','open_costcenterid', array('id'=>$announce->usermodified));
    //             if($neworg){
    //                 if($neworg == $userorgid){   
    //                     $course = $DB->get_field('course','id',array('id' => $announce->courseid, 'visible' => 1));
    //                     if(!$course){
    //                         continue;
    //                     }
    //                     $user = $DB->get_field('user','id',array('id' => $announce->usermodified, 'confirmed' => 1, 'deleted' => 0, 'suspended' => 0));
    //                     if(!$user){
    //                         continue;
    //                     }
    //                     $template = '';
    
    //                     $url = new moodle_url('/blocks/announcement/news.php',array('id'=>$announce->id));
    //                     if($announce->name > substr(($announce->name),0,70)){
    //                         $name = substr($announce->name,0,70).' '.'...';
    //                     }else{
    //                         $name = $announce->name;
    //                     }
    //                     $More = '<div class="col-md-11"><div class = "info"><div>'.html_writer::link($url,$name,array()).'</div>'.
    //                      '<div>'.'By'.' '.fullname($user_name).'</div></div></div>'.
    //                      "\n";
    //                     $template .= $More;
    //                     if($announce->startdate){
    //                         $template .= '<div class="col-md-1 p-0 text-xs-center"><span class="d-block">'.date("d", $announce->startdate).'</span><span>'.date("M'y", $announce->startdate).'</span></div>';
    //                     }else{
    //                         $template .= '<div class="col-md-1 p-0 text-xs-center"><span class="d-block">'.date("d", $announce->timemodified).'</span><span>'.date("M'y", $announce->timemodified).'</span></div>';
    //                     }
                      
    //                     $rowdata = '';
    //                     $presenttime = time();
    //                     if(($announce->startdate <= $presenttime) && ($announce->enddate >= $presenttime)){
    //                         $readycell = new html_table_cell();
    //                         // $img = html_writer::start_tag('div',array('class'=>'col-md-1'));
    //                         //     $img .= html_writer::tag('i', '', array('class'=>'fa '.$news_icon_class .' news_fa_icon'));
    //                         // $img .= html_writer::end_tag('div');
    //                         $readycell->text = $template;
    //                         $readycell->attributes['class'] = 'activeannouncements col-md-12 p-0';
    //                         $row[] = $readycell;
    //                         $rowdata .= html_writer::div( $template, 'activeannouncements col-md-12 p-0');
    //                     }else{
    //                         $readycell = new html_table_cell();
    //                         // $img = html_writer::start_tag('div',array('class'=>'col-md-1'));
    //                         //     $img .= html_writer::tag('i', '', array('class'=>'fa '.$news_icon_class.' news_fa_icon'));
    //                         // $img .= html_writer::end_tag('div');
    //                         $readycell->text = $template;
    //                         $readycell->attributes['class'] = 'notactiveannouncements col-md-12 p-0';
    //                         $row[] = $readycell;
    //                         $rowdata .= html_writer::div($template, 'notactiveannouncements p-0 col-md-12');
    //                     }
    //                     $data[] = $row;
    //                 }
                    
    //             }else{
    //                 $course = $DB->get_field('course','id',array('id' => $announce->courseid, 'visible' => 1));
    //                 if(!$course){
    //                     continue;
    //                 }
    //                 $user = $DB->get_field('user','id',array('id' => $announce->usermodified, 'confirmed' => 1, 'deleted' => 0, 'suspended' => 0));
    //                 if(!$user){
    //                     continue;
    //                 }
    //                 $template = '';
    //                 $user_name = $DB->get_record('user',array('id'=>$announce->usermodified));
    //                 $url = new moodle_url('/blocks/announcement/news.php',array('id'=>$announce->id));
    //                 if($announce->name > substr(($announce->name),0,70)){
    //                     $name = substr($announce->name,0,70).' '.'...';
    //                 }else{
    //                     $name = $announce->name;
    //                 }
    //                 $More = '<div class="col-md-11"><div class = "info"><div>'.html_writer::link($url,$name,array()).'</div>'.
    //                      '<div>'.'By'.' '.fullname($user_name).'</div></div></div>'.
    //                      "\n";
                    
    //                 if($announce->startdate){
    //                         //$template .= '<div class="col-md-1">'.date("d M'y", $announce->startdate).'</div>';
    //                         $template .= '<div class="col-md-1 p-0 text-xs-center"><span class="d-block">'.date("d", $announce->startdate).'</span><span>'.date("M'y", $announce->startdate).'</span></div>';
    //                 }else{
    //                     //$template .= '<div class="col-md-1">'.date("d M'y", $announce->timemodified).'</div>';
    //                     $template .= '<div class="col-md-1 p-0 text-xs-center"><span class="d-block">'.date("d", $announce->timemodified).'</span><span>'.date("M'y", $announce->timemodified).'</span></div>';
    //                 }
    //                 $template .= $More;
    //                 $rowdata = '';
    //                 $presenttime = time();
    //                 if(($announce->startdate <= $presenttime) && ($announce->enddate >= $presenttime)){
    //                     $readycell = new html_table_cell();
                       
    //                     $readycell->text = $template;
    //                     $readycell->attributes['class'] = 'activeannouncements col-md-12 p-0';
    //                     $row[] = $readycell;
    //                     $rowdata .= html_writer::div($img.' '.$template, 'activeannouncements col-md-12 p-0');
    //                 }else{
    //                     $readycell = new html_table_cell();
                       
    //                     $readycell->text = $template;
    //                     $readycell->attributes['class'] = 'notactiveannouncements col-md-12 p-0';
    //                     $row[] = $readycell;
    //                     $rowdata .= html_writer::div($template, 'notactiveannouncements col-md-12 p-0');
    //                 }
    //                 $data[] = $row;
    //             }
    //             $content .= html_writer::div($rowdata, 'fullannouncement');
               
    //             if($new_calc == 2){
    //              $new_calc = 1;
    //             }else{
    //                 $new_calc++;
    //             }
    //         }
    //         $table->data = $data;
    //         $return = '';
            
    //         if(is_siteadmin($USER->id) || has_capability('block/announcement:manage_announcements', $systemcontext)){
    //             //$url = new moodle_url('/blocks/technical_support/announcements.php', array());
    //             $return .= html_writer::div("<span><a href='".new moodle_url('/blocks/announcement/announcements.php', array('collapse'=>0))."'>".get_string('createnew', 'block_announcement')."</a></span>",'cratenew');
    //         }
    //         $return .= html_writer::div($content, 'totalannouncements');
        
    //         if(count($announcements) > 2){
                
    //             $return .= html_writer::div("<span><a href='".new moodle_url('/blocks/announcement/announcements.php', array('collapse'=>1))."'>".get_string('viewmore', 'block_announcement')."</a></span>",'technicalsupport');
    //         }          
    //         return $return;
    //     }else{
    //         $return = '';
    //         $return .= '<div class="w-full pull-left mt-15 alert alert-info text-center">'.get_string('no_announcements', 'block_announcement').'</div>';
    //         if(is_siteadmin($USER->id) || has_capability('local/costcenter:view', $systemcontext)){
                
    //             $return .= html_writer::div("<span><a href='".new moodle_url('/blocks/announcement/announcements.php', array('collapse'=>0))."'>".get_string('createnew', 'block_announcement')."</a></span>",'cratenew');
    //         }
    //         return $return;
    //     }
    // }
    
    public function announcements_view($courseid,$limit = 0){
        global $DB, $COURSE, $USER, $OUTPUT, $PAGE, $CFG;
        $announcement_lib = new \block_announcement\local\lib();
        $allannouncements = $announcement_lib->announcements($courseid,$limit,true);
        //print_object($allannouncements);die();
        $content = '';
        $return = '';
        $systemcontext = context_system::instance();
        if(is_siteadmin($USER->id) || has_capability('block/announcement:manage_announcements', $systemcontext)){
            
            $return .= html_writer::div("<span><a href='".new moodle_url('/blocks/announcement/announcements.php', array('collapse'=>0))."'>".get_string('manageanno', 'block_announcement')."</a></span>",'cratenew');
        }
        if(!empty($allannouncements)){
            $lib = array();
            $rowdata = '';
            foreach($allannouncements as $announce){
               // print_object($announce)
                $user_name = $DB->get_field_sql("SELECT concat(firstname,' ',lastname) as fullname FROM {user} WHERE id=:id AND confirmed=:confirmed AND deleted=:deleted AND suspended=:suspended", array('id' => $announce->usermodified, 'confirmed' => 1, 'deleted' => 0, 'suspended' => 0));
                $template = '';
    
                $url = new moodle_url('/blocks/announcement/news.php',array('id'=>$announce->id));
                if($announce->name > substr(($announce->name),0,12)){
                    $name = substr($announce->name,0,20).' '.'...';
                }else{
                    $name = $announce->name;
                }
                $More = '<div class="col-md-9 col-sm-9 col-9 pull-left announcement_right"><div class = "info"><div class="annoncement_name" title="'.$announce->name.'">'.html_writer::link($url,$name,array()).'</div>'.
                 '<div class="annonun_created">'.'By'.' '.$user_name.'</div></div></div>'.
                 "\n";
                // if($announce->startdate){
                //     $template .= '<div class="col-md-2 p-0 text-xs-center announcedate_container">
                //                         <span class="announcedate">
                //                             <span class="d-block">'.date("d", $announce->startdate).'</span>
                //                             <span>'.date("M'y", $announce->startdate).'</span>
                //                             <!--<span>'.date("Y", $announce->startdate).'</span>-->
                //                             </span>
                //                         </div>';
                // }else{
                //     $template .= '<div class="col-md-2 p-0 text-xs-center announcedate_container">
                //                         <span class="announcedate">
                //                             <span class="d-block">'.date("d", $announce->startdate).'</span>
                //                             <span>'.date("M'y", $announce->startdate).'</span>
                //                             <!--<span>'.date("Y", $announce->startdate).'</span>-->
                //                             </span>
                //                         </div>';
                // }

                if($announce->startdate){
                    $template .= '<div class="col-md-2 col-sm-2 col-2 pull-left p-0 text-center announcedate_container"><span class="announcedate_content"><span class="d-block announceday">'.date("d", $announce->startdate).'</span>
                        <span class="announcemy">'.date("M 'y", $announce->startdate).'</span>
                        </span></div>';
                }else{
                    $template .= '<div class="col-md-2 col-sm-2 col-2 pull-left p-0 text-center announcedate_container"><span class="announcedate_content"><span class="d-block announceday">'.date("d", $announce->timemodified).'</span>
                        <span class="announcemy">'.date("M 'y", $announce->timemodified).'</span>
                        </span></div>';
                }
                $template .= $More;
                if($announce->attachment){
                    $file =$DB->get_record_sql("SELECT * FROM {files} WHERE itemid = $announce->attachment and filename!='.' and component = 'block_announcement' and filearea = 'announcement'");
                    $filedata = get_file_storage();
                    $files = $filedata->get_area_files($file->contextid, 'block_announcement', 'announcement',$file->itemid, 'id', false);
                    $download_link = "<div class='col-md-1 col-sm-1 pull-left p-0 text-center mt-10' title='".get_string('noattachement','block_announcement')."'> - </div>";
                    if(!empty($files)){
                        $url = array(); 
                        foreach ($files as $file) {                       
                            $url[] = file_encode_url("$CFG->wwwroot/pluginfile.php", '/' . $file->get_contextid() . '/' . 'block_announcement' . '/' . 'announcement' .'/'.$file->get_itemid(). $file->get_filepath() . $file->get_filename(), !$isimage);
                        }
                        $download_link = "<div class='col-md-1 col-sm-1 col-1 pull-left p-0 text-center mt-10'><a href=".$url[0]." download title='".get_string('attachment','block_announcement')."'><i class='fa fa-download'></i></a></div>";
                    }
                    $template .= $download_link;

                }else{
                    $template .= "<div class='col-md-1 col-sm-1 col-1  pull-left p-0 text-center pull-right mt-10' title='".get_string('noattachement','block_announcement')."'> - </div>";
                }
                $readycell = new html_table_cell();
                $readycell->text = $template;
                $readycell->attributes['class'] = 'activeannouncements col-md-12 col-sm-12 col-12 pull-left p-0';
                $rowdata .= html_writer::div( $template, 'activeannouncements col-md-12 col-sm-12 col-12 pull-left p-0');

            }
            $content .= html_writer::div($rowdata, 'fullannouncement');
            $return .= html_writer::div($content, 'totalannouncements');
          // echo count($allannouncements);

            //get total count
            $allannouncemetstotal = $announcement_lib->announcements_count($courseid,$limit,true);
            
            if($allannouncemetstotal > 2){

            /*if(count($allannouncements) > 3){ */           
                $return .= html_writer::div("<span><a href='".new moodle_url('/blocks/announcement/announcements.php', array('collapse'=>1))."'>".get_string('viewmore', 'block_announcement')."</a></span>",'technicalsupport');
            }    
        }else{
            
            $return .= '<div class="w-full pull-left mt-15 alert alert-info text-center">'.get_string('no_announcements', 'block_announcement').'</div>';
            
            
        }
        
        return $return;
    }
}





