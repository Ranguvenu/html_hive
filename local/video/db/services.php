<?php
 defined('MOODLE_INTERNAL') || die();
$functions = array(

	 'local_video_show' => array( // local_PLUGINNAME_FUNCTIONNAME is the name of the web service function that the client will call.
                'classname'   => 'local_video_external', // create this class in componentdir/classes/external
                'classpath'   => 'local/video/classes/external.php',
                'methodname'  => 'show', // implement this function into the above class
                'description' => 'This documentation will be displayed in the generated API documentationAdministration > Plugins > Webservices > API documentation)',
                'type'        => 'write', // the value is 'write' if your function does any database change, otherwise it is 'read'.
                'ajax'        => true, // true/false if you allow this web service function to be callable via ajax

        )
);
