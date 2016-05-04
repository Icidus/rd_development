<?php
/*******************************************************************************
Rd/Auth/Ldap.php
Implements an Authentication Plugin for Ldap

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

require_once(APPLICATION_PATH . '/classes/ldapAuthN.class.php');

class Rd_Auth_Ldap extends Rd_Auth_Abstract{

	protected static $_conf = array();
	protected static $_initialized = false;
	protected static $_ldapConnector = NULL;
	
	protected static function _init()
	{
		if (!self::$_initialized) {
			self::$_conf = Rd_Registry::get('root:ldapConf');
			self::$_ldapConnector = new ldapAuthN(self::$_conf);
			//#TODO throw an error if required fields are not provided...
		}
		self::$_initialized = true;
	}
	
	public static function auth()
	{
		self::_init();
		if (
			'' != self::getProvidedUsername()
			&& '' != self::getProvidedPassword()
			&& self::_attemptLogin()
		) {
			return self::_succeed();
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
			if(self::$_ldapConnector->auth($username, $password)) {
				Rd_Auth::setStatus(true, $user);
				return true;
			} else {
				if (-1 == self::$_ldapConnector->getErrorNumber()) {
					Rd_Auth::setStatus(false, null, '002');
				} else {
					Rd_Auth::setStatus(false, NULL, '003');
				}
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
	
	public static function getUserInfo()
	{
		self::$_ldapConnector->search();
		$userInfo = self::$_ldapConnector->getUserInfo();
		return array(
			'username' => self::getProvidedUsername(),
			'firstName' => (
				array_key_exists(self::$_conf['firstname'], $userInfo)
				? $userInfo[self::$_conf['firstname']][0]
				: ''
			),
			'lastName' => (
				array_key_exists(self::$_conf['lastname'], $userInfo)
				? $userInfo[self::$_conf['lastname']][0]
				: ''
			),
			'email' => (
				array_key_exists(self::$_conf['email'], $userInfo)
				? $userInfo[self::$_conf['email']][0]
				: ''
			)
		);
	}
	
	public static function fail()
	{
		if (Rd_Debug::isEnabled()) {
			Rd_Debug::out('LDAP Authentication Declined');
		}
	}
}

/*
function ncsuldapUIDLookup($uid, &$userInfo, $doMerge=TRUE){
	global $g_ldap;
	$userInfo = array();

	// server/context definitions
	define("NCSU_LDAP_SERVER",$g_ldap['domain']);
	define("NCSU_LDAP_CONTEXT",$g_ldap['basedn']);

	$ldapConnect = ldap_connect(NCSU_LDAP_SERVER);		
	ldap_bind($ldapConnect); // a. nony mous
	$context = NCSU_LDAP_CONTEXT;
	$searchstring = "uid=".$uid;
	$searchResult = ldap_search($ldapConnect,$context,$searchstring,array("*","+"));
	$userInfo['uid'] = $uid;
	$userInfo['info']['has_account'] = FALSE;
	$accountInfo = array();
	$userInfo['info']['is_employee'] = FALSE;
	$employeeInfo = array();
	$userInfo['info']['is_student'] = FALSE;
	$studentInfo = array();
	for(
		$entryID = ldap_first_entry($ldapConnect,$searchResult);
		$entryID != FALSE;
		$entryID = ldap_next_entry($ldapConnect,$entryID)
	){
		$thisEntry = array();
		$thisDN = '';
		$thisDN = ldap_get_dn($ldapConnect,$entryID);
		$thisEntry = ldap_get_attributes($ldapConnect,$entryID);
		
		if(!(isset($thisEntry))) continue;
		
		// parse dn
		$dnarray = explode(',',$thisDN);
		$checkou = $dnarray[1];
		switch($checkou) {
			
			case "ou=accounts":
				$userInfo['info']['has_account'] = TRUE;
				$dataInfo = &$accountInfo;
				break;
				
			case "ou=employees":
				$userInfo['info']['is_employee'] = TRUE;
				$dataInfo = &$employeeInfo;
				break;	
				
			case "ou=students":		
				$userInfo['info']['is_student'] = TRUE;
				$dataInfo = &$studentInfo;
				break;					
		
			// not dealing with a group/printer/host/other
			// somehow (don't know how) keyed by identifier uid=$uid...
			default:
				continue 2;
		}
		
		foreach($thisEntry as $attribute => $value) {
			if(!(is_array($value))) continue;
			if($attribute == "uid") continue;
			if($attribute == "count") continue;
			
			if($value['count'] > 1) {
				$dataInfo[$attribute] = $value;
				unset($dataInfo[$attribute]['count']);
			}
			else {
				$dataInfo[$attribute] = $value[0];	
			}
		}	
	}
	// merge information student, then employee, then account

	if($userInfo['info']['is_student']) {
		if($doMerge) $userInfo = array_merge($userInfo,$studentInfo);
		$userInfo['info']['student'] = $studentInfo;
	}

	if($userInfo['info']['is_employee']) {
		if($doMerge) $userInfo = array_merge($userInfo,$employeeInfo);
		$userInfo['info']['employee'] = $employeeInfo;
	}
			
	if($userInfo['info']['has_account']) {
		if($doMerge) $userInfo = array_merge($userInfo,$accountInfo);
		$userInfo['info']['account'] = $accountInfo;
	}
		
	if($doMerge) {
		// merged values we don't care about:
		$noMergeAttribs = array('objectClass',
			 'structuralObjectClass',
			 'entryUUID',
			 'creatorsName',
			 'createTimestamp',
			 'modifyTimestamp',
			 'subschemaSubentry',
			 'hasSubordinates',
			 'modifiersName',
			 'entryCSN'
		);

		foreach($noMergeAttribs as $attribute) {
			unset($userInfo[$attribute]);
		}
	}

	return true;
}
*/

