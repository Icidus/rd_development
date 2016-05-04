<?php
/*******************************************************************************
Rd/Dispatch.php
Implements an Front Controller for RD

Created by Troy Hurteau, NCSU Libraries, NC State University (libraries.opensource@ncsu.edu).

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
/**
 * 
 * Front Controller utility class.
 * @author jthurtea
 *
 */

require_once(APPLICATION_PATH . '/lib/Rd/Exception/RedirectWorkflow.php');

class Rd_Dispatch{

	const COMMAND_KEY_DEFAULT = 'viewCourseList';
	
	public static function getManager($cmdString, $request = NULL)
	{
		global $ci; //#TODO clean these global references up.
		$u = Account_Rd::getUserInterface();
		$mgr = null;
		switch ($cmdString)
		{
			case 'viewReservesList': // myReserves Reserve List
			case 'previewReservesList':
			case 'previewStudentView';
			case 'sortReserves':
			case 'customSort':
			case 'selectInstructor': //addReserve Staff Interface - Search for Class by Instructor or Dept
			case 'addReserve': //add a reserve to a class
			case 'addMultipleReserves': //add a reserve to a class
			case 'searchScreen': //addReserve - Search for Item
			case 'searchResults': //addReserve - Search Results Screen
			case 'storeReserve': //addReserve - Store Reserves Screen
			case 'uploadDocument': //addReserve - upload Document Screen
			case 'addURL': //addReserve - add a URL screen
			case 'placeRequest': // addReserve - instructor enters a request into the processing queue
			case 'storeUploaded': //addReserve page - Store uploaded document
			case 'faxReserve': //addReserve - Fax Reserve Screen
			case 'getFax': //addReserve - Claim Fax Screen
			case 'addFaxMetadata': //addReserve - Fax Meta Data Screen
			case 'storeFaxMetadata': //addReserve - Store Fax Meta Data Screen
			case 'editMultipleReserves':	//edit common reserve data for multiple reserves in a class
				require_once(APPLICATION_PATH . '/managers/reservesManager.class.php');
				$mgr = new reservesManager($cmdString, $_REQUEST, $u);
				break;
		
		
			case 'myReserves':
			case 'viewCourseList':
			case 'activateClass':
			case 'deactivateClass':
			case 'manageClasses':
			case 'editProxies':
			case 'editInstructors':
			case 'editCrossListings':
			case 'editTitle':
			case 'editClass':			// manageClass edit class
			case 'createClass':			// manageClass create class (enter meta-data)
			case 'createNewClass':		// manageClass create class (store meta-data to DB)
			case 'addClass':			// myReserves - add a class as a student
			case 'removeClass':			// myReserves - remove a class you are a student in
			case 'deleteClass':
			case 'confirmDeleteClass':
			case 'deleteClassSuccess':
			case 'copyItems':
			case 'processCopyItems':
				require_once(APPLICATION_PATH . '/managers/classManager.class.php');
				if (is_null($request) || 0 == count($request)) {
					$mgr = new classManager($cmdString, $u, null, $_REQUEST);
				} else {
					$mgr = new classManager($cmdString, $request);
				}
				
				break;
		
			case 'manageUser':
			case 'newProfile':
			case 'editProfile':
			case 'editUser':
			case 'mergeUsers':
			case 'addUser':
			case 'assignProxy':
			case 'assignInstr':
			case 'setPwd':
			case 'resetPwd':
			case 'removePwd':
			case 'setGuest':
			case 'addProxy':
			case 'removeProxy':
				require_once(APPLICATION_PATH . '/managers/userManager.class.php');
				$mgr = new userManager($cmdString, array(), array('960css','jQuery','jQueryUi','noPrototype'));
				break;
		
			case 'editItem':
			case 'editHeading':
			case 'processHeading':
			case 'duplicateReserve';
				require_once(APPLICATION_PATH . '/managers/itemManager.class.php');
				$mgr = new itemManager($cmdString, $u);
				break;
		
			case 'displayRequest':
			case 'setStatus':
			case 'storeRequest':
			case 'deleteRequest':
			case 'printRequest':
			case 'addDigitalItem':
			case 'addPhysicalItem':
			case 'addVideoItem':
			case 'addVideoItem2':
				require_once(APPLICATION_PATH . '/managers/requestManager.class.php');
				$mgr = new requestManager($cmdString, $_REQUEST, $u, $ci);
				break;
		
			case 'copyClass':
			case 'copyClassOptions':
			case 'copyExisting':
			case 'copyNew':
			case 'importClass':			//import reserves list from one ci to another
			case 'processCopyClass':
				require_once(APPLICATION_PATH . '/managers/copyClassManager.class.php');
				$mgr = new copyClassManager($cmdString, $u, $_REQUEST);
				break;
		
			case 'addNote':
			case 'saveNote':
				require_once(APPLICATION_PATH . '/managers/noteManager.class.php');
				$mgr = new noteManager($cmdString, $u);
				break;
		
			case 'exportClass':
			case 'generateBB':
				require_once(APPLICATION_PATH . '/managers/exportManager.class.php');
				$mgr = new exportManager($cmdString);
				break;
		
			case 'searchTab':
			case 'doSearch':
			case 'addResultsToClass':
				require_once(APPLICATION_PATH . '/managers/searchManager.class.php');
				$mgr = new searchManager($cmdString, $u, $_REQUEST);
				break;
		
			case 'reportsTab':
			case 'viewReport':
				Rd_Layout_Tab::set('reports');
				require_once(APPLICATION_PATH . '/managers/reportManager.class.php');
				$mgr = new reportManager($cmdString, $_REQUEST, array('960css'));
				break;
		
			case 'admin':
			case 'switchUser':
			case 'testMxe':
			case 'manualCron':
				Rd_Layout_Tab::set('admin');
				require_once(APPLICATION_PATH . '/managers/adminManager.class.php');
				$mgr = new adminManager($cmdString, $_REQUEST, $u);
				break;
		
			case 'help':
			case 'helpViewArticle':
			case 'helpEditArticle':
			case 'helpViewCategory':
			case 'helpEditCategory':
			case 'helpViewTag':
			case 'helpSearch':	
			case 'helpSetRelated':
				Rd_Layout_Tab::set('help');
				require_once(APPLICATION_PATH . '/managers/helpManager.class.php');
				$mgr = new helpManager($cmdString);
				break;
			
			case 'logout' :
			case 'resetPassword' :
			case 'resetPasswordRequest' :
				Rd_Layout_Tab::set('account');
				require_once(APPLICATION_PATH . '/managers/Auth.php');
				$mgr = new Rd_Manager_Auth(
					$cmdString, 
					$_REQUEST, 
					array('jQuery','noPrototype')
				);
				break;
				
		
			default:
				Rd_Layout_Tab::set('ERROR');
				require_once(APPLICATION_PATH . '/managers/errorManager.php');
				$mgr = new errorManager('notFound');
		}
		
		if (method_exists($mgr,'getDelegate')) { // #TODO make this more automatic and seamless.
		   $mgr = $mgr->getDelegate();
		}
		
		return $mgr;
	}
	
