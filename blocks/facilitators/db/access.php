<?php 
$capabilities = array(
 
    'block/facilitators:addinstance' => array(
        'riskbitmask' => RISK_XSS,
 
        'captype' => 'write',
        'contextlevel' => CONTEXT_BLOCK,
        'archetypes' => array(
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        ),
		 
        'clonepermissionsfrom' => 'moodle/site:manageblocks'
    ),
    
    'block/facilitators:myaddinstance' => array(
        'riskbitmask' => RISK_XSS,

        'captype' => 'write',
        'contextlevel' => CONTEXT_BLOCK,
        'archetypes' => array(
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        ),
		 
        'clonepermissionsfrom' => 'moodle/site:facilitators'
    ),
);
?>