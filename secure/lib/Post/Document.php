<?php
/*******************************************************************************
Post/Document.php
Post Document utility

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
 * Utility for accessing Documents supplied via the POST method
 * @author jthurtea
 *
 */

class Post_Document{

	protected static $_contentString = null;
	protected static $_contentSize = 0;
	protected static $_contentType = '';
	
	protected static function _init()
	{
		if(is_null(self::$_contentString)){
			self::$_contentString = file_get_contents("php://input");
			if (is_null(self::$_contentString)) {
				self::$_contentString = '';
			}
			$data = apache_request_headers();
			self::$_contentSize = (
				array_key_exists('Content-Length', $data)
				? $data['Content-Length']
				: ''
			);
			self::$_contentType = (
				array_key_exists('Content-Type', $data)
				? $data['Content-Type']
				: ''
			);
		}
	}
	
	public static function get()
	{
		self::_init();
		return self::$_contentString;	
	}
	
	public static function length()
	{
		self::_init();
		return self::$_contentSize;	
	}
	
	public static function getMimeType()
	{
		self::_init();
		return self::$_contentType;	
	}
	
}