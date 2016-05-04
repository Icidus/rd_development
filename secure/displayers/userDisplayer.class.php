<?php
/*******************************************************************************
userDisplayer.class.php

Created by Kathy Washington (kawashi@emory.edu)
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
require_once(APPLICATION_PATH . '/managers/ajaxManager.class.php');
require_once(APPLICATION_PATH . '/lib/Rd/Displayer/Base.php');

class userDisplayer extends Rd_Displayer_Base {
	
	protected $_displayerName = 'user';

	public function staffIndex()
	{
		$model = array('msg' => Rd_Layout::getMessage('actionResults'));
		$this->display('index', $model);
	}
	
	public function facultyIndex()
	{
		Rd_Help::setDefaultArticleId(14);
		$model = array('msg' => Rd_Layout::getMessage('actionResults'));
		$this->display('faculty', $model);
	}
	
	public function displayEditProfile($user, $cmd='addUser')
	{
		$allowedPermissions = array();
		$u = Rd_Registry::get('root:userInterface');
		$users = new users();
		if(
			$user->getRole() >= Account_Rd::LEVEL_FACULTY
			|| ( array_key_exists('defaultRole', $_REQUEST) //#TODO this shouldn't be needed... user should have been already escalated...
				&& (int)$_REQUEST['defaultRole'] >= Account_Rd::LEVEL_FACULTY
			) || array_key_exists('staffLibrary', $_REQUEST)
			|| array_key_exists('ilsUserId', $_REQUEST)
		){
			$userClass = (
				array_key_exists('staffLibrary', $_REQUEST)
					|| $user->getRole() >= Account_Rd::LEVEL_STAFF
				? Account_Rd::LEVEL_STAFF
				: Account_Rd::LEVEL_FACULTY
			);
			$user = $users->initUser($userClass, $user->getUserName());
		}
		$editingSelf = $u->getUserID() === $user->getUserID();
		if(($u->getRole() >= Account_Rd::LEVEL_STAFF) && !$editingSelf){
			$permissionLevels = Rd_Registry::get('root:userPermissionLevels');
			foreach ($permissionLevels as $class=>$level){
				if($level > $u->getRole() || $level < 0) {
					continue;
				}
				$allowedPermissions[$class] = $level;
			}
		}
		$model = array(
			'user' => $user,
			'u' => $u,
			'formCommand' => $cmd,
			'editingSelf' => $editingSelf,
			'username' => array_key_exists('username', $_POST)
				? $_POST['username']
				: $user->getUserName(),
			'firstName' => array_key_exists('firstName', $_POST)
				? $_POST['firstName']
				: $user->getFirstName(),
			'lastName' => array_key_exists('lastName', $_POST)
				? $_POST['lastName']
				: $user->getLastName(),
			'email' => array_key_exists('email', $_POST)
				? $_POST['email']
				: $user->getEmail(),
			'defaultRole' => array_key_exists('defaultRole', $_POST)
				? $_POST['defaultRole']
				: $user->getDefaultRole(), //#TODO this defaults to student, which is correct for the form, but wrong for an uninitialized user...
			'libraries' => $user->getLibraries(),
			'staffLibrary' => (
				array_key_exists('staffLibrary', $_POST)
				? $_POST['staffLibrary']
				: (
					is_a($user, 'staff')
					? $user->getStaffLibrary()
					: ''
				)
			),
			'trained' => array_key_exists('notTrained', $_POST)
				? false
				: !$user->isNotTrained(),
			'ilsUserId' => (
				array_key_exists('ilsUserId', $_POST)
				? $_POST['ilsUserId']
				: (
					is_a($user, 'instructor')
					? $user->getILSUserID()
					: ''
				)
			),
			'ilsUsername' => (
				array_key_exists('ilsUsername', $_POST)
				? $_POST['ilsUsername']
				: (
					is_a($user, 'instructor')
					? $user->getILSName()
					: ''
				)
			),
			'allowedPermisions' => $allowedPermissions,
			'msg' => Rd_Layout::getMessage('actionResults'),
			'hasGuestAccess' => (
				'addUser' == $cmd
				? array_key_exists('enableGuest', $_POST)
				: $user->hasGuestAccess()
			),
			'expireDate' => (
				'addUser' == $cmd && array_key_exists('expireDate', $_POST)
				? $_POST['expireDate']
				: ''
			)
		);
		$this->display('edit', $model);
	}
	
	public function displaySelectUser($cmd='editUser'){
		$searchTerm = 
			array_key_exists('searchTerm', $_REQUEST)
			? htmlentities(trim($_REQUEST['searchTerm']))
			: '';
		$userList = NULL;
		if ('' != trim($searchTerm)) {
			$userList = array();
			$users = new users();
			$userData = $users->search('any', trim($searchTerm));
			foreach($userData as $user){
				$userList[$user->getUserID()] = 
					$user->getLastName() 
					. ', ' . $user->getFirstName() 
					. ' (' . $user->getUserName() . ')';
			}
		}
		$model = array(
			'formCommand' => $cmd,
			'userList' => $userList,
			'searchTerm' => $searchTerm
		);
		$this->display('select', $model);
	}
	
	public function displaySetGuest($user)
	{
		$model = array(
			'user' => $user,
			'hasGuestAccess' => $user->hasGuestAccess(),
			'expireDate' => (
				//'' != trim((string)$user->getGuestExpireDate())
				//	&& '0000-00-00' != trim((string)$user->getGuestExpireDate())
				//? trim((string)$user->getGuestExpireDate())
				//: (
				//	array_key_exists('expireDate', $_REQUEST)
				//		&& '' != trim($_REQUEST['expireDate'])
				//	? htmlentities(trim($_REQUEST['expireDate']))
				//	: date('Y-m-d', strtotime('+6 months'))
				//)
				$user->getGuestExpireDate()
			),
			
			'msg' => Rd_Layout::getMessage('actionResults')
		);
		$this->display('guest', $model);
	}
	
	/**
	* @return void
	* @param
	* @desc Display Screens to Manage Users
	*/

	function displayInstructorHome()
	{
		echo"<table width=\"60%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\" align=\"center\">";
		echo"	<tr> ";
		echo"		<td width=\"140%\"><img src=\"public/images/spacer.gif\" width=\"1\" height=\"5\"></td>";
		echo"	</tr>";
		echo"	<tr> ";
		echo"		<td align=\"left\" valign=\"top\">";
		echo"			<p><a href=\"index.php?cmd=editProfile\" class=\"titlelink\">Edit My Profile</a><br>";
		echo"           Edit your name and email address</p>";
		echo"           <p><a href=\"index.php?cmd=addProxy\" class=\"titlelink\">Add a Proxy</a><br>";
		echo"           Add a proxy to one of your classes. Proxies:</p>";
		echo"           <ul>";
		echo"           	<li> <span class=\"small\">Must have signed in to ReservesDirect at ";
		echo"               	least once for you to be able to add them</span></li>";
		echo"              	<li class=\"small\">Are able to manage every aspect of the class that ";
		echo"               	you assign them to (add, delete or edit reserve items, add crosslistings, ";
		echo"                	sort items, etc.). </li>";
		echo"              	<li class=\"small\">Only have access to the specific class that you ";
		echo"               	assign them to</li>";
		echo"              	<li class=\"small\">Expire at the end of the semester, or when you ";
		echo"               	remove them manually, whichever comes first</li>";
		echo"			</ul>";
		echo"           <p><a href=\"index.php?cmd=removeProxy\" class=\"titlelink\">Delete a Proxy</a><br>";
		echo"           <!--This link should take the user to a list of their current and future classes, ask them to select one, then present them with the \"Edit Proxies\" screen. -->";
		echo"           Remove a proxy from one of your classes.</p></td>";
		echo"	</tr>";
		echo"	<tr> ";
		echo"		<td><img src=\"public/images/spacer.gif\" width=\"1\" height=\"15\"></td>";
		echo"	</tr>";
		echo"</table>";
	}

	function displayCustodianHome($msg=null)
	{
		echo"<table width=\"60%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\" align=\"center\">";
		echo"	<tr> ";
		echo"		<td width=\"140%\"><img src=\"public/images/spacer.gif\" width=\"1\" height=\"5\"></td>";
		echo"	</tr>";
		echo "	<tr><td align=\"center\" valign=\"top\" class=\"successText\">$msg&nbsp;</td></tr>\n";
		echo "	<tr><td colspan=2>&nbsp;</td></tr>\n";
		echo"	<tr> ";
		echo"		<td align=\"left\" valign=\"top\">";
		echo"			<p><a href=\"index.php?cmd=editProfile\" class=\"titlelink\">Edit My Profile</a><br>";
		echo"           Edit your name and email address</p>";
		echo"           <p><a href=\"index.php?cmd=setPwd\" class=\"titlelink\">Create an override password</a><br>";
		echo"           Create a temporary user passwrod, for example, for someone who has forgotten their Emory Network password or who is having trouble with their Emory NetID or GBSNet login.</p>";
		echo"           <p><a href=\"index.php?cmd=resetPwd\" class=\"titlelink\">Reset an Override Password</a><br>";
		echo"           Resets a user's override password to the system default</p>";
        echo"           <p><a href=\"index.php?cmd=removePwd\" class=\"titlelink\">Remove an Override Password</a><br>";
		echo"           Deletes a user's override password so that they log in using their regular Emory NetID password or GBSNet password</p></td>";
		echo"	</tr>";
		echo"	<tr> ";
		echo"		<td><img src=\images/spacer.gif\" width=\"1\" height=\"15\"></td>";
		echo"	</tr>";
		echo"</table>";
	}

	function displayEditProxy($courseInstances,$nextCmd, $cmd)
	{	
		echo "<form action=\"index.php\" method=\"post\" name=\"editUser\">\n";
	    echo "<input type=\"hidden\" name=\"cmd\" value=\"$nextCmd\">\n";
		echo '<table width="100%" border="0" cellspacing="0" cellpadding="0" align="center">';
        echo 	'<tr>';
        echo 		'<td width="140%"><img src="public/images/spacer.gif" width="1" height="5"> </td>';
        echo 	'</tr>';
        echo 	'<tr>';
        if($cmd=='addProxy') {	//adding proxy
	        echo 	'  <td align="left" valign="top" class="helperText">Select which classes ';
	        echo 		'to add your proxy to. Note that you may only add one individual at ';
	        echo 		'a time, but you may add them to ';
	        echo 		'as many classes as you wish. You may only add users who have logged ';
	        echo 		'into ReservesDirect at least once to register in the database.</td>';
        }
        else {	//removing proxy
 ?>
 		<td align="left" valign="top" class="helperText">Select from which classes to remove your proxy.</td>
 <?php
        }
        
        echo 	'</tr>';
        echo 	'<tr>';
        echo 		'<td height="14">&nbsp;</td>';
        echo 	'</tr>';
        echo 	'<tr>';
        echo 		'<td height="14">';
        echo 			'<table width="100%" border="0" cellspacing="0" cellpadding="0">';
        echo 				'<tr align="left" valign="top">';
        echo 					'<td height="14" class="headingCell1"><div align="center">YOUR CLASSES</div></td>';
        echo 					'<td width="75%"><div align="center"></div></td>';
        echo 				'</tr>';
        echo 			'</table>';
        echo 		'</td>';
        echo 	'</tr>';
        
        if(empty($courseInstances)) {
?>
		<tr><td>
			<div class="borders" style="padding:10px;">
				You are currently not teaching any courses.
			</div>
		</td></tr>
		</table>
<?php
    		return;
        }
        
        echo 	'<tr>';
        echo 		'<td align="left" valign="top" class="borders">';
        echo 			'<table width="100%" border="0" cellpadding="5" cellspacing="0" class="displayList">';
        echo 				'<tr align="left" valign="middle" bgcolor="#CCCCCC" class="headingCell1">';
        echo 					'<td width="15%">&nbsp;</td>';
        echo 					'<td width="65%">&nbsp;</td>';
        echo 					'<td>&nbsp;</td>';
        echo 					'<td width="10%">Select</td>';
        echo				'</tr>';

        if(!empty($courseInstances))
        {
        	$rowNumber = 0;
	        foreach ($courseInstances as $ci)
	        {
	        	$rowClass = ($rowNumber++ % 2) ? $rowClass = "evenRow" : "oddRow";
	        	$ci->getPrimaryCourse();
	
	        echo				'<tr align="left" valign="middle" bgcolor="#CCCCCC" class="'.$rowClass.'">';
	        echo 					'<td width="15%">'.$ci->course->displayCourseNo().'</td>';
	        echo 					'<td width="65%">'.$ci->course->getName().'</td>';
	        echo 					'<td width="20%">'.$ci->displayTerm().'</td>';
	        echo 					'<td width="10%" align="center"><input type="radio" name="ci" value="'.$ci->getCourseInstanceID().'"></td>';
	        echo 				'</tr>';
	
	        }
        }
        echo 				'<tr align="left" valign="middle" bgcolor="#CCCCCC" class="headingCell1">';
        echo 					'<td width="15%">&nbsp;</td>';
        echo 					'<td width="65%">&nbsp;</td>';
        echo 					'<td>&nbsp;</td>';
        echo 					'<td width="10%">&nbsp;</td>';
        echo 				'</tr>';
        echo 			'</table>';
        echo		'</td>';
        echo 	'</tr>';
        echo 	'<tr>';
        echo 		'<td align="left" valign="top">&nbsp;</td>';
        echo 	'</tr>';
        echo 	'<tr>';
        echo 		'<td align="left" valign="top"><div align="center"><input type="submit" name="Submit" value="Continue"></div></td>';
        echo 	'</tr>';
        echo 	'<tr>';
        echo 		'<td align="left" valign="top"><img src="public/images/spacer.gif" width="1" height="15"></td>';
        echo 	'</tr>';
      	echo '</table>';
	}

	function displayAssignUser($cmd, $nextCmd, $userToAssign, $msg, $usersObj, $label, $request)
	{
		if(empty($userToEdit)) {
			//ajax user lookup
			$mgr = new ajaxManager('lookupUser', $cmd, 'manageUsers', 'Select User', null, true, array('min_user_role'=>0, 'field_id'=>'selectedUser'));
			$mgr->display();
		}
	}

	
	function displayMergeUser($request, $cmd, $usersObj)
	{
		$model = array(
			'users' => $usersObj,
			'request' => $request //#TODO passed down for the select user dialogs, need a better way to do this.
		);
		$this->display('merge',$model);
	}
}
