<?php
/*******************************************************************************
copyrightManager.class.php


Created by Dmitriy Panteleyev (dpantel@emory.edu)
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

require_once(APPLICATION_PATH . '/classes/copyright.class.php');
require_once(APPLICATION_PATH . '/classes/reserveItem.class.php');

class copyrightManager {
	
	
	/**
	 * @return void
	 * @desc uses form values in $_REQUEST to set the copyright status
	 */
	public static function setStatus() {
		if(empty($_REQUEST['item_id'])) {
			return false;
		}
		
		//init an empty copyright object
		$copyright = new Copyright();
		
		//handle copyright status basis
		$status_basis = null;
		if(isset($_REQUEST['copyright_status_basis_id'])) { //trying to set a basis
			if(!empty($_REQUEST['copyright_status_basis_id'])) {	//trying to pick an existing basis
				$status_basis = $_REQUEST['copyright_status_basis_id'];
			}
			elseif(!empty($_REQUEST['copyright_status_basis_new'])) {	//trying to create a new basis
				//create a new basis
				$status_basis = $copyright->createNewStatusBasis($_REQUEST['copyright_status'], $_REQUEST['copyright_status_basis_new']);
				//use it if created successfully
				$status_basis = ($status_basis!==false) ? $status_basis : null;
			}
		}

		//update copyright status						
		if($copyright->getByItemID($_REQUEST['item_id'])) {	//record found, update
			$copyright->setStatus($_REQUEST['copyright_status'], $status_basis);
		}
		else {	//no record exists, create
			$copyright->createNewRecord($_REQUEST['item_id'], $_REQUEST['copyright_status'], $status_basis);
		}
	}
	
	
	/**
	 * @return boolean
	 * @desc uses value from $_REQUEST to set contact; returns true on success
	 */
	public static function setContact() {
		if(empty($_REQUEST['item_id']) || empty($_REQUEST['contact_id'])) {
			return false;
		}
	
		$copyright = new Copyright();
		if($copyright->getByItemID($_REQUEST['item_id'])) {	//record found, update
			$copyright->setContact($_REQUEST['contact_id']);
			return true;
		}
		else {
			return false;
		}
	}
	
	
	/**
	 * @return boolean
	 * @desc deletes supporting item from copyright record
	 */
	public static function deleteSupportingItem() {
		$copyright = new Copyright();
		if($copyright->getByItemID($_REQUEST['item_id'])) {	//record found, update
			return $copyright->deleteSupportingItem($_REQUEST['delete_file_id']);
		}
		else {
			return false;
		}
	}
	
	
	/**
	 * @return boolean
	 * @desc adds supporting item to copyright record
	 */
	public static function addSupportingItem() {
		$copyright = new Copyright();
		if($copyright->getByItemID($_REQUEST['item_id'])) {	//record found, update			
			if(!empty($_REQUEST['url']) || !empty($_FILES['userFile'])) {
				//create item
				$supp_item = new reserveItem();
				$supp_item->createNewItem();
				
				//set title
				$supp_item->setTitle($_REQUEST['file_title']);
				//set fake private user
				$supp_item->setPrivateUserID(0);
			
				//handle the file/url
				if(($_REQUEST['file_source_option'] == 'file') &&  !empty($_FILES['userFile'])) {	//uploaded file
					$file = common_storeUploaded($_FILES['userFile'], $supp_item->getItemID());
					
					$file_loc = $file['dir'] . $file['name'] . $file['ext'];
					$supp_item->setURL($file_loc);
					$supp_item->setMimeTypeByFileExt($file['ext']);
				}
				else if(($_REQUEST['file_source_option'] == 'url') && !empty($_REQUEST['url'])) {	//link?
					$supp_item->setURL($_REQUEST['url']);
				}
			
				//add it to copyright record
				return $copyright->addSupportingItem($supp_item->getItemID());
			}
			else {
				return false;
			}
		}
		else {
			return false;
		}
	}
}
