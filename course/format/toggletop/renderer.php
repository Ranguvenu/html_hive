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
 * Collapsed Topics Information
 *
 * A topic based format that solves the issue of the 'Scroll of Death' when a course has many topics. All topics
 * except zero have a toggle that displays that topic. One or more topics can be displayed at any given time.
 * Toggles are persistent on a per browser session per course basis but can be made to persist longer by a small
 * code change. Full installation instructions, code adaptions and credits are included in the 'Readme.txt' file.
 *
 * @package    course/format
 * @subpackage toggletop
 * @version    See the value of '$plugin->version' in version.php.
 * @copyright  &copy; 2012-onwards G J Barnard in respect to modifications of standard topics format.
 * @author     G J Barnard - {@link http://moodle.org/user/profile.php?id=442195}
 * @link       http://docs.moodle.org/en/Collapsed_Topics_course_format
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License
 *
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/format/renderer.php');
require_once($CFG->dirroot . '/course/format/toggletop/lib.php');
use core_completion\progress;
use core_courseformat\output\section_renderer; //Add for section <revathi>
class format_toggletop_renderer extends section_renderer {

    protected $tccolumnwidth = 100; // Default width in percent of the column(s).
    protected $tccolumnpadding = 0; // Default padding in pixels of the column(s).
    protected $mobiletheme = false; // As not using a mobile theme we can react to the number of columns setting.
    protected $tablettheme = false; // As not using a tablet theme we can react to the number of columns setting.
    protected $courseformat = null; // Our course format object as defined in lib.php;
    protected $tcsettings; // Settings for the format - array.
    protected $defaulttogglepersistence; // Default toggle persistence.
    protected $defaultuserpreference; // Default user preference when none set - bool - true all open, false all closed.
    protected $togglelib;
    protected $currentsection = false; // If not false then will be the current section number.
    protected $isoldtogglepreference = false;
    protected $userisediting = false;
    protected $tctoggleiconsize;
    protected $formatresponsive;
    protected $rtl = false;
    protected $bsnewgrid = false;

    /**
     * Constructor method, calls the parent constructor - MDL-21097.
     *
     * @param moodle_page $page.
     * @param string $target one of rendering target constants.
     */
    public function __construct(moodle_page $page, $target) {
        parent::__construct($page, $target);
        $this->togglelib = new \format_toggletop\togglelib;
        $this->courseformat = course_get_format($page->course); // Needed for collapsed topics settings retrieval.

        /* Since format_toggletop_renderer::section_edit_control_items() only displays the 'Set current section' control when editing
          mode is on we need to be sure that the link 'Turn editing mode on' is available for a user who does not have any
          other managing capability. */
        $page->set_other_editing_capability('moodle/course:setcurrentsection');

        $this->userisediting = $page->user_is_editing();
        $this->tctoggleiconsize = clean_param(get_config('format_toggletop', 'defaulttoggleiconsize'), PARAM_TEXT);
        $this->formatresponsive = get_config('format_toggletop', 'formatresponsive');

        $this->rtl = right_to_left();

        if (strcmp($page->theme->name, 'boost') === 0) {
            $this->bsnewgrid = true;
        }
    }

    /**
     * Generate the starting container html for a list of sections.
     * @return string HTML to output.
     */
    protected function start_section_list() {
        if ($this->bsnewgrid) {
            return html_writer::start_tag('ul', array('class' => 'ctopics bsnewgrid'));
        } else {
            return html_writer::start_tag('ul', array('class' => 'ctopics'));
        }
    }

    /**
     * Generate the starting container html for a list of sections when showing a toggle.
     * @return string HTML to output.
     */
    protected function start_toggle_section_list() {
        global $CFG, $DB; 
        $classes = 'ctopics topics';
        if ($this->bsnewgrid) {
            $classes .= ' bsnewgrid';
        }
        $attributes = array();
        if (($this->mobiletheme === true) || ($this->tablettheme === true)) {
            $classes .= ' ctportable';
        }
        if ($this->formatresponsive) {
            $style = '';
            if ($this->tcsettings['layoutcolumnorientation'] == 1) { // Vertical columns.
                $style .= 'width:' . $this->tccolumnwidth . '%;';
            } else {
                $style .= 'width: 100%;';  // Horizontal columns.
            }
            if ($this->mobiletheme === false) {
                $classes .= ' ctlayout';
            }
            $style .= ' padding-left: ' . $this->tccolumnpadding . 'px; padding-right: ' . $this->tccolumnpadding . 'px;';
            $attributes['style'] = $style;
        } else {
            if ($this->tcsettings['layoutcolumnorientation'] == 1) { // Vertical columns.
                $classes .= ' ' . $this->get_column_class($this->tcsettings['layoutcolumns']);
            } else {
                $classes .= ' ' . $this->get_row_class();
            }
        }
        $attributes['class'] = $classes;

        return html_writer::start_tag('ul', $attributes);
    }

    /**
     * Generate the closing container html for a list of sections.
     * @return string HTML to output.
     */
    protected function end_section_list() {
        return html_writer::end_tag('ul');
    }

    /**
     * Generate the title for this section page.
     * @return string the page title.
     */
    protected function page_title() {
        return get_string('sectionname', 'format_toggletop');
    }

    /**
     * Generate the content to displayed on the right part of a section
     * before course modules are included.
     *
     * @param stdClass $section The course_section entry from DB.
     * @param stdClass $course The course entry from DB.
     * @param bool $onsectionpage true if being printed on a section page.
     * @return string HTML to output.
     */
    protected function section_right_content($section, $course, $onsectionpage) {
        $o = '';

        if ($section->section != 0) {
            $controls = $this->section_edit_control_items($course, $section, $onsectionpage);
            if (!empty($controls)) {
                $o .= $this->section_edit_control_menu($controls, $course, $section);
            } else if (!$onsectionpage) {
                if (empty($this->tcsettings)) {
                    $this->tcsettings = $this->courseformat->get_settings();
                }
                $url = new moodle_url('/course/view.php', array('id' => $course->id, 'section' => $section->section));
                // Get the specific words from the language files.
                $topictext = null;
                if (($this->tcsettings['layoutstructure'] == 1) || ($this->tcsettings['layoutstructure'] == 4)) {
                    $topictext = get_string('setlayoutstructuretopic', 'format_toggletop');
                } else if (($this->tcsettings['layoutstructure'] == 2) || ($this->tcsettings['layoutstructure'] == 3)) {
                    $topictext = get_string('setlayoutstructureweek', 'format_toggletop');
                } else {
                    $topictext = get_string('setlayoutstructureday', 'format_toggletop');
                }
                $title = get_string('viewonly', 'format_toggletop', array('sectionname' => $topictext.' '.$section->section));
                switch ($this->tcsettings['layoutelement']) { // Toggle section x.
                    case 1:
                    case 3:
                    case 5:
                    case 8:
                        // $o .= html_writer::link($url,
                        //     $topictext.html_writer::empty_tag('br').
                        //     $section->section, array('title' => $title, 'class' => 'cps_centre'));
                        $o .= '';
                        break;
                    default:
                        // $o .= html_writer::link($url,
                        //     $this->output->pix_icon('one_section', $title, 'format_toggletop'),
                        //     array('title' => $title, 'class' => 'cps_centre'));
                        $o .= '';
                        break;
                }
            }
        }

        return $o;
    }

    /**
     * Generate the content to displayed on the left part of a section
     * before course modules are included.
     *
     * @param stdClass $section The course_section entry from DB.
     * @param stdClass $course The course entry from DB.
     * @param bool $onsectionpage true if being printed on a section page.
     * @return string HTML to output.
     */
    protected function section_left_content($section, $course, $onsectionpage) {
        $o = '';

        if (($section->section != 0) && (!$onsectionpage)) {
            // Only in the non-general sections.
            if ($this->courseformat->is_section_current($section)) {
                $o .= get_accesshide(get_string('currentsection', 'format_' . $course->format));
            }
            if (empty($this->tcsettings)) {
                $this->tcsettings = $this->courseformat->get_settings();
            }
            switch ($this->tcsettings['layoutelement']) {
                case 1:
                case 2:
                case 5:
                case 6:
                    //$o .= html_writer::tag('span', $section->section, array('class' => 'cps_centre'));
                    $o .= '';
                    break;
            }
        }
        return $o;
    }

    /**
     * Generate the section title, wraps it in a link to the section page if page is to be displayed on a separate page
     *
     * @param stdClass $section The course_section entry from DB
     * @param stdClass $course The course entry from DB
     * @return string HTML to output.
     */
    public function section_title($section, $course) {
        return $this->render($this->courseformat->inplace_editable_render_section_name($section));
    }

    /**
     * Generate the section title to be displayed on the section page, without a link
     *
     * @param stdClass $section The course_section entry from DB
     * @param stdClass $course The course entry from DB
     * @return string HTML to output.
     */
    public function section_title_without_link($section, $course) {
        return $this->render($this->courseformat->inplace_editable_render_section_name($section, false));
    }

    /**
     * Generate the edit controls of a section.
     *
     * @param stdClass $course The course entry from DB.
     * @param stdClass $section The course_section entry from DB.
     * @param bool $onsectionpage true if being printed on a section page.
     * @return array of links with edit controls.
     */
    protected function section_edit_control_items($course, $section, $onsectionpage = false) {

        if (!$this->userisediting) {
            return array();
        }

        $coursecontext = context_course::instance($course->id);
        $sectionreturn = $onsectionpage ? $section->section : null;

        $url = course_get_url($course, $sectionreturn);
        $url->param('sesskey', sesskey());

        if (empty($this->tcsettings)) {
            $this->tcsettings = $this->courseformat->get_settings();
        }
        $controls = array();
        if ((($this->tcsettings['layoutstructure'] == 1) || ($this->tcsettings['layoutstructure'] == 4)) &&
                $section->section && has_capability('moodle/course:setcurrentsection', $coursecontext)) {
            if ($course->marker == $section->section) {  // Show the "light globe" on/off.
                $url->param('marker', 0);
                $markedthissection = get_string('markedthissection', 'format_toggletop');
                $highlightoff = get_string('highlightoff');
                $controls['highlight'] = array('url' => $url, "icon" => 'i/marked',
                                               'name' => $highlightoff,
                                               'pixattr' => array('class' => '', 'alt' => $markedthissection),
                                               'attr' => array('class' => 'editing_highlight', 'title' => $markedthissection,
                                               'data-action' => 'removemarker'));
            } else {
                $url->param('marker', $section->section);
                $markthissection = get_string('markthissection', 'format_toggletop');
                $highlight = get_string('highlight');
                $controls['highlight'] = array('url' => $url, "icon" => 'i/marker',
                                               'name' => $highlight,
                                               'pixattr' => array('class' => '', 'alt' => $markthissection),
                                               'attr' => array('class' => 'editing_highlight', 'title' => $markthissection,
                                               'data-action' => 'setmarker'));
            }
        }

        $parentcontrols = parent::section_edit_control_items($course, $section, $onsectionpage);

        // If the edit key exists, we are going to insert our controls after it.
        if (array_key_exists("edit", $parentcontrols)) {
            $merged = array();
            // We can't use splice because we are using associative arrays.
            // Step through the array and merge the arrays.
            foreach ($parentcontrols as $key => $action) {
                $merged[$key] = $action;
                if ($key == "edit") {
                    // If we have come to the edit key, merge these controls here.
                    $merged = array_merge($merged, $controls);
                }
            }

            return $merged;
        } else {
            return array_merge($controls, $parentcontrols);
        }
    }

    /**
     * Generate a summary of a section for display on the 'course index page'.
     *
     * @param stdClass $section The course_section entry from DB.
     * @param stdClass $course The course entry from DB.
     * @param array    $mods (argument not used).
     * @return string HTML to output.
     */
    protected function section_summary($section, $course, $mods) {
        $classattr = 'section main section-summary clearfix';
        $linkclasses = '';

        // If section is hidden then display grey section link.
        if (!$section->visible) {
            $classattr .= ' hidden';
            $linkclasses .= ' dimmed_text';
        } else if ($this->courseformat->is_section_current($section)) {
            $classattr .= ' current';
        }

        $o = '';
        $title = $this->courseformat->get_toggletop_section_name($course, $section, false);
        $liattributes = array(
            'id' => 'section-' . $section->section,
            'class' => $classattr,
            'role' => 'region',
            'aria-label' => $title
        );
        if (($this->formatresponsive) && ($this->tcsettings['layoutcolumnorientation'] == 2)) { // Horizontal column layout.
            $liattributes['style'] = 'width: ' . $this->tccolumnwidth . '%;';
        }
        $o .= html_writer::start_tag('li', $liattributes);

        $o .= html_writer::tag('div', '', array('class' => 'left side'));
        $o .= html_writer::tag('div', '', array('class' => 'right side'));
        $o .= html_writer::start_tag('div', array('class' => 'content'));

        if ($section->uservisible) {
            $title = html_writer::tag('a', $title,
                            array('href' => course_get_url($course, $section->section), 'class' => $linkclasses));
        }
        $o .= $this->output->heading($title, 3, 'section-title');

        $o .= html_writer::start_tag('div', array('class' => 'summarytext'));
        $o .= $this->format_summary_text($section);
        $o .= html_writer::end_tag('div');
        $o .= $this->section_activity_summary($section, $course, null);

        $o .= $this->section_availability($section);

        $o .= html_writer::end_tag('div');
        $o .= html_writer::end_tag('li');

        return $o;
    }
    function usersection_performance($courseid, $sectionid){ 
        global $DB,$USER; 

        $sql = "SELECT ccc.*,cm.instance FROM {course_modules} cm JOIN {modules} m ON cm.module = m.id JOIN {course_completion_criteria} ccc ON ccc.course = cm.course AND ccc.module = m.name AND cm.id = ccc.moduleinstance WHERE cm.course = $courseid AND cm.section = $sectionid";
        $completioncriteria = $DB->get_records_sql($sql);
        $count_total_activities = count($completioncriteria);

        if(empty($completioncriteria)){ 
            $percent = 0; 
        } else{ 
            $criteriacompletion = array(); 
            $completed = 0;
            foreach($completioncriteria as $activity){
            // FD-187495 Added changes for quiz completion progress in toggletopic course format    
                if ($activity->module == 'quiz') {

                $querysql = "SELECT qa.id FROM {quiz_attempts} qa
                                                    JOIN {quiz} q ON qa.quiz = q.id
                                                    JOIN {grade_items} gi ON gi.iteminstance = q.id AND gi.itemmodule = 'quiz'
                                                    WHERE qa.userid = :userid 
                                                    AND q.course = :courseid 
                                                    AND qa.state = :state 
                                                    AND q.id = :quizid
                                                    AND qa.sumgrades >= gi.gradepass
                                                    ";
                
                $params = [ 
                            'userid' => $USER->id,
                            'courseid' => $courseid,
                            'state' => 'finished',
                            'quizid' => $activity->instance
                ];                                    

                $quiz_passed = $DB->get_record_sql($querysql,$params);                                                                           
                if ($quiz_passed) {
                    $completed++;
                }

                } else {
                    $completioncriteria = $DB->get_record_sql("SELECT id FROM {course_modules_completion} WHERE coursemoduleid = $activity->moduleinstance AND userid = $USER->id AND completionstate > 0");
                    if(empty($completioncriteria)){ 
                        $criteriacompletion[] = 'notcompleted';
                    }else{
                        $completed++;
                    }
                }    
                 
            } 
            $inprogress_act = $count_total_activities-$completed; 
            $percent = ($completed)/($count_total_activities)*100; 
        } 
        return round($percent); 
    }
    /**
     * Generate the display of the header part of a section before
     * course modules are included.
     *
     * @param stdClass $section The course_section entry from DB.
     * @param stdClass $course The course entry from DB.
     * @param bool $onsectionpage true if being printed on a section page.
     * @param int $sectionreturn The section to return to after an action.
     * @return string HTML to output.
     */
    protected function section_header($section, $course, $onsectionpage, $sectionreturn = null) {
        $o = '';

        $sectionstyle = '';
        $rightcurrent = '';
        $linkclasses = '';
        $context = context_course::instance($course->id);
        $section_progress =  $this->usersection_performance($course->id, $section->id);
        $section_progress_bar = '';
        $section_progress_bar .= html_writer::start_tag('div', array('class'=>'progress progress-striped'));
            $section_progress_bar .= html_writer::start_tag('div', array('class'=>'progress-bar progress-bar-success', 'role'=>'progressbar', 'aria-valuenow'=>'20', 'aria-valuemin'=>'0', 'aria-valuemax'=>'100', 'style'=>'width: '.$section_progress.'%'));
                $section_progress_bar .= html_writer::start_tag('p', array('class'=>'progress-bar-percent-value'));
                    $section_progress_bar .= html_writer::tag('span', $section_progress.'%', array('class'=>'percentage_text'));
                $section_progress_bar .= html_writer::end_tag('p');
            $section_progress_bar .= html_writer::end_tag('div');
        $section_progress_bar .= html_writer::end_tag('div');

        if ($section->section != 0) {
            // Only in the non-general sections.
            if (!$section->visible) {
                $sectionstyle = ' hidden';
            }
            if ($section->section == $this->currentsection) {
                $sectionstyle = ' current';
                $rightcurrent = ' left';
            }
        }
        if (!$section->uservisible) {
            $linkclasses .= ' isrestricted dimmed_text';
        }else{
            $linkclasses = ' notrestricted';
        }
        if ((!$this->formatresponsive) && ($section->section != 0) &&
            ($this->tcsettings['layoutcolumnorientation'] == 2)) { // Horizontal column layout.
            $sectionstyle .= ' ' . $this->get_column_class($this->tcsettings['layoutcolumns']);
        }
        $liattributes = array(
            'id' => 'section-' . $section->section,
            'class' => 'section main clearfix w-100 pull-left' . $sectionstyle,
            'role' => 'region',
            'aria-label' => $this->courseformat->get_toggletop_section_name($course, $section, false)
        );
        if (($this->formatresponsive) && ($this->tcsettings['layoutcolumnorientation'] == 2)) { // Horizontal column layout.
            $liattributes['style'] = 'width: ' . $this->tccolumnwidth . '%;';
        }
        $o .= html_writer::start_tag('li', $liattributes);

        if ((($this->mobiletheme === false) && ($this->tablettheme === false)) || ($this->userisediting)) {
            $leftcontent = $this->section_left_content($section, $course, $onsectionpage);
            $rightcontent = '';
            if (($section->section != 0) && $this->userisediting && has_capability('moodle/course:update', $context)) {
                $url = new moodle_url('/course/editsection.php', array('id' => $section->id, 'sr' => $sectionreturn));

                $rightcontent .= html_writer::link($url,
                    $this->output->pix_icon('t/edit', get_string('edit')),
                        array('title' => get_string('editsection', 'format_toggletop'), 'class' => 'tceditsection'));
            }
            $rightcontent .= $this->section_right_content($section, $course, $onsectionpage);

            if ($this->rtl) {
                // Swap content.
                $o .= html_writer::tag('div', $rightcontent, array('class' => 'right side'));
                $o .= html_writer::tag('div', $leftcontent, array('class' => 'left side'));
            } else {
                $o .= html_writer::tag('div', $leftcontent, array('class' => 'left side'));
                $o .= html_writer::tag('div', $rightcontent, array('class' => 'right side'));
            }
        }

        $o .= html_writer::start_tag('div', array('class' => 'content'));
       
            if (($onsectionpage == false) && ($section->section != 0)) {
            $o .= html_writer::start_tag('div',
                array('class' => 'sectionhead toggle toggle-arrow '.$linkclasses.'',
                'id' => 'toggle-'.$section->section)
            );

            if ((!($section->toggle === null)) && ($section->toggle == true)) {
                $toggleclass = 'toggle_open';
                $ariapressed = 'true';
                $sectionclass = ' sectionopen';
            } else {
                $toggleclass = 'toggle_closed';
                $ariapressed = 'false';
                $sectionclass = '';
            }
            $toggleclass .= ' the_toggle ' . $this->tctoggleiconsize;
            $o .= html_writer::start_tag('span',
                array('class' => $toggleclass, 'role' => 'button', 'aria-pressed' => $ariapressed)
            );

            if (empty($this->tcsettings)) {
                $this->tcsettings = $this->courseformat->get_settings();
            }

            if ($this->userisediting) {
                $title = $this->section_title($section, $course);
            } else {
                $title = $this->courseformat->get_toggletop_section_name($course, $section, true);
            }
            if ((($this->mobiletheme === false) && ($this->tablettheme === false)) || ($this->userisediting)) {
                $o .= $this->output->heading($title, 3, 'sectionname');
            } else {
                $o .= html_writer::tag('h3', $title); // Moodle H3's look bad on mobile / tablet with CT so use plain.
            }

            $o .= $this->section_availability($section);

            $o .= html_writer::end_tag('span');
            if ($section->uservisible) {
                $o .= html_writer::start_tag('div', array('class'=>'section_progress'));
                $o .= html_writer::span($section_progress.'% Completed', '', array('class'=>''));
                $o .= $section_progress_bar;
                $o .= html_writer::end_tag('div');
            }
            $o .= html_writer::end_tag('div');
            /* echo $this->tcsettings['showsectionsummary'];die;
            if ($this->tcsettings['showsectionsummary'] == 2) {
                $o .= $this->section_summary_container($section);
            } */

            $o .= html_writer::start_tag('div',
                array('class' => 'sectionbody toggledsection' . $sectionclass,
                'id' => 'toggledsection-' . $section->section)
            );
            $o .= $this->section_summary_container($section);
            if ($this->userisediting && has_capability('moodle/course:update', $context)) {
                $url = new moodle_url('/course/editsection.php', array('id' => $section->id, 'sr' => $sectionreturn));
                $o .= html_writer::link($url,
                    $this->output->pix_icon('t/edit', get_string('edit')),
                    array('title' => get_string('editsection', 'format_toggletop'))
                );
            }

           /*  if ($this->tcsettings['showsectionsummary'] == 1) {
                
                $o .= $this->section_summary_container($section);
            } */
           
        } else {
            // When on a section page, we only display the general section title, if title is not the default one.
            $hasnamesecpg = ($section->section == 0 && (string) $section->name !== '');

            if ($hasnamesecpg) {
                $o .= $this->output->heading($this->section_title($section, $course), 3, 'section-title');
            }
            $o .= $this->section_availability($section);
            $o .= html_writer::start_tag('div', array('class' => 'summary'));
            $o .= $this->format_summary_text($section);

            if ($this->userisediting && has_capability('moodle/course:update', $context)) {
                $url = new moodle_url('/course/editsection.php', array('id' => $section->id, 'sr' => $sectionreturn));
                $o .= html_writer::link($url,
                    $this->output->pix_icon('t/edit', get_string('edit')),
                    array('title' => get_string('editsection', 'format_toggletop'))
                );
            }
            $o .= html_writer::end_tag('div');
        }
        return $o;
    }

    protected function section_summary_container($section) {
        $summarytext = $this->format_summary_text($section);
        if ($summarytext) {
            $classextra = ($this->tcsettings['showsectionsummary'] == 1) ? '' : ' summaryalwaysshown';
            $o = html_writer::start_tag('div', array('class' => 'summary toggletopicsummary' . $classextra));
            //$o .= $this->format_summary_text($section);
            $o .= $this->format_summary_text($section);
            $o .= html_writer::end_tag('div');
        } else {
            $o = '';
        }
        return $o;
    }

    /**
     * Generate the display of the footer part of a section.
     *
     * @return string HTML to output.
     */
    protected function section_footer() {
        $o = html_writer::end_tag('div');
        $o .= html_writer::end_tag('li');

        return $o;
    }

    /**
     * Generate the header html of a stealth section.
     *
     * @param int $sectionno The section number in the coruse which is being dsiplayed.
     * @return string HTML to output.
     */
    protected function stealth_section_header($sectionno) {
        $o = '';
        $sectionstyle = '';
        $course = $this->courseformat->get_course();
        // Horizontal column layout.
        if ((!$this->formatresponsive) && ($sectionno != 0) && ($this->tcsettings['layoutcolumnorientation'] == 2)) {
            $sectionstyle .= ' ' . $this->get_column_class($this->tcsettings['layoutcolumns']);
        }
        $liattributes = array(
            'id' => 'section-' . $sectionno,
            'class' => 'section main clearfix orphaned hidden' . $sectionstyle,
            'role' => 'region',
            'aria-label' => $this->courseformat->get_toggletop_section_name($course, $sectionno, false)
        );
        if (($this->formatresponsive) && ($this->tcsettings['layoutcolumnorientation'] == 2)) { // Horizontal column layout.
            $liattributes['style'] = 'width: ' . $this->tccolumnwidth . '%;';
        }
        $o .= html_writer::start_tag('li', $liattributes);
        $o .= html_writer::tag('div', '', array('class' => 'left side'));
        $section = $this->courseformat->get_section($sectionno);
        $rightcontent = $this->section_right_content($section, $course, false);
        $o .= html_writer::tag('div', $rightcontent, array('class' => 'right side'));
        $o .= html_writer::start_tag('div', array('class' => 'content'));
        $o .= $this->output->heading(get_string('orphanedactivitiesinsectionno', '', $sectionno), 3, 'sectionname');
        return $o;
    }

    /**
     * Generate the html for a hidden section.
     *
     * @param stdClass $section The section in the course which is being displayed.
     * @param int|stdClass $courseorid The course to get the section name for (object or just course id).
     * @return string HTML to output.
     */
    protected function section_hidden($section, $courseorid = null) {
        $o = '';
        $course = $this->courseformat->get_course();
        $sectionstyle = 'section main clearfix hidden';
        if ((!$this->formatresponsive) && ($this->tcsettings['layoutcolumnorientation'] == 2)) { // Horizontal column layout.
            $sectionstyle .= ' ' . $this->get_column_class($this->tcsettings['layoutcolumns']);
        }
        $liattributes = array(
            'id' => 'section-' . $section->section,
            'class' => $sectionstyle,
            'role' => 'region',
            'aria-label' => $this->courseformat->get_toggletop_section_name($course, $section, false)
        );
        if (($this->formatresponsive) && ($this->tcsettings['layoutcolumnorientation'] == 2)) { // Horizontal column layout.
            $liattributes['style'] = 'width: ' . $this->tccolumnwidth . '%;';
        }

        $o .= html_writer::start_tag('li', $liattributes);
        if ((($this->mobiletheme === false) && ($this->tablettheme === false)) || ($this->userisediting)) {
            $leftcontent = $this->section_left_content($section, $course, false);
            $rightcontent = $this->section_right_content($section, $course, false);

            if ($this->rtl) {
                // Swap content.
                $o .= html_writer::tag('div', $leftcontent, array('class' => 'right side'));
                $o .= html_writer::tag('div', $rightcontent, array('class' => 'left side'));
            } else {
                $o .= html_writer::tag('div', $leftcontent, array('class' => 'left side'));
                $o .= html_writer::tag('div', $rightcontent, array('class' => 'right side'));
            }

        }

        $o .= html_writer::start_tag('div', array('class' => 'content sectionhidden'));

        $title = get_string('notavailable');
        if ((($this->mobiletheme === false) && ($this->tablettheme === false)) || ($this->userisediting)) {
            $o .= $this->output->heading($title, 3, 'section-title');
        } else {
            $o .= html_writer::tag('h3', $title); // Moodle H3's look bad on mobile / tablet with CT so use plain.
        }
        $o .= html_writer::end_tag('div');
        $o .= html_writer::end_tag('li');
        return $o;
    }

    // /**
    //  * Renders HTML to display one course module for display within a section.
    //  *
    //  * This function calls:
    //  * {@link core_course_renderer::course_section_cm()}
    //  *
    //  * @param stdClass $course
    //  * @param completion_info $completioninfo
    //  * @param cm_info $mod
    //  * @param int|null $sectionreturn
    //  * @param array $displayoptions
    //  * @return String
    //  */
    // protected function course_section_cm_list_item($course, &$completioninfo, cm_info $mod, $sectionreturn, $displayoptions = array()) {

    //     global $CFG, $DB; 
    //     $output = '';

    //     if (!empty($displayoptions['hidecompletion']) || !isloggedin() || isguestuser() || !$mod->uservisible) {
    //         return $output;
    //     }

    //     if ($completioninfo === null) {
    //         $completioninfo = new completion_info($course);
    //     }

    //     $completion = $completioninfo->is_enabled($mod);
    //     echo "hiii";
    //     if ($completion == COMPLETION_TRACKING_NONE) {

    //         // if ($this->userisediting) {

    //         //     $output .= html_writer::span('&nbsp;', 'filler');
    //         // }
    //         //return $output;
    //     }


    //     $completiondata = $completioninfo->get_data($mod, true);
    //     $completionicon = '';


    //     if ($this->userisediting) {
    //         switch ($completion) {
    //             case COMPLETION_TRACKING_MANUAL :
    //                 $completionicon = 'manual-enabled'; break;
    //             case COMPLETION_TRACKING_AUTOMATIC :
    //                 $completionicon = 'auto-enabled'; break;
    //         }
    //     } else if ($completion == COMPLETION_TRACKING_MANUAL) {
    //         switch($completiondata->completionstate) {
    //             case COMPLETION_INCOMPLETE:
    //                 $completionicon = 'inprogress' . ($completiondata->overrideby ? '-override' : '');
    //                 break;
    //             case COMPLETION_COMPLETE:
    //                 $completionicon = 'completed' . ($completiondata->overrideby ? '-override' : '');
    //                 break;
    //         }
    //     } else { // Automatic
    //         switch($completiondata->completionstate) {
    //             case COMPLETION_INCOMPLETE:
    //                 $completionicon = 'inprogress' . ($completiondata->overrideby ? '-override' : '');
    //                 break;
    //             case COMPLETION_COMPLETE:
    //                 $completionicon = 'completed' . ($completiondata->overrideby ? '-override' : '');
    //                 break;
    //             case COMPLETION_COMPLETE_PASS:
    //                 $completionicon = 'inprogress'; break;
    //             case COMPLETION_COMPLETE_FAIL:
    //                 $completionicon = 'completed'; break;
    //         }
    //     }

    //     $linkclasses = '';
    //     $textclasses = '';
    //     if ($mod->uservisible) {
    //         //$conditionalhidden = $this->is_cm_conditionally_hidden($mod);
    //         $accessiblebutdim = (!$mod->visible || $conditionalhidden) &&
    //             has_capability('moodle/course:viewhiddenactivities', $mod->context);
    //         if ($accessiblebutdim) {
    //             $linkclasses .= ' dimmed';
    //             $textclasses .= ' dimmed_text';
    //             if ($conditionalhidden) {
    //                 $linkclasses .= ' conditionalhidden';
    //                 $textclasses .= ' conditionalhidden';
    //             }
    //         }
    //         if ($mod->is_stealth()) {
    //             // Stealth activity is the one that is not visible on course page.
    //             // It still may be displayed to the users who can manage it.
    //             $linkclasses .= ' stealth';
    //             $textclasses .= ' stealth';
    //         }
    //     } else {
    //         $linkclasses .= ' dimmed';
    //         $textclasses .= ' dimmed dimmed_text';
    //     }
    //     if ($modulehtml = $this->course_section_cm($course, $completioninfo, $mod, $sectionreturn, $displayoptions)) {
    //         $modclasses = 'activity ' . $mod->modname . ' modtype_' . $mod->modname . ' ' . $mod->extraclasses;
    //         $output .= html_writer::tag('li', $modulehtml, array('class' => $modclasses.$linkclasses.$textclasses.$completionicon, 'id' => 'module-' . $mod->id));
    //     }
    //     return $output;
    // }

    /**
     * Checks if course module has any conditions that may make it unavailable for
     * all or some of the students
     *
     * This function is internal and is only used to create CSS classes for the module name/text
     *
     * @param cm_info $mod
     * @return bool
     */
    protected function toggletop_is_cm_conditionally_hidden(cm_info $mod) {
        global $CFG;
        $conditionalhidden = false;
        if (!empty($CFG->enableavailability)) {
            $info = new \core_availability\info_module($mod);
            $conditionalhidden = !$info->is_available_for_all();
        }
        return $conditionalhidden;
    }

    /**
     * Renders html for completion box on course page
     *
     * If completion is disabled, returns empty string
     * If completion is automatic, returns an icon of the current completion state
     * If completion is manual, returns a form (with an icon inside) that allows user to
     * toggle completion
     *
     * @param stdClass $course course object
     * @param completion_info $completioninfo completion info for the course, it is recommended
     *     to fetch once for all modules in course/section for performance
     * @param cm_info $mod module to show completion for
     * @param array $displayoptions display options, not used in core
     * @return string
     */
    //comment by revathi
    // public function course_section_cm_completion($course, &$completioninfo, cm_info $mod, $displayoptions = array()) {
    //     global $CFG, $DB;
    //     $output = '';
    //     if (!empty($displayoptions['hidecompletion']) || !isloggedin() || isguestuser() || !$mod->uservisible) {
    //         return $output;
    //     }
    //     if ($completioninfo === null) {
    //         $completioninfo = new completion_info($course);
    //     }
    //     $completion = $completioninfo->is_enabled($mod);
    //     if ($completion == COMPLETION_TRACKING_NONE) {
    //         if ($this->page->user_is_editing()) {
    //             $output .= html_writer::span('&nbsp;', 'filler');
    //         }
    //         return $output;
    //     }

    //     $completiondata = $completioninfo->get_data($mod, true);
    //     $completionicon = '';
    //     if($completiondata->completionstate == 1){
    //         $act_completedon =  date("dS M Y", $completiondata->timemodified);
    //         // print_object($act_completedon);
    //     }
    //     // if ($mod->uservisible) {
    //         // echo "hii";
    //         // print_object($mod->uservisible);
    //         if ($this->page->user_is_editing()) {
    //             switch ($completion) {
    //                 case COMPLETION_TRACKING_MANUAL :
    //                     $completionicon = 'manual-enabled'; 
    //                     $completiontitle = 'manual-enabled'; break;
    //                 case COMPLETION_TRACKING_AUTOMATIC :
    //                     $completionicon = 'auto-enabled'; 
    //                     $completiontitle = 'auto-enabled'; break;
    //             }
    //             if ($completion == COMPLETION_TRACKING_MANUAL) {
    //                 switch($completiondata->completionstate) {
    //                     case COMPLETION_INCOMPLETE:
    //                         $completionicon = 'format_play1' . ($completiondata->overrideby ? '-override' : '');
    //                         $completiontitle = 'manual-n';
    //                         break;
    //                     case COMPLETION_COMPLETE:
    //                         $completionicon = 'format_check1' . ($completiondata->overrideby ? '-override' : '');
    //                         $completiontitle = 'manual-y';
    //                         break;
    //                 }
    //              } 
    //             else { // Automatic
    //                 switch($completiondata->completionstate) {
    //                     case COMPLETION_INCOMPLETE:
    //                         $completionicon = 'format_play1' . ($completiondata->overrideby ? '-override' : '');
    //                         break;
    //                     case COMPLETION_COMPLETE:
    //                         $completionicon = 'format_check1' . ($completiondata->overrideby ? '-override' : '');
    //                         break;
    //                     // case COMPLETION_COMPLETE_PASS:
    //                     //     $completionicon = 'inprogress'; break;
    //                     // case COMPLETION_COMPLETE_FAIL:
    //                     //     $completionicon = 'completed'; break;
    //                 }
    //             }
    //         } else if ($completion == COMPLETION_TRACKING_MANUAL) {
    //             switch($completiondata->completionstate) {
    //                 case COMPLETION_INCOMPLETE:
    //                     $completionicon = 'format_play1' . ($completiondata->overrideby ? '-override' : '');
    //                     $completiontitle = 'auto-n';
    //                     break;
    //                 case COMPLETION_COMPLETE:
    //                     $completionicon = 'format_check1' . ($completiondata->overrideby ? '-override' : '');
    //                     $completiontitle = 'auto-y';
    //                     break;
    //                 case COMPLETION_COMPLETE_PASS:
    //                     $completionicon = 'auto-pass';
    //                     $completiontitle = 'auto-pass'; break;
    //                 case COMPLETION_COMPLETE_FAIL:
    //                     $completionicon = 'auto-fail';
    //                     $completiontitle = 'auto-fail'; break;
    //             }
    //         } else { // Automatic
    //             switch($completiondata->completionstate) {
    //                 case COMPLETION_INCOMPLETE:
    //                     $completionicon = 'format_play1' . ($completiondata->overrideby ? '-override' : '');
    //                     $completiontitle = 'auto-n';
    //                     break;
    //                 case COMPLETION_COMPLETE:
    //                     $completionicon = 'format_check1' . ($completiondata->overrideby ? '-override' : '');
    //                     $completiontitle = 'auto-y';
    //                     break;
    //                 case COMPLETION_COMPLETE_PASS:
    //                     $completionicon = 'format_check1';
    //                     $completiontitle = 'auto-pass'; break;
    //                 case COMPLETION_COMPLETE_FAIL:
    //                     $completionicon = 'format_play1';
    //                     $completiontitle = 'auto-fail'; break;
    //             }
    //         }
    //     // }
    //     // else{
    //     //     echo "buy";
    //     //     print_object($mod->uservisible);
    //     //     $completionicon = 'format_lock1';
    //     //     $completiontitle = 'manual-enabled';
    //     // }
    //     if ($completionicon) {
    //         $formattedname = $mod->get_formatted_name();
    //         if ($completiondata->overrideby) {
    //             $args = new stdClass();
    //             $args->modname = $formattedname;
    //             $overridebyuser = \core_user::get_user($completiondata->overrideby, '*', MUST_EXIST);
    //             $args->overrideuser = fullname($overridebyuser);
    //            // print_object($completiontitle);
    //             $imgalt = get_string('completion-alt-' . $completiontitle, 'completion', $args);
    //         } else {
    //             //print_object($completiontitle);
    //             $imgalt = get_string('completion-alt-' . $completiontitle, 'completion', $formattedname);
    //         }

    //         if ($this->page->user_is_editing()) {
    //             // When editing, the icon is just an image.
    //             $completionpixicon = new pix_icon($completionicon, $imgalt, 'format_toggletop',
    //                     array('title' => $imgalt, 'class' => 'iconsmall'));
    //             $output .= html_writer::tag('span', $this->output->render($completionpixicon),
    //                     array('class' => 'autocompletion'));
    //             $output .= html_writer::tag('span', $act_completedon, array('class' => 'act_comp_date pull-right'));

    //         } else if ($completion == COMPLETION_TRACKING_MANUAL) {
    //             $newstate =
    //                 $completiondata->completionstate == COMPLETION_COMPLETE
    //                 ? COMPLETION_INCOMPLETE
    //                 : COMPLETION_COMPLETE;
    //             // In manual mode the icon is a toggle form...

    //             // If this completion state is used by the
    //             // conditional activities system, we need to turn
    //             // off the JS.
    //             $extraclass = '';
    //             if (!empty($CFG->enableavailability) &&
    //                     core_availability\info::completion_value_used($course, $mod->id)) {
    //                 $extraclass = ' preventjs';
    //             }
    //             $output .= html_writer::start_tag('form', array('method' => 'post',
    //                 'action' => new moodle_url('/course/togglecompletion.php'),
    //                 'class' => 'togglecompletion'. $extraclass));
    //             $output .= html_writer::start_tag('div');
    //             $output .= html_writer::empty_tag('input', array(
    //                 'type' => 'hidden', 'name' => 'id', 'value' => $mod->id));
    //             $output .= html_writer::empty_tag('input', array(
    //                 'type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()));
    //             $output .= html_writer::empty_tag('input', array(
    //                 'type' => 'hidden', 'name' => 'modulename', 'value' => $mod->name));
    //             $output .= html_writer::empty_tag('input', array(
    //                 'type' => 'hidden', 'name' => 'completionstate', 'value' => $newstate));
    //             $output .= html_writer::tag('button',
    //                 $this->output->pix_icon($completionicon, $imgalt, 'format_toggletop'), array('class' => 'btn btn-link'));
    //             $output .= html_writer::end_tag('div');
    //             $output .= html_writer::end_tag('form');
    //             $output .= html_writer::tag('span', $act_completedon, array('class' => 'act_comp_date pull-right'));
    //         } else {
    //             // In auto mode, the icon is just an image.
    //             $completionpixicon = new pix_icon($completionicon, $imgalt, 'format_toggletop',
    //                     array('title' => $imgalt));
    //             $output .= html_writer::tag('span', $this->output->render($completionpixicon),
    //             array('class' => 'autocompletion'));
    //             $output .= html_writer::tag('span', $act_completedon, array('class' => 'act_comp_date pull-right'));
                
    //         }
    //     }
    //     return $output;
    // }

        /**
     * Renders HTML to display one course module in a course section
     *
     * This includes link, content, availability, completion info and additional information
     * that module type wants to display (i.e. number of unread forum posts)
     *
     * This function calls:
     * {@link core_course_renderer::course_section_cm_name()}
     * {@link core_course_renderer::course_section_cm_text()}
     * {@link core_course_renderer::course_section_cm_availability()}
     * {@link core_course_renderer::course_section_cm_completion()}
     * {@link course_get_cm_edit_actions()}
     * {@link core_course_renderer::course_section_cm_edit_actions()}
     *
     * @param stdClass $course
     * @param completion_info $completioninfo
     * @param cm_info $mod
     * @param int|null $sectionreturn
     * @param array $displayoptions
     * @return string
     */
    public function course_section_cm($course, &$completioninfo, cm_info $mod, $sectionreturn, $displayoptions = array()) {
        $output = '';
        // We return empty string (because course module will not be displayed at all)
        // if:
        // 1) The activity is not visible to users
        // and
        // 2) The 'availableinfo' is empty, i.e. the activity was
        //     hidden in a way that leaves no info, such as using the
        //     eye icon.
        if (!$mod->is_visible_on_course_page()) {
            return $output;
        }

        $indentclasses = 'mod-indent';
        if (!empty($mod->indent)) {
            $indentclasses .= ' mod-indent-'.$mod->indent;
            if ($mod->indent > 15) {
                $indentclasses .= ' mod-indent-huge';
            }
        }

        $output .= html_writer::start_tag('div');

        if ($this->page->user_is_editing()) {
            $output .= course_get_cm_move($mod, $sectionreturn);
        }

        $output .= html_writer::start_tag('div', array('class' => 'mod-indent-outer'));

        // This div is used to indent the content.
        $output .= html_writer::div('', $indentclasses);

        // Start a wrapper for the actual content to keep the indentation consistent
        $output .= html_writer::start_tag('div');

        // Display the link to the module (or do nothing if module has no url)
        $cmname = $this->courserenderer->course_section_cm_name($mod, $displayoptions);

        if (!empty($cmname)) {
            // Start the div for the activity title, excluding the edit icons.
            $output .= html_writer::start_tag('div', array('class' => 'activityinstance d-flex gap-2'));
            $output .= $cmname;


            // Module can put text after the link (e.g. forum unread)
            $output .= $mod->afterlink;

            // Closing the tag which contains everything but edit icons. Content part of the module should not be part of this.
            $output .= html_writer::end_tag('div'); // .activityinstance
        }

        // If there is content but NO link (eg label), then display the
        // content here (BEFORE any icons). In this case cons must be
        // displayed after the content so that it makes more sense visually
        // and for accessibility reasons, e.g. if you have a one-line label
        // it should work similarly (at least in terms of ordering) to an
        // activity.
        $contentpart = $this->courserenderer->course_section_cm_text($mod, $displayoptions);
        $url = $mod->url;
        if (empty($url)) {
            $output .= $contentpart;
        }

        $modicons = '';
        if ($this->userisediting) {
            $editactions = course_get_cm_edit_actions($mod, $mod->indent, $sectionreturn);
            $modicons .= ' '. $this->courserenderer->course_section_cm_edit_actions($editactions, $mod, $displayoptions);
            $modicons .= $mod->afterediticons;
        }

         $modicons .= $this->course_section_cm_completions($course, $completioninfo, $mod, $displayoptions);

        if (!empty($modicons)) {
            $output .= html_writer::span($modicons, 'actions');
        }

        // Show availability info (if module is not available).
        $output .= $this->courserenderer->course_section_cm_availability($mod, $displayoptions);

        // If there is content AND a link, then display the content here
        // (AFTER any icons). Otherwise it was displayed before
        if (!empty($url)) {
            $output .= $contentpart;
        }

        $output .= html_writer::end_tag('div'); // $indentclasses

        // End of indentation div.
        $output .= html_writer::end_tag('div');

        $output .= html_writer::end_tag('div');
        return $output;
    }

    /**
     * Renders HTML to display one course module for display within a section.
     *
     * This function calls:
     * {@link core_course_renderer::course_section_cm()}
     *
     * @param stdClass $course
     * @param completion_info $completioninfo
     * @param cm_info $mod
     * @param int|null $sectionreturn
     * @param array $displayoptions
     * @return String
     */
    public function course_section_cm_list_item($course, &$completioninfo, cm_info $mod, $sectionreturn, $displayoptions = array()) {
        $output = '';
        if ($modulehtml = $this->course_section_cm($course, $completioninfo, $mod, $sectionreturn, $displayoptions)) {
            $modclasses = 'activity ' . $mod->modname . ' modtype_' . $mod->modname . ' ' . $mod->extraclasses;
            $output .= html_writer::tag('li', $modulehtml, array('class' => $modclasses, 'id' => 'module-' . $mod->id));
        }
        return $output;
    }

    /**
     * Checks if course module has any conditions that may make it unavailable for
     * all or some of the students
     *
     * This function is internal and is only used to create CSS classes for the module name/text
     *
     * @param cm_info $mod
     * @return bool
     */
    public function is_cm_conditionally_hidden(cm_info $mod) {
        global $CFG;
        $conditionalhidden = false;
        if (!empty($CFG->enableavailability)) {
            $info = new \core_availability\info_module($mod);
            $conditionalhidden = !$info->is_available_for_all();
        }
        return $conditionalhidden;
    }

    /**
     * Renders HTML to display a list of course modules in a course section
     * Also displays "move here" controls in Javascript-disabled mode
     *
     * This function calls {@link core_course_renderer::course_section_cm()}
     *
     * @param stdClass $course course object
     * @param int|stdClass|section_info $section relative section number or section object
     * @param int $sectionreturn section number to return to
     * @param int $displayoptions
     * @return void
     */
    public function course_section_cm_list($course, $section, $sectionreturn = null, $displayoptions = array()) {
         global $USER;

        $output = '';
        $modinfo = get_fast_modinfo($course);
        if (is_object($section)) {
            $section = $modinfo->get_section_info($section->section);
        } else {
            $section = $modinfo->get_section_info($section);
        }
        $completioninfo = new completion_info($course);

        // check if we are currently in the process of moving a module with JavaScript disabled
        $ismoving = $this->userisediting && ismoving($course->id);
        if ($ismoving) {
            $movingpix = new pix_icon('movehere', get_string('movehere'), 'moodle', array('class' => 'movetarget'));
            $strmovefull = strip_tags(get_string("movefull", "", "'$USER->activitycopyname'"));
        }
            // if (!empty($displayoptions['hidecompletion']) || !isloggedin() || isguestuser() || !$mod->uservisible) {
            //     return $output;
            // }

            // if ($completioninfo === null) {
            //     $completioninfo = new completion_info($course);
            // }

            $completion = $completioninfo->is_enabled($mod);
            if ($completion == COMPLETION_TRACKING_NONE) {
                if ($this->userisediting) {
                    $output .= html_writer::span('&nbsp;', 'filler');
                }
                return $output;
            }

            //$completionicon = '';
            // $linkclasses = '';
            // $textclasses = '';
            // if ($mod->uservisible) {
            //     $conditionalhidden = $this->courserenderer->toggletop_is_cm_conditionally_hidden($mod);
            //     $accessiblebutdim = (!$mod->visible || $conditionalhidden) &&
            //         has_capability('moodle/course:viewhiddenactivities', $mod->context);
            //     if ($accessiblebutdim) {
            //         $linkclasses .= ' dimmed';
            //         $textclasses .= ' dimmed_text';
            //         if ($conditionalhidden) {
            //             $linkclasses .= ' conditionalhidden';
            //             $textclasses .= ' conditionalhidden';
            //         }
            //     }
            //     if ($mod->is_stealth()) {
            //         // Stealth activity is the one that is not visible on course page.
            //         // It still may be displayed to the users who can manage it.
            //         $linkclasses .= ' stealth';
            //         $textclasses .= ' stealth';
            //     }
            // } else {
            //     $linkclasses .= ' dimmed';
            //     $textclasses .= ' dimmed dimmed_text';
            // }
             // $modulehtml = '<div class="activity_item_container '.$textclasses.$linkclasses.$completionicon.'">'.$this->courserenderer->course_section_cm_list_item($course,
        //                 $completioninfo, $mod, $sectionreturn, $displayoptions).'</div>';

        // Get the list of modules visible to user (excluding the module being moved if there is one)
        $moduleshtml = array();
        if (!empty($modinfo->sections[$section->section])) {
            foreach ($modinfo->sections[$section->section] as $modnumber) {
                $mod = $modinfo->cms[$modnumber];

                if ($ismoving and $mod->id == $USER->activitycopy) {
                    // do not display moving mod
                    continue;
                }

                $completiondata = $completioninfo->get_data($mod, true);
             if ($this->userisediting) {
                // switch ($completion) {
                //     case COMPLETION_TRACKING_MANUAL :
                //         $completionicon = 'manual-enabled'; break;
                //     case COMPLETION_TRACKING_AUTOMATIC :
                //         $completionicon = 'auto-enabled'; break;
                // }
                if ($completion == COMPLETION_TRACKING_MANUAL) {
                switch($completiondata->completionstate) {
                    case COMPLETION_INCOMPLETE:
                        $completionicon = 'inprogress' . ($completiondata->overrideby ? '-override' : '');
                        break;
                    case COMPLETION_COMPLETE:
                        $completionicon = 'completed' . ($completiondata->overrideby ? '-override' : '');
                        break;
                }
             } 
            else { // Automatic
                switch($completiondata->completionstate) {
                    case COMPLETION_INCOMPLETE:
                        $completionicon = 'inprogress' . ($completiondata->overrideby ? '-override' : '');
                        break;
                    case COMPLETION_COMPLETE:
                        $completionicon = 'completed' . ($completiondata->overrideby ? '-override' : '');
                        break;
                    case COMPLETION_COMPLETE_PASS:
                        $completionicon = 'completed'; break;
                    case COMPLETION_COMPLETE_FAIL:
                        $completionicon = 'inprogress'; break;
                }
            }
            } else if ($completion == COMPLETION_TRACKING_MANUAL) {
                switch($completiondata->completionstate) {
                    case COMPLETION_INCOMPLETE:
                        $completionicon = 'inprogress' . ($completiondata->overrideby ? '-override' : '');
                        break;
                    case COMPLETION_COMPLETE:
                    //echo "buy";
                        $completionicon = 'completed' . ($completiondata->overrideby ? '-override' : '');
                        break;
                }
            } else { // Automatic
                switch($completiondata->completionstate) {
                    case COMPLETION_INCOMPLETE:
                        $completionicon = 'inprogress' . ($completiondata->overrideby ? '-override' : '');
                        break;
                    case COMPLETION_COMPLETE:
                        $completionicon = 'completed' . ($completiondata->overrideby ? '-override' : '');
                        break;
                    case COMPLETION_COMPLETE_PASS:
                        $completionicon = 'completed'; break;
                    case COMPLETION_COMPLETE_FAIL:
                        $completionicon = 'inprogress'; break;
                }
            }

            $linkclasses = '';
            $textclasses = '';
            if ($mod->uservisible) {
                $conditionalhidden = $this->is_cm_conditionally_hidden($mod);
                $accessiblebutdim = (!$mod->visible || $conditionalhidden) &&
                    has_capability('moodle/course:viewhiddenactivities', $mod->context);
                if ($accessiblebutdim) {
                    $linkclasses .= ' dimmed';
                    $textclasses .= ' dimmed_text';
                    if ($conditionalhidden) {
                        $linkclasses .= ' conditionalhidden';
                        $textclasses .= ' conditionalhidden';
                    }
                }
                if ($mod->is_stealth()) {
                    // Stealth activity is the one that is not visible on course page.
                    // It still may be displayed to the users who can manage it.
                    $linkclasses .= ' stealth';
                    $textclasses .= ' stealth';
                }
            } else {
                $linkclasses .= ' dimmed';
                $textclasses .= ' dimmed dimmed_text';
            }
                $modulehtml = '<div class="activity_item_container '.$textclasses.' '.$linkclasses.' '.$completionicon.'">'.$this->course_section_cm_list_item($course,
                        $completioninfo, $mod, $sectionreturn, $displayoptions).'</div>';

                if ($modulehtml) {
                    $moduleshtml[$modnumber] = $modulehtml;
                }
            }
        }

        $sectionoutput = '';
        if (!empty($moduleshtml) || $ismoving) {
            foreach ($moduleshtml as $modnumber => $modulehtml) {
                if ($ismoving) {
                    $movingurl = new moodle_url('/course/mod.php', array('moveto' => $modnumber, 'sesskey' => sesskey()));
                    $sectionoutput .= html_writer::tag('li',
                            html_writer::link($movingurl, $this->output->render($movingpix), array('title' => $strmovefull)),
                            array('class' => 'movehere'));
                }

                $sectionoutput .= $modulehtml;
            }

            if ($ismoving) {
                $movingurl = new moodle_url('/course/mod.php', array('movetosection' => $section->id, 'sesskey' => sesskey()));
                $sectionoutput .= html_writer::tag('li',
                        html_writer::link($movingurl, $this->output->render($movingpix), array('title' => $strmovefull)),
                        array('class' => 'movehere'));
            }
        } else{
            if(!$this->userisediting)
              $sectionoutput .= html_writer::div(get_string('noactivities', 'format_toggletop'), '', array('class'=>'col-md-12 pull-left text-center alert alter-info'));
        }

        // Always output the section module list.
        $output .= html_writer::tag('ul', $sectionoutput, array('class' => 'section img-text'));

        return $output;

        // global $USER;
        // $output = '';
        // $modinfo = get_fast_modinfo($course);
        // if (is_object($section)) {
        //     $section = $modinfo->get_section_info($section->section);
        // } else {
        //     $section = $modinfo->get_section_info($section);
        // }
        // $completioninfo = new completion_info($course);

        // // check if we are currently in the process of moving a module with JavaScript disabled
        // $ismoving = $this->userisediting && ismoving($course->id);
        // if ($ismoving) {
        //     $movingpix = new pix_icon('movehere', get_string('movehere'), 'moodle', array('class' => 'movetarget'));
        //     $strmovefull = strip_tags(get_string("movefull", "", "'$USER->activitycopyname'"));
        // }

        // // Get the list of modules visible to user (excluding the module being moved if there is one)
        // $moduleshtml = array();
        // if (!empty($modinfo->sections[$section->section])) {
        
        //     foreach ($modinfo->sections[$section->section] as $modnumber) {
        //         $mod = $modinfo->cms[$modnumber];

        //     if (!empty($displayoptions['hidecompletion']) || !isloggedin() || isguestuser() || !$mod->uservisible) {
        //         return $output;
        //     }

        //     if ($completioninfo === null) {
        //         $completioninfo = new completion_info($course);
        //     }

        //     $completion = $completioninfo->is_enabled($mod);
        //     if ($completion == COMPLETION_TRACKING_NONE) {
        //         if ($this->userisediting) {
        //             $output .= html_writer::span('&nbsp;', 'filler');
        //         }
        //         return $output;
        //     }
        //     $completiondata = $completioninfo->get_data($mod, true);
        //     $completionicon = '';

        //     if ($this->userisediting) {
        //         // switch ($completion) {
        //         //     case COMPLETION_TRACKING_MANUAL :
        //         //         $completionicon = 'manual-enabled'; break;
        //         //     case COMPLETION_TRACKING_AUTOMATIC :
        //         //         $completionicon = 'auto-enabled'; break;
        //         // }
        //         if ($completion == COMPLETION_TRACKING_MANUAL) {
        //         switch($completiondata->completionstate) {
        //             case COMPLETION_INCOMPLETE:
        //                 $completionicon = 'inprogress' . ($completiondata->overrideby ? '-override' : '');
        //                 break;
        //             case COMPLETION_COMPLETE:
        //                 $completionicon = 'completed' . ($completiondata->overrideby ? '-override' : '');
        //                 break;
        //         }
        //     } else { // Automatic
        //         switch($completiondata->completionstate) {
        //             case COMPLETION_INCOMPLETE:
        //                 $completionicon = 'inprogress' . ($completiondata->overrideby ? '-override' : '');
        //                 break;
        //             case COMPLETION_COMPLETE:
        //                 $completionicon = 'completed' . ($completiondata->overrideby ? '-override' : '');
        //                 break;
        //             case COMPLETION_COMPLETE_PASS:
        //                 $completionicon = 'inprogress'; break;
        //             case COMPLETION_COMPLETE_FAIL:
        //                 $completionicon = 'completed'; break;
        //         }
        //     }
        //     } else if ($completion == COMPLETION_TRACKING_MANUAL) {
        //         switch($completiondata->completionstate) {
        //             case COMPLETION_INCOMPLETE:
        //                 $completionicon = 'inprogress' . ($completiondata->overrideby ? '-override' : '');
        //                 break;
        //             case COMPLETION_COMPLETE:
        //                 $completionicon = 'completed' . ($completiondata->overrideby ? '-override' : '');
        //                 break;
        //         }
        //     } else { // Automatic
        //         switch($completiondata->completionstate) {
        //             case COMPLETION_INCOMPLETE:
        //                 $completionicon = 'inprogress' . ($completiondata->overrideby ? '-override' : '');
        //                 break;
        //             case COMPLETION_COMPLETE:
        //                 $completionicon = 'completed' . ($completiondata->overrideby ? '-override' : '');
        //                 break;
        //             case COMPLETION_COMPLETE_PASS:
        //                 $completionicon = 'inprogress'; break;
        //             case COMPLETION_COMPLETE_FAIL:
        //                 $completionicon = 'completed'; break;
        //         }
        //     }

        //     $linkclasses = '';
        //     $textclasses = '';
        //     if ($mod->uservisible) {
        //         $conditionalhidden = $this->toggletop_is_cm_conditionally_hidden($mod);
        //         $accessiblebutdim = (!$mod->visible || $conditionalhidden) &&
        //             has_capability('moodle/course:viewhiddenactivities', $mod->context);
        //         if ($accessiblebutdim) {
        //             $linkclasses .= ' dimmed';
        //             $textclasses .= ' dimmed_text';
        //             if ($conditionalhidden) {
        //                 $linkclasses .= ' conditionalhidden';
        //                 $textclasses .= ' conditionalhidden';
        //             }
        //         }
        //         if ($mod->is_stealth()) {
        //             // Stealth activity is the one that is not visible on course page.
        //             // It still may be displayed to the users who can manage it.
        //             $linkclasses .= ' stealth';
        //             $textclasses .= ' stealth';
        //         }
        //     } else {
        //         $linkclasses .= ' dimmed';
        //         $textclasses .= ' dimmed dimmed_text';
        //     }
        //         if ($ismoving and $mod->id == $USER->activitycopy) {
        //             // do not display moving mod
        //             continue;
        //         }
        //         $sectionoutput = '';
        //         $modulehtml = '<div class="activity_item_container '.$textclasses.$linkclasses.$completionicon.'">'.$this->courserenderer->course_section_cm_list_item($course,
        //                 $completioninfo, $mod, $sectionreturn, $displayoptions).'</div>';

        //         if ($modulehtml) {
        //             $moduleshtml[$modnumber] = $modulehtml;
        //         }
        //         // if ($modulehtml = $this->courserenderer->course_section_cm_list_item($course,
        //         //         $completioninfo, $mod, $sectionreturn, $displayoptions)) {
        //         //     $moduleshtml[$modnumber] = $modulehtml;
        //         // }
        //     }
        // }

        
        // if (!empty($moduleshtml) || $ismoving) {
        //     foreach ($moduleshtml as $modnumber => $modulehtml) {
        //         if ($ismoving) {
        //             $movingurl = new moodle_url('/course/mod.php', array('moveto' => $modnumber, 'sesskey' => sesskey()));
        //             $sectionoutput .= html_writer::tag('li',
        //                     html_writer::link($movingurl, $this->output->render($movingpix), array('title' => $strmovefull)),
        //                     array('class' => 'movehere'));
        //         }

        //         $sectionoutput .= $modulehtml;
        //     }

        //     if ($ismoving) {
        //         $movingurl = new moodle_url('/course/mod.php', array('movetosection' => $section->id, 'sesskey' => sesskey()));
        //         $sectionoutput .= html_writer::tag('li',
        //                 html_writer::link($movingurl, $this->output->render($movingpix), array('title' => $strmovefull)),
        //                 array('class' => 'movehere'));
        //     }
        // }
        // else{
        //     //echo "hiii";exit;
        //             if(!$this->userisediting)
        //              $sectionoutput .= html_writer::div(get_string('noactivities', 'format_toggletop'), '', array('class'=>'col-md-12 pull-left text-center alert alter-info'));
        //          // $sectionoutput .= html_writer::tag('li',
        //          //        html_writer::tag('span', 'no activities created', array('class' => 'test')),
        //          //        array('class' => 'best'));
        //         }

        // // Always output the section module list.
        // $output .= html_writer::tag('ul', $sectionoutput, array('class' => 'section img-text'));

        // return $output;
    }
    

    public function get_module_details($course){
        global $DB, $USER;
        $completioninfo = new completion_info($course);
        if($course->enddate){
            $course_enddate = date("dS M Y", $course->enddate);
        }
        $courseenddate = $course_enddate ? $course_enddate : 'N/A';
        // First, let's make sure completion is enabled.
            if ($completioninfo->is_enabled()) {
                $percent = progress::get_course_progress_percentage($course, $USER->id);

                if (!is_null($percent)) {
                    $percent = floor($percent);
                }else{
                    $percent = 0;
                }
            }
            $progressbar_width = '';
            $completedon = $DB->get_field('course_completions', 'timecompleted', array('course'=> $course->id, 'userid'=> $USER->id));
            if($completedon && $percent ==100){
                $course_completedon = date("dS M Y", $completedon);
                $display_completiondate = '<span class="completion_date">on '.$course_completedon.'</span>';
                $courseprogress_width = 'courseprogress_width';
            }
            $module_details = '';
            $module_details .= html_writer::start_tag('div', array('class'=>'col-12 pull-left module_progress_info'));
                // $module_details .= html_writer::start_tag('div', array('class'=>'left_side col-md-3 col-sm-6 col-12 pull-left'));
                //     $module_details .= html_writer::start_tag('p', array('class'=>'m-0'));
                //         $module_details .= html_writer::tag('span', 'Course Progress', array('class'=>'info_header'));
                //     $module_details .= html_writer::end_tag('p');
                //     $module_details .= html_writer::start_tag('p', array('class'=>'mb-1'));
                //         $module_details .= html_writer::tag('span', $percent.'% Completed '.$display_completiondate.'', array('class'=>'module_progress'));
                //     $module_details .= html_writer::end_tag('p');
                //     $module_details .= html_writer::start_tag('div', array('class'=>$courseprogress_width));
                //     $module_details .= html_writer::start_tag('div', array('class'=>'progress progress-striped'));
                //         $module_details .= html_writer::start_tag('div', array('class'=>'progress-bar progress-bar-success', 'role'=>'progressbar', 'aria-valuenow'=>'20', 'aria-valuemin'=>'0', 'aria-valuemax'=>'100', 'style'=>'width: '.$percent.'%'));
                //             $module_details .= html_writer::start_tag('p', array('class'=>'progress-bar-percent-value'));
                //                 $module_details .= html_writer::tag('span', $percent.'%', array('class'=>'percentage_text'));
                //             $module_details .= html_writer::end_tag('p');
                //         $module_details .= html_writer::end_tag('div');
                //     $module_details .= html_writer::end_tag('div');
                //     $module_details .= html_writer::end_tag('div');
                // $module_details .= html_writer::end_tag('div');

                // $module_details .= html_writer::start_tag('div', array('class'=>'col-md-6 pull-left'));
                // $module_details .= html_writer::end_tag('div');

                // $module_details .= html_writer::start_tag('div', array('class'=>'col-md-4 col-sm-6 col-12 pull-right'));
                //     $module_details .= html_writer::start_tag('div', array('class'=>'due_date_info'));
                //         $module_details .= html_writer::start_tag('div', array('class'=>'item_icon'));
                //             $module_details .= html_writer::tag('span', '<i class="fa fa-calendar-o" aria-hidden="true"></i>', array('class'=>'due_date_calendar'));
                //         $module_details .= html_writer::end_tag('div');
                //         $module_details .= html_writer::start_tag('div', array('class'=>'item_info'));
                //             $module_details .= html_writer::tag('span', 'Due Date</br>', array('class'=>'info_header'));
                //             $module_details .= html_writer::tag('span', $courseenddate, array('class'=>'module_due_date'));
                //         $module_details .= html_writer::end_tag('div');
                //     $module_details .= html_writer::end_tag('div');
                // $module_details .= html_writer::end_tag('div');
        $module_details .= html_writer::end_tag('div');
        // $module_details .= html_writer::start_tag('div', array('class'=>'col-md-12 sessions_list_headers mt-15'));
        //     $module_details .= html_writer::div('SESSIONS', '', array('class'=>'col-md-6'));
        //     $module_details .= html_writer::div('STATUS', '', array('class'=>'col-md-6 pull-right text-right'));
        // $module_details .= html_writer::end_tag('div');

        return $module_details;
    }

    /**
     * Output the html for a multiple section page
     *
     * @param stdClass $course The course entry from DB
     * @param array $sections (argument not used)
     * @param array $mods (argument not used)
     * @param array $modnames (argument not used)
     * @param array $modnamesused (argument not used)
     */
    public function print_multiple_section_page($course, $sections, $mods, $modnames, $modnamesused) {
        global $USER, $OUTPUT, $CFG, $COURSE,$DB;
        $chelper = new coursecat_helper();
        $coursetype = $DB->get_record('local_course_types', array('id' => $course->open_identifiedas));
	
        if(!empty($course->open_courseprovider)){
            $courseprovider = $DB->get_field('local_course_providers','course_provider', array('id' => $course->open_courseprovider,'active' => 1), $strictness=IGNORE_MISSING);
        }else{
            $courseprovider = 'N/A';
        }
        $completion = new \completion_info($course);
        if ($completion->is_enabled()) {
            $percentage = progress::get_course_progress_percentage($course, $USER->id);
               if (!is_null($percentage)) {
                 $percentage = floor($percentage);
               }
                 $progress  = $percentage; 
            }
        if (!$progress) {
            $progress = 0;
        } else {
            $progress = round($progress);
        }

        if(!empty($course->category)){
            $course_category = $DB->get_field('course_categories', 'name', array('id'=>$course->category));
        }else{
            $course_category = 'NA';
        }
       
        if(!empty($course->open_level)){
            $level = $DB->get_field('local_levels','name', array('id' => $course->open_level,), $strictness=IGNORE_MISSING);
        }else{
            $level = 'N/A';
        }
        if(is_null($course->open_grade) || $course->open_grade == '' || $course->open_grade == -1){
            $course_grade = get_string('all');
        }else{
            $course_grade = $course->open_grade;
        }
        $course_in_list =new core_course_list_element($course);//new course_in_list($course);<revathi>
        $course_summary_info = '';
        $coursesummary = strip_tags($chelper->get_course_formatted_summary($course_in_list,array('overflowdiv' => false, 'noclean' => false, 'para' => false)));
        $summarystring = strlen($coursesummary) > 520 ? substr($coursesummary, 0, 520)."..." : $coursesummary;
        $course_summary = strlen($summarystring) == 0 ? html_writer::div(get_string('nocoursedesc', 'local_courses'), 'alert alert-info') : $summarystring;
        // Ratings for courses
        $ratings_plugin_exist = core_component::get_plugin_directory('local', 'ratings');
        if($ratings_plugin_exist){
            require_once($CFG->dirroot . '/local/ratings/lib.php');
            /*$PAGE->requires->jquery();
            $PAGE->requires->js('/local/ratings/js/jquery.rateyo.js');
            $PAGE->requires->js('/local/ratings/js/ratings.js');*/
            $course_duration = display_rating($COURSE->id,'local_courses');
            $course_like = display_like_unlike($COURSE->id,'local_courses');
            $course_review = display_comment($COURSE->id,'local_courses');

            $course_summary .= html_writer::start_tag('div', array('class'=>'col-lg-6 col-md-6 course_detail_container'));
            $course_summary .= html_writer::start_tag('p', array('class'=>'course_duration m-0'));
            $course_summary .= html_writer::tag('span', $course_duration, array('class'=>'ml-15 course_detail_value'));
            $course_summary .= html_writer::end_tag('p');

            $course_summary .= html_writer::start_tag('span', array('class'=>'course_like'));
            $course_summary .= html_writer::tag('span', '<span class="course_detail_labelname"></span>', array('class'=>'course_detail_label'));
            $course_summary .= html_writer::tag('span', $course_like, array('class'=>'course_detail_value d-inline-block'));
            $course_summary .= html_writer::end_tag('span');

            $course_summary .= html_writer::start_tag('span', array('class'=>'course_like'));
            $course_summary .= html_writer::tag('span', '<span class="course_detail_labelname"></span>', array('class'=>'course_detail_label'));
            $course_summary .= html_writer::tag('span', $course_review, array('class'=>'course_detail_value d-inline-block'));
            $course_summary .= html_writer::end_tag('span');
            
            $course_summary .= html_writer::end_tag('div');
        }
        foreach ($course_in_list->get_course_overviewfiles() as $file) {
            $isimage = $file->is_valid_image();
            if ($isimage) {
                $courseimage = file_encode_url("$CFG->wwwroot/pluginfile.php",
                                      '/'. $file->get_contextid(). '/'. $file->get_component(). '/'.
                                      $file->get_filearea(). $file->get_filepath(). $file->get_filename(), !$isimage);
            }
        }
        if (!empty($courseimage)) {
            $course_image = $courseimage;
            //$courseimage =  $courseimage;
        } else {
            //sets default course image 
            $course_image = $OUTPUT->image_url('courseimg', 'local_courses');
            //$courseimage = $OUTPUT->image_url('courseimg', 'local_courses');
        }
        $course_image = course_thumbimage($course);
        $enrolldata = $DB->get_record_sql("SELECT ue.* FROM {user_enrolments} ue JOIN {enrol} e ON ue.enrolid = e.id JOIN {course} c ON c.id = e.courseid WHERE e.courseid = $course->id AND ue.userid = $USER->id");
        if(!empty($course->open_skillcategory)){
            $skillcategory = $DB->get_field('local_skill_categories','name', array('id' => $course->open_skillcategory), $strictness=IGNORE_MISSING);
        }else{
            $skillcategory = 'N/A';
        }
        if(!empty($course->open_skill)){
              $skillsql = "SELECT GROUP_CONCAT(sk.name) FROM {local_skill} sk WHERE sk.id IN ($course->open_skill) ";
              $skills = $DB->get_field_sql($skillsql);  
        }else{
            $skills = 'N/A';
        }
        //if(strtolower($coursetype->shortname) == 'mooc'){
            if ($enrolldata && !empty($course->open_url)) {
                //$course->button = '<a href="' .$course->open_url . '" class="viewmore_btn">' . get_string('launch', 'local_search') . '</a>';
                $url = $course->open_url ;
                $launch = true;	
            }
       // }
        if(!empty($course->open_ouname )){
            $ouname = ($course->open_ouname == -1) ? 'All' :  $course->open_ouname;
        }else{
            $ouname ='N/A';
        }
        $enrolmenttypes = 'N/A';
        //$enrolmenttype = $DB->get_field_sql("SELECT e.enrol FROM {user_enrolments} ue JOIN {enrol} e ON ue.enrolid = e.id JOIN {course} c ON c.id = e.courseid WHERE e.courseid = $course->id AND ue.userid = $USER->id AND e.status = 0");
        $enrolmethods = $DB->get_fieldset_sql("SELECT e.enrol FROM {enrol} e WHERE e.courseid = $course->id AND e.status = 0 AND e.enrol IN ('auto','manual','self')");
        if(!empty($enrolmethods))  {
            $enrolmenttypes = implode(', ', $enrolmethods);
        }    
        $courseenddate = !empty($course->expirydate)?date('d-M-Y',$course->expirydate) : 'N/A';
        $course_summary_info .= html_writer::start_tag('div', array('class'=>'col-12  courseSummaryInfo', 'style'=>'background-image:url('.$course_image.')'));
            $course_summary_info .= html_writer::start_tag('div',array('class'=>'col-md-8 courseinfo'));
                $course_summary_info .= html_writer::tag('h3', $course->fullname, array('class'=>'col-md-8 p-0 col-12 coursename'));
                $course_summary_info .= html_writer::start_tag('div', array('class'=>'left_side p-0 col-md-6 col-sm-6 col-12 mt-4'));
                    $course_summary_info .= html_writer::start_tag('div', array('class'=>$courseprogress_width));
                        $course_summary_info .= html_writer::start_tag('div', array('class'=>'progress progress-striped'));
                            $course_summary_info .= html_writer::start_tag('div', array('class'=>'progress-bar progress-bar-success', 'role'=>'progressbar', 'aria-valuenow'=>'20', 'aria-valuemin'=>'0', 'aria-valuemax'=>'100', 'style'=>'width: '.$progress.'%'));
                                $course_summary_info .= html_writer::start_tag('p', array('class'=>'progress-bar-percent-value'));
                                    $course_summary_info .= html_writer::tag('span', $progress.'%', array('class'=>'percentage_text'));
                                $course_summary_info .= html_writer::end_tag('p');
                            $course_summary_info .= html_writer::end_tag('div');
                        $course_summary_info .= html_writer::end_tag('div');
                    $course_summary_info .= html_writer::end_tag('div');
                    $course_summary_info .= html_writer::tag('span', $progress.'% Completed '.$display_completiondate.'', array('class'=>'module_progress'));
                $course_summary_info .= html_writer::end_tag('div');
                $course_summary_info .= html_writer::start_tag('div', array('class'=>'row  p-0 mt-4 align-items-center'));
                    $course_summary_info .= html_writer::start_tag('div',array('class'=>'col-md-8 launch_button col12'));            
                    if($launch){                      
                        $course_summary_info .= html_writer::link($url,'<button class="btn btn-primary">'.get_string('start_now', 'local_search').'</button>',  array('target'=>'_blank'));	
                    }
                   $course_summary_info .=html_writer::end_tag('div');
                    $course_summary_info .=html_writer::start_tag('div',array('class'=>'col-md-4 col-12 d-flex'));
                        
                    $course_summary_info .= html_writer::end_tag('div');
                $course_summary_info .= html_writer::end_tag('div');
                $course_summary_info .= html_writer::start_tag('div',array('class'=>'row mt-2'));
                    $course_summary_info .= html_writer::start_tag('div',array('class'=>'col-md-12 col-12 p-0 mt-4'));
                        $course_summary_info .= html_writer::start_tag('div',array('class'=>'course_bottom d-flex justify-content-between text-white'));
                            $course_summary_info .=html_writer::start_tag('div',array('class'=>'learning_type'));
                                $course_summary_info .=html_writer::tag('span','Learning type :<b> '.$coursetype->course_type.'</b>');
                            $course_summary_info .=html_writer::end_tag('div');
                            $course_summary_info .= html_writer::start_tag('div', array('class'=>'due_date_info d-flex'));
                            $course_summary_info .= html_writer::start_tag('div', array('class'=>'item_icon'));
                                $course_summary_info .= html_writer::tag('span', '<i class="fa fa-calendar-o" aria-hidden="true"></i>', array('class'=>'due_date_calendar mr-2'));
                            $course_summary_info .= html_writer::end_tag('div');
                            $course_summary_info .= html_writer::start_tag('div', array('class'=>'item_info d-flex'));
                                $course_summary_info .= html_writer::tag('span', 'Due Date :', array('class'=>'mr-2'));
                                $course_summary_info .= html_writer::tag('span', $courseenddate, array('class'=>'module_due_date'));
                            $course_summary_info .= html_writer::end_tag('div');
                        $course_summary_info .= html_writer::end_tag('div');
                        //     $course_summary_info .=html_writer::start_tag('div',array('class'=>'col-md-3'));
                        //     $course_summary_info .=html_writer::tag('span','Skill Category:<b> '. $skillcategory .'</b>');
                        // $course_summary_info .=html_writer::end_tag('div');
                        //     $course_summary_info .=html_writer::start_tag('div',array('class'=>'col-md-3'));
                        //         $course_summary_info .=html_writer::tag('span','Skill :<b> '. $skills .'</b>');
                        //     $course_summary_info .=html_writer::end_tag('div');
                            // if(strtolower($coursetype->shortname) == 'mooc'){
                            //     $url = !empty($course->open_url)?$course->open_url:'N/A';
                            //     $url =	($url != 'N/A') ? '<a href = '.$url.' target ="_blank" class="text-white">'.$url.'</a>' : 'N/A';
                            //     $course_summary_info .=html_writer::start_tag('div',array('class'=>'col-md-4 url'));
                            //     $course_summary_info .=html_writer::tag('span','URL :'. $url);
                            //     $course_summary_info .=html_writer::end_tag('div'); 
                            // }                        
                        $course_summary_info .=html_writer::end_tag('div');
                    $course_summary_info .=html_writer::end_tag('div');
                $course_summary_info .=html_writer::end_tag('div');
            $course_summary_info .= html_writer::end_tag('div');


        // $course_summary_info .= html_writer::start_tag('div', array('class'=>'w-100 pull-left'));
        // // $course_summary_info .= html_writer::div('', '', array('class'=>'col-md-4 col-12 pull-left courseimg_bg', 'style'=>'background-image:url('.$course_image.')', 'alt'=>''.$course->fullname.'', 'title'=> ''.$course->fullname.''));
        
        // $course_summary_info .= html_writer::end_tag('div');

        $course_summary_info .= html_writer::end_tag('div');
        $course_summary_info .= html_writer::start_tag('div',array('class'=>'row mt-4'));
            $course_summary_info .= html_writer::start_tag('div',array('class'=>'col-md-8 '));
            $course_summary_info .= html_writer::tag('h3', 'Description', array('class'=>'col-md-8 p-0 col-12 bold '));
            $course_summary_info .= html_writer::tag('span', $course->summary, array('class'=>''));
            $course_summary_info .=html_writer::end_tag('div');
            $course_summary_info .= html_writer::start_tag('div',array('class'=>'col-md-4 right_container'));
                $course_summary_info .=html_writer::start_tag('div',array('class'=>'details_block mr-4'));
                    $course_summary_info .=html_writer::tag('h6','Course Info',array('class'=>'heading'));
                   /*  $course_summary_info .=html_writer::start_tag('ul');
                        $course_summary_info .=html_writer::start_tag('li',array('class'=>'d-flex'));
                            $course_summary_info .=html_writer::tag('span','',array('class'=>'calendar_icon'));
                            $course_summary_info .=html_writer::tag('span','21 Oct 22 -31 oct 22',array('class'=>'dates'));
                        $course_summary_info .=html_writer::end_tag('li');
                        $course_summary_info .=html_writer::start_tag('li',array('class'=>'new_class d-flex'));
                            $course_summary_info .=html_writer::tag('span','',array('class'=>'announce_icon'));
                            $course_summary_info .=html_writer::tag('span','New');
                        $course_summary_info .=html_writer::end_tag('li');
                        $course_summary_info .=html_writer::start_tag('li',array('class'=>'trainer d-flex'));
                            $course_summary_info .=html_writer::tag('span','',array('class'=>'trainer_icon'));
                            $course_summary_info .=html_writer::tag('span','No trainers Assigned');
                        $course_summary_info .=html_writer::end_tag('li');
                    $course_summary_info .=html_writer::end_tag('ul'); */
                $course_summary_info .=html_writer::end_tag('div');
                $course_summary_info .=html_writer::start_tag('div',array('class'=>'coursedetails_block mr-4'));
                    $course_summary_info .=html_writer::start_tag('ul');
                        $course_summary_info .=html_writer::start_tag('li',array('class'=>'d-flex'));
                            $course_summary_info .=html_writer::tag('span','',array('class'=>'track_icon'));
                            $course_summary_info .=html_writer::tag('span',get_string('career_track_tag','local_users'),array('class'=>'text-muted mx-2'));
                            $course_summary_info .=html_writer::tag('span',''.$course->open_careertrack.'',array('class'=>'count'));

                        if($course->duration){
                            $hours = floor((int)$course->duration/3600);
                            $minutes = ((int)$course->duration/60)%60;
                            $c_duration =$hours.':'. $minutes;
                        }else{
                            $c_duration = 'NA';
                        }
                        $course_summary_info .=html_writer::start_tag('li',array('class'=>'d-flex'));
                        $course_summary_info .=html_writer::tag('span','',array('class'=>'duration_icon'));
                        $course_summary_info .=html_writer::tag('span',get_string('duration','local_users'),array('class'=>'text-muted mx-2'));
                        $course_summary_info .=html_writer::tag('span',''.$c_duration.'',array('class'=>'count'));

                    $course_summary_info .=html_writer::start_tag('li',array('class'=>'d-flex'));
                            $course_summary_info .=html_writer::tag('span','',array('class'=>'credit_icon'));
                            $course_summary_info .=html_writer::tag('span',get_string('credits_tag','local_users'),array('class'=>'text-muted mx-2'));
                            $course_summary_info .=html_writer::tag('span',''.$course->open_points.'',array('class'=>'count'));  
                        $course_summary_info .=html_writer::end_tag('li');
                        $course_summary_info .=html_writer::start_tag('li',array('class'=>'d-flex'));
                            $course_summary_info .=html_writer::tag('span','',array('class'=>'category_icon'));
                            $course_summary_info .=html_writer::tag('span','Category',array('class'=>'text-muted mx-2'));
                            $course_summary_info .=html_writer::tag('span',$course_category,array('class'=>'count'));
                        $course_summary_info .=html_writer::end_tag('li'); 
                        $course_summary_info .=html_writer::start_tag('li',array('class'=>'d-flex'));
                            $course_summary_info .=html_writer::tag('span','',array('class'=>'level_icon'));
                            $course_summary_info .=html_writer::tag('span','Level',array('class'=>'text-muted mx-2'));
                            $course_summary_info .=html_writer::tag('span',$level,array('class'=>'count'));
                        $course_summary_info .=html_writer::end_tag('li');
                        $course_summary_info .=html_writer::start_tag('li',array('class'=>'d-flex'));
                            $course_summary_info .=html_writer::tag('span','',array('class'=>'grade_icon'));
                            $course_summary_info .=html_writer::tag('span','Grade',array('class'=>'text-muted mx-2'));
                            $course_summary_info .=html_writer::tag('span',$course_grade,array('class'=>'count'));
                        $course_summary_info .=html_writer::end_tag('li');
                    
                        $course_summary_info .=html_writer::start_tag('li',array('class'=>'d-flex'));
                            $course_summary_info .=html_writer::tag('span','',array('class'=>'crcprovider_icon'));
                            $course_summary_info .=html_writer::tag('span','course provider',array('class'=>'text-muted mx-2'));
                            $course_summary_info .=html_writer::tag('span', $courseprovider ,array('class'=>'count'));
                        $course_summary_info .=html_writer::end_tag('li');
                        
                        $course_summary_info .=html_writer::start_tag('li',array('class'=>'d-flex'));
                            $course_summary_info .=html_writer::tag('span','',array('class'=>'skillcat_icon'));
                            $course_summary_info .=html_writer::tag('span','skill category',array('class'=>'text-muted mx-2'));
                            $course_summary_info .=html_writer::tag('span', $skillcategory ,array('class'=>'count'));
                        $course_summary_info .=html_writer::end_tag('li');
                        $course_summary_info .=html_writer::start_tag('li',array('class'=>'d-flex'));
                            $course_summary_info .=html_writer::tag('span','',array('class'=>'skill_icon'));
                            $course_summary_info .=html_writer::tag('span','skill',array('class'=>'text-muted mx-2'));
                            $course_summary_info .=html_writer::tag('span', $skills ,array('class'=>'count'));
                        $course_summary_info .=html_writer::end_tag('li');
                        $course_summary_info .=html_writer::start_tag('li',array('class'=>'d-flex'));
                            $course_summary_info .=html_writer::tag('span','',array('class'=>'enroll_icon'));
                            $course_summary_info .=html_writer::tag('span','enrolment type',array('class'=>'text-muted mx-2'));
                            $course_summary_info .=html_writer::tag('span', ucwords($enrolmenttypes ),array('class'=>'count'));
                        $course_summary_info .=html_writer::end_tag('li');
                        
                        // if(strtolower($coursetype->shortname) == 'mooc'){
                            $url = !empty($course->open_url)?$course->open_url:'N/A';
                            $url =	($url != 'N/A') ? '<a href = '.$url.' target ="_blank" class="text-blue">Click here</a>' : 'N/A';
                         
                            $course_summary_info .=html_writer::start_tag('li',array('class'=>'my-1 incentives__text url_course d-flex align-items-start'));
                            $course_summary_info .=html_writer::tag('span','',array('class'=>'url_icon'));
                            $course_summary_info .=html_writer::tag('span','Url :' ,array('class'=>'text-muted mx-2 '));
                            $course_summary_info .=html_writer::tag('span', $url ,array('class'=>'count'));
                            $course_summary_info .=html_writer::end_tag('li');
                        // }      

                    $course_summary_info .=html_writer::end_tag('ul');
                $course_summary_info .=html_writer::end_tag('div');
            $course_summary_info .=html_writer::end_tag('div');
        $course_summary_info .=html_writer::end_tag('div');
        echo $course_summary_info;
        $modinfo = get_fast_modinfo($course);
        $course = $this->courseformat->get_course();
        if (empty($this->tcsettings)) {
            $this->tcsettings = $this->courseformat->get_settings();
        }

        $context = context_course::instance($course->id);
        // Title with completion help icon.
        $completioninfo = new completion_info($course);
        //echo $completioninfo->display_help_icon();
        echo $this->output->heading($this->page_title(), 2, 'accesshide');

        // Copy activity clipboard..
        echo $this->course_activity_clipboard($course, 0);

        // Now the list of sections..
        if ($this->formatresponsive) {
            $this->tccolumnwidth = 100; // Reset to default.
        }
        echo $this->start_section_list();

        $sections = $modinfo->get_section_info_all();
       // General section if non-empty.
        $thissection = $sections[0];
        unset($sections[0]);
        if ($thissection->summary or ! empty($modinfo->sections[0]) or $this->userisediting) {
            echo $this->section_header($thissection, $course, false, 0);
            echo $this->course_section_cm_list($course, $thissection, 0);
            echo $this->courserenderer->course_section_add_cm_control($course, $thissection->section, 0);
            echo $this->section_footer();
        }
        $shownonetoggle = false;
        $coursenumsections = $this->courseformat->get_last_section_number();
        if ($coursenumsections > 0) {
            $sectiondisplayarray = array();
            if ($coursenumsections > 1) {
                if (($this->userisediting) || ($this->tcsettings['onesection'] == 1)) {
                    // Collapsed Topics all toggles.
                    //echo $this->toggle_all();
                }
                if ($this->tcsettings['displayinstructions'] == 2) {
                    // Collapsed Topics instructions.
                    //echo $this->display_instructions();
                }
            }
            $currentsectionfirst = false;
            if (($this->tcsettings['layoutstructure'] == 4) && (!$this->userisediting)) {
                $currentsectionfirst = true;
            }

            if (($this->tcsettings['layoutstructure'] != 3) || ($this->userisediting)) {
                $section = 1;
            } else {
                $timenow = time();
                $weekofseconds = 604800;
                $course->enddate = $course->startdate + ($weekofseconds * $coursenumsections);
                $section = $coursenumsections;
                $weekdate = $course->enddate;      // This should be 0:00 Monday of that week.
                $weekdate -= 7200;                 // Subtract two hours to avoid possible DST problems.
            }

            $numsections = $coursenumsections; // Because we want to manipulate this for column breakpoints.
            if (($this->tcsettings['layoutstructure'] == 3) && ($this->userisediting == false)) {
                $loopsection = 1;
                $numsections = 0;
                while ($loopsection <= $coursenumsections) {
                    $nextweekdate = $weekdate - ($weekofseconds);
                    if ((($thissection->uservisible ||
                            ($thissection->visible && !$thissection->available && !empty($thissection->availableinfo))) &&
                            ($nextweekdate <= $timenow)) == true) {
                        $numsections++; // Section not shown so do not count in columns calculation.
                    }
                    $weekdate = $nextweekdate;
                    $section--;
                    $loopsection++;
                }
                // Reset.
                $section = $coursenumsections;
                $weekdate = $course->enddate;      // This should be 0:00 Monday of that week.
                $weekdate -= 7200;                 // Subtract two hours to avoid possible DST problems.
            }

            if ($numsections < $this->tcsettings['layoutcolumns']) {
                $this->tcsettings['layoutcolumns'] = $numsections;  // Help to ensure a reasonable display.
            }
            if (($this->tcsettings['layoutcolumns'] > 1) && ($this->mobiletheme === false)) {
                if ($this->tcsettings['layoutcolumns'] > 4) {
                    // Default in config.php (and reset in database) or database has been changed incorrectly.
                    $this->tcsettings['layoutcolumns'] = 4;

                    // Update....
                    $this->courseformat->update_toggletop_columns_setting($this->tcsettings['layoutcolumns']);
                }

                if (($this->tablettheme === true) && ($this->tcsettings['layoutcolumns'] > 2)) {
                    // Use a maximum of 2 for tablets.
                    $this->tcsettings['layoutcolumns'] = 2;
                }

                if ($this->formatresponsive) {
                    $this->tccolumnwidth = 100 / $this->tcsettings['layoutcolumns'];
                    if ($this->tcsettings['layoutcolumnorientation'] == 2) { // Horizontal column layout.
                        $this->tccolumnwidth -= 0.5;
                        $this->tccolumnpadding = 0; // In 'px'.
                    } else {
                        $this->tccolumnwidth -= 0.2;
                        $this->tccolumnpadding = 0; // In 'px'.
                    }
                }
            } else if ($this->tcsettings['layoutcolumns'] < 1) {
                // Distributed default in plugin settings (and reset in database) or database has been changed incorrectly.
                $this->tcsettings['layoutcolumns'] = 1;

                // Update....
                $this->courseformat->update_toggletop_columns_setting($this->tcsettings['layoutcolumns']);
            }

            echo $this->end_section_list();
            echo $this->get_module_details($course);
            if ((!$this->formatresponsive) && ($this->tcsettings['layoutcolumnorientation'] == 1)) { // Vertical columns.
                echo html_writer::start_tag('div', array('class' => $this->get_row_class()));
            }
            echo $this->start_toggle_section_list();

            $loopsection = 1;
            $breaking = false; // Once the first section is shown we can decide if we break on another column.

            while ($loopsection <= $coursenumsections) {
                if (($this->tcsettings['layoutstructure'] == 3) && ($this->userisediting == false)) {
                    $nextweekdate = $weekdate - ($weekofseconds);
                }
                $thissection = $modinfo->get_section_info($section);

                /* Show the section if the user is permitted to access it, OR if it's not available
                  but there is some available info text which explains the reason & should display. */
                // if (($this->tcsettings['layoutstructure'] != 3) || ($this->userisediting)) {
                //     $showsection = $thissection->uservisible ||
                //         ($thissection->visible && !$thissection->available && !empty($thissection->availableinfo));
                // } else {
                //     $showsection = ($thissection->uservisible ||
                //         ($thissection->visible && !$thissection->available && !empty($thissection->availableinfo))) &&
                //         ($nextweekdate <= $timenow);
                // }
                // if (($currentsectionfirst == true) && ($showsection == true)) {
                //     // Show the section if we were meant to and it is the current section:....
                //     $showsection = ($course->marker == $section);
                // } else if (($this->tcsettings['layoutstructure'] == 4) &&
                //     ($course->marker == $section) && (!$this->userisediting)) {
                //     $showsection = false; // Do not reshow current section.
                // }
                // if (!$showsection) {
                //     // Hidden section message is overridden by 'unavailable' control.
                //     $testhidden = false;
                //     if ($this->tcsettings['layoutstructure'] != 4) {
                //         if (($this->tcsettings['layoutstructure'] != 3) || ($this->userisediting)) {
                //             $testhidden = true;
                //         } else if ($nextweekdate <= $timenow) {
                //             $testhidden = true;
                //         }
                //     } else {
                //         if (($currentsectionfirst == true) && ($course->marker == $section)) {
                //             $testhidden = true;
                //         } else if (($currentsectionfirst == false) && ($course->marker != $section)) {
                //             $testhidden = true;
                //         }
                //     }
                //     if ($testhidden) {
                //         if (!$course->hiddensections && $thissection->available) {
                //             $thissection->ishidden = true;
                //             $sectiondisplayarray[] = $thissection;
                //         }
                //     }
                // } else {
                    if ($this->isoldtogglepreference == true) {
                        $togglestate = substr($this->togglelib->get_toggles(), $section, 1);
                        if ($togglestate == '1') {
                            $thissection->toggle = true;
                        } else {
                            $thissection->toggle = false;
                        }
                    } else {
                        $thissection->toggle = $this->togglelib->get_toggle_state($thissection->section);
                    }

                    if ($this->courseformat->is_section_current($thissection)) {
                        $this->currentsection = $thissection->section;
                        $thissection->toggle = true; // Open current section regardless of toggle state.
                        $this->togglelib->set_toggle_state($thissection->section, true);
                    }

                    // $thissection->isshown = true;
                    $sectiondisplayarray[] = $thissection;
                // }

                if (($this->tcsettings['layoutstructure'] != 3) || ($this->userisediting)) {
                    $section++;
                } else {
                    $section--;
                    if (($this->tcsettings['layoutstructure'] == 3) && ($this->userisediting == false)) {
                        $weekdate = $nextweekdate;
                    }
                }

                $loopsection++;
                if (($currentsectionfirst == true) && ($loopsection > $coursenumsections)) {
                    // Now show the rest.
                    $currentsectionfirst = false;
                    $loopsection = 1;
                    $section = 1;
                }
                if ($section > $coursenumsections) {
                    // Activities inside this section are 'orphaned', this section will be printed as 'stealth' below.
                    break;
                }
            }

            $canbreak = ($this->tcsettings['layoutcolumns'] > 1);
            $columncount = 1;
            $breakpoint = 0;
            $shownsectioncount = 0;
            if ((!$this->userisediting) && ($this->tcsettings['onesection'] == 2) && (!empty($this->currentsection))) {
                $shownonetoggle = $this->currentsection; // One toggle open only, so as we have a current section it will be it.
            }
            foreach ($sectiondisplayarray as $thissection) {
                $shownsectioncount++;
                if (!empty($thissection->ishidden)) {

                    echo $this->section_hidden($thissection);
                } else if (!empty($thissection->issummary)) {

                    echo $this->section_summary($thissection, $course, null);
                } 
                // else if (!empty($thissection->isshown)) {


                    if ((!$this->userisediting) && ($this->tcsettings['onesection'] == 2)) {
                        if ($thissection->toggle) {
                            if (!empty($shownonetoggle)) {
                                // Make sure the current section is not closed if set above.
                                if ($shownonetoggle != $thissection->section) {
                                    // There is already a toggle open so others need to be closed.
                                    $thissection->toggle = false;
                                    $this->togglelib->set_toggle_state($thissection->section, false);
                                }
                            } else {
                                // No open toggle, so as this is the first, it can be the one.
                                $shownonetoggle = $thissection->section;
                            }
                        }
                    }

                    echo $this->section_header($thissection, $course, false, 0);
                    
                    if ($thissection->uservisible) {
                        echo  $this->course_section_cm_list($course, $thissection, 0);
                        echo $this->courserenderer->course_section_add_cm_control($course, $thissection->section, 0);
                    }else{
                        echo html_writer::div(get_string('section_restriction_string', 'format_toggletop'), '', array('class'=>'col-md-12 pull-left text-center alert alter-info'));
                    }
                    echo html_writer::end_tag('div');
                    echo $this->section_footer();
                // }

                // Only check for breaking up the structure with rows if more than one column and when we output all of the sections.
                // if ($canbreak === true) {
                //     // Only break in non-mobile themes or using a responsive theme.
                //     if ((!$this->formatresponsive) || ($this->mobiletheme === false)) {
                //         if ($this->tcsettings['layoutcolumnorientation'] == 1) {  // Vertical mode.
                //             // This is not perfect yet as does not tally the shown sections and divide by columns.
                //             if (($breaking == false) && ($showsection == true)) {
                //                 $breaking = true;
                //                 // Divide the number of sections by the number of columns.
                //                 $breakpoint = $numsections / $this->tcsettings['layoutcolumns'];
                //             }

                //             if (($breaking == true) && ($shownsectioncount >= $breakpoint) &&
                //                 ($columncount < $this->tcsettings['layoutcolumns'])) {
                //                 echo $this->end_section_list();
                //                 echo $this->start_toggle_section_list();
                //                 $columncount++;
                //                 // Next breakpoint is...
                //                 $breakpoint += $numsections / $this->tcsettings['layoutcolumns'];
                //             }
                //         } else {  // Horizontal mode.
                //             if (($breaking == false) && ($showsection == true)) {
                //                 $breaking = true;
                //                 // The lowest value here for layoutcolumns is 2 and the maximum for shownsectioncount is 2, so :).
                //                 $breakpoint = $this->tcsettings['layoutcolumns'];
                //             }

                //             if (($breaking == true) && ($shownsectioncount >= $breakpoint)) {
                //                 echo $this->end_section_list();
                //                 echo $this->start_toggle_section_list();
                //                 // Next breakpoint is...
                //                 $breakpoint += $this->tcsettings['layoutcolumns'];
                //             }
                //         }
                //     }
                // }

                unset($sections[$thissection->section]);
            }
        }

        if ($this->userisediting and has_capability('moodle/course:update', $context)) {
            // Print stealth sections if present.
            foreach ($modinfo->get_section_info_all() as $section => $thissection) {
                if ($section <= $coursenumsections or empty($modinfo->sections[$section])) {
                    // This is not stealth section or it is empty.
                    continue;
                }
                echo $this->stealth_section_header($section);
                echo $this->courserenderer->course_section_cm_list($course, $thissection->section, 0);
                echo $this->stealth_section_footer();
            }

            echo $this->end_section_list();

            if ((!$this->formatresponsive) && ($this->tcsettings['layoutcolumnorientation'] == 1)) { // Vertical columns.
                echo html_writer::end_tag('div');
            }

            echo $this->change_number_sections($course, 0);
        } else {
            echo $this->end_section_list();
            if ((!$this->formatresponsive) && ($this->tcsettings['layoutcolumnorientation'] == 1)) { // Vertical columns.
                echo html_writer::end_tag('div');
            }
        }

        // Now initialise the JavaScript.
        $toggles = $this->togglelib->get_toggles();
        $this->page->requires->js_init_call('M.format_toggletop.init', array(
            $course->id,
            $toggles,
            $coursenumsections,
            $this->defaulttogglepersistence,
            $this->defaultuserpreference,
            ((!$this->userisediting) && ($this->tcsettings['onesection'] == 2)),
            $shownonetoggle,
            $this->userisediting), true);
        $this->page->requires->jquery();
        // $this->page->requires->jquery_plugin('ui', true);
        // $this->page->requires->jquery_plugin('ui-css', true);
        // Make sure the database has the correct state of the toggles if changed by the code.
        // This ensures that a no-change page reload is correct.
        set_user_preference('toggletop_toggle_'.$course->id, $toggles);
    }

    /**
     * Displays the toggle all functionality.
     * @return string HTML to output.
     */
    protected function toggle_all() {
        $o = html_writer::start_tag('li', array('class' => 'tcsection main clearfix', 'id' => 'toggle-all'));

        if ((($this->mobiletheme === false) && ($this->tablettheme === false)) || ($this->userisediting)) {
            $o .= html_writer::tag('div', $this->output->spacer(), array('class' => 'left side'));
            $o .= html_writer::tag('div', $this->output->spacer(), array('class' => 'right side'));
        }

        $o .= html_writer::start_tag('div', array('class' => 'content'));
        $iconsetclass = ' toggle-' . $this->tcsettings['toggleiconset'];
        if ($this->tcsettings['toggleallhover'] == 2) {
            $iconsetclass .= '-hover' . $iconsetclass;
        }
        $o .= html_writer::start_tag('div', array('class' => 'sectionbody' . $iconsetclass));
        $o .= html_writer::start_tag('h4', null);
        $o .= html_writer::tag('span', get_string('toggletopopened', 'format_toggletop'),
            array('class' => 'on ' . $this->tctoggleiconsize, 'id' => 'toggles-all-opened',
            'role' => 'button')
        );
        $o .= html_writer::tag('span', get_string('toggletopclosed', 'format_toggletop'),
            array('class' => 'off ' . $this->tctoggleiconsize, 'id' => 'toggles-all-closed',
            'role' => 'button')
        );
        $o .= html_writer::end_tag('h4');
        $o .= html_writer::end_tag('div');
        $o .= html_writer::end_tag('div');
        $o .= html_writer::end_tag('li');

        return $o;
    }

    /**
     * Displays the instructions functionality.
     * @return string HTML to output.
     */
    protected function display_instructions() {
        $o = html_writer::start_tag('li',
            array('class' => 'tcsection main clearfix', 'id' => 'toggletop-display-instructions'));

        if ((($this->mobiletheme === false) && ($this->tablettheme === false)) || ($this->userisediting)) {
            $o .= html_writer::tag('div', $this->output->spacer(), array('class' => 'left side'));
            $o .= html_writer::tag('div', $this->output->spacer(), array('class' => 'right side'));
        }

        $o .= html_writer::start_tag('div', array('class' => 'content'));
        $o .= html_writer::start_tag('div', array('class' => 'sectionbody'));
        $o .= html_writer::tag('p', get_string('instructions', 'format_toggletop'),
            array('class' => 'toggletop-display-instructions')
        );
        $o .= html_writer::end_tag('div');
        $o .= html_writer::end_tag('div');
        $o .= html_writer::end_tag('li');

        return $o;
    }

    public function set_portable($portable) {
        switch ($portable) {
            case 1:
                $this->mobiletheme = true;
                break;
            case 2:
                $this->tablettheme = true;
                break;
            default:
                $this->mobiletheme = false;
                $this->tablettheme = false;
                break;
        }
    }

    public function set_user_preference($userpreference, $defaultuserpreference, $defaulttogglepersistence) {
        $this->defaultuserpreference = $defaultuserpreference;
        $this->defaulttogglepersistence = $defaulttogglepersistence;
        $coursenumsections = $this->courseformat->get_last_section_number();
        if ($userpreference != null) {
            $this->isoldtogglepreference = $this->togglelib->is_old_preference($userpreference);
            if ($this->isoldtogglepreference == true) {
                $ts1 = base_convert(substr($userpreference, 0, 6), 36, 2);
                $ts2 = base_convert(substr($userpreference, 6, 12), 36, 2);
                $thesparezeros = "00000000000000000000000000";
                if (strlen($ts1) < 26) {
                    // Need to PAD.
                    $ts1 = substr($thesparezeros, 0, (26 - strlen($ts1))) . $ts1;
                }
                if (strlen($ts2) < 27) {
                    // Need to PAD.
                    $ts2 = substr($thesparezeros, 0, (27 - strlen($ts2))) . $ts2;
                }
                $tb = $ts1 . $ts2;
                $this->togglelib->set_toggles($tb);
            } else {
                // Check we have enough digits for the number of toggles in case this has increased.
                $numdigits = $this->togglelib->get_required_digits($coursenumsections);
                $totdigits = strlen($userpreference);
                if ($numdigits > $totdigits) {
                    if ($this->defaultuserpreference == 0) {
                        $dchar = $this->togglelib->get_min_digit();
                    } else {
                        $dchar = $this->togglelib->get_max_digit();
                    }
                    for ($i = $totdigits; $i < $numdigits; $i++) {
                        $userpreference .= $dchar;
                    }
                } else if ($numdigits < $totdigits) {
                    // Shorten to save space.
                    $userpreference = substr($userpreference, 0, $numdigits);
                }
                $this->togglelib->set_toggles($userpreference);
            }
        } else {
            $numdigits = $this->togglelib->get_required_digits($coursenumsections);
            if ($this->defaultuserpreference == 0) {
                $dchar = $this->togglelib->get_min_digit();
            } else {
                $dchar = $this->togglelib->get_max_digit();
            }
            $userpreference = '';
            for ($i = 0; $i < $numdigits; $i++) {
                $userpreference .= $dchar;
            }
            $this->togglelib->set_toggles($userpreference);
        }
    }

    protected function get_row_class() {
        if ($this->bsnewgrid) {
            return 'row';
        } else {
            return 'row-fluid';
        }
    }

    protected function get_column_class($columns) {
        if ($this->bsnewgrid) {
            $colclasses = array(
                1 => 'col-sm-12 col-md-12 col-lg-12',
                2 => 'col-sm-6 col-md-6 col-lg-6',
                3 => 'col-md-4 col-lg-4',
                4 => 'col-lg-3');
        } else {
            $colclasses = array(1 => 'span12', 2 => 'span6', 3 => 'span4', 4 => 'span3');
        }

        return $colclasses[$columns];
    }

    public function get_format_responsive() {
        return $this->formatresponsive;
    }
    //Add for user manual completion <Revathi>
    public function course_section_cm_completions($course, &$completioninfo, cm_info $mod, $displayoptions = array()) {
        global $CFG, $DB;
        $output = '';
        if (!empty($displayoptions['hidecompletion']) || !isloggedin() || isguestuser() || !$mod->uservisible) {
            return $output;
        }
        if ($completioninfo === null) {
            $completioninfo = new completion_info($course);
        }
        $completion = $completioninfo->is_enabled($mod);
        if ($completion == COMPLETION_TRACKING_NONE) {
            if ($this->page->user_is_editing()) {
                $output .= html_writer::span('&nbsp;', 'filler');
            }
            return $output;
        }

        $completiondata = $completioninfo->get_data($mod, true);
        $completionicon = '';
        if($completiondata->completionstate == 1){
            $act_completedon =  date("dS M Y", $completiondata->timemodified);
            // print_object($act_completedon);
        }
        // if ($mod->uservisible) {
            // echo "hii";
            // print_object($mod->uservisible);
            if ($this->page->user_is_editing()) {
                switch ($completion) {
                    case COMPLETION_TRACKING_MANUAL :
                        $completionicon = 'manual-enabled'; 
                        $completiontitle = 'manual-enabled'; break;
                    case COMPLETION_TRACKING_AUTOMATIC :
                        $completionicon = 'auto-enabled'; 
                        $completiontitle = 'auto-enabled'; break;
                }
                if ($completion == COMPLETION_TRACKING_MANUAL) {
                    switch($completiondata->completionstate) {
                        case COMPLETION_INCOMPLETE:
                            $completionicon = 'format_play1' . ($completiondata->overrideby ? '-override' : '');
                            $completiontitle = 'manual-n';
                            break;
                        case COMPLETION_COMPLETE:
                            $completionicon = 'format_check1' . ($completiondata->overrideby ? '-override' : '');
                            $completiontitle = 'manual-y';
                            break;
                    }
                 } 
                else { // Automatic
                    switch($completiondata->completionstate) {
                        case COMPLETION_INCOMPLETE:
                            $completionicon = 'format_play1' . ($completiondata->overrideby ? '-override' : '');
                            break;
                        case COMPLETION_COMPLETE:
                            $completionicon = 'format_check1' . ($completiondata->overrideby ? '-override' : '');
                            break;
                        // case COMPLETION_COMPLETE_PASS:
                        //     $completionicon = 'inprogress'; break;
                        // case COMPLETION_COMPLETE_FAIL:
                        //     $completionicon = 'completed'; break;
                    }
                }
            } else if ($completion == COMPLETION_TRACKING_MANUAL) {
                switch($completiondata->completionstate) {
                    case COMPLETION_INCOMPLETE:
                        $completionicon = 'format_play1' . ($completiondata->overrideby ? '-override' : '');
                        $completiontitle = 'auto-n';
                        break;
                    case COMPLETION_COMPLETE:
                        $completionicon = 'format_check1' . ($completiondata->overrideby ? '-override' : '');
                        $completiontitle = 'auto-y';
                        break;
                    case COMPLETION_COMPLETE_PASS:
                        $completionicon = 'auto-pass';
                        $completiontitle = 'auto-pass'; break;
                    case COMPLETION_COMPLETE_FAIL:
                        $completionicon = 'auto-fail';
                        $completiontitle = 'auto-fail'; break;
                }
            } else { // Automatic
                switch($completiondata->completionstate) {
                    case COMPLETION_INCOMPLETE:
                        $completionicon = 'format_play1' . ($completiondata->overrideby ? '-override' : '');
                        $completiontitle = 'auto-n';
                        break;
                    case COMPLETION_COMPLETE:
                        $completionicon = 'format_check1' . ($completiondata->overrideby ? '-override' : '');
                        $completiontitle = 'auto-y';
                        break;
                    case COMPLETION_COMPLETE_PASS:
                        $completionicon = 'format_check1';
                        $completiontitle = 'auto-pass'; break;
                    case COMPLETION_COMPLETE_FAIL:
                        $completionicon = 'format_play1';
                        $completiontitle = 'auto-fail'; break;
                }
            }
        // }
        // else{
        //     echo "buy";
        //     print_object($mod->uservisible);
        //     $completionicon = 'format_lock1';
        //     $completiontitle = 'manual-enabled';
        // }
        if ($completionicon) {
            $formattedname = $mod->get_formatted_name();
            if ($completiondata->overrideby) {
                $args = new stdClass();
                $args->modname = $formattedname;
                $overridebyuser = \core_user::get_user($completiondata->overrideby, '*', MUST_EXIST);
                $args->overrideuser = fullname($overridebyuser);
               // print_object($completiontitle);
                $imgalt = get_string('completion-alt-' . $completiontitle, 'completion', $args);
            } else {
                //print_object($completiontitle);
                $imgalt = get_string('completion-alt-' . $completiontitle, 'completion', $formattedname);
            }

            if ($this->page->user_is_editing()) {
                // When editing, the icon is just an image.
                $completionpixicon = new pix_icon($completionicon, $imgalt, 'format_toggletop',
                        array('title' => $imgalt, 'class' => 'iconsmall'));
                $output .= html_writer::tag('span', $this->output->render($completionpixicon),
                        array('class' => 'autocompletion'));
                $output .= html_writer::tag('span', $act_completedon, array('class' => 'act_comp_date pull-right'));

            } else if ($completion == COMPLETION_TRACKING_MANUAL) {
                $newstate =
                    $completiondata->completionstate == COMPLETION_COMPLETE
                    ? COMPLETION_INCOMPLETE
                    : COMPLETION_COMPLETE;
                // In manual mode the icon is a toggle form...

                // If this completion state is used by the
                // conditional activities system, we need to turn
                // off the JS.
                $extraclass = '';
                if (!empty($CFG->enableavailability) &&
                        core_availability\info::completion_value_used($course, $mod->id)) {
                    $extraclass = ' preventjs';
                }
                $output .= html_writer::start_tag('form', array('method' => 'post',
                    'action' => new moodle_url('/course/togglecompletion.php'),
                    'class' => 'togglecompletion'. $extraclass));
                $output .= html_writer::start_tag('div');
                $output .= html_writer::empty_tag('input', array(
                    'type' => 'hidden', 'name' => 'id', 'value' => $mod->id));
                $output .= html_writer::empty_tag('input', array(
                    'type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()));
                $output .= html_writer::empty_tag('input', array(
                    'type' => 'hidden', 'name' => 'modulename', 'value' => $mod->name));
                $output .= html_writer::empty_tag('input', array(
                    'type' => 'hidden', 'name' => 'completionstate', 'value' => $newstate));
                $output .= html_writer::tag('button',
                    $this->output->pix_icon($completionicon, $imgalt, 'format_toggletop'), array('class' => 'btn btn-link'));
                $output .= html_writer::end_tag('div');
                $output .= html_writer::end_tag('form');
                $output .= html_writer::tag('span', $act_completedon, array('class' => 'act_comp_date pull-right'));
            } else {
                // In auto mode, the icon is just an image.
                $completionpixicon = new pix_icon($completionicon, $imgalt, 'format_toggletop',
                        array('title' => $imgalt));
                $output .= html_writer::tag('span', $this->output->render($completionpixicon),
                array('class' => 'autocompletion'));
                $output .= html_writer::tag('span', $act_completedon, array('class' => 'act_comp_date pull-right'));
                
            }
        }
        return $output;
    }
}
