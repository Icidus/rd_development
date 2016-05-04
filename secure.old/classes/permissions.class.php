<?php
/*******************************************************************************
permissions.class.php
permissions object handles permissions_levels table

Created by Troy Hurteau (libraries.opensource@ncsu.edu).

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

class permissions
{
	static function getAllIds()
	{
		global $g_dbConn;
		$tmpArray = array();

		$sql = "SELECT permission_id "
			. "FROM permissions_levels "
			. "ORDER BY permission_id ASC ;";

		$rs = $g_dbConn->query($sql);
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }

		while($rows = $rs->fetch(PDO::FETCH_NUM)){
			$tmpArray[] = $rows[0];
		}

		return $tmpArray;
	}
	
	static function getLabel($id)
	{
		global $g_dbConn;

		$sql = 	"SELECT label "
			.		"FROM permissions_levels "
			. 		"WHERE permission_id = {$id} ;";				

		$rs = $g_dbConn->query($sql);
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }


		while($rows = $rs->fetch(PDO::FETCH_NUM)){
			return $rows[0];
		}

		return '';
	}	
}
