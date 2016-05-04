<?php
/*******************************************************************************
managers/errorManager.php
Manager for error cases

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

class errorManager extends Rd_Manager_Base
{
	
	public function __construct($cmd='notFound', $request=array(), $with=array())
	{
		parent::__construct($cmd, $request,$with);
		$this->_mappedDisplayArguments = true;
		$this->displayFunction = 'index';
	}
	
	public function notFoundAction()
	{
		$this->_setLocation('404 - Not Found');
		$this->_setTab('404');
		$this->argList = array('message' => 'The resource you requested is not available.');	
	}
	
	public function applicationErrorAction($request = array())
	{
		$this->indexAction($request);
	}
	
	public function indexAction($request = array()) //#TODO hook in logging and e-mail features, this replaces common.inc.php common_ErrorHandler
	{
		$this->_setTab('500');
		$hasException = 
			array_key_exists('e', $request)
			&& is_object($request['e'])
			&& method_exists($request['e'], 'getMessage');
		$userException = $hasException && is_a($request['e'], 'Rd_Exception_Support');
		if ($userException) {
			$this->_setLocation('Unable to proceed');
		} else {
			$this->_setLocation('500 - Application Error');
		}
		$this->argList = array(
			'message' => (
				(Rd_Debug::isEnabled() || $userException) 
				&& $hasException
				? (method_exists($request['e'], 'getFullMessage') ? $request['e']->getFullMessage() : $request['e']->getMessage())
				: 'The application encountered an unexpected critical error.'
		));
		if (
			array_key_exists('e', $request)
			&& is_object($request['e'])
		) {
			$this->argList['exception'] = $request['e'];
		}
	}
}

