<?php
/*******************************************************************************
managers/Auth.php

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

class Rd_Manager_Auth extends Rd_Manager_Base
{
	
	protected function _logoutAction($request)
	{
		$idpLogoutAttempted = array_key_exists('idpLogoutAttempted', $_SESSION) 
			&& 'splogout' == $_SESSION['idpLogoutAttempted'];
		if (Rd_Auth::isExternallyLoggedIn()) {
			Rd_Auth::logoutExternally();
		}
		Rd_Auth::logoutLocally();
		$this->_role = Account_Rd::LEVEL_GUEST; //#TODO better way to do this? maybe remove the manager reference to user and role?
		$this->displayFunction = 
			((array_key_exists('step', $_GET) && 'splogout' == $_GET['step'])
				|| $idpLogoutAttempted)
			? 'displaySpLogout'
			: 'displayLogout';
	}
	
	protected function _resetPasswordAction($request)
	{
		$username = (
			array_key_exists('username', $request)
			? trim($request['username'])
			: ''
		);
		$secret = (
			array_key_exists('v', $request)
			?trim($request['v'])
			: ''
		);
		$minPasswordLength = 6;
		$userToReset = new user();
		if (
			'' == $username
			|| '' == $secret
			|| !$userToReset->getUserBySecret($username,$secret)
		) {
			Rd_Layout::setMessage('actionResults', 'Unable to proceeed. Verification code did not match.');
			$this->displayFunction = 'displayNonAuth';
			return;
		}
		Rd_Auth::logoutLocally();
		if (!array_key_exists('resetPasswordSubmit', $request)) {
			$this->_role = Account_Rd::LEVEL_GUEST; //#TODO better way to do this? maybe remove the manager reference to user and role?
			$this->displayFunction = 'displayPasswordReset';
			$this->argList = array($username,$secret);
			return;
		}
		if(
			array_key_exists('password', $request)
			&& array_key_exists('passwordConfirm', $request)
			&& $request['password'] == $request['passwordConfirm']
			&& strlen($request['password']) >= $minPasswordLength
			&& $userToReset->resetPassword($request['password'])
		) {
			$this->displayFunction = 'displayPasswordResetSuccess';
			$this->argList = array($username);
			return;
		}
		$reason = (
			array_key_exists('password', $request)
			&& array_key_exists('passwordConfirm', $request)
			&& $request['password'] == $request['passwordConfirm']
			? (
				strlen($request['password']) >= $minPasswordLength
				? 'Unable to store new password.'
				: 'Please provide a password that is at least six characters long.'
			)
			: 'The two password fields did not match.'
		);
		Rd_Layout::setMessage('actionResults', $reason);
		$this->displayFunction = 'displayPasswordReset';
		$this->argList = array($username, $secret);
	}
	
	protected function _resetPasswordRequestAction($request)
	{
		$email = trim(
			array_key_exists('email', $request)
			? trim($request['email'])
			: ''
		);
		$emailIsValid = preg_match(Rd_Registry::get('root:emailRegExp'), $email);
		if (!array_key_exists('resetPasswordSubmit', $request)) {
			$this->displayFunction = 'displayPasswordResetRequest';
			$this->argList = array($email);
			return;
		}
		if(
			array_key_exists('email', $request)
			&& '' != $email
			&& $emailIsValid
			&& users::initiatePasswordReset($email)
		) {
			$this->displayFunction = 'displayPasswordResetRequestSuccess';
			$this->argList = array($email);
			return;
		}
		Rd_Layout::setMessage('actionResults', (
			'' != $email
			&& $emailIsValid
			? 'No qualifiying account with that email address found.'
			: 'Please provide a valid email address.'
		));
		$this->displayFunction = 'displayPasswordResetRequest';
		$this->argList = array($email);
	}	
}

