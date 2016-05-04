<?php
/*******************************************************************************
common.inc.php
common functions that don't quite fit anywhere else

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
require_once(APPLICATION_PATH . '/classes/users.class.php');
require_once(APPLICATION_PATH . '/classes/note.class.php');
require_once(APPLICATION_PATH . '/classes/reserveItem.class.php');
require_once(APPLICATION_PATH . '/lib/Curl_Tunnel.php');

require_once(APPLICATION_PATH . '/lib/Rd/Exception.php');
require_once(APPLICATION_PATH . '/lib/Rd/Dispatch.php');
require_once(APPLICATION_PATH . '/lib/Rd/Manager/Base.php');
require_once(APPLICATION_PATH . '/managers/errorManager.php'); //#TODO move this to lib

require_once(APPLICATION_PATH . '/lib/Rd/Help.php');

$g_permission = Account_Rd::getLevelClassMap(); //array("student"=>0, "custodian"=>1, "proxy"=>2, "instructor"=>3, "staff"=>4, "admin"=>5);
Rd_Registry::set('root:userPermissionLevels', $g_permission);
$g_notetype = array('instructor'=>'Instructor', 'content'=>'Content', 'staff'=>'Staff', 'copyright'=>'Copyright');
Rd_Registry::set('root:noteTypes', $g_notetype);
$g_terms	= array('Fall', 'Spring', 'Summer');
Rd_Registry::set('root:termNames', $g_terms);

// user defined error handling function
/**
 * @return void
 * @param int $errno
 * @param string $errmsg
 * @param string $filename
 * @param string $linenum
 * @param string $vars
 * @desc Handle Errors
*/
function common_ErrorHandler($errno, $errmsg, $filename, $linenum, $vars) //#TODO make this throw to errorManager and deprecate
{
	global $g_error_log;	
	try {
		$cmd = Rd_Registry::get('root:requestCommand');
	} catch (Exception $e) {
		$cmd = '';
	}
	try {
		$u = Rd_Registry::get('root:userInterface');
	} catch (Exception $e) {
		$u = NULL;
	}

	$dt = date('Y-m-d H:i:s (T)');
	$errortype = array (
		E_ERROR => "Error",
		E_WARNING => "Warning",
		E_PARSE => "Parsing Error",
		E_NOTICE => "Notice",
		E_CORE_ERROR => "Core Error",
		E_CORE_WARNING => "Core Warning",
		E_COMPILE_ERROR => "Compile Error",
		E_COMPILE_WARNING => "Compile Warning",
		E_USER_ERROR => "User Error",
		E_USER_WARNING => "User Warning",
		E_USER_NOTICE => "User Notice",
		E_STRICT => "Runtime Notice"
		);
	// set of errors for which a var trace will be saved
	$user_errors = array(E_USER_ERROR, E_USER_WARNING, E_USER_NOTICE, E_USER_ERROR);
	$err = "<errorentry>\n"
		. "\t<datetime>" . $dt . "</datetime>\n"
		. "\t<errornum>" . $errno . "</errornum>\n"
		. "\t<errortype>" . (array_key_exists($errno,$errortype) ? $errortype[$errno] : '') . "</errortype>\n"
		. "\t<errormsg>" . $errmsg . "</errormsg>\n"
		. "\t<scriptname>" . $filename . "</scriptname>\n"
		. "\t<scriptlinenum>" . $linenum . "</scriptlinenum>\n";
	if ($u instanceof user) {
		$err .= "\t<user><username>" . $u->getUserName() . "</username><userID>" . $u->getUserID() . "</userID></user>\n";
	}
	$err .= "\t<cmd>$cmd</cmd>\n";
	if (in_array($errno, $user_errors)) {
		//$err .= "\t<vartrace>" . wddx_serialize_value($vars, "Variables") . "</vartrace>\n";
	}
	$err .= "</errorentry>\n\n";
	
	if ($errno <> E_NOTICE && $errno <> E_STRICT && $errno <> E_WARNING) {
		// timestamp for the error entry


		// save to the error log, and e-mail me if there is a critical user error
		if('' != $g_error_log) {
			error_log($err, 3, $g_error_log);
		}

		if(Rd_Debug::isEnabled()) {
			print('<pre>' . htmlentities($err) . '</pre>');
			//print("<pre>{$err}</pre>");
		}
		//mail($g_errorEmail, "ReservesDirect Error", $err);
		include_once('error.php');
		die;
	} else {
		if(Rd_Debug::isEnabled()) {
			print('<pre>' . htmlentities($err) . '</pre>');
			//print("<pre>{$err}</pre>");
		}
	}
}


