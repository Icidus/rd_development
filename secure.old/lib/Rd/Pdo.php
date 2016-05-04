<?php
/*******************************************************************************
Rd/Pdo.php
Implements a Pdo wrapper for managing the RD database (and possibly others)

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
 * Wrapper class for the PHP PDO object.
 * @author jthurtea
 *
 */

class Rd_Pdo{
	
	protected static $_connection = null;
	protected static $_hostName = '';
	protected static $_userName = '';
	protected static $_schemaName = '';
	protected static $_driverName = '';
	protected static $_errorMessage = '';
	
	public static function connect($dsn){
		self::$_driverName = (
			array_key_exists('phptype', $dsn)
			? $dsn['phptype']
			: 'mysql'
		);
		self::$_hostName = (
			array_key_exists('hostspec', $dsn)
			? $dsn['hostspec']
			: 'localhost'
		);
		self::$_userName = (
			array_key_exists('username', $dsn)
			? $dsn['username']
			: ''
		);
		self::$_schemaName = (
			array_key_exists('database', $dsn)
			? $dsn['database']
			: 'reservesdirect'
		);
		$password = (
			array_key_exists('password', $dsn)
			? $dsn['password']
			: ''
		);
		$dsnString = self::$_driverName . ':host=' . self::$_hostName . ';dbname=' . self::$_schemaName;
		$options = array();
		try {
			self::$_connection = new PDO($dsnString, self::$_userName, $password, $options);
			self::$_errorMessage = '';
		} catch (Exception $e) {
			self::$_errorMessage = $e->getMessage();
			return false;
		}
		return true;
	}
	
	public static function reconnectAs($user, $password, $dbName = ''){
		self::$_connection = NULL;
		self::$_userName = $user;
		if ('' != $dbName) {
			self::$_schemaName = $dbName;
		}
		$dsnString = self::$_driverName . ':host=' . self::$_hostName . ';dbname=' . self::$_schemaName;
		$options = array();
		try{
			self::$_connection = new PDO($dsnString, self::$_userName, $password, $options);
			self::$_errorMessage = '';
		} catch (Exception $e) {
			self::$_errorMessage = $e->getMessage();
			return false;
		}
		return true;
		
	}
	
	public static function getAdapter($toss='',$toss2='')
	{
		return new Rd_Pdo_PearAdapter(); //#TODO deprecate this once all references to the class have been exterminated.
	}
	
	public static function getSchemaName()
	{
		return self::$_schemaName;
	}
	
	public static function getHostName()
	{
		return self::$_hostName;
	}
	
	public static function getUserName()
	{
		return self::$_userName;
	}
	
	public static function getDriverName()
	{
		return self::$_driverName;
	}
	
	public static function query($query,$args=NULL){
		if (!is_null($args)) {
			return self::prepareExecute($query,$args);
		}
		
		$statement = self::$_connection->query($query, PDO::FETCH_ASSOC);
/*
		$error = NULL;
		if ($statement) {
			$error = $statement->errorInfo();
			$error = $error[1];
		}
		
		if ($error || Rd_Pdo::hasError()) {
			if (Rd_Debug::isEnabled()) {
				$message1 = Rd_Pdo_PearAdapter::getErrorMessage($statement);
				$message2 = Rd_Pdo::getErrorMessage();
				Rd_Debug::out('Attempting auto-prepared statment because of failure: ' 
					. ('' != $message1 ? $message1 : $message2)
					. '<br/><br/><pre>' . htmlentities($query) . '</pre>'
				);
			}
			$statement2 = self::$_connection->prepare($query);
			if ($statement2) {
				if (!is_array($args)) {
					$args = array($args);
				}
				$statement2->execute($args);
				return $statement2;
			}
		}
*/
		return $statement;
	}
	
	public static function prepareExecute($query, $args)
	{
		$cleanArgs = array();
		if (!is_array($args)) {
			$cleanArgs = array($args);
		} else {
			foreach($args as $arg) {
				$cleanArgs[] = $arg;
			}
		}
		$explodingBinds = array();
		foreach($cleanArgs as $key=>$arg) {
			if (is_array($arg) && count($arg) > 1){
				$explodingBinds[$key] = count($arg);
			} else if(is_array($arg)) {
				$cleanArgs[$key] = (
					array_key_exists(0, $arg)
					? $arg[0]
					: NULL
				);
			}
		}
		if (count($explodingBinds) > 0) {
			$query = self::_explodePreparedQuery($query,$explodingBinds);
			$cleanArgs = self::_flattenParams($cleanArgs);
			//Rd_Debug::out('Attempting to expand parameters...');
			//Rd_Debug::outData(array($query,$args));
		}

		$statement = self::$_connection->prepare($query);
//			Rd_Debug::outData(array($query,$cleanArgs));
		if ($statement) {
			$statement->execute($cleanArgs);
			if('00000' != $statement->errorCode() && Rd_Debug::isEnabled()) {
				throw new Exception('OH HI! Bad Query Detected. HERE IZ UR STACK TRACE. GO FIXZORZ ET PEWPEW!');
			}
		}
		return $statement;
	}
	
