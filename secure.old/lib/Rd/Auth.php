<?php
/*******************************************************************************
Rd/Auth.php
Implements an Authentication Plugin manager for RD

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

require_once(APPLICATION_PATH . '/classes/user.class.php');
require_once(APPLICATION_PATH . '/classes/users.class.php');
require_once(APPLICATION_PATH . '/lib/Rd/CodeLookup.php');
require_once(APPLICATION_PATH . '/lib/Rd/UrlRewrite.php');
require_once(APPLICATION_PATH . '/lib/Rd/Acl.php');

require_once(APPLICATION_PATH . '/lib/Rd/Auth/Abstract.php');

/**
 * 
 * Auth utility class.
 * @author jthurtea
 *
 */

class Rd_Auth{
	
	protected static $_loadedPlugins = array();
	protected static $_defaultPlugins = array();
	protected static $_initialized = false;
	protected static $_classMap = array();
	protected static $_autocreate = false;
	protected static $_authenticated = false;
	protected static $_activePlugin = null;
	protected static $_userObject = null;
	protected static $_errorMessages = array();
	
	public static function init()
	{
		if (self::$_initialized) {
			return;
		}
		$authConfigString = Rd_Registry::get('authenticationOptions');
		self::$_autocreate = (
			'true' == trim(strtolower(Rd_Registry::get('authenticationAutocreateUsers')))
		);
		$pluginOptions = explode(':', $authConfigString);
		$firstPass = true;
		foreach($pluginOptions as $optionItem) {
			$authChain = explode('+', $optionItem);
			foreach($authChain as $pluginName){
				if ($firstPass) {
					self::$_defaultPlugins[] = $pluginName;
				}
				if (!in_array($pluginName, self::$_loadedPlugins)) {
					self::$_loadedPlugins[] = $pluginName;
					$className = 'Rd_Auth_' . $pluginName;
					$classPath = APPLICATION_PATH . '/lib/' 
						. str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';
					require_once($classPath);
					self::$_classMap[$pluginName] = $className;
				}
				
			}
			$firstPass = false;
		}
		self::$_initialized = true;
	}
	
	public static function autodetect()
	{
		if(self::_login()){
			return true;
		}
		self::init();
		$plugins = (
			!array_key_exists('loginRealm', $_GET)
				|| !in_array(trim($_GET['loginRealm']),self::$_loadedPlugins)
			? self::$_defaultPlugins
			: array(trim($_GET['loginRealm']))
		);
		foreach($plugins as $pluginName){
			try {
				$pluginClass = self::$_classMap[$pluginName];
				$plugin = new $pluginClass();
				self::$_activePlugin = $plugin;
				if($plugin->auth() && self::$_authenticated) { //#TODO maybe too over zealous (artifact of old methods)
					return self::_login(self::$_userObject);
				} else {
					self::$_activePlugin = NULL;
				}
			} catch (Exception $e) {
				self::$_activePlugin = NULL;
				if (Rd_Debug::isEnabled()) {
					self::$_errorMessages[] = 
						"Exception in auth plugin {$pluginName} : " . $e->getMessage();
				}
			}
		}
		if (count(self::$_errorMessages) > 0) {
			count(self::$_errorMessages) == 1
			? Rd_Layout::setMessage(
				'loginError', self::$_errorMessages[0]
			) : Rd_Layout::setMessage(
				'loginError', 'Multiple errors: <ul><li>' 
				. implode('</li><li>', self::$_errorMessages) 
				. '</li></ul>'
			);
		}
		$usersObject = new users();
		Rd_Registry::set('root:userInterface',$usersObject->initUser('', ''));
		Account_Rd::init();
		return false;
	}
	
	public static function loginAs($userObject)
	{
		$u = Rd_Registry::get('root:userInterface');
		if ($u->getDefaultRole() < Account_Rd::LEVEL_ADMIN) {
			throw new Exception('ERROR: Attempting to switch user when not an admin.');
		}
		return self::_login($userObject);
	}
	
	protected static function _login($userObject=NULL){
		if (
			array_key_exists('username', $_SESSION) 
			&& array_key_exists('userclass', $_SESSION)
			&& '' != $_SESSION['username']
			&& '' != $_SESSION['userclass']
		) {
			$username = $_SESSION['username'];
			$userClass = $_SESSION['userclass'];
		} else if (!is_null($userObject)){
			$username = $userObject->getUsername();
			$userClass = $userObject->getUserClass();
			$_SESSION['username'] = $username;
			$_SESSION['userclass'] = $userClass;
		} else {
			return false;
		}
		if (
			isset($username) 
			&& '' != trim($username) 
			&& isset($userClass) 
			&& !is_null($userClass)
		) {
			$usersObject = new users(); 		
			$userInterface = $usersObject->initUser($userClass, $username);
			if(is_a($userInterface,'Account_Nonuser')) {
				self::logoutLocally();
			}
			Rd_Registry::set('root:userInterface',$userInterface);
			Account_Rd::init();
			return true;
		}
		return false;
	}
	
