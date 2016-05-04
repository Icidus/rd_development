<?php
/*******************************************************************************
Rd/Layout.php
Implements a layout manager

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
 * Output utility
 * @author jthurtea
 *
 */

class Rd_Layout{

	protected static $_buffer = '';
	protected static $_medium = 'desktop';
	protected static $_supportedMedia = array (
		'desktop',
		'mobile'
	);
	protected static $_messages = array();

	protected static function _init()
	{
		
	}
	
	public static function detectMobile()
	{
		$requestingMobile = array_key_exists('mobile', $_REQUEST) 
				&& 'true' == $_REQUEST['mobile'];
		$requestingFull = array_key_exists('mobile', $_REQUEST) 
				&& 'true' != $_REQUEST['mobile'];
		$inMobileMode =  array_key_exists('mobile', $_SESSION) 
				&& 'true' == $_SESSION['mobile'];
		if (
			$requestingMobile 
			|| ($inMobileMode && !$requestingFull)
		) {
			$_SESSION['mobile'] = 'true';
			self::setMedium('mobile');
		} else {
			$_SESSION['mobile'] = 'false';
			self::setMedium('desktop');
		}
	}
	
	public static function isMobile()
	{
		return self::$_medium == 'mobile';
	}
	
	public static function setMedium($newMedium)
	{
		if (in_array($newMedium,self::$_supportedMedia)) {
			self::$_medium = $newMedium;
		} else {
			throw new Exception("Unsupported Media Type: {$newMedium}.");
		}
	}

	public static function printJQuery($options = array())
	{
?>
	<script type="text/javascript" src="public/javascript/jquery-1.6.4.min.js"></script>
<?php 		
	}
	
	public static function printJqueryUi($options = array())
	{
?>
	<link rel="stylesheet" href="public/css/jquery_ui_blitzer/main.css" type="text/css" />
<?php 
		$version = (
			array_key_exists('version', $options)
			? $options['version']
			: '1.8.1'
		);
		switch ($version) {
			case '1.9.0':
?>	<script language="javascript" src="public/javascript/jquery-ui-1.9.0.min.js"></script>
<?php 					
				break;
			case '1.8.1':
?>	<script language="javascript" src="public/javascript/jquery.ui.1-8-1min.js"></script>
<?php 	
				break;
			default:
				Rd_Debug::out('Unrecognized jQueryUI version requested.', 'WARNING');
?>	<script language="javascript" src="public/javascript/jquery.ui.1-8-1min.js"></script>
<?php 				
		}
	}
	
	public static function printIeStandardsMode($options = array())
	{
?>
	<meta http-equiv="X-UA-Compatible" content="IE=9">
<?php 		
	}
	
	public static function printFavIcon($options = array())
	{ //#TODO make this portable #2.1.0
?>
	<!-- <link rel="shortcut icon" href="/favicon.ico" type="image/vnd.microsoft.icon"> -->
<?php 		
	}
	
	public static function printCiRssLink($courseInstance)
	{
		$courseInstance->unCouch();
		$title = "{$courseInstance->course->department->name} {$courseInstance->course->courseNo} {$courseInstance->term} {$courseInstance->year}";
		$url = "rss.php?ci={$courseInstance->courseInstanceID}";
?>
	<link rel="alternate" title="<?php print($title); ?>" href="<?php print($url); ?>" type="application/rss+xml"/>
<?php 
	}
	
	public static function autoCss($manager,$command)
	{
		$themePath = self::getCssThemePath();
		$globalPath = self::getCssThemePath('global');
		$basePath = "{$themePath}/{$manager}";
		$globalBasePath = "{$globalPath}/{$manager}";
		if(file_exists( ROOT_PATH . "/{$basePath}.css")){
			print("<link rel=\"stylesheet\" href=\"{$basePath}.css\" type=\"text/css\" />\n");
		}
		if(file_exists( ROOT_PATH . "/{$basePath}/{$command}.css")){
			print("<link rel=\"stylesheet\" href=\"{$basePath }/{$command}.css\" type=\"text/css\" />\n");
		}
			if(file_exists( ROOT_PATH . "/{$globalBasePath}.css")){
			print("<link rel=\"stylesheet\" href=\"{$globalBasePath}.css\" type=\"text/css\" />\n");
		}
		if(file_exists( ROOT_PATH . "/{$globalBasePath}/{$command}.css")){
			print("<link rel=\"stylesheet\" href=\"{$globalBasePath }/{$command}.css\" type=\"text/css\" />\n");
		}		
	}
	
	public static function getCssThemePath($theme='')
	{
		return Rd_Registry::get('stylesFolder') 
			. (
				'' != trim($theme)
				? trim($theme)
				: Rd_Registry::get('stylesTheme')
			);	
	}
	
	public static function getHtmlIncludePath($file, $theme='')
	{
		$basePath = APPLICATION_PATH . '/html/';
		$globalPath = "{$basePath}global/{$file}";
		$themePath = $basePath . (
				'' != trim($theme)
				? trim($theme)
				: Rd_Registry::get('stylesTheme')
			) . "/{$file}";
		return (
			file_exists($themePath)
			? $themePath
			: (
				file_exists($globalPath)
				? $globalPath
				: ''
			)
		);
	}
	
	public static function includeFile($file, $theme='', $scope=array())
	{
		$originalFile = $file;
		$file = self::getHtmlIncludePath($file, $theme);
		if ('' != trim($file)) {
			foreach($scope as $var=>$value) {
				$$var = $value;	
			}
			include($file);
			return true;
		} else {
			if (Rd_Debug::isEnabled()) {
				Rd_Debug::out('Failed to include file: ' . htmlentities($originalFile));
			}
			return false;
		}
	}
	
	public static function setMessage($name,$message)
	{
		self::$_messages[$name] = $message;
	}
	
	public static function hasMessage($name)
	{
		return array_key_exists($name, self::$_messages);
	}
	
	public static function clearMessage($name)
	{
		if (array_key_exists($name, self::$_messages)) {
			unset(self::$_messages[$name]);
		}
	}
	
	public static function getMessage($name)
	{
		return 	
			array_key_exists($name, self::$_messages)
			? self::$_messages[$name]
			:'';
	}
}