<?php
/**
 * This file is part of eAbyas
 *
 * Copyright eAbyas Info Solutons Pvt Ltd, India
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 * @package   local
 * @subpackage  coursera
 * @author eabyas  <info@eabyas.in>
**/
require_once(dirname(__FILE__) . '/../../config.php');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
use local_coursera\plugin;

//$token=plugin::get_token();

//$programid="udJCiY40EeuuSw7hyFGIcQ";
$settings= plugin::get_plugin_settings(); 
   $programs = $settings['programlist'];
echo 'hii123';
// print_object($programs);
$programs=explode(',',$programs);
$limit=1000;
$start=0;
foreach($programs as $programid){
   echo "Programid".$programid;
    
 $courses=plugin::get_programcourses($programid,$limit,$start);   
    
    
}
    


