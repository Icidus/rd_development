<?php
/*******************************************************************************
Custodian.class.php
Custodian Object

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
require_once(APPLICATION_PATH . '/interface/student.class.php');
require_once(APPLICATION_PATH . '/classes/specialUser.class.php');

class custodian extends student
{
	var $sp;

	function custodian($userName=null) {
		if(!empty($userName)) {
			$this->getUserByUserName($userName);	
		}
	}

	function createSpecialUser($userName, $email, $date=null)
	{
		//this function is duplicated in the staff class
		$sp = new specialUser();
		return (string) $sp->createNewSpecialUser($userName, $email, $date);
	}

	function resetSpecialUserPassword($userName)
	{
		//this function is duplicated in the staff class
		$this->sp = new specialUser();
		$this->sp->resetPassword($userName);
	}

	function getSpecialUsers()
	{
		//this function is duplicated in the custodian class
		global $g_dbConn;

		$sql = "SELECT sp.user_id FROM special_users as sp JOIN users as u ON sp.user_id = u.user_id ORDER BY u.username";

		$rs = $g_dbConn->query($sql);

		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }

		$tmpArray = array();
		while($row = $rs->fetch(PDO::FETCH_NUM))
		{
			$tmpArray[] = new user($row[0]);
		}
		return $tmpArray;
	}

	function getSpecialUserMsg() { return $this->sp->getMsg(); }
}
