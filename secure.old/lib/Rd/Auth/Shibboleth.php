<?php
/*******************************************************************************
Rd/Auth/Shibboleth.php
Implements an Authentication Plugin for Shibboleth

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

class Rd_Auth_Shibboleth extends Rd_Auth_Abstract{
	
	protected static $_conf = array();
	protected static $_initialized = false;
	protected static $_shibLoggedIn = false;
	
	protected static function _init()
	{
		if (!self::$_initialized) {
			self::$_conf = Rd_Registry::get('root:shibConf');
			//#TODO throw an error if usernameField isn't provided...
		}
		self::$_initialized = true;
	}
	
	public static function auth()
	{
		self::_init();
		if (array_key_exists(self::$_conf['usernameField'], $_SERVER)) {
			return self::_succeed();
		}
		self::fail();
		return false;
	}
	
	public static function isLoggedIn()
	{
		self::_init();
		return array_key_exists(self::$_conf['usernameField'], $_SERVER);
	}
	
	public static function logout()
	{
		self::_init();
		$searchIdps = array_key_exists('idpField', self::$_conf)
				&& array_key_exists('idps', self::$_conf)
				&& array_key_exists(self::$_conf['idpField'], $_SERVER);
		$idpLogout = '';
		if (
			$searchIdps
				&& (!array_key_exists('step', $_GET) 
					|| 'splogout' != $_GET['step'] ) // these two conditions are redundant, extra precaution to avoid redirect loop.
				&& !array_key_exists('idpLogoutAttempted', $_SESSION)		
		) {
			foreach(self::$_conf['idps'] as $name=>$values){
				if (
					array_key_exists('idpId', $values)
					&& array_key_exists('idpLogout', $values)
					&& $values['idpId'] == $_SERVER[self::$_conf['idpField']]
				) {
					$idpLogout = $values['idpLogout'] 
					. '?' . self::$_conf['returnUrlField'] 
					. '=' . Rd_Registry::get('root:mainUrlProper') . '?cmd=logout%26step=splogout';
				}
			}
		}
		if ('' != $idpLogout) {
			$_SESSION['idpLogoutAttempted'] = true;
			throw new Rd_Exception_RedirectWorkflow($idpLogout);
		}
	}
	
	public static function allowedIdp($idpId)
	{
		self::_init();
		$searchIdps = array_key_exists('idpField', self::$_conf)
				&& array_key_exists('idps', self::$_conf);
		if ($searchIdps) {
			foreach(self::$_conf['idps'] as $idp=>$idpData){
				if(array_key_exists('allowed', $idpData)
					&& 'false' == strtolower($idpData['allowed'])
					&& array_key_exists('idpId', $idpData)
					&& $idpId == $idpData['idpId']
				){
					return false;
				}
			}
			return true;
		}
	}
	
	public static function getExternalLogoutUrl()
	{
		return self::getSpLogoutUrl(true);
	}
	
	public static function getSpLogoutUrl($fullyQualified = false)
	{
		return (
			$fullyQualified
			? Rd_Registry::get('root:mainUrl')
			: ''
		) . self::$_conf['logoutUrl'];
	}
	
	public static function getIdpLogoutUrl($idpId, $redirectTo='')
	{
		self::_init();
		$searchIdps = array_key_exists('idpField', self::$_conf)
				&& array_key_exists('idps', self::$_conf);
		if ($searchIdps) {
			foreach(self::$_conf['idps'] as $idp=>$idpData){
				if(
					array_key_exists('idpLogout', $idpData)
					&& array_key_exists('idpId', $idpData)
					&& $idpId == $idpData['idpId']
				){
					$idpLogout = $idpData['idpLogout'];
				}
			}
		} else {
			throw new Exception('Trying to get logout URL for unknown IDP.');	
		}
		return $idpLogout . ( 
			'' != $redirectTo
			? '?' . self::$_conf['returnUrlField'] . '=' . $redirectTo
			: ''
		);
	}
	
	public static function fail()
	{
		if (array_key_exists(self::$_conf['idpField'], $_SERVER)
			&& !self::allowedIdp($_SERVER[self::$_conf['idpField']])
		) {
			Rd_Auth::setStatus(false, NULL, '008');
			$url = self::getIdpLogoutUrl(
				$_SERVER[self::$_conf['idpField']], 
				self::getSpLogoutUrl(true)
			);
			Rd_Layout::setMessage('emergencyLogout', "<p class=\"error\">You may <a href=\"{$url}\">log out of Shibboleth</a> if desired.</p>");
		}
		if ('optional' != trim(strtolower(self::$_conf['authMode']))) {
			Rd_Auth::setStatus(false, NULL, '002');
			// the config indicates we should not be here with no shib credentials
			// possibly the apache shib module isn't loaded properly
		}
		if (Rd_Debug::isEnabled()) {
			Rd_Debug::out('Shibboleth Authentication Declined.');
		}
	}

	public static function getProvidedUsername()
	{
		self::_init();
		return (
			array_key_exists(self::$_conf['usernameField'], $_SERVER)
			? $_SERVER[self::$_conf['usernameField']]
			: '' 
		);
	}
	
	public static function getUserInfo() //#TODO try using preffered
	{
		return array(
			'username' => self::getProvidedUsername(),
			'firstName' => (
				array_key_exists(self::$_conf['firstnameField'], $_SERVER)
				? $_SERVER[self::$_conf['firstnameField']]
				: ''
			),
			'lastName' => (
				array_key_exists(self::$_conf['lastnameField'], $_SERVER)
				? $_SERVER[self::$_conf['lastnameField']]
				: ''
			),
			'email' => (
				array_key_exists(self::$_conf['emailField'], $_SERVER)
				? $_SERVER[self::$_conf['emailField']]
				: ''
			)
		);
	}
	
	public static function alreadyLoggedIn(){
		return self::$_shibLoggedIn;
	}
	
	public static function setPluginStatus($success,$errorCode)
	{
		self::$_shibLoggedIn = $success || '008' == $errorCode;
	}
}
	