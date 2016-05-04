<?php
/*******************************************************************************
term.class.php
term object handles term table

Created by Jason White (jbwhite@emory.edu)
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
class term
{
	public $term_id;
	public $sort_order;
	public $term_name;
	public $term_year;
	public $begin_date;
	public $end_date;
	
	
	function __construct($term_id=null) {
		if(!empty($term_id)) {
			$this->getTermByID($term_id);
		}
	}
	
	
	function create($name, $year, $begin, $end, $sort)
	{
		global $g_dbConn;
		$sql = "INSERT INTO terms (term_name, term_year, begin_date, end_date, sort_order) VALUES (?, ?, ?, ?, ?)";
		$sql2 = "SELECT LAST_INSERT_ID() FROM terms";

		$rs = $g_dbConn->query($sql, array($name, $year, $begin, $end, $sort));		
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }

		$rs = $g_dbConn->query($sql2);
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }

		$row = $rs->fetch(PDO::FETCH_NUM);
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }
		
		$this->getTermByID($row[0]);
		return true;		
	}
	
	/**
	 * @return boolean
	 * @param int $term_id Term ID
	 * @desc Fetches the object, based on term ID. Returns true on success, false otherwise;
	 */
	function getTermByID($term_id) {
		global $g_dbConn;
		
		if(empty($term_id)) {
			return false;
		}
		
		$sql = "SELECT term_id, sort_order, term_name, term_year, begin_date, end_date FROM terms WHERE term_id = $term_id LIMIT 1";

		$rs = $g_dbConn->query($sql);
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }
		
		if($rs->rowCount()==1) {
			list($this->term_id, $this->sort_order, $this->term_name, $this->term_year, $this->begin_date, $this->end_date) = $rs->fetch(PDO::FETCH_NUM);
			return true;
		}
		else {
			return false;
		}
	}

	
	/**
	 * @return boolean
	 * @param string $date The date; format: YYYY-MM-DD
	 * @desc If possible, sets this object to the term spanning date and returns true; otherwise returns false;
	 */
	public function getTermByDate($date) {
		global $g_dbConn;
		
		if(empty($date)) {
			$date = date("Y-m-d");
		}

		$sql = "SELECT term_id FROM terms WHERE begin_date <= '{$date}' AND '{$date}' <= end_date LIMIT 1";

		$rs = $g_dbConn->getOne($sql);
		if (Rd_Pdo_PearAdapter::isError($rs)) {
			trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); 
		}
		
		if(empty($rs)) {
			return false;
		}
		else {
			$this->getTermByID($rs);
			return true;
		}
	}
	
	
	/**
	 * @return boolean
	 * @param string $name Term name - spring/summer/fall/etc
	 * @param int $year Term year - YYYY
	 * @desc If possible, sets this object to the term matching name/year and returns true; otherwise returns false;
	 */
	public function getTermByName($name, $year) {
		global $g_dbConn;
		
		if(empty($name) || empty($year)) {
			return false;
		}
		
		$sql = "SELECT term_id FROM terms WHERE term_name = '$name' AND term_year = $year LIMIT 1";
		
		$rs = $g_dbConn->getOne($sql);
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }
		
		if(empty($rs)) {
			return false;
		}
		else {
			$this->getTermByID($rs);
			return true;
		}
	}
	

	function getTermID() { return $this->term_id; }
	function getSortOrder() { return $this->sort_order; }
	function getTerm() { return $this->term_name . " " . $this->term_year; }
	function getTermName() { return $this->term_name; }
	function getTermYear() { return $this->term_year; }
	function getBeginDate() { return $this->begin_date; }
	function getEndDate() { return $this->end_date; }
	
	function update($name, $year, $begin, $end, $sort)
	{
		global $g_dbConn;
		$sql = "UPDATE terms SET term_name = ?, term_year = ?, begin_date = ?, end_date = ?, sort_order = ? WHERE term_id = ?";


		$rs = $g_dbConn->query($sql, array($name, $year, $begin, $end, $sort, $this->getTermID()));		
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }

		return true;		
	}
	
	
}
