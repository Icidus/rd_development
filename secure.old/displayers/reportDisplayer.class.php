<?php
/*******************************************************************************
reportDisplayer.class.php


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
require_once(APPLICATION_PATH . '/classes/terms.class.php');
require_once(APPLICATION_PATH . '/classes/permissions.class.php');
require_once(APPLICATION_PATH . '/managers/ajaxManager.class.php');
require_once(APPLICATION_PATH . '/lib/Rd/Displayer/Base.php');

class reportDisplayer extends Rd_Displayer_Base {

	protected $_displayerName = 'report';
	/**
	 * Display List all reports available to user
	 *
	 * $reportList array reportID, reportTitle
	 */
	
	function displayReportList($reportList)
	{
		$model = $this->_getDefaultModel();
		$model['list'] = $reportList;
		$this->display('index', $model);
	}
	
	function enterReportParams($report)
	{
		$model = $this->_getDefaultModel();
		$model['report'] = $report;
		$this->display('params', $model);
	}
	
	function displayReport($title, $dataSet)
	{
		$model = $this->_getDefaultModel();
		$model['title'] = $title;
		$model['data'] = $dataSet;
		$this->display('report', $model);
	}
	
	function generateTsvLink($data){
?>
		<div style="text-align:center;">
			<form method="post" action="tsvGenerator.php"><br/>
				<input type="hidden" name="dataSet" value="<?php print(base64_encode(serialize($data))); ?>"/>
	    		<input type="submit" name="exportTsv" value="Export to Spreadsheet"/>
	    	</form>
	    </div>
<?php 
	}

	
	function showTermLibraryForm($report)
	{
		$model = $this->_getDefaultModel();
		$model['report'] = $report;
		$this->display('form/termlibrary', $model);
	}	
	
	function showTermForm($report)
	{
		$model = $this->_getDefaultModel();
		$model['report'] = $report;
		$this->display('form/term', $model);
	}
	
	function showTermUserTypeForm($report)
	{
		$model = $this->_getDefaultModel();
		$model['report'] = $report;
		$this->display('form/termusertype', $model);
	}
	
	function showFiscalYearPicker($report)
	{
		$model = $this->_getDefaultModel();
		$model['report'] = $report;
		$this->display('form/fiscalyearpicker', $model);
	}

	function showTermItemGroupForm($report)
	{
		$model = $this->_getDefaultModel();
		$model['report'] = $report;
		$this->display('form/termitemgroup', $model);
	}
	
	function showTermDateForm($report)
	{
		$model = $this->_getDefaultModel();
		$model['report'] = $report;
		$this->display('form/termdate', $model);
	}
	
	function showTermDatePicker($terms)
	{
		$model = $this->_getDefaultModel();
		$model['terms'] = $terms;
		$this->display('form/termdatepicker', $model);
	}
	
	function showTermDateScript($terms)
	{
		$model = $this->_getDefaultModel();
		$model['terms'] = $terms;
		$this->display('form/termdatejs', $model);
	}
	
	function showTermDateUserTypeForm($report)
	{
		$model = $this->_getDefaultModel();
		$model['report'] = $report;
		$this->display('form/termdateusertype', $model);
	}
	
	function showReportConfigError($param_group)
	{
?>
		<p>This report is not configured properly. Invalid parameter group: "<?php print($param_group); ?>"</p>
<?php 		
	}
}


