<?php
/*******************************************************************************
proxy.class.php
Proxy Interface Object

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
require_once(APPLICATION_PATH . '/interface/student.class.php');
require_once(APPLICATION_PATH . '/classes/courseInstance.class.php');

class proxy extends student
{
	function proxy($userName=null)
	{
		if (!is_null($userName)) {
			$this->getUserByUserName($userName);
		}
	}

	
	/**
	 * @return array
	 * @desc Returns an array of current and future CIs this user can edit
	 */
	public function getCourseInstancesToEdit() {
		//show current courses, or those that will start within a year
		//do not show expired courses
		$activation_date = date('Y-m-d', strtotime('+1 year'));
		$expiration_date = date('Y-m-d');
		
		//now query
		return array_merge(
			$this->fetchCourseInstances('proxy', $activation_date, $expiration_date, 'ACTIVE'),
			$this->fetchCourseInstances('proxy', $activation_date, $expiration_date, 'AUTOFEED')
		);
	}
	

	/**
	* @return $errorMsg
	* @desc remove a cross listing from the database
	*/
	function removeCrossListing($courseAliasID)
	{
		global $g_dbConn;

		$errorMsg = "";
		$course = new course($courseAliasID);

		$sql =	"SELECT CONCAT(u.last_name,', ',u.first_name) AS full_name "
			.	"FROM access a "
			.	" LEFT JOIN users u ON u.user_id = a.user_id "
			.	"WHERE a.alias_id = ? "
			.	"AND a.permission_level = 0 "
			.	"ORDER BY full_name";
		$sql2 = "DELETE FROM access "
			.	"WHERE alias_id = ? "
			.	"AND permission_level >= 2";
		$sql3 = "DELETE FROM course_aliases "
			.  "WHERE course_alias_id = ?";

		//Check to see if any students have added the course_alias
		$rs = $g_dbConn->query($sql, $courseAliasID);
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }

		if ($rs->rowCount() == 0) {
			//Delete entries, for Proxy or greater, from the access table
			$rs = $g_dbConn->query($sql2, $courseAliasID);
			if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }

			//Delete entry from the course_alias table
			$rs = $g_dbConn->query($sql3, $courseAliasID);
			if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }

			//Delete the actual course
			$course->destroy();
		} else {
			$errorMsg = "<br>The cross listed course, ".$course->displayCourseNo().", could not be deleted because the following student(s) have added the course:<br>";

			$i=0;
			while ($row = $rs->fetch(PDO::FETCH_NUM)) {
				if ($i==0) {
					$errorMsg = $errorMsg.$row[0];
				} else {
					$errorMsg = $errorMsg."; ".$row[0];
				}
				$i++;
			}
			$errorMsg = $errorMsg."<br>Please contact the Reserves Desk for further assistance.<br>";
		}
		$ciaudit = new courseInstanceAudit();
		$ciaudit->logCrossListingRemoveEvent($courseAliasID, courseInstanceAudit::EVENT_REMOVE);
		return $errorMsg;
	}


	/**
	* @return void
	* @desc add a cross listing to a course_instance
	*/
	function addCrossListing($ci, $dept, $courseNo, $section, $courseName, $ca=null)
	{

		$course = new course();
		if (is_null($ca))
			$course->createNewCourse($ci->courseInstanceID);
		else
		{
			$course->course($ca);
			$course->bindToCourseInstance($ci->courseInstanceID);
		}
	
				
		$course->setDepartmentID($dept);
		$course->setCourseNo($courseNo);
		$course->setSection($section);
		$course->setName($courseName);
		
		$ciaudit = new courseInstanceAudit();
		$ciaudit->logCrossListingAddEvent($ci->courseInstanceID, $course->getCourseAliasID(), courseInstanceAudit::EVENT_ADD);
		

		$ci->getInstructors();
		$ci->getProxies();

		//Add access to the Cross Listing for all instructors teaching the course
		for($i=0;$i<count($ci->instructorIDs);$i++) {
			$ci->addInstructor($course->courseAliasID,$ci->instructorIDs[$i]);
		}

		/* commented out by kawashi - No longer able to change primary, so this is not necessary 11.12.04
		//Add access to the Cross Listing for all proxies assigned to the course
		for($i=0;$i<count($ci->proxyIDs);$i++) {
			$ci->addProxy($course->courseAliasID,$ci->proxyIDs[$i]);
		}
		*/
	}

	
	function getAllDocTypeIcons()
	{
		global $g_dbConn;

		$sql = "SELECT DISTINCT mimetype_id, helper_app_name, helper_app_icon "
			.	   "FROM mimetypes "
			.	   "ORDER BY mimetype_id ASC"	;
		
		
		$rs = $g_dbConn->query($sql);
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }

		$tmpArray = array();
		
		$tmpArray[0] = array ('mimetype_id' => null, 'helper_app_name' => 'Default', 'helper_app_icon' => null);
		
		while($row = $rs->fetch(PDO::FETCH_ASSOC))
		{
			$tmpArray[] = $row;
		}

		return $tmpArray;
	}
	
	
	/**
	 * @return boolean
	 * @param int $ci_id CourseInstance ID
	 * @param array $student_IDs Array of student userIDs
	 * @param string $roll_action Roll action to perform (add/remove/deny)
	 * @desc Adds, removes, etc students to/from class
	 */
	function editClassRoll($ci_id, $student_IDs, $roll_action) {
		global $g_permission;
		$u = Rd_Registry::get('root:userInterface');
		//make sure we have all the info
		if(empty($ci_id) || empty($student_IDs)) {
			return false;
		}
		//for compatibility, make sure $student_IDs is an array
		if(!is_array($student_IDs)) {
			$student_IDs = array($student_IDs);	//make it an array
		}
		
		$ci = new courseInstance($ci_id);	//init CI
		//only allow instructors and proxies for THIS class to manipulate roll (or staff+)					
		$ci->getInstructors();
		$ci->getProxies();					
		if(in_array($u->getUserID(), $ci->instructorIDs) || in_array($u->getUserID(), $ci->proxyIDs) || ($u->getRole() >= $g_permission['staff'])) {
			foreach($student_IDs as $student_id) {	
				if(empty($student_id)) {
					continue;	//skip blank IDs
				}
				
				//get the student
				//some limitations -- cannot create a new student object by user ID
				$student = new user($student_id);	//init a generic user object
				$student = new student($student->getUsername());	//now init a student object by username
				
				//get the primary course for this user
				$ci->getCourseForUser($student->getUserID());
				
				//perform action
				switch($roll_action) {
					case 'add':
						$student->joinClass($ci->course->getCourseAliasID(), 'APPROVED');
					break;				
					case 'remove':
						$student->leaveClass($ci->course->getCourseAliasID());
					break;				
					case 'deny':
						$student->joinClass($ci->course->getCourseAliasID(), 'DENIED');
					break;
				}
			}
		}
	}

	/**
	* @return void
	* @desc Updates the DB setting the user's default role if the current role < proxy
	*/
	function setAsProxy()
	{
        global $g_permission;

        if ($this->dfltRole < $g_permission['proxy'])
        {
            $this->setDefaultRole($g_permission['proxy']);
        }
	}
}
