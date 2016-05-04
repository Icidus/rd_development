#!/usr/bin/php
<?php

$configPath = 'path/to/your/config.xml'; // IMPORTANT
ini_set('error_reporting', E_ALL);
ini_set('display_errors', 'On');

/*******************************************************************************
auditReport.php

Created by Karl Doerr modified by Troy Hurteau, NCSU Libraries (libraries.opensource@ncsu.edu)

This is an NCSU-specific report. It generates a csv with a list of files
uploaded to RD for the current day and emails this csv to reserves staff.

This report needs to be scheduled to run daily in cron.

Sample cron command: 
0 1 * * * /var/www/reserves/admin/auditReport.php >/dev/null 2>&1

*******************************************************************************/
$usageMessage = 'usage: ./' . basename(__FILE__) . " [YYYY-MM-DD]\n";

if (array_key_exists(1, $argv) 
	&& ( '?' == trim($argv[1]) || 'help' == trim(strtolower($argv[1])))
) {
	die($usageMessage);	
}

function sendFailureMessage($mailTo, $mailFrom, $mailBody)
{
	$mailSubject = 'ReservesDirect Audit Report Failure';
	$mailSuccess =  mail($mailTo, $mailSubject, $mailBody, "From: $mailFrom\nReturn-Path: $mailFrom");
	die($mailBody . (
		$mailSuccess
		? ' The application admin was notified.'
		: ' Unable to notify the application admin.'
	)  ."\n");
}

function rdConnect($hostname, $database, $username, $password)
{
	$handle = mysql_connect($hostname, $username, $password);
	$db = mysql_select_db($database);
	if (false == $db) {
		throw new Exception("Could not select {$database} on {$hostname}.");
	}
	return $handle;
}

function rdQuery($query, $db, $eol)
{
	echo $query . $eol;
	$result = mysql_query($query, $db);
	print mysql_info($db);
	print ($result) ? "The query was successful." . $eol : "The query was unsuccessful.". $eol;
	return $result;
}

function urlIsLocal($url)
{
	$regexString = "#^http://#";
	$secureRegexString = "#^https://#";
	return preg_match($regexString, $url) || preg_match($secureRegexString, $url);
}

/* //these are not currently used...
function getSizeFile($url) 
{
    if (substr($url,0,4)=='http') {
        $x = array_change_key_case(get_headers($url, 1),CASE_LOWER);
        if ( strcasecmp($x[0], 'HTTP/1.1 200 OK') != 0 ) { $x = $x['content-length'][1]; }
        else { $x = $x['content-length']; }
    }
    else { $x = @filesize($url); }

    return $x;
}

function format_size($size, $round = 0) 
{
    //Size must be bytes!
    $sizes = array('B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
    $total = count($sizes);
    for ($i=0; $size > 1024 && $i < $total; $i++) $size /= 1024;
    return round($size,$round).$sizes[$i];
}
*/

function send($to, $from, $path, $eol)
{
	$subject = "ReservesDirect Audit Report";
	return mail_attachment($path, $to, $from, $subject, '', $eol);
}

function mail_attachment($path, $mailto, $mailFrom, $subject, $message, $eol)
{
	$attachmentName = 'rd_audit_report.csv';
	$file_size = filesize($path);
	/*$handle = fopen($path, 'r');
	$content = fread($handle, $file_size);
	fclose($handle);*/
	$content = file_get_contents($path);
	$content = chunk_split(base64_encode($content));
	$uid = md5(uniqid(time()));
	$header = "From: {$mailFrom}\r\nReturn-Path: {$mailFrom}\r\n";
	$header .= "MIME-Version: 1.0\r\n";
	$header .= "Content-Type: multipart/mixed; boundary=\"{$uid}\"\r\n\r\n";
	$header .= "This is a multi-part message in MIME format.\r\n";
	$header .= "--{uid}\r\n";
	$header .= "Content-type:text/plain; charset=iso-8859-1\r\n";
	$header .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
	$header .= "{$message}\r\n\r\n";
	$header .= "--{$uid}\r\n";
	$header .= "Content-Type: application/octet-stream; name=\"{$attachmentName}\"\r\n"; // use diff. tyoes here
	$header .= "Content-Transfer-Encoding: base64\r\n";
	$header .= "Content-Disposition: attachment; filename=\"{$attachmentName}\"\r\n\r\n";
	$header .= $content."\r\n\r\n";
	$header .= "--{$uid}--";
	return mail($mailto, $subject, $message, $header);
}

$reportFor = (
	array_key_exists(1, $argv)
	? str_replace('/', '-', $argv[1])
	: date('Y-m-d', mktime(
		0, 0, 0, 
		date('m'), 
		date('d'), 
		date('Y')
	))
);
$reportTimestamp = strtotime($reportFor);
if (false === $reportTimestamp || -1 === $reportTimestamp) { //-1 catches the case of PHP < 5.1.0
	die("Date format not recognized.\n$usageMessage");
}
$reportFrom = date('Y-m-d', mktime(
	0, 0, 0, 
	date('m',$reportTimestamp), 
	date('d',$reportTimestamp), 
	date('Y',$reportTimestamp)
));
$reportUntil = date('Y-m-d', mktime(
	0, 0, 0, 
	date('m',$reportTimestamp), 
	date('d',$reportTimestamp) - 1, 
	date('Y',$reportTimestamp)
));

