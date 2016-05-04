<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<?php 
global $g_institution, $g_name, $g_siteURL, $g_reservesEmail;
$institutionName = Rd_Registry::get('root:institutionName');
$instanceName = Rd_Registry::get('root:instanceName');
$url = Rd_Registry::get('root:mainUrlProper');
$email = Rd_Registry::get('root:supportEmail');
?>

<html><head>


<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"><title>File not accessible</title>

<style type="text/css">
<!--
.style1 {
	font-size: x-large;
	font-weight: bold;
	color: #000066;
	font-family: Arial, Helvetica, sans-serif;
}
.style2 {
	font-family: "Times New Roman", Times, serif;
	color: #666666;
}
-->
</style></head><body linkifytime="7" linkified="2" linkifying="false">
<p class="style1">THIS FILE IS NOT ACCESSIBLE </p>
<div style="width: 450px;"><p class="style2">The file you requested is not accessible using the link that you tried. 
For security and copyright reasons, the system allows access to documents only to 
students and instructors enrolled in or teaching a class for the current semester. 
</p>
<?php 
	if (class_exists('Account_Rd') && Account_Rd::getLevel() >= 0) {
?>
<p class="style2">You are logged in. The file you are trying to access may no longer be active, or is not for a class in which you are enrolled.</p>
<?php 		
	} else  {
?>
  <p class="style2">The link you tried may no longer be active,
may be a broken link, or you may not be officially enrolled in the
class that is using the file. Please <a href="<?php print($url); ?>">log in</a> to <?php print($instanceName); ?> to access your classes and view files.</p>
  <p><span class="style2">If you are an instructor trying to link to this file, <a href="<?php print($url); ?>">log in</a>, click on your class to edit it, and copy the "Link to this file" URL under the reading you are trying to link to. 
  That link may be posted anywhere and will only be accessible to students currently enrolled in your course</span>. </p>
</div>
<?php } ?>
<div style="border-top: 1px solid rgb(51, 51, 51); padding: 10px; width: 400px; font-size: small; color: rgb(102, 51, 0);">
<?php print($instanceName); ?><br>
<?php print($institutionName); ?><br>
<a class="linkification-ext" href="<?php print($url); ?>" title="Linkification: <?php print($url); ?>"><?php print($g_siteURL); ?></a><br>
For assistance, email <a class="linkification-ext" href="mailto:<?php print($email); ?>" title="Linkification: mailto:<?php print($email); ?>"><?php print($email); ?></a><br>
</div>
</body></html>