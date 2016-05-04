<?php
/*******************************************************************************
reportManager.class.php


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
require_once(APPLICATION_PATH . '/displayers/reportDisplayer.class.php');
require_once(APPLICATION_PATH . '/classes/report.class.php');

class reportManager extends Rd_Manager_Base 
{

	
	function indexAction($request = array())
	{
		$this->_setLocation('view system statistics');
		$this->_setHelp(11);
		$this->displayFunction = "displayReportList";
		$this->argList = array($this->_user->getReportList());
	}
	
	function reportsTabAction($request)
	{
		$this->indexAction($request);
	}
	
	function viewReportAction($request)
	{
		$report = new cachedReport((int)$_REQUEST['reportID']);	//init the object
		$this->_setLocation('view system statistics');
		$this->_setHelp(11);		
		if (array_key_exists('item_group', $_REQUEST)) {
			if(is_array($_REQUEST['item_group'])){
				foreach($_REQUEST['item_group'] as $itemGroupIndex=>$itemGroupValue){
					$_REQUEST['item_group'][$itemGroupIndex] = "'{$itemGroupValue}'";
				}						
			} else {
				$_REQUEST['item_group'] = "'{$_REQUEST['item_group']}'";
			}
		}
		$report->fillParameters($_REQUEST);	//attempt to fill parameters
		if($report->checkParameters()) {	//are all the parameters filled in?
			$this->displayFunction = "displayReport";
			$title = $report->getTitle();
			if (array_key_exists('begin_date', $_REQUEST)) {
				$title .= " from {$_REQUEST['begin_date']}";
			}
			if (array_key_exists('end_date', $_REQUEST)) {
				$title .= " to {$_REQUEST['end_date']}";
			}
			$this->argList = array($title, $report->doQry());	//run the query and display results
		} else {	//need to fill params
			$this->displayFunction = "enterReportParams";
			$this->argList = array($report);
		}
	}
}
