<?php

require('../../config.php');

require_login();
$context = context_system::instance();
$PAGE->set_context($context);

if ($bookmarkurl = htmlspecialchars_decode($_GET["bookmarkurl"]) and $userid = $_GET["userid"] and $cid = $_GET["cid"] ) {

    $bookmarkurl = htmlspecialchars_decode(str_replace($CFG->wwwroot,'',$bookmarkurl));
    // for checking all bookmarks of login user...
    if ($record = $DB->get_record('block_custom_userbookmark', array('userid' => $USER->id , 'url' => $bookmarkurl, 'courseid' => $cid), '*', IGNORE_MULTIPLE)) {
      
        $delete = $DB->delete_records('block_custom_userbookmark',array('userid' => $USER->id, 'url' => $bookmarkurl, 'courseid' => $cid));

        // Go back to index.php page
        if ($delete == true) {
          //  redirect($CFG->wwwroot, get_string('remove_noti', 'block_user_bookmarks'), null, \core\output\notification::NOTIFY_SUCCESS);
           header('Location: ' . $_SERVER["HTTP_REFERER"] );
           exit;
        } else {
            print_error(get_string('error:nobookmarksforuser', 'block_user_bookmarks'), 'admin');
            die;
        }
    } else {
        print_error(get_string('error:nobookmarksforuser', 'block_user_bookmarks'), 'admin');
        die;
    }


    print_error(get_string('error:nobookmarksforuser', 'block_user_bookmarks'), 'admin');
    die;

} else {
    print_error(get_string('error:invalidsection', 'block_user_bookmarks'), 'admin');
    die;
}






// old and original code.....
    // if (get_user_preferences('user_bookmarks')) {

    //     $bookmarks = explode(',', get_user_preferences('user_bookmarks'));

    //     $bookmarkremoved = false;

    //     foreach($bookmarks as $bookmark) {
    //         $tempBookmark = explode('|', $bookmark);
    //         if ($tempBookmark[0] == $bookmarkurl) {
    //             $keyToRemove = array_search($bookmark, $bookmarks);
    //             unset($bookmarks[$keyToRemove]);
    //             $bookmarkremoved = true;
    //         }
    //     }
        
    //     if ($bookmarkremoved == false) {
    //          print_error(get_string('error:nonexistentbookmark', 'block_user_bookmarks'), 'admin');
    //         die;
    //     }
        
    //     $bookmarks = implode(',', $bookmarks);
    //     set_user_preference('user_bookmarks', $bookmarks);
        
    //     global $CFG;
    //     header("Location: " . $CFG->wwwroot . $bookmarkurl);
    //     die;
    // }