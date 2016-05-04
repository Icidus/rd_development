<?php
/*******************************************************************************
classManager.class.php

Created by Kathy Washington (kawashi@emory.edu)
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
require_once(APPLICATION_PATH . '/common.inc.php');
require_once(APPLICATION_PATH . '/classes/users.class.php');
require_once(APPLICATION_PATH . '/displayers/classDisplayer.class.php');
require_once(APPLICATION_PATH . '/displayers/mobileClassDisplayer.class.php');
require_once(APPLICATION_PATH . '/managers/copyClassManager.class.php');

class classManager extends Rd_Manager_Base
{
	public $user;
	public $displayClass;
	public $displayFunction;
	public $argList;
	
	protected $_newStyleCommands = array(
		'viewCourseList'
	);

/*	function display()
	{
		//echo "attempting to call ". $this->displayClass ."->". $this->displayFunction ."<br>";
		$args = array();
		foreach($this->argList as $index=>$value){
			$args[$index] =& $this->argList[$index];
		}
		if (is_callable(array($this->displayClass, $this->displayFunction))){	
			call_user_func_array(array($this->displayClass, $this->displayFunction), $args);
		}
	}
*/

	function __construct($cmd, $user, $adminUser, $request)
	{
		$u = Account_Rd::getUserInterface();
		$this->displayClass = (
			Rd_Layout::isMobile()
			? 'mobileClassDisplayer'
			: 'classDisplayer'
		);
		if ( in_array($cmd, $this->_newStyleCommands)) {
			$with = (array('960css'));
			return parent::__construct($cmd,$request,$with);
		}

		//otherwise proceed down the path of good intentions
        global $g_permission, $ci;
		
//echo "classManager($cmd, $user, $adminUser)<P>"; //classManager

		//set the page (tab)
		if($u->getRole() >= $g_permission['staff']) {
			$this->_setTab('manageClasses');
		}
		else {
			$this->_setTab('myReserves');
		}

		switch ($cmd)
		{
			case 'manageClasses':
				$this->_withOptions[] = '960css';
				return $this->manageClassesAction();
			break;
			
			case 'myReserves':
			case 'viewCourseList':
				return $this->viewCourseListAction();
			break;
			
	
			case 'addClass':
				$this->_setLocation('join a class');
				Rd_Help::setDefaultArticleId(27);
				
				$ci_id = !empty($_REQUEST['ci']) ? $_REQUEST['ci'] : null;
				$instructor_id = !empty($_REQUEST['selected_instr']) ? $_REQUEST['selected_instr'] : null;
				$department_id = !empty($_REQUEST['department']) ? $_REQUEST['department'] : null;
				
				if($ci_id) {	//we have a class
					$ci = new courseInstance($ci_id);
					$ci->getCourseForUser();
					
					if($ci->getEnrollment() == 'OPEN') {	//if class has open enrollment, automatically approve the student
						$user->joinClass($ci->course->getCourseAliasID(), 'APPROVED');
					}
					else {	//else, add the student as pending
						$user->joinClass($ci->course->getCourseAliasID(), 'PENDING');
					}
					
					//go to course listing
					$this->_delegateManager = new classManager('viewCourseList', $user, $adminUser, null);
				}
				else {	//no class, find one
					if($user->getRole() >= $g_permission['staff']) {	//if staff, show ajax lookup
						$this->displayFunction = 'displaySelectClass';
						$this->argList = array('addClass', null, 'Select a class to add:');
					}
					elseif($instructor_id || $department_id) {	//searching by instructor or department
						//grab the courses			
						if($instructor_id) {	//search for class by instructor
							$course_instances = $user->getCourseInstancesByInstr($instructor_id);
						}
						elseif($department_id) {	//search by department
							$termsObj = new terms();	
							$current_term = $termsObj->getCurrentTerm();
							$usersObj = new users();
												
							$course_instances = $usersObj->searchForCI(null, $department_id, null, null, $current_term->getTermID());
						}
							
						//do not display courses with closed enrollment or inactive status						
						if (!empty($course_instances))
						{
							foreach($course_instances as $ci_x_index=>$ci_x) {
								if(!$ci_x->EnrollmentAllowed()) {								
									unset($course_instances[$ci_x_index]);
								}
							}
						}
										
						$msg = 'Select a class to add:';							
						$this->displayFunction = 'displaySelectClass';
						$this->argList = array('addClass', $course_instances, $msg);
					}
					else {	//need either instructor or dept					
						$this->displayFunction = 'displaySelectDept_Instr';
						$this->argList = array();
					}
				}
			break;
			
			case 'removeClass':	//remove classes from a user's list (deletes access entry)
				$this->_setLocation('leave a class');
				Rd_Help::setDefaultArticleId(27);
				$leave = $u->getDefaultRole() < Account_Rd::LEVEL_FACULTY || array_key_exists('asStudent', $_REQUEST);
				if(!empty($_REQUEST['ci'])) {	//user selected class
					$ci = new courseInstance($_REQUEST['ci']);
					$ci->getCourseForUser();
					if(!$leave) {	//trying to remove a class the user is teaching
						$u->removeClass($ci->course->getCourseAliasID());						
					}
					else {	//trying to leave a class the user is enrolled in	
						$u->leaveClass($ci->course->getCourseAliasID());
					}
					
					//go to course listing
					$this->_delegateManager = new classManager('viewCourseList', $u, $adminUser, null);
				}
				else {	//show class list					
					if(!$leave) {
						//for instructors this will be a list of cancelled/inactive courses they are teaching
						$course_instances = $u->getCourseInstancesToRemove();
						$type = 'instructor';
					}
					else {
						//for students this will be a list of all courses in which they are enrolled (except autofed)
						$course_instances = $u->getCourseInstancesToLeave();
						$type = '';
					}

					$msg = (
						$leave
						? 'Select a class to leave:'
						: 'Select a class to remove:'
					);
						
					$this->displayFunction = 'displaySelectClass';
					$this->argList = array('removeClass', $course_instances, $msg, array('type'=>$type), true);
				}				
			break;
	
			
			case 'activateClass':
				if(empty($_REQUEST['ci'])) {
					return false;
				}
				
				//just need to change the class status to active
				$ci = new courseInstance($_REQUEST['ci']);
				$ci->setStatus('ACTIVE');
				
				//show success screen
				$this->displayFunction = 'displayActivateSuccess';
				$this->argList = array($ci->getCourseInstanceID());			
			break;
			
			case 'deactivateClass':
				global $adminUser;
				$u = Rd_Registry::get('root:userInterface');
				if(empty($_REQUEST['ci'])) {
					return false;
				}
				
				//just need to change the class status to active
				$ci = new courseInstance($_REQUEST['ci']);
				$ci->setStatus('AUTOFEED');
				
				//go to course listing
				$this->_delegateManager = new classManager('viewCourseList', $u, $adminUser, null);
			break;	
			
			case 'editClass':
				
				$this->_setLocation('edit class');
				//$this->_withOptions[] = 'quickView';
				Rd_Help::setDefaultArticleId(23);
				
				if(empty($_REQUEST['ci'])) {	//get ci
					//get array of CIs (ignored for staff)
					$courses = $u->getCourseInstancesToEdit();					
					$this->displayFunction = 'displaySelectClass';					
					$this->argList = array($cmd, $courses, 'Select class to edit', $_POST);
					break;
				}

				$ci = new courseInstance($_REQUEST['ci']);	
			
				//update class status
				if(isset($_REQUEST['updateClassStatus'])) {
					if(!empty($_REQUEST['status'])) {
						$ci->setStatus($_REQUEST['status']);
					}
				}
				//update class dates
				if(isset($_REQUEST['updateClassDates'])) {
					//if not empty, set activation and expiration dates
					//try to convert dates to proper format
					if(!empty($_REQUEST['activation'])) {
						$ci->setActivationDate($_REQUEST['activation'], $u->getRole());
					}
					if(!empty($_REQUEST['expiration'])) {
						$ci->setExpirationDate($_REQUEST['expiration'], $u->getRole());
					}
				}
				//change enrollment type
				if(isset($_REQUEST['setEnrollment'])) {
					$ci->setEnrollment($_REQUEST['enrollment']);					
				}
				//add/remove/approve/deny enrollment for student
				if(isset($_REQUEST['rollAction'])) {
					//get student list for approve/deny-all
					if($_REQUEST['student_id']=='all') {
						$roll = $ci->getRoll();
						$students = array();
						foreach($roll as $courseRoll) { // each possible course
							foreach($courseRoll['PENDING'] as $student) {   //just need the pending students
								$students[] = $student->getUserID();    //just need IDs
							}
						}
					}
					else {	//single-student action
						$students = array($_REQUEST['student_id']);
					}
					
					$user->editClassRoll($ci->getCourseInstanceID(), $students, $_REQUEST['rollAction']);
				}
				
				//get the tab to show
				$tab = !empty($_REQUEST['tab']) ? $_REQUEST['tab'] : null;

				//show screen
				$this->displayFunction = 'displayEditClass';
				$this->argList = array($ci, $cmd, $tab);
			break;

			case 'editTitle':
			case 'editCrossListings':
	
				$this->_setLocation('edit title and crosslistings');
				Rd_Help::setDefaultArticleId(29);

				$msg = null;			
				
				if (!isset($_REQUEST['ci']))
				{
					//Prompt user to select class to xlist
					$this->displayFunction = 'displaySelectClass';
					$this->argList = array($cmd, null, 'Select class to add Crosslisting:', null);
				} else {
					$ci = new courseInstance($_REQUEST['ci']);							
	
					//Delete xlist 	
					if (isset($_REQUEST['deleteCrossListings']) 
						&& is_array($_REQUEST['deleteCrossListing']) 
						&& !empty($_REQUEST['deleteCrossListing']))
					{
						$ci->removeCrossListing($_REQUEST['deleteCrossListing']);
						$msg = " Crosslistings Successfully Removed.";
					}					
	
					//User has selected CA to xlist
					if (isset($_REQUEST['addCrossListing']))
					{
						if (isset($_REQUEST['xlist_ci']))
						{
							$x_list_ci = new courseInstance($_REQUEST['xlist_ci']);
							$x_list_ci->getPrimaryCourse();
							
							$dept 		= $x_list_ci->course->deptID;
							$courseNo 	= $x_list_ci->course->courseNo;
							$section 	= $x_list_ci->course->section;
							$courseName = $x_list_ci->course->name;	
							$xlist_ca	= $x_list_ci->getPrimaryCourseAliasID();
						} else {
							$xlist_ca = null;
							if (isset($_REQUEST['ca_variable']))
							{
								$c = new course($_REQUEST['selected_ca']);
								$dept = $c->deptID;
								$courseNo = $c->courseNo;
								$section = $c->section;
								$courseName = $c->name;							
							} else {
								$dept = $_REQUEST['newDept'];
								$courseNo = $_REQUEST['newCourseNo'];
								$section = $_REQUEST['newSection'];
								$courseName = $_REQUEST['newCourseName'];
							}											
						}
					
						if (is_null($dept) || is_null($courseNo) || is_null($courseName))	
							Rd_Layout::setMessage('generalAlert', 
								'Please supply a Department, Course#, Section, and Title before adding the Cross Listing.'
							);
						else 
							$user->addCrossListing($ci, $dept, $courseNo, $section, $courseName, $xlist_ca);
							$msg = " Crosslistings Successfully Added.";						
	
					}
	
					if (isset($_REQUEST['updateCrossListing'])) {
						/* commented out by kawashi on 11.12.04 - No longer able to change primary course
						$oldPrimaryCourse = new course($_REQUEST['oldPrimaryCourse']);
						$oldPrimaryCourse->setDepartmentID($_REQUEST['primaryDept']);
						$oldPrimaryCourse->setCourseNo($_REQUEST['primaryCourseNo']);
						$oldPrimaryCourse->setSection($_REQUEST['primarySection']);
						$oldPrimaryCourse->setName($_REQUEST['primaryCourseName']);
	
						//Set New Primary Course
						$ci->setPrimaryCourseAliasID($_REQUEST['primaryCourse']);
						*/
	
						$primaryCourse = new course($_REQUEST['primaryCourse']);
						$primaryCourse->setDepartmentID($_REQUEST['primaryDept']);
						$primaryCourse->setCourseNo($_REQUEST['primaryCourseNo']);
						$primaryCourse->setSection($_REQUEST['primarySection']);
						$primaryCourse->setName($_REQUEST['primaryCourseName']);
	
						if ($_REQUEST['cross_listings'])
						{
							$cross_listings = array_keys($_REQUEST['cross_listings']);
							foreach ($cross_listings as $cross_listing)
							{
								$updateCourse = new course($cross_listing);
								$updateCourse->setDepartmentID($_REQUEST['cross_listings'][$cross_listing]['dept']);
								$updateCourse->setCourseNo($_REQUEST['cross_listings'][$cross_listing]['courseNo']);
								$updateCourse->setSection($_REQUEST['cross_listings'][$cross_listing]['section']);
								$updateCourse->setName($_REQUEST['cross_listings'][$cross_listing]['courseName']);
							}
							$msg = " Crosslistings Successfully Updated.";
						}
					}
	
					//$ci->getCourseForInstructor($user->getUserID());
					$ci->getPrimaryCourse();
					$ci->course->getDepartment();   //$this->department = new department($this->deptID);
					try{
						$ci->getCrossListings();  //load cross listings	
					} catch (Rd_Exception $e) {
						
					}	
	
					$deptID = $ci->course->department->getDepartmentID();
					
					if ($u->getRole() < $g_permission['staff']) {
						$potential_xlistings = $u->fetchCourseInstances('instructor', date('Y-m-d'), date('Y-m-d'), 'ACTIVE');
					} else {
                        $potential_xlistings = array();
                    }
					$this->displayFunction = 'displayEditTitle';
					$this->argList = array($cmd, $ci, $deptID, $msg, $potential_xlistings);
				}
			break;

			case 'editInstructors':
				$this->_setLocation('edit instructors');
				Rd_Help::setDefaultArticleId(12);
				
				$ci = new courseInstance($_REQUEST['ci']);
				$ci->getPrimaryCourse();
				try{
					$ci->getCrossListings();  //load cross listings	
				} catch (Rd_Exception $e) {
					
				}	

				if (
					array_key_exists('addInstructor', $_REQUEST) 
					&& $_REQUEST['addInstructor']
				) {
					$ci->addInstructor($ci->primaryCourseAliasID, (int)$_REQUEST['instructor']); //Add instructor to primary course alias
					for ($i=0; $i<count($ci->crossListings); $i++) {
						$ci->addInstructor($ci->crossListings[$i]->courseAliasID, (int)$_REQUEST['instructor']); // add instructor to the Xlistings
					}		
					
				}

				if (array_key_exists('removeInstructor', $_REQUEST) && $_REQUEST['removeInstructor']) {
					//Problem - Should there be a stipulation that you can't remove the last instructor?
					$instructors = $_REQUEST['Instructor'];

					if (is_array($instructors) && !empty($instructors)){
						foreach($instructors as $instructorID)
						{
							$ci->removeInstructor($ci->primaryCourseAliasID,$instructorID); //remove instructor from primary course alias
							for ($i=0; $i<count($ci->crossListings); $i++) {
								$ci->removeInstructor($ci->crossListings[$i]->courseAliasID, $instructorID); // remove instructor from the Xlistings
							}
						}
					}
				}

				$ci->getInstructors(); //load current instructors
				//$ci->getCourseForInstructor($user->getUserID());
				$ci->getPrimaryCourse();
				//$instructorList = common_getUsers('instructor'); //get instructors to populate drop down box				
				$this->displayFunction = 'displayEditInstructors';
				$this->argList = array($ci, 'ADD AN INSTRUCTOR', 'Choose an Instructor', 'Instructor', 'CURRENT INSTRUCTORS', 'Remove Selected Instructors', $request);
			break;

			case 'editProxies':
				$this->_setLocation('edit proxies');
				Rd_Help::setDefaultArticleId(13);
				
				$ci = new courseInstance($_REQUEST['ci']);
				$ci->getPrimaryCourse();
				try{
					$ci->getCrossListings();  //load cross listings	
				} catch (Rd_Exception $e) {
					
				}	
				

				if (isset($_REQUEST['addProxy'])) {
					$user->makeProxy($_REQUEST['proxy'],$ci->courseInstanceID);
					/*
					$ci->addProxy($ci->primaryCourseAliasID,$_REQUEST['prof']); //Add proxy to primary course alias
					for ($i=0; $i<count($ci->crossListings); $i++) {
						$ci->addProxy($ci->crossListings[$i]->courseAliasID, $_REQUEST['prof']); // add proxy to the Xlistings
					}
					*/
				}

				if (isset($_REQUEST['removeProxy'])) {
					$proxies = $_REQUEST['proxies'];

					if (is_array($proxies) && !empty($proxies)){
						foreach($proxies as $proxyID)
						{
							$user->removeProxy($proxyID, $ci->courseInstanceID);
							/*
							$ci->removeProxy($ci->primaryCourseAliasID,$proxyID); //remove proxy from primary course alias
							for ($i=0; $i<count($ci->crossListings); $i++) {
								$ci->removeProxy($ci->crossListings[$i]->courseAliasID, $proxyID); // remove proxy from the Xlistings
							}
							*/
						}
					}
				}

				$ci->getProxies(); //load current proxies
				$ci->getPrimaryCourse();

				if (isset($_REQUEST['queryText']) &&  $_REQUEST['queryText'] != "")
				{
					$usersObj = new users();
					$usersObj->search($_REQUEST['queryTerm'], $_REQUEST['queryText']);  //populate userList
				}

				//$ci->getCourseForInstructor($user->getUserID());
				//$instructorList = common_getUsers('proxy'); //get instructors to populate drop down box

				$this->displayFunction = 'displayEditProxies';
				$this->argList = array($ci, (isset($usersObj) && isset($usersObj->userList) ? $usersObj->userList : null), $_REQUEST);
			break;

			case 'createClass':
				$this->_setLocation('create new class');
				Rd_Help::setDefaultArticleId(25);

//				$msg = 'Create your class below.  You will have a chance to reactivate readings from previous courses on the next screen.';
				$this->displayFunction = 'displayCreateClass';
				$this->argList = array('createClass', null, null, (isset($msg) ? $msg : ''));
			break;

			case 'createNewClass':
				global $ci;
				$this->_setLocation('create class');
			
				$t = new term($_REQUEST['term']);
				
				if (!($ci instanceof courseInstance)){
                    $newCi = (
                        array_key_exists('src_ci', $_REQUEST) 
                            && $_REQUEST['src_ci'] 
                        ? $_REQUEST['src_ci'] 
                        : (
                            array_key_exists('ci', $_REQUEST) 
                            ? $_REQUEST['ci'] 
                            : null
                        )
                    );
					$ci = new courseInstance($newCi);
                }		

				if (!$ci->getDuplicatesByMatch($_REQUEST['department'], 
												   $_REQUEST['course_number'], 
												   $_REQUEST['section'], $t->getTermYear(), $t->getTermName()))
				{		
					//attempt to create the course instance
					if($ci->createCourseInstance($_REQUEST['department'], $_REQUEST['course_number'], $_REQUEST['course_name'], $_REQUEST['section'], $t->getTermYear(), $t->getTermName())) 
					{	//course created successfully, insert data
						$ci->addInstructor($ci->getPrimaryCourseAliasID(), $_REQUEST['selected_instr']);
						$ci->setTerm($t->getTermName());
						$ci->setYear($t->getTermYear());
						$ci->setActivationDate($_REQUEST['activation_date']);
						$ci->setExpirationDate($_REQUEST['expiration_date']);
						$ci->setEnrollment($_REQUEST['enrollment']);
						$ci->setStatus('ACTIVE');
						
						$new_ci = $ci->getCourseInstanceID();
						
						//course is now complete, decide what to do next
						$postproc_cmd = !empty($_REQUEST['postproc_cmd']) ? $_REQUEST['postproc_cmd'] : '';					
						switch($postproc_cmd) {
							case 'importClass':	//turn control over to a different manager
							case 'processCopyClass':
								$_REQUEST['new_ci'] = $new_ci;
								$this->_delegateManager = new copyClassManager($postproc_cmd, $u, $_REQUEST);
							break;
	
							default:	//do not need to do any post-processing
								//show success screen
								$this->displayFunction = 'displayCreateSuccess';
								$this->argList = array($new_ci);
						}
					}
				} else {	//duplicates found					
					//display duplicate info
					$this->displayFunction = 'displayDuplicateCourse';
					//make sure we go back to the previous screen
					$preproc_cmd = !empty($_REQUEST['preproc_cmd']) ? $_REQUEST['preproc_cmd'] : 'createClass';
					$_REQUEST['cmd'] = $preproc_cmd;
					$this->argList = array($ci, base64_encode(serialize($_REQUEST)));
				}
			break;

			case 'deleteClass':
				$this->_setLocation('delete class');

				if ($user->getRole() >= $g_permission['staff'])
				{
					$this->displayFunction = 'displayDeleteClass';
					$this->argList = array($cmd, $user, $request);
				}
			break;

			case 'confirmDeleteClass':
				$this->_setLocation('confirm delete class');

				if (isset($request['ci']))
				{
					$courseInstance = new courseInstance($request['ci']);
					$courseInstance->getPrimaryCourse();
					$courseInstance->getInstructors();

					$this->displayFunction = 'displayConfirmDelete';
					$this->argList = array($courseInstance);
				}
			break;

			case 'deleteClassSuccess':
				$this->_setLocation('delete class');

				if (isset($request['ci']))
				{
					$courseInstance = new courseInstance($request['ci']);
					$courseInstance->getPrimaryCourse();
					$courseInstance->destroy();

					$this->displayFunction = 'displayDeleteSuccess';
					$this->argList = array($courseInstance);
				}
			break;
			
			case 'copyItems':
				$this->_setLocation('copy reserve items to another class');
				Rd_Help::setDefaultArticleId(20);
				
				$class_list = $user->getCourseInstancesToEdit();
				$this->displayFunction = 'displaySelectClass';
				$this->argList = array('processCopyItems', $class_list, 'Select class to copy TO:', $request);
			break;
			
			case 'processCopyItems':
				$this->_setLocation('copy reserve items to another class');

				$srcCI = new courseInstance($_REQUEST['originalClass']);
				//copy reserves
				if(is_array($_REQUEST['reservesArray']) && !empty($_REQUEST['reservesArray'])) {
					$srcCI->copyReserves($_REQUEST['ci'], $_REQUEST['reservesArray']);
				}
				
				$dstCI = new courseInstance($_REQUEST['ci']);
				$srcCI->getPrimaryCourse();
				$dstCI->getPrimaryCourse();
			
				$this->displayFunction = 'displayCopyItemsSuccess';
				$this->argList = array($dstCI,$srcCI,count($_REQUEST['reservesArray']));
			break;
				
		}	
	}
	
	public function viewCourseListAction($request=array())
	{
		$this->_setTab('myReserves');
		$this->_setLocation('home');
		$instructorCiList = array();
		$proxyCiList = array();
		$studentCiList = array();
		$activationDate = date('Y-m-d', strtotime('+1 year'));
		$expirationDate = date('Y-m-d');
		
		$studentCiList = $this->_user->getCourseInstances();
		
		if($this->_user->getDefaultRole() >= Account_Rd::LEVEL_FACULTY) {									
			$tmp = $this->_user->fetchCourseInstances(
				'instructor', $activationDate, $expirationDate, 'ACTIVE'
			);
			if(!empty($tmp)) {
				$instructorCiList['ACTIVE'] = $tmp;
			}
			$tmp = $this->_user->fetchCourseInstances(
				'instructor', $activationDate, $expirationDate, 'INACTIVE'
			);
			if(!empty($tmp)) {
				$instructorCiList['INACTIVE'] = $tmp;
			}					
			$tmp = $this->_user->fetchCourseInstances(
				'instructor', $activationDate, $expirationDate, 'AUTOFEED'
			);
			if(!empty($tmp)) {
				$instructorCiList['AUTOFEED'] = $tmp;
			}
			$tmp = $this->_user->fetchCourseInstances(
				'instructor', $activationDate, $expirationDate, 'CANCELED'
			);
			if(!empty($tmp)) {
				$instructorCiList['CANCELED'] = $tmp;
			}
	   	}
	   	
		if($this->_user->getDefaultRole() >= Account_Rd::LEVEL_PROXY) {									
			$proxyCiList = $this->_user->fetchCourseInstances(
				'proxy', $activationDate, $expirationDate, 'ACTIVE'
			);
			$proxyCiList = array_merge(
				$proxyCiList, 
				$this->_user->fetchCourseInstances(
					'proxy', $activationDate, $expirationDate, 'INACTIVE'
				)
			);
		}

		$this->displayFunction = "displayCourseList";
		$this->argList = array(
			$studentCiList, 
			$instructorCiList, 
			$proxyCiList
		);
	}
	
	public function manageClassesAction($request=array()) {
		$this->_setLocation('manage classes home');
		$this->displayFunction = 'displayStaffHome';
		$this->argList = array($this->_user);
	}
}
