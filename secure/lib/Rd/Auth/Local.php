<?php
/*******************************************************************************
Rd/Auth/Local.php
Implements an Authentication Plugin for the Local DB

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

class Rd_Auth_Local extends Rd_Auth_Abstract{

	public static function auth()
	{
		if (
			'' != self::getProvidedUsername()
			&& '' != self::getProvidedPassword()
		) {
			return self::_attemptLogin();
		}
		self::fail();
		return false;
	}
	
	public static function getProvidedUsername()
	{
		return (
			array_key_exists('username', $_POST)
			? trim($_POST['username'])
			: '' 
		);
	}
	
	public static function getProvidedPassword()
	{
		return (
			array_key_exists('password', $_POST)
			? trim($_POST['password'])
			: '' 
		);
	}	

	protected static function _attemptLogin()
	{
		$username = self::getProvidedUsername();
		$password = self::getProvidedPassword();
		$user = new user();
		try{
			if($user->getUserByUserName_Pwd($username, md5($password))) {
				Rd_Auth::setStatus(true, $user);
				return true;
			} else {
				Rd_Auth::setStatus(false, NULL, 
					$user->getUserByUserName_Pwd($username, md5($password),true)
					? '004'
					: '003'
				);
				self::fail();
				return false;
			}
		} catch (Exception $e) {
			if (Rd_Debug::isEnabled()) {
				Rd_Debug::out($e->getMessage());
			}
			Rd_Auth::setStatus(false, NULL, '003');
			self::fail();
			return false;
		}
	}
	
	protected static function _succeed()
	{
		$user = new user();
		$username = self::getProvidedUsername();
		if ('' == trim($username)) {
			self::fail();
			return false;
		}
		if (!$user->getUserByUserName($username)) {
			Rd_Auth::setStatus(false, NULL, '020');			
		} else {
			Rd_Auth::setStatus(true, $user);
			return true;
		}
	}
	
	public static function fail()
	{
		if (Rd_Debug::isEnabled()) {
			Rd_Debug::out('Local Authentication Declined');
		}
	}
	
	public static function getUserInfo()
	{
		return array(); //#TODO implement
	}

	
}