/**
 * @return user Array
 * @param mixed $role int role or all
 * @desc returns array of users with role >= given role'
*/
function common_getUsers($role)
{
	$usersObject = new users();
	return $usersObject->getUsersByRole($role);
}

function common_getAllUsers()
{
	$usersObject = new users();
	return $usersObject->getAllUsers();
}

function common_getDepartments()
{
	global $g_dbConn;

	$sql =	"SELECT department_id, `abbreviation` "
		.	"FROM `departments` "
		.	"WHERE name IS NOT NULL "
		.	"ORDER BY abbreviation";


	$deptList = $g_dbConn->query($sql);
	if (Rd_Pdo_PearAdapter::isError($deptList))  trigger_error($deptList->getMessage(), E_USER_ERROR) ;
	return $deptList;
}


/**
 * @return assoc array (dir, name, ext) of new dir/filename.ext and ext (ext used to set mimetypes)
 * @param string $src_name filename to be formatted
 * @param int $item_id necessary to format the proper destination path
 * @desc  create filename 
 *		  <upload_directory>/dir/md4hash_itemID.ext where dir is the first 2 char or the md5hash
*/
function common_formatFilename($src, $item_id) {

	$src_file = $src['tmp_name'];
	$src_name = $src['name'];

	//get filename/ext
	$file_path = pathinfo($src_name);	
	
	$md5_file = md5_file($src_file);
	
	if ($md5_file == '' || $item_id == '')
		trigger_error("Could not formatFilename common_formatFilename($src_name, $item_id) tmp_name=$src_file", E_USER_ERROR);
		
	$filename = $md5_file . "_" . $item_id;
	$dir = substr($md5_file,0,2) . "/";
	$ext = ".".$file_path['extension'];
	
	return array('dir' => $dir, 'name'=>$filename, 'ext'=>$ext);
}

/**
 * @return array (name, ext) of new filename and ext
 * @param string $src element of $_FILES[]; uploaded file info array
 * @param int $item_id necessary to format the proper destination path
 * @desc cleans up filename and moves uploaded file to a destination set in the config
*/
function common_storeUploaded($src, $item_id) {
	global $g_documentDirectory, $g_uploadErrorMessage;
	
	//check for errors
	if( $src['error'] ) {
		$uploadLimit = Rd_Registry::get('uploadLimitSize');
		//trigger_error(str_replace('[[uploadLimitSize]]',$uploadLimit,$g_uploadErrorMessage), E_USER_ERROR);
		throw new Exception(str_replace('[[uploadLimitSize]]', $uploadLimit, $g_uploadErrorMessage));	
	}
	
	//format the filename; extract extension
	$file = common_formatFilename($src, $item_id);
	
	//test dir
	if (!file_exists($g_documentDirectory . $file['dir']) || !opendir($g_documentDirectory . $file['dir']))
	{
		//create directory
		if(!mkdir($g_documentDirectory.$file['dir'], 0775, true))
			trigger_error("Could not create directory " .$g_documentDirectory.$file['dir'], E_USER_ERROR);
	}
	
	$newFile = $g_documentDirectory.$file['dir'].$file['name'].$file['ext'];
	//store file
	if( !move_uploaded_file($src['tmp_name'], $newFile) ) {
		trigger_error('Failed to move uploaded file '.$src['tmp_name'].' to '.$newFile, E_USER_ERROR);
	}
	
	//return destination filename/ext to store in DB
	return $file;
}

