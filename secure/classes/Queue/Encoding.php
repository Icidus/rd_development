<?php
/*******************************************************************************
classes/Queue/Encoding.php
Encoding Queue Object

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
require_once(APPLICATION_PATH . '/lib/Filter/Alphanumeric.php');

class Queue_Encoding
{

    protected static $_uploadPath = null;
	
    protected static function _init()
    {
    	if (is_null(self::$_uploadPath)){
    		self::$_uploadPath = Rd_Registry::get('root:videoUploadPath');
    	}
    }
	
	public static function deleteFile($filename)
	{
		self::_init();
		if(file_exists(self::$_uploadPath.$filename)) {
			if(is_file(self::$_uploadPath.$filename)) {
				if(unlink(self::$_uploadPath.$filename)){
					return true;
				} else {
					throw new Exception('Failed to delete the file.');
				}
			} else {
				throw new Exception('The path specified exists, but is not a file.');
			}
		} else {
			return true;
		}
	}
	
	public static function delete($key)
	{
		self::_init();
		$key = Filter_Alphanumeric::filter($key);
		$result = Rd_Pdo::query('SELECT id, filename FROM encoding_queue '
			. "WHERE hash = '{$key}' LIMIT 1;");
		$match = $result->fetch();
		if ($match) {
			if (self::deleteFile($match['filename'])) {
				$deleteResult = Rd_Pdo::query('DELETE FROM encoding_queue '
					. "WHERE id = {$match['id']} LIMIT 1;");
				if (!$deleteResult){
					throw new Exception('File removed, but unable to remove entry from dtatabase.');
				}
			}
			return true;
		} else {
			return false;
		}

	}
	
	
	public static function userHasUnassigned($userId)
	{
		self::_init();
		if('' == $userId || !is_numeric($userId) || $userId <= 0){
			return false;
		}
		$result = Rd_Pdo::query('SELECT COUNT(*) FROM encoding_queue '
			. "WHERE user_id = {$userId};");
		$count = $result->fetchColumn();
		return $count > 0 ? $count : false;
	}
	
	public static function getUnassigned($userId)
	{
		self::_init();
		if('' == $userId || !is_numeric($userId) || $userId <= 0){
			return array();
		}
		$result = Rd_Pdo::query('SELECT hash, original_filename FROM encoding_queue '
			. "WHERE user_id = {$userId};");
		return $result->fetchAll();
	}
	
	public static function createEntry($targetFilename,$sourceFilename)
	{
		self::_init();
		$userId = Account_Rd::getId();
		if (!$userId) {
			$userId = Account_Rd::getIdFor('admin');
		}
		$date = date('Y-m-d');
		$hash = md5($userId . '_' . $targetFilename);
		$targetFilename = addslashes($targetFilename);
		$sourceFilename = addslashes($sourceFilename);
		$result = Rd_Pdo::query('INSERT INTO encoding_queue '
			. ' (`hash`, `user_id`, `filename`, `original_filename`, `date`)' 
			. 'VALUES ('
			. "'{$hash}', "
			. "{$userId}, "
			. "'{$targetFilename}', "
			. "'{$sourceFilename}', "
			. "'{$date}'"
			. ');');
		
		return $hash;
	}
	
}

