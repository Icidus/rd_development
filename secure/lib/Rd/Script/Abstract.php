<?php
/*******************************************************************************
Rd/Script/Abstract.php
Implements a base class for managed script execution

Created by Troy Hurteau, NCSU Libraries, NC State University (libraries.opensource@ncsu.edu).

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

/**
 * 
 * Abstract script wrapper.
 * @author jthurtea
 *
 */

class Rd_Script_Abstract{
	
	protected $_path = NULL;
	
	public function run() {
		$fullPath = APPLICATION_PATH . $this->_path;
		$outputLines = array();
		$status = NULL;
		$return = "Running {$fullPath} \n------------------------------------------------------------\n";
		$result =  exec($fullPath, $outputLines, $status);
		$fullResult = implode("\n",$outputLines);
		$statusDescription = $this->_explainStatus($status);
		$return .= (
			is_null($status)
			? 'An error occured attempting to run the script.'
			: "Status: {$statusDescription}\n" . $fullResult
		);
		return $return;
	}
	
	protected function _explainStatus($code)
	{
		switch($code)
		{
			case 0:
				return 'Script executed.';
			case 126:
				return 'Requested script is not file executable.';
			case 127:
				return 'Requested script does not exist.';
			case 255:
				return 'Script encountered a fatal error.';
			default:
				return "Unknown Status Result: {$code}";
		}
	}
	
}
