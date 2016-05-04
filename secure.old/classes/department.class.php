<?php
/*******************************************************************************
department.class.php
Department Primitive Object

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
require_once(APPLICATION_PATH . '/classes/library.class.php');

class department extends library
{
	//Attributes
	public $deptID;
	public $name;
	public $abbr;

	function department($deptID=null)
	{
		global $g_dbConn;

		if (!is_null($deptID))
		{
			$this->deptID = $deptID;

			$sql  = "SELECT d.name, d.abbreviation, l.library_id, l.name, l.nickname, l.url "
			  . "FROM departments as d LEFT JOIN libraries as l ON d.library_id = l.library_id "
			  . "WHERE d.department_id = ?";

			$rs = $g_dbConn->query($sql, $this->deptID);
			if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }

			$row = $rs->fetch(PDO::FETCH_NUM);
				$this->name 			= $row[0];
				$this->abbr 			= $row[1];
				$this->libraryID 		= $row[2];
				$this->library 			= $row[3];
				$this->libraryNickname 	= $row[4];
				$this->libraryURL 		= $row[5];
		}

	}

	function getDepartmentByAbbr($abbr)
	{
		global $g_dbConn;

		$sql  = "SELECT d.name, d.abbreviation, l.library_id, l.name, l.nickname, l.url, d.department_id "
		  . "FROM departments as d LEFT JOIN libraries as l ON d.library_id = l.library_id "
		  . "WHERE d.abbreviation = ?";

		$rs = $g_dbConn->query($sql, $abbr);
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }
		
		//if($rs->rowCount() > 0) 
		//{
			$row = $rs->fetch(PDO::FETCH_NUM);
				$this->name 			= $row[0];
				$this->abbr 			= $row[1];
				$this->libraryID 		= $row[2];
				$this->library 			= $row[3];
				$this->libraryNickname 	= $row[4];
				$this->libraryURL 		= $row[5];
				$this->deptID			= $row[6];		
			return $row[6];
		//} else {
		//	return null;
		//}
	}	
	
	function createDepartment($name, $abbr, $library_id)
	{
		global $g_dbConn;
		$sql['new']  = "INSERT INTO departments (name, abbreviation, library_id) VALUES (?,?,?)";
		$sql['inserted'] = "SELECT LAST_INSERT_ID() FROM departments";

		$rs = $g_dbConn->query($sql['new'], array($name, $abbr, $library_id));
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }
		
		$d_id =  $g_dbConn->getOne($sql['inserted']);
		$this->department($d_id);		
	}

	function updateDepartment()
	{
		global $g_dbConn;
		$sql  = "UPDATE departments SET name=?, abbreviation=?, library_id=? WHERE department_id = ?";

		$rs = $g_dbConn->query($sql, array($this->name, $this->abbr, $this->libraryID, $this->deptID));
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }
		
		return true;
	}

	/**
	* @return department recordset
	* @desc returns all departments
	*/
	function getAllDepartments()
	{
		global $g_dbConn;

		$sql  = "SELECT d.department_id "
			. "FROM departments d "
			.	"WHERE d.name IS NOT NULL "
			. "ORDER BY d.abbreviation ASC";

		$rs = $g_dbConn->query($sql);
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }

		$tmpArray = array();
		while($row = $rs->fetch(PDO::FETCH_NUM)) {
			$tmpArray[] = new department($row[0]);
		}
		return $tmpArray;
	}

	function getDepartmentID() { return $this->deptID; }
	function getName() { return $this->name; }
	function getAbbr() { return $this->abbr; }
	
	
	function setName($name){ $this->name = stripslashes($name);}
	function setAbbr($abbr){ $this->abbr = stripslashes($abbr);}
	function setLibraryID($library_id) 
	{ 
		$this->libraryID = $library_id;
		
		$l = new library($this->libraryID);
		$this->library = $l;
		$this->libraryNickname = $l->getLibraryNickname();
		$this->libraryURL = $l->getLibraryURL();
	}
	
	/**
	 * Return an array of human readable loan periods from the db
	 */
	
	/*
	function getInstructorLoanPeriods()
	{
		global $g_dbConn;

		$sql  = "SELECT lp.loan_period, lpi.default "
			. "FROM inst_loan_periods as lp "
			. " JOIN inst_loan_periods_libraries as lpi ON lp.loan_period_id = lpi.loan_period_id "
			.	"WHERE lpi.library_id = ? "
			. "ORDER BY lp.loan_period_id";


		$rs = $g_dbConn->query($sql, $this->libraryID);
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }

		$tmpArray = null;
		while ($row = $rs->fetch(PDO::FETCH_ASSOC)) {
			$tmpArray[] = $row;
		}
		return $tmpArray;		
	}
	*/
	
	function findByPartialName($deptName)
	{
		global $g_dbConn;

		$sql  = "SELECT d.department_id, d.abbreviation, d.name, l.library_id, l.nickname "
			. "FROM departments as d "
			.	"JOIN libraries as l ON d.library_id = l.library_id "
			.	"WHERE d.name like '$deptName%' or d.abbreviation like '$deptName%' AND d.status IS NULL "
			. "ORDER BY d.abbreviation LIMIT 30";

		$rs = $g_dbConn->query($sql);
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }

		$tmpArray = null;
		while ($row = $rs->fetch(PDO::FETCH_ASSOC)) {
			$tmpArray[] = $row;
		}		
		return $tmpArray;		
	}
	
	public function __toString(){
		return $this->getAbbr();
	}
}
