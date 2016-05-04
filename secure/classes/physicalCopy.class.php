<?php
/*******************************************************************************
physicalCopy.class.php
User Primitive Object

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

class physicalCopy
{
	//Attributes
	public $physicalCopyID;
	public $reserveID;
	public $itemID;
	public $status;
	public $callNumber;
	public $barcode;
	public $owningLibrary;
	public $itemType;
	public $ownerUserID;


	/**
	* Constructor Method
	* @return void
	* @param optional int $physicalCopyID
	* @desc If physicalCopyID=NULL, call createPhysicalCopy to insert new record in the DB
	* @desc If physicalCopy not NULL, call getCopyByID to set object attributes w/values from the DB
	*/
	function physicalCopy($physicalCopyID=NULL)
	{
		if (!is_null($physicalCopyID)){
			$this->getCopyByID($physicalCopyID);
		}
	}

	/**
	* @return int physicalCopyID
	* @desc Insert new physical copy record into the DB and return the new physicalCopyID
	*/
	function createPhysicalCopy()
	{
		global $g_dbConn;
		$sql = "INSERT INTO physical_copies (reserve_id) VALUES (0)";
		$sql2 = "SELECT LAST_INSERT_ID() FROM physical_copies";

		$rs = $g_dbConn->query($sql); //insert new row into PHSICAL_COPIES table
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }

		$rs2 = $g_dbConn->query($sql2);
		if (Rd_Pdo_PearAdapter::isError($rs2)) { trigger_error($rs2->getMessage(), E_USER_ERROR); }

		$row = $rs2->fetch(PDO::FETCH_NUM);		//retrieve the row just inserted into the PHYSICAL_COPIES table
		if (Rd_Pdo_PearAdapter::isError($row)) { trigger_error($row->getMessage(), E_USER_ERROR); }

		$this->physicalCopyID = $row[0]; //return physical_copy_id of newly created record
	}


	/**
	* @return physicalCopyID on success or null if no item found
	* @param string $itemBarcode
	* @desc searches for an physItem based on barcode and populates object
	*/
	function getByBarcode($itemBarcode) {
		global $g_dbConn;

		$sql = "SELECT item_id FROM physical_copies WHERE barcode = ?";

		//query db
		$rs = $g_dbConn->query($sql, $itemBarcode);
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }

		//check to see if item found
		if( ($row = $rs->fetch(PDO::FETCH_NUM)) != null ) {
			$this->getByItemID($row[0]);
			return $this->physicalCopyID;
		}
		else {
			return null;
		}
	}



	function getByItemID($itemID)
	{
		global $g_dbConn;

		$sql = "SELECT physical_copy_id, reserve_id, item_id, status, call_number, barcode, owning_library, item_type, owner_user_id "
					.  "FROM physical_copies "
					.  "WHERE item_id = ? ORDER BY physical_copy_id DESC";

		$rs = $g_dbConn->query($sql, $itemID);
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }

		if ($row = $rs->fetch(PDO::FETCH_NUM))
		{
			list($this->physicalCopyID, $this->reserveID, $this->itemID, $this->status, $this->callNumber, $this->barcode, $this->owningLibrary, $this->itemType, $this->ownerUserID) = $row;
			return true;
		} else
			return false;
	}



	/**
	* @return void
	* @param int $reserveID
	* @desc Updates the reserveID, associated w/the physicalCopy, in the DB
	*/
	function setReserveID($reserveID)
	{
		global $g_dbConn;

		$this->reserveID = $reserveID;
		$sql = "UPDATE physical_copies SET reserve_id = ? WHERE physical_copy_id = ?";

		$rs = $g_dbConn->query($sql, array($reserveID, $this->physicalCopyID));
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }
	}

	/**
	* @return void
	* @param int $itemID
	* @desc Updates the itemID, associated w/the physicalCopy, in the DB
	*/
	function setItemID($itemID)
	{
		global $g_dbConn;

		$this->itemID = $itemID;
		$sql = "UPDATE physical_copies SET item_id = ? WHERE physical_copy_id = ?";

		$rs = $g_dbConn->query($sql, array($itemID, $this->physicalCopyID));
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }
	}

	/**
	* @return void
	* @param string $status
	* @desc Updates the status, associated w/the physicalCopy, in the DB
	*/
	function setStatus($status)
	{
		global $g_dbConn;

		$this->status = $status;
		$sql = "UPDATE physical_copies SET status = ? WHERE physical_copy_id = ?";

		$rs = $g_dbConn->query($sql, array($status, $this->physicalCopyID));
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }
	}

	/**
	* @return void
	* @param string $callNumber
	* @desc Updates the callNumber, associated w/the physicalCopy, in the DB
	*/
	function setCallNumber($callNumber)
	{
		global $g_dbConn;

		$this->callNumber = $callNumber;
		$sql = "UPDATE physical_copies SET call_number = ? WHERE physical_copy_id = ?";

		$rs = $g_dbConn->query($sql, array($callNumber, $this->physicalCopyID));
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }
	}

	/**
	* @return void
	* @param string $barcode
	* @desc Updates the barcode, associated w/the physicalCopy, in the DB
	*/
	function setBarcode($barcode)
	{
		global $g_dbConn;

		$this->barcode = $barcode;
		$sql = "UPDATE physical_copies SET barcode = ? WHERE physical_copy_id = ?";

		$rs = $g_dbConn->query($sql, array($barcode, $this->physicalCopyID));
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }
	}

	/**
	* @return void
	* @param int $owningLibrary
	* @desc Updates the Owning Library, associated w/the physicalCopy, in the DB
	*/
	function setOwningLibrary($owningLibrary)
	{
		global $g_dbConn;

		$this->owningLibrary = $owningLibrary;
		$sql = "UPDATE physical_copies SET owning_library = ? WHERE physical_copy_id = ?";

		$rs = $g_dbConn->query($sql, array($owningLibrary, $this->physicalCopyID));
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }
	}

	/**
	* @return void
	* @param string $itemType
	* @desc Updates the item type, associated w/the physicalCopy, in the DB
	*/
	function setItemType($itemType)
	{
		global $g_dbConn;

		$this->itemType = $itemType;
		$sql = "UPDATE physical_copies SET item_type = ? WHERE physical_copy_id = ?";

		$rs = $g_dbConn->query($sql, array($itemType, $this->physicalCopyID));
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }
	}

	/**
	* @return void
	* @param int $ownerUserID
	* @desc Updates the owner_user_id value, associated w/the physicalCopy, in the DB
	*/
	function setOwnerUserID($ownerUserID)
	{
		global $g_dbConn;

		$this->ownerUserID = $ownerUserID;
		$sql = "UPDATE physical_copies SET owner_user_id = ? WHERE physical_copy_id = ?";

		$rs = $g_dbConn->query($sql, array($ownerUserID, $this->physicalCopyID));
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }
	}

	function getPhysicalCopyID() { return $this->physicalCopyID; }
	function getReserveID() { return $this->reserveID; }
	function getItemID() { return $this->itemID; }
	function getStatus() { return $this->status; }
	function getCallNumber() { return $this->callNumber; }
	function getBarcode() { return $this->barcode; }
	function getOwningLibrary() { return $this->owningLibrary; }
	function getItemType() { return $this->itemType; }
	function getOwnerUserID() { return $this->ownerUserID; }


	/**
	* @return void
	* @param int $physicalCopyID
	* @desc Gets physical copies record from DB by physicalCopyID
	*/
	function getCopyByID($physicalCopyID)
	{
		global $g_dbConn;

		$sql = "SELECT physical_copy_id, reserve_id, item_id, status, call_number, barcode, owning_library, item_type, owner_user_id "
			.  "FROM physical_copies "
			.  "WHERE physical_copy_id = ?";

		$rs = $g_dbConn->query($sql, $physicalCopyID);
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }

		list($this->physicalCopyID, $this->reserveID, $this->itemID, $this->status, $this->callNumber, $this->barcode, $this->owningLibrary, $this->itemType, $this->ownerUserID) = $rs->fetch();
	}

	/**
	* @return void
	* @desc destroy the database entry
	*/
	function destroy()
	{
		global $g_dbConn;

		$sql = "DELETE "
			.  "FROM physical_copies "
			.  "WHERE physical_copy_id = ?";

		$rs = $g_dbConn->query($sql, $this->physicalCopyID);
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }
	}
}
