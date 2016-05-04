<?php
/*******************************************************************************
itemManager.class.php

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
//require_once(APPLICATION_PATH . '/common.inc.php');
require_once(APPLICATION_PATH . '/displayers/itemDisplayer.class.php');
require_once(APPLICATION_PATH . '/managers/classManager.class.php'); //#TODO deprecate?
require_once(APPLICATION_PATH . '/managers/copyrightManager.class.php'); //#TODO deprecate?
require_once(APPLICATION_PATH . '/managers/reservesManager.class.php'); //#TODO deprecate?
require_once(APPLICATION_PATH . '/classes/note.class.php');
require_once(APPLICATION_PATH . '/displayers/requestDisplayer.class.php');

class itemManager extends Rd_Manager_Base
{
	public $user;
	public $displayClass;
	public $displayFunction;
	public $argList;

	function display()
	{
		$args = array();
		foreach($this->argList as $index=>$value){
			$args[$index] =& $this->argList[$index];
		}
		if (is_callable(array($this->displayClass, $this->displayFunction))){
			$this->_displayer = new $this->displayClass();
			call_user_func_array(array($this->_displayer, $this->displayFunction), $args);
		}
	}

	function itemManager($cmd, $user)
	{
		global $g_permission, $ci;
		$u = Rd_Registry::get('root:userInterface');
		$this->displayClass = "itemDisplayer";
		
		switch ($cmd)
		{
			
			// *
			// This case depends on editItem and so MUST COME IMMEDIATELY BEFORE `case 'editItem':`			
			// *			
			case 'duplicateReserve':	//duplicates reserve AND item
				if(empty($_REQUEST['reserveID']))
					break;	//error, no reserveID set
								
				//get the source reserve
				$srcReserve = new reserve($_REQUEST['reserveID']);				
				//duplicate it
				$dupReserveID = $srcReserve->duplicateReserve();				
				
				//set up some vars
				
				$selected_instr = $_REQUEST['selected_instr'];	//remember instructor
				
				$_REQUEST = array();	//clear current request
				
				$_REQUEST['reserveID'] = $dupReserveID;	//set up new reserveID
				$_REQUEST['dubReserve'] = true;	//set flag, to let editItem handler know this is a dupe
				$_REQUEST['selected_instr'] = $selected_instr;	//set instructor

			//use editItem
			//no break!
						
			case 'editItem':
				//switch b/n editing item or editing reserve+item
				$reserve = null;
				$item = null;
				if(!empty($_REQUEST['reserveID'])) {	//editing item+reserve
					//get reserve
					$reserve = new reserve($_REQUEST['reserveID']);
					//get item
					$item = new reserveItem($reserve->getItemID());
					
					//init a courseInstance to show location				
					$ci = new courseInstance($reserve->getCourseInstanceID());
				} elseif(!empty($_REQUEST['itemID'])) {	//editing item only
					$item = new reserveItem($_REQUEST['itemID']);
				} else {	//no IDs set, error
					break;
				}

				//form submitted - edit item meta
				if(!empty($_REQUEST['submit_edit_item_meta'])) {
					//were we editing a reserve?
					if($reserve instanceof reserve) {	//set some data;
						//set status
						$reserve->setStatus($_REQUEST['reserve_status']);
						
						//set dates, if status is ACTIVE
						if($_REQUEST['reserve_status']=='ACTIVE') {
							//if not empty, set activation and expiration dates
							if(!empty($_REQUEST['reserve_activation_date'])) {
								$reserve->setActivationDate($_REQUEST['reserve_activation_date']);
							}
							if(!empty($_REQUEST['reserve_expiration_date'])) {
								$reserve->setExpirationDate($_REQUEST['reserve_expiration_date']);
							}		
						}
						
						//set parent heading
						if(!empty($_REQUEST['heading_select'])) {
							$reserve->setParent($_REQUEST['heading_select'], true);
						}	
					}
					
					//if editing electronic item, manage files
					
					if(isset($_REQUEST['documentType'])) {						
						if($_REQUEST['documentType'] == 'DOCUMENT') {	//uploaded file?
							$file = common_storeUploaded($_FILES['userFile'], $item->getItemID());
							
							$file_loc = $file['dir'] . $file['name'] . $file['ext'];
							$item->setURL($file_loc);
							$item->setMimeTypeByFileExt($file['ext']);
							if ($item->copyrightReviewRequired())
							{
								$classes = $item->getAllCourseInstances();
								for($i=0; $i < sizeof($classes); $i++)
								{
									$classes[$i]->clearReviewed();
								}
							}
						}
						elseif($_REQUEST['documentType'] == 'VIDEO'){
							$file = common_storeVideo($_FILES['userFile'], $item->getItemID(), $u, $item->getTitle());
							$item->setURL($file);
							$item->setMimeTypeByFileExt($file['ext'], true);
							if ($item->copyrightReviewRequired())
							{
								$classes = $item->getAllCourseInstances();
								for($i=0; $i < sizeof($classes); $i++)
								{
									$classes[$i]->clearReviewed();
								}
							}
						}
						
						
						elseif($_REQUEST['documentType'] == 'URL') {	//link?
							$item->setURL($_REQUEST['url']);
						}
						//else maintaining the same link; do nothing
					}
					
					//set item data
					$item->setTitle($_REQUEST['title']);
					$item->setAuthor($_REQUEST['author']);
					$item->setPerformer($_REQUEST['performer']);
					$item->setDocTypeIcon($_REQUEST['selectedDocIcon']);
					$item->setVolumeTitle($_REQUEST['volumeTitle']);
					$item->setVolumeEdition($_REQUEST['volumeEdition']);
					$item->setPagesTimes($_REQUEST['pagesTimes']);
					$item->setSource($_REQUEST['source']);										
					$item->setISBN($_REQUEST['ISBN']);
					$item->setISSN($_REQUEST['ISSN']);
					$item->setOCLC($_REQUEST['OCLC']);	
					if($u->getRole() >= $g_permission['staff']){
						$item->setStatus((array_key_exists('item_status', $_REQUEST) ? $_REQUEST['item_status']: ''));	
					}
						
					//physical item data
					if($item->isPhysicalItem()) {
						$item->setHomeLibraryID($_REQUEST['home_library']);
						
						//physical copy data
						if($item->getPhysicalCopy()) {	//returns false if not a physical copy
							//only set these if they were part of the form
							if(isset($_REQUEST['barcode'])) $item->physicalCopy->setBarcode($_REQUEST['barcode']);
							if(isset($_REQUEST['call_num'])) $item->physicalCopy->setCallNumber($_REQUEST['call_num']);							
						}
						
						if(!empty($_REQUEST['local_control_key'])) {
							$item->setLocalControlKey($_REQUEST['local_control_key']);
						}						
					}
					
					//personal copies
					if($_REQUEST['personal_item'] == 'no') {	//do not want a private owner
						$item->setPrivateUserID('null');
					}
					elseif($_REQUEST['personal_item'] == 'yes') {	//we want a private owner
						//if we are choosing a new private owner, set it
						if( ($_REQUEST['personal_item_owner']=='new') && !empty($_REQUEST['selected_owner']) ) {
							$item->setprivateUserID($_REQUEST['selected_owner']);
						}
						//else we are keeping old private owner, so no change necessary
					}
					
					//notes
					if(!empty($_REQUEST['notes'])) {
						foreach($_REQUEST['notes'] as $note_id=>$note_text) {
							if(!empty($note_id)) {
								$note = new note($note_id);
								$note->setText($note_text);
							}
						}
					}
					
					//if duplicating, show a different success screen
					if($_REQUEST['dubReserve']) {
						//get course instance
						$ci = new courseInstance($reserve->getCourseInstanceID());
						$ci->getPrimaryCourse();

						//call requestDisplayer method
						$this->_setLocation('add an item');
						$this->displayClass = 'requestDisplayer';
						$this->displayFunction = 'addSuccessful';
						$this->argList = array($ci, $item->getItemID(), $reserve->getReserveID(), true);
					}
					else {
						//get courseinstance id, if editing reserve
						$ci_id = ($reserve instanceof reserve) ? $reserve->getCourseInstanceID() : null;
						
						// display success
						$this->displayFunction = 'displayItemSuccessScreen';
						$this->argList = array($ci_id, array_key_exists('search', $_REQUEST) ? urlencode($_REQUEST['search']) : '');
					}
				}
				elseif(!empty($_REQUEST['submit_edit_item_copyright'])) {	//form submitted - edit item copyright
					switch($_REQUEST['form_id']) {
						case 'copyright_status':
							copyrightManager::setStatus();						
						break;
						
						case 'copyright_supporting_items_delete':
							copyrightManager::deleteSupportingItem();
						break;
						
						case 'copyright_supporting_items_add':
							copyrightManager::addSupportingItem();
						break;
					}
					
					//go back to edit item copyright screen
					$this->_setTab('addReserve');
					$this->_setLocation('edit item');		
					Rd_Help::setDefaultArticleId(33);
					
					$this->displayFunction = 'displayEditItem';
					$this->argList = array($item, $reserve);
				}
				else {	//display edit page
					$this->_setTab('addReserve');
					$this->_setLocation('edit item');
					Rd_Help::setDefaultArticleId(33);
					
					$this->displayFunction = 'displayEditItem';
					$this->argList = array($item, $reserve, array(
						'dubReserve'=>(array_key_exists('dubReserve', $_REQUEST) ? $_REQUEST['dubReserve'] : ''), 
						'selected_instr'=>(array_key_exists('selected_instr', $_REQUEST) ? $_REQUEST['selected_instr'] : '')
					));
				}			
			break;

			case 'editHeading':
				$this->_setTab(($u->getRole() >= Account_Rd::LEVEL_STAFF) ? 'manageClasses' : 'addReserve');
				$this->_setLocation('edit heading');
				Rd_Help::setDefaultArticleId(35);
				
				$headingID = !empty($_REQUEST['headingID']) ? $_REQUEST['headingID'] : null;
				$heading = new reserve($headingID);
				
				$this->displayFunction = 'displayEditHeadingScreen';
				$this->argList = array($_REQUEST['ci'], $heading);
			break;
			
			case 'processHeading':
				$this->_setTab('myReserves');
				$this->_setLocation('edit heading');
				Rd_Help::setDefaultArticleId(35);
				$tempReserveId = "";

				$ci = new courseInstance($_REQUEST['ci']);
				$headingText = $_REQUEST['heading'];
				$headingID = $_REQUEST['headingID'];
				$activation_date = isset($_REQUEST['reserve_activation_date']) ? $_REQUEST['reserve_activation_date'] : $ci->activationDate;
				$expiration_date = isset($_REQUEST['reserve_expiration_date']) ? $_REQUEST['reserve_expiration_date'] : $ci->expirationDate;
				$aciton = "";
				
				//Set activation Date
				//Set expiration Date
				
				if(empty($headingID)) {	//need to create a new item
					if ($headingText) {
						$heading = new item();				
						$heading->createNewItem();
						$heading->makeHeading();
						$reserve = new reserve();
						$reserve->createNewReserve($ci->getCourseInstanceID(), $heading->itemID);
						$reserve->setStatus('ACTIVE');
						$reserve->setActivationDate($activation_date);
						$reserve->setExpirationDate($expiration_date);
						$reserve->insertIntoSortOrder($ci->getCourseInstanceID(), $headingText, 'zzzzz');	//zzzz will put the heading last if author-sorted
						$tempReserveId = $reserve->getReserveID();
						$headingID = $heading->getItemID();
						$action = courseInstanceAudit::EVENT_ADD;
						
					}
				}
				else {
					$heading = new item($headingID);
					$action = courseInstanceAudit::EVENT_EDIT;
				}
				$headingReserveID = (isset($_REQUEST['reserveID']) && $_REQUEST['reserveID'] != "") ? $_REQUEST['reserveID'] : $tempReserveId;
				
				
				if ($headingText)
					$heading->setTitle($headingText);
				
				if(isset($_REQUEST['reserve_activation_date']) || isset($_REQUEST['reserve_expiration_date'])){	
					$reserve_heading = new reserve($headingReserveID);
					if(isset($_REQUEST['reserve_activation_date'])){
						$reserve_heading->setActivationDate($activation_date);
					}
					if(isset($_REQUEST['reserve_expiration_date'])){
						$reserve_heading->setExpirationDate($expiration_date);
					}	
				}
				//notes
				if(!empty($_REQUEST['notes'])) {
					foreach($_REQUEST['notes'] as $note_id=>$note_text) {
						if(!empty($note_id)) {
							$note = new note($note_id);
							$note->setText($note_text);
						}
					}
				}
				
				$cia = new courseInstanceAudit();
				echo $action;
				$cia->logHeadingEvent($_REQUEST['ci'], $headingID, $action);
				$this->displayFunction = 'displayHeadingSuccessScreen';
				$this->argList = array($_REQUEST['ci']);
			break;
		}	
	}
}

