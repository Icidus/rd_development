<?php
/*******************************************************************************
exportManager.class.php

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
require_once(APPLICATION_PATH .'/classes/courseInstance.class.php');
require_once(APPLICATION_PATH .'/displayers/exportDisplayer.class.php');

class exportManager extends Rd_Manager_Base {
	public $user;

	function exportManager($cmd) {
		global $g_permission, $g_siteURL, $ci;
		$u = Rd_Registry::get('root:userInterface');
		switch ($cmd) {
			case 'generateBB':
				$ci = new courseInstance($_REQUEST['ci']);
				$ci->getPrimaryCourse();

				$filename = 'blackboard_' . $ci->course->displayCourseNo() . '.html';
				$filename = str_replace(' ', '_', $filename);
				
				$data = "<HTML>\n";
				$data .= "	<HEAD>\n";		
				$data .= "		<script src=\"". $g_siteURL."/reservelist.php?style=reserves&ci=". $ci->getCourseInstanceID() ."\"></script>\n";		
				$data .= "	</HEAD>\n";
				$data .= "</HTML>\n";		
				
				header("Content-Type: text/plain");
				header("Content-Disposition: attachment; filename=\"$filename\"");
                header("Cache-control: ");
                header("Pragma: ");
				
				echo $data;
				exit;
			break;		
				
			default:
				$this->displayClass = 'exportDisplayer';
				$this->_setLocation('export class');
				//set the page (tab)
				if($u->getRole() >= Account_Rd::LEVEL_STAFF) {
					$this->_setTab('manageclasses');
				}
				else {
					$this->_setTab('myReserves');
				}
				
				if(empty($_REQUEST['ci'])) {	//get ci
					//get array of CIs (ignored for staff)
					$courses = $u->getCourseInstancesToEdit();
					
					$this->displayFunction = 'displaySelectClass';
					$this->argList = array($cmd, $courses, 'Select class to export');
				}
				elseif(empty($_REQUEST['course_ware'])) {	//get export option
					$course = new courseInstance($_REQUEST['ci']);
					
					$this->displayFunction = 'displaySelectExportOption';
					$this->argList = array($course);
				}
				else {
					$course = new courseInstance($_REQUEST['ci']);
					$course->getCourseForUser();
					
					$this->displayFunction = 'displayExportInstructions_'.$_REQUEST['course_ware'];
					$this->argList = array($course);
				}
		}
	}
	
}
