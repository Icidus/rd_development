<?php
/*******************************************************************************
errorDisplayer.php

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
require_once(APPLICATION_PATH . '/lib/Rd/Displayer/Base.php');

class errorDisplayer extends Rd_Displayer_Base{

	protected $_displayerName = 'error';
		
	/**
	 * @return void
	 * @desc Displays the screen to confirm multiple submitted reserve items.
	 */
	function index($request) {
		global $g_siteURL;
		//#TODO this is a really late place to do these
		defineLoad('STANDARD_PORT', '80');
		// Define an exception format
		defineLoad('SSL_PORT', '443');
		$requestProtocol = (
			array_key_exists('HTTPS', $_SERVER) 
				&& $_SERVER['HTTPS'] 
				&& $_SERVER['HTTPS'] != 'off' 
			? 'https://' 
			: 'http://'
		);
		$optionalPort = (
			$_SERVER['SERVER_PORT'] != STANDARD_PORT 
				&& $_SERVER['SERVER_PORT'] != SSL_PORT
				&& false === strpos($_SERVER['HTTP_HOST'], ":{$_SERVER['SERVER_PORT']}")
			? ":{$_SERVER['SERVER_PORT']}" 
			: ''
		);
		$promptIt = (
			array_key_exists('exception', $request) 
			&& !is_a($request['exception'], 'Rd_Exception_Support')
			&& Account_Rd::atLeastStaff()
		);
		$model = array(
			'message' => $request['message'],
			'rdUrl' => $g_siteURL,
			'requestUrl' => $requestProtocol . $_SERVER['HTTP_HOST'] . $optionalPort . $_SERVER['REQUEST_URI'],
			'referrerUrl' => array_key_exists('HTTP_REFERER', $_SERVER) ? $_SERVER['HTTP_REFERER'] : '',
			'promptIt' => $promptIt,
			'host' => $_SERVER['REMOTE_ADDR'],
			'agent' => $_SERVER['HTTP_USER_AGENT']
		);
		if (array_key_exists('exception', $request) && Rd_Debug::isEnabled()) {
			$model['exceptionDump'] = $request['exception']->getTraceAsString();
		} else {
			$model['exceptionDumpEncode'] = $request['exception']->getTraceAsString();
			$model['exceptionDumpEncode'] .= Rd_Debug::getRequestString();
			$model['exceptionDumpEncode'] = str_split(base64_encode($model['exceptionDumpEncode']) . "\n\n",32);
			$model['exceptionDumpEncode'] = implode('<br/>',$model['exceptionDumpEncode']);
		}
		$this->display('index', $model);
	}
}
