<?php
namespace local_ilp\render;
use stdClass;
use templatable;
use html_writer;
use html_table;
use moodle_url;
use DateTime;
class plans {
    
    function financialyear_for_ilp(){
    global $DB;
		$ilpdates = $DB->get_record('config',array('name'=>'ilp'));

		if($ilpdates){
			$value = explode(',',$ilpdates->value);
			$date = new \DateTime;
			$date->setTimestamp($value[1]);
			$date->setTime( 23, 59, 59);
		    $end_date = $date->getTimestamp();
			
			$requiredates = new \stdClass();
			$requiredates->startdate = $value[0];
			$requiredates->enddate = $end_date;

			return $requiredates;
		}else{
			return null;
		}
	}

	function plansview_tabs(){
		global $OUTPUT;
		$data = array();
		$dates = $this->financialyear_for_ilp();
		$data['tab1_title'] =  date('Y',$dates->startdate).'-'.date('Y',$dates->enddate);
		$data['tab2_title'] =  (date('Y',$dates->startdate) - 1).'-'.(date('Y',$dates->enddate) - 1);

		return $OUTPUT->render_from_template('local_ilp/myplans', $data);
	}

    public function actionplans_view($tab = 'presentyear'){
        global $DB, $USER;

        $context = \context_system::instance();

        $sql = "SELECT * 
                FROM {local_ilp} 
                WHERE 1 = 1 ";
        
        if(is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations', $context)){
            $sql .= " ";   
        }else{
            $sql .= " AND  userid = $USER->id ";        
            $dates = $this->financialyear_for_ilp();
            if($dates){
            	if($tab == 'presentyear'){
           			$sql .= " AND  timecreated >= $dates->startdate AND timecreated <= $dates->enddate ";
           		}elseif($tab == 'pastyear'){
           			$laststartyear = strtotime("-1 year", $dates->startdate);
                	$lastendyear = strtotime("-1 year", $dates->enddate);
                	$sql .= " AND  timecreated >= $laststartyear AND timecreated <= $lastendyear";
           		}
            }
        }
        $plans = $DB->get_records_sql($sql);
        if($plans){
        	$data = array();
            $titlestring = get_string('addnew_ilps','local_ilp');
        	foreach ($plans as $plan) {
	        	$row = array();
		        $row[] = $plan->careertrack;
		        if($plan->courseid == -1){
		        	$coursename = $plan->comment;
		        	$credits = '--';
		        }else{
		        	$course = $DB->get_record('course',array('id'=>$plan->courseid),'id,fullname,open_points');
		        	if($course){
		        		$coursename = $course->fullname;
		        		$credits = ($course->open_points) ? $course->open_points : '--';;
		        	}else{
		        		$coursename = '--';
		        		$credits = '--';
		        	}
		        }
		        $row[] = $coursename;
		        $row[] = $credits;
		        $row[] = date('d/m/Y', $plan->targetdate);
		        $row[] = ($plan->completiondate) ? date('d/m/Y', $plan->completiondate) : '--';
		        $delete = "";
                
                $edit = '<a href="javascript:void(0)" title = "Edit" onclick="(function(e){require(\'local_ilp/lpcreate\').init({selector:\'updatelpmodal\', contextid:1, planid:'.$plan->id.', form_status:0 }) })(event)">
                            <i class="fa fa-pencil fa-fw" aria-hidden="true" title="Edit" aria-label="Edit"></i>
                        </a>';

                $delete = '<a href=\'javascript:void(0)\' title = "delete" onclick="(function(e){ require(\'local_ilp/lpcreate\').deleteConfirm({action:\'deleteplan\' , id:'.$plan->id.', name:\''. $coursename.'\' }) })(event)">
                        <i class="fa fa-trash fa-fw" aria-hidden="true" title="delete" aria-label="Delete"></i>
                    </a>';

		        $row[] = $edit.' '.$delete;
		        $data[] = $row;
	        }
	        $table = new html_table();
	        $table->id = 'actionplans_view';
	        $table->width = '100%';
	        $table->head = array(get_string('careertrack','local_users'),get_string('course/comment','local_ilp'),
	        						get_string('credits','local_ilp'),get_string('targetdate','local_ilp'),
	        						get_string('completeddate','local_ilp'),get_string('actions','local_ilp')
	        					);
            $table->align = array('left','center','center','center','center','center');
	        $table->size = array('20%','30%','10%','15%','15%','10%');
	        $table->data = $data;

            $retrundata = html_writer::table($table);
            $retrundata .= html_writer::script('$(document).ready(function(){
                                                $("#actionplans_view").DataTable({});
                                           });');
        }else{
            $retrundata = html_writer::tag('div',get_string('norecordsmsg','local_ilp'),array('class'=>"pull-left w-full alert alert-info text-center"));
        }
        return $retrundata;
    }
}
?>