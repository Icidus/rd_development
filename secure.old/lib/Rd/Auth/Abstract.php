<?php
/*******************************************************************************
Rd/Auth/Abstract.php
Defines an Authentication Plugin Base Class

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

abstract class Rd_Auth_Abstract{
	
	abstract public static function auth();
	
	public static function isLoggedIn()
	{
		return false;
	}
	
	public static function logout()
	{
		return true;
	}
	
	public static function getExternalLogoutUrl()
	{
		return '';
	}
	
	abstract public static function getProvidedUsername();
	
	public static function getProvidedPassword()
	{
		return '';
	}
	
	public static function getUserInfo(){
		return array();
	}
	
	abstract public static function fail();
	
	protected static function _succeed()
	{
		$user = new user();
		$username = Rd_Auth::getPluginProvidedUsername();
		if ('' == trim($username)) {
			Rd_Auth::failPlugin();
			return false;
		}
		if (!$user->getUserByUserName($username)) {
			if (Rd_Auth::willAutocreateUsers()){
				$userInfo = Rd_Auth::getPluginUserInfo();
				if (Rd_Auth::createUser($user,$userInfo)) {
					Rd_Auth::setStatus(true, $user);
					return true;
				}
				Rd_Auth::setStatus(false, $user, '011');
				return false;
			} else {
				Rd_Auth::setStatus(false, NULL, '009');
			}			
		} else {
			Rd_Auth::setStatus(true, $user);
			return true;
		}
	}
	
	public static function setPluginStatus($success, $errorCode)
	{
		//only some plugins will need to do this.
	}
}
	