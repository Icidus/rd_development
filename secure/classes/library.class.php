<?php
/*******************************************************************************
library.class.php
Library Primitive Object

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
class library
{
	//Attributes
	public $libraryID;
	public $library;
	public $libraryNickname;
	public $ilsPrefix;
	public $reserveDesk;
	public $libraryURL;
	public $contactEmail;
	private $monograph_library_id;
	private $multimedia_library_id;

	function library($libraryID = NULL)
	{

		if (!is_null($libraryID))
		{
			$this->$libraryID = $libraryID;
	
			$sql  = "SELECT l.library_id AS id, l.name AS name, "
				. "l.nickname AS nickname, l.ils_prefix AS ilsPrefix, "
				. "l.reserve_desk AS reserveDesk, l.url AS url, "
				. "l.contact_email AS contactEmail, "
				. "l.monograph_library_id AS monographLibraryId, "
				. "l.multimedia_library_id AS multimediaLibraryId "
				. "FROM libraries as l "
				. "WHERE l.library_id = ?";
	
			$rs = Rd_Pdo::prepareExecute($sql, $libraryID);
			if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }
			$libraryData = $rs->fetch();
			$this->libraryID = $libraryData['id'];
			$this->library = $libraryData['name'];
			$this->libraryNickname = $libraryData['nickname'];
			$this->ilsPrefix = $libraryData['ilsPrefix'];
			$this->reserveDesk = $libraryData['reserveDesk'];
			$this->libraryURL = $libraryData['url'];
			$this->contactEmail= $libraryData['contactEmail'];
			$this->monograph_library_id = $libraryData['monographLibraryId'];
			$this->multimedia_library_id = $libraryData['multimediaLibraryId'];
		}
	}
	
	function createNew($name, $nickname, $ils_prefix, $reserveDesk, $url, $contactEmail, $monograph_library_id, $multimedia_library_id)
	{
		global $g_dbConn;
			
		$sql  = "INSERT INTO libraries (name, nickname, ils_prefix, reserve_desk, url, contact_email, monograph_library_id, multimedia_library_id) VALUES (?,?,?,?,?,?,?,?)";
		
		$rs = $g_dbConn->query($sql, array($name, $nickname, $ils_prefix, $reserveDesk, $url, $contactEmail, $monograph_library_id, $multimedia_library_id));				
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }
		
		$id = $g_dbConn->getOne("SELECT LAST_INSERT_ID() FROM libraries");
		if (Rd_Pdo_PearAdapter::isError($id)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }

		$this->library($id);

		if (!is_numeric($monograph_library_id) || !is_numeric($multimedia_library_id == 'null'))
		{
			//null Processing Libraries should be self referencial 
			$this->setMonograph_library_id($monograph_library_id);
			$this->setMultimedia_library_id($multimedia_library_id);	
			
			$this->update();			
		}
	}

	function update()
	{
		global $g_dbConn;
		
		$sql  = "UPDATE libraries set name = ?, nickname = ?, ils_prefix =?, reserve_desk = ?, url = ?, contact_email = ?, monograph_library_id = ?, multimedia_library_id = ? WHERE library_id = ?";

		$rs = $g_dbConn->query($sql, array(
										$this->getLibrary(), 
										$this->getLibraryNickname(), 
										$this->getILS_prefix(), 
										$this->getReserveDesk(), 
										$this->getLibraryURL(),
										$this->getContactEmail(),  
										$this->getMonograph_library_id(), 
										$this->getMultimedia_library_id(), 
										$this->getLibraryID()
									));
									
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }
	}	

	function getLibraryID() { return $this->libraryID; }
	function getLibrary() { return $this->library; }
	function getLibraryNickname() { return $this->libraryNickname; }
	function getILS_prefix() { return $this->ilsPrefix; }
	function getReserveDesk() { return $this->reserveDesk; }
	function getLibraryURL() { return $this->libraryURL; }
	function getContactEmail() { return $this->contactEmail; }
	function getMonograph_library_id() { return $this->monograph_library_id; }
	function getMultimedia_library_id() { return $this->multimedia_library_id; }
	
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
		
	function setLibrary($name) { $this->library = stripslashes($name); }
	function setLibraryNickname($nickname) { $this->libraryNickname = stripslashes($nickname); }
	function setILS_prefix($prefix) { $this->ilsPrefix = stripslashes($prefix); }
	function setReserveDesk($desk) { $this->reserveDesk = stripslashes($desk); }
	function setLibraryURL($url) { $this->libraryURL = stripslashes($url); }
	function setContactEmail($email) { $this->contactEmail = $email; }	
	
	
	function setMonograph_library_id($library_id) 
	{ 
		$this->monograph_library_id   = (
			!is_numeric($library_id)) 
			? $this->getLibraryID() 
			: $library_id; 
	}
	
	function setMultimedia_library_id($library_id) { 
		$this->multimedia_library_id = (
			!is_numeric($library_id)) 
			? $this->getLibraryID() : 
			$library_id; 
	}	
	
	static function getDefaultLibrary(){
		$defaultId = 1;
		return new library($defaultId);
	}
}