$config = simplexml_load_file($configPath);

$documentDirectory = (string)$config->documentDirectory;
$dataDirectory = (string)$config->dataDirectory;
$failureNotificationTo = (string)$config->errorEmail;
$reportTo = (string)$config->adminEmail;
$failureNotificationFrom = 'yournotificationdestination@yourdomain.com';
$reportSender = 'no-reply@yourdomain.com';
$csvFileName = 'uploaded.csv';
$csvFilePath = $dataDirectory . $csvFileName;

if (!$config->database) {
	sendFailureMessage(
		$failureNotificationTo, 
		$failureNotificationFrom, 
		'RD Database configuration missing.'
	);
}

$dbName = (string)$config->database->dbname;
$dbHost = (string)$config->database->host;
$dbUser = (string)$config->database->username;
$dbPass = (string)$config->database->pwd;

$eolString = (
	array_key_exists('SERVER_PROTOCOL', $_SERVER) && $_SERVER['SERVER_PROTOCOL']
	? '<br />' 
	: "\n"
);

if ('' == $documentDirectory
	|| '' == $dataDirectory
	|| !is_writable($dataDirectory)
	|| '' == $dbName
	|| '' == $dbHost
	|| '' == $dbUser
	|| '' == $dbPass
) {
	sendFailureMessage(
		$failureNotificationTo, 
		$failureNotificationFrom, 
		'RD is not properly configured.'
	);
}

$title = '';
$author = '';
$course_number = '';
$department = '';
$instructor = '';
$uploader = '';
$url = '';

try {
	$rdDb = rdConnect($dbHost, $dbName, $dbUser, $dbPass);
} catch (Exception $e) {
	sendFailureMessage(
		$failureNotificationTo, 
		$failureNotificationFrom, 
		$e->getMessage()
	);
}
if(!$rdDb){
	sendFailureMessage(
		$failureNotificationTo, 
		$failureNotificationFrom, 
		"Unable to connect to database server: {$dbHost}"
	);
}

$csvString = "'TITLE', 'AUTHOR', 'COURSE NUMBER', 'DEPARTMENT', 'USER WHO UPLOADED', 'FILE URL' \n";

$query = "SELECT DISTINCT eia.audit_id, i.title, i.author, eia.date_added, eia.added_by, ca.course_name, u1.username, d.abbreviation, co.course_number, i.url " 
	. "FROM `electronic_item_audit` eia "
	. "INNER JOIN `items` i  ON eia.item_id = i.item_id "
	. "INNER JOIN `reserves` re ON i.item_id = re.item_id AND eia.date_added = re.date_created " 
	. "INNER JOIN course_instances ci ON re.course_instance_id = ci.course_instance_id "
	. "INNER JOIN course_aliases ca ON ci.primary_course_alias_id = ca.course_alias_id "
	. "INNER JOIN users u1 ON eia.added_by = u1.user_id "
	. "INNER JOIN courses co ON ca.course_id = co.course_id " 
	. "INNER JOIN departments d ON co.department_id = d.department_id "
	. "INNER JOIN access a ON ca.course_alias_id = a.alias_id "
	. "WHERE i.url is NOT NULL and (eia.date_added = '$reportFrom' OR eia.date_added = '$reportUntil')";

$result = rdQuery($query, $rdDb, $eolString);
$rowCount = 0;
while($resarray = mysql_fetch_array($result)){
	$rowCount++;
	$title = $resarray[1];
	$title = str_replace(',', '', $title);
	$author = $resarray[2];
	$author = str_replace(',', '', $author);
	$course_number = $resarray[8];
	$course_number  =str_replace(',', '', $course_number);
	$department = $resarray[7];
	$department = str_replace(',', '', $department);
	$uploader = $resarray[6];
	$uploader = str_replace(',', '', $uploader);
	$url = $resarray[9];
	$url = str_replace(',', '', $url);
	//$size ="";
	if($url){
		$finalUrl ="";
		if(!urlIsLocal($url)){
			$finalUrl = $documentDirectory . $url;
		}
	//	$tempsize = getSizeFile($finalUrl);
	//	$size = format_size($tempsize);
	}
	$csvString .= "'$title', '$author', '$course_number', '$department', '$uploader', '$url'" . "\n";
}

$fh = fopen($csvFilePath, 'w');
$wroteToFile = fwrite($fh, $csvString);
fclose($fh);

if (!$wroteToFile) {
	sendFailureMessage(
		$failureNotificationTo, 
		$failureNotificationFrom, 
		"Unable to write data to the temp file. {$rowCount} new uploads found in DB for {$reportFrom}."
	);
}

if (!send($reportTo, $reportSender, $csvFilePath, $eolString)){
	sendFailureMessage(
		$failureNotificationTo, 
		$failureNotificationFrom, 
		"Unable to send mail. {$rowCount} new uploads found in DB for {$reportFrom}."
	);
} else {
	print("Report was Sent.{$eolString}");
}
