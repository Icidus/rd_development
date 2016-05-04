<?php
/*******************************************************************************
CodeLookup.php
Implements a lookup service for status codes and messages

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
 * Class for lookingup status codes.
 * @author jthurtea
 *
 */

class Rd_CodeLookup{
	
	protected static $_codes = NULL;
	
	protected static function _init(){
		if(!isset(self::$_codes)) {
			try{
				self::$_codes = Rd_Dictionary::get('codes:+');
			} catch (Exception $e){
				throw new Exception('Configuration Error: Unable to lookup status codes.',NULL,$e);
			}
		}
	}
	
	public static function tryMessage($facet, $code)
	{
		try {
			return self::getMessage($facet, $code);
		} catch (Exception $e) {
			return $e->getMessage();
		}
	}
	
	public static function getMessage($facet, $code, $fallBack='')
	{
		self::_init();
		if(!isset(self::$_codes->$facet)){
			throw new Exception("Configuration Error: Unable to lookup status codes for \"{$facet}\".");
		}
		foreach(self::$_codes->$facet->children() as $codeNode) {
			if(isset($codeNode->code) && $codeNode->code == $code && isset($codeNode->message)){
				return $codeNode->message;
			}
		}
		if('' != $fallBack){
			return $fallBack;
		} else {
			throw new Exception("Code Lookup Error: No data for status code for \"{$facet}:{$code}\".");
		}
	}
	
	public static function getMessageByCode($code)
	{
		self::_init();
		if (self::$_codes->count() > 0){
			foreach (self::$_codes->children() as $facetNode) {
				if ($facetNode->count() > 0){
					foreach ($facetNode->children() as $codeNode) {
						if (isset($codeNode->code) && $codeNode->code == $code && isset($codeNode->message)){
							return $codeNode->message;
						}
					}
				}
			}
		}
		throw new Exception("Code Lookup Error: No data for status code: {$code}.");
	}
}