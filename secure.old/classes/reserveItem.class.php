<?php
/*******************************************************************************
reserveItem.class.php
ReserveItem Primitive Object

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
require_once(APPLICATION_PATH . '/classes/item.class.php');
require_once(APPLICATION_PATH . '/classes/physicalCopy.class.php');
require_once(APPLICATION_PATH . '/classes/user.class.php');

require_once(APPLICATION_PATH . '/lib/Rd/Pdo.php');

class reserveItem extends item
{
	//Attributes
	protected $_reserveId;
	public $author;
	public $source;
	public $volumeTitle;
	public $volumeEdition;
	public $pagesTimes;
	public $performer;
	public $localControlKey;
	public $URL;
	public $mimeTypeID;
	public $homeLibraryID;
	public $privateUserID;
	public $privateUser;
	public $physicalCopy;
	public $itemIcon;
	public $status;
	
	private $ISSN;
	private $ISBN;
	private $OCLC;
	
	private $temp_call_num;
	
	function reserveItem($itemId = null)
	{
		if (!is_null($itemId)){
			if (!is_numeric($itemId)){
				throw new Exception('Attempting to retrieve an item by non-numeric ID');
			}
			$this->getItemByID($itemId);
		}
	}


	/**
	* @return void
	* @param int $itemID
	* @desc get item info from the database. This is meant to replace the parent method.
	*/
	function getItemByID($itemID)
	{
		global $g_dbConn;
		
		if(empty($itemID)) {
			return false;	//no ID	
		}

		$sql = "SELECT item_id, title, item_group, 
						last_modified, creation_date, item_type, 
						author, source, volume_edition, pages_times, 
						performer, local_control_key, url, mimetype, 
						home_library, private_user_id, volume_title, 
						item_icon, ISBN, ISSN, OCLC, status, 
						temp_call_num
						FROM items
						WHERE item_id = ?";
		
		$rs = $g_dbConn->getRow($sql, array($itemID));
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }
		
		if(empty($rs)) {
			return false;
		}
		else {
			//pull the info
			list($this->itemID, $this->title, $this->itemGroup, $this->lastModDate, $this->creationDate, 
				$this->itemType, $this->author, $this->source, $this->volumeEdition, $this->pagesTimes, 
				$this->performer, $this->localControlKey, $this->URL, $this->mimeTypeID, 
				$this->homeLibraryID, $this->privateUserID, $this->volumeTitle, $this->itemIcon, 
				$this->ISBN, $this->ISSN, $this->OCLC, $this->status, $this->temp_call_num) 
			= $rs;
				
			//get the notes
			$this->setupNotes('items', $this->itemID);
			$this->fetchNotes();
			
			return true;
		}
	}
	
	/**
	* @return void
	* @desc destroy the database entry and file if one exists
	* @param boolean if true will destroy even if attached to a class
	*/
	function destroy($override = false)
	{
		global $g_dbConn;

		$sql = "SELECT count(*) FROM reserves WHERE item_id = ?";	
		
		if(!empty($this->itemID)) {			
			//attempt to use transactions
			Rd_Pdo::beginTransaction();
			
			try {						
				$reserveCnt = $g_dbConn->getOne($sql, array($this->itemID));
				
				if ($reserveCnt == 0 || $override)
				{
					if ($this->isLocalFile())
					{
						unlink($this->URL);
					}
					
					//parent::destroy();
				}
			} catch (Exception $e) {
				Rd_Pdo::rollback();
				trigger_error($reserveCnt->getMessage(), E_USER_ERROR);
			}
			
			//commit this set
			Rd_Pdo::commit();	
		}
	}

	/**
	* @return boolean
	* @param string localControl
	* @desc get item info from the database by localcontrolkey; return TRUE on success, FALSE otherwise
	*/
	function getItemByLocalControl($local_control_key)
	{
		if(empty($local_control_key))
			return false;	//no key
		
		$sql = "SELECT item_id FROM items WHERE local_control_key = ?";
		
		//query to get item ID
		$result = Rd_Pdo_PearAdapter::getOne($sql, $local_control_key);
		if (Rd_Pdo_PearAdapter::isError($result)) { 
			trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($result), E_USER_ERROR); 
		}
				
		//get item by ID
		return $this->getItemByID($result);
	}
	
	/**
	 * This is called _specify_ instead of set because of the muddled
	 * way RD DAOs were designed... this doesn't set anything in the
	 * DB, but rather populates a run-time value that isn't included 
	 * in instantiation. 
	 * @param int $id
	 */
	function specifyReserveId($id){
		$this->_reserveId = $id;
	}

	/**
	* @return void
	* @param string $author
	* @desc set new author in database
	*/
	function setAuthor($author)
	{
		global $g_dbConn;

		$sql = "UPDATE items SET author = ?, last_modified = ? WHERE item_id = ?";
		$d = date("Y-m-d"); //get current date

		$rs = $g_dbConn->query($sql, array(stripslashes($author), $d, $this->itemID));
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }

		$this->author = $author;
		$this->lastModDate = $d;
	}

	/**
	* @return void
	* @param string $source
	* @desc set new source in database
	*/
	function setSource($source)
	{
		global $g_dbConn;

		$sql = "UPDATE items SET source = ?, last_modified = ? WHERE item_id = ?";
		$d = date("Y-m-d"); //get current date

		$rs = $g_dbConn->query($sql, array(stripslashes($source), $d, $this->itemID));
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }

		$this->source = $source;
		$this->lastModDate = $d;
	}

	/**
	* @return void
	* @param string $volumeTitle
	* @desc set new volumeTitle in database
	*/
	function setVolumeTitle($volumeTitle)
	{
		global $g_dbConn;

		$sql = "UPDATE items SET volume_title = ?, last_modified = ? WHERE item_id = ?";
		$d = date("Y-m-d"); //get current date

		$rs = $g_dbConn->query($sql, array(stripslashes($volumeTitle), $d, $this->itemID));
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }

		$this->volumeTitle = $volumeTitle;
		$this->lastModDate = $d;
	}


	/**
	* @return void
	* @param string $volumeEdition
	* @desc set new volumeEdition in database
	*/
	function setVolumeEdition($volumeEdition)
	{
		global $g_dbConn;

		$sql = "UPDATE items SET volume_edition = ?, last_modified = ? WHERE item_id = ?";
		$d = date("Y-m-d"); //get current date

		$rs = $g_dbConn->query($sql, array(stripslashes($volumeEdition), $d, $this->itemID));
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }

		$this->volumeEdition = $volumeEdition;
		$this->lastModDate = $d;
	}

	/**
	* @return void
	* @param string $pagesTimes
	* @desc set new pagesTimes in database
	*/
	function setPagesTimes($pagesTimes)
	{
		global $g_dbConn;

		$sql = "UPDATE items SET pages_times = ?, last_modified = ? WHERE item_id = ?";
		$d = date("Y-m-d"); //get current date
		
		$rs = $g_dbConn->query($sql, array(stripslashes($pagesTimes), $d, $this->itemID));
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }

		$this->pagesTimes = $pagesTimes;
		$this->lastModDate = $d;
	}

	/**
	* @return void
	* @param string $performer
	* @desc set new performer in database
	*/
	function setPerformer($performer)
	{
		global $g_dbConn;

		$sql = "UPDATE items SET performer = ?, last_modified = ? WHERE item_id = ?";
		$d = date("Y-m-d"); //get current date

		$rs = $g_dbConn->query($sql, array(stripslashes($performer), $d, $this->itemID));
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }

		$this->performer = $performer;
		$this->lastModDate = $d;
	}

	/**
	* @return void
	* @param string $localControlKey
	* @desc set new localControlKey in database
	*/
	function setLocalControlKey($localControlKey)
	{
		global $g_dbConn;

		$sql = "UPDATE items SET local_control_key = ?, last_modified = ? WHERE item_id = ?";
		$d = date("Y-m-d"); //get current date

		$rs = $g_dbConn->query($sql, array($localControlKey, $d, $this->itemID));
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }

		$this->localControlKey = $localControlKey;
		$this->lastModDate = $d;
	}

	/**
	* @return void
	* @param string $URL
	* @desc set new URL in database
	*/
	function setURL($URL)
	{
		global $g_dbConn;

		$sql = "UPDATE items SET url = ?, last_modified = ? WHERE item_id = ?";
		$d = date("Y-m-d"); //get current date
		
		$rs = $g_dbConn->query($sql, array($URL, $d, $this->itemID));
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }

		$this->URL = $URL;
		$this->lastModDate = $d;
	}

	/**
	* @return void
	* @param string $mimeType
	* @desc set new mimeType in database
	*/
	function setMimeType($mimeType)
	{
		global $g_dbConn;

		$sql1 = "SELECT mimetype_id FROM mimetypes WHERE mimetype = ?";

		$sql = "UPDATE items SET mimetype = ?, last_modified = ? WHERE item_id = ?";
		$d = date("Y-m-d"); //get current date

		$mimeType = (!is_null($mimeType)) ? $mimeType : "text/html";
		
		$mimeTypeID = $g_dbConn->getOne($sql1, array($mimeType));
		if (Rd_Pdo_PearAdapter::isError($mimeTypeID)) { trigger_error($mimeTypeID->getMessage(), E_USER_ERROR); }

		$this->mimeTypeID = $mimeTypeID;

		$rs = $g_dbConn->query($sql, array($this->mimeTypeID, $d, $this->itemID));
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }

		$this->lastModDate = $d;
	}

	function setMimeTypeByFileExt($ext, $isVideo=false)
	{
		global $g_dbConn;

        $ext = str_replace(".", "", $ext);

		$sql1 = "SELECT m.mimetype, m.mimetype_id FROM mimetypes AS m JOIN mimetype_extensions AS me ON m.mimetype_id = me.mimetype_id WHERE file_extension = ?";
				
		$sql = "UPDATE items SET mimetype = ?, last_modified = ? WHERE item_id = ?";
		$d = date("Y-m-d"); //get current date

		$mimetypeArray = $g_dbConn->getRow($sql1, array($ext));
		
		// What should the behavior of the mimetypeID be if the item is video? Why are mimetypeId and mimetype set separately?
		$this->mimeTypeID = $mimetypeArray[1];
		
		if (Rd_Pdo_PearAdapter::isError($mimetypeArray)) { trigger_error($mimetypeArray->getMessage(), E_USER_ERROR); }		
		
		if(!$isVideo){
			// If it's not a video, set the mimetype to what we retrieved from the database.
			$this->setMimeType($mimetypeArray[0]);
		}
		else{
			// If it is a video, update the mimetype to be "video" and the lastupdate date to be now.
			// (but don't change the mimetypeId??? Confused here.)
			$rs = $g_dbConn->query($sql, array("video", $d, $this->itemID));
		}
		if (isset($rs) && Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }

		$this->lastModDate = $d;
	}
	
	function setDocTypeIcon($docTypeIcon)
	{
		global $g_dbConn;

		$sql = "UPDATE items SET item_icon = ?, last_modified = ? WHERE item_id = ?";
		$d = date("Y-m-d"); //get current date
		
		$rs = $g_dbConn->query($sql, array($docTypeIcon, $d, $this->itemID));
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }

		$this->itemIcon = $docTypeIcon;
		$this->lastModDate = $d;
	}

    static function getGenericIcon(){
        return 'public/images/doc_type_icons/doctype-clear.gif';
    }

	function getItemIcon()
	{
		global $g_dbConn;
			
		if (!isset($this)) //#TODO what?
			return 'public/images/doc_type_icons/doctype-clear.gif';
			
		if (is_null($this->itemIcon) || $this->itemIcon == "")	
		{	
			switch ($this->mimeTypeID)
			{
				case '7':
				case 'text/html':
				case 'video':
				case null:
					switch ($this->itemGroup)
					{
						case 'MONOGRAPH':
							return 'public/images/doc_type_icons/doctype-book.gif';
						break;
						case 'MULTIMEDIA':
							return 'public/images/doc_type_icons/doctype-disc2.gif';
						break;
						case 'ELECTRONIC':
							return 'public/images/doc_type_icons/doctype-link.gif';
						break;
						case 'VIDEO':
							return 'public/images/doc_type_icons/doctype-movie.gif';
						break;
						default:
							return 'public/images/doc_type_icons/doctype-clear.gif';
					}
				break;
	
				case '1': // PDF
					return 'public/images/doc_type_icons/doctype-pdf.gif';
				break;
				
				default:
					$sql = "SELECT helper_app_icon FROM mimetypes WHERE mimetype_id = ?";

					$rs = $g_dbConn->query($sql, array($this->mimeTypeID));
					if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }
	
					if ($rs->rowCount() < 1)
						return 'public/images/doc_type_icons/doctype-clear.gif';
					else {
						$row = $rs->fetch(PDO::FETCH_NUM);
						return $row[0];
					}
			}
		} else 
			return $this->itemIcon;
	}

	

	/**
	* @return void
	* @param string $homeLibraryID
	* @desc set new homeLibraryID in database
	*/
	function setHomeLibraryID($homeLibraryID)
	{
		global $g_dbConn;

		$this->homeLibraryID = $homeLibraryID;
		$sql = "UPDATE items SET home_library = ?, last_modified = ? WHERE item_id = ?";
		$d = date("Y-m-d"); //get current date

		$rs = $g_dbConn->query($sql, array($homeLibraryID, $d, $this->itemID));
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }

		$this->homeLibraryID = $homeLibraryID;
		$this->lastModDate = $d;
	}

	/**
	* @return void
	* @param int $privateUserID
	* @desc set new privateUserID in database
	*/
	function setPrivateUserID($privateUserID)
	{
		global $g_dbConn;

		$this->privateUserID = $privateUserID;
		$sql = "UPDATE items SET private_user_id = ?, last_modified = ? WHERE item_id = ?";
		$d = date("Y-m-d"); //get current date

		$rs = $g_dbConn->query($sql, array($privateUserID, $d, $this->itemID));
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }

		$this->privateUserID = $privateUserID;
		$this->lastModDate = $d;
	}
	
	/**
	* @return void
	* @param string $ISBN
	* @desc Updates the ISBN value, associated w/the item, in the DB
	*/
	function setISBN($ISBN)
	{
		global $g_dbConn;

		$noISBN = ($ISBN === '' || $ISBN === '0' || is_null($ISBN));
		$this->ISBN = ($noISBN ? null : substr(preg_replace('/[^0-9]/i', '', $ISBN), 0, 13));

		$sql = "UPDATE items SET ISBN = ? WHERE item_id = ?";

		$rs = $g_dbConn->query($sql, array($this->ISBN, $this->itemID));
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }
	}	
	
	/**
	* @return void
	* @param string $ISSN
	* @desc Updates the ISSN value, associated w/the item, in the DB
	*/
	function setISSN($ISSN)
	{
		global $g_dbConn;

		//$this->ISSN = substr(preg_replace('/[^0-9xX]/i', '', $ISSN), 0, 9);
		$this->ISSN = $ISSN;
		$sql = "UPDATE items SET ISSN = ? WHERE item_id = ?";

		$rs = $g_dbConn->query($sql, array($this->ISSN, $this->itemID));
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }
	}

	function setOldId($temp_call_num)
	{
		global $g_dbConn;

		//$this->ISSN = substr(preg_replace('/[^0-9xX]/i', '', $ISSN), 0, 9);
		$this->temp_call_num = $temp_call_num;
		$sql = "UPDATE items SET temp_call_num = ? WHERE item_id = ?";

		$rs = $g_dbConn->query($sql, array($this->temp_call_num, $this->itemID));
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }
	
	}

	/**
	* @return void
	* @param string $status
	* @desc Updates the status value
	*/
	function setStatus($status)
	{
		if (is_null($status) || $this->isHeading())
		{
			//do not update the status of headings
			return null;
		} else {
			global $g_dbConn;
	
			$this->status = $status;
			$sql = "UPDATE items SET status = ? WHERE item_id = ?";

			$rs = $g_dbConn->query($sql, array($status, $this->itemID));
			if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }
		}
	}	
	
	
	/**
	* @return void
	* @param string $OCLC
	* @desc Updates the OCLC value, associated w/the item, in the DB
	*/
	function setOCLC($OCLC)
	{
		global $g_dbConn;

		$this->OCLC = $OCLC;
		$sql = "UPDATE items SET OCLC = ? WHERE item_id = ?";

		$rs = $g_dbConn->query($sql, array($this->OCLC, $this->itemID));
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }
	}		

	function getReserveId()
	{
		return $this->_reserveId;
	}

	function getAuthor() 
	{ 
		return htmlentities(stripslashes($this->author)); 
	}
	
	function getSource() 
	{ 
		return htmlentities(stripslashes($this->source)); 
	}
	
	function getVolumeTitle() 
	{ 
		return htmlentities(stripslashes($this->volumeTitle)); 
	}
	
	function getVolumeEdition() 
	{ 
		return htmlentities(stripslashes($this->volumeEdition)); 
	}
	
	function getPagesTimes() 
	{ 
		return htmlentities(stripslashes($this->pagesTimes)); 
	}
	
	function getPerformer() 
	{ 
		return htmlentities(stripslashes($this->performer)); 
	}
	
	function getLocalControlKey() 
	{ 
		return stripslashes($this->localControlKey); 
	}
	
	function getURL() 
	{ 
		return 
			($this->URL != '' && !is_null($this->URL)) 
			? stripslashes($this->URL) 
			: false; 
	}
	
	function getISBN() 
	{ 
		return $this->ISBN; 
	}
	
	function getISSN() 
	{ 
		return $this->ISSN; 
	}
	
	function getOCLC() 
	{ 
		return $this->OCLC; 
	}	
	
	function getOldId() 
	{ 
		return $this->temp_call_num; 
	}
	
	function getStatus() 
	{ 
		return $this->status; 
	}	
	
	function getMimeType()
	{
		global $g_dbConn;

		$mimetype = "x-application";
		if (!is_null($this->mimeTypeID) && is_numeric($this->mimeTypeID)){
			$sql = "SELECT mimetype FROM mimetypes WHERE mimetype_id = ? LIMIT 1";

			$rs = $g_dbConn->query($sql, array($this->mimeTypeID));
			if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }


			while($row = $rs->fetch(PDO::FETCH_NUM)) { //#TODO this will match the last found?
				$mimetype = $row[0]; 
			}
		}

		return $mimetype;
	}

	function getHomeLibraryID() 
	{ 
		return $this->homeLibraryID; 
	}

	function getPrivateUser()
	{
		$this->privateUser = new user($this->privateUserID);
	}

	function getPrivateUserID() 
	{ 
		return 
			(!is_null($this->privateUserID) && $this->privateUserID != "") 
			? $this->privateUserID 
			: null; 
	}

	function getPhysicalCopy()
	{
		$this->physicalCopy = new physicalCopy();
		return $this->physicalCopy->getByItemID($this->getItemID());
	}

	function isPhysicalItem()
	{
		if ($this->itemGroup == 'MULTIMEDIA' || $this->itemGroup == 'MONOGRAPH') {
			return true;
		} else {
			return false;
		}
	}
	
	function isVideoItem()
	{
		if($this->itemGroup == 'VIDEO') {
			return true;
		} else {
			return false;
		}
	}

	function isPersonalCopy()
	{
		if ($this->privateUserID != null)
			return true;
		else
			return false;
	}
	
	/**
	 * @return boolean
	 * @desc Attempts to guess if the file is stored locally or remotely. Returns true if local file, false otherwise
	 */
	function isLocalFile() 
	{
		if(empty($this->URL)) {	//blank URLs are not local
			return false;
		}
		
		//parse the url into its component parts
		$parsed_url = parse_url($this->URL);
		
		//if the url does not contain a scheme (http, ftp, etc), assume it's local		
		return empty($parsed_url['scheme']) ? true : false;
	}
	
	/**
	 * @return courseInstance Object
	 * @desc Returns courseInstace objects if the reserveId is specified
	 */
	function getCourseInstance() {
		if (is_null($this->_reserveId)) {
			trigger_error('This item does not have a reserve ID specified.', E_USER_ERROR);
		}
		$sql = (
			"SELECT r.course_instance_id AS course_instance_id "
			. "FROM reserves AS r "
			. "WHERE r.reserve_id = {$this->_reserveId}"	
		);
		
		$result = Rd_Pdo::one(Rd_Pdo::query($sql));
		if(!$result) {
			return null;
		}
		return new courseInstance($result['course_instance_id']);
	}
	
	/**
	 * @return array of courseInstance Objects
	 * @desc Returns array of courseInstace objects that have used this item
	 */
	function getAllCourseInstances() {
		global $g_dbConn;
		if (is_null($this->itemID)) {
			trigger_error('Unable to find the item for this reserve in the database.', E_USER_ERROR);
		}
		$sql = "SELECT DISTINCT r.course_instance_id 
			FROM reserves AS r
				JOIN course_instances AS ci ON ci.course_instance_id = r.course_instance_id
				JOIN course_aliases AS ca on ca.course_alias_id = ci.primary_course_alias_id
				JOIN courses AS c ON c.course_id = ca.course_id
				JOIN departments AS d ON d.department_id = c.department_id
			WHERE r.item_id = {$this->itemID}
			ORDER BY ci.activation_date DESC, d.abbreviation ASC, c.course_number ASC, ca.section ASC";					

		$rs = $g_dbConn->query($sql);
		
		if (Rd_Pdo_PearAdapter::isError($rs)) {
			trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR);
		}
		
		$classes = array();
		while($row = $rs->fetch(PDO::FETCH_NUM)) {
			$classes[] = new courseInstance($row[0]);
		}
		
		return $classes;
	}
	
	/**
	* @return boolean
	* @desc determine if adding this item to a reserves list will require staff review
	*/	
	function copyrightReviewRequired()
	{
		//review is required for all documents except physical items and external links
		
		$not_heading 	= !$this->isHeading();
		$local			= $this->isLocalFile();
		$not_manuscript = !($this->getItemGroup() == 'MANUSCRIPT');
		
		return ($not_heading && $local && $not_manuscript);		
	}
	
	public function getMathcingItemsBy($field)
	{
		switch($field){
			case 'local_control_key':
				$items = self::_getMatchingItemsByLocalControlKey($this->getLocalControlKey());
				return $items;
			break;
			default:
				throw new Exception("Unsupported Matching Criteria, {$field}, for Reserve Item.");
		}
	}
	
	protected static function _getMatchingItemsByLocalControlKey($localControlKey)
	{
		if('' == $localControlKey || !is_numeric($localControlKey)){
			return array();
		} else {
			$localControlKey = intval($localControlKey);
		}
		$sql = (
			"SELECT r.reserve_id as reserve_id, i.item_id AS item_id FROM items AS i "
			. "LEFT JOIN reserves AS r ON i.item_id = r.item_id "
			. "WHERE i.local_control_key = {$localControlKey}"
		);
		$result = Rd_Pdo::all(Rd_Pdo::query($sql));
		if(!$result) {
			return array();
		}
		$newItems = array();
		foreach($result as $row){
			$newItems[] = new reserveItem($row['item_id']);
			$newItems[count($newItems) - 1]->specifyReserveId($row['reserve_id']);
		}
		return $newItems;
	}
}
