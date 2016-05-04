<?php
/*******************************************************************************
Rd/Debug.php
Implements a Debug utility for RD

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
 * Debugging utility class.
 * @author jthurtea
 *
 */
require_once APPLICATION_PATH . '/lib/Rd/Config.php';

class Rd_Debug{

	protected static $_enabled = false;
	public static $buffer = '';

	public static function autodetect(){
		//if ($_REQUEST['debug'] == 'clear') {
		//	unset($_SESSION['debug']);
		//	die('debug settings cleared');
		//}		
		$sessionDebugSet = isset($_SESSION) && array_key_exists('debug', $_SESSION);
		$sessionDebugSetting = $sessionDebugSet && $_SESSION['debug'];
		$requestDebug = array_key_exists('debug', $_REQUEST);
		$requestNoDebug = array_key_exists('nodebug', $_REQUEST);
		//print_r(array(self::isAvailable(), $sessionDebugSet, $sessionDebugSetting, $requestDebug, $requestNoDebug));
		if (
			!$requestNoDebug
			&& self::isAvailable()
			&& (
				($sessionDebugSetting) //&& self::isAvailable())
				|| ($requestDebug) //&& self::isAvailable())
				|| (self::isDefault() && (!$requestNoDebug && (!$sessionDebugSet || $sessionDebugSetting))
				//|| (self::isAvailable() 
				//	&& !$requestNoDebug 
				//	&& (!$sessionDebugSet 
						|| ($sessionDebugSet && $sessionDebugSetting)
				//	)
				)
			)
		) {
			self::enable();
		} else {
			self::disable();
		}
	}
	
	public static function enable()
	{
		self::$_enabled = true;
		$_SESSION['debug'] = true;
		ini_set('display_errors', 1);
		error_reporting(-1);//E_ALL | E_STRICT);
	}
	
	public static function disable()
	{
		self::$_enabled = false;
		$_SESSION['debug'] = false;
		ini_set('display_errors', 0);
	}
	
	public static function isAvailable()
	{
		return 
			self::isDefault() || 'true' == strtolower(trim(Rd_Config::get('debug')))
			? true 
			: false;
	}
	
	public static function isDefault()
	{
		return
			'auto' == strtolower(trim(Rd_Config::get('debug')))
			? true 
			: false;
	}
	
	public static function isEnabled()
	{
		return self::$_enabled;
	}
	
	public static function out($message, $level='ERROR')
	{
		$level = ucfirst(strtolower($level));
		$output = "<p class=\"debug{$level}\">$message</p>\n";
		self::_out($output);
	}
	
	public static function outData($message, $level='ERROR')
	{
		$level = ucfirst(strtolower($level));
		ob_start();			
		print("\n<pre class=\"debug{$level}\">Data:<br/>\n");
		print_r($message);
		print("\n</pre>\n");
		$output = ob_get_contents();
		ob_end_clean();
		self::_out($output);
	}
	
	protected static function _out($output)
	{
		if (self::isEnabled()) {
			print($output);
		} else {
			self::$buffer . $output;
		}
	}
	
	public static function getBuffer()
	{
		return self::$buffer;
	}
	
	public static function cleanBuffer()
	{
		self::$buffer = '';
	}
	
	public static function getRequestString()
	{
		ob_start();		
		$sanitizedRequest = $_REQUEST;
		if(array_key_exists('pwd',$sanitizedRequest)){
			$sanitizedRequest['pwd'] = '***redacted***';
		}
		if(array_key_exists('password',$sanitizedRequest)){
			$sanitizedRequest['password'] = '***redacted***';
		}
		print_r($sanitizedRequest);
		$output = ob_get_contents();
		ob_end_clean();
		return $output;
	}
	
	public static function outputRequest()
	{
		ob_start();	
		print("\n<pre>Request:<br/>\n");
		print_r(self::getRequestString());
		print("\n</pre>\n");
		$output = ob_get_contents();
		ob_end_clean();
		self::_out($output);
	}
	
	public static function printDebugExit()
	{
		print("\n<br/><a href='?nodebug=true'>Disable debugging for this session.</a><br/>\n");
	}
	
	public static function printDebugEntry()
	{
		print("\n<br/><a href='?debug=true'>Enable debugging for this session.</a><br/>\n");
	}
	
	public static function dieSafe($message = '')
	{
		if (self::isEnabled()) {
			$loadTime = microtime(true) - APPLICATION_START_TIME;
			self::out("This page took {$loadTime} seconds to load.");
		} else if (Rd_Debug::isAvailable()) {
			self::printDebugEntry();
		}
		die($message);
	}
	
}