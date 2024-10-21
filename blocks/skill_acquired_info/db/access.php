<?php 
$capabilities = array(
 
    'block/skill_acquired_info:addinstance' => array(
        'riskbitmask' => RISK_XSS,
 
        'captype' => 'write',
        'contextlevel' => CONTEXT_BLOCK,
        'archetypes' => array(
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        ),
		 
        'clonepermissionsfrom' => 'moodle/site:manageblocks'
    ),
    
    'block/skill_acquired_info:myaddinstance' => array(
        'riskbitmask' => RISK_XSS,

        'captype' => 'write',
        'contextlevel' => CONTEXT_BLOCK,
        'archetypes' => array(
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        ),
		 
        'clonepermissionsfrom' => 'moodle/site:skill_acquired_info'
    ),
);
?>