<?php
/*******************************************************************************
Rd/Pdo/PearAdapter.php
Implements a Pdo wrapper with the interfaces of the PEAR DB object RD has used traditionally

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
 * Adapter class for the PDO object to make it more like PEAR DB.
 * @author jthurtea
 *
 */

class Rd_Pdo_PearAdapter{
	
	public static function connect()
	{
		return Rd_Pdo::connect();
	}	
	
	public static function isError($what=NULL)
	{
		if (!is_null($what) && is_object($what) && method_exists($what, 'errorInfo')) {
			$error = $what->errorInfo();
			return 
				is_array($error)
					&& array_key_exists(1, $error)
					&& array_key_exists(2, $error)
					&& ($error[1] || $error[2])
				? $what->errorInfo()
				: false ;
		} else if (is_null($what) || $what === false) {
			$error = Rd_Pdo::getError();
			return $error[1] || $error[2];
		} else {
			return false;
		}
	}
	
	protected function errorInfo()
	{
		return Rd_Pdo::getError();
	}
	
	public static function getErrorMessage($object = NULL)
	{
		$error = (
			is_null($object) || !is_object($object)
			? Rd_Pdo::getError()
			: (
				method_exists($object, 'getError')
				? $object->getError()
				: $object->errorInfo()
			)
		);
		return $error[2];
	}
	
	public static function query()
	{
		return call_user_func_array('Rd_Pdo::query', func_get_args());
	}
	
	public static function getOne($sql, $args=NULL)
	{
		$statement = call_user_func_array('Rd_Pdo::query', func_get_args());
		if(!$statement){
			return $statement;
		}
		$result = $statement->fetch(PDO::FETCH_NUM);
		return $result[0];
	}
	
	public static function getRow($sql, $args=NULL)
	{
		$statement = call_user_func_array('Rd_Pdo::query', func_get_args());
		if(!$statement){
			return $statement;
		}
		$result = $statement->fetch(PDO::FETCH_NUM);
		return $result;
	}
	
	public static function quoteSmart($word)
	{
		return Rd_Pdo::escapeAuto($word);
	}
}