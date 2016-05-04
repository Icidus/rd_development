<?php
/*******************************************************************************
userManager.class.php
methods to edit and display users

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

require_once(APPLICATION_PATH . '/displayers/userDisplayer.class.php');
require_once(APPLICATION_PATH . '/managers/classManager.class.php');
require_once(APPLICATION_PATH . '/classes/specialUser.class.php');
require_once(APPLICATION_PATH . '/interface/admin.class.php');	//this will also include all children

class userManager extends Rd_Manager_Base {

	public function __construct($cmd, $request = array(), $with = array())
	{
		parent::__construct($cmd, $request, $with);
		$this->_setTab('manageUser');
	}
	
	public function indexAction($request = NULL)
	{
		$this->_setLocation('manage users');
		switch ($this->_role) {
			case Account_Rd::LEVEL_ADMIN:
			case Account_Rd::LEVEL_STAFF:
				$this->displayFunction = 'staffIndex';
				break;
			case Account_Rd::LEVEL_FACULTY:
				$this->displayFunction = 'facultyIndex';
				break;
		}
	}
	
	public function manageUserAction($request = NULL)
	{
		$this->indexAction($request);
	}
	
	public function userSelectAction($request = NULL) //#TODO not in acl/dispatch yet. needed?
	{
		$this->displayFunction = 'displaySelectUser';
		$this->argList = array(
			(array_key_exists('cmd', $request) ? $request['cmd'] : '')
		);
	}
	
	public function addUserAction($request = NULL)
	{
		$this->_setLocation('create a new user');
		if(array_key_exists('editUserSubmit', $_REQUEST)) {
			try{
				$userCreated = $this->_createUser();
				$username = $userCreated->getUsername();
				$userId = $userCreated->getUserId();
				Rd_Layout::setMessage('actionResults', "New user <a href=\"./?cmd=editUser&uid={$userId}\">({$username})</a> created successfully.");
				$this->_setLocation('edit a user');
				$this->displayFunction = 'displayEditProfile';
				$this->argList = array($userCreated, 'editUser');
				return;
			} catch (Exception $e) {
				Rd_Layout::setMessage('actionResults', $e->getMessage());
			}
		} 
		$userToEdit = new user();
		$this->displayFunction = 'displayEditProfile';
		$this->argList = array($userToEdit, 'addUser');
		return;
	}
	
	protected function _createUser($request = NULL)
	{
		if (!is_array($request)){
			$request = $_REQUEST;
		}
		if(
			!array_key_exists('username', $request) 
			|| '' == trim($request['username'])
		) {	
			throw new Rd_Exception_Form('Username is required');
		}
		if(
			!array_key_exists('firstName', $request) 
			|| '' == trim($request['firstName'])
		) {	
			throw new Rd_Exception_Form('First name is required');
		} 
		if(
			!array_key_exists('lastName', $request) 
			|| '' == trim($request['lastName'])
		) {	
			throw new Rd_Exception_Form('Last name is required');
		}
		if(
			!array_key_exists('email', $request) 
			|| '' == trim($request['email'])
		) {	
			throw new Rd_Exception_Form('E-mail is required');
		} else if (!preg_match(Rd_Registry::get('root:emailRegExp'), trim($request['email']))) {
			throw new Rd_Exception_Form('E-mail must be valid format (x@y.z)');
		}
			if(
			!array_key_exists('defaultRole', $request) 
			|| '' == trim($request['defaultRole'])
		) {	
			throw new Rd_Exception_Form('Default Role is required');
		}
		$user = new user();
		$users = new users();
		$matchingUsers = $users->getUsersByUsername(trim($request['username']), NULL, true);
		if ($matchingUsers && count($matchingUsers) > 0){
			throw new Exception('A user with that username already exists.');
		}
		if ($user->createUser(trim($request['username']),array(
				'firstName' => trim($request['firstName']),
				'lastName' => trim($request['lastName']),
				'email' => trim($request['email']),
				'defaultRole' => $request['defaultRole']
		))) {
			if (array_key_exists('enableGuest',$request)) {
				$this->_setGuest($user, $request);
				$user->messageNewGuest();
			}
			return $user;
		} else {
			throw new Exception('User not created');
		}
	}
	
	public function editUserAction($request = NULL)
	{
		$userToEdit = new user();
		$this->_setLocation('edit a user');
		if (!array_key_exists('uid', $_REQUEST) && Account_Rd::atLeastStaff()) { 
			$this->displayFunction = 'displaySelectUser';
			$this->argList = array('editUser');
			return;
		}
		//extra protection since we sometimes forward from editProfile
		if (Account_Rd::atLeastStaff()) {
			$userId = (int)$_REQUEST['uid'];
		} else { 
			$userId = Account_Rd::getId();
		}

		if (!$userToEdit->getUserByID($userId)) {
			Rd_Layout::setMessage('actionResults', 'No matching user found.');
			$this->displayFunction = 'index';
			return;
		} else if (array_key_exists('editUserSubmit', $_REQUEST)) {
			try{
				if (!$this->_editUser($userToEdit, $_REQUEST)) {
					Rd_Layout::setMessage('actionResults', 'Unable to edit user.');
					$this->displayFunction = 'displayEditProfile';
					$this->argList = array($userToEdit, $this->_currentCommand);
					return;
				} else if('newProfile' == $this->_currentCommand) {
					$this->_delegate(Rd_Dispatch::COMMAND_KEY_DEFAULT, array());
					return;
				}
				Rd_Layout::setMessage('actionResults', 'Changes Saved.');
			} catch (Exception $e) {
				Rd_Layout::setMessage('actionResults', $e->getMessage());
			}
		}
		$this->displayFunction = 'displayEditProfile';
		$this->argList = array($userToEdit, $this->_currentCommand);
	}
	
	public function editProfileAction($request = NULL)
	{
		if (!array_key_exists('editUserSubmit', $_REQUEST)) {
			$userToEdit = $this->_user;
			$this->_setLocation('edit your profile');
			$this->displayFunction = 'displayEditProfile';
			$this->argList = array($userToEdit, $this->_currentCommand);
		} else {
			$this->editUserAction($_REQUEST);
			$this->_user->refresh();
		}
	}
	
	public function newProfileAction($request = NULL)
	{
		$userMessage = (
			Account_Rd::atLeastStaff()
			? 'Please provide a First Name, Last Name, E-mail address, ILS User ID (this is usually the same as your User Name), and Primary Library to use this application.'
			: 'Please provide a First Name, Last Name, and E-mail address to use this application.'
		);
		Rd_Layout::setMessage('actionResults', $userMessage);
		if (
			array_key_exists('uid', $_REQUEST)
			&& (int)$_REQUEST['uid'] == Account_Rd::getId()
			&& array_key_exists('editUserSubmit', $_REQUEST)
		) {
			$this->editUserAction();
		} else {
			$this->editProfileAction();
		}
	}
	
	protected function _editUser($user, $request = NULL) // probably shoud change this to take userID from controller, not request...
	{
		if (!is_array($request)){
			$request = $_REQUEST;
		}
		$users = new users();
		if(
			!array_key_exists('firstName', $request) 
			|| '' == trim($request['firstName'])
		) {	
			throw new Rd_Exception_Form('First name is required');
		}
		if(
			!array_key_exists('lastName', $request) 
			|| '' == trim($request['lastName'])
		) {	
			throw new Rd_Exception_Form('Last name is required');
		}
		if(
			!array_key_exists('email', $request) 
			|| '' == trim($request['email'])
		) {	
			throw new Rd_Exception_Form('E-mail is required');
		} else if (!preg_match(Rd_Registry::get('root:emailRegExp'), trim($request['email']))) {
			throw new Rd_Exception_Form('E-mail must be valid format (x@y.z)');
		}
/*		if(
			!array_key_exists('defaultRole', $request) 
			|| '' == trim($request['defaultRole'])
		) {	
			throw new Rd_Exception_Form('Default Role is required');
		}
		if ('' == $user->getUserID()) {
			throw new Rd_Exception_Form('No matching user found.');
		}*/
		$userOriginalRole = $user->getDefaultRole();
		$updateFirstName = $user->setFirstName(trim($request['firstName']));
		$updateLastName = $user->setLastName(trim($request['lastName']));
		$updateEmail = $user->setEmail(trim($request['email']));
		if(array_key_exists('defaultRole', $request) 
			&& '' != trim($request['defaultRole'])) {
			$updateRole = $user->setDefaultRole(trim($request['defaultRole']));
		} else {
			$updateRole = NULL;
		}
		
		
		$ilsUserId = 
			array_key_exists('ilsUserId', $request)
			? $request['ilsUserId']
			: '';
			
		$ilsUsername = 
			array_key_exists('ilsUsername', $request)
			? $request['ilsUsername']
			: '';
		
		if ('' != $ilsUserId) {
			$instructor = $users->initUser(Account_Rd::LEVEL_FACULTY, $user->getUserName());
			$updateIls = $instructor->storeInstructorAttributes(
				trim($request['ilsUserId']),
				trim($request['ilsUsername'])
			);
		}
		if(array_key_exists('notTrained',$_REQUEST)) {
			$user->addNotTrained();
		} else if (
			$userOriginalRole == Account_Rd::LEVEL_FACULTY
		) {
			$user->removeNotTrained();
		}
		$defaultLibrary = 
			array_key_exists('staffLibrary', $request)
			? $request['staffLibrary']
			: '';
			
		if ('' != $defaultLibrary) {
			$staff = $users->initUser(Account_Rd::LEVEL_STAFF, $user->getUserName());
			$updateLibrary = $staff->assignStaffLibrary((int)$defaultLibrary);
		}
		//print_r(array($updateIls,$updateLibrary));
		return true;
	}
	
	public function setGuestAction($request = NULL)
	{
		$userToEdit = new user();
		$this->_setLocation('manage guest access');
		if (!array_key_exists('uid', $_REQUEST)) { 
			$this->displayFunction = 'displaySelectUser';
			$this->argList = array('setGuest');
			return;
		}
		if (!$userToEdit->getUserByID((int)$_REQUEST['uid'])) {
			Rd_Layout::setMessage('actionResults', 'No matching user found.');
			$this->displayFunction = 'index';
			return;
		} else if (array_key_exists('setGuestSubmit', $_REQUEST)) {
			try{
				if (!$this->_setGuest($userToEdit, $_REQUEST)) {
					Rd_Layout::setMessage('actionResults', 'Unable to edit user\'s guest access.');
					$this->displayFunction = 'displaySetGuest';
					$this->argList = array($userToEdit);
					return;
				} else {
					if (array_key_exists('enableGuest', $_REQUEST)) {
						$userToEdit->messageNewGuest();
					}
					Rd_Layout::setMessage('actionResults', 'Guest access updated.');
				}
			} catch (Exception $e) {
				Rd_Layout::setMessage('actionResults', $e->getMessage());
			}
		}
		$this->displayFunction = 'displaySetGuest';
		$this->argList = array($userToEdit);
		return;
	}
	
	protected function _setGuest($user, $request = NULL)
	{
		if (!is_array($request)){
			$request = $_REQUEST;
		}
		$users = new users();
		if(
			array_key_exists('expireDate', $request) 
			&& '' != trim($request['expireDate'])
			&& '0000-00-00' != trim($request['expireDate'])
			&& !preg_match(Rd_Registry::get('root:dbDateRegExp'), trim($request['expireDate']))
		) {	
			throw new Rd_Exception_Form('The expiration date provided is not formated correctly.');
		}
		$expireDate = (
			array_key_exists('expireDate', $request)
			? trim($request['expireDate'])
			: ''
		);
		$isGuest = array_key_exists('enableGuest', $request);
		
		if($isGuest) {
			return $user->grantGuestAccess($expireDate);
		} else {
			return $user->revokeGuestAccess();
		}
	}
	
	public function mergeUsersAction($request = NULL)
	{
		$this->_setLocation('merge user profiles');
		$users = new users();
		if (
			array_key_exists('userToKeep_selectedUser', $_REQUEST) 
			&& array_key_exists('userToMerge_selectedUser', $_REQUEST) 
			&& array_key_exists('submitMergeUser', $_REQUEST)
		){
			if ((int)$_REQUEST['userToKeep_selectedUser'] != (int)$_REQUEST['userToMerge_selectedUser']){
				try{
					if($users->mergeUsers($_REQUEST['userToKeep_selectedUser'], $_REQUEST['userToMerge_selectedUser'])) {
						Rd_Layout::setMessage('generalAlert', "Users successfully merged.");
						$this->displayFunction = 'staffIndex';
						$this->argList = array();
						return;
					} else {
						Rd_Layout::setMessage('generalAlert', "Unable to find user to merge.");	
					}
				} catch (Exception $e) {
					if (strpos($e->getMessage, 'PEWPEW') === false) { //#TODO this is bad, I know
						Rd_Layout::setMessage('generalAlert', $e->getMessage());
					}	
				}
			} else {
				Rd_Layout::setMessage('generalAlert', "User to keep and user to merge must be different users.");	
			}
		}
		$this->displayFunction = 'displayMergeUser';
		$this->argList = array($_REQUEST, 'mergeUsers', $users);
	}


	public function assignProxyAction($request = NULL)
	{
		$this->_setLocation('assign proxy to class &gt&gt ');
		$hidden = array('addProxy'=>'true');
		if(array_key_exists('proxyUserId', $_REQUEST)) {
			$this->_appendLocation('select class &gt&gt');
			$hidden['proxy'] = (int)$_REQUEST['proxyUserId'];
			$this->displayClass = 'ajaxDisplayer';
			$this->displayFunction 	= 'classLookup';
			$this->argList = array('editProxies', 'Select Class', $hidden);
		} else {
			$this->_appendLocation('select user &gt&gt');
			$this->displayClass	= "ajaxDisplayer";					
			$this->displayFunction 	= "userLookup";
			$this->argList = array('assignProxy', 'Select User', array('min_user_role'=>Account_Rd::LEVEL_STUDENT), true, Account_Rd::LEVEL_STUDENT, 'proxyUserId');
			return;
		}
	}
	
	public function addProxyAction()
	{
			$this->_appendLocation('select class &gt&gt');
			$this->displayClass = 'ajaxDisplayer';
			$this->displayFunction 	= 'classLookup';
			$this->argList = array('editProxies', 'Select Class', array());
		
	}
	
	public function removeProxyAction()
	{
			$this->_appendLocation('select class &gt&gt');
			$this->displayClass = 'ajaxDisplayer';
			$this->displayFunction 	= 'classLookup';
			$this->argList = array('editProxies', 'Select Class', $hidden);
		
	}

	/*
		$courseInstances = Account_Rd::getUserInterface()->getCourseInstancesToEdit();
		$this->displayFunction = 'displayEditProxy';
		$this->argList = array($courseInstances,'editProxies', 'assignProxy');		
				
				
				//init a manager - sets the displayer
					
				if(!empty($_REQUEST[$field_name])) {	//if already selected a user, show class lookup
					$this->_appendLocation('select class');	//show where we are
					$hidden[$field_name] = $_REQUEST[$field_name];	//pass on the user id
					
					//override displayer to show ajaxDisplayer::classLookup
					$this->displayClass 	= "ajaxDisplayer";
					$this->displayFunction 	= "classLookup";
					$this->argList 			= array($next_cmd, 'Select Class', $hidden);
				}
				else {	//show user lookup
					$this->_appendLocation('select user');	//show where we are

					//override displayer to show ajaxDisplayer::userLookup
					$this->displayClass		= "ajaxDisplayer";					
					$this->displayFunction 	= "userLookup";
					$this->argList 			= array($cmd, "Select User", array('min_user_role'=>$min_user_role), true, $g_permission['student'], $field_name);
				}
	*/
	
	public function assignInstrAction($request = NULL)
	{
		$this->_setLocation('assign instructor to class &gt&gt ');
		$hidden = array('addInstructor'=>'true');
		if(array_key_exists('instructorUserId', $_REQUEST)) {
			$this->_appendLocation('select class &gt&gt');
			$hidden['instructor'] = (int)$_REQUEST['instructorUserId'];
			$this->displayClass = 'ajaxDisplayer';
			$this->displayFunction 	= 'classLookup';
			$this->argList = array('editInstructors', 'Select Class', $hidden);
		} else {
			$this->_appendLocation('select user &gt&gt');
			$this->displayClass	= "ajaxDisplayer";					
			$this->displayFunction 	= "userLookup";
			$this->argList = array('assignInstr', 'Select User', array('min_user_role'=>Account_Rd::LEVEL_FACULTY), true, Account_Rd::LEVEL_FACULTY, 'instructorUserId');
			return;
		}
	}
}

