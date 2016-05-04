<?php
/*******************************************************************************
Rd/Acl.php
Implements an ACL manager for RD

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
 * ACL utility class.
 * @author jthurtea
 *
 */

class Rd_Acl{

	protected static $_acl = array();
	protected static $_initialized = false;

	protected static function _init()
	{
		global $g_permission;
		if (!self::$_initialized) {
			self::$_acl = array(
				'viewReservesList' => $g_permission['student'],
				'previewReservesList' => $g_permission['proxy'],
				'previewStudentView' => $g_permission['proxy'],
				'customSort' => $g_permission['proxy'],
				'selectInstructor' => $g_permission['student'],
				'addReserve' => $g_permission['proxy'],
				'addMultipleReserves' => $g_permission['proxy'],
				'searchScreen' => $g_permission['proxy'],
				'searchResults' => $g_permission['proxy'],
				'storeReserve' => $g_permission['proxy'],
				'uploadDocument' => $g_permission['proxy'],
				'addURL'  => $g_permission['proxy'],
				'placeRequest' => $g_permission['proxy'],
				'storeUploaded' => $g_permission['proxy'],
				'faxReserve' => $g_permission['proxy'],
				'getFax' => $g_permission['proxy'],
				'addFaxMetadata' => $g_permission['proxy'],
				'storeFaxMetadata' => $g_permission['proxy'],
				'myReserves' => $g_permission['student'],
				'viewCourseList' => $g_permission['student'],
				'activateClass' => $g_permission['instructor'],
				'deactivateClass' => $g_permission['instructor'],
				'manageClasses' => $g_permission['staff'],
				'editProxies' => Account_Rd::LEVEL_FACULTY,
				'editInstructors' => Account_Rd::LEVEL_FACULTY,
				'editCrossListings' => Account_Rd::LEVEL_FACULTY, // was Proxy... that's dangerous
				'editTitle' => Account_Rd::LEVEL_FACULTY, // was Proxy... that's dangerous
				'editClass' => $g_permission['proxy'],
				'createClass' => $g_permission['instructor'],
				'createNewClass' => $g_permission['instructor'],
				'addClass' => $g_permission['student'],
				'removeClass' => $g_permission['student'],
				'deleteClass' => $g_permission['staff'],
				'confirmDeleteClass' => $g_permission['staff'],
				'deleteClassSuccess' => $g_permission['staff'],
				'copyItems' => $g_permission['instructor'],
				'processCopyItems' => $g_permission['instructor'],
				'manageUser' => $g_permission['custodian'],
				'newProfile' => $g_permission['student'],
				'editProfile' => $g_permission['student'],
				'editUser' => $g_permission['staff'],
				'mergeUsers' => $g_permission['admin'],
				'addUser' => $g_permission['staff'],
				'assignProxy' => $g_permission['instructor'],
				'assignInstr' => $g_permission['instructor'],
				'setPwd' => $g_permission['custodian'],
				'resetPwd' => $g_permission['custodian'],
				'setGuest' => $g_permission['admin'],
				'removePwd' => $g_permission['custodian'],
				'addProxy' => $g_permission['instructor'],
				'removeProxy' => $g_permission['instructor'],
				'editItem' => $g_permission['proxy'],
				'editMultipleReserves' => $g_permission['proxy'],
				'editHeading' => $g_permission['proxy'],
				'processHeading' => $g_permission['proxy'],
				'duplicateReserve' => $g_permission['staff'],
				'displayRequest' => $g_permission['staff'],
				'storeRequest' => $g_permission['staff'],
				'deleteRequest' => $g_permission['staff'],
				'printRequest' => $g_permission['staff'],
				'setStatus' => $g_permission['staff'],	
				'addDigitalItem' => $g_permission['proxy'],
				'addPhysicalItem' => $g_permission['proxy'],
				'addVideoItem' => $g_permission['proxy'],
				'addVideoItem2' => $g_permission['proxy'],
				'copyClass' => $g_permission['staff'],
				'copyClassOptions' => $g_permission['staff'],
				'copyExisting' => $g_permission['staff'],
				'copyNew' => $g_permission['staff'],
				'importClass' => $g_permission['instructor'],
				'processCopyClass' => $g_permission['instructor'],
				'addNote' => $g_permission['proxy'],
				'saveNote' => $g_permission['proxy'],
				'exportClass' => $g_permission['proxy'],
				'generateBB' => $g_permission['proxy'],
				'searchTab' => $g_permission['staff'],
				'doSearch' => $g_permission['staff'],
				'addResultsToClass' => $g_permission['staff'],
				'reportsTab' => $g_permission['instructor'],
				'viewReport' => $g_permission['instructor'],
				'admin' => $g_permission['admin'],
				'switchUser' => $g_permission['admin'],
				'testMxe' => $g_permission['admin'],
				'manualCron' => Account_Rd::LEVEL_ADMIN,
				'help' => $g_permission['student'],
				'helpViewArticle' => $g_permission['student'],
				'helpEditArticle' => $g_permission['student'],
				'helpViewCategory' => $g_permission['student'],
				'helpEditCategory' => $g_permission['student'],
				'helpViewTag' => $g_permission['student'],	 
				'helpSearch' => $g_permission['student'],
				'helpSetRelated' => $g_permission['student'],
				'logout' => $g_permission['student'],
				'login' => Account_Rd::LEVEL_GUEST,
				'resetPassword' => Account_Rd::LEVEL_GUEST,
				'resetPasswordRequest' => Account_Rd::LEVEL_GUEST
			);
			self::$_initialized = true;
		}
	}
	
