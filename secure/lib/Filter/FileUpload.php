<?php
/*******************************************************************************
Filter/FileUpload.php
FileUpload integration service

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
 * Filter to map standard JSON data service returns to what the fileupload.js scripts expect
 * @author jthurtea
 *
 */

class Filter_Fileupload{

	public static function filter($subject)
	{
		if (array_key_exists('result',$subject)) {
			if(!is_array($subject['result'])){
				$subject['result'] = json_decode($subject['result']);
			}
			foreach($subject['result'] as $index=>$value){
				if (array_key_exists($index,$subject)) {
					$subject['_original_'. $index] = $subject[$index];
				}
				$subject[$index] = $value;
			}
			unset($subject['result']);
			if (array_key_exists('error', $subject)) {
				$subject['success'] = false;
			}
		}
		return $subject;
	}
	
}
