<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Version details
 *
 * @package    block
 * @subpackage block_user_bookmarks
 * @copyright  Jonas R�egge
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');

require_login();
$context = context_system::instance();
$PAGE->set_context($context);

global $CFG, $DB, $USER;

if ($bookmarkurl = htmlspecialchars_decode($_GET["bookmarkurl"]) and $title = $_GET["title"] and $ctype = $_GET["ctype"] and $cid = $_GET["cid"] ) {
    $bookmarkurl = htmlspecialchars_decode(str_replace($CFG->wwwroot,'',$bookmarkurl));
    $description = $DB->get_record_sql("SELECT summary FROM {course} WHERE id = $cid");
    $description = $description->summary;


    // for checking all bookmarks of login user...
    if ($records = $DB->get_record('block_custom_userbookmark', array('userid' => $USER->id , 'url' => $bookmarkurl), '*', IGNORE_MULTIPLE)) {
            print_error(get_string('error:bookmarkalreadyexists', 'block_user_bookmarks'), 'admin');
            die;
    } else {
        $createrec                  = new \stdClass();
        $createrec->userid          = $USER->id;
        $createrec->url             = $bookmarkurl;
        $createrec->title           = $title;
        $createrec->courseid        = $cid;
        $createrec->learningtype    = $ctype;
        $createrec->description     = $description;

        // print_object($createrec); die;
        $insertrecord = $DB->insert_record('block_custom_userbookmark', $createrec);

        // Go back to index.php page
        //redirect($CFG->wwwroot, 'Your Bookmark successfully added.', null, \core\output\notification::NOTIFY_SUCCESS);
        if ($insertrecord == true) {
            header('Location: ' . $_SERVER["HTTP_REFERER"] );
            exit;
        }
    }

} else {
    print_error(get_string('error:invalidsection', 'block_user_bookmarks'), 'admin');
    die;
}




// old and original code.....
    // if (get_user_preferences('user_bookmarks')) {
    //     $bookmarks = explode(',', get_user_preferences('user_bookmarks'));
        
    //     if (in_array(($bookmarkurl . "|" . $title ."|" .$ctype ."|" .$cid), $bookmarks)) {
    //         print_error(get_string('error:bookmarkalreadyexists', 'block_user_bookmarks'), 'admin');
    //         die;
    //     }

    // } else {
    //     $bookmarks = array();
    // }

    // //adds the bookmark at end of array
    // $bookmarks[] = $bookmarkurl . "|" . $title ."|" .$ctype ."|" .$cid;
    // $bookmarks = implode(',', $bookmarks);
    
    // //adds to preferences table
    // set_user_preference('user_bookmarks', $bookmarks);
    
    // global $CFG;
    // header("Location: " . $CFG->wwwroot . $bookmarkurl);