	public static function allowedCommand($userObject, $cmdString)
	{
/*		if ( //#TODO this is over paranoid, should just check against the list.
			!is_object($userObject)
			|| !method_exists($userObject, 'getUserName')
			|| is_a($userObject, 'Account_Nonuser')
			|| '' == trim($userObject->getUserName())
		) {
			return 'login';
		}
*/
		self::_init();		
		$role = $userObject->getRole();
		if (
			'login' == $cmdString
			&& $role > Account_Rd::LEVEL_GUEST
		) {
			return Rd_Dispatch::COMMAND_KEY_DEFAULT;
		}
		if (
			!array_key_exists($cmdString, self::$_acl) 
			|| $role < self::$_acl[$cmdString]
		){
			if (Rd_Debug::isEnabled()) {
				Rd_Debug::out("Failed Permission check: {$role} trying to access {$cmdString}");
			}
			return self::_checkPlugins($userObject, (
				Account_Rd::isGuest()
				? 'login'
				: Rd_Dispatch::COMMAND_KEY_DEFAULT
			));
		} else {
			return self::_checkPlugins($userObject, $cmdString);
		}
	}
	
	protected static function _checkPlugins($userObject, $cmdString)
	{
		//#TODO write plugin infrastructure
		//Force user to update email address
		if (
			!Account_Rd::isGuest()
			&& (
				'' == $userObject->getEmail() 
				|| '' == $userObject->getLastName() 
				|| '' == $userObject->getFirstName()
			) && !isset($_REQUEST['editUserSubmit'])
		){
	 		return 'newProfile';
		}
		if (	
			Account_Rd::atLeastStaff()
			&& (
				'' == $userObject->getStaffLibrary()
				|| '' == $userObject->getILSUserID()
			)
			&& !isset($_REQUEST['editUserSubmit'])
		){
	 		return 'newProfile';
		}
		return $cmdString;
	}
	
	public static function audit($userId,$level,$action,$table,$id)
	{
		$whenFormat = Rd_Registry::get('root:dbDateTimeFormat');
		$when = Rd_Pdo::escapeDate(date($whenFormat, time()),true);
		$userId = Rd_Pdo::escapeInt($userId);
		$level = Rd_Pdo::escapeInt($level);
		$action = Rd_Pdo::escapeString($action);
		$table = Rd_Pdo::escapeString($table);
		$id = Rd_Pdo::escapeInt($id);
		$columns = '`user`, `level`, `action`, `when`, `table`, `table_id`';
		$values = "{$userId}, {$level}, {$action}, {$when}, {$table}, {$id}";
		$result = Rd_Pdo::query("INSERT INTO `action_audit` ({$columns}) VALUES({$values});");
		return (
			$result
			? $result->rowCount() > 0
			: false
		);
	}
	
}