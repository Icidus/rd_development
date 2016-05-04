<?php
/*******************************************************************************
adminManager.class.php

Created by Jason White (jbwhite@emory.edu)
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
require_once(APPLICATION_PATH . '/displayers/adminDisplayer.class.php');
require_once(APPLICATION_PATH . '/classes/department.class.php');
require_once(APPLICATION_PATH . '/classes/library.class.php');
require_once(APPLICATION_PATH . '/classes/terms.class.php');
require_once(APPLICATION_PATH . '/classes/news.class.php');
require_once(APPLICATION_PATH . '/lib/Client/Mxe.php');

class adminManager extends Rd_Manager_Base
{

	//protected $_managerName = 'admin';
	function __construct($cmd, $request=array(), $with=array())//#TODO consolicate with parent constructor
	{
		$u = Rd_Registry::get('root:userInterface');
		$this->displayClass = "adminDisplayer";
		$this->with('960css');

		if($cmd == 'admin'){
			$function = (isset($request['function'])) ? $request['function'] : null;
			
			switch ($function) {
	/* Departments */			
				case 'editDept':
					$libraries = $u->getLibraries();
				
					$this->displayFunction = "displayEditDept";
					$this->argList = array($function, $libraries);			
				break;
				
				case 'saveDept':
					$d_id = (isset($_REQUEST['dept_id']) && $_REQUEST['dept_id'] != "") ? $_REQUEST['dept_id'] : null;
					$dept = new department($d_id);
					
					if (is_null($d_id))
					{
						$dept->createDepartment($_REQUEST['dept_name'], $_REQUEST['dept_abbr'], $_REQUEST['library_id']);
						Rd_Layout::setMessage('generalAlert', "Department Successfully Added");
						$request['function'] = '';
						$this->_delegateManager = new adminManager($cmd, $request, $this->_user);
					} else {
						$dept->setName($_REQUEST['dept_name']);
						$dept->setAbbr($_REQUEST['dept_abbr']);
						$dept->setLibraryID($_REQUEST['library_id']);
						if ($dept->updateDepartment())
						{
							Rd_Layout::setMessage('generalAlert', "Department Successfully Updated");
							//$this->adminManager($cmd, $user, null);
							$request['function'] = '';
							$this->_delegateManager = new adminManager($cmd, $request, $this->_user);
						}
							
					}
				break;
	/* Libraries */			
				case 'editLib':
					$this->displayFunction = "displayEditLibrary";
					$this->argList = array($u->getLibraries());
				break;
				
				case 'saveLib':
					$lib_id = ($_REQUEST['lib_id']  != "") ? $_REQUEST['lib_id'] : null;
					$l = new library($lib_id);
					
					if (is_null($lib_id))
						$l->createNew(
							$_REQUEST['lib_name'],
							$_REQUEST['lib_nickname'],
							$_REQUEST['ils_prefix'],
							$_REQUEST['desk'],
							$_REQUEST['lib_url'],
							$_REQUEST['contactEmail'],
							$_REQUEST['monograph_library_id'],
							$_REQUEST['multimedia_library_id']
						);
					else 
					{
						$l->setLibrary($_REQUEST['lib_name']);
						$l->setLibraryNickname($_REQUEST['lib_nickname']);
						$l->setILS_prefix($_REQUEST['ils_prefix']);
						$l->setReserveDesk($_REQUEST['desk']);
						$l->setLibraryURL($_REQUEST['lib_url']);
						$l->setContactEmail($_REQUEST['contactEmail']);
						$l->setMonograph_library_id($_REQUEST['monograph_library_id']);
						$l->setMultimedia_library_id($_REQUEST['multimedia_library_id']);
						$l->update();
					}
					
					Rd_Layout::setMessage('generalAlert', "Library Successfully Updated");
					//$this->adminManager($cmd, $user, null);
					$request['function'] = '';
					$this->_delegateManager = new adminManager($cmd, $request, $this->_user);
					
				break;
	/* Registrar Key */			
				case 'editClassFeed':
					//Admin will be prompted to select a CI 
					//then if Cross-listings exist for this CI a course_alias (CA) must be selected
					$courses = (array_key_exists('course_aliases', $_REQUEST) && $_REQUEST['course_aliases']) ? $_REQUEST['course_aliases'] : null;
									
					if (!array_key_exists('src_ci', $_REQUEST) || is_null($_REQUEST['src_ci']))
					{
						$this->displayFunction = "displaySelectClass";
						$this->argList = array("admin", null, "", array("function" => "editClassFeed"), false, "src_ci", null);					
					} elseif (is_null($courses)) {				
					
	                    $src_ci =  new courseInstance($_REQUEST['src_ci']);
	                    $src_ci->getPrimaryCourse();
	                    try{
							$src_ci->getCrossListings();	  //load cross listings	
						} catch (Rd_Exception $e) {
												
						}	
	
	                    $courseList =  array_merge(array($src_ci->course), $src_ci->crossListings);
	
						$this->displayFunction = "displayEditRegistrarKey";
						$this->argList = array ("admin", $courseList, "To detach from Registrar Feed check override box.", array("function" => "editClassFeed", "src_ci" => $src_ci->getCourseInstanceID()), "course_aliases");
						
					} else {
						//store Registrar keys if blank store null
						foreach ($courses as $ca_id => $value)
						{
	
							$ca = new course($ca_id);
							$rk = ($value['registrar_key'] == '') ? null : $value['registrar_key'];
							$ca->setRegistrarKey($rk);
	
							$or = (isset($value['override_feed']) && $value['override_feed'] == 'true') ? 1 : 0;
							$ca->setOverrideFeed($or);
						}
						
						Rd_Layout::setMessage('generalAlert', "Course(s) Successfully Updated");
						$request['function'] = '';
						$this->_delegateManager = new adminManager($cmd, $request, $this->_user);
					}
				break;
	/* Copyright Flag */			
				case 'clearReviewedFlag':
					$src_ci = null;				
					if (array_key_exists('src_ci', $_REQUEST) && !is_null($_REQUEST['src_ci'])) {
						$src_ci =  new courseInstance($_REQUEST['src_ci']);
						$src_ci->getPrimaryCourse();
						try{
							$src_ci->getCrossListings();	  //load cross listings	
						} catch (Rd_Exception $e) {
							
						}	
						$courseList =  array_merge(array($src_ci->course), $src_ci->crossListings);
					}				
	
					if (is_null($src_ci))
					{
						$this->displayFunction = "displaySelectClass";
						$this->argList = array("admin", null, 'Select a course to reset review status', array("function" => $function), false, "src_ci", null);					
					} else {				
						$src_ci->clearReviewed();
						
						Rd_Layout::setMessage('generalAlert', "Course flagged for copyright review");
						$request['function'] = '';
						$this->_delegateManager = new adminManager($cmd, $request, $this->_user);			
					} 
	
					
				break;
	/* Term */
				case 'editTerms':				
					$terms = terms::getTerms(true);
					$this->displayFunction = "displayEditTerm";
					$this->argList = array($function, $terms, (array_key_exists('term_id_select', $_REQUEST) ? $_REQUEST['term_id_select'] : ''));			
				break;			
	
				case 'saveTerm':
					$t_id = (isset($_REQUEST['term_id']) && $_REQUEST['term_id'] != "") ? $_REQUEST['term_id'] : null;
					$term = new term($t_id);
					
					if (is_null($t_id))
					{
						$term->create($_REQUEST['term_name'], $_REQUEST['term_year'], $_REQUEST['begin_date'], $_REQUEST['end_date'], $_REQUEST['sort_order']);
						Rd_Layout::setMessage('generalAlert', "Term Successfully Added");
						//$this->adminManager($cmd, $user, null);
						$request['function'] = '';
						$this->_delegateManager = new adminManager($cmd, $request, $this->_user);
					} else {
	
						if ($term->update($_REQUEST['term_name'], $_REQUEST['term_year'], $_REQUEST['begin_date'], $_REQUEST['end_date'], $_REQUEST['sort_order']))
						{
							Rd_Layout::setMessage('generalAlert', "Term Successfully Updated");
							//$this->adminManager($cmd, $user, null);
							$request['function'] = '';
							$this->_delegateManager = new adminManager($cmd, $request, $this->_user);
						}
							
					}
				break;		
	/* News */
				case 'editNews':
					$news_item = 
						array_key_exists('id', $_REQUEST)
						? news::getByID($_REQUEST['id'])
						: null;
					
					$news = news::getAll();
					$this->displayFunction = "displayEditNews";
					$this->argList = array($function, $news, $news_item);			
				break;	
				
				case 'insertNews':
					$perm_levels = array();
					if(sizeof($_REQUEST['permission_level']) == Account_Rd::levelCount())
					{
						$perm_levels[0] = null;
					} else {
						$perm_levels = $_REQUEST['permission_level'];
					}
					if(isset($_REQUEST['begin_time_null']) && $_REQUEST['begin_time_null'] == 'on')
						$begin = null;
					else
						$begin = date('Y-m-d H:i:s', strtotime($_REQUEST['begin_time']));
					if(isset($_REQUEST['end_time_null']) && $_REQUEST['end_time_null'] == 'on')
						$end = null;
					else
						$end =  date('Y-m-d H:i:s', strtotime($_REQUEST['end_time']));
										
					foreach ($perm_levels as $p)
					{
						$n = new news();
						$n->createNew($p, $_REQUEST['font_class'], $begin, $end, $_REQUEST['news_text'], $_REQUEST['sort_order']);
					}
					Rd_Layout::setMessage('generalAlert', "News Items Added");
					//$this->adminManager($cmd, $user, null);
					$request['function'] = '';
					$this->_delegateManager = new adminManager($cmd, $request, $this->_user);
				break;		
				
				case 'updateNews':
					if(isset($_REQUEST['begin_time_null']) && $_REQUEST['begin_time_null'] == 'on')
						$begin = null;
					else
						$begin = date('Y-m-d H:i:s', strtotime($_REQUEST['begin_time']));
					if(isset($_REQUEST['end_time_null']) && $_REQUEST['end_time_null'] == 'on')
						$end = null;
					else
						$end =  date('Y-m-d H:i:s', strtotime($_REQUEST['end_time']));				
					
					
					news::update($_REQUEST['font_class'], $begin, $end, $_REQUEST['news_text'], $_REQUEST['sort_order'], $_REQUEST['news_id']);
					Rd_Layout::setMessage('generalAlert', "News Items Updated");
					//$this->adminManager($cmd, $user, null);
					$request['function'] = '';
					$this->_delegateManager = new adminManager($cmd, $request, $this->_user);			
				break;
				
				default:
					parent::__construct($cmd, $request);	
			}
			return;
		}
		parent::__construct($cmd, $request);
	}
	
	protected function _testMxeAction($request)
	{
		//$this->_updateCommand($cmd);
		$this->_mappedDisplayArguments = true;
		$this->with('jQueryUi');
		$this->_setLocation('Cisco MXE Integration Test');
		$this->displayFunction = "displayMxeInterface";
		$mxeStatus = Client_Mxe::getStatus();
		$systemMessage = (
			'ready' == $mxeStatus
			? 'The MXE appears to be operating normally.'
			: 'The MXE did not respond normally. ' . Client_Mxe::getError()
		);
		$this->argList = array(
			'model' => array(
				'statusImage' => 'public/images/icons/system-monitor' . ('ready' == $mxeStatus ? '' : '-exclamation') . '.png',
				'statusMessage' => $systemMessage
			)
		);
	}
	
	public function indexAction($request = array())
	{
		$this->_updateCommand('index');
		$this->_setLocation('System Administration');
		$this->displayFunction = "displayAdminFunctions";
		$this->argList = array();
	}
	
	public function adminAction($request = array())
	{
		$this->indexAction($request);
	}
	
	public function switchUserAction($request = array())
	{
		$this->_setLocation('access as another user');
		if (!array_key_exists('uid', $_REQUEST)) { 
			$this->displayClass = 'userDisplayer';
			$this->displayFunction = 'displaySelectUser';
			$this->argList = array('switchUser');
			return;
		}
		$newUser = new user((int)$_REQUEST['uid']);
		if ($newUser->getDefaultRole() >= Account_Rd::LEVEL_ADMIN) {
			Rd_Layout::setMessage('actionResults','You cannot switch to another admin account. (attempting to switch to ' . $newUser->getUsername().' )');
			$this->displayClass = 'userDisplayer';
			$this->displayFunction = 'displaySelectUser';
			$this->argList = array('switchUser');
			return;
		}
		if (!array_key_exists('confirmUserSwitch', $_REQUEST)) {
			$this->displayFunction = "displaySwitchUser";
			$this->argList = array($newUser);
			return;
		}
		if (!$this->_logAdminActivity($this->_currentCommand,'users',$newUser->getUserID())) {
			Rd_Layout::setMessage('actionResults','Unable to proceed, unable to audit this action.');
			$this->displayFunction = "displaySwitchUser";
			$this->argList = array($newUser);
			return;
		}
		Rd_Auth::logoutLocally(false);
		Rd_Auth::loginAs($newUser);
		Rd_Dispatch::redirect('./');
	}
	
	protected function _logAdminActivity($cmd, $table, $id)
	{
		return Rd_Acl::audit(
			$this->_user->getUserID(), 
			Account_Rd::LEVEL_ADMIN, 
			$cmd, 
			$table, 
			$id
		);
	}
	
	public function manualCronAction($request = array())
	{
		$this->_setLocation('run cron jobs manually');
		$scriptNames = array(
			'EnrollmentImport',
			'AuditReport'
		);
		$scripts = array();
		foreach( $scriptNames as $scriptName) {
			if(!class_exists($scriptName)) {
				require_once(APPLICATION_PATH . '/lib/Rd/Script/' . $scriptName . '.php');
			}
			$fullScriptName = 'Rd_Script_' . $scriptName;
			$scripts[$scriptName] = new $fullScriptName();
		}
		if (!array_key_exists('script', $_REQUEST)) { 
			$this->displayFunction = 'displaySelectScript';
			$this->argList = array($scripts);
			return;
		} else if (!in_array($_REQUEST['script'], $scriptNames)) {
			Rd_Layout::setMessage('actionResults','The requested script is not a valid option.');
			$this->displayFunction = 'displaySelectScript';
			$this->argList = array($scripts);
			return;
		}
		$this->displayFunction = "displayScriptResult";
		$this->argList = array($scripts[$_REQUEST['script']]);
		return;
	}
}

