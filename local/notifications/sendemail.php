<?php 
    require_once(dirname(__FILE__) . '/../../config.php');
    global $CFG;    
    require_once($CFG->dirroot.'/local/notifications/notification.php');
    $emails = new \notification_triger(false);
    $emails->send_emaillog_notifications();