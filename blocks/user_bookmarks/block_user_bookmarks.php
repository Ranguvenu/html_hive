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
 * User Bookmarks Block page.
 *
 * @package    block
 * @subpackage user_bookmarks
 * Version details
 * @copyright  2012 Moodle
 * @author     Authors of Admin Bookmarks:-
 *               2006 vinkmar
 *               2011 Rossiani Wijaya (updated)
 *             Authors of User Bookmarks This Version:-
 *               2013 Jonas Rueegge
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

/**
 * The user bookmarks block class
 */
class block_user_bookmarks extends block_base {

    /** @var string */
    public $blockname = null;

    /** @var bool */
    protected $contentgenerated = false;

    /** @var bool|null */
    protected $docked = null;

    /**
     * Set the initial properties for the block
     */
    function init() {
        $this->blockname = get_class($this);
        $this->title = get_string('blocktitle', 'block_user_bookmarks');
    }

    /**
     * All multiple instances of this block
     * @return bool Returns false
     */
    function instance_allow_multiple() {
        return false;
    }

    /**
     * Set the applicable formats for this block to all
     * @return array
     */
    function applicable_formats() {
        if (has_capability('moodle/site:config', context_system::instance())) {
            return array('all' => true);
        } else {
            return array('site' => true);
        }
    }

    public function specialization() {
        if(!isset($this->config)){
            $this->config = new stdClass();
            $this->config->title = get_string('blocktitle', 'block_user_bookmarks');
        }   
    }  

    /**
     * Gets the content for this block
     * Needed Strings for Multilingual Support for this function
     * avaiable via get_string(); (@JR2013)
     */
    function get_content() {
        global $CFG, $PAGE, $DB;
        $PAGE->requires->js('/blocks/user_bookmarks/js/javascript_file.js');
        $this->config->title = get_string('blocktitle', 'block_user_bookmarks');
        // First check if we have already generated, don't waste cycles
        if ($this->contentgenerated === true) {
            return $this->content;
        }
        $this->content = new stdClass();
        
        $noscript = '<noscript>'.get_string('error:noscript', 'block_user_bookmarks').'</noscript>';
        $javascript='    <script type="text/javascript"></script>';
        
        if (get_user_preferences('user_bookmarks')) {
            require_once($CFG->libdir.'/adminlib.php');

            $tempbookmarks = explode(',', get_user_preferences('user_bookmarks'));
            /// Accessibility: markup as a list.
            $contents = array();
            foreach($tempbookmarks as $bookmark) {
                //the bookmarks are in the following format- url|title
                //so exploading the bookmark by "|" to get the url and title
                $tempBookmark = explode('|', $bookmark);
                //making the url for bookmark
                $contenturl = new moodle_url($CFG->wwwroot . $tempBookmark[0]);
                //now making a link
                $contentlink = html_writer::link($contenturl, $tempBookmark[1]);
                //this is the url to delete bookmark
                $bookmarkdeleteurl = new moodle_url('/blocks/user_bookmarks/delete.php', array('bookmarkurl'=>$tempBookmark[0], 'sesskey'=>sesskey()));
                //this has the link to delete the bookmark
                $deleteLink = "<a href='$bookmarkdeleteurl'><img alt='" .get_string('deletebookmark', 'block_user_bookmarks')
                  . "' title='" .get_string('deletebookmark', 'block_user_bookmarks')
                  . "' src='$CFG->wwwroot/blocks/user_bookmarks/pix/delete.gif'></a>";
                
                //creating the link to update the title for bookmark
                $editLink = '
    <a style="cursor: pointer;" onClick="updateBookmark(\''
   . $tempBookmark[0].'\', \''.$tempBookmark[1].'\', \''.sesskey().'\', \''.$CFG->wwwroot.'\');">
    <img alt="'.get_string('editbookmark', 'block_user_bookmarks')
       .'" title="'.get_string('editbookmark', 'block_user_bookmarks')
       .'" src="'. $CFG->wwwroot.'/blocks/user_bookmarks/pix/edit.gif">
    </a>';
                //setting layout for the bookmark and its delete and edit buttons
                $contents[] = html_writer::tag('li', $contentlink . " ".$editLink." " . $deleteLink);
                $bookmarks[]= html_entity_decode($tempBookmark[0]);
            }
            $this->content->text = html_writer::tag('ol', implode('', $contents), array('class' => 'list'));
        } else {
            $bookmarks = array();
        }

        $this->content->footer = '';
        $this->page->settingsnav->initialise();
        $node = $this->page->settingsnav->get('root', navigation_node::TYPE_SETTING);
        
        $bookmarkurl    = htmlspecialchars_decode(str_replace($CFG->wwwroot,'',$PAGE->url));
        $bookmarktitle  = $PAGE->title;
        $ctype          = $PAGE->course->open_identifiedas;
        $cid            = $PAGE->course->id;

        if (in_array($bookmarkurl, $bookmarks)) {
            //this prints out the link to unbookmark a page
            $this->content->footer = $javascript . $noscript . '
    <form style="cursor: hand;">
    <a style="cursor: pointer;" onClick="deleteBookmark(\''.$bookmarkurl.'\', \''.sesskey().'\', \''.$CFG->wwwroot.'\');"> ('
     .get_string('deletebookmarkthissite', 'block_user_bookmarks'). ') </a>
    </form>';
        } else {
            //this prints out link to bookmark a page
            $this->content->footer = $javascript . '
    <form>
    <a style="cursor: pointer;" onClick="addBookmark(\''.$bookmarkurl.'\', \''.$bookmarktitle.'\',  \''.$ctype.'\', \''.$cid.'\', \''.sesskey().'\', \''.$CFG->wwwroot.'\');">' 
        . get_string('bookmarkpage', 'block_user_bookmarks'). '</a>
    </form>';
        }
    //    return $this->content;
    }
}

