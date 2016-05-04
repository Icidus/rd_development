<?php	
/*******************************************************************************
AJAX_functions.php
returns data for ajax data fields

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

require_once(APPLICATION_PATH . '/headers.inc.php');
require_once(APPLICATION_PATH . '/config.inc.php');
require_once(APPLICATION_PATH . '/classes/copyright.class.php');
require_once(APPLICATION_PATH . '/classes/department.class.php');
require_once(APPLICATION_PATH . '/classes/users.class.php');
require_once(APPLICATION_PATH . '/classes/terms.class.php');
require_once(APPLICATION_PATH . '/managers/noteManager.class.php');
require_once(APPLICATION_PATH . '/managers/copyrightManager.class.php');
require_once(APPLICATION_PATH . '/managers/helpManager.class.php');
require_once(APPLICATION_PATH . '/managers/requestManager.class.php');
require_once(APPLICATION_PATH . '/displayers/noteDisplayer.class.php');
require_once(APPLICATION_PATH . '/common.inc.php');


//set up error-handling/debugging, skins, etc.

//authenticate user
//if user is valid, then initializes global user object as $u
//else shows login page

Rd_Auth::autodetect();

//process passed arguments
$f = $_REQUEST['f'];
$qry = (isset($_REQUEST['qu'])) ? base64_decode($_REQUEST['qu']) : null;
$rf  = (isset($_REQUEST['rf'])) ? base64_decode($_REQUEST['rf']) : null;


switch ($f)
{
	case 'deptList':			
		$dept = new department();
		$depts = $dept->findByPartialName($qry);
		
		$returnValue = xmlHead();
		
		if (count($depts) > 0)					
			foreach($depts as $d)
				$returnValue .=	wrapResults(json_encode($d), $d['abbreviation'] . ' - ' . $d['name']);
		
		$returnValue .= xmlFoot();		
	break;
	
	case 'libList':
		$library = new library($qry);
		
		$data = array (
			'id'   => $library->getLibraryID(),
			'name' => $library->getLibrary(),
			'nickname' => $library->getLibraryNickname(),
			'ils_prefix' => $library->getILS_prefix(),
			'desk' => $library->getReserveDesk(),
			'url' => $library->getLibraryURL(),
			'email' => $library->getContactEmail(),
			'monograph_library_id' => $library->getMonograph_library_id(),
			'multimedia_library_id' => $library->getMultimedia_library_id()
		);		
		
		$returnValue = base64_encode(json_encode($data));
	break;
	
	case 'userList':
		//get the role - all by default
		$min_role = (
			array_key_exists('role', $_REQUEST) 
				&& is_numeric($_REQUEST['role']) 
			? (int)$_REQUEST['role'] 
			: 0
		);
		
		$usersObj = new users();
		$usersObj->search(null, $qry, $min_role);
		
		$returnValue = xmlHead();
		
		if (isset($usersObj->userList) && count($usersObj->userList) > 0){			
			foreach($usersObj->userList as $usr){
				$returnValue .=	wrapResults(json_encode($usr), $usr->getName() . ' -- ' . $usr->getUsername());			
			}
		}
		$returnValue .= xmlFoot();
	break;
	
	case 'courseList':	
		$usersObj = new users();
		$courses = $usersObj->searchForCourses($qry);
					
		$returnValue = xmlHead();
		
		foreach($courses as $info) {
			//show num and name or just name?
			$label = !empty($info['num']) ? $info['num'].' - '.$info['name'] : $info['name'];
			
			$returnValue .= wrapResults(json_encode($info), $label);
		}

		$returnValue .= xmlFoot();		
	break;			

	case 'classList':
		/*
			Expects $_REQUEST['qry'] to be base64 encode '::' delimited string
			instructor_id :: department_id :: course_num :: course_name :: term_id :: ci_variable
			ANY values can be empty			
		*/
	
		list($user_id, $dept_id, $course_num, $course_name, $term_id, $ci_variable) = explode('::', $qry);			
		
		$userObj = new users();
		$ci_list = $userObj->searchForCI($user_id, $dept_id, $course_num, $course_name, $term_id);
		$returnValue = '';
		if(sizeof($ci_list) > 0) {
			//display table header
			$returnValue .= "<div align=\"left\" class=\"headingCell1\">\n";
			$returnValue .= "	<div align=\"left\" style=\"width:60px; float:left;\">&nbsp;</div>\n";
			$returnValue .= "	<div align=\"left\" style=\"width:15%; float:left;\">Course Number</div>\n";
			$returnValue .= "	<div align=\"left\" style=\"width:30%; float:left;\">Course Name</div>\n";
			$returnValue .= "	<div align=\"left\" style=\"width:25%; float:left;\">Instructor</div>\n";
			$returnValue .= "	<div align=\"left\" style=\"width:14%; float:left;\">Last Active</div>\n";
			$returnValue .= "	<div align=\"left\" style=\"width:55px; float:right; padding-right:5px;\">Preview</div>\n";
			$returnValue .= "	<div style=\"clear:both;\" class=\"headingCell1\"></div>\n";
			$returnValue .= "</div>\n";		
			
			foreach($ci_list as $ci) {
				//show status icon
				switch($ci->getStatus()) {
					case 'AUTOFEED':
						$edit_icon = '<img src="public/images/activate.gif" width="24" height="20" />';	//show the 'activate-me' icon
					break;
					case 'CANCELED':
						$edit_icon = '<img src="public/images/cancel.gif" alt="edit" width="24" height="20">';	//show the 'activate-me' icon
					break;
					default:
						$edit_icon = '<img src="public/images/pencil.gif" alt="edit" width="24" height="20">';	//show the edit icon
					break;						
				}
								
				//get crosslistings
				$ci->getCourseForUser();
				try{
					$crosslistings = $ci->getCrossListings();
					$crosslistings_string = '';
					foreach($crosslistings as $crosslisting) {
						$crosslistings_string .= ', '.$crosslisting->displayCourseNo().' '.$crosslisting->getName();
					}
					$crosslistings_string = ltrim($crosslistings_string, ', ');	//trim off the first comma
				} catch (Rd_Exception $e){
					$crosslistings_string = 'An error occured getting the crosslistings for this course: Error ' .$e->getCode();
				}

				
				//being "output"					
				$rowStyle = (empty($rowStyle) || ($rowStyle=='evenRow')) ? 'oddRow' : 'evenRow';	//set the style
								
				$returnValue .= "<div align=\"left\" class=\"$rowStyle\" style=\"padding:5px;\">\n";					
				$returnValue .= "	<div align=\"left\" style=\"width: 30px; float:left; text-align:left;\"><input name=\"".$ci_variable."\" type=\"radio\" value=\"".$ci->getCourseInstanceID()."\" onClick=\"document.getElementById('editButton').disabled=false\"></div>\n";
				$returnValue .= '	<div style="width: 30px; float:left; text-align:left">'.$edit_icon.'</div>';
				$returnValue .= "	<div align=\"left\" style=\"width:15%; float:left;\">".$ci->course->displayCourseNo()."&nbsp;</div>\n";
				$returnValue .= "	<div align=\"left\" style=\"width:30%; float:left;\">".$ci->course->getName()."&nbsp;</div>\n";
				$returnValue .= "	<div align=\"left\" style=\"width:25%; float:left;\">".$ci->displayInstructors()."&nbsp;</div>\n";
				$returnValue .= "	<div align=\"left\" style=\"width:14%; float:left;\">".$ci->displayTerm()."&nbsp;</div>\n";
				$returnValue .= "	<div align=\"left\" style=\"width:55px; float:right;\"><a href=\"javascript:openWindow('no_control=1&cmd=previewReservesList&ci=".$ci->getCourseInstanceID(). "','width=800,height=600');\">preview</a></div>\n";
				$returnValue .= "	<div style=\"clear:both;\">";
				
				if(!empty($crosslistings_string)) {
					$returnValue .= "<div style=\" margin-left:30px; padding-top:5px;\"><em>Crosslisted As:</em> <small>$crosslistings_string</small></div>";
				}
				
				$returnValue .= "	</div>\n";
				$returnValue .= "</div>\n";
			}
		} else {
			$returnValue .= "<div align=\"center\" class=\"failedText\">No Matches Found.</div>\n";
		}
	break;
	
	case 'termsList':
		$t = new terms();
		$returnValue = json_encode($t->getTerms(true));
	break;
	

	case 'fetchNotes':
		//parse the request
		parse_str(base64_decode($_REQUEST['query']), $request);
		
		//fetch notes
		$notes = noteManager::fetchNotesForObj($request['obj_type'], $request['id'], true);
		
		//start output buffering
		ob_start();
		//output edit-note blocks (table rows)
		noteDisplayer::displayNotesContentAJAX($notes, $request['obj_type'], $request['id']);
		//grab the content for return
		$returnValue = ob_get_contents();
		//end buffering
		ob_end_clean();		
	break;

	
	case 'saveNote':	
		//parse the request
		parse_str(base64_decode($_REQUEST['query']), $request);	
		//save note
		noteManager::saveNote($request['obj_type'], $request['id'], $request['note_text'], $request['note_type'], $request['note_id']);	
	break;
	
	case 'deleteNote':
		//parse the request
		parse_str(base64_decode($_REQUEST['query']), $request);
		//delete note
		noteManager::deleteNote($request['id'], $request['obj_type'], $request['obj_id']);
	break;
	
	case 'copyrightContactList':
		//search for contacts
		$copyright = new Copyright();
		$contacts = $copyright->findContacts($qry);
		
		//add xml header
		$returnValue = xmlHead();
		
		//add contacts to result as a li
		foreach($contacts as $contact) {
			$returnValue .= wrapResults(json_encode($contact['contact_id']), $contact['org_name']);
		}
	break;
	
	case 'fetchCopyrightContact':
		//parse the request
		parse_str(base64_decode($_REQUEST['query']), $request);
		
		//search for contacts
		$copyright = new Copyright();
		$contact = $copyright->getContact($request['contact_id']);	
			
		//return info
		$returnValue = json_encode($contact);
	break;
	
	case 'saveCopyrightContact':
		//parse the request
		parse_str(base64_decode($_REQUEST['query']), $request);
		
		//init a blank object
		$copyright = new Copyright();
		//save contact
		$copyright->saveContact($request['org_name'], $request['contact_name'], $request['address'], $request['phone'], $request['email'], $request['www'], $request['contact_id']);
	break;
	
	case 'setCopyrightContact':
		//parse the request
		parse_str(base64_decode($_REQUEST['query']), $request);
		
		//set some vars
		$_REQUEST['item_id'] = $request['item_id'];
		$_REQUEST['contact_id'] = $request['contact_id'];
		
		copyrightManager::setContact();
	break;
	
	case 'fetchHelpTags':
		//parse the request
		parse_str(base64_decode($_REQUEST['query']), $request);
		
		$returnValue = helpManager::getTags($request['article_id']);
	break;
	
	case 'saveHelpTags';
		//parse the request
		parse_str(base64_decode($_REQUEST['query']), $request);
		
		helpManager::setTags($request['article_id'], $request['tags_string']);
	break;
	
	case 'storeRequest':
		//parse the request
		parse_str(base64_decode($_REQUEST['query']), $request);
		
		//actually need all the data in $_REQUEST for storeReserve() to work, so we'll replace it
		$_REQUEST = $request;
		
		//create the reserve
		/* ejl - 8/19/08 - $data is a hash of ['reserve_id']['ils_results']
			this array will never be false; always populated with output from ILS */
		try{
			
			if(($data = requestManager::storeReserve()) !== false) {
				$reserve = new reserve($data['reserve_id']);
				$reserve->getItem();
				
				//duplicate links for digital items
				$duplicate = !$reserve->item->isPhysicalItem() ? $duplicate = true : false;
				
				//build return message
				if($reserve->item->isVideoItem()){
					//Video reserves are stored on a streaming sever, but a web page is created for each video reserve which is used for the streaming video player.  This page is stored in the RD database.
					$returnValue = '<div class="borders" style="margin:10px; padding:10px; background:lightgreen; text-align:center"><strong>Video reserve has been submitted successfully.<br />You will be notified via email when the file has been processed for streaming</strong>';
				}
				else{
					/* ejl - 8/19/08 - temporary fix - show output from ILS request whether successful or not */
					$returnValue = '<div class="borders" style="margin:10px; padding:10px; background:lightgreen; text-align:center">' . $data['ils_results'];
				}
				
				//show "duplicate" links for non-physical items.
				//show "Return to the request queue" links for physical items.
				if(!$reserve->item->isPhysicalItem()) {
					$returnValue .= '<p />You may: <ul>
						<li><a href="index.php?cmd=editClass&amp;ci=' . $reserve->getCourseInstanceID() . '"> Go to class</a></li>
						<li><a href="index.php?cmd=addPhysicalItem&amp;ci=' . $reserve->getCourseInstanceID() . '">Add another physical item to this class</a></li>
						<li><a href="index.php?cmd=addDigitalItem&amp;ci=' . $reserve->getCourseInstanceID() . '">Add another electronic item to this class</a></li>
						<li><a href="index.php?cmd=addVideoItem&amp;ci=' . $reserve->getCourseInstanceID() . '">Add another video item to this class</a></li>
						<li><a href="index.php?cmd=duplicateReserve&amp;reserveID='.$reserve->getReserveID().'">Duplicate this item and add copy to the same class</a></li>
						</li></ul><small>Note: clicking this link will take you away from this screen</small>';
				}
				else{
					$returnValue .= '<p />You may: <ul>
						<li><a href="index.php?cmd=editClass&amp;ci=' . $reserve->getCourseInstanceID() . '"> Go to class</a></li>
						<li><a href="index.php?cmd=addPhysicalItem&amp;ci=' . $reserve->getCourseInstanceID() . '">Add another physical item to this class</a></li>
						<li><a href="index.php?cmd=addDigitalItem&amp;ci=' . $reserve->getCourseInstanceID() . '">Add another electronic item to this class</a></li>
						<li><a href="index.php?cmd=addVideoItem&amp;ci=' . $reserve->getCourseInstanceID() . '">Add another video item to this class</a></li>
						<li><a href="index.php?cmd=displayRequest">Go back to the request queue</a></li>
						</li></ul><small>Note: clicking this link will take you away from this screen</small>';
				}
				if(!$reserve->item)
				
				$returnValue .= '<p /><a href="index.php?cmd=editClass&ci='.$reserve->getCourseInstanceID().'">Return to Edit Class.</a><br />';
				
				$returnValue .= '</div>';
			}
			else {
				$returnValue = '<div class="borders" style="margin:10px; padding:10px; background:#FF9900; text-align:center"><strong>Problem creating reserve.</strong>';
			}
		} catch (Exception $e) {
			$returnValue = '<div class="borders"><p>' . $e->getMessage() . '</p><p>You may re-attempt this request by hitting "reload" in your browser and accepting the request to "repost data" or "resubmit the form".</p></div>'; #TODO asking the user to repost data isn't ideal by any means. this needs to be re-written at the JS level to solve.
		}
	break;
	
	case 'updateRequestStatus':
		parse_str($rf, $args);		
		$r = new request($args['request_id']);
		$r->setStatus($args['status']);
		$returnValue = '<img src="public/images/check.png" />';
	break;

	default:
		return null;
}




print($returnValue);

function xmlHead(){	return "<?xml version='1.0' encoding='utf-8'  ?><ul class=\"LSRes\">";	}
function xmlFoot(){ return "</ul>"; }

function wrapResults($value, $option)
{
	return "<li class=\"LSRow\" onmouseover='liveSearchHover(this)' onclick='liveSearchClicked(this, \"". base64_encode($value)."\")'>$option</li>";
}

	