	protected static function _explodePreparedQuery($query,$map)
	{
		$replacements = array();
		foreach ($map as $position=>$count) {
			$replacements[] = implode(',', array_fill(0, $count, '?')); 
			//the above may not work as desired with 0 items in the array. This is prevented upstream.
		}
		$queryBits = explode('?', $query);
		$query = array_shift($queryBits);
		foreach ($queryBits as $bit) {
			$query .= array_shift($replacements) . $bit;
		}
		return $query;
	}
	
	protected static function _flattenParams($args)
	{
		$newArgs = array();
		foreach($args as $arg) {
			if(is_array($arg)) {
				foreach($arg as $subArg) {
					$newArgs[] = $subArg;
				}
			} else {
				$newArgs[] = $arg;
			}
		}
		return $newArgs;
	}
	
	public static function autoload(){
		if(is_null(self::$_connection)){
			global $dsn;
			self::connect($dsn);
		}
	}
	
	public static function all($result, $mode = PDO::FETCH_BOTH){
		return ($result ? $result->fetchAll($mode) : false);
	}
	
	public static function one($result, $mode = PDO::FETCH_BOTH){
		return ($result ? $result->fetch($mode) : false);
	}
	
	public static function count($result){
		return ($result ? $result->count() : false);
	}
	
	public static function getVersion(){
		return self::$_connection->getAttribute(PDO::ATTR_SERVER_VERSION);
	}
	
	public static function hasTable($tableName){
		$result = self::$_connection->query('SHOW TABLES;', PDO::FETCH_NUM);
		$tables =  $result->fetchAll();
		foreach($tables as $table) {
			if($table[0] == $tableName) {
				return true;
			}
		}
		return false;
	}
	
	public static function getError(){
		return self::$_connection->errorInfo();
	}
	
	public static function hasError(){
		$error =  self::$_connection->errorInfo();
		return $error[1];
	}
	
	public static function getErrorMessage($what = NULL){
		$error = 
			self::$_connection
			? self::$_connection->errorInfo()
			: self::$_errorMessage;
		return 
			is_array($error)
				? $error[2]
				: $error;
	}
	
	public static function isError($what = NULL)
	{
		if (
			!is_null($what) 
			&& is_object($what) 
			&& method_exists($what, 'errorInfo')
		) {
			$error = $what->errorInfo();
		} else if (
			is_null($what)
			|| $what === false
		) {
			$error = self::getError();
		} else {
			return false;
		}
		return (
			is_array($error)
			&& array_key_exists(1, $error)
			&& array_key_exists(2, $error)
			&& ($error[1] || $error[2])
		);
	}
	
	public static function beginTransaction()
	{
		if (!self::$_connection) {
			return -2;
		}
		return self::$_connection->beginTransaction();
	}
	
	public static function inTransaction()
	{
		return self::$_connection->inTransaction();
	}
	
	public static function rollback()
	{
		if (!self::$_connection) {
			return -2;
		}
		if (self::$_connection->inTransaction()) {
			return self::$_connection->rollback();
		} else {
			return -1;
		}
	}
	
	public static function commit()
	{
		if (!self::$_connection) {
			return -2;
		}
		if (self::$_connection->inTransaction()) {
			return self::$_connection->commit();
		} else {
			return -1;
		}
	}
	
	public static function escapeString($string, $quote=true)
	{
		return (
			$quote
			? "'" . addslashes((string)$string) . "'"
			: addslashes((string)$string)
		);
	}
	
	public static function escapeInt($int)
	{
		return intval($int);
	}
	
	public static function escapeDate($date, $quote=false)
	{
		//#TODO there is a lot of functionality we could add here with PHP's date functions...
		$cleanDate = preg_replace('/[^0-9\- :]/','',$date);
		$return = (
			strpos($cleanDate,':') === false
			? trim($cleanDate) . ' 00:00:00'
			: trim($cleanDate)
		);
		return (
			$quote
			? "'{$return}'"
			: $return
		);
			
	}
	
	public static function escapeAuto($param)
	{
		if (is_numeric($param)) {
			return self::escapeInt($param);
		} else {
			return self::escapeString($param);
		}
	}
	
	public static function escapeArray($array, $delimiter = ',', $cast = 'auto')
	{
		if (!is_array($array)) {
			$array = explode(',', $array);
		}
		foreach($array as $key=>$param) {
			switch(strtolower($cast)) {
				case 'int':
				case 'integer':
					$array['key'] = self::escapeInt($param);
					break;
				case 'string':
				case 'str':
					$array['key'] = self::escapeString($param);
					break;
				case 'date':
					$array['key'] = self::escapeDate($param);
					break;
				default:
					$array[$key] = self::escapeAuto($param);
			}
		}
		return $array;
	}
	
	public static function getLastInsertId($table)
	{
		$select =  self::query("SELECT LAST_INSERT_ID() FROM {$table}");
		$id = $select->fetch(PDO::FETCH_NUM);
		return $id[0];
	}
	
}