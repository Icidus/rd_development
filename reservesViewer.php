<?php
/*******************************************************************************
reservesViewer.php
controls and logs user access to reserves items

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
if(file_exists('localize.php')){
	require_once('localize.php');
}

require_once('DefineLoad.php');
require_once('constants.php');
require_once(APPLICATION_PATH . '/lib/FileExistsInPath.php');

require_once(APPLICATION_PATH . '/config.inc.php');
require_once(APPLICATION_PATH . '/common.inc.php');
require_once(APPLICATION_PATH . '/classes/reserves.class.php');
require_once(APPLICATION_PATH . '/classes/reserveItem.class.php');
require_once(APPLICATION_PATH . '/classes/proxyHost.class.php');
require_once(APPLICATION_PATH . '/classes/user.class.php');
require_once(APPLICATION_PATH . '/classes/skins.class.php');

//set up error-handling/debugging, skins, etc.
require_once(APPLICATION_PATH . '/session.inc.php');	

//authenticate user
//if user is valid, then initializes global user object as $u
//else shows login page
require_once(APPLICATION_PATH . '/debug.inc.php');
Rd_Auth::autodetect();	
if (!Account_Rd::atLeastStudent())
{
	Rd_Dispatch::setStatus(403);
}

function changeReserveId(){
	global $reserve;
	$u = Account_Rd::getUserInterface();
	$currentDate = date("Y-m-d");
	$newCourseInstanceId = 0;
	$currentCourseInstanceId = $reserve->getCourseInstanceID();
	$itemId = $reserve->itemID;
	// Get the courses the user is enrolled in, if, for each course: 
	//((all access levels), today is after activation date, today is before expiration date, course is active, user is approved for course)
	$currentItem = new reserveItem($reserve->itemID);

	// Get all course instances associated with the user.
	$userCourseInstances = $u->fetchCourseInstances(null, $currentDate, $currentDate, 'ACTIVE', 'APPROVED');

	// Get all course instances associated with the reserve item.
	$itemCourseInstances = $currentItem->getAllCourseInstances();

	// For each item course instance and user course instance, compare the two and see if they are the same. 
	// If there are any matches, then return the matching course instance ID as the new course instance ID.
	foreach ($userCourseInstances as $userClass) {
		$userCourseInstanceId = $userClass->courseInstanceID;
		foreach ($itemCourseInstances as $itemClass) {
			$itemCourseInstanceId = $itemClass->courseInstanceID;
			if ($userCourseInstanceId == $itemCourseInstanceId) {
				$newCourseInstanceId = $userCourseInstanceId;
				break 2;
			}
		}
	}

	// If no match is found, just return the original file and let failure take place as normal.
	if($newCourseInstanceId == 0){
		$newCourseInstanceId = $currentCourseInstanceId;
	}

	return array ($newCourseInstanceId, $itemId);
}

header("Cache-Control: public"); // DON'T CHANGE THESE
header("Pragma: public"); // SERIOUSLY DON'T
header("Expires: -1");	
$u = Account_Rd::getUserInterface();
//get the reserve/item
//When previewing items we do not have a reserve_id and must use the item_id to view the URL
//This should only be accessed by staff/admin and will not be tracked
if (array_key_exists('reserve', $_REQUEST)) {	//requesting reserve
	$reserve = new reserve($_REQUEST['reserve']);
	
	// Search through reserves to see if user has any current access for this item.
	list($courseInstanceId, $itemId) = changeReserveId();
	if ($reserve->getReserveByCI_Item($courseInstanceId, $itemId) != false) {
	}
	// End reserve substitution

	if($reserve->getItemForUser($u)) {	//make sure this user should be allowed access to the reserve
		$item =& $reserve->item;	//grab the item for info
	}	
	
    if ($reserve->getStatus() == 'DENIED'
        || ($u->getRole() < $g_permission['proxy'] && $reserve->getStatus() != 'ACTIVE'))
    {
        Rd_Dispatch::setStatus(403);
    }

	//since a reserve was requested, track the views
	if($u->getRole() < $g_permission['instructor']) {	//only count student views
		$reserve->addUserView($u->getUserID());	//log user, reserve and access time
	}
} else if (array_key_exists('item', $_REQUEST) 
	&& ($u->getRole() >= $g_permission['proxy'])
) {	//requesting item		
	$item = new reserveItem($_REQUEST['item']);	//grab the item for info
} 

//if we have an item object, then we try to serve the doc
if($item instanceof reserveItem) {
    if ($item->getStatus() == 'DENIED')
    {
        Rd_Dispatch::setStatus(403);
    }

	$url = $item->getURL();	//grab the url
    if ($url == FALSE) {        	
        Rd_Dispatch::setStatus(404);
        exit;
    }
	
	if($item->isLocalFile()) {	//if item URL points to local server, serve the document directly
		if(file_exists($g_documentDirectory . $url) && $stream = @fopen($g_documentDirectory . $url, "rb")) {       //open file for reading

            $author = preg_replace('/[^A-Za-z0-9]/', '', $item->author);
            if ($author != "") {
                $author = substr(($author), 0, 24) . "_";
            }
            
            $title = preg_replace('/[^A-Za-z0-9]/', '', $item->title);
            if ($title != "") {
                $title = substr(($title), 0, 24) . "_";
            }
			$urlParts = explode('.', $url);
            $ext = end($urlParts);
            if ('' != $ext) {
                $ext = '.' . $ext;
            }

            $filename = $author . $title . $item->itemID . $ext;

			//serve the doc			
			$mimetype = $item->getMimeType();
			header('Content-Type: '.$mimetype);
			if ($mimetype == 'audio/x-pn-realaudio' || strpos('image/', $mimetype) === 0) {
				header('Content-Disposition: attachment; filename="'.$filename.'"');
			}
			elseif (isset($_SESSION['mobile']) && $_SESSION['mobile'] == "true"){
				header('Content-Disposition: attachment; filename="'.$filename.'"');
			}
			 else {
				header('Content-Disposition: inline; filename="'.$filename.'"');
			}
			header("Accept-Ranges: bytes");
			header("Cache-control: private");	//send some headers
			header("Pragma: public");
			fpassthru($stream);
			fclose($stream);	//close file
		}
		else {	//file not found
			header("Cache-control: private");	//send some headers
			header("Pragma: public");
			Rd_Dispatch::setStatus(404);
		}
	}
	else {	//item is on remote server -- redirect	
		//echo 'Location: '.proxyHost::proxyURL($url, $u->getUsername());exit;			
		header("Cache-control: private");	//send some headers
	    header("Pragma: public");
		header('Location: ' . proxyHost::proxyURL($url, $u->getUsername()));
	}
}
else {	//no item, assume that no ID was specified
	header('"HTTP/1.0 403 Permission Denied');
	Rd_Dispatch::setStatus(403);
}

