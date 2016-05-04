<?php 
/*******************************************************************************
courseInstance.class.php
Course Instance Primitive Object

Created by Jason White (jbwhite@emory.edu)
Modified by NCSU Libraries, NC State University. Modifications by Karl Doerr and Troy Hurteau (libraries.opensource@ncsu.edu).

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

require_once(APPLICATION_PATH . '/classes/course.class.php');
require_once(APPLICATION_PATH . '/classes/department.class.php');
require_once(APPLICATION_PATH . '/interface/instructor.class.php');
require_once(APPLICATION_PATH . '/classes/reserveItem.class.php');
require_once(APPLICATION_PATH . '/classes/reserves.class.php');
require_once(APPLICATION_PATH . '/classes/request.class.php');
require_once(APPLICATION_PATH . '/classes/tree.class.php');
require_once(APPLICATION_PATH . '/classes/terms.class.php');

class courseInstance
{
	//Attributes

	public $courseInstanceID;
	public $crossListings = array();			//array of courses
	public $course;						//single course
 	public $courseList = array();	//array of All courses associated with a course instance - note this publiciable was added by kawashi 11.5.2004
	public $instructorList = array();		//array of users
	public $instructorIDs = array(); 		//array of instructor userIDs
	public $primaryCourseAliasID;
	public $term;
	public $year;
	public $activationDate;
	public $expirationDate;
	public $status;
	public $enrollment;
	public $proxies = array();
	public $proxyIDs = array();
	public $students = array();
	public $containsHeading = false;
	public $duplicates = array();
	
	public $reviewedBy;
	public $reviewedOn;
	


	function courseInstance($courseInstanceId = NULL)
	{	
		$courseInstanceId = intval($courseInstanceId);
		if (!is_null($courseInstanceId)) {
			$this->getCourseInstance($courseInstanceId);
		}
	}

	/**
	 * @return boolean
	 * @param int $dept_id Department ID
	 * @param string $course_number Course number
	 * @param string $course_name Course name
	 * @param string $section Course section
	 * @param int $year Course year
	 * @param string $term Course term
	 * @desc True on success, false if there was a duplicate. MUST check return value, because this method _always_ initializes self to a valid CI (either dupe or new CI)
	 */
	function createCourseInstance($dept_id, $course_number, $course_name, $section, $year, $term) {
		global $g_dbConn;		
		
		$sql_insert_ci = 'INSERT INTO course_instances () VALUES ()';
		$sql_last_insert_id = 'SELECT LAST_INSERT_ID() FROM course_instances';

		//insert
		$rs = $g_dbConn->query($sql_insert_ci);
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }
		//get id
		$ci_id = $g_dbConn->getOne($sql_last_insert_id);
		if (Rd_Pdo_PearAdapter::isError($ci_id)) { trigger_error($ci_id->getMessage(), E_USER_ERROR); }
		
		//set the ID -- needed for setting of the course info
		$this->courseInstanceID = $ci_id;		
		$this->clearReviewed();		
		
		//now get or create a course
		
		$c  = new course(null);					
		//see if a matching course exists
		if($c->getCourseByMatch($dept_id, $course_number, $course_name)) {
			//course found, reuse it
			$this->setPrimaryCourse($c->getCourseID(), $section);
		}
		else {	//no such course, create new
			$c->createNewCourse($this->courseInstanceID);
			$c->setCourseNo($course_number);
			$c->setDepartmentID($dept_id);
			$c->setName($course_name);
			$c->setSection($section);
			$this->setPrimaryCourseAliasID($c->getCourseAliasID());
		}

		//Log creation of new course instance.
		
		$cia = new courseInstanceAudit();
		$cia->logCourseInstanceEvent($ci_id, courseInstanceAudit::EVENT_ADD);
		
		return true;	
	}

	private function getCourseInstance($courseInstanceID)
	{
		global $g_dbConn;

		$this->courseInstanceID = $courseInstanceID;

		$sql  = "SELECT ci.primary_course_alias_id, ci.term, ci.year, ci.activation_date, ci.expiration_date, ci.status, ci.enrollment, ci.reviewed_date, ci.reviewed_by "
			 //. "FROM course_instances as ci LEFT JOIN course_aliases as ca ON ci.course_instance_id = ca.course_instance_id "
			 . "FROM course_instances as ci "
			. "WHERE ci.course_instance_id = ?";

		$rs = $g_dbConn->query($sql, $this->courseInstanceID);
		if (Rd_Pdo_PearAdapter::isError($rs)) { 
			trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); 
		}

		$row = $rs->fetch(PDO::FETCH_NUM);
			$this->primaryCourseAliasID	= $row[0];
			$this->term					= $row[1];
			$this->year					= $row[2];
			$this->activationDate		= $row[3];
			$this->expirationDate		= $row[4];
			$this->status				= strtoupper($row[5]);
			$this->enrollment			= strtoupper($row[6]);
			$this->reviewedOn			= $row[7];
			$this->reviewedBy			= $row[8];
	}
	
	
	/**
	 * @return boolean
	 * @param int $dept_id Department ID
	 * @param string $courseNumber Course number
	 * @param string $section Course section
	 * @param int $year Course year
	 * @param string $term Course term
	 * @desc Initializes $this to the matching CI and returns TRUE if match found, else returns FALSE
	 */
	function getDuplicatesByMatch($dept_id, $courseNumber, $section, $year, $term) {
		global $g_dbConn;
		
		//select matching course instance ID
		$sql = "SELECT ci.course_instance_id "
			. "FROM course_instances AS ci "
			. "JOIN course_aliases AS ca ON ca.course_alias_id = ci.primary_course_alias_id "
			. "JOIN courses AS c ON c.course_id = ca.course_id "
			. "WHERE c.department_id = ? "
			. "AND c.course_number = ? "
			. "AND ca.section = ? "
			. "AND ci.year = ? "
			. "AND ci.term = ?";

		//query
		$rs = $g_dbConn->query($sql, array($dept_id, $courseNumber, $section, $year, $term));		
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }
		
		if($rs->rowCount() == 0) {	//no match found
			return false;
		}
		else {
			$this->duplicates = null;  // clear array			
			while($row = $rs->fetch(PDO::FETCH_NUM))
			{
				if ($this->courseInstanceID != $row[0])
					$this->duplicates[] = new courseInstance($row[0]);	//add duplicate to array
			}
			return true;
		}
	}
	

	/**
	 * 
	 * Enter description here ...
	 * @throws Rd_Exception 410
	 */
	function getCrossListings()
	{
		global $g_dbConn;

		$sql  = "SELECT ca.course_alias_id "
		  . "FROM course_aliases AS ca "
		  . "LEFT JOIN course_instances AS ci ON ca.course_instance_id = ci.course_instance_id "
		  . "WHERE ca.course_instance_id = ? "
		  . "AND ca.course_alias_id <> ? "; //ca.course_alias_id ?= ci.primary_course_alias_id
		
		if(!$this->course->getCourseAliasID()){
			throw new Rd_Exception(410);
		}
		
		$rs = $g_dbConn->query($sql, array($this->courseInstanceID, $this->course->getCourseAliasID()));
/*
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }
*/
		
		unset($crossListings);
		$crossListings = array();
		if(!Rd_Pdo_PearAdapter::isError($rs)){
			while ($row = $rs->fetch(PDO::FETCH_NUM)) {
				$this->crossListings[] = new course($row[0]);
			}
		} else {
			Rd_Debug::out("<p>Database error on CI: {$this->courseInstanceID}</p>");
		}

		//if this->crosslistings is set, or is empty, add a new course with the primary course alias id.
		return $this->crossListings;
	}

	/**
	 * 
	 * Enter description here ...
	 * @param unknown_type $courseID
	 * @param unknown_type $section
	 */
	function addCrossListing($courseID, $section="")
	{
		global $g_dbConn;

		$sql_check_listing = "SELECT course_alias_id FROM course_aliases WHERE course_instance_id = ? AND course_id = ? AND section = ?";
		$sql_cpy_listing = "INSERT INTO course_aliases (course_instance_id, course_id, section) VALUES (?,?,?)";
		
		//check to see if the crosslisting -- maybe should just add a DB unique-constraint
		$rs = $g_dbConn->query($sql_check_listing, array($this->courseInstanceID, $courseID, $section));
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }
		
		if($rs->rowCount() == 0) {	//crosslisting does not exist - add it
			$rs = $g_dbConn->query($sql_cpy_listing, array($this->courseInstanceID, $courseID, $section));
			if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }
			$ciaudit = new courseInstanceAudit();
			$ciaudit->logCrossListingAddEvent($this->courseInstanceID, $courseID, courseInstanceAudit::EVENT_ADD);
			
		}
		
		#TODO the below may throw an exception
		$this->getCrossListings();
	}
	
	/**
	* Remove Crosslistings from Class
	* @return boolean
	* @param array $course_alias_ids Array of course_alias_id
	* @desc delete crosslisting return false on failure; if primary_course_alias_id appears in 
	* 	    $course_alias_ids no changes will be made
	*/	
	function removeCrossListing($course_alias_ids)
	{	
		global $g_dbConn;
		
		$rv = false;	
		$sql = "DELETE FROM course_aliases WHERE course_alias_id = ?";
				
		$ciaudit = new courseInstanceAudit();
		if (is_array($course_alias_ids) && !empty($course_alias_ids))
		{
			//before removing crosslist confirm it is not primary
			if (in_array($this->getPrimaryCourseAliasID(), $course_alias_ids))
				return false;
				
			foreach($course_alias_ids as $ca_id)
			{				
				$ciaudit->logCrossListingRemoveEvent($ca_id, courseInstanceAudit::EVENT_REMOVE);
				$rs = $g_dbConn->query($sql, $ca_id);
				if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }
			}
			$rv = true;
		}
		
		return $rv;
	}
	

	function getProxies()
	{
		global $g_dbConn;

		$sql  = "SELECT DISTINCT u.username, u.user_id "
		  . "FROM users u "
		  .	"LEFT JOIN access AS a ON a.user_id = u.user_id "
		  . "LEFT JOIN course_aliases AS ca ON ca.course_alias_id = a.alias_id "
		  . "WHERE ca.course_instance_id = ? "
		  . "AND a.permission_level = 2";

		$rs = $g_dbConn->query($sql, $this->courseInstanceID);

		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }

		$this->proxyIDs = $this->proxies = array();
		while ($row = $rs->fetch(PDO::FETCH_NUM)) {
			$this->proxies[] = new proxy($row[0]);
			$this->proxyIDs[] = $row[1];
		}
		
		return $this->proxies;
	}

	
	/**
	 * @return array
	 * @desc Resturns array of arrays indexed by course and status; each subarray holds student objects
	 */
	function getRoll() {
		global $g_dbConn;
		
		$sql = "SELECT DISTINCT u.username, a.enrollment_status, a.alias_id "
			. "FROM users AS u "
			.  "JOIN access AS a ON a.user_id = u.user_id "
			. "JOIN course_aliases AS ca ON ca.course_alias_id = a.alias_id "
			. "WHERE ca.course_instance_id = {$this->courseInstanceID} "
			. "AND a.permission_level = 0 "
			. "ORDER BY u.last_name, u.first_name, u.username";
		
		//execute query
		$rs = $g_dbConn->query($sql);
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }
		
		$roll = array();
		while($row = $rs->fetch(PDO::FETCH_ASSOC)) {
			$status = !empty($row['enrollment_status']) ? $row['enrollment_status'] : 'PENDING';
			$ca = $row['alias_id'];
			
			//add students into subarrays indexed by enrollment status
			$roll[$ca][$status][] = new student($row['username']);
		}
		
		return $roll;
	}

	/**
	* @return void
	* @desc destroy the database entry
	*/
	/*
	function destroy()
	{
		global $g_dbConn;

		$sql = "DELETE "
			.  "FROM course_instances "
			.  "WHERE course_instance_id = ?";

		$rs = $g_dbConn->query($sql, $requestID);
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }
	}
	*/
	function destroy()
	{
		global $g_dbConn;

		$sql_deleteReserves =
			"DELETE "
			.  "FROM reserves "
			.  "WHERE course_instance_id = ?"
			;

		$sql_deleteCourseInstance =
			"DELETE "
			.  "FROM course_instances "
			.  "WHERE course_instance_id = ?"
			;

		$sql_deleteAccess =
			"DELETE access "
			.  "FROM  access "
			.	"JOIN course_aliases as ca on ca.course_alias_id = access.alias_id "
			.  "WHERE ca.course_instance_id = ?"
			;


		//Combine these queries into a single nested SQL statement, once MySQL is upgraded
		$sql_checkCourse =
			"SELECT course_id "
			.		"FROM course_aliases "
			.		"WHERE course_instance_id = ? "
			;

		$sql_checkCourse2 =
			"SELECT count( ca.course_instance_id ) "
			.	"FROM course_aliases AS ca "
			.	"WHERE ca.course_id = ? "
			.	"AND ca.course_instance_id <> ?"
			;
		//End SQL statements to be combined into a nested SQL statement

		/*
		$sql_deleteCourse =
			"DELETE courses "
			.  "FROM  courses "
			.	"JOIN course_aliases as ca on ca.course_id = courses.course_id "
			.  "WHERE ca.course_instance_id = ?"
			;
		*/

		$sql_deleteCourse =
			"DELETE "
			.  "FROM  courses "
			.  "WHERE course_id = ?"
			;

		$sql_deleteCourseAliases =
			"DELETE "
			.  "FROM course_aliases "
			.  "WHERE course_instance_id = ?"
			;

		$sql_deleteRequests =
			"DELETE "
			.  "FROM requests "
			.  "WHERE course_instance_id = ?"
			;

		$rs = $g_dbConn->query($sql_deleteReserves, $this->courseInstanceID);
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }

		$cia = new courseInstanceAudit();
		$cia->logCourseInstanceEvent($this->courseInstanceID, courseInstanceAudit::EVENT_REMOVE);
		
		$rs = $g_dbConn->query($sql_deleteCourseInstance, $this->courseInstanceID);
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }

		$rs = $g_dbConn->query($sql_deleteAccess, $this->courseInstanceID);
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }

		$rs = $g_dbConn->query($sql_checkCourse, $this->courseInstanceID);
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }

		while ($row = $rs->fetch(PDO::FETCH_NUM)) {
			$rs2 = $g_dbConn->query($sql_checkCourse2, array($row[0],$this->courseInstanceID));
			if (Rd_Pdo_PearAdapter::isError($rs2)) { trigger_error($rs2->getMessage(), E_USER_ERROR); }

			$row2 = $rs2->fetch(PDO::FETCH_NUM);
			if ($row2[0] == 0) {
				$rs3 = $g_dbConn->query($sql_deleteCourse, $row[0]);
				if (Rd_Pdo_PearAdapter::isError($rs3)) { trigger_error($rs3->getMessage(), E_USER_ERROR); }
			}
		}

		$rs = $g_dbConn->query($sql_deleteCourseAliases, $this->courseInstanceID);
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }

		$rs = $g_dbConn->query($sql_deleteRequests, $this->courseInstanceID);
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }
	}

	/**
	* @return void
	* @desc getAddedCourses from DB
	*/
	function getCourses()
	{
		global $g_dbConn;

		$sql  = "SELECT course_alias_id FROM course_aliases WHERE course_instance_id = ?";

		$rs = $g_dbConn->query($sql, $this->courseInstanceID);
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }

		while ($row = $rs->fetch(PDO::FETCH_NUM)) {
			$this->courseList[] = new course($row[0]);
		}
	}

	public function unCouch()
	{
		$this->getPrimaryCourse();
		if ($this->course) {
			$this->course->unCouch();
		}
	}
	
	function getPrimaryCourse()
	{
		return $this->course = new course($this->primaryCourseAliasID);
	}
	
	
	/**
	 * @return void
	 * @param int $user_id (optional) User for which to get the course
	 * @desc Returns the course (crosslisting) for a user.  If user is not specified, attempts to get course for current user.  If user has no course specified (staff) this method gets primary course.  In the end $this->course is set.
	 */
	/*
	public function getCourseForUser($user_id=null) {
		global $u, $g_dbConn;
		
		//build the query
		switch($g_dbConn) {
			default:	//mysql
				$sql = "SELECT DISTINCT ca.course_alias_id
						FROM course_aliases AS ca
							JOIN access AS a ON a.alias_id = ca.course_alias_id
						WHERE a.user_id = ?
							AND ca.course_instance_id = ?";
		}
		
		//make sure we have a user ID
		if(empty($user_id)) {	//if not passed an ID
			$user_id = $u->getUserID();	//use the current user
		}
		
		//if the user is an instructor for this class
		if(in_array($user_id, $this->instructorIDs)) {
			$this->course = new course($this->primaryCourseAliasID);	//use primary course
		}
		else {	//user not an instructor, query DB
			$rs = $g_dbConn->query($sql, array($user_id, $this->courseInstanceID));
			if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }
			
			if($rs->rowCount() > 0) {	//if user has a specified course
				$row = $rs->fetch(PDO::FETCH_NUM);
				$this->course = new course($row[0]);	//use it
			}
			else {	//no specified course
				$this->course = new course($this->primaryCourseAliasID);	//use primary course
			}
		}		
	}
	*/
	
	public function getCourseForUser($user_id=null) {
		global $g_dbConn;
		$u = Rd_Registry::get('root:userInterface');
		//build the query
		switch($g_dbConn) {
			default:	//mysql
				$sql = "SELECT DISTINCT ca.course_alias_id
						FROM course_aliases AS ca
							JOIN access AS a ON a.alias_id = ca.course_alias_id
						WHERE a.user_id = ?
							AND ca.course_instance_id = ?";
		}
		
		//make sure we have a user ID
		if(empty($user_id)) {	//if not passed an ID
			$user_id = $u->getUserID();	//use the current user
		}
		
		//if the user is an instructor for this class
		$rs = $g_dbConn->query($sql, array($user_id, $this->courseInstanceID));
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }
		
		if($rs->rowCount() > 0) {	//if user has a specified course
			$row = $rs->fetch(PDO::FETCH_NUM);
			$this->course = new course($row[0]);	//use it
		}
		else {	//no specified course
			$this->course = new course($this->primaryCourseAliasID);	//use primary course
		}		
	}


	function getPermissionForUser($userID)
	{
		global $g_dbConn;

		$sql  = "SELECT a.permission_level, nt.permission_level "
			.  		"FROM course_aliases as ca "
			.  		"  LEFT JOIN access as a on a.alias_id = ca.course_alias_id "
			.  		"  LEFT JOIN not_trained as nt on nt.user_id = a.user_id "
			.	    "WHERE ca.course_instance_id = ? AND a.user_id = ? "
			.		"ORDER BY a.permission_level DESC ";

		$rs = $g_dbConn->query($sql, array($this->courseInstanceID, $userID));
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }

		$row = $rs->fetch(PDO::FETCH_NUM);
		return (is_null($row[1]) ? $row[0] : $row[1]);
	}

	function setPrimaryCourse($courseID, $section="")
	{

		global $g_dbConn;

		$sql_primary_listing	 = "INSERT INTO course_aliases (course_id, course_instance_id, section) VALUES (?,?,?)";
		$sql_inserted_listing	 = "SELECT LAST_INSERT_ID() FROM course_aliases";
		$sql 					 = "UPDATE course_instances SET primary_course_alias_id = ? WHERE course_instance_id = ?";


		$rs = $g_dbConn->query($sql_primary_listing, array($courseID, $this->courseInstanceID, $section));
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }

		$rs = $g_dbConn->query($sql_inserted_listing);
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }

		$row = $rs->fetch(PDO::FETCH_NUM);
		$this->primaryCourseAliasID = $row[0];

		$rs = $g_dbConn->query($sql, array($this->getPrimaryCourseAliasID(), $this->getCourseInstanceID()));
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }
	}


	function setPrimaryCourseAliasID($primaryCourseAliasID)
	{
		global $g_dbConn;

		$this->primaryCourseAliasID = $primaryCourseAliasID;
		$sql = "UPDATE course_instances SET primary_course_alias_id = ? WHERE course_instance_id = ?";

		$rs = $g_dbConn->query($sql, array($primaryCourseAliasID, $this->courseInstanceID));
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }
	}

	function setTerm($term)
	{
		global $g_dbConn;

		$this->term = $term;
		$sql = "UPDATE course_instances SET term = ? WHERE course_instance_id = ?";

		$rs = $g_dbConn->query($sql, array($term, $this->courseInstanceID));
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }
	}

	function setYear($year)
	{
		global $g_dbConn;

		$this->year = $year;
		$sql = "UPDATE course_instances SET year = ? WHERE course_instance_id = ?";

		$rs = $g_dbConn->query($sql, array($year, $this->courseInstanceID));
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }
	}

	
	/**
	 * @return void
	 * @param string $activationDate New activation date (YYYY-MM-DD)
	 * @desc Sets the activation date as long as it's within the boundaries defined by the term; 
	 * 		 otherwise term boundaries are used; do not restrict staff
	 */
	function setActivationDate($activationDate, $userRole=0) {
		global $g_dbConn;
				
		//attempt to parse input date that may be in non-standard formats and get unix timestamp
		$activation_unix_tstamp = strtotime($activationDate);		
		
		if ($userRole < Account_Rd::LEVEL_STAFF)
		{
			//get bounding activation date (based on term)
			//bound = term begin - 1 month
			$term = new term();
			$term->getTermByName($this->term, $this->year);
			//get unix timestamp of the date 1 month before the beginning of term
			$activation_limit = strtotime("-1 month", strtotime($term->getBeginDate()));					
			
			//use either the input or the boundary, whichever occurs at a later date (greater unix tstamp)
			$proper_tstamp = ($userRole >= Account_Rd::LEVEL_STAFF && $activation_unix_tstamp > $activation_limit) ? $activation_unix_tstamp : $activation_limit;
		} else {
			$proper_tstamp = $activation_unix_tstamp;	
		}
		
		//now build the actual activation date
		$activation_date = date('Y-m-d', $proper_tstamp);		
		
		$sql = "UPDATE course_instances SET activation_date = '$activation_date' WHERE course_instance_id = {$this->courseInstanceID}";

		$rs = $g_dbConn->query($sql);
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }
		
		$this->activationDate = $activation_date;
	}
	
	
	/**
	 * @return void
	 * @param string $expirationDate New expiration date (YYYY-MM-DD)
	 * @desc Sets the expiration date as long as it's within the boundaries defined by the term; 
	 * 		 otherwise term boundaries are used; Do not limit staff or greater
	 */
	function setExpirationDate($expirationDate, $userRole=0) {
		global $g_dbConn;

		//attempt to parse input date that may be in non-standard formats and get unix timestamp
		$expiration_unix_tstamp = strtotime($expirationDate);
		
		
		if ($userRole < Account_Rd::LEVEL_STAFF)
		{
			//get bounding expiration date (based on term)
			//bound = term end + 1 month
			$term = new term();
			$term->getTermByName($this->term, $this->year);
			//get unix timestamp of the date 1 month after the end of term
			$expiration_limit = strtotime("+1 month", strtotime($term->getEndDate()));
								
			//use either the input or the boundary, whichever occurs at an earlier date (smaller unix tstamp)
			$proper_tstamp = ($expiration_unix_tstamp < $expiration_limit) ? $expiration_unix_tstamp : $expiration_limit;
		} else {
			$proper_tstamp = $expiration_unix_tstamp;			
		}
		
		//now build the actual expiration date
		$expiration_date = date('Y-m-d', $proper_tstamp);		
		
		$sql = "UPDATE course_instances SET expiration_date = '$expiration_date' WHERE course_instance_id = {$this->courseInstanceID}";

		$rs = $g_dbConn->query($sql);
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }
		
		$this->expirationDate = $expiration_date;
	}
	

	function setStatus($status)
	{
		global $g_dbConn;

		$this->status = $status;
		$sql = "UPDATE course_instances SET status = ? WHERE course_instance_id = ?";

		$rs = $g_dbConn->query($sql, array($status, $this->courseInstanceID));
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }
	}

	function setEnrollment($enrollment) {
		global $g_dbConn;
		
		//make sure this is a valid choice
		$allowed_enrollment = array('OPEN', 'MODERATED', 'CLOSED');
		if(!in_array($enrollment, $allowed_enrollment)) {
			$enrollment = 'OPEN';	//if choice is not valid, change to OPEN
		}

		$this->enrollment = $enrollment;
		$sql = "UPDATE course_instances SET enrollment = ? WHERE course_instance_id = ?";

		$rs = $g_dbConn->query($sql, array($enrollment, $this->courseInstanceID));
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }
	}

	/**
	* @return array
	* @param string $sort_by How to sort the list
	* @param int $parent_id Which sublist to sort
	* @desc load reserves in a particular sort order
	*/
	function getSortedReserves($sort_by=null, $parent_id=null) {
		global $g_dbConn;

		$sql = "SELECT r.reserve_id
						FROM reserves as r
							JOIN items as i ON r.item_id = i.item_id
						WHERE r.course_instance_id = ".$this->courseInstanceID;				
		$sql_where_parent_set = "	AND r.parent_id = ".intval($parent_id);
		$sql_where_parent_unset = " AND (r.parent_id IS NULL OR r.parent_id = '')";				
		$sql_order_default 	= " ORDER BY r.sort_order, i.title";
		$sql_order_author  	= " ORDER BY i.author, i.title";
		$sql_order_title	= " ORDER BY i.title, i.author";

		//set sort
		switch ($sort_by) {
			case 'author':
				$sort = $sql_order_author;
				break;
			case 'title':
				$sort = $sql_order_title;
				break;
			default:
				$sort = $sql_order_default;
		}
		//set parent
		$parent = empty($parent_id) ? $sql_where_parent_unset : $sql_where_parent_set;
		
		$rs = $g_dbConn->query($sql.$parent.$sort);
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }
			
		$reserves = array();
		while($row = $rs->fetch(PDO::FETCH_NUM)) {
			$reserves[] = $row[0];
        }
        
        return $reserves;
	}
	
	
	/**
	 * @return obj reference
	 * @param string $tree_gen_method Specifies a callback method for generating the tree
	 * @param array $tree_gen_method_args Array of arguments for the callback metehod
	 * @param array $reserve_data array of reserve data for tree building
	 * @desc Returns a reference to a tree object. The tree is built using the passed data, or by using the callback method specified by the first param. If no data or callback method is specified, returns a tree for all reserves.
	 */
	//function &getReservesAsTree($tree_gen_method='getReserves', $tree_gen_method_args=array(), &$reserve_data=null) {
	function getReservesAsTree($tree_gen_method='getReserves', $tree_gen_method_args=array(), &$reserve_data=null) {
		$tree_gen_method = ($tree_gen_method ? $tree_gen_method : 'getReserves');		
		//get array of reserves for this CI for tree-building
		$tree_data = array();
		
		//first see if tree data has already been passed
		if(is_array($reserve_data)) {
			if(is_array($reserve_data[0]) && is_array($reserve_data[1])) {	//is the array properly formatted?
				$tree_data = &$reserve_data;	//use data
			}
		}
		
		//if no data has been passed, try generating it
		if(empty($tree_data) && method_exists($this, $tree_gen_method)) {	//check to see if method exists
			$tree_data = call_user_func_array(array(&$this, $tree_gen_method), $tree_gen_method_args);
		}
		
		//if still no data, then we can do nothing else
		if(empty($tree_data)) {
			return null;
		}
		else {	//we have data; build and return the tree
			//build tree
			$tree = new Tree('root');
			$tree->buildTree($tree_data[0], $tree_data[1]);
			return $tree;
		}		
	}
	
	
	/**
	 * @return obj reference
	 * @param string $tree_gen_method Specifies a callback method for generating the tree
	 * @param array $tree_gen_method_args Array of arguments for the callback metehod
	 * @desc Returns a reference to a recursive tree iterator object.  The tree is built using the callback method specified by the first param.
	 */
	function &getReservesAsTreeWalker($tree_gen_method='getReserves', $tree_gen_method_args=array()) {
		if(!empty($tree_gen_method)) {	//if passed a way to generate a new tree
			$tree = new treeWalker($this->getReservesAsTree($tree_gen_method, $tree_gen_method_args));
		}
		else {	//no tree => no walker
			$tree = null;
		}	
		return $tree;	
	}
	

	/**
	 * @return array (with 3 subarrays) [0] indexed by rID, with parentID as value; [1] indexed by rID, with sort order as value; [2]holds rIDs of all reserves marked as hidden.  Arrays are meant to be used tree-builder precursors
	 * @param int $user_id (optional) User ID.  If specified, method will ignore items marked 'hidden' by the specified user
	 * @param boolean $show_hidden (optional) Only matters if user_id is set.  If true, will override the default behavior and include hidden items in the returned array.  Will also include an array of items marked 'hidden' as part of the result
	 * @param boolean $show_inactive (optional) If false, will return ACTIVE items only, otherwise will return all
	 * @desc Fetches and reserves info from DB, based on flags.  Used by other methods to do their fetching.
	 */
	function getReservesAsTreePrecursor($user_id=null, $show_hidden=false, $show_inactive=false, $heading_only=false) {
		global $g_dbConn;

		$sql_select = "SELECT r.reserve_id, r.parent_id, r.sort_order";
		$sql_select_hidden = ", hr.user_id";
		$sql_from = " FROM reserves AS r JOIN items AS i ON i.item_id = r.item_id";
		$sql_from_join_hidden = " LEFT JOIN hidden_readings AS hr ON (hr.reserve_id = r.reserve_id AND hr.user_id = " . intval($user_id).")";
		$sql_where = " WHERE course_instance_id = " . $this->courseInstanceID;
		$sql_where_active_only = " AND r.status='ACTIVE' AND r.activation_date <= NOW() AND NOW() <= r.expiration";
		$sql_where_noshowhidden = " AND hr.user_id IS NULL";
		$sql_where_heading_only = " AND i.item_type = 'HEADING'";
		$sql_order = " ORDER BY r.sort_order, i.title";	
		
		//build query	
		if(!empty($user_id)) {	//if user specified, join to hidden_readings table and select user_id
			$sql_select .= $sql_select_hidden;
			$sql_from .= $sql_from_join_hidden;
			
			if(!$show_hidden) {	//if we do not want to get hidden items, exclude them
				$sql_where .= $sql_where_noshowhidden;
			}			
		}
		if(!$show_inactive) {	//if we do not want inactive (and in process) items, exclude them
			$sql_where .= $sql_where_active_only;
		}
		if($heading_only) {	//only interested in headings/folders
			$sql_where .= $sql_where_heading_only;
		}
		//piece the query together		
		$sql = $sql_select.$sql_from.$sql_where.$sql_order;
			
		//query
		$rs = $g_dbConn->query($sql);
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }
	
		//need 3 arrays
		$reserves_data = array();
		$sort_data = array();
		$hidden_data = array();
		
		while( ($row = $rs->fetch(PDO::FETCH_NUM)) ) {
			$reserves_data[$row[0]] = $row[1];	//indexed by rID, with parentID as value
			$sort_data[$row[0]] = $row[2];		//indexed by rID, with sort order as value
			
			if(!empty($row[3])) {
				$hidden_data[] = $row[0];	//holds rIDs of all reserves marked as hidden
			}
		}

		return array($reserves_data, $sort_data, $hidden_data);	
	}
	
	
	/**
	 * @return array (with 3 subarrays) [0] indexed by rID, with parentID as value; [1] indexed by rID, with sort order as value; [2]holds rIDs of all reserves marked as hidden. Arrays are meant to be used tree-builder precursors
	 * @param int $user_id (optional) User ID.  If specified, method will ignore items marked 'hidden' by the specified user
	 * @param boolean $show_hidden (optional) Only matters if user_id is set.  If true, will override the default behavior and include hidden items in the returned array.  Will also include an array of items marked 'hidden' as part of the result
	 * @desc Returns info on reserves.
	 */
	function getActiveReservesForUser($user_id, $show_hidden) {
		return $this->getReservesAsTreePrecursor($user_id, $show_hidden);
	}
	
	/**
	 * @return array (with 3 subarrays) [0] indexed by rID, with parentID as value; [1] indexed by rID, with sort order as value; [2]holds rIDs of all reserves marked as hidden.  Arrays are meant to be used tree-builder precursors
	 * @desc Returns info on reserves.
	 */
	function getActiveReserves() {
		return $this->getReservesAsTreePrecursor();
	}
	
	
	/**
	 * @return array (with 3 subarrays) [0] indexed by rID, with parentID as value; [1] indexed by rID, with sort order as value; [2]holds rIDs of all reserves marked as hidden.  Arrays are meant to be used tree-builder precursors
	 * @desc Returns info on reserves.
	 */
	function getReserves() {
		return $this->getReservesAsTreePrecursor(null, null, true);
	}
	
	public function getReservesCount()
	{
		$sql = 'SELECT COUNT(r.reserve_id) FROM course_aliases AS ca '
			. 'JOIN course_instances AS ci ON ci.primary_course_alias_id = ca.course_alias_id '
			. 'JOIN reserves AS r ON r.course_instance_id = ci.course_instance_id '
			. "WHERE ca.course_instance_id = {$this->courseInstanceID};";
		$result = Rd_Pdo::one(Rd_Pdo::query($sql),PDO::FETCH_NUM);
		return $result[0];	//#TODO not the best way to mine this...
	}
	
	
	/**
	 * @return array (with 3 subarrays) [0] indexed by rID, with parentID as value; [1] indexed by rID, with sort order as value; [2]holds rIDs of all reserves marked as hidden.  Arrays are meant to be used tree-builder precursors
	 * @desc Returns only headings/folders.
	 */
	function getHeadings() {
		return $this->getReservesAsTreePrecursor(null, null, true, true);
	}
	
		
	/**
	 * @return void
	 * @param int $new_ci_id ID of the destination CI
	 * @param array $selected_reserves (optional) Array of reserve IDs to copy.  Used when only part of the reserve list is to be copied.
	 * @param array $requested_loan_periods (optional) Array of requested loan periods for physical-item reserves.
	 * @desc Copies reserves from this CI to the destination CI
	 */
	function copyReserves($dst_ci_id, $selected_reserves=null, $requested_loan_periods=null) 
	{		
		if(empty($dst_ci_id)) {
			return;
		}
		
		//this script may take a while, so increase max exec time
		set_time_limit(180);	//allow 3 minutes for this script
		
		//gather info
		$tree = $this->getReservesAsTree();
		$dst_ci = new courseInstance($dst_ci_id);
		$dst_ci->getInstructors();

		//copy reserves
		$this->copyReserveTree($tree, $dst_ci, null, $selected_reserves, $requested_loan_periods);
		
		if (!is_null($this->getReviewedBy()))
		{
			//if src has been reviewed set reviewed for copy
			$dst_ci->setReviewed($this->getReviewedBy(), $this->getReviewedOn());
		}
	}	
	
	
	/**
	 * @return void
	 * @param Tree $root Reference The reserve tree.
	 * @param courseInstance $target_ci Refernce The destination CI
	 * @param int $parent_id ID of reserve to be used as parent for the leaves of the growing tree.
	 * @param array $selected_reserves (optional) Array of reserve IDs to copy.  Used when only part of the reserve list is to be copied.
	 * @param array $requested_loan_periods (optional) Array of requested loan periods for physical-item reserves.
	 * @desc Copies reserves from this CI to the destination CI (recursive)
	 */	
	function copyReserveTree(&$root, &$target_ci, $parent_id=null, &$selected_reserves=null, &$requested_loan_periods=null) {
		foreach($root as $leaf) {	//walk through the children
			//if this is a non-empty array, then only copy those reserves that are in the array
			$copy_reserve = true;
			if(is_array($selected_reserves) && !empty($selected_reserves)) {
				if(!in_array($leaf->getID(), $selected_reserves)) {
					$copy_reserve = false;	//if this reserve is not in the "selected" list, then skip it
				}
			}

			if($copy_reserve) {	//copy reserve
				//fetch source reserve
				$src_reserve = new reserve($leaf->getID());
				$src_reserve->getItem();
				
				//create new reserve
				$reserve = new reserve();
				if($reserve->createNewReserve($target_ci->getCourseInstanceID(), $src_reserve->getItemID())) {
					$reserve->setActivationDate($target_ci->getActivationDate());
					$reserve->setExpirationDate($target_ci->getExpirationDate());
					$reserve->setStatus($src_reserve->getStatus());
					$reserve->setSortOrder($src_reserve->getSortOrder());
					$reserve->setParent($parent_id);
					//duplicate notes
					$src_reserve->duplicateNotes($reserve->getReserveID());
					
					//if physical item, put it on request
					if($src_reserve->item->isPhysicalItem()) {
						$reserve->setStatus("IN PROCESS");
						if(!empty($requested_loan_periods)) {	//set requested loan period if specified
							$reserve->setRequestedLoanPeriod($requested_loan_periods[$src_reserve->getReserveID()]);
						}
						
						//create request
						$req = new request();
						//make sure request does not exist
						//prevent duplicate requests
						if($req->getRequestByCI_Item($target_ci->getCourseInstanceID(), $src_reserve->getItemID()) === false) {
							$req->createNewRequest($target_ci->getCourseInstanceID(), $src_reserve->getItemID());
							$req->setRequestingUser($target_ci->instructorIDs[0]);
							$req->setReserveID($reserve->getReserveID());
						}
					}
				}
				//use this as the parent_id for any children
				$new_parent_id = $reserve->getReserveID();
			}
			else {
				$new_parent_id = null;
			}
							
			//copy reserve's children
			if($leaf->hasChildren()) {
				$this->copyReserveTree($leaf, $target_ci, $new_parent_id, $selected_reserves, $requested_loan_periods);
			}
		}
	}
		
	
	/**
	* @return void
	* @desc load instructorList from DB
	*/
	function getInstructors()
	{
		global $g_dbConn;

		$sql = "SELECT DISTINCT a.user_id "
			.	   "FROM access as a "
			.	   "LEFT JOIN course_aliases as ca on ca.course_alias_id = a.alias_id "
			.	   "WHERE ca.course_instance_id = ? AND a.permission_level = 3"; //3 = instructor #TODO fix this hardcoding
		
		$rs = $g_dbConn->query($sql, array($this->getCourseInstanceID()));		
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }
		
		$this->instructorIDs = $this->instructorList = array();
		while ($row = $rs->fetch(PDO::FETCH_NUM)) {
			$tmpI = new instructor();
			$tmpI->getUserByID($row[0]);
			$this->instructorList[] = $tmpI;
			$this->instructorIDs[] = $row[0];
		}
		
		return $this->instructorList;
	}

	function addInstructor($courseAliasID, $instructorID)
	{
		global $g_dbConn, $g_permission;
		$this->getInstructors();	
		
		if(in_array($instructorID, $this->instructorIDs)) { return null; }
		
		$sql  = "SELECT access_id from access WHERE user_id = ? AND alias_id = ?";
		$sql2 = "INSERT INTO access (user_id, alias_id, permission_level, enrollment_status) VALUES (?, ?, ?, ?)";
		$sql3 = "UPDATE access set enrollment_status='APPROVED', permission_level = ".$g_permission['instructor']." WHERE access_id = ?"; 

		$rs = $g_dbConn->query($sql, array($instructorID, $courseAliasID));
		if (Rd_Pdo_PearAdapter::isError($rs)) {trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR);}

		if ($rs->rowCount() == 0) {
			$rs = $g_dbConn->query($sql2, array($instructorID, $courseAliasID, Account_Rd::LEVEL_FACULTY, 'APPROVED'));
			if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }
			$ciaudit = new courseInstanceAudit();
			$ciaudit->logInstructorEvent($courseAliasID, $this->getCourseInstanceID(), $instructorID, courseInstanceAudit::EVENT_ADD);
		} else {
			$data = $rs->fetch(PDO::FETCH_ASSOC); 
			$rs = $g_dbConn->query($sql3, array($data['access_id']));
			if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }
		}
	}


	function addProxy($courseAliasID, $proxyID)
	{
		global $g_dbConn, $g_permission;

		$sql = "SELECT access_id, permission_level from access WHERE user_id = ? AND alias_id = ?";
		$sql2 = "INSERT INTO access (user_id, alias_id, permission_level, enrollment_status) VALUES (?, ?, ?, ?)";
		$sql3 = "UPDATE access set enrollment_status='APPROVED', permission_level = ".$g_permission['proxy']." WHERE access_id = ?"; 

        $proxy = new Proxy();
        $proxy->getUserByID($proxyID);
        $proxy->setAsProxy(); //upgrade default user role if needed
        unset($proxy);

		$rs = $g_dbConn->query($sql, array($proxyID, $courseAliasID));
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }

        //if numRows == 0 then insert new enrollment record
		if ($rs->rowCount() == 0) {
			$rs2 = $g_dbConn->query($sql2, array($proxyID, $courseAliasID, $g_permission['proxy'], 'APPROVED'));
			if (Rd_Pdo_PearAdapter::isError($rs2)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }
			$ciaudit = new courseInstanceAudit();
			$ciaudit->logProxyEvent($courseAliasID, $proxyID, courseInstanceAudit::EVENT_ADD);
		} else {
            //enrollment record exists upgrade class permissions if needed
			$data = $rs->fetch(PDO::FETCH_ASSOC); 
			
			if ($data['permission_level'] < $g_permission['proxy'])
			{
				$rs = $g_dbConn->query($sql3, array($data['access_id']));
				if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }
			}
			if ($data['permission_level'] > $g_permission['proxy'])
			{
				throw new Exception('ERROR: User cannot be a proxy for a class that they teach.');
			}
		}
	}


	function removeInstructor($courseAliasID, $instructorID)
	{
		global $g_dbConn;

		$sql = "DELETE FROM access WHERE user_id = ? AND alias_id = ? and permission_level = 3 LIMIT 1";

		$rs = $g_dbConn->query($sql, array($instructorID, $courseAliasID));
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }
		$ciaudit = new courseInstanceAudit();
		$ciaudit->logInstructorEvent($courseAliasID, $_REQUEST['ci'], $instructorID, courseInstanceAudit::EVENT_REMOVE);
	}

	function displayInstructors($add_emails=false) {
		$retValue = '';
		foreach($this->instructorList as $instr) {
			if($add_emails) {
				if($instr->getName(false) != ""){
					$retValue .= '<a href="mailto:'.$instr->getEmail().'">'.$instr->getName(false).'</a>, ';
				}
			}
			else {
				if($instr->getName(false) != null){
					$retValue .= $instr->getName(false).', ';
				}
			}
		}
		$retValue = rtrim($retValue, ', ');	//trim the last comma
		return empty($retValue) ? "None" : $retValue;
	}

	function displayCrossListings()
	{
		$retValue = "";
		for($i=0;$i<count($this->crossListings);$i++)
		{
			$retValue .=  $this->crossListings[$i]->getName() . " ";
		}
		return ($retValue == "" ? "No Crosslistings" : $retValue);
	}
	
	/**
	 * @return boolean
	 * @desc True if ci is enrollable
	 */	
	function EnrollmentAllowed() {
		//only allow active courses with open or moderated enrollment
		if(($this->getEnrollment() != 'CLOSED') && ($this->getStatus() == 'ACTIVE')) {
			return true;
		}
		else {
			return false;
		}
	}

	/**
	 * @return void
	 * @param user_id integer modified user id
	 * @param date date most likely now
	 * @desc set reviewing user and date
	 */	
	function setReviewed($user_id, $date) {
		global $g_dbConn;

		$sql = "UPDATE course_instances SET reviewed_by = ?, reviewed_date = ? WHERE course_instance_id = ?";

		$rs = $g_dbConn->query($sql, array($user_id, $date, $this->getCourseInstanceID()));
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }		
	}

	/**
	 * @return void
	 * @desc reset reviewed
	 */	
	function clearReviewed() {
		global $g_dbConn;

		$sql = "UPDATE course_instances SET reviewed_by = null, reviewed_date = null WHERE course_instance_id = ?";

		$rs = $g_dbConn->query($sql, array($this->getCourseInstanceID()));
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }		
	}	
	
	function getAuditTrail()
	{
		$ciaudit = new courseInstanceAudit();
		return $ciaudit->getEventsForCourse($this->courseInstanceID);
	}

	function getCourseInstanceID()
	{ 
		return $this->courseInstanceID; 
	}
	
	function getPrimaryCourseAliasID()
	{
		return $this->primaryCourseAliasID;
	}
	
	function getTerm()
	{ 
		return $this->term; 
	}
	function getYear()
	{ 
		return $this->year; 
	}
	
	function displayTerm()
	{ 
		return $this->term . " " . $this->year; 
	}
	
	public function isActive()
	{
		$activeTime = strtotime($this->activationDate);
		$expireTime = strtotime($this->expirationDate) + ((60*60*24));
		$now = time();
		$manuallyDeactivated = 'INACTIVE' == $this->status;
		//$hasReserves = $this->getReservesCount() > 0;
		return (
			!$manuallyDeactivated //&& $hasReserves
			&& $now >= $activeTime && $now < $expireTime
		);
	}
	
	function getActivationDate()
	{
		return $this->activationDate; 
	}
	
	function getExpirationDate()
	{ 
		return $this->expirationDate; 
	}
	
	function getStatus()
	{ 
		return $this->status; 
	}
	
	function getEnrollment() 
	{ 
		return $this->enrollment; 
	}
	
	public function hasStudent($userId)
	{
		$sql  = 'SELECT a.user_id FROM access AS a '
			. 'JOIN course_aliases AS ca ON ca.course_alias_id = a.alias_id AND a.user_id = ?'
			. 'WHERE ca.course_instance_id = ? '
			. 'AND a.permission_level = ?';

		$rs = Rd_Pdo::query($sql, array($userId, $this->courseInstanceID, Account_Rd::LEVEL_STUDENT));

		if (Rd_Pdo_PearAdapter::isError($rs)) { 
			trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); 
		}

		while ($row = $rs->fetch(PDO::FETCH_NUM)) {
			if ($userId == $row[0]) {
				return true;
			}
		}
		return false;
	}
	
	function reviewed() 
	{ 
		return !is_null($this->reviewedBy); 
	}
	
	function getReviewedBy() 
	{ 
		return $this->reviewedBy; 
	}
	
	function getReviewedOn() 
	{ 
		return $this->reviewedOn; 
	}
	
	function getRegistrarKey()
	{
		return $this->course ? $this->course->registrarKey : '';
	}
	
	function getReviewed() 
	{
		if (!is_null($this->reviewedBy))
		{
			$r_user = new user($this->reviewedBy);
			return 	'Staff review completed by ' . $r_user->getName() . ' on ' . $this->reviewedOn;
		} else {
			return 'Library staff review needed';
		}
	}

}
