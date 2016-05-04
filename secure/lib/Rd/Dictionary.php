<?php
/*******************************************************************************
Dictionary.php
Implements a manager for dictionary loading and lookup

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
 * Class for loading and looking up terms from a configuration file
 * @author jthurtea
 *
 */

require_once(APPLICATION_PATH . '/lib/Rd/Config.php');

class Rd_Dictionary extends Rd_Config{
	
	protected static $_configuration = null;
	
	protected static $_currentLanguage = '';

	public static function get($name)
	{
		if (is_null(self::$_configuration)) {
			throw new Exception('Attempted to use Dictionary functions with out first loading a language file.');
		}
		return self::_get(is_array($name) ? $name : explode(':', $name), self::$_configuration);
	}	
	
	public static function getXml($name)
	{
		return self::get($name.':+')->asXml();
	}
	
	public static function loadLanguage($lang)
	{
		$dictionaryFolder = Rd_Config::get('dictionary:path');
		$dictionaryPath = 
			realpath((
				strpos($dictionaryFolder, '/') !== 0 
				? (APPLICATION_PATH . '/') 
				: ''
			) . $dictionaryFolder . $lang . '.xml');
		if(is_readable($dictionaryPath)){
			self::$_configuration = simplexml_load_file($dictionaryPath);
		} else {
			throw new Exception('Unable to load the specified language dictionary.');
		}
	}
	
}