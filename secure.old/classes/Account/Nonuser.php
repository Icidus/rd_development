<?php
/*******************************************************************************
Account/Nonuser.php
Implements an interface for non-users

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
require_once(APPLICATION_PATH . '/classes/user.class.php');
require_once(APPLICATION_PATH . '/classes/courseInstance.class.php');
//require_once(APPLICATION_PATH . '/common.inc.php');
require_once(APPLICATION_PATH . '/classes/courseInstanceAudit.class.php');

class Account_Nonuser extends user
{
	//Attributes
	var $courseInstances = array();
	//var $reservesList = array();
	var $courseList = array();

	function __construct()
	{
	
	}

	
	/**
	* @return void
	* @param int $courseAliasID
	* @param string $enrollment_status (optional) APPROVED/PENDING/DENIED status
	* @desc Add course alias to the user profile. If record already exists, the enrollment status is updated
	*/
	function joinClass($courseAliasID, $enrollment_status=null) {
		return false;
	}
	
		
	/**
	* @return void
	* @param int $courseAliasID
	* @desc Remove the access record for this user and course alias
	*/
	function leaveClass($courseAliasID) {
		return false;
	}


	/**
	 * @return array of arrays
	 * @desc fetches all CIs that have status of ACTIVE, that this user is enrolled in, and whose date range includes today. Returns array of subarrays indexed by enrollment status
	 */
	public function getCourseInstances() {
		return array();
	}
	
	
	/**
	 * @return array
	 * @param int $instr_id Instructor ID
	 * @desc Returns array of currently active CIs that this instructor is teaching
	 */
	public function getCourseInstancesByInstr($instr_id) {
		return array();
	}
	
	
	/**
	 * @return array
	 * @desc Returns an array of CIs a student is allowed to "leave" (everything but autofed classes)
	 */
	public function getCourseInstancesToLeave() {
		return array();
	}
	
	
	/**
	* @return void
	* @desc surpresses a reserve from display --Not Yet Implemented
	*/
	function hideReserve()
	{
	}

	/**
	* @return void
	* @desc unsurpresses a reserve from display --Not Yet Implemented
	*/
	function unhideReserve()
	{
	}


	/**
	* @return array of reserves
	* @param int $courseInstanceID
	* @desc get Reserve items hidden by user for a course
	*/
	function getHiddenReserves($courseInstanceID)
	{
	}

	/**
	* @return array of reserves
	* @param int $courseInstanceID
	* @desc get Reserve items not hidden by user for a course
	*/
	function getUnhiddenReserves($courseInstanceID)
	{
	}
	
	function getUserByUserName($userName)
	{
		return null;
	}
	
	function getUserByExternalUserKey($key)
	{
		return null;
	}
	
	function getUserByUserName_Pwd($username, $password)
	{
		return null;
	}
	
	function createUser($userName)
	{
		return false;
	}
	
	function setUserName($userName)
	{
		return false;
	}
	
	function setFirstName($firstName)
	{
		return false;
	}
	
	function setLastName($lastName)
	{
		return false;
	}
	
	function setEmail()
	{
		return false;
	}
	
	function setDefaultRole()
	{
		return false;
	}
	
	function setLastLogin()
	{
		return false;
	}
	
	function getName()
	{
		return 'Guest User';
	}
	
	function getUserID()
	{
		return null;
	}
	
	function getId()
	{
		return null;
	}
	
	function getUsername()
	{
		'';
	}
	
	function getFirstName()
	{
		return 'Guest';
	}
	
	function getLastName()
	{
		return '';
	}
	
	function getEmail()
	{
		return '';
	}
	
	function getLastLogin()
	{
		return '';
	}
	
	function getExternalUserKey()
	{
		return '';
	}
	
	function isSpecialUser()
	{
		return false;
	}
	
	function getDefaultRole()
	{
		return null;
	}
	
	function getDefaultClass()
	{
		return null;
	}
	
	function getUserClass()
	{
		return null;
	}
	
	function getRole()
	{
		return -1;
	}
	
	function getUserByID($id)
	{
		return false;
	}
	
	function destroy()
	{
		return false;
	}
	
	static function getLibraries()
	{
		return array();
	}
	
	function getLoanPeriods()
	{
		return array();
	}
	
	function sendUserEmail()
	{
		return false;
	}
	
	function addNotTrained()
	{
		return false;
	}
	
	function removeNotTrained()
	{
		return false;
	}
	
	function isNotTrained()
	{
		return true;
	}
	
	function setExternalUserKey(){
		return false;
	}
	
	function fetchCourseInstances(){
		return array();
	}
}