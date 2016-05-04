<?php
/*******************************************************************************
circRules.class.php
Circulation Rules Primitive Object

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

class circRules
{
	function circRules() {}

	function getCircRules()
	{		
		$sql = 	"SELECT circ_rule, alt_circ_rule, default_selected FROM circ_rules ORDER BY circ_rule";

		$result = Rd_Pdo::query($sql);
		if (Rd_Pdo_PearAdapter::isError($result)) { 
			trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($result), E_USER_ERROR); 
		}
		$cRules = array();
		print_r(array($result->rowCount()));
		while($row = $result->fetch(PDO::FETCH_NUM))
		{
			print_r($row);
			$cRules[] = array(
				'circRule' => $row[0],
				'alt_circRule' => $row[1],
				'default' => ($row[2] == 'yes') ? 'selected="selected"' : ''
			);
		}
		return $cRules;
	}


}
