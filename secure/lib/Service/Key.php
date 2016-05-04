<?php
/*******************************************************************************
Key.php
Key service for accessing RD

Created by Troy Hurteau, NCSU Libraries, NC State University (libraries.opensource@ncsu.edu).

This file is part of NCSU's distribution of ReservesDirect. This version has not been downloaded from Emory University
or the original developers of ReservesDirect. Neither Emory University nor the original developers of ReservesDirect have authorized
or otherwise endorsed or approved this distribution of the software.

Copyright (c) 2004-2006 Emory University, Atlanta, Georgia.

Licensed under the NCSU ReservesDirect License, Version 2.0 (the "License"); 
you may not use this file except in compliance with the License. You may obtain a copy of the full License at
 http://www.lib.ncsu.edu/it/opensource/

ReservesDirect is distributed in the hope that it will be useful,
but is distributed "AS IS" and WITHOUT ANY WARRANTY, without even the
implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR
PURPOSE, and without any warranty as to non-infringement of any third
party's rights. See the License for the specific language governing permissions and limitations under the License.

The original version of ReservesDirect is located at:
http://www.reservesdirect.org/

This version of ReservesDirect, distributed by NCSU, is located at:
http://code.google.com/p/reservesdirect-ncsu/

*******************************************************************************/
require_once(APPLICATION_PATH . '/lib/Rd/Config.php');

/**
 * 
 * Service for logging into RD via a private key for specific functions.
 * @author jthurtea
 *
 */

class Service_Key{
	
	protected static $_privateKeys = null;
	
	const RD_SERVICE_PERM_LEVEL = 0; //#TODO this is the same as student :/
	
	static public function authenticate(){
		self::_initKeys();
		if(!isset($_SESSION)){
			session_start();
		}
		if (array_key_exists('rd_service_key', $_REQUEST)){
			$_SESSION['service_auth'] = self::_validate($_REQUEST['rd_service_key']);
			return $_SESSION['service_auth'];
		} else if(array_key_exists('service_auth', $_SESSION) && $_SESSION['service_auth']){
			return true;
		} else  {
			return false;
		}
	}
	
	static protected function _validate($key){
		return in_array(trim($key), self::$_privateKeys);
	}
	
	static protected function _initKeys(){
		if (is_null(self::$_privateKeys)){
			$keys = Rd_Config::get('serviceKeys:+');
			self::$_privateKeys = array();
			if(isset($keys->key) && $keys->children() && $keys->children()->count() > 1){
				foreach($keys->children() as $key){
					if(strlen(trim($key)) >= 16){ // Keys need to have a secure length...
						self::$_privateKeys[] = trim($key);
					}
				}
			} else {
				self::$_privateKeys[] = trim($keys->key);
			}
		}
	}
	
	static public function getPrimary(){
		self::_initKeys();
		return self::$_privateKeys[0];
	}
	
}