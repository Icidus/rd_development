<?php
/*******************************************************************************
index.php
primary processing and display page

Created by Jason White (jbwhite@emory.edu)
Modified by NCSU Libraries, NC State University. Modifications by Karl Doerr & Troy Hurteau (libraries.opensource@ncsu.edu).

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
define('APPLICATION_START_TIME', microtime(true));

if(file_exists('localize.php')){
	require_once('localize.php');
}

require_once('DefineLoad.php');
require_once('constants.php');

if (!file_exists(APPLICATION_PATH . '/headers.inc.php')) {
	die('This application appears to be misconfigured. Unable to locate critical files (check the APPLICATION_PATH settings).');
}
require_once(APPLICATION_PATH . '/headers.inc.php');

try {
	require_once(APPLICATION_PATH . '/config.inc.php');
} catch (Exception $e) {
	if (Rd_Debug::isEnabled()) {
		Rd_Debug::out('<pre>' . $e->getTraceAsString() . '</pre>');
	}
	print('This application appears to be misconfigured. ' . $e->getMessage());
	die;
}

if( defined('APPLICATION_STATUS') && 'down' == APPLICATION_STATUS) {
	Rd_Status::set(500); //#TODO have a better include page for this, themable
	die('<html><head><title>ReservesDirect Maintenance Mode</title></head><body>'
		. '<p>This instance of ReservesDirect is in Maintenance Mode, and currently unavailable.</p>'
		. '</body></html>'); 
}

require_once(APPLICATION_PATH . '/common.inc.php');
require_once(APPLICATION_PATH . '/startup.inc.php');

if (Rd_Debug::isEnabled()) {
	Rd_Debug::outputRequest();
	Rd_debug::printDebugExit();
}

$defaultCommand = Rd_Dispatch::COMMAND_KEY_DEFAULT;
$cmd = (
	array_key_exists('cmd', $_REQUEST) && '' != trim($_REQUEST['cmd']) 
	? trim($_REQUEST['cmd']) 
	: $defaultCommand
);
Rd_Registry::set('root:requestCommand',$cmd);
try {
	$cmd = Rd_Acl::allowedCommand(Account_Rd::getUserInterface(), $cmd);
} catch (Exception $e) {
	$cmd = $defaultCommand;
	if (Rd_Debug::isEnabled()) {
		Rd_Debug::out('Authentication plugin failure, User not initiated properly.');
		if (!Rd_Layout::hasMessage('loginError')) {
			Rd_Layout::setMessage('loginError', Rd_CodeLookup::tryMessage('loginFailure', '010'));
		}
	}
	require_once(APPLICATION_PATH . '/login.php');
	Rd_Debug::dieSafe();
}
Rd_Registry::set('root:commandStack',array($cmd));
Rd_Debug::out("cmd={$cmd}");

if ('login' == $cmd) { //#TODO pull this into the manager architecture...
	require_once(APPLICATION_PATH . '/login.php');
	Rd_Debug::dieSafe();
}

require_once (APPLICATION_PATH . '/doMisc.php');

try {
	$mgr = Rd_Dispatch::getManager($cmd);
	Rd_Registry::set('root:rootManager',$mgr);
} catch (Rd_Exception_RedirectWorkflow $e) {
	$url = $e->getMessage();
	$keepInHistory = true; //#TODO decide how/when to keep these out of browser history
	Rd_Dispatch::redirect($e->getMessage(), $keepInHistory);
} catch (Exception $e) {
	Rd_Layout_Tab::set('500');
	require_once(APPLICATION_PATH . '/managers/errorManager.php');
	$mgr = new errorManager('applicationError', array('e'=>$e));
	Rd_Registry::set('root:rootManager',$mgr);
}


require_once(APPLICATION_PATH . '/doLayout.php');

Rd_Debug::dieSafe();
