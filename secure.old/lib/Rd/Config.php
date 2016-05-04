<?php
/*******************************************************************************
Rd/Config.php
Implements a manager for configuration loading and access

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
 * Class for loading and providing access to configuration information.
 * @author jthurtea
 *
 */

class Rd_Config{
	
	protected static $_configuration = null;
	
	private static $_unavailableExceptionMessage = 'Requested configuration option is not available.';
	
	private static $_corruptedExceptionMessage = 'The configuration file is corrupted.';
	
	public static function getOptional($name, $default=''){
		try{
			return self::get($name);
		} catch (Exception $e) {
			return $default;
		}
	}
	
	public static function get($name)
	{
		if (is_null(self::$_configuration)) {
			self::$_configuration = simplexml_load_file(APPLICATION_CONF);
			if(!self::$_configuration) {
				require_once(APPLICATION_PATH . '/lib/Rd/Debug.php');
				Rd_Debug::out(libxml_get_errors());
				if(!Rd_Debug::isEnabled()){
					print(Rd_Debug::getBuffer());
					Rd_Debug::cleanBuffer();
				}
				throw new Exception(self::$_corruptedExceptionMessage);
			}
		}
		return self::_get(is_array($name) ? $name : explode(':', $name), self::$_configuration);
	}
	
	protected static function _get($name, $source=null)
	{
		$currentPosition = (is_null($source) ? self::$_configuration : $source);
		foreach ($name as $index=>$subName) {
			if(isset($currentPosition->$subName)){
				if($index == count($name) - 1){
					return (string)$currentPosition->$subName;
				} else if (array_key_exists($index + 1, $name) && '+' == $name[$index + 1]) {
					return $currentPosition->$subName;
				} else {
					$currentPosition = $currentPosition->$subName;
				}
			} else {
				throw new Exception(self::$_unavailableExceptionMessage);
			}
		}
	}
	
	public static function valueMap($data,$cast=''){
		switch($cast)
		{
			case 'boolean' :
			case 'bool' :
				return (boolean) $data;
				break;
			case 'truty' :
				$matches = array();
				return false; //#TODO implement this when needed... possibly in a separate utility...
			case 'string' :
				return (string) $data;
			case 'int' :
			case 'integer' :
				return (int) $data;
			case 'float' :
			case 'double' :
			case 'real' :
				return (float) $data;
			default:
				return is_array($data) || is_object($data)
				? self::arrayMap($data)
				: $data;
		}
	}
	
	public static function arrayMap($data,$flatten=false)
	{
		$return = array();
		if(is_array($data)) { 
			foreach ($data as $key=>$value) {
				$return[$key] = self::valueMap($value);
			}
		} else if (is_object($data) && method_exists($data, 'toArray')) {
			$return = self::arrayMap($data->toArray());
		} else if (is_object($data) && method_exists($data, '__toArray')) {
			$return = self::arrayMap($data->__toArray());
		} else if (is_object($data) && in_array('Traversable', class_implements($data))) {
			if(0 ==count($data)) {
				$return = (string)$data;
			} else {
				foreach ($data as $key=>$value) {
					$return[$key] = self::valueMap($value);
				}
			}
		} else {
			$return[] = $data;
		}
		if ($flatten && is_array($return)) {
			$flattenedReturn = array();
			foreach($return as $value) {
				if(is_array($value)) {
					foreach($value as $subKey=>$subValue) {
						$flattenedReturn[$subKey] = $subValue;
					}
				} else {
					$flattenedReturn[] = $value;
				}
			}
			$return = $flattenedReturn;
		}
		return $return;
	}
}