function common_storeVideo($src, $item_id, $user, $title, $stime="", $etime=""){
	global $g_documentDirectory, $logfile, $g_encoderScript, $g_encoderServer;

	$getExt = preg_split('/[.]/', $src['name']);
	$getExtsize = count($getExt);
	$ext = $getExt[$getExtsize-1];
	
	//error_log("[" . date("F j, Y, g:i a") . "] ". "About to format filename for video item!" . "\n", 3, $logfile);
	$file = common_formatFilename($src, $item_id);
	$newFile = $g_documentDirectory.$file['dir'].$file['name'].".html";
	$videoFile = $file['name'];
	$fileDirectory = $g_documentDirectory.$file['dir'];
	common_createVideoPage($newFile, $videoFile, $fileDirectory);

	
	$email= $user->getEmail();
	$curlTunnel = new Curl_Tunnel($g_encoderServer, array(
		'opt' => array( 
			CURLOPT_FOLLOWLOCATION => true, 
			CURLOPT_MAXREDIRS => 10
		)
	));
	rename($src['tmp_name'], $src['tmp_name'] . '.' . $ext);
	$uploadFile = array('field_uploadfile' => $src['tmp_name'] . '.' . $ext);
	$postData = array('user_email'=>$email,
                     'title'=>$title,
               		 'upload_id'=>$videoFile,
					 'stime'=>$stime,
					 'etime'=>$etime);
	$result = $curlTunnel->post($g_encoderScript, $postData, $uploadFile);
	
	$returnFile = $file['dir'].$file['name'].".html";

	return $returnFile;
}


function common_getStatusStyleTag($status)
{

	$status = strtoupper($status);
	switch ($status) {
		case 'ACTIVE':
		case 'PUBLIC':
			$statusTag = 'active';
		break;

		case 'INACTIVE':
		case 'UNAVAILABLE':
			$statusTag = 'inactive';
		break;

		case 'IN PROCESS':
		case 'HIDDEN':
		case 'SEARCHING STACKS':
		case 'RECALLED':
		case 'PURCHASING':
		case 'RESPONSE NEEDED':
		case 'SCANNING':
		case 'COPYRIGHT REVIEW':
		case 'NEW':
		case 'RUSH':
		case 'ON ORDER':
		case 'MISSING':
			$statusTag = 'inprocess';
		break;
		
		case 'HEADING':
			$statusTag = 'heading';
		break;
		
		case 'DENIED':
		case 'DENIED ALL':	
			$statusTag = 'copyright_denied';
		break;
		default:
			$statusTag = 'black';
	}

	return $statusTag;
}

function common_getEnrollmentStyleTag($enrollment) {
	switch(strtoupper($enrollment)) {
		case 'OPEN':
			$tag = 'openEnrollment';
		break;
		case 'MODERATED':
			$tag = 'moderatedEnrollment';
		break;
		case 'CLOSED':
			$tag = 'closedEnrollment';
		break;
		default:
			$tag = '';
	}
	return $tag;
}

function common_formatDate($d, $format)
{
		$D = explode('-', $d);
		if (is_array($D) && count($D) > 2)
		{
			switch ($format)
			{
				case "MM-DD-YYYY":
				default:
					return $D[1].'-'.$D[2].'-'.$D[0];
			}
		} else return '';
}

