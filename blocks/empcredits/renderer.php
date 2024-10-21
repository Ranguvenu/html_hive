<?php
require_once($CFG->dirroot . '/blocks/empcredits/lib.php');
require_once($CFG->dirroot . '/local/externalcertificate/lib.php');
class block_empcredits_renderer extends plugin_renderer_base
{

	public function get_empcredits_credits()
	{
		$data = '';
		$data = html_writer::start_tag('div', array('id' => 'emp_credits', 'class' => 'nps_count'));
		$data .= html_writer::tag('h4', 'Learning Credits');
		$text = '<i class="fa fa-fighter-jet" aria-hidden="true"></i>
				<i style="font-family: serif;">
					Click on the learning credits count below for annual credit achievement towards learning goals
				</i>';
		$data .= html_writer::tag('div', $text, array());
		$mycredits = get_mycourses_credits_sum(true);
		$url = new moodle_url('/blocks/empcredits/mycourses_info.php', array());
		$mycreditslink = html_writer::link($url, round($mycredits), array('id' => 'credits'));
		$data .= html_writer::tag('span', $mycreditslink, array('id' => 'fs_credits', 'class' => 'fs_credits'));

		$data .= html_writer::end_tag('div');
		return $data;
	}

	//=========get completed course view=========== 
	function get_courses_view($info)
	{

		global $DB;
		if ($info) {
			$myresults = get_courses_info($data = true);
		} else {
			$myresults = get_courses_info($data = false);
		}
		if ($myresults) {
			$table = new html_table();
			$table->width = '100%';

			if ($info) {
				$table->id = 'coursesinfo';
				$action = 'lastoneyearccdata';
			} else {
				$table->id = 'allcoursesinfo';
				$action = 'allccdata';
			}

			$downloadurl = new moodle_url('/blocks/empcredits/export.php', array('tableid' => $action));
			$text =   html_writer::link($downloadurl, '', array('class' => 'fa fa-download pull-right'));

			$table->attributes['class'] = 'completedcoursesinfo';
			// if($info){
			$table->head = array('Name of Course/Certificate ', 'Category', 'LearningType', 'CourseProvider', 'Grade', 'DateofEnrolment', 'ScoreAchieved', 'SkillCategory', 'SkillsAchieved', 'CompletionDate');
			$table->align = array('left', 'center', 'center', 'center', 'center');
			// }else{
			// 	$table->head = array('Module Name ', 'Module type', 'Category' , 'Completion Date');
			// 	$table->align = array('left','center','center', 'center');
			// }
			foreach ($myresults as $record) {
				$data = array();

				if ($record->moduletypeshort == 'learning_path') {
					$url = new moodle_url('/local/learningplan/view.php', array('id' => $record->id));
					$data[] = html_writer::link($url, $record->name, array('target' => '_blank'));
				} else {
					$url = new moodle_url('/course/view.php', array('id' => $record->id));
					$data[] = html_writer::link($url, $record->fullname, array('target' => '_blank'));
				}
				$data[] = ($record->category) ? $record->category : 'N/A';
				$data[] = $record->moduletype;
				$data[] = ($record->course_provider) ? $record->course_provider : 'N/A';
				$data[] = ($record->open_grade) ? $record->open_grade : 'N/A';
				$data[] = ($record->enroleddate) ? date('d/M/Y',$record->enroleddate) : 'N/A';
				$data[] = ($record->finalgrade) ? $record->finalgrade : 'N/A';
				$data[]  = ($record->skillcategory) ? $record->skillcategory : 'N/A';
				$data[]  = ($record->skill) ? $record->skill : 'N/A';
				
				$data[] = date('d/m/Y', $record->timecompleted);

				$table->data[] = $data;
			}
			/* 
			if($info){
				$totalcredits = get_mycourses_credits_sum($data = true);
				$table->data[] = array('', '','','', '<b>Total Credits</b>', '<b>'.$totalcredits.'</b>');
				
			} */

			$script = html_writer::script("$('#$table->id').dataTable({
  												'ordering': false
											});");
			$content = html_writer::table($table) . $script;
		} else {
			$content = html_writer::tag('div', get_string('emptymsg', 'block_empcredits'), array('id' => 'emptymsg'));
		}
		//$text = '';
		/*if($info){
			$text .= $this->currentfinancialyeartabtext();
		}*/
		$text .= $content;

