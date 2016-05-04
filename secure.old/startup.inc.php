<?php
/*******************************************************************************
startup.inc.php

Created by Troy Hurteau (jthurtea@ncsu.edu) NCSU Libraries, NC State University (libraries.opensource@ncsu.edu).

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

require_once(APPLICATION_PATH . '/classes/calendar.class.php');
require_once(APPLICATION_PATH . '/classes/users.class.php');
//require_once(APPLICATION_PATH . '/classes/skins.class.php');
require_once(APPLICATION_PATH . '/lib/Rd/Layout.php'); //#TODO consider consolidating the layout objects
require_once(APPLICATION_PATH . '/lib/Rd/Layout/Tab.php');
require_once(APPLICATION_PATH . '/lib/Rd/Layout/Location.php');
require_once(APPLICATION_PATH . '/classes/news.class.php');

require_once(APPLICATION_PATH . '/interface/student.class.php');
require_once(APPLICATION_PATH . '/interface/custodian.class.php');
require_once(APPLICATION_PATH . '/interface/proxy.class.php');
require_once(APPLICATION_PATH . '/interface/instructor.class.php');
require_once(APPLICATION_PATH . '/interface/staff.class.php');
require_once(APPLICATION_PATH . '/interface/admin.class.php');

require_once(APPLICATION_PATH . '/managers/ajaxManager.class.php');
require_once(APPLICATION_PATH . '/managers/noteManager.class.php');

require_once(APPLICATION_PATH . '/lib/Rd/Email.php');

if(strpos($_SERVER['QUERY_STRING'], '%26') !== false){ //#TODO make this a utility?
	$redirect = (str_replace( '%26', '&', $_SERVER['REQUEST_URI'])); //primarily for a shibboleth redirect quirk	
	Rd_Dispatch::redirect($redirect, false);
}

//require_once(APPLICATION_PATH . '/functional_permissions.inc.php');

//
// Skins
//

$skins = new skins(); //#TODO this feature is almost certainly defunct...

if (
	!array_key_exists('css', $_SESSION) 
	|| !array_key_exists('theme', $_SESSION)
	|| '' == trim($_SESSION['css'])
	|| '' == trim($_SESSION['theme'])
){
	$theme = Rd_Registry::get('stylesTheme');
    $_SESSION['theme'] = $theme;
	$_SESSION['css'] = $skins->getSkin($theme);
}

Rd_Auth::autodetect();