function common_createVideoPage($filename, $videoFile, $fileDirectory){
	global $g_siteURL, $g_streamingServer;
	
	if (!opendir($fileDirectory))
	{
		//create directory
		if(!mkdir($fileDirectory, 0775, true))
			trigger_error("Could not create directory " .$fileDirectory, E_USER_ERROR);
	}	
// #TODO: I DON'T WANT TO LIVE ON THIS PLANET ANY MORE O_O	
$page = "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN" . "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">
<html xmlns=\"http://www.w3.org/1999/xhtml\">
<head>
<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />
<title>Video Reserve</title>
<script language=\"JavaScript\" src=\"$g_siteURL/public/javascript/jsFunctions.js\"></script>
<script type=\"text/javascript\" src=\"$g_siteURL/public/javascript/jquery-1.6.4.min.js\"></script>
<script src=\"$g_siteURL/public/javascript/AC_RunActiveContent.js\" type=\"text/javascript\"></script>
<script type=\"text/javascript\">
	function MM_CheckFlashVersion(reqVerStr,msg){
		with(navigator){
			var isIE  = (appVersion.indexOf(\"MSIE\") != -1 && userAgent.indexOf(\"Opera\") == -1);
			var isWin = (appVersion.toLowerCase().indexOf(\"win\") != -1);
			if (!isIE || !isWin){  
				var flashVer = -1;
				if (plugins && plugins.length > 0){
					var desc = plugins[\"Shockwave Flash\"] ? plugins[\"Shockwave Flash\"].description : \"\";
					desc = plugins[\"Shockwave Flash 2.0\"] ? plugins[\"Shockwave Flash 2.0\"].description : desc;
					if (desc == \"\") flashVer = -1;
					else{
						var descArr = desc.split(\" \");
						var tempArrMajor = descArr[2].split(\".\");
						var verMajor = tempArrMajor[0];
						var tempArrMinor = (descArr[3] != \"\") ? descArr[3].split(\"r\") : descArr[4].split(\"r\");
						var verMinor = (tempArrMinor[1] > 0) ? tempArrMinor[1] : 0;
						flashVer =  parseFloat(verMajor + \".\" + verMinor);
					}
				}
				// WebTV has Flash Player 4 or lower -- too low for video
				else if (userAgent.toLowerCase().indexOf(\"webtv\") != -1) flashVer = 4.0;
				var verArr = reqVerStr.split(\",\");
				var reqVer = parseFloat(verArr[0] + \".\" + verArr[2]);
				if (flashVer < reqVer){
					if (confirm(msg))
						window.location = \"http://www.adobe.com/shockwave/download/download.cgi?P1_Prod_Version=ShockwaveFlash\";
					}
    			}
			} 
		}
</script> 
</head>
<body style=\"text-align:center; vertical-align:middle;\"onload=\"MM_CheckFlashVersion('10,0,0,0','Content on this page requires a newer version of Adobe Flash Player. Do you want to download it now?');\" bgcolor=\"#ACAC9F\" text=\"#000000\">

			<div style=\"left:50%;\">
			<script type=\"text/javascript\">
				AC_FL_RunContent( 'codebase','http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=9,0,28,0',
					'width','640',
					'height','377',
					'title','SWF ',
					'id', 'videoPlayer',
					'bgcolor','#000000',
					'src','$g_siteURL/swfs/videoPlayer',
					'allowfullscreen','true',
					'quality','high',
					'flashvars', '&videoWidth=0&videoHeight=0&dsControl=manual&dsSensitivity=100&serverURL=$g_streamingServer/$videoFile&DS_Status=true&streamType=vod&autoStart=true',
					'pluginspage','http://www.adobe.com/shockwave/download/download.cgi?P1_Prod_Version=ShockwaveFlash',
					'movie','$g_siteURL/swfs/videoPlayer' ); //end AC code
			</script><noscript><object width='640' height='377' id='videoPlayer' name='videoPlayer' type='application/x-shockwave-flash' classid='clsid:d27cdb6e-ae6d-11cf-96b8-444553540000' ><param name='movie' value='$g_siteURL/swfs/videoPlayer.swf' /> <param name='quality' value='high' /> <param name='bgcolor' value='#000000' /> <param name='allowfullscreen' value='true' /> <param name='flashvars' value= '&videoWidth=0&videoHeight=0&dsControl=manual&dsSensitivity=100&serverURL=$g_streamingServer/$videoFile&DS_Status=true&streamType=vod&autoStart=true'/><embed src='$g_siteURL/swfs/videoPlayer.swf' width='640' height='377' id='videoPlayer' quality='high' bgcolor='#000000' name='videoPlayer' allowfullscreen='true' pluginspage='http://www.adobe.com/go/getflashplayer' flashvars='&videoWidth=0&videoHeight=0&dsControl=manual&dsSensitivity=100&serverURL=$g_streamingServer/$videoFile&DS_Status=true&streamType=vod&autoStart=true' type='application/x-shockwave-flash'> </embed></object> </noscript>
			</div>
</body>
</html>";
	
	$fh = fopen($filename, 'w') or trigger_error('Failed to move create video page', E_USER_ERROR);
	fwrite($fh, $page);
	fclose($fh);
	return true;
}


