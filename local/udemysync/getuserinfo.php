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
 * @subpackage  udemysync
 * @author eabyas  <info@eabyas.in>
**/
require_once(dirname(__FILE__) . '/../../config.php');

use local_udemysync\plugin;

 $testing=0;
$useremail="v_shashank.gudimella@fractal.ai";
$pagesize=1;
$vexist=array();
$vexist= plugin::verify_userlicence($useremail,$pagesize);
print_object($vexist);
/*if($vexist){
echo "User exist in Udemy System";
} else {
echo "Not exist in system";
} */


