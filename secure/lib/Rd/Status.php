<?php
/*******************************************************************************
Rd/Status.php
Implements a utility for status headers

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
 * Class for managing HTTP headers
 * @author jthurtea
 *
 */

class Rd_Status{
	
	public static function set($status){
		switch ($status){ //#TODO 201, 202, 301, 302, 303
			case 400:
			case '400':
				header("{$_SERVER["SERVER_PROTOCOL"]} 400 Bad Request");
				break;
			case 401:
			case '401':
				header("{$_SERVER["SERVER_PROTOCOL"]} 400 Unauthorized");
				break;
			case 404:
			case '404':
				header("{$_SERVER["SERVER_PROTOCOL"]} 404 Not Found");
				break;
			case 410:
			case '410':
				header("{$_SERVER["SERVER_PROTOCOL"]} 410 Gone");
				break;
			case 413:
			case '413':
				header("{$_SERVER["SERVER_PROTOCOL"]} 413 Request Entity Too Large");
				break;
			case 500:
			case '500':
				header("{$_SERVER["SERVER_PROTOCOL"]} 500 Internal Server Error");
				break;
			case 502:
			case '502':
				header("{$_SERVER["SERVER_PROTOCOL"]} 502 Bad Gateway");
				break;
			case 503:
			case '503':
				header("{$_SERVER["SERVER_PROTOCOL"]} 503 Service Unavailable");
				break;
			case 504:
			case '504':
				header("{$_SERVER["SERVER_PROTOCOL"]} 504 Gateway Timeout");
				break;
			default:
				
		}
	}	
}