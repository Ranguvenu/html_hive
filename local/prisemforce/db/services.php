<?php 

defined('MOODLE_INTERNAL') || die;
$functions = [
    'local_prisemforce_skillmaster' => [
        'classname' => 'local_prisemforce_external',
		'methodname' => 'skillmaster',
		'classpath' => 'local/prisemforce/classes/external.php',
		'description' => 'Create update skillmaster',
		'type' => 'read',
		'ajax' => true,
    ],
    'local_prisemforce_courses' => [
		'classname' => 'local_prisemforce_external',
		'methodname' => 'get_courses',
		'classpath' => 'local/prisemforce/classes/external.php',
		'description' => 'Get Courses',
		'type' => 'read',
		'ajax' => true,
    ],
    'local_prisemforce_skillgroups' => [
        'classname' => 'local_prisemforce_external',
		'methodname' => 'skillgroups',
		'classpath' => 'local/prisemforce/classes/external.php',
		'description' => 'Skill Category creation and updation',
		'type' => 'read',
		'ajax' => true,
    ],
    'local_prisemforce_insertlog' => [
        'classname' => 'local_prisemforce_external',
		'methodname' => 'insertlog',
		'classpath' => 'local/prisemforce/classes/external.php',
		'description' => 'Inserting log details',
		'type' => 'read',
		'ajax' => true,
    ],
];

$services = [
    'skillmaster_service' => [
        'functions' => [
            'local_prisemforce_skillmaster'
        ],
        'enabled' => 1,
		'shortname' => 'skillmaster'
    ],
    'custom_courselist_service' => [
        'functions' =>array(
            'local_prisemforce_courses'
        ),
        'enabled' => 1,
		'shortname' => 'courselist'
    ],
    'skillgroups_service' => [
        'functions' => [
            'local_prisemforce_skillgroups'
        ],
        'enabled' => 1,
		'shortname' => 'skillgroups'
    ],
    'insertlog_service' => [
        'functions' => [
            'local_prisemforce_insertlog'
        ],
        'enabled' => 1,
		'shortname' => 'insertlog'
    ],
];
