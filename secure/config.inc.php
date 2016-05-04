<?php
/*******************************************************************************
config.inc.php
Read config.xml

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
require_once(APPLICATION_PATH . '/lib/Rd/Config.php');
require_once(APPLICATION_PATH . '/lib/Rd/Dictionary.php');
require_once(APPLICATION_PATH . '/lib/Rd/Registry.php');
require_once(APPLICATION_PATH . '/lib/Rd/Pdo.php');
require_once(APPLICATION_PATH . '/lib/Rd/Pdo/PearAdapter.php');
require_once(APPLICATION_PATH . '/lib/Rd/Status.php');
require_once(APPLICATION_PATH . '/lib/Rd/Auth.php');
require_once(APPLICATION_PATH . '/lib/FileExistsInPath.php');

if(
	defined('APPLICATION_STATUS') 
	&& 'install' == APPLICATION_STATUS 
	&& !array_key_exists('install', $_GET)
) {
	Rd_Status::set(500); //#TODO be more helpful? themable?
	die('<html><head><title>ReservesDirect Install Mode</title></head><body>'
		. '<p>This instance of ReservesDirect is in Install Mode, and currently unavailable.</p>'
		. '<p><a href="./?install=true">Install RD?</a></p>'
		. '</body></html>');
} else if (
	defined('APPLICATION_STATUS') 
	&& 'install' == APPLICATION_STATUS
) {
	include(APPLICATION_PATH . '/scripts/installer.php');
	die;
}

if (!is_readable(realpath(APPLICATION_CONF))) {
	Rd_Status::set(500);
	die('<html><head><title>ReserveDirect Confirgutation Not Found</title></head><body>'
		. '<p>Could not read configure xml file path = ' . APPLICATION_CONF . '</p>'
		. '<p>Make sure .appliction_conf is set properly.</p>'
		. (
			defined('APPLICATION_STATUS') && 'install' == APPLICATION_STATUS
			? '<p><a href="./?install=true">Install RD?</a></p>'
			: '<p>You may set .application_status to "install" to perform an installation.</p>'
		) . '</body></html>'); 
}

$configure = simplexml_load_file(APPLICATION_CONF); //#TODO deprecate this, see below

if ('commandline' != APPLICATION_STATUS) {
	require_once(APPLICATION_PATH . '/session.inc.php');
}
require_once(APPLICATION_PATH . '/debug.inc.php');

/* #TODO
 * Please do not put any more new config options in the globals
 * Start using Rd_Config...
 */

//  examples:
//Rd_Config::get('institution'); # single value at root of config file
//Rd_Config::get('database:host'); # single value nested in another tag
//Rd_Config::get('database:+'); # object with properties mapped to tags in this tag
//  i.e. Rd_Config::get('database:host') is similarish to (string)Rd_Config::get('database:+')->host 

Rd_Registry::set('stylesFolder', Rd_Config::get('stylesFolder'));
Rd_Registry::set('stylesTheme', Rd_Config::getOptional('stylesTheme','default'));

Rd_Registry::set('authenticationOptions', Rd_Config::getOptional('authentication:type','Local'));
Rd_Registry::set('authenticationAutocreateUsers', Rd_Config::getOptional('authentication:autocreateUsers','false'));

$g_libraryURL		= (string)$configure->library_url;
$g_name				= (string)$configure->name;
$g_libraryLogo		= (string)$configure->library_logo;
$g_institution		= (string)$configure->institution;

Rd_Registry::set('root:libraryUrl', Rd_Config::get('library_url'));
Rd_Registry::set('root:instanceName', Rd_Config::get('name'));
Rd_Registry::set('root:libraryLogo', Rd_Config::get('library_logo'));
Rd_Registry::set('root:libraryMobileUrl', Rd_Config::get('library_mobile_url'));
Rd_Registry::set('root:libraryMobileLogo', Rd_Config::get('library_mobile_logo'));
Rd_Registry::set('root:institutionName', Rd_Config::get('institution'));

