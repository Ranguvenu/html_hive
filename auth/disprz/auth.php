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
 * Authenticate 
 *
 * @package   auth_disprz
 * @copyright info@eabyas.in
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use auth_disprz\admin\disprz_settings;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir.'/authlib.php');

/**
 * Plugin for disprz authentication.
 *
 * @copyright  Brendan Heywood <brendan@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class auth_plugin_disprz extends auth_plugin_base {
    /**
     * @var array $defaults The config defaults
     */
        function init_plugin($authtype) {
          $this->pluginconfig = 'auth_'.$authtype;
          $this->config = get_config($this->pluginconfig);



       }
      /**
     * Constructor with initialisation.
     */
    public function __construct() {
        $this->authtype = 'disprz';
        $this->roleauth = 'auth_disprz';
        $this->errorlogtag = '[AUTH disprz] ';
        $this->init_plugin($this->authtype);
    }
       
    /**
     * A debug function, dumps to the php log
     *
     * @param string $msg Log message
     */
    private function log($msg) {
        if ($this->config->debug) {
            // @codingStandardsIgnoreStart
            error_log('auth_disprz: ' . $msg);
            // @codingStandardsIgnoreEnd
        }
    }

       /**
     * We don't manage passwords internally.
     *
     * @return bool Always false
     */
    public function is_internal() {
        return false;
    }

   

    /**
     * Shows an error page for various authentication issues.
     *
     * @param string $msg The error message.
     */
    public function error_page($msg) {
        global $PAGE, $OUTPUT;

        $logouturl = new moodle_url('/auth/disprz/logout.php');

        $PAGE->set_context(context_system::instance());
        $PAGE->set_url('/');
        echo $OUTPUT->header();
        echo $OUTPUT->box($msg);
        echo html_writer::link($logouturl, get_string('logout'));
        echo $OUTPUT->footer();
        exit;
    }

    /**
     * All the checking happens before the login page in this hook
     */
    public function pre_loginpage_hook() {

        global $SESSION;

        $this->log(__FUNCTION__ . ' enter');
        $this->loginpage_hook();
        $this->log(__FUNCTION__ . ' exit');
    }

    /**
     * All the checking happens before the login page in this hook
     */
    public function loginpage_hook() {
        $this->execute_callback('auth_disprz_loginpage_hook');

        $this->log(__FUNCTION__ . ' enter');

        // If the plugin has not been configured then do NOT try to use auth_disprz.
        if ($this->is_configured() === false) {
            return;
        }

        if ($this->should_login_redirect()) {
            exit;
            //$this->disprz_login();
        } else {
            $this->log(__FUNCTION__ . ' exit');
            return;
        }

    }

    
    /**
     * All the checking happens before the login page in this hook
     */
    public function disprz_login($userinfo,$password,$courseid,$returnUrl) {

        // @codingStandardsIgnoreStart
        global $CFG, $DB, $USER, $SESSION;
        // @codingStandardsIgnoreEnd

        require_once("$CFG->dirroot/login/lib.php");

         $tokenrec = new \stdClass;
        $tokenrec->username = $userinfo->username;
        $tokenrec->firstname = $userinfo->firstname;
        $tokenrec->lastname = $userinfo->lastname;
        $tokenrec->email = $userinfo->email;
        $tokenrec->timecreated = time();

        $tokenrec->id = $DB->insert_record('auth_disprz_temp', $tokenrec);


        $user = $DB->get_record('user', array('username'=>$username, 'mnethostid'=>$CFG->mnet_localhost_id));
       
        // We store the IdP in the session to generate the config/config.php array with the default local SP.
        $newuser = false;
       
        if (!$user) {
            
                $this->log(__FUNCTION__ . " user '$uid' is not in moodle so autocreating");
                $user = create_user_record($username, '', 'disprz');
               
                $newuser = true;
          
        } else {
            // Prevent access to users who are suspended.
            if ($user->suspended) {
                $this->error_page(get_string('suspendeduser', 'auth_disprz', $uid));
            }
            // Make sure all user data is fetched.
            $user = get_complete_user_data('username', $user->username);
            $this->log(__FUNCTION__ . ' found user '.$user->username);
        }
        
            $this->update_user_profile_fields($user, $email,$firstname,$lastname);


        if ($user->auth != 'disprz') {
            $this->log(__FUNCTION__ . " user $uid is auth type: $user->auth");
            $this->error_page(get_string('wrongauth', 'auth_disprz', $uid));
        }

        $this->enrol_todisprzcourse($user,$courseid);

        // If admin has been set for this IdP we make the user an admin.
        // Make sure all user data is fetched.
        $user = get_complete_user_data('username', $user->username);
        complete_user_login($user);
        $USER->loggedin = true;
        $USER->site = $CFG->wwwroot;
        set_moodle_cookie($USER->username);

        $SESSION->returnUrl= $returnUrl;
        $urltogo =$activityurl;
        $lourl= core_login_get_return_url();
        $qlogin =qualified_me();
        //$urltogo = core_login_get_return_url();
        // If we are not on the page we want, then redirect to it.
        if ( qualified_me() !== $urltogo ) {
            $this->log(__FUNCTION__ . " redirecting to $urltogo");
            redirect($urltogo);
            exit;
        } else {
            $this->log(__FUNCTION__ . " continuing onto " . qualified_me() );
        }

        return;
    }

    /**
     * Simplifies attribute key names
     *
     * Rather than attempting to have an explicity mapping this simply
     * detects long key names which contain non word characters and then
     * grabs the last useful component of the string. Note it creates new
     * keys, doesn't remove the old ones, and will not overwrite keys either.
     */
    public function simplify_attr($attributes) {

        foreach ($attributes as $key => $val) {
            if (preg_match("/\W/", $key)) {
                $parts = preg_split("/\W/", $key);
                $simple = $parts[count($parts) - 1];
                $attributes[$simple] = $attributes[$key];
            }
        }
        return $attributes;
    }

   
   
    /**
     * {@inheritdoc}
     */
    public function user_login($username, $password) {
        return false;
    }

    /**
     * Processes and stores configuration data for this authentication plugin.
     *
     * @param object $config
     * @return boolean
     */
    public function process_config($config) {
        $haschanged = false;

        foreach (array_keys($this->defaults) as $key) {
            if ($config->$key != $this->config->$key) {
                set_config($key, $config->$key, 'auth_disprz');
                $haschanged = true;
            }
        }

        if ($haschanged) {
            $file = $this->get_file_sp_metadata_file();
            @unlink($file);
        }
        return true;
    }

     /**
     * Reads any other information for a user from external database,
     * then returns it in an array.
     *
     * @param string $username
     * @return array
     */
    function get_userinfo($email) {
        global $CFG;

        $extusername = core_text::convert($email, 'utf-8', $this->config->extencoding);

        // Array to map local fieldnames we want, to external fieldnames.

        $result = array();
       
        $result['email'] = core_text::convert($email, $this->config->extencoding, 'utf-8');
                  
        return $result;
    }
    
    /**
     * Allow auth method to be manually set for users e.g. bulk uploading users.
     */

    public function can_be_manually_set() {
        return true;
    }

   public function update_user_profile_fields(&$user, $email) {
       global $CFG;
       require_once($CFG->dirroot . '/user/lib.php');

        $user->email= $email;
       
        user_update_user($user, false, false);
        // Save custom profile fields.
        profile_save_data($user);
        $update = true;
       return $update;


  }

    
    /**
     * Execute callback function
     * @param $function name of the callback function to be executed
     * @param string $file file to find the function
     */
    private function execute_callback($function, $file = 'lib.php') {
        if (function_exists('get_plugins_with_function')) {
            $pluginsfunction = get_plugins_with_function($function, $file);
            foreach ($pluginsfunction as $plugintype => $plugins) {
                foreach ($plugins as $pluginfunction) {
                    $pluginfunction();
                }
            }
        }
    }

    public function enrol_todisprzcourse($user,$courseid) {
        global $DB,$CFG,$SESSION;
        
         $sql="SELECT ra.* FROM {course} c JOIN {context} ctx ON c.id = ctx.instanceid AND ctx.contextlevel = 50
        JOIN {role_assignments} ra ON ra.contextid = ctx.id AND ra.userid = ? AND c.id= ?";
        $enrolormnot=$DB->get_field_sql($sql,[$user->id,$courseid]);
    
        if (empty($SESSION->traincourseid)) {
        $SESSION->traincourseid = $courseid;
        }
        if(!$enrolormnot) {
            $instance = $DB->get_record('enrol', array('courseid' => $courseid, 'enrol' => 'self'));
            $plugin = enrol_get_plugin('self');

            if (empty($instance)) {
            // Only add an enrol instance to the course if non-existent
                $course = $DB->get_record('course', array('id' => $courseid));
                $enrolid = $plugin->add_instance($course);
                $instance = $DB->get_record('enrol', array('id' => $enrolid));
            }

            $roleid = 5;;
            $timestart=0;
            $timeend=0;
            // not anymore so
            $plugin->enrol_user($instance, $user->id,$roleid,$timestart,$timeend);
        }
    }

}

