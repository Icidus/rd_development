<?php
/*******************************************************************************
ldapAuthN.class.php
Methods to allow ldap authentication

Created by Kyle Fenton (kyle.fenton@emory.edu)
Modified by NCSU Libraries, NC State University. Modifications by Adam Constabaris and Troy Hurteau (libraries.opensource@ncsu.edu).

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

class ldapAuthN	{
	/**
	 * Declaration
	 */
	private $conn;	//LDAP resource link identifier
	private $username;	//username
	private $password;	//password
	private $userAccountDn;  // distinguished name for the user account
	private $user_found;	//boolean stores search result
	private $user_authed;	//boolean stores the authentication result
	private $user_info;	//a subset of the user's LDAP record
	
	protected $_errorMessage = '';
	protected $_errorNumber = -99;
	protected $_config = NULL;
	
	/**
	 * @return void
	 * @desc constructor
	 */
	public function ldapAuthN($config) {
		$this->_config = $config;
		$this->conn = $this->user_info = $this->username = $this->password = null;
		$this->user_authed = $this->user_found = false;
	}

	protected function checkUsername($user) {
		return ( preg_match("/^[a-z0-9]{2,8}$/", $user) === 1 );
	}

			
	/**
	 * @return boolean
	 * @param string $user Username
	 * @param string $pass Password
	 * @desc Attempts to authenticate the user against LDAP. Returns true/false
	 */
	public function auth($user, $pass) {
		if ( !$this->checkUsername($user) ) {
			return false;
		}
		//check if authentication has already been run
		if(($user==$this->username) && ($pass==$this->password)) {	//username and password match
			return $this->user_authed;	//return previous result
		}
		
		//requires username and password
		if(empty($user) || empty($pass)) {
			return false;	//return false if it is
		} else {
			$this->username = $user;
			$this->password = $pass;
		}
		
		//establish a connection
		if(!$this->connect()) {
			return false;
		}
		
		//attempt to bind as user
		$this->userAccountDn = sprintf("uid=%s,%s", $this->username, $this->_config['accountbasedn']);
		//Rd_Debug::out("Authenticating as '" . $this->userAccountDn . "<br />");
		$this->user_authed = @ldap_bind($this->conn, $this->userAccountDn, $this->password);
		if ( !$this->user_authed ) {
			Rd_Debug::out("Ldap error: " . ldap_error($this->conn) . " " . ldap_errno($this->conn) . "\n");
		} 
		if (!$this->user_authed){
			$this->_errorMessage = ldap_error($this->conn);
			$this->_errorNumber = ldap_errno($this->conn);
		}
			
		
		//close connection and clean up
		$this->disconnect();
		
		return $this->user_authed;
	}
	
	/**
	 * @return boolean
	 * @desc returns true if user was found with LDAP (only true if LDAP returned exactly 1 entry)
	 */
	public function userExists() {
		return $this->user_found;
	}
	
	
	/**
	 * @return array
	 * @desc returns the array of user info gathered from the directory
	 */
	public function getUserInfo() {
		return $this->user_info;
	}	
	
	
	/**
	 * @return boolean
	 * @desc Attempt to connect to LDAP server
	 */
	protected function connect() {		
		//determine if trying to go through SSL
		//this is a bit of a hack, b/c we're just checking the port #
		//	if the port matches "secure ldap" port, prepend "ldaps://" to hostname
		//also make sure the host does not already include the prefix
		$host = $this->_config['host'];	//default to plain host		
		if(($this->_config['port'] == '636') && (stripos($this->_config['host'], 'ldaps://') === false)) {
			$host = 'ldaps://'.$this->_config['host'];
		}

		//Rd_Debug::out("LDAP Host: $host, port: " . $this->_config['port']);
		$conn = ldap_connect($host, $this->_config['port']);		
		if($conn !== false) {
			//set version
			ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, $this->_config['version']);	
			$this->conn = $conn;	//save resource link
			return true;
		}
		return false;
	}
	
	
	/**
	 * @return void
	 * @desc disconnect from server and do a little cleanup
	 */
	protected function disconnect() {
		ldap_close($this->conn);
		$this->conn = NULL;
	}
	
	
	/**
	 * @return boolean
	 * @desc Searches the user in the directory. Set the info, if user is found
	 */
	public function search() {
		$this->user_info = null;
		$this->user_found = false;
		if(is_null($this->conn)){
			$this->connect();
		}
		$account = ('' == $this->_config['searchaccount'] ? $this->_config['searchaccount'] : NULL);
		$pass = ('' == $this->_config['searchpassword'] ? $this->_config['searchpassword'] : NULL);
		if(ldap_bind($this->conn, $this->_config['searchaccount'], $pass )) {
			$filter = $this->_config['canonicalName'].'='.$this->username;
			$fetch_attribs = array('dn', $this->_config['canonicalName'], $this->_config['firstname'], $this->_config['lastname'], $this->_config['email']);
			$result = ldap_search($this->conn, $this->_config['searchdn'], $filter, $fetch_attribs);			
			if($result !== false) {
				$info = ldap_get_entries($this->conn, $result);
				ldap_free_result($result);
				if($info['count'] >= 1) {
					$this->user_info = $info[0];
					$this->user_found = true;
					return true;
				}
			}
		}		
		return false;
	}
	
	public function getErrorNumber(){
		return $this->_errorNumber;
	}
}
?>