$dsn = array(
    'phptype'  => Rd_Config::get('database:dbtype'),
    'username' => Rd_Config::get('database:username'),
    'password' => Rd_Config::get('database:pwd'),
    'hostspec' => Rd_Config::get('database:host'),
    'database' => Rd_Config::getOptional('database:dbname','reservesdirect'),
    'key'      => Rd_Config::getOptional('database:dbkey',''),
    'cert'     => Rd_Config::getOptional('database:dbcert',''),
    'ca'       => Rd_Config::getOptional('database:dbca',''),
    'capath'   => Rd_Config::getOptional('database:capath',''),
    'cipher'   => Rd_Config::getOptional('database:cipher','')
);

$options = array(
    'ssl' 		=> (string)$configure->database->ssl,
    'debug'     => (string)$configure->database->debug
);

$utf8 = array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8");

$connected = Rd_Pdo::connect($dsn);
if(!$connected) {
	throw new Exception(
		(Rd_Debug::isEnabled() ? Rd_Pdo::getErrorMessage() : '')
		. '<p>Unable to connect to the application database. '
		. ('install' == APPLICATION_STATUS 
			? '<a href="scripts/installer.php">Install RD? </a></p>'
			: Rd_Debug::isEnabled() 
				? 'Switch to "install" mode (APPLICATION_STAUS) if you wish to configure RD.</p> '
				: '</p>'
		)
	);
}

// #TODO temporary adapter to move off PearDB
$g_dbConn = Rd_Pdo::getAdapter($dsn, $options);
if (Rd_Pdo_PearAdapter::isError($g_dbConn)) { //#TODO maybe deprecate?
	trigger_error($g_dbConn->getMessage() . '<p>This instance of RD does not have a properly configured Database. <a href="scripts/installer.php">Install RD?</a></p>', E_USER_ERROR);
}

$g_ldap = array(
	'host'      => Rd_Config::get('ldap:host'),
	'domain'    => Rd_Config::get('ldap:domain'),
	'port'      => Rd_Config::getOptional('ldap:port','389'),
	'version'   => Rd_Config::getOptional('ldap:version','3'),
	'basedn'    => Rd_Config::get('ldap:baseDistinguishedName'),
	'accountbasedn'    => Rd_Config::get('ldap:accountBaseDistinguishedName'),
	'searchdn'	=> Rd_Config::get('ldap:searchDistinguishedName'),
	'searchaccount'	=> Rd_Config::getOptional('ldap:searchAccount',''),
	'searchpassword'	=> Rd_Config::getOptional('ldap:searchPassword',''),
	'canonicalName'    => Rd_Config::get('ldap:userAttributes:canonicalName'),
	'firstname' => Rd_Config::getOptional('ldap:userAttributes:firstName',''),
	'lastname'  => Rd_Config::getOptional('ldap:userAttributes:lastName',''),
	'email'     => Rd_Config::getOptional('ldap:userAttributes:email','')
);

if (Rd_Auth::pluginIsLoaded('Ldap')) {
	Rd_Registry::set('root:ldapConf', $g_ldap);
}

if (Rd_Auth::pluginIsLoaded('Shibboleth')) {
	Rd_Registry::set('root:shibConf', array(
		'authMode' => Rd_Config::getOptional('shib:authMode','optional'),
		'idpField' => Rd_Config::getOptional('shib:idpField',''),
		'usernameField' => Rd_Config::get('shib:usernameField'),
	    'logoutUrl' => Rd_Config::getOptional('shib:logoutUrl','/Shibboleth.sso/Logout'),
		'emailField' => Rd_Config::getOptional('shib:emailField',''),
		'firstnameField' => Rd_Config::getOptional('shib:firstnameField',''),
		'lastnameField' => Rd_Config::getOptional('shib:lastnameField',''),
		'displaynameField' => Rd_Config::getOptional('shib:displaynameField', trim(
			Rd_Config::getOptional('shib:firstnameField','') 
			. ' '
			. Rd_Config::getOptional('shib:lastnameField','')
		)),
		'patronIdField' => Rd_Config::getOptional('shib:patronIdField',''),
		'membershipField' => Rd_Config::getOptional('shib:membershipField',''),
		'returnUrlField' => Rd_Config::getOptional('shib:returnUrlField','return_url'),
		'idps' => Rd_Config::arrayMap(Rd_Config::getOptional('shib:idps:+',array()),true)
	));
}