/*
function authByLDAP($username, $password) {
	global $g_ldap;
	$ldap = new ldapAuthN();
	$user = new user();
	
	//try to authenticate against ldap
	if($ldap->auth($username, $password)) {
		//passed authentication, try to get user from DB
		if(!$user->getUserByUserName($username)) {	//if user record not found in our DB, attempt to create one
			//get directory info
			$ldap->search();
			$user_info = $ldap->getUserInfo();			
			//create a new record with directory info
			//(LDAP returns username in caps, so strtolower() it)
			$resultUsername = (
				array_key_exists($g_ldap['firstname'], $user_info)
				&& is_array($user_info[$g_ldap['firstname']]) 
					&& '' != trim($user_info[$g_ldap['canonicalName']][0]) 
				? trim(strtolower($user_info[$g_ldap['canonicalName']][0]))
				: addslashes(substr(trim(strtolower($username)),0,12))
			);
			$resultFirstname = (
				array_key_exists($g_ldap['firstname'], $user_info) 
					&& is_array($user_info[$g_ldap['firstname']]) 
				? trim($user_info[$g_ldap['firstname']][0])
				: ''
			);
			$resultLastname = (
				array_key_exists($g_ldap['lastname'], $user_info) 
					&& is_array($user_info[$g_ldap['lastname']]) 
				? trim($user_info[$g_ldap['lastname']][0])
				: ''
			);
			$resultEmail = (
				array_key_exists($g_ldap['email'], $user_info) 
					&& is_array($user_info[$g_ldap['email']]) 
				? trim(strtolower($user_info[$g_ldap['email']][0]))
				: ''
			);
			$user->createUser($resultUsername, $resultFirstname, $resultLastname, $resultEmail, 0);
		}
				
		//user is now authenticated, set the session vars
		setAuthSession(true, $user);
		//return true; #execution will end above
	} else {
		//unset these
		//setAuthSession(false, null, '002');
		if (-1 == $ldap->getErrorNumber()) {
			setAuthSession(false, null, '002');
			//throw new Exception('Unable to contact the authentication service.');
		}
		return false;
	}
	
}*/