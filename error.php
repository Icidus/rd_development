<?php 
/*******************************************************************************
error.php

Created by Jason White (jbwhite@emory.edu)
Modified by NCSU Libraries, NC State University. Modifications by Karl Doerr and Troy Hurteau (libraries.opensource@ncsu.edu).

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
if (!class_exists('Rd_Registry')) {
	if(file_exists('localize.php')){
		require_once('localize.php');
	}
	
	require_once('DefineLoad.php');
	require_once('constants.php');
	require_once(APPLICATION_PATH . '/lib/FileExistsInPath.php');
	
	require_once(APPLICATION_PATH . '/headers.inc.php');
	require_once(APPLICATION_PATH . '/config.inc.php');
	require_once(APPLICATION_PATH . '/common.inc.php');
}

try {
	$name = Rd_Registry::get('root:instanceName');
	$logo = Rd_Registry::get('root:libraryLogo');
} catch(Exception $e) {
	$name = 'This Application';
	$logo = 'public/images/powered_by_rd.gif';
}

if(array_key_exists('hardReset', $_REQUEST) && $_REQUEST['hardReset']) {
	Rd_Registry::cleanSession();
	Rd_Dispatch::redirect(Rd_Registry::get('root:mainUrlProper'));
}

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
	<title><?php print($name); ?></title> 
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	<link href="public/css/ReservesStyles.css" rel="stylesheet" type="text/css">
</head>
<body>
<div id="header"><img src="<?php print($logo); ?>"></div>

<div id="content">
	<p><?php print($name); ?> experienced an error during the action you were trying to perform. If this error occurs again, closing your browser and then logging into the system again may help.</strong></p>
	<p><a href="index.php">RETURN TO <?php print(strtoupper($name)); ?></a></p>
	<p>If you continue to experience this error, you may want to <a href="error.php?hardReset=1">log out</a> and try logging in again.</p>
<?php 
	if(Rd_Debug::isAvailable()){
		Rd_Debug::printDebugEntry();
	}
?>
</div>
</body>
</html>
