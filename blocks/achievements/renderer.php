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
 * 
 *
 * @package    block_achievements
 * @copyright  2017 eAbyas info solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;
require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot.'/blocks/achievements/lib.php');
require_once($CFG->libdir . '/badgeslib.php');
class block_achievements_renderer extends plugin_renderer_base {
	
	public function my_achievements_tabs(){
		global $CFG;


		$badges_tab=true;
		$points_tab=true;
		$certifications_tab=true;

		$achievementtabslist = [
      
            'contextid' => '1',
            'plugintype' => 'block',
            'plugin_name' =>'achievements',
            'badges_tab'=>$badges_tab,
            'points_tab'=>$points_tab,
            'certifications_tab'=>$certifications_tab,
           
        ];


		//calling the mustache template
		return $this->render_from_template('block_achievements/achievements_tabs',$achievementtabslist);

		/*$certificates = new achievements();
		
		$extrainfo = '';
		$extrainfo .= '<div id="achievements">
						<ul class="nav nav-tabs">';
			$extrainfo .= '<li class="achievements_tabs tab1">
								<a data-target="#tab_badges" data-toggle="tabajax" href="' . $CFG->wwwroot . '/blocks/achievements/ajax.php?page=3">'.strtoupper(get_string('badges', 'block_achievements')).'</a>
							</li>';
			$extrainfo .='<li class="achievements_tabs tab2 ">
							<a data-target="#tab_points" data-toggle="tabajax" href="' . $CFG->wwwroot . '/blocks/achievements/ajax.php?page=1">'.strtoupper(get_string('points', 'block_achievements')).'</a>
						  </li>';
			
			$extrainfo .= '<li class="achievements_tabs tab3">
							<a data-target="#tab_certificates " data-toggle="tabajax" href="' . $CFG->wwwroot . '/blocks/achievements/ajax.php?page=2">'.strtoupper(get_string('certifications', 'block_achievements')).'</a>
						   </li>';
			
			
		$extrainfo .=' </ul>';
		$extrainfo .='<div id="achievements_response" class="tab-content">
							<div class="tab-pane " id="tab_badges"></div>
							<div class="tab-pane" id="tab_points"></div>
							<div class="tab-pane" id="tab_certificates"></div>
					  </div>';
					  
		$extrainfo .= '</div>';*/
		/*end of #achievements div*/
		/*$extrainfo .= html_writer::script('$( "#achievements" ).tabs({
			      beforeLoad: function( event, ui ) {
			        ui.jqXHR.fail(function() {
			          ui.panel.html(
			            "Couldnot load this tab. We will try to fix this as soon as possible. " +
			            "If this wouldnot be a demo." );
			        });
			      }
			    });');*/
		// $extrainfo .= $certificates->achievements_tab_script();
		//return $extrainfo;
	}


	

	
	