	public static function redirect($url, $keepInHistory = true)
	{
		if (Rd_Debug::isEnabled()) {
			Rd_Debug::out('Intercepting auto-redirect to:');
			$htmlSafeUrl = htmlentities($url);
			print("<a class=\"redirectLink\" href=\"{$url}\">{$htmlSafeUrl}</a>");
			Rd_Debug::dieSafe();
		} else {
			if (!$keepInHistory) {
				header("HTTP/1.0 302 Found"); //#TODO decide when 303 See Other is safe instead.
			}
			header("Location: {$url}");
			die();
		}
	}
	
	public static function setStatus($code)
	{
		switch ($code)
		{
			case 403:
			case '403':
				header('HTTP/1.0 403 Forbidden');
	            break;
	        case 404:
	        case '404':
	            header('HTTP/1.0 404 Not Found');
	            break;
	        default:
	            header("HTTP/1.0 {$code}");	        
		}
        include(APPLICATION_PATH . '/html/denied.php'); //#TODO handle other kinds of statuses with different content pages...
		die;
	}
	
	public static function getAgentCommand()
	{
		try {
			return Rd_Registry::get('root:requestCommand');
		} catch (Exception $e) {
			return '';
		}
	}
	
	public static function getRootCommand()
	{
		try {
			$cmdStack = Rd_Registry::get('root:commandStack');
			return $cmdStack[0];
		} catch (Exception $e) {
			return '';
		}
	}
}