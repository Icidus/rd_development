<?php
/*******************************************************************************
requestCollection.class.php
request Collection Object

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

require_once(APPLICATION_PATH . '/classes/request.class.php');

class requestCollection extends ArrayObject 
{	
	
	function sort($sortBy = "request_id")
	{
				
		switch ($sortBy)
		{
			case "call_number":				
				$call_back = "_sort_by_call_number";
			break;
			
			case "request_id":
			default:
				$call_back = "_sort_by_id";
				
		}
		
		//usort($this, array('requestCollection', $call_back));
		$this->uasort(array('requestCollection', $call_back));	
	}
	
	public function id_list()
	{
		$rv = "";
		foreach ($this as $r)
		{
			$rv .= $r->getRequestID() . ",";
		}
		return rtrim($rv, ","); //strip trailing ,
	}
		
	public static function _sort_by_call_number($a, $b)
	{
		return strcasecmp($a->holdings[0]['callNum'], $b->holdings[0]['callNum']);		
	}
	
	public static function _sort_by_id($a, $b)
	{
	    if ($a->request_id == $b->request_id) {
	        return 0;
	    }		
		return ($a->request_id < $b->request_id) ? -1 : 1;
	}	
}