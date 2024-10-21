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
 * Force login page for PMP
 *
 * @package    auth_disprz
 * @copyright  eabyas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// This pagex is only used when dual auth is turned on. In this case
// the user is redirected to here which forces SAML auth and then
// returns to the wantsurl.

//namespace Encbiz;

class Procryptor
{

  public function encrypt($data)
  {
	    $method = 'aes-256-cbc';
      $encryption_key = '32461312abmceds';
      $key = substr(hash('sha256', $encryption_key, true), 0, 32);
      $iv="7894598721098294";
      $encrypted = base64_encode(openssl_encrypt($data, $method, $key, OPENSSL_RAW_DATA, $iv));
   return $encrypted;
  }

  // decrypt encrypted string
  public function decrypt($data)
  {
       $method= "aes-256-cbc";
       $iv="7894598721098294";
       $encryption_key = '32461312abmceds';
	  
     $key = substr(hash('sha256', $encryption_key, true), 0, 32);
    $decrypted = openssl_decrypt(base64_decode($data), $method, $key, OPENSSL_RAW_DATA, $iv);
     return $decrypted;
  }

}



?>