		return $text;
	}

	function currentfinancialyeartabtext()
	{
		$text = "<div class='info'>
				<ul>
					<li>Annual target of 6 credits to be completed in FY 21-22</li>
					<li>Last date to complete the learning goals for FY 21-22 is  1st Mar, 2022</li>
					<li>A maximum of 2 credits can be earned from Life skills and the remaining credits should be earned from Technical skills (overachievement under life skills would not be counted)</li>
					<li>New joiners will have an annual goal of 6 or 4 or 2 or 0 credits depending on which quarter you join Fractal</li>
					<li>
					Technical skills should be picked based on the career track. Please align with your manager and pick the relevant course. Refer the Learning Grid <a href='https://sway.office.com/AYudemIg0x9APRhZ?ref=Link' target='_blank'>https://sway.office.com/AYudemIg0x9APRhZ?ref=Link</a>
					</li>
				<ul>
				</div>";

		return $text;
	}
	//===========facilitator view==============
	function get_facilitator_view($info)
	{
		global $DB, $USER;
		if ($info) {
			$myfacilitator = get_facilitator_info($data = true);
		} else {
			$myfacilitator = get_facilitator_info($data = false);
		}
		if ($myfacilitator) {
			$table = new html_table();
			$table->id = 'facilitatorinfo';
			$table->head = array('Course Name ', 'Content Type', 'Course Type', 'Created Date', 'Completion Date', 'Credits');
			$table->align = array('left', 'center', 'center', 'center', 'center', 'center');
			foreach ($myfacilitator as $facilitator) {
				$coursename = $facilitator->fullname;
				//=======for content type=============
				$contenttype = $facilitator->contenttype;
				if ($contenttype == 1) {
					$content = get_string('project', 'block_empcredits');;
				} elseif ($contenttype == 2) {
					$content = get_string('classroom', 'block_empcredits');
				} elseif ($contenttype == 3) {
					$content = get_string('eLearning', 'block_empcredits');
				} elseif ($contenttype == 4) {
					$content = get_string('others', 'block_empcredits');
				} elseif ($contenttype == 5) {
					$content = get_string('classroomdelivery', 'block_empcredits');
				} else {
					$content = get_string('null', 'block_empcredits');
				}

				//================type=================																				
				if ($facilitator->identifiedas == 1) {
					$type = get_string('mooc', 'block_empcredits');
				} elseif ($facilitator->identifiedas == 2) {
					$type = get_string('ilt', 'block_empcredits');
				} elseif ($facilitator->identifiedas == 3) {
					$type = get_string('elearning', 'block_empcredits');
				}
				$timecreated = date('d/m/Y', $facilitator->timecreated);
				//==========from course_completion table=======================
				$sql = "SELECT * FROM {course_completions}
						WHERE course = $facilitator->courseid
						AND userid = $USER->id AND timecompleted IS NOT NULL";
				$time = $DB->get_record_sql($sql);
				if ($time) {
					$completion = date('m/d/Y', $time->timecompleted);
				} else {
					$completion = get_string('notcompleted', 'block_empcredits');
				}

				$credits = $facilitator->percentage;
				$table->data[] = array($coursename, $content, $type, $timecreated, $completion, $credits);
			}

			if ($info) {
				$fc_total = facilitator_credits_sum($data = true);
			} else {
				$fc_total = facilitator_credits_sum($data = false);
			}
			$table->data[] = array('', '', '', '', '<b>Total Credits</b>', '<b>' . $fc_total . '</b>');
			return html_writer::table($table);
		} else {
			return html_writer::tag('div', get_string('emptymsg', 'block_empcredits'), array('id' => 'emptymsg'));
		}
	}
	function ilp_strartend_view_table()
	{
		global $DB, $OUTPUT;
		$rec = get_ilp_strartend_records();
		$table = new html_table();
		$table->width = '100%';
		$table->head = array('Start Date', 'End Date', 'Actions');
		foreach ($rec as $records) {
			$id = $records->id;
			// $name=strtoupper($records->name);
			$value = array();
			$value = explode(',', $records->value);
			$value_start = date('d/m/Y', $value[0]);
			$value_end = date('d/m/Y', $value[1]);

			$editurl = new moodle_url('/blocks/empcredits/ilp_startend.php', array('id' => $id));
			$actions = html_writer::link($editurl, 'Edit', array());
			$editiconurl = $OUTPUT->pix_url('i/settings');
			$editicon = html_writer::empty_tag('img', array('src' => $editiconurl));
			$actions = html_writer::link($editurl, $editicon, array());

			$table->data[] = array($value_start, $value_end, $actions);
		}
		return html_writer::table($table);
	}


	function get_certificates_view($info)
	{

		$filtervalues =  json_encode(array());
		$stable = new \stdClass();
		$stable->thead = false;
		$myresults = get_listof_internal_certificates($stable, $filtervalues);

		if ($myresults['totalrecords'] > 0) {
			$table = new html_table();
			$table->id = 'certificatesinfo';
			$table->width = '100%';
			$table->attributes['class'] = 'completedcoursesinfo';

			$table->head = array('Name of Course/Certificate', 'Learning Type', 'Skills achieved', 'Upload Date', 'Approved Date', 'Download Certificate');
			$table->align = array('left', 'center', 'center', 'center', 'center', 'center');

			foreach ($myresults['result'] as $record) {
				$data = array();
				if ($record['learningtype'] != 'External') {
					$url = new moodle_url('/course/view.php', array('id' => $record['id']));
					$data[] = html_writer::link($url, $record['coursename'], array('target' => '_blank'));
				} else {
					$data[] = $record['coursename'];
				}
				$data[] = $record['learningtype'];
				$data[]  = $record['skill'];
				$data[]  =  $record['uploadeddate'];
				$data[]  = ($record['approveddate']) ?  $record['approveddate'] : 'N/A';
				$data[] = html_writer::link($record['imageurl'], get_string('download'), array('target' => '_blank'));
				$table->data[] = $data;
			}

			$script = html_writer::script("$('#$table->id').dataTable({
  												'ordering': false
											});");
			$content = html_writer::table($table) . $script;
		} else {
			$content = html_writer::tag('div', get_string('emptymsg', 'block_empcredits'), array('id' => 'emptymsg'));
		}
		$text = '';

		$text .= $content;

		return $text;
	}
	function get_courses_info_tabs($data, $stable)
	{
		global $DB, $USER;
		$concatsql = '';$concatsql1 ='';$concatsql2 ='';$concatsql3 ='';$concatsql4 ='';
		$systemcontext = \context_system::instance();

		$countsql = "SELECT COUNT(DISTINCT(tbl.fid)) ";
		$value = ilp_startend_dates();
		$sql="SELECT DISTINCT(tbl.fid), tbl.* ";
				
		$fromsql = " FROM (SELECT concat('Course_',c.id) as fid,c.id as id,c.fullname as fullname,c.open_points,c.duration,
					cat.name as category, ct.course_type as moduletype,ct.shortname as moduletypeshort, c.open_courseprovider as course_provider,c.open_grade,cc.timecompleted as timecompleted,
					c.open_skill as skill, c.open_skillcategory as skillcategory,
					(SELECT MAX(ue.timecreated)  FROM {user_enrolments} ue LEFT JOIN {enrol} e ON (ue.enrolid = e.id AND ue.userid=$USER->id)
					WHERE c.id = e.courseid) AS enroleddate 
					FROM {course} c					
					JOIN {course_categories} cat ON cat.id = c.category
					JOIN {course_completions} cc ON cc.course=c.id
					JOIN {local_course_types} ct ON ct.id = c.open_identifiedas  			        
					WHERE cc.userid = $USER->id AND cc.timecompleted IS NOT NULL  ";
		
		/* if ($data) {
			$fromsql .= "AND cc.timecompleted >= $value->startdate AND cc.timecompleted <= $value->enddate ";
		} */
		
		$fromsql .= " UNION  ";
	  
		$fromsql .= " SELECT concat('Learningplan_',lp.id) as fid,lp.id as id, lp.name as fullname, lp.open_points, NULL as category, 'Learning Path' as moduletype,'learning_path' as moduletypeshort,null as course_provider, lp.open_grade,NULL as duration,
					lpu.completiondate as timecompleted,NULL as skill, NULL as skillcategory ,lpu.timecreated as enroleddate
					FROM {local_learningplan_user} lpu
					JOIN {local_learningplan} lp ON lp.id = lpu.planid
					WHERE lpu.userid = $USER->id AND lpu.completiondate IS NOT NULL";

		/* if ($data) {
			$fromsql .= " AND lpu.completiondate >= $value->startdate AND lpu.completiondate <= $value->enddate ";
		} */

		$fromsql .= " AND lpu.status = 1";
		$fromsql .= " ) As tbl ";
		$fromsql .= " WHERE tbl.id > 0 ";
		if ($data) {
			$fromsql .= " AND tbl.timecompleted >= $value->startdate AND tbl.timecompleted <= $value->enddate ";
		} 
		$filterdata = (array) $stable->filterdata;

		if($filterdata['fromdate[year]'] && $filterdata['todate[year]']){ 
			
			$from_year=$filterdata['fromdate[year]'];
			$from_month=$filterdata['fromdate[month]'];
			$from_day=$filterdata['fromdate[day]'];
	
			$filter_fromdate=mktime(0, 0, 0, $from_month, $from_day, $from_year) ; 
	
			$to_year=$filterdata['todate[year]'];
			$to_month=$filterdata['todate[month]'];
			$to_day=$filterdata['todate[day]'];
	
			$filter_todate=mktime(0, 0, 0, $to_month, $to_day, $to_year);
			
			$fromsql .=" AND tbl.timecompleted BETWEEN :filter_fromdate AND :filter_todate ";
			$params['filter_fromdate'] = $filter_fromdate;
			$params['filter_todate'] = $filter_todate;
		
		}else if($filterdata['fromdate[year]']){        
			$from_year=$filterdata['fromdate[year]'];
			$from_month=$filterdata['fromdate[month]'];
			$from_day=$filterdata['fromdate[day]'];
	
			$filter_fromdate=mktime(0, 0, 0, $from_month, $from_day, $from_year) ;        
			$fromsql .=" AND tbl.timecompleted >= :filter_fromdate ";
			$params['filter_fromdate'] = $filter_fromdate;
		} 

		
		if (!empty($stable->search)) {
			$fields = array(
				0 => 'tbl.fullname',
			);
			$fields = implode(" LIKE '%" . $stable->search . "%' OR ", $fields);
			$fields .= " LIKE '%" . $stable->search . "%' ";
			$fromsql .= " AND ($fields) ";
		}
		
		$fromsql .= " ORDER BY tbl.id DESC "; 
	
		$recordcount = $DB->count_records_sql($countsql . $fromsql,$params);
		if($stable->thead){
			$results = $DB->get_records_sql($sql . $fromsql,$params,$stable->start, $stable->length ); 
		}else{
			$results = $DB->get_records_sql($sql . $fromsql,$params ); //for export
		}
		return compact('results', 'recordcount');

	}

	function get_certificates_view_tabs($stable, $filtervalues)
	{
		
		$myresults = get_listof_internal_certificates($stable, $filtervalues);
		
		$recordcount = array();
		$totalcount = $myresults['totalrecords'];
		foreach ($myresults['result'] as $record) {
			$data = array();
			if ($record['learningtype'] != 'External') {
				$url = new moodle_url('/course/view.php', array('id' => $record['id']));
				$data['name'] = html_writer::link($url, $record['coursename'], array('target' => '_blank'));
			} else {
				$data['name'] = $record['coursename'];
			}
			$data['learningtype'] = $record['learningtype'];
			$data['skill']  = $record['skill'];
			$data['uploadeddate']  =  $record['uploadeddate'];
			$data['approveddate']  = ($record['approveddate']) ?  $record['approveddate'] : 'N/A';
			//$data[] = $record['imageurl'];
			$data['download'] = html_writer::link($record['imageurl'], get_string('download'), array('target' => '_blank'));
			$recordcount[] = $data;
		}
		return array('results' => $recordcount, 'recordcount' => $totalcount);
	}

	public function display_tabcompletedcourses($filter = false)
	{
		$systemcontext = context_system::instance();

		$options = array('targetID' => 'allccdata_tabdata', 'perPage' => 5, 'cardClass' => 'tableformat', 'viewType' => 'table');
		$options['methodName'] = 'block_empcredits_learninganalytics_allccdata';
		$options['templateName'] = 'block_empcredits/learninganalyticstabs_allccdata';

		$options = json_encode($options);

		$filterdata = json_encode(array());
		$dataoptions = json_encode(array('contextid' => $systemcontext->id));
		$context = [
			'targetID' => 'allccdata_tabdata',
			'options' => $options,
			'dataoptions' => $dataoptions,
			'filterdata' => $filterdata
		];

		if ($filter) {
			return  $context;
		} else {
			return  $this->render_from_template('local_costcenter/cardPaginate', $context);
		}
	}
}