    public function display_achievements($type=false) {
        global $DB, $CFG, $USER,$PAGE;
		
        $certificates_lib = new achievements();
		$output = '';
		$trueorfalse = 'false';
		$data = array();
		$table = new html_table();
		if($type==1){
			$table->id = 'achievementstable1';
			$table->head = array('','');
			$table->width = '100%';
			$table->size = array('80%', '20%');
			$table->align = array('left','center');
			$credits = $certificates_lib->get_user_credits();
			$creditscount = $certificates_lib->get_user_credits(1);

			// $output .= html_writer::script('$("#achievements li.achievements_tabs").removeClass("active");
			// 							   $("#achievements li.tab2").addClass("active");');
			if(!empty($credits)){
				// $output .= "<div class='text-center'>
				// 				<i class='fa fa-star bigfont gold'></i><br/>
				// 				<span class='points name total'>
				// 					<b> ".$creditscount." Points</b>
				// 				</span>
				// 			</div>";
				foreach($credits as $credit){
					$out = array();
					$course = $DB->get_field('course','fullname',array('id'=>$credit->id));
					
					
					if(strlen($course)>30){
						
                
                        $out[]= "<span class='task-bell'><i class='fa fa-star gold'></i></span><span class='task-title-sp'>".substr(strip_tags($course),0,30)."...</span>";
				
                        }else{
                        $out[]= "<span class='task-bell'><i class='fa fa-star gold'></i></span><span class='task-title-sp'>$course</span>";
                        }
				
					$out[]= "<span class='points credits '>".$credit->open_points."&nbspPoints</span>";
					
					$data[] = $out;
				}
				
				if(count($credits) > 5){
					$trueorfalse = 'true';
				}
				
			}
			$emptystring = get_string('nopointsachieved','block_achievements');
			
		}elseif($type==2){
			
			$table->id = 'achievementstable2';
			$table->head = array('','');
			$table->width = '100%';
			$table->size = array('80%', '20%');
			$table->align = array('left','center');
			$certificates = $certificates_lib->get_user_certificates();
			
			// $output .= html_writer::script('$("#achievements li.achievements_tabs").removeClass("active");
			// 							   $("#achievements li.tab3").addClass("active");');
			if(!empty($certificates)){
				// $output .= "<div class='text-center'>
				// 				<i class='fa fa-trophy bigfont gold'></i><br/>
				// 				<span class='certificates total'>
				// 					<b> ".count($certificates)." Certificates</b>
				// 				</span>
				// 			</div>";
				foreach($certificates as $certificate){
					$out = array();

					$url = $CFG->wwwroot."/mod/certificate/view.php?id=$certificate->moduleid&action=get";
					if(strlen($certificate->name)>30){
						$out[]= "<span class='task-bell'><i class='fa fa-trophy gold'></i></span><a href = $url target='_blank' class='task-title-sp'>".substr(strip_tags($certificate->name),0,30)."...</a>";
					}else{
						$out[]= "<span class='task-bell'><i class='fa fa-trophy gold'></i></span><a href = $url target='_blank' class='task-title-sp'>$certificate->name</a>";
					}

					$out[] = "<a href=".$url." target='_blank' class=''>".get_string('download')."</a>";
					$data[] = $out;
				}
				if(count($certificates) > 5){
					$trueorfalse = 'true';
				}
			}
			
			$emptystring = get_string('nocertificatesachieved','block_achievements');
			
		}elseif($type==3){
			
			$content = array();
			if ($badges = badges_get_user_badges($USER->id, null, 0, 6)) {
            	$output1 = $PAGE->get_renderer('core', 'badges');
            	$data[] = $output1->print_badges_list($badges, $USER->id, true);
            	if($extrabadges = badges_get_user_badges($USER->id,null,0,10)){
            		$count = count($extrabadges);
            		if($count>6){
            			// $data[] = '<span class="viewmorebutton">view more</span>';
            			$url = new moodle_url('/blocks/achievements/displayallbadges.php');
            			$data[] = html_writer::link($url, get_string('viewmore','block_achievements'), array('class' => 'viewmorebutton btn'));
             		}
            	}
        	}
        	$emptystring = get_string('nobadgesachieved','block_achievements');
		}
		if(!empty($data)&& $type!=3){
			$table->attributes['class'] = 'achievementsclass';
			$table->data = $data;
			$output .= html_writer::table($table);
			$output .= html_writer::script("$(document).ready(function(){
							$('#achievementstable$type').dataTable({
								'bPaginate' : ". $trueorfalse .",
								'iDisplayLength':2,
								'bLengthChange': false,
								'searching': false,
								'bInfo': false,
								language: {
									paginate: {
									  'previous': '<',
									  'next': '>'
									}
								}
							});
						});");
		}elseif(!empty($data)&&$type==3){
			// $data[] = html_writer::script('$("#achievements li.achievements_tabs").removeClass("active");
			// 							   $("#achievements li.tab1").addClass("active");');
			$output = $data;
			
		}else{
			$output .= '<span class="noacheivements alert alert-info text-center pull-left">'.$emptystring.'</span>';
		}
        return $output;
    }
    
}
