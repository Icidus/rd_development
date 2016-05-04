<?php 

//this is from an old auth method written by Emory that NCSU does not support.

/**
 * @return boolean
 * @param string $qs QueryString data
 * @desc Attempt to auth from external system.  Compare passed values against secret key
 * 		$qs key values: u username, 
 * 						sys  external system identifier
 * 						t	timestamp  seconds since ‘00:00:00 1970-01-01 UTC’
 * 						key md5 of concatenation of above
 */
function authBySecretKey($qs_data) {
	if (!is_null($qs_data)) {
		global $g_trusted_systems, $g_permission;
	
	if (is_null($qs_data)) return false;
		
		parse_str(base64_decode($qs_data), $auth_data);
		
		$trusted_system_key = $g_trusted_systems[$auth_data['sys']]['secret'];
		$timeout = $g_trusted_systems[$auth_data['sys']]['timeout'];
	
		$timestamp = new DateTime($auth_data['t']);
		$expire = new DateTime(time());
		$expire->modify("+$timeout minutes");
	
		if ($timestamp >  $expire)
			return false; //encoded timestamp is too old
		else {
			$user = new user();
		
			if ($user->getUserByUserName($auth_data['u']) == false || $user->getRole() > $g_permission['instructor'])
				return false;	//do not allow privileged users access without login
		
			$verification = $auth_data['u'] . $auth_data['t'];
			
			$verification .= $auth_data['sys'];
			$verification .= $trusted_system_key;			
			
			if (hash("sha256", $verification) == $auth_data['key'])
			{
				setAuthSession(true, $user);
				//return true; #will terminate above
			}
		}
	}
	
	return false;
}