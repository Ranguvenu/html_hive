<?php 

defined('MOODLE_INTERNAL') || die;
$functions = array(
	'local_fmsapi_search_course' => array(
		'classname' => 'local_fmsapi_external',
		'methodname' => 'get_coursedetails',
		'classpath' => 'local/fmsapi/classes/external.php',
		'description' => 'Get Course details',
		'type' => 'read',
		'ajax' => true,
	),
	'local_fmsapi_skills' => array(
		'classname' => 'local_fmsapi_external',
		'methodname' => 'get_skills',
		'classpath' => 'local/fmsapi/classes/external.php',
		'description' => 'Load Skills',
		'type' => 'read',
		'ajax' => true,
	),
	'local_fmsapi_course_info' => array(
		'classname' => 'local_fmsapi_external',
		'methodname' => 'get_courseinfo',
		'classpath' => 'local/fmsapi/classes/external.php',
		'description' => 'Get Course info',
		'type' => 'read',
		'ajax' => true,
	),
	'local_fmsapi_courses' => array(
		'classname' => 'local_fmsapi_external',
		'methodname' => 'get_courses',
		'classpath' => 'local/fmsapi/classes/external.php',
		'description' => 'Get Courses',
		'type' => 'read',
		'ajax' => true,
	),
	'local_fmsapi_course_enrolment' => array(
		'classname' => 'local_fmsapi_external',
		'methodname' => 'course_enrolment',
		'classpath' => 'local/fmsapi/classes/external.php',
		'description' => 'enrolment to course',
		'type' => 'read',
		'ajax' => true,
	),
	'local_fmsapi_quiz_information' => array(
		'classname' => 'local_fmsapi_external',
		'methodname' => 'quiz_information',
		'classpath' => 'local/fmsapi/classes/external.php',
		'description' => 'quiz activity information',
		'type' => 'read',
		'ajax' => true,
	),
	'local_fmsapi_course_unenrolment' => array(
		'classname' => 'local_fmsapi_external',
		'methodname' => 'course_unenrolment',
		'classpath' => 'local/fmsapi/classes/external.php',
		'description' => 'user unenroll to the course',
		'type' => 'read',
		'ajax' => true,
	),
	'local_fmsapi_custom_course_complete' => array(
		'classname' => 'local_fmsapi_external',
		'methodname' => 'custom_course_complete',
		'classpath' => 'local/fmsapi/classes/external.php',
		'description' => 'user unenroll to the course',
		'type' => 'read',
		'ajax' => true,
	),
	
);

$services = array(
    'custom_coursesearch_service' => array(
        'functions' =>array(
            'local_fmsapi_search_course'
        ),
        'enabled' => 1,
		'shortname' => 'custom_course_search'
    ) ,
	'custom_skills_service' => array(
        'functions' =>array(
            'local_fmsapi_skills'
        ),
        'enabled' => 1,
		'shortname' => 'get_skills'
    ) ,
	'custom_courses_service' => array(
        'functions' =>array(
            'local_fmsapi_courses'
        ),
        'enabled' => 1,
		'shortname' => 'get_courses'
    ) ,
	'custom_course_enrolment_service' => array(
        'functions' =>array(
            'local_fmsapi_course_enrolment'
        ),
        'enabled' => 1,
		'shortname' => 'course_enrolment'
    ) ,
	'custom_quiz_information_service' => array(
        'functions' =>array(
            'local_fmsapi_quiz_information'
        ),
        'enabled' => 1,
		'shortname' => 'quiz_information'
    ) ,
	'custom_course_unenrolment_service' => array(
        'functions' =>array(
            'local_fmsapi_course_unenrolment'
        ),
        'enabled' => 1,
		'shortname' => 'course_unenrolment'
    ),
	'custom_course_complete_service' => array(
        'functions' =>array(
            'local_fmsapi_custom_course_complete'
        ),
        'enabled' => 1,
		'shortname' => 'course_complete'
    ),
	
);
