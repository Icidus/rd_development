<?php
/*******************************************************************************
item.class.php
Item Primitive Object

Created by Jason White (jbwhite@emory.edu)
Modified by NCSU Libraries, NC State University. Modifications by Karl Doerr & Troy Hurteau (libraries.opensource@ncsu.edu).

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

class student extends user
{
	//Attributes
	var $courseInstances = array();
	//var $reservesList = array();
	var $courseList = array();

	function student($userName)
	{
		if (is_null($userName)) trigger_error($userName . " has not been authorized as student", E_USER_ERROR);
		else {
			$this->getUserByUserName($userName);
		}		
	}

	
	/**
	* @return void
	* @param int $courseAliasID
	* @param string $enrollment_status (optional) APPROVED/PENDING/DENIED status
	* @desc Add course alias to the user profile. If record already exists, the enrollment status is updated
	*/
	function joinClass($courseAliasID, $enrollment_status=null) {
		global $g_dbConn;
		
		if(empty($enrollment_status)) {
			$enrollment_status = 'PENDING';
		}

		$sql_check = "SELECT access_id, permission_level from access WHERE user_id = ? AND alias_id = ?";
		$sql_insert = "INSERT INTO access (user_id, alias_id, permission_level, enrollment_status) VALUES (?,?,0,?)";
		$sql_update = "UPDATE access SET enrollment_status= ? WHERE user_id = ? AND alias_id = ?";

		$rs = $g_dbConn->query($sql_check, array($this->userID, $courseAliasID));
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }
		
		if($rs->rowCount() == 0) {	//insert
			$rs = $g_dbConn->query($sql_insert, array($this->userID, $courseAliasID, $enrollment_status));
			if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }
		}
		else{
			$row = $rs->fetch(PDO::FETCH_NUM);
		
			//print_r($row);
			if($row[1] > 0){
				throw new Exception("ERROR: Cannot be a student in a class that you teach or proxy");
			}
			else {	//update
				$rs = $g_dbConn->query($sql_update, array($enrollment_status, $this->userID, $courseAliasID));
				if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }
			}
		}
		$ciaudit = new courseInstanceAudit();
		$ciaudit->logStudentEvent($courseAliasID, $this, courseInstanceAudit::EVENT_ADD);	
	}
	
		
	/**
	* @return void
	* @param int $courseAliasID
	* @desc Remove the access record for this user and course alias
	*/
	function leaveClass($courseAliasID) {
		global $g_dbConn;

		$sql = "DELETE FROM access WHERE user_id = ? AND alias_id = ? and permission_level = 0 LIMIT 1";
		$ciaudit = new courseInstanceAudit();
		$ciaudit->logStudentEvent($courseAliasID, $this, courseInstanceAudit::EVENT_REMOVE);
		$rs = $g_dbConn->query($sql, array($this->userID, $courseAliasID));
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }
	}


	/**
	 * @return array of arrays
	 * @desc fetches all CIs that have status of ACTIVE, that this user is enrolled in, and whose date range includes today. Returns array of subarrays indexed by enrollment status
	 */
	public function getCourseInstances() {
		$today = date('Y-m-d');
		$courses = array();
		
		//go through a $tmp var to avoid adding empty array()s to the $courses array
		//this is done to simplify running empty() on $courses, since
		//empty(array(array())) returns false		
		$tmp = $this->fetchCourseInstances('student', $today, $today, 'ACTIVE', 'AUTOFEED');	//enrolled by registrar
		if(!empty($tmp)) {
			$courses['AUTOFEED'] = $tmp;			
		}
		$tmp = $this->fetchCourseInstances('student', $today, $today, 'ACTIVE', 'APPROVED');	//enrolled manually
		if(!empty($tmp)) {
			$courses['APPROVED'] = $tmp;			
		}
		$tmp = $this->fetchCourseInstances('student', $today, $today, 'ACTIVE', 'PENDING');	//requested enrollment
		if(!empty($tmp)) {
			$courses['PENDING'] = $tmp;			
		}
		$tmp = $this->fetchCourseInstances('student', $today, $today, 'ACTIVE', 'DENIED');	//denied enrollment
		if(!empty($tmp)) {
			$courses['DENIED'] = $tmp;			
		}
		
		return $courses;
	}
	
	
	/**
	 * @return array
	 * @param int $instr_id Instructor ID
	 * @desc Returns array of currently active CIs that this instructor is teaching
	 */
	public function getCourseInstancesByInstr($instr_id) {
		$today = date('Y-m-d');
		$instr = new user($instr_id);
		//return all currently active courses that this user is teaching
		return $instr->fetchCourseInstances('instructor', $today, $today, 'ACTIVE');
	}
	
	
	/**
	 * @return array
	 * @desc Returns an array of CIs a student is allowed to "leave" (everything but autofed classes)
	 */
	public function getCourseInstancesToLeave() {
		$today = date('Y-m-d');
		$approved = $this->fetchCourseInstances('student', $today, $today, 'ACTIVE', 'APPROVED');	//enrolled manually
		$pending = $this->fetchCourseInstances('student', $today, $today, 'ACTIVE', 'PENDING');	//requested enrollment
		$denied = $this->fetchCourseInstances('student', $today, $today, 'ACTIVE', 'DENIED');	//denied enrollment
		
		return ($approved + $pending + $denied);
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
}
