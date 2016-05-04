<?php
/*******************************************************************************
user.class.php
User Primitive Object

Created by Kathy Washington (kawashi@emory.edu)
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

require_once(APPLICATION_PATH . '/classes/Account/Rd.php');

class user
{
	//Attributes
	public $userID;
	public $userName;
	public $firstName;
	public $lastName;
	public $email;
	public $dfltRole;
	private $dfltClass;
	protected $role;
	public $lastLogin;
	private $userClass;
	private $not_trained;
	public $external_user_key;

	/**
	* Constructor Method
	* @return void
	* @param optional int $userID
	* @desc If userID not NULL, call getUserByID to set user object attributes w/values from DB
	*/
	function user($userID=NULL)
	{
		if (!is_null($userID))
			$this->getUserByID($userID);
		else
			$this->userID = null;
	}	

	function isLoggedIn()
	{
		return array_key_exists('username', $_SESSION);
	}
	
	/**
	* @return boolean
	* @param int $userID
	* @desc Gets user record from DB by userID; returns TRUE on success, FALSE otherwise
	*/
	function getUserByID($userID)
	{
		
		if(empty($userID)) {
			return false;
		}
		
		if (!is_numeric($userID)) {
			$userID = "'{$userID}'"; //TODO totally not adequite
		}

		$sql = 'SELECT u.user_id, u.username, '
			. 'u.first_name, u.last_name, u.email, '
			. 'u.external_user_key, u.dflt_permission_level, '
			. 'p.label, '
			. 'CASE WHEN nt.user_id IS NOT NULL THEN nt.permission_level '
			.	'ELSE u.dflt_permission_level END as permission_level, '
			. 'CASE WHEN nt.user_id IS NOT NULL THEN nt_p.label '
			.	'ELSE p.label END as userclass, '
			.'CASE WHEN nt.user_id IS NOT NULL THEN 1 '
			.	'ELSE 0 END as not_trained '
			. 'FROM users as u '
		    . 'LEFT JOIN not_trained as nt on u.user_id = nt.user_id ' 
			. 'JOIN permissions_levels as p ON p.permission_id = u.dflt_permission_level '
			. 'LEFT JOIN permissions_levels as nt_p ON nt_p.permission_id = nt.permission_level '  
			. 'WHERE u.user_id = ?;';
		// #TODO there is a bug where not_trained can set permissions higher than the default.
		$result = Rd_Pdo::query($sql, $userID);
		if (Rd_Pdo_PearAdapter::isError($result)) { 
			if (Rd_Debug::isEnabled()) {
				print_r(array($sql,$result));
			} 
			trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($result), E_USER_ERROR);
		} 
		
		if($result->rowCount() == 0) {
			return false;
		} else {
			$userData = $result->fetch();
			$this->userID = $userData['user_id'];
			$this->userName =  $userData['username'];
			$this->firstName = $userData['first_name'];
			$this->lastName = $userData['last_name'];
			$this->email =  $userData['email'];
			$this->external_user_key =  $userData['external_user_key'];
			$this->dfltRole =  $userData['dflt_permission_level'];
			$this->dfltClass =  $userData['label'];
			$this->role = $userData['permission_level'];
			$this->userClass =  $userData['userclass'];
			$this->not_trained = $userData['not_trained'];		
			return true;
		}
	}	
	
	/**
	* @return void
	* @param string $userName
	* @desc alternate constructor method
	*/
	function getUserByUserName($userName)
	{
		global $g_dbConn;

		$sql = "SELECT user_id FROM users WHERE username = ?";

		$rs = $g_dbConn->query($sql, array($userName));
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }

		if ($rs->rowCount() == 1)
		{
			$row = $rs->fetch(PDO::FETCH_NUM);
			$this->user($row[0]);
			return true;
		} else return false;

	}

	function getUserBySecret($username, $secret)
	{
		$username = Rd_Pdo::escapeString($username,false);
		$secret = Rd_Pdo::escapeString($secret,false);
		$sql = "SELECT user_id FROM users WHERE username = '{$username}' AND secret = '{$secret}';";

		$rs = Rd_Pdo::query($sql);
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }

		if ($rs->rowCount() == 1)
		{
			$row = $rs->fetch(PDO::FETCH_NUM);
			$this->user($row[0]);
			return true;
		} else return false;

	}	
	
	/**
	* @return void
	* @param string $external_user_key
	* @desc alternate constructor method
	*/
	function getUserByExternalUserKey($external_user_key)
	{
		global $g_dbConn;

		$sql = "SELECT user_id FROM users WHERE external_user_key = ?";

		$rs = $g_dbConn->query($sql, array($external_user_key));
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }

		if ($rs->rowCount() == 1)
		{
			$row = $rs->fetch(PDO::FETCH_NUM);
			$this->user($row[0]);
			return true;
		} else return false;

	}	
	
	/**
	* @return void
	* @param string $userName, string encrypted pwd
	* @desc retrieve user by username and encrypted pwd
	*/
	function getUserByUserName_Pwd($userName, $pwd, $ignoreExpire = false)
	{
		$date = Rd_Pdo::escapeDate(date('Y-m-d'));
		$sql = 'SELECT u.user_id '
			. 'FROM users as u '
			. 'JOIN special_users as sp ON u.user_id = sp.user_id '
			. 'WHERE u.username = ? AND sp.password = ? '
			. (
				$ignoreExpire
				? ';'
				: "AND (expiration >= '{$date}' OR expiration IS NULL OR expiration = '' OR expiration = '0000-00-00');"
			
			);
		
		$result = Rd_Pdo::query($sql, array(
			$userName, 
			$pwd
		));		
		if (Rd_Pdo_PearAdapter::isError($result)) { 
			trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); 
		}
		if ($result->rowCount() == 1) {
			$this->user($result->fetchColumn());
			return true;
		} else {
			return false;
		} 
	}
	
	
	/**
	* @returns false if user already exists
	* @desc Insert new user record into the DB and return the new userID
	*/
	function createUser($username, $firstName='', $lastName='', $email='', $dfltRole = Account_Rd::LEVEL_STUDENT) //#TODO make this a const
	{
		if(is_array($firstName)){
			$configArray = $firstName;
			$firstName = (
				array_key_exists('firstName', $configArray)
				? $configArray['firstName']
				: '' 
			);
			$lastName = (
				array_key_exists('lastName', $configArray)
				? $configArray['lastName']
				: $lastName
			);
			$email = (
				array_key_exists('email', $configArray)
				? $configArray['email']
				: $email
			);
			$dfltRole = (
				array_key_exists('defaultRole', $configArray)
				? $configArray['defaultRole']
				: $dfltRole
			);
			if (!preg_match(Rd_Registry::get('root:emailRegExp'),$email)) {
				return false;
			}
		}
		$username = Rd_Pdo::escapeString($username);
		$firstName = Rd_Pdo::escapeString($firstName);
		$lastName = Rd_Pdo::escapeString($lastName);
		$email = Rd_Pdo::escapeString($email);
		$dfltRole = Rd_Pdo::escapeInt($dfltRole);
		Rd_Pdo::beginTransaction();
		$insertResult = Rd_Pdo::query(
			'INSERT INTO users (username, first_name, last_name, email, dflt_permission_level) '
			. "VALUES ({$username},{$firstName},{$lastName},{$email},{$dfltRole});"
		);
		
		if (Rd_Pdo_PearAdapter::isError($insertResult)) {
			if (Rd_Debug::isEnabled()) {
				Rd_Debug::out(Rd_Pdo_PearAdapter::getErrorMessage($insertResult));
			}
			return false;
		}
		if (!$insertResult) {
			Rd_Pdo::commit();
			return false;
		}
		$id = Rd_Pdo::getLastInsertId('users');
		Rd_Pdo::commit();
		$this->getUserByID($id);
		return true;
	}


	/**
	* @return void
	* @param string $userName
	* @desc Updates the user's user_name in the DB
	*/
	function setUserName($userName)
	{
		global $g_dbConn;

		$this->userName = $userName;
		$sql = "UPDATE users SET username = ? WHERE user_id = ?";

		$rs = $g_dbConn->query($sql, array($userName, $this->getUserID()));
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }
	}

	/**
	* @return void
	* @param string $firstName
	* @desc Update DB with user's First Name
	*/
	function setFirstName($firstName)
	{
		global $g_dbConn;

		$this->firstName = $firstName;
		$sql = "UPDATE users SET first_name = ? WHERE user_id = ?";

		$rs = $g_dbConn->query($sql, array($firstName, $this->getUserID()));
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }
	}

	/**
	* @return void
	* @param string $lastName
	* @desc Update DB with user's Last Name
	*/
	function setLastName($lastName)
	{
		global $g_dbConn;

		$this->lastName = $lastName;
		$sql = "UPDATE users SET last_name = ? WHERE user_id = ?";

		$rs = $g_dbConn->query($sql, array($lastName, $this->getUserID()));
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }
	}

	/**
	* @return void
	* @param string $email
	* @desc Updates the DB with the users's email address
	*/
	function setEmail($email)
	{
		global $g_dbConn, $g_newUserEmail;

		if ($this->email != $email)
		{
			// if $email valid format add email to database
			if(preg_match(Rd_Registry::get('root:emailRegExp'), $email)){ //#TODO this sort of thing doesn't really belong in conf...
				$this->email = $email;
				$sql = "UPDATE users SET email = ? WHERE user_id = ?";

				$rs = $g_dbConn->query($sql, array($email, $this->getUserID()));
				if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }
				// #TODO moving this....
				//$this->sendUserEmail($g_newUserEmail['subject'], $g_newUserEmail['msg']);			
				return true;
			} else {
				return false;
			}
		}
		return true;
	}

	/**
	* @return void
	* @param int $dfltRole
	* @desc Updates the DB with the user's default role
	*/
	function setDefaultRole($dfltRole)
	{
		global $g_dbConn;

		$this->dfltRole = $dfltRole;
		$sql = "UPDATE users SET dflt_permission_level = ? WHERE user_id = ?";

		$rs = $g_dbConn->query($sql, array($dfltRole, $this->getUserID()));
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }
	}

	function setLastLogin()
	{
		global $g_dbConn;

		$sql = "UPDATE users SET last_login = ? WHERE user_id = ?";
		$d = date('Y-m-d');

		$rs = $g_dbConn->query($sql, array($d, $this->getUserID()));
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }

		$this->lastLogin = $d;
	}
	
	
	/**
	 * @return string
	 * @param boolean $last_name_first If true, will return "LAST, FIRST" else will return "FIRST LAST"
	 * @desc returns the user's name
	 */	
	function getName($last_name_first=true) {
		if($last_name_first) {
			$displayname = stripslashes($this->lastName) . ", " . stripslashes($this->firstName);			
		}
		else {
			$displayname = stripslashes($this->firstName).' '.stripslashes($this->lastName);
		}
		
		if (trim($displayname) == '' || trim($displayname) == ',')
			$displayname = $this->getUsername();
		
		return $displayname;
	}

	public function getUserID()
	{ 
		return $this->userID; 
	}

    public function getId() 
    {
       	return $this->getUserID(); 
    }
    
	public function getUsername() 
	{ 
		return stripslashes($this->userName); 
	}
	
	public function getFirstName() 
	{ 
		return stripslashes($this->firstName); 
	}
	
	public function getLastName() 
	{ 
		return stripslashes($this->lastName); 
	}
	
	public function getEmail() 
	{ 
		return stripslashes($this->email); 
	}
	
	public function getLastLogin() 
	{ 
		return $this->lastLogin; 
	}
	
	public function getExternalUserKey() 
	{ 
		return $this->external_user_key; 
	}

	function isSpecialUser() // #TODO deprecate in favor of hasGuestAccess
	{
		global $g_dbConn;

		$sql = "SELECT count(user_id) from special_users WHERE user_id = ?";


		$rs = $g_dbConn->query($sql, $this->userID);
		if (Rd_Pdo_PearAdapter::isError($rs)) { return false; }

		$row = $rs->fetch(PDO::FETCH_NUM);
		return ($row[0] == 1) ? true : false;		
	}
	
	function hasGuestAccess()
	{
		if ('' == $this->getUserID() ) {
			return false;
		}
		$sql = "SELECT count(user_id) from special_users WHERE user_id = ? LIMIT 1";
		$result = Rd_Pdo::query($sql, Rd_Pdo::escapeInt($this->userID));
		if (Rd_Pdo_PearAdapter::isError($result)) { 
			Rd_Debug::out('Query for hasGuestAccess failed');
			return false; 
		}
		return ($result->fetchColumn() > 0);	
	}
	
	function getGuestExpireDate()
	{
		$sql = "SELECT expiration from special_users WHERE user_id = ? ORDER BY expiration ASC LIMIT 1";
		$result = Rd_Pdo::query($sql, Rd_Pdo::escapeInt($this->userID));
		if (Rd_Pdo_PearAdapter::isError($result)) { 
			throw new Exception('Query for getGuestExpireDate failed');
		}
		return ($result->fetchColumn());	
	}
	
	function grantGuestAccess($expireDate)
	{
		if ($this->hasGuestAccess()) {
			return $this->updateGuestExpireDate($expireDate);
		}
		if(
			'' != $this->getUserID() 
			&& $this->_auditAction(Rd_Registry::get('root:userInterface'))
		) {
			$userId = Rd_Pdo::escapeInt($this->getUserID());
			$sql = "INSERT INTO special_users "
				. "(user_id, password, expiration) "
				. "VALUES (?, ?, ?)";
						
			$result = Rd_Pdo::query( $sql, array(
				$userId, 
				md5(rand(9999,PHP_INT_MAX)), 
				Rd_Pdo::escapeDate($expireDate)
			));
			if (Rd_Pdo_PearAdapter::isError($result)) { 
				Rd_Debug::out('Failed to grant Guest Access');
				return false;
			}
			$this->generateSecret();
			//Rd_Email::template(); //#TODO fire off e-mail to the owner of the account.
			return true;
		} else {
			return false;
		}
	}
	
	function updateGuestExpireDate($expireDate)
	{
		if (!$this->hasGuestAccess()) {
			Rd_Debug::out('Unable to update guest expriation date for ' . $this->getUsername() . '. No guest access record.');
			return false;
		}
		if(
			'' != $this->getUserID() 
			&& $this->_auditAction(Rd_Registry::get('root:userInterface'))
		) {
			$userId = Rd_Pdo::escapeInt($this->getUserID());
			$sql = "UPDATE special_users "
				. "SET expiration = ? "
				. "WHERE user_id = ?;";
			$result = Rd_Pdo::query( $sql, array(
				Rd_Pdo::escapeDate($expireDate)
				, $userId
			));
			if (Rd_Pdo_PearAdapter::isError($result)) { 
				Rd_Debug::out('Failed to update Guest Access');
				return false;
			}
			return true;
		} else {
			return false;
		}
	}
	
	function revokeGuestAccess()
	{
		if (!$this->hasGuestAccess()) {
			return true;
		}
		if('' != $this->getUserID()) {
			$userId = Rd_Pdo::escapeInt($this->getUserID());
			$sql = "DELETE FROM special_users "
				. "WHERE user_id = ?;";
						
			$result = Rd_Pdo::query( $sql, array(
				$userId
			));
			if (Rd_Pdo_PearAdapter::isError($result)) { 
				Rd_Debug::out('Failed to remove Guest Access');
				return false;
			}
			$this->clearSecret();
			return true;
		} else {
			return false;
		}
	}

	protected function _auditAction($actionUser, $email=NULL)
	{
		$sql = "INSERT INTO special_users_audit "
			. "(user_id, creator_user_id, email_sent_to, date_created) "
			. "VALUES (?, ?, ?, ?);";

		$result = Rd_Pdo::query(
			$sql, array(
				$this->getUserID(), 
				$actionUser->getUserID(), 
				$email, 
				strftime("%Y-%m-%d %H:%M:%S"))
		);
		if (Rd_Pdo_PearAdapter::isError($result)) {
				Rd_Debug::out('Failed to audit activity on user');
				//trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($result), E_USER_ERROR); 
				return false;
		}
		return ($result->rowCount() > 0);

	}
	
	/**
	* @return int
	* @desc Returns User's Default Role as specified in the users table
	*/
	function getDefaultRole() {
		return $this->dfltRole;
	}
	
	/**
	* @return string
	* @desc Returns User's Class as specified in the users table
	*/
	function getDefaultClass() {
		return $this->dfltClass;
	}
	
	/**
	* @return string
	* @desc Returns User's Class as specified in the users table may be overwritten by not_trained
	*/
	function getUserClass()
	{
			return $this->userClass;
	}	
	
	/**
	* @return int
	* @desc Returns User's Role may be overwritten by not_trained table
	*/
	function getRole() {
		return $this->role;
	}

	/**
	* @return void
	* @desc destroy the database entry
	*/
	function destroy()
	{
		global $g_dbConn;

		$sql = "DELETE FROM users WHERE user_id = ?";

		$rs = $g_dbConn->query($sql, $this->getUserID());
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }
	}

	/**
	* @return array of all Libraries
	*/
	static function getLibraries() {
		global $g_dbConn;

		$sql  = "SELECT library_id "
			.  		"FROM libraries";

		$rs = $g_dbConn->query($sql);
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }

		while($row = $rs->fetch(PDO::FETCH_NUM))
		{
			$tmpArry[] = new library($row[0]);
		}
		return $tmpArry;
	}
	
	function getLoanPeriods() {
		global $g_dbConn;

		$sql  = "SELECT loan_period "
			.  		"FROM inst_loan_periods";

		$rs = $g_dbConn->query($sql);
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }

		while($row = $rs->fetch(PDO::FETCH_NUM)) {
			//$tmpRow = array($row[0], $row[1]);
			$tmpArry[] = $row[0];
		}
		return $tmpArry;
	}

	function sendUserEmail($subject, $baseMsg, $pwd='')
	{
		global $g_siteURL, $g_reservesEmail;

		$msg = preg_replace('/\?url/', $g_siteURL, $baseMsg);
		$msg = preg_replace('/\?username/', $this->getUsername(), $msg);
		$msg = preg_replace('/\?password/', $pwd, $msg);
		$msg = preg_replace('/\?deskemail/', $g_reservesEmail, $msg);

		
		$to      = $this->getEmail();
		$headers = "From: $g_reservesEmail" . "\r\n" .
		   "Reply-To: $g_reservesEmail" . "\r\n" .
		   "X-Mailer: PHP/" . phpversion();
		
		mail($to, $subject, $msg, $headers);
	}
		
	
	function addNotTrained()
	{
		$countSql = "SELECT count(user_id) FROM not_trained WHERE user_id=?";
		$sql = "INSERT INTO not_trained (user_id, permission_level) VALUES (?, 0)";
		$userId = Rd_Pdo::escapeInt($this->getUserID());
		$count = Rd_Pdo::query($countSql, $userId);
		if (Rd_Pdo_PearAdapter::isError($count)) { 
			trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($count), E_USER_ERROR); 
		}
		if ($count->fetchColumn() == 0){
			$result = Rd_Pdo::query($sql, $userId);
			$this->getUserByID($this->getUserID());	
			if (Rd_Pdo_PearAdapter::isError($result)) { 
				trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($result), E_USER_ERROR); 
			}
		}		
	}
	
	function removeNotTrained()
	{
		global $g_dbConn;

		$sql = "DELETE FROM not_trained WHERE user_id = ?";
		
		$rs = $g_dbConn->query($sql, $this->getUserID());
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }

		//reload user object
		$this->getUserByID($this->getUserID());		
	}
	
	function isNotTrained()
	{ 
		return $this->not_trained;
	}
	
	function getCopyrightAccepted()
	{
		$query = "SELECT copyright_accepted FROM users WHERE user_id = " . $this->getUserId();
		$result = Rd_Pdo::query($query);
		$rs = Rd_Pdo::one($result);
		return $rs['copyright_accepted'];
	}
	
	function setCopyrightAccepted($value = TRUE)
	{
		$value = (
			$value
			? 'TRUE'
			: 'FALSE'
		);
		$query = "UPDATE users SET copyright_accepted = {$value} WHERE user_id = " . $this->getUserId();
		Rd_Pdo::query($query);
	}
	
	public function setExternalUserKey($user_key)
	{
		global $g_dbConn;

		$sql = "UPDATE users SET external_user_key = ? WHERE user_id = ?";

		$rs = $g_dbConn->query($sql, array($user_key, $this->getUserID()));
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }

		$this->external_user_key = $user_key;
	}
	
	/**
	 * @return array - Array of CourseInstances
	 * @param string $access_level (optional) Level of access to CIs (student/proxy/instructor/etc)
	 * @param string $act_date (optional) CIs activated on or after this date
	 * @param string $exp_date (optional) CIs expiring before or on this date
	 * @param string $ci_status (optional) CIs with this status
	 * @param string $enrollment_status (optional) Enrollment status of this user [only really matters for students (access_level=0)]	
	 * @param int $dept_id (optional) CIs in this department
	 * @desc Returns an array of CI objects (indexed by CI ID) for this user with the given qualifications. If a parameter is not specified, no restriction is placed.  This is the catch-all logic to get CIs to be used by public methods with selective criteria. 
	 */
	public function fetchCourseInstances($access_level=null, $act_date=null, $exp_date=null, $ci_status=null, $enrollment_status=null, $dept_id=null) {
		global $g_dbConn, $g_permission;
		
		//format access - if trying to set the access level, but provided an improper level, then unset it
		if(!empty($access_level) && !in_array($access_level, $g_permission)) {
			$access_level = null;	//not a valid access level, do not restrict
		}
		//format dates
		if(!empty($act_date)) { 
			$act_date = date("Y-m-d", strtotime($act_date));
		}
		if(!empty($exp_date)) { 
			$exp_date = date("Y-m-d", strtotime($exp_date));
		}

		//build query
		$sql = "SELECT DISTINCT ca.course_instance_id
			FROM course_aliases AS ca
				JOIN access AS a ON a.alias_id = ca.course_alias_id
				JOIN course_instances AS ci ON ci.course_instance_id = ca.course_instance_id
				JOIN courses AS c ON c.course_id = ca.course_id
				JOIN departments AS d ON d.department_id = c.department_id
			WHERE a.user_id = ".$this->userID;
		
		//add restrictions
		if(!empty($access_level)) {
			$sql .=	" AND a.permission_level = ".$g_permission[$access_level];
		}
		if(!empty($enrollment_status)) {
			$sql .= " AND a.enrollment_status = '$enrollment_status'";
		}
		if(!empty($ci_status)) {
			$sql .= " AND ci.status = '$ci_status'";
		}
		if(!empty($act_date)) {
			$sql .= " AND ci.activation_date <= '$act_date'";
		}
		if(!empty($exp_date)) {
			$sql .= " AND ci.expiration_date >= '$exp_date'";
		}
		if(!empty($dept_id)) {
			$sql .= " AND d.department_id = '$dept_id'";
		}
		
		//finish off with sorting
		$sql .= " ORDER BY d.abbreviation ASC, c.course_number ASC, ca.section ASC, ci.year DESC, ci.activation_date DESC";				

		//query
		$rs = $g_dbConn->query($sql);
		if(Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }
					
		$course_instances = array();
		while($row = $rs->fetch(PDO::FETCH_NUM)) {
			$course_instances[$row[0]] = new courseInstance($row[0]);
		}
		
		return $course_instances;
	}
	
	public function generateSecret()
	{
		$secret = md5($this->getUsername().$this->getUserID().time());
		$userId = Rd_Pdo::escapeInt($this->getUserID());
		$result = Rd_Pdo::query("UPDATE `users` SET `secret` = '{$secret}' WHERE `user_id` = {$userId}");
		if(!Rd_Pdo::isError($result)) {
			return $secret;
		}
		Rd_Debug::out(Rd_Pdo::getErrorMessage($result));
		return false;
	}
	
	public function clearSecret()
	{
		$userId = Rd_Pdo::escapeInt($this->getUserID());
		$result = Rd_Pdo::query("UPDATE users SET secret = NULL WHERE user_id = {$userId}");
		if(!Rd_Pdo::isError($result)) {
			return true;
		}
		Rd_Debug::out(Rd_Pdo::getErrorMessage($result));
		return false;
	}
	
	public function messageNewGuest()
	{
		$secret = $this->generateSecret();
		$username = $this->getUsername();
		Rd_Email::template('newUserWelcome', array(
			'user' => $this,
			'url' => Rd_Registry::get('root:mainUrlProper') . "?cmd=resetPassword&username={$username}&v={$secret}"
		));
	}
	
	public function resetPassword($newPassword)
	{
		$userId = Rd_Pdo::escapeInt($this->getUserID());
		$newPassword = Rd_Pdo::escapeString(md5($newPassword));
		$result = Rd_Pdo::query("UPDATE special_users SET password = {$newPassword} WHERE user_id = {$userId}");
		if(!Rd_Pdo::isError($result)) {
			if (0 == $result->rowCount()) {
				//#TODO do a more thorough check (see if user has a secret, and special_user record first...)
				//throw new Exception('Unable to set passwords for accounts that do not have guest access.');
			}
			$this->clearSecret();
			return true;
		}
		Rd_Debug::out(Rd_Pdo::getErrorMessage($result));
		return false;
	}
	
	public function refresh()
	{
		$this->getUserByID($this->userID);
	}
}

