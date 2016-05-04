<?php
/*******************************************************************************
RD/User.php
Implements a utilty for managing user identity

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
/**
 * 
 * Class for managing user identity.
 * @author jthurtea
 *
 */

class Rd_User
{

	protected static $_userLevel = -1;
	
	protected static $_userClass = 'guest';
	
	protected static $_userName = '';
	
	const LEVEL_GUEST = -1;
	const LEVEL_STUDENT = 0;
	const LEVEL_CUSTOIAN = 1;
	const LEVEL_PROXY = 2;
	const LEVEL_FACULTY = 3;
	const LEVEL_STAFF = 4;
	const LEVEL_ADMIN = 5;
	
	protected static $_classLevelMap = array(
		-1 => 'guest',
		0 => 'student',
		1 => 'custodian',
		2 => 'proxy',
		3 => 'instructor',
		4 => 'staff',
		5 => 'admin'
	);
	
	public static function init()
	{
		if (isset($_SESSION) 
			&& is_array($_SESSION) 
			&& array_key_exists('userclass', $_SESSION)
		) {
			self::$_userClass = $_SESSION['userclass'];
			$map = array_flip(self::$_classLevelMap);
			self::$_userLevel = (
				array_key_exists(self::$_userClass, $map)
				? $map[self::$_userClass]
				: self::LEVEL_GUEST
			);
			if (array_key_exists('username', $_SESSION)
			) {
				self::$_userName = $_SESSION['username'];
			}
		}
	}

	public static function getLevel(){
		return self::$_userLevel;
	}
	
	public static function getClass(){
		return self::$_userClass;
	}
	
	public static function getName(){
		return self::$_userName;
	}
	
	public static function isAdmin(){
		return self::$_userLevel == self::LEVEL_ADMIN;
	}
	
}