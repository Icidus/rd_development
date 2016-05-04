<?php
/*******************************************************************************
reservesManager.class.php

Created by Kathy Washington (kawashi@emory.edu)
Modified by NCSU Libraries, NC State University. Modifications by Karl Doerr, Troy Hurteau and Jason Raitz (libraries.opensource@ncsu.edu).

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
require_once(APPLICATION_PATH . '/common.inc.php');
require_once(APPLICATION_PATH . '/displayers/reservesDisplayer.class.php');
require_once(APPLICATION_PATH . '/classes/searchItems.class.php');
require_once(APPLICATION_PATH . '/classes/request.class.php');
require_once(APPLICATION_PATH . '/classes/faxReader.class.php');
require_once(APPLICATION_PATH . '/classes/itemAudit.class.php');
require_once(APPLICATION_PATH . '/classes/reserves.class.php');
require_once(APPLICATION_PATH . '/managers/noteManager.class.php');
require_once(APPLICATION_PATH . '/managers/classManager.class.php');
require_once(APPLICATION_PATH . '/classes/reserveItem.class.php');
require_once(APPLICATION_PATH . '/displayers/mobileReservesDisplayer.class.php');

//require_once(APPLICATION_PATH . '/lib/Rd/Manager/Base.php');
require_once(APPLICATION_PATH . '/lib/Rd/Synchronizer/Item.php');

class reservesManager extends Rd_Manager_Base
{
	function __construct($cmd, $request, $user)
	{
		global $g_permission, $g_faxDirectory;
		global $g_documentDirectory, $g_notetype, $g_dbConn, $g_scanningLibrary, $g_scannedItemGroup;
		$u = Rd_Registry::get('root:userInterface');
		$ci = Rd_Registry::get('root:selectedCourseInstance');
		$this->user = $user;
		$this->displayClass = (
			isset($_SESSION['mobile']) && $_SESSION['mobile'] == 'true'
			? 'mobileReservesDisplayer'
			: 'reservesDisplayer'
		);
		$this->_withOptions = array('960css');
		$this->_setTab('myReserves');	
		
		switch ($cmd)
		{
			case 'removeStudent':
				if ($_REQUEST['deleteAlias'])
				{
					$aliases = $_REQUEST['alias'];
					if (is_array($aliases) && !empty($aliases)){
						foreach($aliases as $a)
						{
							$user->detachCourseAlias($a);
						}
					}
				}
			case 'addStudent':
				if ($_REQUEST['aID']) {
					$user->attachCourseAlias($_REQUEST['aID']);
				}
			case 'myReserves':
			case 'viewCourseList':
				$this->_setLocation('home');

				$user->getCourseInstances();
				for ($i=0;$i<count($user->courseInstances);$i++)
				{
					$my_ci = $user->courseInstances[$i];
					$my_ci->getInstructors();
					$my_ci->getProxies();

					//Look at this later - should this logic be handled by ci->getCourseForUser? - kawashi 11.2.2004
					if (in_array($user->getUserID(),$my_ci->instructorIDs) || in_array($user->getUserID(),$my_ci->proxyIDs)) {
						//$my_ci->getCourseForInstructor($user->getUserID());
						$my_ci->getPrimaryCourse();
					} else {
						$my_ci->getCourseForUser($user->getUserID());  //load courses
					}
				}

				$this->displayFunction = "displayCourseList";
				$this->argList = array($user);
			break;
			
			
			case 'previewStudentView':	//see if($cmd==...) statement in previewReservesList	
			case 'previewReservesList':
				$ci = new courseInstance($_REQUEST['ci']);
				$ci->getInstructors();
				$ci->getPrimaryCourse();
				try{
					$ci->getCrossListings();
				} catch (Rd_Exception $e) {
					
				}
				//get different reserve data based on $cmd
				if($cmd=='previewStudentView') {
					$reserve_data = $ci->getActiveReserves();	
				}
				elseif($cmd=='previewReservesList') {
					$reserve_data = $ci->getReserves();
				}
				
				//build treen and get recursive iterator
				$tree = $ci->getReservesAsTree(null, array(), $reserve_data);
				$walker = new treeWalker($tree);
				
				$this->_setLocation('home');
				$this->displayFunction = "displayReserves";
				$this->argList = array($cmd, $ci, $walker, count($reserve_data[0]), $reserve_data[2], true);				
			break;
			
			
			case 'viewReservesList':
				$ci = new courseInstance($_REQUEST['ci']);
				if (Account_Rd::isStudent() && !$ci->isActive()) {
					throw new Rd_Exception_Support_CourseAccess('Students cannot view inactive classes.');
				}
				if (Account_Rd::isStudent() && !$ci->hasStudent($user->getUserID())) {
					throw new Rd_Exception_Support_CourseAccess('You are not enrolled in this course.');
				}

				$ci->getInstructors();
				$ci->getCourseForUser($user->getUserID());
				try{
					$ci->getCrossListings();
				} catch (Rd_Exception $e) {
					
				}
				
				//hide/unhide items
				if(!empty($_REQUEST['hideSelected'])) {
					//we need to fetch the whole tree (including hidden items)
					$reserve_data = $ci->getActiveReservesForUser($user->getUserID(), true);
					//build tree
					$tree = $ci->getReservesAsTree(null, array(), $reserve_data);
					
					$hidden = !empty($_REQUEST['selected_reserves']) ? $_REQUEST['selected_reserves'] : array();
					
					//unhide first
					if(!empty($reserve_data[2])) {	//only bother if there were hidden items before
						$unhide = array_diff($reserve_data[2], $hidden);
				
						//are there changes?
						if(!empty($unhide)) {
							foreach($unhide as $r_id) {	//must unhide element AND its children
								//unhide reserve
								$reserve = new reserve($r_id);
								$reserve->unhideReserve($user->getUserID());
								//unhide its children
								$walker = new treeWalker($tree->findDescendant($r_id));
								foreach($walker as $leaf) {
									$reserve = new reserve($leaf->getID());
									$reserve->unhideReserve($user->getUserID());
								}
							}
						}
					}
			
					//now hide (the same process in reverse)
					if(!empty($hidden)) {	//only bother if anything was checked
						$hide = array_diff($hidden, $reserve_data[2]);

						//are there changes?
						if(!empty($hide)) {
							foreach($hide as $r_id) {	//must hide element AND its children
								//hide reserve
								$reserve = new reserve($r_id);
								$reserve->hideReserve($user->getUserID());
								//hide its children
								$walker = new treeWalker($tree->findDescendant($r_id));
								foreach($walker as $leaf) {
									$reserve = new reserve($leaf->getID());
									$reserve->hideReserve($user->getUserID());
								}
							}
						}
					}					
				}
				
				//get array of reserves for this CI for tree-building
				$reserve_data = $ci->getActiveReservesForUser($user->getUserID(), (array_key_exists('showAll', $_REQUEST) && $_REQUEST['showAll']));
				//build treen and get recursive iterator
				$tree = $ci->getReservesAsTree(null, array(), $reserve_data);
				$walker = new treeWalker($tree);
				
				$this->_setLocation('home');
				$this->displayFunction = "displayReserves";
				$this->argList = array($cmd, $ci, $walker, count($reserve_data[0]), $reserve_data[2], false);	
			break;

			case 'customSort':
				$this->_setTab(
					$u->getRole() >= $g_permission['staff']
					? 'manageClasses' 
					: 'myReserves'
				);
				$this->_setLocation('sort reserves list');
				Rd_Help::setDefaultArticleId(34);
				
				$ci = new courseInstance($_REQUEST['ci']);
				
				if(isset($_REQUEST['saveOrder'])) {	//update order
					foreach($_REQUEST['new_order'] as $r_id=>$order) {
						$reserve = new reserve($r_id);
						$reserve->setSortOrder($order);
					}
					$reserves = $ci->getSortedReserves('', $_REQUEST['parentID']);
				}
				else {
					$reserves = $ci->getSortedReserves($_REQUEST['sortBy'], $_REQUEST['parentID']);
				}
				
				$this->displayFunction = "displayCustomSort";
				$this->argList = array($ci, $reserves);
			break;
			
			case 'selectInstructor':
				$this->_setTab('addReserve');
				if (($user->getRole() >= $g_permission['staff']) && $cmd=='selectInstructor') {
					$this->displayFunction = "displaySelectInstructor";
					$this->argList = array($user, 'addReserve', 'addReserve');
				}
			break;
			
			/*case 'addReserve':
				$this->_addReserve();
			break;*/
			
			case 'addMultipleReserves':
				$this->_addMultipleReserves($cmd, $user);
			break;
			
			case 'searchScreen':
				$this->_setTab('addReserve');
				$this->_setLocation('search for an item');
				Rd_Help::setDefaultArticleId(19);
				

				$this->displayFunction = "displaySearchScreen";
				$this->argList = array(null, 'searchResults', $_REQUEST['ci']);
			break;
			case 'searchResults':
				$this->_setTab('addReserve');
				$this->_setLocation('search for an item');
				Rd_Help::setDefaultArticleId(19);
				
				$search = new searchItems();

				if (isset($_REQUEST['f'])) {
					$f = $_REQUEST['f'];
				} else {
					$f = "";
				}

				if (isset($_REQUEST['e'])) {
					$e = $_REQUEST['e'];
				} else {
					$e = "";
				}

				$search->search($_REQUEST['field'], urldecode($_REQUEST['query']), $f, $e);

				if (isset($_REQUEST['request'])) {
					$HiddenRequests = $_REQUEST['request'];
				} else {
					$HiddenRequests = "";
				}
				if (isset($_REQUEST['reserve'])) {
					$HiddenReserves = $_REQUEST['reserve'];
				} else {
					$HiddenReserves = "";
				}
								
				if (!$ci->course instanceof course)
					$ci->getPrimaryCourse();
				
				if (!$ci->course->department instanceof department)
					$ci->course->getDepartment();
				$LoanPeriods = $ci->course->department->getInstructorLoanPeriods();

				$this->displayFunction = "displaySearchResults";
				$this->argList = array($user, $search, 'storeReserve', $_REQUEST['ci'], $HiddenRequests, $HiddenReserves, $LoanPeriods);
			break;
			case 'storeReserve':
				$this->_setTab('addReserve');
				
				//attempt to use transactions
				Rd_Pdo::beginTransaction();
				try {
					$requests = (isset($_REQUEST['request'])) ? $_REQUEST['request'] : null;
					$items = (isset($_REQUEST['reserve'])) ? $_REQUEST['reserve'] : null;
	
					$ci = new courseInstance($_REQUEST['ci']);
	
					//add items to reserve
					if (is_array($items) && !empty($items)){
						foreach($items as $i_id)
						{
							$reserve = new reserve();
							if ($reserve->createNewReserve($ci->getCourseInstanceID(), $i_id))
							{
								$reserve->setActivationDate($ci->getActivationDate());
								$reserve->setExpirationDate($ci->getExpirationDate());
								//attempt to insert this reserve into order
								$reserve->getItem();
								$reserve->insertIntoSortOrder($ci->getCourseInstanceID(), $reserve->item->getTitle(), $reserve->item->getAuthor());
							}
						}
					}
	
					//make requests
					if (is_array($requests) && !empty($requests)){
						foreach($requests as $i_id)
						{
							//store reserve with status processing
							$reserve = new reserve();
							if ($reserve->createNewReserve($ci->getCourseInstanceID(), $i_id))
							{
								$reserve->setStatus("IN PROCESS");
								$reserve->setActivationDate($ci->getActivationDate());
								$reserve->setExpirationDate($ci->getExpirationDate());
								$reserve->setRequestedLoanPeriod(array_key_exists('requestedLoanPeriod_'.$i_id, $_REQUEST) ? $_REQUEST['requestedLoanPeriod_'.$i_id] : '');
								//attempt to insert this reserve into order
								$reserve->getItem();
								$reserve->insertIntoSortOrder($ci->getCourseInstanceID(), $reserve->item->getTitle(), $reserve->item->getAuthor());
	
								//create request
								$request = new request();
								//make sure request does not exist
								//prevent duplicate requests
								if($request->getRequestByCI_Item($ci->getCourseInstanceID(), $i_id) === false) {
									$request->createNewRequest($ci->getCourseInstanceID(), $i_id);
									$request->setRequestingUser($user->getUserID());
									$request->setReserveID($reserve->getReserveID());
								}
							}
						}
					}
				} catch (Exception $e) {
					Rd_Pdo::rollback();
					trigger_error("Error Occurred While processing StoreRequest ".$e->getMessage(), E_USER_ERROR);				
				}
				//commit this set
				Rd_Pdo::commit();
								
				$this->displayFunction = "displayReserveAdded";
				$this->argList = array($user, null, $_REQUEST['ci'], null);
			break;
			case 'uploadDocument':
				$this->_setTab('addReserve');
				$this->_setLocation('upload a document');
				Rd_Help::setDefaultArticleId(18);
				$this->displayFunction = "displayUploadForm";
				$this->argList = array($user, $_REQUEST['ci'], "DOCUMENT", $user->getAllDocTypeIcons());
			break;
			case 'addURL':
				$this->_setTab('addReserve');
				$this->_setLocation('add a URL / link');
				Rd_Help::setDefaultArticleId(16);
				$this->displayFunction = "displayUploadForm";
				$this->argList = array($user, $_REQUEST['ci'], "URL", $user->getAllDocTypeIcons());
			break;
			case 'placeRequest':
				$this->_setTab('addReserve');
				$this->_setLocation('request an item to be put on reserve');
				Rd_Help::setDefaultArticleId(1);
				if (array_key_exists('requestSubmitted', $_REQUEST) && $_REQUEST['requestSubmitted'] == "submitted") {
				
					$ci = new courseInstance($_REQUEST['ci']);
					$makeRequests = array();
					$firstReserve = null;
					$firstRequest = null;
				
					if ($_REQUEST['requestType'] == "physical" || $_REQUEST['requestType'] == "physAndElec") {
						$makeRequests[] = "physical";
					}
					
					if ($_REQUEST['requestType'] == "electronic" || $_REQUEST['requestType'] == "physAndElec") {
						$makeRequests[] = "electronic";
					}
					
					foreach ($makeRequests as $thisRequest) {
						//attempt to use transactions
						Rd_Pdo::beginTransaction();
						
						try {
							// make a reserveItem.
							$item = new reserveItem();
							$itemId = $item->createNewItem();
							// (populate item properties here)
							
							$item->setTitle($_REQUEST['title']);
							if ($thisRequest != "electronic") {
								$item->setGroup($_REQUEST['item_group']);
							}
							else {
								$item->setGroup($g_scannedItemGroup);
							}
							
							if (isset($_REQUEST['author'])) {
								$item->setAuthor($_REQUEST['author']);
							}
							
							if (isset($_REQUEST['source'])) {
								$item->setSource($_REQUEST['source']);
							}
							
							if (isset($_REQUEST['volume_title'])) {
								$item->setVolumeTitle($_REQUEST['volume_title']);
							}
							
							if (isset($_REQUEST['volume_edition'])) {
								$item->setVolumeEdition($_REQUEST['volume_edition']);
							}
							
							if (isset($_REQUEST['times_pages'])) {
								$item->setPagesTimes($_REQUEST['times_pages']);
							}
							
							if (isset($_REQUEST['performer'])) {
								$item->setPerformer($_REQUEST['performer']);
							}
							
							if (isset($_REQUEST['home_library']) && $thisRequest != "electronic") {
								$item->setHomeLibraryID($_REQUEST['home_library']);
							}
							else {
								$item->setHomeLibraryID($g_scanningLibrary);
							}
							
							if (isset($_REQUEST['call_number'])) {
								$item->setOldId($_REQUEST['call_number']);
							}
							
							if (isset($_REQUEST['ISBN'])) {
								$item->setISBN($_REQUEST['ISBN']);
							}
							
							if (isset($_REQUEST['ISSN'])) {
								$item->setISSN($_REQUEST['ISSN']);
							}
							
							if (isset($_REQUEST['OCLC'])) {
								$item->setOCLC($_REQUEST['OCLC']);
							}
							
							// make a reserve.
							$reserve = new reserve();
							if ($reserve->createNewReserve($ci->getCourseInstanceID(), $itemId)) {
								if (empty($firstReserve)) {
									$firstReserve = $reserve;
								}
								$reserve->setStatus("IN PROCESS");
								$reserve->setActivationDate($ci->getActivationDate());
								$reserve->setExpirationDate($ci->getExpirationDate());
								if($thisRequest == 'physical'){
									$reserve->setRequestedLoanPeriod($_REQUEST['loanPeriod']);
								}
								//attempt to insert this reserve into order
								// Not sure what this does, but cribbed from StoreRequest case (ako 10/27/09)
								$reserve->getItem();
								$reserve->insertIntoSortOrder($ci->getCourseInstanceID(), $reserve->item->getTitle(), $reserve->item->getAuthor());
								
								// make request
								$request = new request();
								if ($request->getRequestByCI_Item($ci->getCourseInstanceID(), $itemId) === false) {
									$request->createNewRequest($ci->getCourseInstanceID(), $itemId);
									$request->setRequestingUser($user->getUserID());
									$request->setReserveID($reserve->getReserveID());
									if (isset($_REQUEST['dateNeeded'])) {
										$request->setDateDesired($_REQUEST['dateNeeded']);
									}
									if (isset($_REQUEST['notes'])) {
										$request->setNote($_REQUEST['notes'], $g_notetype['instructor']);
									}
									if ($thisRequest == "physical") {
										$request->setType("PHYSICAL");
									}
									if ($thisRequest == "electronic") {
										$request->setType("SCAN");
									}
									if ($_REQUEST['requestType'] == "physAndElec") {
										$request->setNote("This item has both PHYSICAL and SCAN requests from this instructor.", $g_notetype['staff']);
									}
									if (empty($firstRequest)) {
										$firstRequest = $request;
									}
								}
							}							
						}
						catch (Exception $e) {
							Rd_Pdo::rollback();
							trigger_error("Error Occurred While processing PlaceRequest ".$e->getMessage(), E_USER_ERROR);
						}
						
						// commit.
						Rd_Pdo::commit();
					}
					
					$this->displayFunction = "displayReserveAdded";
					$this->argList = array($user, $firstReserve, $_REQUEST['ci'], $_REQUEST['requestType'], $firstRequest);
				}
				else {
					$this->displayFunction = "displayRequestForm";
					$this->argList = array($user, $_REQUEST['ci']);
				}
			break;
			
			case 'storeUploaded':
				$this->_setTab('addReserve');
				// Check to see if this was a valid file they submitted
	    		if ($_REQUEST['type'] == 'DOCUMENT'){
	    			if (!$_FILES['userFile']['tmp_name']) {
	    				$uploadLimit = Rd_Registry::get('uploadLimitSize');
	    				$this->displayFunction = 'displayError';
						$this->argList = array('addDigitalItem', "Unable to process the uploaded file. Filename: {$_FILES['userFile']['name']} If you are trying to load a large file (> {$uploadLimit}) contact Reserves to add the file.");	
						return;	
	    			}
	    		}

			    $item = new reserveItem();
			    $item->createNewItem();
	    		$item->setTitle($_REQUEST['title']);
	    		$item->setAuthor($_REQUEST['author']);
	    		$item->setPerformer($_REQUEST['performer']);
	    		$item->setVolumeTitle($_REQUEST['volumetitle']);
	    		$item->setVolumeEdition($_REQUEST['volume']);
	    		$item->setSource($_REQUEST['source']);
	    			    
	    		$item->setDocTypeIcon($_REQUEST['selectedDocIcon']);

	    		if ($_REQUEST['type'] == 'DOCUMENT'){
	    			$file = common_storeUploaded($_FILES['userFile'], $item->getItemID());
     				$file_loc = $file['dir'] . $file['name'] . $file['ext'];
	 			$item->setURL($file_loc);
				$item->setMimeTypeByFileExt($file['ext']);
	    		} else {
	    			$file_path = pathinfo(
                        isset($_FILES) && is_array($_FILES) 
                            && array_key_exists('userFile', $_FILES)
                        ? $_FILES['userFile']['name']
                        : ''
                    );
	    			$item->setURL($_REQUEST['url']);
	    			$item->setMimeTypeByFileExt(
                        is_array($file_path) && array_key_exists('extension', $file_path)
                        ? $file_path['extension']
                        : ''
                    );
	    		}
	    		
				$p = $_REQUEST['pagefrom'] . " / " . $_REQUEST['pageto'];
				$t = $_REQUEST['timefrom'] . " / " . $_REQUEST['timeto'];

				//set time or pages if both set overwrite with time
				if ($p != " - ") $item->setPagesTimes($p);
				elseif ($t != " - ") $item->setPagesTimes($t);

				if(isset($_REQUEST['personal'])) $item->setPrivateUserID($user->getUserID());

				$item->setGroup('ELECTRONIC');
				$item->setType('ITEM');
				
				$ci = new courseInstance($_REQUEST['ci'])	;
				$reserve = new reserve();
				
				if ($reserve->createNewReserve($ci->getCourseInstanceID(), $item->getItemID()))
				{
					if(!empty($_REQUEST['noteText']) && isset($_REQUEST['noteType'])) {
						if($_REQUEST['noteType']==$g_notetype['instructor']) {
							$reserve->setNote($_REQUEST['noteText']);
						}
						else {
							$item->setNote($_REQUEST['noteText']);
						}
					}
					
					$reserve->setActivationDate($ci->getActivationDate());
					$reserve->setExpirationDate($ci->getExpirationDate());					
					//attempt to insert this reserve into order
					$reserve->getItem();
					$reserve->insertIntoSortOrder($ci->getCourseInstanceID(), $reserve->item->getTitle(), $reserve->item->getAuthor());

					$itemAudit = new itemAudit();
					$itemAudit->createNewItemAudit($item->getItemID(),$user->getUserID());
				}
				
				$this->displayFunction = "displayReserveAdded";
	    		$this->argList = array($user, $reserve, $_REQUEST['ci'], null);
			break;
			case 'faxReserve':
				$this->_setTab('addReserve');
				$this->_setLocation('fax a document');
				Rd_Help::setDefaultArticleId(17);
				$this->displayFunction = "displayFaxInfo";
				$this->argList = array($_REQUEST['ci']);
			break;
			case 'getFax':
				$this->_setTab('addReserve');
				$this->_setLocation('claim your fax');
				Rd_Help::setDefaultArticleId(17);
				$faxReader = new faxReader();
				$faxReader->getFaxesFromFile($g_faxDirectory);

				$this->displayFunction = "claimFax";
				$this->argList = array($faxReader, $_REQUEST['ci']);
			break;
			case 'addFaxMetadata':
				$this->_setTab('addReserve');
				$this->_setLocation('add fax document information');
				Rd_Help::setDefaultArticleId(17);
				$faxReader = new faxReader();

				$claims =& $_REQUEST['claimFax'];

				$claimedFaxes = array();
				if (is_array($claims) && !empty($claims))
				{
					foreach ($claims as $claim)
						$claimedFaxes[] = $faxReader->parseFaxName($claim);
				}

				$this->displayFunction = "displayFaxMetadataForm";
				$this->argList = array($user, $claimedFaxes, $_REQUEST['ci']);
			break;
			case 'storeFaxMetadata':
				$this->_setTab('addReserve');
				$files = array_keys($_REQUEST['file']);

				$items = array();
				foreach ($files as $file)
				{
					$ci = new courseInstance($_REQUEST['ci']);

					$item = new reserveItem();
					$item->createNewItem();

					$item->setTitle($_REQUEST[$file]['title']);
					$item->setAuthor($_REQUEST[$file]['author']);
					$item->setVolumeTitle($_REQUEST[$file]['volumetitle']);
					$item->setVolumeEdition($_REQUEST[$file]['volume']);
					
					//store the fax
					$md5 = md5_file($g_faxDirectory . $_REQUEST['file'][$file]);
                    $dst_dir = $g_documentDirectory . substr($md5,0,2);
						
					$dst_fname = "{$md5}_{$item->getItemID()}.pdf";
					if(!copy($g_faxDirectory . $_REQUEST['file'][$file], "$dst_dir/$dst_fname")) {
						trigger_error('Failed to copy file '.$g_faxDirectory . $_REQUEST['file'][$file] . ' to ' . "$dst_dir/$dst_fname", E_USER_ERROR);
					}
	
					$item->setURL(substr($md5,0,2) . "/" . $dst_fname);
					$item->setMimeType('application/pdf');

					$p = $_REQUEST[$file]['pagefrom'] . "-" . $_REQUEST[$file]['pageto'];
					if ($p != "-") $item->setPagesTimes($p);

					if ($_REQUEST[$file]['personal'] == "on") $item->setPrivateUserID($user->getUserID());

					$item->setGroup('ELECTRONIC');
					$item->setType('ITEM');

					$reserve = new reserve();

					if ($reserve->createNewReserve($ci->getCourseInstanceID(), $item->getItemID()))
					{
	    				if(!empty($_REQUEST[$file]['noteText']) && isset($_REQUEST[$file]['noteType'])) {
							if($_REQUEST[$file]['noteType']==$g_notetype['instructor']) {
								$reserve->setNote($_REQUEST[$file]['noteText']);
							}
							else {
								$item->setNote($_REQUEST[$file]['noteText']);
							}
						}
						
						$reserve->setActivationDate($ci->getActivationDate());
						$reserve->setExpirationDate($ci->getExpirationDate());
						//attempt to insert this reserve into order
						$reserve->getItem();
						$reserve->insertIntoSortOrder($ci->getCourseInstanceID(), $reserve->item->getTitle(), $reserve->item->getAuthor());

						$itemAudit = new itemAudit();
						$itemAudit->createNewItemAudit($item->getItemID(),$user->getUserID());
					}
				}

				$this->displayFunction = "displayReserveAdded";
				$this->argList = array($user, null, $_REQUEST['ci'], null);
			break;
			
			case 'editMultipleReserves':
				//determine what we are trying to do with the multiple reserves
				
				if((isset($_REQUEST['itemCmd'])) && ($_REQUEST['itemCmd'] == 'edit_multiple')) {	//want to edit some reserve info
					//show form
					$ci = new courseInstance($_REQUEST['ci']);					
					$this->_setTab('addReserve');
					$this->_setLocation('edit multiple reserves');	
					Rd_Help::setDefaultArticleId(41);
					$this->displayFunction = 'displayEditMultipleReserves';
					$this->argList = array($ci, $_REQUEST['selected_reserves']);
				}
				else {	//need to perform the action (save edits / delete / copy)
					//need the CI
					$ci = new courseInstance($_REQUEST['ci']);
					
					if (isset($_REQUEST['itemCmd']) && ($_REQUEST['itemCmd'] == 'approve_copyright'))
					{
						$ci->setReviewed($u->getUserID(), date("Y-m-d"));
					}
					//get array of selected reserve IDs
					$reserve_ids = !empty($_REQUEST['selected_reserves']) ? $_REQUEST['selected_reserves'] : array();
									
					//copy reserves
					if((isset($_REQUEST['itemCmd'])) && ($_REQUEST['itemCmd'] == 'copy_multiple')) {
						$this->_delegateManager = new classManager('copyItems', $u, $adminUser, array('originalClass'=>$_REQUEST['ci'], 'reservesArray'=>$reserve_ids));
						break;	//do not go further
					}
					
					//determine if need to pull in descendants for selected headings
					//only need descendants if deleting OR editing status/dates OR setting copyright flags
					if((isset($_REQUEST['submit_edit_multiple'])) || (isset($_REQUEST['itemCmd']) && (($_REQUEST['itemCmd'] == 'delete_multiple')
						|| ($_REQUEST['itemCmd'] == 'copyright_deny_class') || ($_REQUEST['itemCmd'] == 'copyright_deny_all_classes')))) {
						
						//get reserve tree
						$tree = $ci->getReservesAsTree('getReserves');
						//build a new reserve IDs array that includes all descendants
						$reserve_ids_with_desc = array();
						foreach($reserve_ids as $r_id) {
							//add the reserve
							if(!isset($reserve_ids_with_desc[$r_id])) {
								$reserve_ids_with_desc[$r_id] = $r_id;	//index by id to prevent duplicate values
								$walker = new treeWalker($tree->findDescendant($r_id));	//get the node with that ID
								foreach($walker as $leaf) {
									$reserve_ids_with_desc[$leaf->getID()] = $leaf->getID();	//add child to array
								}
							}
						}
						
						//go through all reserves and their descendants and delete or set status/dates
						foreach($reserve_ids_with_desc as $reserve_id) {
							//init the reserve object
							$reserve = new reserve($reserve_id);
							$reserve->getItem();
							
							//delete reserve
							if(isset($_REQUEST['itemCmd']) && ($_REQUEST['itemCmd'] == 'delete_multiple')) {
								//delete request for physical items
								if($reserve->item->isPhysicalItem()) {
									$request = new request();
									$request->getRequestByReserveID($reserve_id);
									$request->destroy();
								}
								//delete reserve
								$reserve->destroy();
							}

							if (isset($_REQUEST['itemCmd']) && (($_REQUEST['itemCmd'] == 'copyright_deny_class') || ($_REQUEST['itemCmd'] == 'copyright_deny_all_classes')))
							{
								//physical items cant be denied copyright
								if (!$reserve->item->isPhysicalItem()) {
                                    if (isset($_REQUEST['itemCmd']) && ($_REQUEST['itemCmd'] == 'copyright_deny_all_classes'))
                                        $reserve->item->setStatus('DENIED');
                                    else
                                        $reserve->setStatus('DENIED');
								}
								
								//if request exists and is not processed set as processed
								$request = new request();
								$request->getRequestByReserveID($reserve_id);
								if (!is_null($request->requestID) && is_null($request->processedDate))
								{
                                    //do not attempt to set if Request was not found by reserveID
									$request->setDateProcessed(date('Y-m-d'));
								}
							}
							
						
							if(isset($_REQUEST['edit_status']) && isset($_REQUEST['reserve_status'])) {
								//do NOT allow anyone to change status of a physical item that is 'IN PROCESS'	
								//do NOT allow < Staff to change Copyright status	
								
								if ((($u->getRole() == $g_permission['proxy'] || $u->getRole() == $g_permission['instructor']) 
									&& ($reserve->getStatus() == 'ACTIVE' || $reserve->getStatus() == 'INACTIVE'))
									|| ($u->getRole() >= $g_permission['staff'] && !($reserve->item->isPhysicalItem() && $reserve->getStatus() == 'IN PROCESS' )))
								{
									if($_REQUEST['reserve_status'] == 'DENY ALL'){
										$reserve->item->setStatus('DENIED');
										$reserve->setStatus('DENIED');
									}
									else{
										$reserve->item->setStatus('ACTIVE');
										$reserve->setStatus($_REQUEST['reserve_status']);
									}
									
								}
							}
							
							if(isset($_REQUEST['item_status'])){
								$reserve->item->setStatus($_REQUEST['item_status']);
							}
							
							//edit dates
							if(isset($_REQUEST['edit_dates'])) {
								//do not change dates of a heading
								if(!$reserve->isHeading()) {
									if(!empty($_REQUEST['reserve_activation_date'])) {
										$reserve->setActivationDate($_REQUEST['reserve_activation_date']);
									}
									if(!empty($_REQUEST['reserve_expiration_date'])) {
										$reserve->setExpirationDate($_REQUEST['reserve_expiration_date']);
									}
								}	
							}

						}	//end reserves and decendants loop	
					}
					
					//changes to parent headings and notes do not apply to descendants of a heading					
					if(isset($_REQUEST['submit_edit_multiple']) && (isset($_REQUEST['edit_heading']) || isset($_REQUEST['edit_note']))) {
						//go only through the selected reserves
						foreach($reserve_ids as $reserve_id) {
							//init reserve object
							$reserve = new reserve($reserve_id);
							
							//edit heading
							if(isset($_REQUEST['edit_heading']) && !empty($_REQUEST['heading_select'])) {
								$reserve->setParent($_REQUEST['heading_select'], true);
							}
							
							//add note
							if(isset($_REQUEST['edit_note']) && !empty($_REQUEST['note_text'])) {
								noteManager::saveNote('reserve', $reserve->getReserveID(), $_REQUEST['note_text'], $_REQUEST['note_type']);
							}							
						}
					}
					
					//go back to editClass
					$_REQUEST = array();
					$_REQUEST['ci'] = $ci->getCourseInstanceID();	//pass CI to editClass
                    $this->_delegateManager = new classManager('editClass', $u, null, null);
				}			
			break;
			default:
				parent::__construct($cmd, $request);
		}
	}
	
	public function addReserveAction()
	{
		$permissions = Rd_Registry::get('root:userPermissionLevels');
		$userInterface = Rd_Registry::get('root:userInterface');
		$this->_setTab('addReserve');
		$this->_setLocation('add a reserve');
		Rd_Help::setDefaultArticleId(15);
		$ci = (array_key_exists('ci', $_REQUEST) 
			&& '' != trim($_REQUEST['ci']) 
			&& 0 < intval($_REQUEST['ci'])
			? intval($_REQUEST['ci'])
			: false); //#TODO should be NULL, also this is in the Registry :P
		
		if ($this->user->getRole() >= Account_Rd::LEVEL_STAFF) {
			$this->displayFunction = 'displayStaffAddReserve';
			$this->argList = array($ci);
		} else if ($this->user->getRole() >= Account_Rd::LEVEL_PROXY) { //2 = proxy
			if($ci) {
				$this->displayFunction = 'displaySearchItemMenu';
				$this->argList = array($ci);	
			} else {
				$courses = $userInterface->getCourseInstancesToEdit();				
				$this->displayFunction = 'displaySelectClass';					
				$this->argList = array('addReserve', $courses);	
			}
		}
	}
	
	protected function _addMultipleReserves($cmd, $user)
	{
		global $g_permission;
		$u = Rd_Registry::get('root:userInterface');
		$this->_setTab('addMultipleReserves');
		$this->_setLocation('add reserves');
		Rd_Help::setDefaultArticleId(999);  //#TODO make stub help article since we don't support the help system?
		if(array_key_exists('items', $_REQUEST) && is_array($_REQUEST['items'])){
			$itemList = array();
			foreach($_REQUEST['items'] as $itemId){
				if('' != trim($itemId)){
					$itemList[] = trim($itemId);
				}
			}
			$_REQUEST['items'] = implode('+',$itemList);
		} else if(array_key_exists('items', $_REQUEST)){
			$itemList = explode('+',str_replace(' ','+',$_REQUEST['items']));
			$cleanItemList = array();
			foreach($itemList as $itemId){
				if('' != trim($itemId)){
					$cleanItemList[] = trim($itemId);
				}
			}
			$_REQUEST['items'] = implode('+',$cleanItemList);
		}
		if ($user->getRole() >= $g_permission['proxy']) { //2 = proxy
			if(!array_key_exists('items', $_REQUEST) 
				|| '' == trim($_REQUEST['items'])){
				$this->displayFunction = 'displayGoToCatalog';
				return;
			} else if(
				!array_key_exists('identifier', $_REQUEST) 
				|| ('catkey' != $_REQUEST['identifier']
					&& 'itemid' != $_REQUEST['identifier']
				)
			) {
				$this->displayFunction = 'displayError';
				$this->argList = array($cmd, 'Unsupported Identifier');	
				return;					
			} 
			
			if('catkey' == $_REQUEST['identifier']){
				$importResults = Rd_Synchronizer_Item::importCatKeys($_REQUEST['items'], array('duplicate'=>true));
				//print_r($importResults);
				if (!$importResults['importedAllItems']) {
					$this->displayFunction = 'displayFailedImportItems';
					$unImportedItems = Rd_Synchronizer_Item::catKeysNotInRd($_REQUEST['items']);
					$this->argList = array($cmd, $unImportedItems, $importResults);
					return;
				}
				$itemIds = array();
				foreach($importResults['importedItems'] as $importItem){
					if(array_key_exists('item_id', $importItem) 
						&& !is_null($importItem['item_id'])
						&& '' != $importItem['item_id']
						&& $importItem['item_id'] > 0) {
						$itemIds[] = $importItem['item_id'];
					}
				}
			} else {
				$itemIds = array();
				$requestedItems = preg_split('/[\+\s]/',$_REQUEST['items']);
				foreach($requestedItems as $item){
					$itemId = intval($item);
					if ($itemId > 0){
						$itemIds[] = $itemId;
					}
				}
			}
			
			if(!array_key_exists('ci', $_REQUEST) || '' == trim($_REQUEST['ci'])) {	//get ci
				$courses = $u->getCourseInstancesToEdit();				
				$this->displayFunction = 'displaySelectClass';					
				$this->argList = array($cmd, $courses,null,array('identifier'=>'itemid','items'=>$itemIds));						
			} else {
				$items = array();
				foreach($itemIds as $itemId){
					$items[] = new reserveItem($itemId);
				}
				if(!array_key_exists('save', $_REQUEST) || 'true' != trim($_REQUEST['save'])) {
					$this->displayFunction = "createMultipleReserves"; 
					$this->argList = array($_REQUEST['ci'],$items,array());
				} else {
					$this->displayFunction = "createMultipleReserves";
					$valid = $this->_validateAddMultipleReservesForm($_REQUEST);
					$itemsStored = $this->_extractMultipleReserveItems($_REQUEST,$user);
					foreach($itemsStored as $index=>$reserve){
						$itemsStored[$index] = $reserve->getItem();
					}
					$itemsRejected = $this->_extractRejectedItems($_REQUEST,$itemsStored);
					if($valid && 0 == count($itemsRejected)){
						$this->displayFunction = "storeMultipleReserves";
						$this->argList = array($_REQUEST['ci'],$items,$itemsStored);
					} else {
						$this->argList = array($_REQUEST['ci'],$itemsRejected,$itemsStored);
					}				
				}
			}
		}
	}
	
	protected function _validateAddMultipleReservesForm($data)
	{
		if(!array_key_exists('items',$data)
			|| '' == trim($data['items'])
			|| !array_key_exists('home_library',$data)
			|| '' == trim($data['home_library'])
			|| !array_key_exists('ci',$data)
			|| '' == trim($data['ci'])
		){
			return false;
		}
		return true;
	}

	protected function _extractMultipleReserveItems($data,$user)
	{
		global $g_dbConn;
		if(!array_key_exists('items',$data)
			|| '' == trim($data['items'])
		){
			return array();
		}
		$itemIds = explode('+',str_replace(' ', '+',$data['items']));
		$course = new courseInstance($data['ci']);
		$successfulItems = array();
		//attempt to use transactions
		Rd_Pdo::beginTransaction();
		
		try{
			foreach($itemIds as $itemId){
				if ('physAndElec' == $data['requestType_'.$itemId]){
					$physicalItem = new reserveItem($itemId);
					$controlNumber = $physicalItem->getLocalControlKey();
					
					$newElecRequest = Rd_Synchronizer_Item::importCatKey('NCSU'.$controlNumber, array('duplicate'=>true));

					$successfulItems[] = $this->_extractSingleReserveItem($data,$newElecRequest['item_id'],$course,$user,'SCAN');
					$successfulItems[] = $this->_extractSingleReserveItem($data,$itemId,$course,$user, 'PHYSICAL');
					
				}else{
					$requestType = (
						'physical' == strtolower($data['requestType_'.$itemId]) 
						? 'PHYSICAL' 
						: 'SCAN'
					);
					$successfulItems[] = $this->_extractSingleReserveItem($data,$itemId,$course,$user,$requestType);
				}
			}
		} catch (Exception $e) {
			Rd_Pdo::rollback();
			$successfulItems = array();
			trigger_error("Error Occurred While processing Store Request ".$e->getMessage(), E_USER_ERROR);				
		}
		//commit this set
		Rd_Pdo::commit();
		return $successfulItems;	
	}
	
	protected function _extractSingleReserveItem($data,$itemId,$course,$user, $requestType)
	{
		global $g_notetype;
		//store reserve with status processing
		$reserve = new reserve();
		if ($reserve->createNewReserve($course->getCourseInstanceID(), $itemId))
		{
			$reserve->setStatus("IN PROCESS");
			$reserve->setActivationDate($course->getActivationDate());
			$reserve->setExpirationDate($course->getExpirationDate());
			$reserve->setRequestedLoanPeriod(array_key_exists('loanPeriod_'.$itemId, $data) ? $data['loanPeriod_'.$itemId] : '');
			//attempt to insert this reserve into order
			$item = $reserve->getItem();
			if(array_key_exists('volume_title_'.$itemId,$data) && '' != trim($data['volume_title_'.$itemId])){
				$item->setVolumeTitle($data['volume_title_'.$itemId]);
			}
			if(array_key_exists('times_pages_'.$itemId,$data) && '' != trim($data['times_pages_'.$itemId])){
				$item->setPagesTimes($data['times_pages_'.$itemId]);
			}
			if(array_key_exists('notes_'.$itemId,$data) && '' != trim($data['notes_'.$itemId])){
				$item->setNote($data['notes_'.$itemId], $g_notetype['staff']);
			}
			$reserve->insertIntoSortOrder($course->getCourseInstanceID(), $reserve->item->getTitle(), $reserve->item->getAuthor());

			//create request
			$request = new request();
			//make sure request does not exist
			//prevent duplicate requests
			if($request->getRequestByCI_Item($course->getCourseInstanceID(), $itemId) === false) {
				$request->createNewRequest($course->getCourseInstanceID(), $itemId);
				$request->setRequestingUser($user->getUserID());
				$request->setReserveID($reserve->getReserveID());
				if(array_key_exists('dateNeeded_'.$itemId,$data) && '' != trim($data['dateNeeded_'.$itemId])){
					$request->setDateDesired($data['dateNeeded_'.$itemId]);
				}

				$request->setType($requestType);
				$item->setGroup('PHYSICAL' == $requestType ? 'MONOGRAPH' : 'ELECTRONIC');
				
				if(array_key_exists('home_library',$data) 
					&& '' != trim($data['home_library'])
					&& 'electronic' != strtolower($requestType)
				){
					$item->setHomeLibraryID($data['home_library']);
				}
			}
		} else {
			$reserve->getReserveByCI_Item($course->getCourseInstanceID(), $itemId);
		}
		return $reserve;
	}
	
	protected function _extractRejectedItems($data,$items)
	{
		if(!array_key_exists('items',$data)
			|| '' == trim($data['items'])
		){
			throw new Exception('Required input "items" not provided.');
		};
		$itemIds = explode('+',str_replace(' ', '+',$data['items']));
		$returnArray = array();
		$successfulItemIds = array();
		foreach($items as $item){
			$successfulItemIds[] = $item->getItemID();
		}
		foreach($itemIds as $itemId){
			if(!in_array($itemId, $successfulItemIds)
				|| is_array($itemId) 
				|| intval($itemId) <= 0
			) {
				$returnArray[] = new reserveItem($itemId);
			}
		}
		return $returnArray;
	}
}
