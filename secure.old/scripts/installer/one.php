<?php 
/*******************************************************************************
installer/one.php

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
?>
<h2>Step 1: Server check</h2>
<?php 
$phpPass = check_php_version();
$mySqlPass = check_mysql();
$xmlPass = check_simplexml();
$domPass = check_dom();
?>

<hr />
<?php 

if ($phpPass && $mySqlPass && $xmlPass && $domPass) {
?><p>All requirements seem in order. <a href="./?install=true&step=two">Proceed to the next step.</a></p><?php 
}

function check_php_version() 
{
	$requiredVersion = '5.2.3';
	if(!function_exists('version_compare') || version_compare(phpversion(), $requiredVersion, '<')) {
		print_error('PHP ' . $requiredVersion . ' or higher is required. Version ' . phpversion() . ' was detected.');
		return false;
	} else {
		// Test for PHP bug which breaks PHP 5.0.x on 64-bit...
		// As of 1.8 this breaks lots of common operations instead
		// of just some rare ones like export. 
		//#TODO this is definately deprecated, but keeping it in as an example of how to handle such issues.
		$borked = str_replace('a', 'b', array(-1 => -1));
		if(!isset($borked[-1])) {
			print_error('PHP 5.0.x is buggy on 64-bit systems; you must upgrade to PHP 5.1.x or higher.<br />(http://bugs.php.net/bug.php?id=34879 for details).');
			return false;
		}
	}
	print_success('PHP version fine -- ' . phpversion());	
	return true;
}


/**
 * Checks to make sure PHP has MySQL PDO functionality
 */
function check_mysql() 
{
	$supported_dbs = array(
		'pdo_mysql' => 'MySQL PDO'
		//'mysql' => 'MySQL', 
		//'mysqli' => 'MySQLi'
	);
	$hasDb = false;
	$DbName = '';
	foreach($supported_dbs as $db => $name) {
		if(extension_loaded($db) or dl($db.'.'.PHP_SHLIB_SUFFIX)) {
			$hasDb = true;
			$DbName = $name;
			break;
		}
	}
	if(!$hasDb) {
		print_error('MySQL PDO DB client for PHP is required.');
		return false;
	} else {
		print_success("Found {$DbName} DB client.");
		return true;
	}
}

/**
 * Checks for simpleXML
 */
function check_simplexml() 
{
	if(extension_loaded('libxml') && extension_loaded('SimpleXML')){
		if(function_exists('simplexml_load_file')) {
			print_success('Found SimpleXML.');
			return true;
		}
	}
	print_error('SimpleXML is required.');
	return false;
}

/**
 * checks DOM functionality;  Should be built into PHP5
 */
function check_dom() 
{
	if(extension_loaded('dom')
		&& class_exists('DOMDocument') 
		&& class_exists('DOMXPath')
	) {
		return true;
	}
	else {
		print_error('DOM functionality (DOMDocument, DOMXPath, etc.) is required.');
		return false;
	}
}
	