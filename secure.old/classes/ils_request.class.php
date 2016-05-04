<?php
/*******************************************************************************
ils_request.class.php
Manipulates ILS-request data

Created by Dmitriy Panteleyev (dpantel@gmail.com)
Modified by NCSU Libraries, NC State University. Modifications by Troy Hurteau (libraries.opensource@ncsu.edu).

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
class ILS_Request {
	private $request_id;
	private $date_added;
	private $date_processed;
	private $ils_control_key;
	private $user_net_id;
	private $user_ils_id;
	private $ils_course;
	private $requested_loan_period;	
	
	
	/**
	 * Constructor - Initializes object by request_id, if provided
	 * 
	 * @param int $request_id (optional)
	 */
	function __construct($request_id=null) {
		if(!empty($request_id)) {
			$this->getByID($request_id);
		}
	}
	
	
	/**
	 * Initializes object based on id; returns TRUE on success, FALSE on failure
	 *
	 * @param string $request_id
	 * @return boolean
	 */
	function getByID($request_id) {
		global $g_dbConn;
		
		if(empty($request_id)) {
			return false;
		}
		
		$sql = "SELECT request_id, date_added, date_processed, ils_control_key, user_net_id, user_ils_id, ils_course, requested_loan_period
				FROM ils_requests WHERE request_id = ? and date_processed IS NULL";
		$rs = $g_dbConn->query($sql, $request_id);
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }

		if($rs->rowCount() > 0) {
			list($this->request_id, $this->date_added, $this->date_processed, $this->ils_control_key, $this->user_net_id, $this->user_ils_id, $this->ils_course, $this->requested_loan_period) = $rs->fetch();
			return true;
		}
		else {
			return false;
		}
	}
	
	
	/**
	 * Retrieves rows matching on control key; Returns array of ILS_Request objects
	 *
	 * @param string $control_key
	 * @return array
	 */
	public static function getRequestsByControlKey($control_key) { //#TODO this is an odd pattern
		global $g_dbConn;
		
		if(empty($control_key)) {
			return array();
		}
		
		//most control keys in DB have 'ocm' prefix, but the ils keys have 'o' prefix
		$control_key = preg_replace('/oc[mn]/i', 'o', trim($control_key));
		
		$sql = "SELECT request_id
				FROM ils_requests WHERE ils_control_key = ?";
		$rs = $g_dbConn->query($sql, $control_key);
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }

		$ils_requests = array();
		while($row = $rs->fetch(PDO::FETCH_NUM)) {
			$ils_requests[] = new ILS_Request($row[0]);
		}
		
		return $ils_requests;
	}

	
	/**
	 * Checks whether dept + course# provided in the feed match any aliases of the supplied CI
	 *
	 * @param int $ci_id
	 * @return boolean
	 */
	function doesCourseMatch($ci_id) {
		global $g_dbConn;

		if(empty($ci_id)) {
			return false;
		}
		
		$sql = "SELECT d.abbreviation, c.course_number
				FROM course_aliases AS ca
					JOIN courses AS c ON c.course_id = ca.course_id
					JOIN departments AS d ON d.department_id = c.department_id
				WHERE ca.course_instance_id = ?";
		$rs = $g_dbConn->query($sql, $ci_id);
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }
		
		while($row = $rs->fetch(PDO::FETCH_NUM)) {
			//if ILS course contains the course number and department abbreviation, then it's a match
			if((stripos($this->ils_course, $row[0]) !== false) && (strpos($this->ils_course, $row[1]) !== false)) {
				return true;
			}
		}
		
		//no match found
		return false;
	}
	
	
	/**
	 * Mark ils request as processed
	 */
	function markAsProcessed() {
		global $g_dbConn;
		
		if(!empty($this->request_id)) {
			$sql = " UPDATE ils_requests SET date_processed = CURRENT_TIMESTAMP WHERE request_id = ?";
			$rs = $g_dbConn->query($sql, $this->request_id);
			if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }
		}
	}
	
	
	/**
	 * Returns request ID (row ID)
	 *
	 * @return int
	 */
	function getRequestID() {
		return $this->request_id;
	}
	
	/**
	 * returns user ILS id
	 *
	 * @return string
	 */
	function getUserILSID() {
		return $this->user_ils_id;
	}
	
	
	function getUserNetID() {
		return $this->user_net_id;
	}
	
	function getCourseName() {
		return $this->ils_course;
	}
	
	/**
	 * Returns requested loan period
	 *
	 * @return string
	 */
	function getRequestedLoanPeriod() {
		return $this->requested_loan_period;
	}
}