	public static function isLoggedIn()
	{
		self::init();
		return self::isExternallyLoggedIn() 
			|| array_key_exists('username', $_SESSION) 
			|| array_key_exists('userclass', $_SESSION);
	}
	
	public static function isExternallyLoggedIn()
	{
		foreach(self::$_loadedPlugins as $pluginName){
			$pluginClass = self::$_classMap[$pluginName];
			$plugin = new $pluginClass();
			if($plugin->isLoggedIn()){
				return true;
			}
		}
		return false;
	}
	
	public static function logout($realm='*')
	{
		self::logoutExternally();
		self::logoutLocally();
	}

	public static function logoutExternally($realm='*')
	{
		if('*' != $realm) {
			if (in_array(trim($realm),self::$_loadedPlugins)) {
				$realms = array($realm);
			}
			else {
				$realms = array();
			}
		} else {
			$realms = self::$_loadedPlugins;
		}
		foreach($realms as $pluginName){
			$pluginClass = self::$_classMap[$pluginName];
			$plugin = new $pluginClass();
			$plugin->logout();
		}
	}
	
	public static function getExternalLogoutUrl($pluginName='')
	{
		if ('' == $pluginName) {
			$pluginName = self::$_defaultPlugins[0];
		}
		$pluginClass = self::$_classMap[$pluginName];
		$plugin = new $pluginClass();
		return $plugin->getExternalLogoutUrl();
	}
	
	public static function logoutLocally($resetUser = true)
	{
		if (array_key_exists('username', $_SESSION) ) {
			unset($_SESSION['username']);
		}
		if (array_key_exists('userclass', $_SESSION)) {
			unset($_SESSION['userclass']);	
		}
		Rd_Registry::cleanSession();
		if ($resetUser) {
			Rd_Registry::set('root:userInterface', new Account_Nonuser());
		}
	}
	
	public static function pluginIsLoaded($name)
	{
		self::init();
		return in_array($name, self::$_loadedPlugins);
	}
	
	public static function getDefaultPlugin()
	{
		self::init();
		return self::$_defaultPlugins[0];
	}
	
	public static function getLoadedPlugins()
	{
		self::init();
		return self::$_loadedPlugins;
	}
	
	public static function willAutocreateUsers()
	{
		return self::$_autocreate;
	}
	
	public static function setStatus($success, $userObject = NULL, $errorCode = '')
	{
		self::$_authenticated = $success && !is_null($userObject);
		self::$_userObject = $userObject;
		self::$_activePlugin->setPluginStatus($success,$errorCode);
		if ('' != $errorCode) {
			$message = Rd_CodeLookup::tryMessage('loginFailure', $errorCode);
			self::$_errorMessages[] = (
				'' != trim($message)
				? $message
				: "Code Lookup Error: No data for status code for \"loginFailure:{$code}\"."
			);
		}
	}
	
	public static function createUser($user, $userInfo){
		if(!self::willAutocreateUsers()) {
			self::setStatus(false, NULL, '009');
			return false;
		}
		if(!array_key_exists('username', $userInfo)) {
			throw new Exception('Must specify username to create user.');
		}
		$username = $userInfo['username'];
		$firstName = (
			array_key_exists('firstName', $userInfo)
			? $userInfo['firstName']
			: ''
		);
		$lastName = (
			array_key_exists('lastName', $userInfo)
			? $userInfo['lastName']
			: ''
		);
		$email = (
			array_key_exists('email', $userInfo)
			? $userInfo['email']
			: ''
		);
		return $user->createUser($username, $firstName, $lastName, $email, Account_Rd::LEVEL_STUDENT);
	}
	
	public static function getPluginUserInfo()
	{
		return 
			self::$_activePlugin
			? self::$_activePlugin->getUserInfo()
			: array(
				'username' => '',
				'firstName' => '',
				'lastName' => '',
				'email' => '',
			);
	}
	
	public static function getPluginProvidedUsername()
	{
		return 
			self::$_activePlugin
			? self::$_activePlugin->getProvidedUsername()
			: '';
	}
	
	public static function failPlugin()
	{
		if (self::$_activePlugin) {
			self::$_activePlugin->fail();
		}
	}
}