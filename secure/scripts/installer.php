<?php 
/*******************************************************************************
installer.php

Created by Emory University.
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
require_once(APPLICATION_PATH . '/lib/Xml/Form.php');
require_once(APPLICATION_PATH . '/lib/Rd/Layout.php');

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
	<head>
		<title>ReservesDirect Installer</title>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
		
		<style type="text/css" media="all">
			body {
				background-color: #F9F9F9;
				color: black;
				font-family: sans-serif;
				margin:2em;
			}
			
			input {
				padding: 2px;
			}
			
			.error { color: red; }
			.warning { color: #ca0; }
			.success { color: green; }
			.normal {color:black; }
			.nobreak {white-space:pre;}
			.small {font-size:0.75em;}
			label {display:block; clear:both;margin-top:0.5em;}
		</style>
		<?php Rd_Layout::printJquery(); ?>
	</head>
	
	<body>

<?php
/****************************************************************************
<pre>  
  
			No-PHP Warning
			--------------
			
If you can read this in your browser, then you do not have PHP installed.

Please revew the server requirements for ReservesDirect:

	- Apache HTTPd
	- PHP 5+ with:
		- MySQL client
		- SimpleXML functions
		- DOM functions
	- MySQL 4.1+
	
Please visit http://code.google.com/p/reservesdirect-ncsu/ for more information.

</pre>
*****************************************************************************/
ini_set('display_errors', 'On');
error_reporting(E_ALL | E_STRICT);
$validSteps = array(
	'one',
	'two',
	'three',
	'four',
	'phpinfo'
);
define('RD_ROOT', realpath('..').'/');
define('HELP_URL', 'http://code.google.com/p/reservesdirect-ncsu/');
define('HELP_LINK_LABEL','NCSU ReservesDirect Google Code Repository');
//grab step from form/url
$step = array_key_exists('step', $_REQUEST)
	? in_array($_REQUEST['step'], $validSteps)
		? $_REQUEST['step']
		: 'error'
	: 'one';

include(APPLICATION_PATH . "/scripts/installer/{$step}.php");
?>
	</body>
</html><?php 

/**
 * Print out messages in different colors
 * 
 * @param string $msg
 */
function print_error($msg) {
	echo '<p class="error">ERROR: '.$msg.'</p>';
}
function print_warning($msg) {
	echo '<p class="warning">WARNING: '.$msg.'</p>';
}		
function print_success($msg) {
	echo '<p class="success">SUCCESS: '.$msg.'</p>';
}