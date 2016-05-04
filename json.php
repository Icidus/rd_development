<?php
/*******************************************************************************
json.php
Provides RD Data Service

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
define('APPLICATION_START_TIME', microtime(true));

if(file_exists('localize.php')){
	require_once('localize.php');
}

require_once('DefineLoad.php');
require_once('constants.php');
require_once(APPLICATION_PATH . '/lib/FileExistsInPath.php');

require_once(APPLICATION_PATH . '/config.inc.php');
require_once(APPLICATION_PATH . '/common.inc.php');
require_once(APPLICATION_PATH . '/session.inc.php');

require_once(APPLICATION_PATH . '/debug.inc.php');
Rd_Auth::autodetect();
require_once(APPLICATION_PATH . '/lib/Rd/Resolver.php');
require_once(APPLICATION_PATH . '/lib/Service/Key.php');
require_once(APPLICATION_PATH . '/lib/Service/Json.php');

require_once(APPLICATION_PATH . '/classes/Account/Rd.php');

require_once(APPLICATION_PATH . '/lib/Filter/FileUpload.php');
$resourceFilters = array(
	'fileupload' => new Filter_FileUpload()
);

try{
	Rd_Resolver::resolve();
	$connected = Rd_Pdo::connect($dsn);
	if (!$connected) {
		throw new Exception('Unable to Connect to the Database.' . Rd_Pdo::getErrorMessage());
	}
	Account_Rd::init();
	$resource = Rd_Resolver::getResource();
	$action = Rd_Resolver::getAction();
	$useMultiple = '' != Rd_Resolver::getParameters();
	
	$information = (
		'' != $action
		? Service_Json::resource($resource, (
			$useMultiple
			? array_merge(
				array($action), 
				array(Rd_Resolver::getParameters()), 
				Rd_Resolver::getAdditional()
			)
			: $action
		))
		: Service_Json::resource($resource)
	);
	$information['executionTime'] = (microtime(true) - APPLICATION_START_TIME);
} catch (Exception $e){
	Rd_Status::set(500);
	$information = array(
		'success' => false,
		'executionTime' => (microtime(true) - APPLICATION_START_TIME),
		'message' => $e->getMessage(),
		'errorType' => 'RD_SERVICE_EXCEPTION'
	);
	if (Rd_Debug::isEnabled()) {
		$information['exceptionStack'] = $e->getTrace();
	}
}

if (isset($resource) && array_key_exists($resource, $resourceFilters)) {
	print(json_encode($resourceFilters[$resource]->filter($information)));	
} else {
	print(json_encode($information));
}

die;
