<?php
/*******************************************************************************
Registry.php
Implements a manager for all application state loading and access

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
 * Class for managing application state.
 * @author jthurtea
 *
 */

class Rd_Registry{
	
	protected static $_configuration = null;
	
	private static $_unavailableExceptionMessage = 'Requested registry value is not available.';

	private static $_setExceptionTemplate = 'Unable to edit values in the {{facet}} facet.';
	
	private static $_unwritableExceptionMessage = 'Requested registry value could not be edited.';

	protected static $_writableFacets = array('root','response','session');
	
	protected static function _init()
	{
		if (is_null(self::$_configuration)) {
			self::$_configuration = array(
				'root' => array(),
				'response' => array(),
				'get' => $_GET,
				'post' => $_POST,
				'request' => $_REQUEST,
				'session' => &$_SESSION
			);
		}
	}
	
	public static function get($name)
	{
		self::_init();
		$request = 
			is_array($name)
			? $name
			: explode(':', $name);
		$facet = array_shift($request);
		if ('config' == $facet) {
			return Rd_Config::get($request);
		} else {
			try{
				if(array_key_exists($facet, self::$_configuration)){
					return self::_get($request, self::$_configuration[$facet]);
				} else {
					array_unshift($request,$facet);
					return self::_get($request, self::$_configuration['root']);
				}
			} catch (Exception $e){
				throw new Exception(self::$_unavailableExceptionMessage);
			}
		}
	}
	
	protected static function _get($name, $source)
	{
		if (0 == count($name)) {
			return $source;
		}
		$newSourceName = array_shift($name);
		if (is_array($source) 
			&& array_key_exists($newSourceName, $source)
		){
			return self::_get($name, $source[$newSourceName]);
		} else if (is_object($source) 
			&& property_exists($source, $newSourceName)
		) {
			return self::_get($name, $source->$newSourceName);
		} //#TODO handle magic __get()
		
		throw new Exception(self::$_unavailableExceptionMessage);
	}
	
	public static function set($name, $value)
	{
		self::_init();
		$request = 
			is_array($name)
			? $name
			: explode(':', $name);
		$facet = array_shift($request);
		if(
			(array_key_exists($facet, self::$_configuration) 
				&& !in_array($facet, self::$_writableFacets)
			) || (!array_key_exists($facet, self::$_configuration) 
				&& !in_array('root', self::$_writableFacets)
			)
		) {
			throw new Exception(str_replace('{{facet}}', $facet, self::$_setExceptionTemplate));
		}
		if(array_key_exists($facet, self::$_configuration)){
			return self::_set($request, self::$_configuration[$facet], $value);
		} else {
			array_unshift($request, $facet);
			return self::_set($request, self::$_configuration['root'], $value);
		}
	}
	
	protected static function _set($name, &$source, $value){
		if (0 == count($name)) {
			$source = $value;
			return;
		}
		$newSourceName = array_shift($name);
		if (is_array($source)) {
			return self::_set($name, $source[$newSourceName], $value);
		} else if (is_object($source) 
			&& property_exists($source, $newSourceName)
		) {
			return self::_set($name, $source->$newSourceName, $value);
		}//#TODO handle magic __set()
		//print_r(array($name, $source, $value));
		throw new Exception(self::$_unwritableExceptionMessage);
	}
	
	public static function cleanSession($keepers = array('debug','mobile','css'))
	{
		if ('' == session_id()) {
			session_start();
		}
		
		//#TODO not sure what the below is supposed to be doing...
		$host = '.' . $_SERVER['HTTP_HOST'];		
		$domain = preg_replace('/\/secure\/[A-z]*\.php/', '', $_SERVER['SCRIPT_NAME']);
		$authKey = (string)(array_key_exists('authKey', $_SESSION) ? $_SESSION['authKey'] : '');
		setcookie($authKey, "", time() -3600, $domain, $host, 1);
		//#TODO not sure exactly what the above is supposed to be doing...
		
		$keeperValues = array();
		foreach($keepers as $keeperName){
			if (array_key_exists($keeperName, $_SESSION)) {
				$keeperValues[$keeperName] = $_SESSION[$keeperName];
			}
		}
		session_unset();
		foreach($keeperValues as $keeperName=>$keeperValue){
			$_SESSION[$keeperName] = $keeperValue;
		}
	}
}