<?php
/*******************************************************************************
lookupManager.class.php


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
require_once(APPLICATION_PATH . '/common.inc.php');
require_once(APPLICATION_PATH . '/classes/users.class.php');
require_once(APPLICATION_PATH . '/displayers/lookupDisplayer.class.php');

class lookupManager
{
	public $user;
	public $displayClass;
	public $displayFunction;
	public $argList;

	function display()
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


	function lookupManager($tableHeading="CLASS LOOKUP", $cmd, $user, $request, $hidden_fields=null)
	{
		global $g_permission;

		$this->displayClass = "lookupDisplayer";

		switch ($cmd)
		{
			case 'lookupInstructor':
				
				if (isset($request['select_instr_by']) && isset($request['instr_qryTerm'])) //user is searching for an instructor
				{
					$users = new users();
					$users->search($request['select_instr_by'], $request['instr_qryTerm'], $g_permission['proxy']);
					$instr_list = (isset($users->userList) ? $users->userList : array());
				} else $instr_list = null;


				$this->displayFunction = 'instructorLookup';
				$this->argList = array($instr_list, $request);
			break;
		}

	}
}
