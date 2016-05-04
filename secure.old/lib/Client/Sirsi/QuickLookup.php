<?php
/*******************************************************************************
Client/QuickLookup.php
Sirsi service for accessing textthis data on items by catkey

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
 * Client for the textthis service running on Sirsi
 * @author jthurtea
 *
 */

class Rd_Client_Sirsi_QuickLookup{ //#TODO this should not include Rd_ ...

	static function getText($catKey){
			if(strpos('NCLIVE', $catKey) === 0){
				return "NC Live Resources are not supported at this time. Unsupported Catalog Key: {$catKey}";
			}
			$serviceUrl = Rd_Config::get('catalog:textThis');
			$rawKey = str_replace('NCSU', '', $catKey);
			$curl = curl_init();
			$options = array(
				CURLOPT_URL => $serviceUrl.$rawKey,
				CURLOPT_HEADER => false,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_CONNECTTIMEOUT => 1
			);
			curl_setopt_array($curl, $options);
			$result = curl_exec($curl);
			Rd_Debug::out(curl_error($curl));
			curl_close($curl);
			
			return str_replace('\n', "<br/>\n", $result);
	}
}