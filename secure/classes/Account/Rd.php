<?php
/*******************************************************************************
Account/Rd.php
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

class Account_Rd
{

	protected static $_userLevel = -1;
	
	protected static $_userClass = 'guest';
	
	protected static $_userName = '';
	
	protected static $_userId = NULL;
	
	protected static $_user = NULL;
	
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
	
	public static function init() //#TODO update this now that Auth has been re-written
	{
		if (isset($_SESSION) 
			&& is_array($_SESSION) 
			&& array_key_exists('userclass', $_SESSION)
		) {
			self::$_userClass = $_SESSION['userclass'];
			if ('' == trim(self::$_userClass)) {
				self::$_userClass = 'guest';
			}
			$map = array_flip(self::$_classLevelMap);
			self::$_userLevel = (
				array_key_exists(self::$_userClass, $map)
				? $map[self::$_userClass]
				: self::LEVEL_GUEST
			);
			if (
				array_key_exists('username', $_SESSION)
				&& self::$_userClass >= self::LEVEL_STUDENT
			) {
				self::$_userName = $_SESSION['username'];
				self::$_userId = self::getId();
				self::$_user = Rd_Registry::get('root:userInterface');
			}
		}
	}
	
	public static function getClassLevelMap()
	{
		return self::$_classLevelMap;	
	}
	
	public static function getLevelClassMap()
	{
		return array_flip(self::$_classLevelMap);	
	}

	public static function getLevel()
	{
		return self::$_userLevel;
	}
	
	public static function getClass()
	{
		return self::$_userClass;
	}
	
	public static function getName()
	{
		return self::$_userName;
	}
	
	public static function isAdmin()
	{
		return self::$_userLevel == self::LEVEL_ADMIN;
	}
	
	public static function isStaff()
	{
		return self::$_userLevel == self::LEVEL_STAFF;
	}
	
	public static function atLeastStaff()
	{
		return self::$_userLevel >= self::LEVEL_STAFF;
	}
	
	public static function lessThanStaff()
	{
		return self::$_userLevel <= self::LEVEL_STAFF;
	}
	
	public static function isFaculty()
	{
		return self::$_userLevel == self::LEVEL_FACULTY;
	}
	
	public static function atLeastFaculty()
	{
		return self::$_userLevel >= self::LEVEL_FACULTY;
	}
	
	public static function isStudent()
	{
		return self::$_userLevel == self::LEVEL_STUDENT;
	}
	
	public static function atLeastStudent()
	{
		return self::$_userLevel >= self::LEVEL_STUDENT;
	}
	
	public static function isGuest()
	{
		
		return self::$_userLevel == self::LEVEL_GUEST;
	}

	public static function getId()
	{
		if (is_null(self::$_userId) && '' != trim(self::$_userName)) {
			$userName = self::$_userName;
			$result = Rd_Pdo::query("SELECT user_id FROM users WHERE username ='{$userName}' LIMIT 1;");
			self::$_userId = $result->fetchColumn();
		}
		return self::$_userId;
	}
	
	public static function getUserInterface()
	{
		return self::$_user ? self::$_user : new Account_Nonuser();
	}
	
	public static function levelCount()
	{
		return 6; //don't include guest
	}
}