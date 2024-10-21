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


require_once('../../config.php');
//require_once($CFG->dirroot . "/local/courses/classes/encry.php");
require_once($CFG->dirroot . "/auth/disprz/classes/encry.php");

defined('MOODLE_INTERNAL') || die();

global $CFG,$DB,$SESSION;
require_once($CFG->libdir.'/authlib.php');
$sso = required_param('sso',  PARAM_RAW);
$returnUrl= optional_param('returnUrl', '', PARAM_RAW);


$sso= rawurldecode($sso);

if (!isset($sso)) {
throw new moodle_exception('Query parameters are missing or not as expected.', 'error');

}
                    

  $cryptor = new Procryptor();
  $token = $cryptor->decrypt($sso);;
  $params= explode("&",$token);

 $userinfo= str_replace("userid=",'',$params['0']);
 $activityurl=str_replace('activity=','',$params['3']);
 $courseid=str_replace('courseid=','',$params['4']);

 $SESSION->disperzcourseid=NULL;



//$user= $DB->get_record('user',array('id'=>$userid));
//$userinfo->username= $user->username;
//$email= $user->email;

//if($username) {
	$password = generate_password(8);
	$disprzauth = get_auth_plugin('disprz');
 	$disprzauth->disprz_login($userinfo,$password,$courseid,$returnUrl);

// }
// else {

//     throw new moodle_exception('Invalid Logins,please try with valid login details.', 'error');

// }

?>