$g_cacheReports = 'true' == trim((string)$configure->cacheReports);

//$GLOBALS['logfile'] = "/users/krdoerr/www/logFileRD.txt";
$g_maxUploadSize		= (string)$configure->max_upload_size;
Rd_Registry::set('root:uploadLimitSize', Rd_Config::get('max_upload_size'));
$g_error_log			= (string)$configure->error_log;
Rd_Registry::set('root:scriptLogPath', Rd_Config::get('script_log_path'));
$g_errorEmail		 	= (string)$configure->errorEmail;
$g_uploadErrorMessage			= (string)$configure->uploadErrorMessage;
$g_adminEmail		 	= (string)$configure->adminEmail;
$g_reservesEmail		= (string)$configure->reservesEmail;
Rd_Registry::set('root:supportEmail', $g_reservesEmail);
$g_faxDirectory			= (string)$configure->fax->directory;
$g_faxURL				= (string)$configure->fax->URL;
$g_faxCopyright         = (string)$configure->fax->copyright;
$g_faxLog               = (string)$configure->fax->log;
$g_fax2pdf_bin          = (string)$configure->fax->fax2pdf_bin;
$g_faxinfo_bin          = (string)$configure->fax->faxinfo_bin;
$g_gs_bin               = (string)$configure->fax->gs_bin;
$g_documentDirectory	= (string)$configure->documentDirectory;
$g_documentURL			= (string)$configure->documentURL; //#TODO deprecate, appears to no longer be used...
$g_docCover				= (string)$configure->documentCover;

$instancePrefix = APPLICATION_PROTOCOL . '://';
$instanceHost = (
	'commandline' != APPLICATION_STATUS 
	? $_SERVER['HTTP_HOST']
	: 'localhost'
);
$instanceDir = rtrim('/',dirname($_SERVER['PHP_SELF'])); // #TODO trim the ending slash for now, even though it is technically prefered...
$g_siteURL = Rd_Config::getOptional('siteURL', '__AUTO__');
if('__AUTO__' == $g_siteURL) {
	$g_siteURL = $instancePrefix . $instanceHost . $instanceDir;
}
Rd_Registry::set('root:mainUrl', $g_siteURL);
Rd_Registry::set('root:mainUrlProper', $g_siteURL . '/'); //#TODO trailing slashes are always preffered for HTTP

//$g_librarySiteURL		= (string)$configure->librarySite_url;
$g_serverName           = (string)$configure->serverName;
$g_copyrightNoticeURL	= (string)$configure->copyrightNoticeURL;
$g_newUserEmail['subject']  = (string)$configure->newUserEmail->subject;
$g_newUserEmail['msg']  = (string)$configure->newUserEmail->msg;	
$g_specialUserEmail['subject']  = (string)$configure->specialUserEmail->subject;
$g_specialUserEmail['msg']  = (string)$configure->specialUserEmail->msg;
$g_specialUserDefaultPwd = (string)$configure->specialUserDefaultPwd;

$g_EmailRegExp = (string)$configure->emailRegExp;
Rd_Registry::set('root:emailRegExp',Rd_Config::get('emailRegExp')); //#TODO make some kind of utility for filters
Rd_Registry::set('root:dbDateRegExp',Rd_Config::get('dbDateRegExp'));
Rd_Registry::set('root:dbDateFormat','Y-m-d');
Rd_Registry::set('root:dbDateTimeFormat','Y-m-d H:i:s');



