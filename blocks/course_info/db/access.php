<?php 
$capabilities = array(
 
    'block/course_info:addinstance' => array(
        'riskbitmask' => RISK_XSS,
 
        'captype' => 'write',
        'contextlevel' => CONTEXT_BLOCK,
        'archetypes' => array(
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        ),
		 
        'clonepermissionsfrom' => 'moodle/site:manageblocks'
    ),
    
    'block/course_info:myaddinstance' => array(
        'riskbitmask' => RISK_XSS,

        'captype' => 'write',
        'contextlevel' => CONTEXT_BLOCK,
        'archetypes' => array(
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        ),
		 
        'clonepermissionsfrom' => 'moodle/site:course_info'
    ),
);
?>