<?php
/*******************************************************************************
copyClassManager.class.php

Created by Dmitriy Panteleyev (dpantel@emory.edu)
Modified by NCSU Libraries, NC State University. Modifications by Karl Doerr & Troy Hurteau (libraries.opensource@ncsu.edu).

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

require_once(APPLICATION_PATH . '/displayers/copyClassDisplayer.class.php');
require_once(APPLICATION_PATH . '/displayers/classDisplayer.class.php');
require_once(APPLICATION_PATH . '/classes/courseInstance.class.php');
require_once(APPLICATION_PATH . '/classes/course.class.php');
require_once(APPLICATION_PATH . '/classes/department.class.php');
require_once(APPLICATION_PATH . '/classes/term.class.php');
require_once(APPLICATION_PATH . '/classes/terms.class.php');
require_once(APPLICATION_PATH . '/classes/reserves.class.php');
require_once(APPLICATION_PATH . '/classes/request.class.php');

class copyClassManager extends Rd_Manager_Base {

	function copyClassManager($cmd, $user, $request, $hidden_fields=null)
	{
		global $g_permission;
		$u = Rd_Registry::get('root:userInterface');
		$this->displayClass = "copyClassDisplayer";
		//set the page (tab)
		if($u->getRole() >= $g_permission['staff']) {
			$this->_setTab('manageClasses');
		}
		else {
			$this->_setTab('myReserves');
		}

		switch ($cmd)
		{
			case 'importClass':	
				$src_ci = !empty($_REQUEST['src_ci']) ? $_REQUEST['src_ci'] : null;
				$dst_ci = !empty($_REQUEST['dst_ci']) ? $_REQUEST['dst_ci'] : null;

				if (!empty($_REQUEST['dst_ci']))
					$dst_ci = $_REQUEST['dst_ci'];  //user selected destination
				elseif (!empty($_REQUEST['new_ci']))
					$dst_ci = $_REQUEST['new_ci'];  //user created new destination course
				else
					$dst_ci = null;
				
				if(!empty($dst_ci) && !empty($src_ci)) {	//have both source and destination, display options
					//get the source ci
					$ci = new courseInstance($_REQUEST['src_ci']);
					//get reserves as a tree + recursive iterator
					$walker = $ci->getReservesAsTreeWalker('getReserves');
					
					$this->_setLocation('import class >> import details');
					$this->displayFunction = 'displayImportClassOptions';
					$this->argList = array($ci, $walker, $dst_ci, 'processCopyClass');
				}
				elseif(empty($src_ci)) {	//need source ci -- find it
					$class_list = $u->getCourseInstancesToImport();
				
					$this->_setLocation('import class >> select source class');
					$this->displayFunction = 'displaySelectClass';
					//pass on destination ci, in case it is already set
					$this->argList = array('importClass', $class_list, 'Select course to import FROM:', array('dst_ci'=>$dst_ci), false, 'src_ci', null);
				}
				elseif(empty($dst_ci)) {	//need destination ci -- create it
					if (array_key_exists('createNew',$_REQUEST) && $_REQUEST['createNew']) //User has chosen to create new class 
					{
                        //assume that we already have a source and initialize it
                        $ci = new courseInstance($src_ci);
                        $ci->getCourseForUser();        //get course info
                        $ci->course->getDepartment();   //get the department
                        $ci->getInstructors();  //get instructors

                        //attempt to pre-fill create-class form by faking the $_REQUEST array
                        $_REQUEST = array(
                                'department' => $ci->course->department->getDepartmentID(),
                                'section' => $ci->course->getSection(),
                                'course_number' => $ci->course->getCourseNo(),
                                'course_name' => $ci->course->getName(),
                                'enrollment' => $ci->getEnrollment(),
                                'selected_instr' => $ci->instructorIDs[0],
                                'search_selected_instr' => $ci->instructorList[0]->getName().' -- '.$ci->instructorList[0]->getUsername()
                        );
                        //pass on the source CI id
                        $needed_info = array('src_ci' => $src_ci, 'submit' => 'Continue');

                        $this->_setLocation('import class >> create destination class');
                        $this->displayClass = 'classDisplayer';
                        $this->displayFunction = 'displayCreateClass';
                        $this->argList = array('importClass', 'importClass', $needed_info, 'Create course to import INTO: ');
					} else {
						//display list of course to reactivate
						$class_list = $u->getAllFutureCourseInstances();
						
						//remove source class from selection
						unset($class_list[$src_ci]);
		
						//pass on the source CI id
						$needed_info = array('src_ci' => $src_ci);
						
						$this->_setLocation('import class >> select destination class');
						$this->displayClass = 'classDisplayer';
						$this->displayFunction = 'displaySelectClass';
						$this->argList = array('importClass', $class_list, 'Select course to import INTO: <br>Select readings to reactivate on the next screen.', $needed_info, false, 'dst_ci', 'index.php?cmd=importClass&createNew=true&postproc_cmd=importClass&src_ci='.$src_ci);								

					}
				}			
			break;

			case 'copyClass':
				$class_list = $u->getCourseInstancesToEdit();
				
				$this->_setLocation('copy course reserves list >> select source class');				
				$this->displayFunction = 'displaySelectClass';
				$this->argList = array('copyClassOptions', $class_list, 'Select Source Class:'); //Select class to copy FROM:
			break;
				
			case 'copyClassOptions':
				$sourceClass = new courseInstance($_REQUEST['ci']);
				$sourceClass->getPrimaryCourse();
				$sourceClass->getInstructors();

				$this->_setLocation('copy course reserves list >> copy options');				
				$this->displayFunction = 'displayCopyClassOptions';
				$this->argList = array($sourceClass);				
			break;

			case 'copyExisting':				
				//propagate the info
				$needed_info = array();
				if(!empty($_REQUEST['sourceClass']))		$needed_info['sourceClass'] = $_REQUEST['sourceClass'];
				if(!empty($_REQUEST['copyReserves']))		$needed_info['copyReserves'] = $_REQUEST['copyReserves'];
				if(!empty($_REQUEST['copyCrossListings']))	$needed_info['copyCrossListings'] = $_REQUEST['copyCrossListings'];
				if(!empty($_REQUEST['copyEnrollment']))		$needed_info['copyEnrollment'] = $_REQUEST['copyEnrollment'];
				if(!empty($_REQUEST['copyInstructors']))	$needed_info['copyInstructors'] = $_REQUEST['copyInstructors'];
				if(!empty($_REQUEST['copyProxies']))		$needed_info['copyProxies'] = $_REQUEST['copyProxies'];
				if(!empty($_REQUEST['deleteSource']))		$needed_info['deleteSource'] = $_REQUEST['deleteSource'];	
				if(!empty($_REQUEST['crosslistSource']))	$needed_info['crosslistSource'] = $_REQUEST['crosslistSource'];	
				
				$class_list = $u->getCourseInstancesToEdit();

				$this->_setLocation('copy course reserves list >> select destination class');
				$this->displayFunction = 'displaySelectClass';
				$this->argList = array('processCopyClass', $class_list, 'Select class to copy TO:', $needed_info);	
			break;

			case 'copyNew':				
				//propagate the info
				$needed_info = array();
				if(!empty($_REQUEST['sourceClass']))		$needed_info['sourceClass'] = $_REQUEST['sourceClass'];
				if(!empty($_REQUEST['copyReserves']))		$needed_info['copyReserves'] = $_REQUEST['copyReserves'];
				if(!empty($_REQUEST['copyCrossListings']))	$needed_info['copyCrossListings'] = $_REQUEST['copyCrossListings'];
				if(!empty($_REQUEST['copyEnrollment']))		$needed_info['copyEnrollment'] = $_REQUEST['copyEnrollment'];
				if(!empty($_REQUEST['copyInstructors']))	$needed_info['copyInstructors'] = $_REQUEST['copyInstructors'];
				if(!empty($_REQUEST['copyProxies']))		$needed_info['copyProxies'] = $_REQUEST['copyProxies'];
				if(!empty($_REQUEST['deleteSource']))		$needed_info['deleteSource'] = $_REQUEST['deleteSource'];
				if(!empty($_REQUEST['crosslistSource']))	$needed_info['crosslistSource'] = $_REQUEST['crosslistSource'];	
				
				$this->_setLocation('copy course reserves list >> create destination class');
				$this->displayClass = 'classDisplayer';
				$this->displayFunction = 'displayCreateClass';
				$this->argList = array('copyNew', 'processCopyClass', $needed_info, 'Create course to copy TO:');	
			break;

			case 'processCopyClass':
				//determine if we are copying or importing
				$importing = isset($_REQUEST['importClass']) ? true : false;
				
				//init the source class
				$sourceClass = (!empty($_REQUEST['sourceClass'])) ? new courseInstance($_REQUEST['sourceClass']) : new courseInstance($_REQUEST['new_ci']);
				$sourceClass->getPrimaryCourse();
				
				//figure out the destination class ID
				if(!empty($_REQUEST['new_ci'])) {
					$dst_ci = $_REQUEST['new_ci'];	//destination class has just been created
				}else{
					$dst_ci = (!empty($_REQUEST['ci'])) ? $_REQUEST['ci'] : $_REQUEST['dst_ci'];
				}
				
				//init the destination class
				$targetClass = new courseInstance($dst_ci);
				$targetClass->getPrimaryCourse();
				
				//init an array to store progress of import/copy/merge process				
				$copyStatus = array();
				
				//make sure that user is not trying to merge the same course
				if($sourceClass->getCourseInstanceID() == $targetClass->getCourseInstanceID()) {
					$copyStatus[] = "Cannot merge a class into itself!";
					//make sure we do nothing else
					$this->displayFunction = 'displayCopySuccess';
					$this->argList = array($sourceClass, $targetClass, $copyStatus, $importing);
					break;
				}

				//split the difference b/n copying and importing
				
				if($importing) {	//importing only
					//copy reserves
					$sourceClass->copyReserves(
						$targetClass->getCourseInstanceID(), 
						$_REQUEST['selected_reserves'], 
						(array_key_exists('requestedLoanPeriod', $_REQUEST) ? $_REQUEST['requestedLoanPeriod'] : '')
					);
					$copyStatus[]="Reserves List sucessfully copied";
				}
				else {	//copying only
					if(isset($request['copyReserves'])) {
						$sourceClass->copyReserves($targetClass->getCourseInstanceID());
						$copyStatus[]="Reserves List sucessfully copied";
					}

					if (isset($request['copyProxies']))
					{
						$sourceClass->getProxies();
	
						for ($i=0; $i<count($sourceClass->proxyIDs); $i++)
						{
							$targetClass->addProxy($targetClass->getPrimaryCourseAliasID(),$sourceClass->proxyIDs[$i]);
						}
	
						$copyStatus[]="Proxies successfully copied";
					}
					
					if(isset($request['copyEnrollment'])) {
						$roll = $sourceClass->getRoll();
						$target_CA_id = $targetClass->getPrimaryCourseAliasID();
						
						foreach($roll as $ci=>$statusstudents) {
							foreach($statusstudents as $status=>$students)
								foreach($students as $student) {
									$student->joinClass($target_CA_id, $status);
								}
							}

						$copyStatus[] = "Enrollment list successfully copied";
					}
				}
				
				//both
				
				if (isset($request['copyCrossListings']))
				{
					try{
						$sourceClass->getCrossListings();	
					} catch (Rd_Exception $e) {
						
					}	

					for ($i=0; $i<count($sourceClass->crossListings); $i++)
					{
						$targetClass->addCrossListing($sourceClass->crossListings[$i]->getCourseID(),$sourceClass->crossListings[$i]->getSection());
					}

					$copyStatus[]="Crosslistings successfully copied";
				}				

				if (isset($request['copyInstructors']))
				{
					$sourceClass->getInstructors();
					try{
						$targetClass->getCrossListings();	
					} catch (Rd_Exception $e) {
						
					}	
					
					$targetClass->getPrimaryCourseAliasID();


					for ($i=0; $i<count($sourceClass->instructorIDs); $i++)
					{
						$targetClass->addInstructor($targetClass->getPrimaryCourseAliasID(), $sourceClass->instructorIDs[$i]);
						for ($k=0; $k<count($targetClass->crossListings); $k++)
						{
							$targetClass->addInstructor($targetClass->crossListings[$k]->getCourseAliasID(),$sourceClass->instructorIDs[$i]);
						}
					}

					$copyStatus[]="Instructors successfully copied";
				}	
				
				//delete source?
				if(!$importing && isset($request['deleteSource'])) {
					$sourceClass->destroy();
					$copyStatus[]="Source Class successfully deleted";
				}
				
				//if crosslist source
				if (isset($request['crosslistSource']))
				{
					$sourceClass->getCourses();					

					foreach ($sourceClass->courseList as $course)
					{						
						$course->bindToCourseInstance($targetClass->getCourseInstanceID());					
						$copyStatus[]= $course->displayCourseNo() . " successfully crosslisted";
					}
					$sourceClass->destroy();
					$copyStatus[]="Source Class successfully deleted";					
				}
				
				$targetClass->setStatus('ACTIVE');

				$this->displayFunction = 'displayCopySuccess';
				$this->argList = array($sourceClass, $targetClass, $copyStatus, $importing);

			break;
		}
	}
}
