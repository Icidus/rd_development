<?php
/*******************************************************************************
copyright.class.php
Manipulates copyright data

Created by Dmitriy Panteleyev (dpantel@emory.edu)
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
require_once(APPLICATION_PATH . '/classes/notes.class.php');

class Copyright extends Notes {
	
	/**
	 * Declaration
	 */
	//copyright
	protected $id;
	protected $itemID;
	protected $status;
	protected $statusBasisID;
	protected $contactID;
	//copyright_status_bases
	protected $statusBasis;
	protected $statusAllBases;
	//copyright_contacts
	protected $contact;
	//copyright_files
	protected $supportingItems;
	//copyright_log
	protected $logs;
	
	
	/**
	 * @return void
	 * @param int $item_id (optional) ID of item
	 * @desc Constructs object; Initializes to copyright record, if passed an item id
	 */	
	public function __construct($item_id=null) {
		if(!empty($item_id)) {
			$this->getByItemID($item_id);
		}
	}
	
	
	/**
	 * @return boolean
	 * @param int $item_id (optional) ID of item
	 * @desc Fetches copyright record data from DB and sets up the object; Returns true on succes, false otherwise
	 */
	public function getByItemID($item_id) {
		global $g_dbConn, $g_notetype;
		
		//filter empty ID
		if(empty($item_id)) {
			return false;
		}
		
		$sql = "SELECT copyright_id, item_id, status, status_basis_id, contact_id FROM copyright WHERE item_id = {$item_id}";
		$rs = $g_dbConn->query($sql);
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }
		
		if($rs->rowCount() > 0) {
			list($this->id, $this->itemID, $this->status, $this->statusBasisID, $this->contactID) = $rs->fetch(PDO::FETCH_NUM);
						
			//get the notes
			$this->setupNotes('copyright', $this->id, $g_notetype['copyright']);
			$this->fetchNotesByType();
		
			return true;
		}
		else {
			return false;
		}
	}
	
	
	/**
	 * @return boolean
	 * @param int $item_id ID of item to which this copyright record is tied
	 * @param string $status Status of copyright (NEW/PENDING/APPROVED/DENIED)
	 * @param int $status_basis_id ID of the basis for the status (reason); applies only to APPROVED/DENIED status
	 * @desc Creates new DB record and initializes object to it.
	 */
	public function createNewRecord($item_id, $status=null, $status_basis_id=null) {
		global $g_dbConn;
		
		//filter passed data
		if(empty($item_id)) {
			return false;	//require item id to create new record
		}

		$sql_check = "SELECT copyright_id FROM copyright WHERE item_id = $item_id";
		$sql_insert = "INSERT INTO copyright (item_id) VALUES ($item_id)";
		$sql_inserted_id = "SELECT LAST_INSERT_ID() FROM copyright";
		
		//check if record for item already exists
		$rs = $g_dbConn->query($sql_check);
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }
		
		if($rs->rowCount() > 0) {
			return false;
		}

		//no record, create new
		$rs = $g_dbConn->query($sql_insert);
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }
		
		//get the id
		$rs = $g_dbConn->getOne($sql_inserted_id);
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }
		
		//initialize this object to the ID
		$this->id = $rs;
		
		//add log
		$this->log('create record', 'copyright record created');
		
		//add status info
		$this->status = '';
		$this->setStatus($status, $status_basis_id);
		
		return true;
	}
	
	
	/**
	 * @return boolean
	 * @param string $status New status of copyright (NEW/PENDING/APPROVED/DENIED)
	 * @param int $status_basis_id ID of the basis for the status (reason); applies only to APPROVED/DENIED status
	 * @desc sets new status
	 */
	public function setStatus($status, $status_basis_id=null) {
		global $g_dbConn;
		
		//filter data
		if(empty($status)) {
			return false;
		}
		if(empty($status_basis_id)) {
			$status_basis_id = 'NULL';
		}
		
		$sql = "UPDATE copyright SET status = ?, status_basis_id = $status_basis_id WHERE copyright_id = {$this->id}";
		
		$rs = $g_dbConn->query($sql, array($status));
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }
		
		//update the object values
		$old_status = $this->status; //need this for log
		$this->status = $status;
		$this->statusBasisID = $status_basis_id;
		$this->statusBasis = null;
		$this->getStatusBasis();
		
		//log status change
		$log_details = $old_status.' >> '.$status;
		//add status basis, if provided
		if(!empty($this->statusBasis)) {
			$log_details .= " ({$this->statusBasis})";
		}
		$this->log('update status', $log_details);
		
		return true;
	}
	
	
	/**
	 * @return mixed
	 * @param string $status The status category for this basis (APPROVED/DENIED)
	 * @param string $new_basis The text for the new basis
	 * @desc creates and new status basis; returns new basis ID on success, false on failure
	 */
	public function createNewStatusBasis($status, $new_basis) {
		global $g_dbConn;
		
		if(empty($status) || empty($new_basis)) {
			return false;
		}
		
		$sql_insert = "INSERT INTO copyright_status_bases (status_type, status_basis) VALUES (?, ?)";
		$sql_inserted_id = "SELECT LAST_INSERT_ID() FROM copyright_status_bases";				

		
		//create new record
		$rs = $g_dbConn->query($sql_insert, array($status, $new_basis));
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }
		
		//get the id
		$rs = $g_dbConn->getOne($sql_inserted_id);
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }
		
		//return id on success, false on failure
		return !empty($rs) ? $rs : false;
	}
	
	
	
	/**
	 * @return multi-dimensional array
	 * @param string $status (optional) Status type to filter by
	 * @desc Returns array indexed by status type, holding arrays of bases, indexed by basis_ids. If valid status is passed, only the sub-array matching the status will be returned.
	 
 		The idea is to build a multidimensional array:
		
		array(
			'approved' => array(
				4 => 'Fair Use',
				8 => 'Permission'
			),
			'denied' => array(
				7 => 'License Violation',
				2 => 'Other'
			)
		)
	 */
	public function getAllStatusBases($status=null) {
		global $g_dbConn;
		
		if(empty($this->statusAllBases)) {
			$sql = "SELECT status_basis_id, status_type, status_basis "
				. "FROM copyright_status_bases "
				. "ORDER BY status_basis ASC";
			$rs = $g_dbConn->query($sql);
			if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }
			
			$this->statusAllBases = array();
			while($row = $rs->fetch(PDO::FETCH_NUM)) {
				if(!array_key_exists($row[1], $this->statusAllBases)) {
					$this->statusAllBases[$row[1]] = array();
				}
				$this->statusAllBases[$row[1]][$row[0]] = $row[2];
			}
		}
		
		//return results
		if(!empty($status)) {	//if user requesting bases for a specific status
			//and this is a valid status
			if(array_key_exists($status, $this->statusAllBases)) {
				//return it
				return $this->statusAllBases[$status];
			}
			else {
				return array();
			}
		}
		else {	//return all bases
			return $this->statusAllBases;
		}
	}
	

	/**
	 * @return boolean
	 * @param int $contact_id ID of contact to attach to this copyright record
	 * @desc sets the contact for this copyright record
	 */
	public function setContact($contact_id) {
		global $g_dbConn;
		
		if(empty($contact_id)) {
			return false;
		}
		
		$sql = "UPDATE copyright SET contact_id = $contact_id WHERE copyright_id = {$this->id}";
		$rs = $g_dbConn->query($sql);
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }
		
		//update contact info
		$this->contactID = $contact_id;
		$this->contact = null;
		$this->getContact();
		
		//log
		$this->log('update contact', '>> '.$this->contact['org_name']);		
		
		return true;
	}
	
	
	/**
	 * @return boolean
	 * @param string $org_name Organization name
	 * @param string $contact_name Primary contact name
	 * @param string $address Physical address
	 * @param string $phone	Phone number
	 * @param string $email Email address
	 * @param string $www WWW address
	 * @param int $contact_id (optional) ID of contact record
	 * @desc If passed an ID, updates contact record, else creates a new one
	 */
	public function saveContact($org_name, $contact_name, $address, $phone, $email, $www, $contact_id=null) {
		global $g_dbConn;
		
		//require organization name
		if(empty($org_name)) {
			return false;
		}
		
		$sql_insert = "INSERT INTO copyright_contacts (org_name, contact_name, address, phone, email, www) VALUES (?,?,?,?,?,?)";
		$sql_inserted_id = "SELECT LAST_INSERT_ID() FROM copyright_status_bases";	
		$sql_update = "UPDATE copyright_contacts SET org_name=?, contact_name=?, address=?, phone=?, email=?, www=? WHERE contact_id=$contact_id";				

		
		if(!empty($contact_id)) {
			//update
			$rs = $g_dbConn->query($sql_update, array($org_name, $contact_name, $address, $phone, $email, $www));
			if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }
			
			return true;			
		}
		else {
			//create new record
			$rs = $g_dbConn->query($sql_insert, array($org_name, $contact_name, $address, $phone, $email, $www));
			if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }
			
			//get the id
			$rs = $g_dbConn->getOne($sql_inserted_id);
			if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }
			
			//return id on success, false on failure
			return !empty($rs) ? $rs : false;	
		}	
	}
	
	
	/**
	 * @return boolean
	 * @param int $supporting_item_id ID of supporting item
	 * @desc Adds supporting item to this copyright record
	 */
	public function addSupportingItem($supporting_item_id) {
		global $g_dbConn;
		
		if(empty($supporting_item_id)) {
			return false;
		}
		
		$sql_insert = "INSERT INTO copyright_files (copyright_id, item_id) VALUES ({$this->id}, $supporting_item_id)";
		$sql_inserted_id = "SELECT LAST_INSERT_ID() FROM copyright_status_bases";				
		
		//create new record
		$rs = $g_dbConn->query($sql_insert);
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }
		
		//get the id
		$rs = $g_dbConn->getOne($sql_inserted_id);
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }
		
		//log
		$this->log('add file', '#'.$supporting_item_id);
		
		//return id on success, false on failure
		return !empty($rs) ? $rs : false;		
	}
	
	
	/**
	 * @return boolean
	 * @param int $supporting_item_id ID of supporting item
	 * @desc deletes supporting item from this copyright record
	 */
	public function deleteSupportingItem($supporting_item_id) {
		global $g_dbConn;
		
		if(empty($supporting_item_id)) {
			return false;
		}
		
		$sql = "DELETE FROM copyright_files WHERE copyright_id = {$this->id} AND item_id = $supporting_item_id";		
		
		$rs = $g_dbConn->query($sql);
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }
		
		//log
		$this->log('delete file', '#'.$supporting_item_id);
		
		return true;
	}
		
	
	/**
	 * @return array of (assoc) arrays
	 * @param string $query
	 * @desc returns array or assoc arrays of contacts, where argument maches all or part of org name;
	 */
	public function findContacts($query) {
		global $g_dbConn;
		
		//wrap query in sql *
		$query = '%'.$query.'%';
		
		$sql = "SELECT contact_id, org_name, contact_name, address, phone, email, www FROM copyright_contacts WHERE org_name LIKE ?";
		
		$rs = $g_dbConn->query($sql, array($query));
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }
			
		$contacts = array();
		while($row = $rs->fetch(PDO::FETCH_ASSOC)) {
			$contacts[] = $row;
		}
		
		return $contacts;
	}		
	
	
	public function getStatus() {
		return $this->status;
	}
	public function getStatusBasisID() {
		return $this->statusBasisID;
	}
	public function getID() {
		return $this->id;
	}
	public function getItemID() {
		return $this->itemID;
	}
	public function getStatusBasis() {
		global $g_dbConn;
		
		if(empty($this->statusBasis)) {
			if(empty($this->statusBasisID)) {
				return null;
			}
			
			$sql = "SELECT status_basis FROM copyright_status_bases WHERE status_basis_id = {$this->statusBasisID}";

			$rs = $g_dbConn->getOne($sql);
			if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }
			
			$this->statusBasis = $rs;
		}
		
		return $this->statusBasis;
	}
	
	/**
	 * @return return array (assoc)
	 * @param int $contact_id (optional) fetch info for an arbitrary contact record; if left empty, will fetch contact info for this copyright record's contact
	 * @desc Returns contact info as an associative array
	 */
	public function getContact($contact_id=null) {
		global $g_dbConn;
		
		//query DB if no info for this contact or if fetching arbitrary contact
		if(empty($this->contact) || !empty($contact_id)) {
			$contact_to_fetch = !empty($contact_id) ? $contact_id : $this->contactID;
			
			if(empty($contact_to_fetch)) {
				return null;
			}
			
			$sql = "SELECT contact_id, org_name, contact_name, address, phone, email, www FROM copyright_contacts WHERE contact_id = $contact_to_fetch";
			$rs = $g_dbConn->query($sql);
			if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }
			
			//if fetching arbitrary contact info, just return it
			if(!empty($contact_id)) {
				return $rs->fetch(PDO::FETCH_ASSOC);
			}
			else {	//fetching this record's contact, so save it
				$this->contact = $rs->fetch(PDO::FETCH_ASSOC);
			}
		}
		
		return $this->contact;
	}
	
	/**
	 * @return array of reserveItem objects
	 * @desc returns supporting files as reserveItem objects
	 */
	public function getSupportingItems() {
		global $g_dbConn;
		
		if(empty($this->supportingItems)) {
			$sql = "SELECT item_id FROM copyright_files WHERE copyright_id = {$this->id}";
			$rs = $g_dbConn->query($sql);
			if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }
			
			$this->supportingItems = array();
			while($row = $rs->fetch(PDO::FETCH_NUM)) {
				$this->supportingItems[$row[0]] = new reserveItem($row[0]);
			}
		}
		
		return $this->supportingItems;
	}
	
	
	/**
	 * @return array of arrays
	 * @desc Returns array of assoc arrays; each log entry has the following indeces: u_tstamp, user_id, action, details
	 */
	public function getLogs() {
		global $g_dbConn;
		
		if(empty($this->logs)) {		
			$sql = "SELECT UNIX_TIMESTAMP(tstamp) as u_tstamp, l.user_id, u.username, l.action, l.details "
				. "FROM copyright_log AS l "
				. "JOIN users AS u ON l.user_id = u.user_id "
				. "WHERE copyright_id = {$this->id} "
				. "ORDER BY tstamp ASC";
			$rs = $g_dbConn->query($sql);
			if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }
			
			$this->logs = array();
			while($row = $rs->fetch(PDO::FETCH_ASSOC)) {
				$this->logs[] = $row;					
			}
		}
		
		return $this->logs;
	}
	
	
	/**
	 * @return void
	 * @param string $action Action performed
	 * @param string $details Details about the action
	 * @desc Logs changes to the copyright record
	 */
	public function log($action, $details=null) {
		global $g_dbConn, $u;
		
		$user_id = $u->getUserID();		
		if(empty($action) || empty($user_id)) {
			return false;
		}
		$action = trim($action);
		$details = trim(htmlspecialchars($details));
		
		$sql = "INSERT INTO copyright_log (copyright_id, tstamp, user_id, action, details) "
			. "VALUES ({$this->id}, NOW(), {$u->getUserID()}, ?, ?)";
		$rs = $g_dbConn->query($sql, array($action, $details));
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }
	}
}
?>
