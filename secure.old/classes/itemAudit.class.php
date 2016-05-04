<?php
/*******************************************************************************
itemAudit.class.php
itemAudit Primitive Object

Created by Kathy Washington (kawashi@emory.edu)
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

class itemAudit
{
	//Attributes
	var $auditID;
	var $itemID;
	var $dateAdded;
	var $addedBy;

	function itemAudit($itemID=NULL)
	{
		if (!is_null($itemID)){
			$this->getItemAuditByItemID($itemID);
		}
	}

	/**
	* @return int reserveID
	* @desc create new item_audit record in database
	*/
	function createNewItemAudit($itemID, $addedBy)
	{
		global $g_dbConn;

		$sql = "INSERT INTO electronic_item_audit (item_id, date_added, added_by) VALUES (?, ?, ?)";
		$sql2 = "SELECT LAST_INSERT_ID() FROM electronic_item_audit";

		$d = date("Y-m-d"); //get current date


		$rs = $g_dbConn->query($sql, array($itemID, $d, $addedBy));
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }

		$rs = $g_dbConn->query($sql2);
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }

		$row = $rs->fetch(PDO::FETCH_NUM);
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }

		$this->auditID = $row[0];
		$this->itemID = $itemID;
		$this->dateAdded = $d;
		$this->addedBy = $addedBy;
	}

	/**
	* @return void
	* @param int $itemID
	* @desc get itemAudit info from the database
	*/
	function getItemAuditByItemID($itemID)
	{
		global $g_dbConn;

		$sql = "SELECT audit_id, item_id, date_added, added_by "
			.  "FROM electronic_item_audit "
			.  "WHERE item_id = ?";

		$rs = $g_dbConn->query($sql, $itemID);
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }

		$row = $rs->fetch(PDO::FETCH_NUM);

		$this->auditID		= $row[0];
		$this->itemID		= $row[1];
		$this->dateAdded	= $row[2];
		$this->addedBy		= $row[3];
	}

	function getAuditID() { return $this->auditID; }
	function getItemID() { return $this->itemID; }
	function getDateAdded() { return $this->dateAdded; }
	function getAddedBy() { return htmlentities(stripslashes($this->addedBy)); }
}
