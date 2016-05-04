<?php
/*******************************************************************************
proxy.class.php
Proxy Interface

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
require_once(APPLICATION_PATH . '/interface/proxy.class.php');
require_once(APPLICATION_PATH . '/classes/courseInstance.class.php');

class instructor extends proxy
{
	//Attributes
	var $ils_user_id;
	var $ils_name;
	var $organization_status;

	protected $_ilsInitialized = false;
	
	protected function _ilsInit()
	{
		if(!$this->_ilsInitialized){
			$this->getInstructorAttributes();
			$this->_ilsInitialized = true;
		}
	}

	function instructor($userName=null)
	{
		if (!is_null($userName)) {
			$this->getUserByUserName($userName);
		}
	}


	/**
	* @return void
	* @get intructor attributes from table
	*/
	function getInstructorAttributes()
	{
		global $g_dbConn;

		$d = date('Y-m-d');
		$sql = 	"SELECT ils_user_id, ils_name, organizational_status "
			.		"FROM instructor_attributes "
			.		"  WHERE user_id = ? ";
			
		$rs = $g_dbConn->query($sql, $this->getUserID());
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }

		list($this->ils_user_id, $this->ils_name, $this->organization_status) = $rs->fetch(PDO::FETCH_NUM);
	}
	
	
	public function getByILSUserID($ils_user_id) {
		global $g_dbConn;
		
		if(empty($ils_user_id)) {
			return false;
		}
		
		//get user_id by ils_user_id
		$sql = "SELECT user_id FROM instructor_attributes WHERE ils_user_id = '{$ils_user_id}'";
		$rs = $g_dbConn->getOne($sql);
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }
		
		if(empty($rs)) {
			return false;
		}
		else {
			//init object
			return $this->getUserByID($rs);
		}
	}
	

	/**
	* @return void
	* @get intructor attributes from table
	*/
	function storeInstructorAttributes($ILS_userID, $ILS_name, $orgStatus="")
	{
		global $g_dbConn;
		//print_r(array($ILS_userID, $ILS_name));
		$d = date('Y-m-d');
		$sql = 	"SELECT count(user_id) "
			.		"FROM instructor_attributes "
			.		"WHERE user_id = ? ";

		$rs = $g_dbConn->query($sql, $this->getUserID());
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }

		$cnt = $rs->fetch(PDO::FETCH_NUM);

		if ($cnt[0] == 0)
		{
			$sql = "INSERT INTO instructor_attributes "
				 . "(user_id, ils_user_id, ils_name, organizational_status ) "
				 . "VALUES (?,?,?,?)"
				 ;
			$values = array($this->getUserID(), $ILS_userID, $ILS_name, $orgStatus);
		} else {
			$sql = "UPDATE instructor_attributes "
				 . "SET ils_user_id=?, ils_name=?, organizational_status=? "
				 . "WHERE user_id=?"
				 ;
			$values = array($ILS_userID, $ILS_name, $orgStatus, $this->getUserID());
		}

		$rs = $g_dbConn->query($sql, $values);
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }
	}

	function getILSUserID() { 
		$this->_ilsInit();
		return ($this->ils_user_id != "") ? $this->ils_user_id : null;
	}
	function getILSName()	{ 
		$this->_ilsInit();
		return $this->ils_name; 
	}
	function getOrgStatus() { return $this->organizational_status; }


	/**
	* @return void
	* @desc return array of proxies for instructors current and future classes
	*/
	/*
	function getProxies()
	{
		global $g_dbConn, $g_permission;

		$sql = 	"SELECT DISTINCT proxy.user_id "
			.		"FROM access as proxy "
			.		"  JOIN access as a ON proxy.alias_id = a.alias_id AND proxy.permission_level = " . $g_permission['proxy'] . " AND a.user_id = ? "
			.		"  JOIN course_aliases as ca ON a.alias_id = ca.course_alias_id "
			.		"  JOIN course_instances as ci ON ca.course_instance_id = ci.course_instance_id "
			.		"  WHERE ci.expiration_date > ?";

		$d = date('Y-m-d');
		
		$rs = $g_dbConn->query($sql, array($this->getUserID(), $d));
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }

		$tmpArray = array();
		while($row = $rs->fetch(PDO::FETCH_NUM))
		{
			$p = new proxy();
			$p->getUserByID($row[0]);

			$tmpArray[] = $p;
		}
		return $tmpArray;
	}
	*/
	function removeProxy($proxyID, $courseInstanceID)
	{
		global $g_dbConn;

		$sql = 	"SELECT a.access_id "
			.		"FROM access as a "
			.		"  JOIN course_aliases as ca ON a.alias_id = ca.course_alias_id "
			.		"WHERE a.user_id = ? AND ca.course_instance_id = ?";

		$sql1 =	"DELETE FROM access WHERE access_id = ?";
		$rs = $g_dbConn->query($sql, array($proxyID, $courseInstanceID));
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }

		while($row = $rs->fetch(PDO::FETCH_NUM))
		{
			$aliasID = $row[0];

			$ciaudit = new courseInstanceAudit();
			$ciaudit->logProxyEvent($aliasID, $proxyID, courseInstanceAudit::EVENT_REMOVE);
			$rs2 = $g_dbConn->query($sql1, $aliasID);
			if (Rd_Pdo_PearAdapter::isError($rs2)) { trigger_error($rs2->getMessage(), E_USER_ERROR); }
		}
	}

	/**
	* @return void
	* @param int $proxyID
	* @param int $courseInstanceID
	* @desc update users table if necessary and insert into access table to make the specified user a proxy for the specified class
	*/
	function makeProxy($proxyID, $courseInstanceID)
	{
		$c = new CourseInstance($courseInstanceID);
		$c->addProxy($c->getPrimaryCourseAliasID(), $proxyID);
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
	
		//get CIs where user is an instructor
		$intructor_CIs = array_merge(
			$this->fetchCourseInstances('instructor', $activation_date, $expiration_date, 'ACTIVE'),
			$this->fetchCourseInstances('instructor', $activation_date, $expiration_date, 'AUTOFEED')
		);
		//get CIs where user is a proxy
		$proxy_CIs = array_merge(
			$this->fetchCourseInstances('proxy', $activation_date, $expiration_date, 'ACTIVE'),
			$this->fetchCourseInstances('proxy', $activation_date, $expiration_date, 'AUTOFEED')
		);
		
		//return the combined list
		return array_merge($intructor_CIs, $proxy_CIs);
	}
	
	/**
	 * @return array
	 * @desc Returns an array of all current and future CIs for this instructor
	 */
	public function getAllFutureCourseInstances() {		
		//show current courses, or those that will start within a year
		//do not show expired courses
		$activation_date = null;
		$expiration_date = date('Y-m-d');
		$status = null;
	
		//get CIs where user is an instructor
		$intructor_CIs = $this->fetchCourseInstances('instructor', $activation_date, $expiration_date, $status);
		//$intructor_auto_CIs = $this->fetchCourseInstances('instructor', $activation_date, $expiration_date, 'AUTOFEED');
		//get CIs where user is a proxy
		//$proxy_active_CIs = $this->fetchCourseInstances('proxy', $activation_date, $expiration_date, 'ACTIVE');
		
		//return the combined list
		return ($intructor_CIs);
	}	
	
	/**
	 * @return array
	 * @desc Returns an array of current and past CIs this user can edit
	 */
	public function getCourseInstancesToImport() {
		//show current courses, or those that have already expired
		$activation_date = date('Y-m-d');
		
		//get list of CIs
		$active = $this->fetchCourseInstances('instructor', $activation_date, null, 'ACTIVE');
		$inactive = $this->fetchCourseInstances('instructor', $activation_date, null, 'INACTIVE');
			
		//return combined list
		return ($active + $inactive);
	}
	
	
	/**
	 * @return array
	 * @desc Returns an array of cancelled/inactive CIs an instructor may remove from their list (NOT same as 'delete')
	 */
	public function getCourseInstancesToRemove() {
		//get list of CIs
		$cancelled = $this->fetchCourseInstances('instructor', null, null, 'CANCELED');
		$not_activated = $this->fetchCourseInstances('instructor', null, null, 'AUTOFEED');
			
		//return combined list
		return ($cancelled + $not_activated);
	}
	
	
	/**
	 * @return void
	 * @param int $courseAliasID
	 * @desc Remove the access record for this user and course alias
	 */
	function removeClass($courseAliasID) {
		global $g_dbConn;

		$sql = "DELETE FROM access WHERE user_id = {$this->getUserID()} AND alias_id = $courseAliasID AND permission_level = 3 LIMIT 1";

		$ciaudit = new courseInstanceAudit();
		$ciaudit->logInstructorEvent($courseAliasID, $_REQUEST['ci'], $this->getUserID(), courseInstanceAudit::EVENT_REMOVE);
		$rs = $g_dbConn->query($sql);
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }
	}


    /**
     * Generate a list of available reports
     *
     * @return array containing report_id, title, sql, parameters
     */
    function getReportList()
    {
            global $g_dbConn, $g_permission;

			$sql = "SELECT report_id, title, `sql`, parameters "
				.  "FROM reports "
				.  "WHERE min_permissions <= " . $this->getRole() . " "
				.  "ORDER BY title";

            $rs = $g_dbConn->query($sql);
            if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }
			
            $tmpArray = null;
            while ($row = $rs->fetch(PDO::FETCH_ASSOC))
                    $tmpArray[] = $row;

            return $tmpArray;
    }
}