//zWidget configuration
$g_zhost 			= (string)$configure->catalog->zhost;
$g_zport 			= (string)$configure->catalog->zport;
$g_zcisport			= (string)$configure->catalog->zcisport;
$g_zdb	 			= (string)$configure->catalog->zdb;
$g_zReflector		= (string)$configure->catalog->zReflector;
$g_ciurl			= (string)$configure->catalog->ciurl;
$g_catalogName		= (string)$configure->catalog->catalogName;
$g_reserveScript	= (string)$configure->catalog->reserve_script;
$g_holdingsScript	= (string)$configure->catalog->holdings_script;
$g_uniqueIdPrefix	= (string)$configure->catalog->uniqueIdPrefix;
$g_reservesViewer	= (string)$configure->catalog->web_search;
$g_mReservesViewer 	= (string)$configure->catalog->mobile_web_search;
$g_textbookSearch	= (string)$configure->catalog->textbook_search;
Rd_Registry::set('root:catalogUrl', Rd_Config::get('catalog:searchUrl'));
$g_recordDisplay	= (string)$configure->catalog->recordDisplay;
Rd_Registry::set('root:titleRecordUrl', $g_recordDisplay);
//Video Configuration
//$g_useVideo			= (string)$configure->video->useVideoUpload;
$useVideo = strtolower(Rd_Config::get('video:enabled'));
Rd_Registry::set(
	'root:videoReservesEnabled',
	'true' == $useVideo
);
if ($useVideo) {
	Rd_Registry::set('root:videoUploadPath',Rd_Config::get('video:uploadHoldingPath'));
}
$g_encoderServer	= (string)$configure->video->encoderServer;
$g_encoderScript	= (string)$configure->video->encoderScript;
$g_streamingServer	= (string)$configure->video->streamingServer;

$g_courseWare		= (string)$configure->courseware_system;
	
// Defaults for scanned items
$g_scanningLibrary	= (string)$configure->scan->defaultScanLibrary;
$g_scannedItemGroup	= (string)$configure->scan->defaultItemGroup;


$loadCourseTools = Rd_Config::getOptional('course_tools:flag','false');
Rd_Registry::set('root:courseToolsLoaded', 'false' != $loadCourseTools);
if ('false' != $loadCourseTools) {
	$g_courseToolsFlag	= $loadCourseTools;
	$g_courseToolsURL	= Rd_Config::getOptional('course_tools:url','false');
	$g_courseToolsName	= Rd_Config::getOptional('course_tools:name','false');
	
	Rd_Registry::set('root:courseTools', array(
		'url' => "{$g_courseToolsURL}{$g_courseToolsName}"
	));
}

$g_request_notifier_lastrun = (string)$configure->request_notifier->last_run;
$g_activation_padding_days = (integer)$configure->registar_feed->activation_padding_days;
$g_expiration_padding_days = (integer)$configure->registar_feed->expiration_padding_days;
$g_EZproxyAuthorizationKey = (string)$configure->EZproxyAuthorizationKey;
$g_BlackboardLink = (string)$configure->BlackBoardLink;

$g_ldapServiceUrl = (string)$configure->ldapServiceUrl;

$trustedSystems = $configure->trusted_systems;
foreach ($trustedSystems->system as $sys)
{	
	$k = (string)$sys['id'];
	$t = (string)$sys['timeout'];
	$g_trusted_systems[$k]['secret'] = (string)$sys;    	
	$g_trusted_systems[$k]['timeout'] = $t;    	
	unset($k, $t);
}
unset($trustedSystems);
    
$g_courseware = array();
$courseware = $configure->courseware;
$x=0;
foreach ($courseware->courseware_system as $cw){
	$g_courseware[$x]['name'] = (string)$cw->name;
	$g_courseware[$x]['url'] = (string)$cw->url;
	$x++;
}

$g_ils = (string)$configure->ils->class_name;

Rd_Dictionary::loadLanguage($configure->dictionary->default_language);

$g_no_javascript_msg = Rd_Dictionary::get('messages:noJsWarning');

Rd_Registry::set('itAssistanceMessage', Rd_Config::get('itAssistanceMessage'));
Rd_Registry::set('assistanceMessage', Rd_Config::get('assistanceMessage'));