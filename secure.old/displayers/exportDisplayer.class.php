<?php
/*******************************************************************************
exportDisplayer.class.php


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
require_once(APPLICATION_PATH . '/lib/Rd/Displayer/Base.php');

class exportDisplayer extends Rd_Displayer_Base {
	
	static function getRSS_URL($file)
	{
		global $g_siteURL; //#TODO unused... but it probably should be :P
		return $g_siteURL . '/' . $file; //preg_replace('/index.php/', $file, $_SERVER['PHP_SELF']);
	}
	
	function displaySelectExportOption($ci) {
		global $g_courseware;
		$ci->getCourseForUser();
?>
		<div style="text-align:right; font-weight:bold;"><a href="index.php?cmd=editClass&amp;ci=<?php print($ci->getCourseInstanceID()); ?>">Return to Class</a></div>
		
		<form method="post" action="index.php">
			<input type="hidden" name="cmd" value="exportClass" />
			<input type="hidden" name="ci" value="<?php print($ci->getCourseInstanceID()); ?>" />
			
			<div style="width:500px; margin:auto;">
				<div class="headingCell1">Choose a Courseware Package</div>
				<div class="borders" style="padding:10px;">
					<strong>Class:</strong>
					<?php print($ci->course->displayCourseNo().' -- '.$ci->course->getName()); ?>
					<p />
					<strong>Export To:</strong>
					<br />
					<label><input type="radio" name="course_ware" checked value="website">Personal Web Page</label><br>
					<label><input type="radio" name="course_ware" value="blackboard"  value=\"radio\">Campus courseware system (<?php 		$delim = "";
		foreach ($g_courseware as $cw){
			echo $delim . $cw['name'];
			 
			$delim = ", ";	
		} ?>)</label><br>
					<p />
					<input type="submit" name="Submit" value="Get Instructions on How to Export Class">					
				</div>
			</div>
		</form>
<?php
	}


	function displayExportInstructions_blackboard($ci)
	{
		global $g_BlackboardLink, $g_courseware;
		
		echo "<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"3\" align=\"center\">\n";
		echo "	<tr><td width=\"140\"><img src=\"public/images/spacer.gif\" width=\"1\" height=\"5\"> </td></tr>\n";
		echo "	<tr>\n";
		echo "		<td class=\"headingCell2\">Export Reserve List for ". $ci->course->displayCourseNo() . ' -- ' . $ci->course->getName() . " to Courseware</td>\n";
		echo "	</tr>\n";
		
		echo "	<tr>\n";
		echo "		<td align=\"left\" valign=\"top\">\n";
		echo "			<p><strong>Instructions:</strong></p>\n";
		echo "			<p>Generate the courseware connection with the link below.  The link will create a small file to download to your computer. When prompted save the file to your local machine with an html extension, making note of the save location.</p>\n";
		echo "			<p>Then access your course within ";
		foreach ($g_courseware as $cw){
			echo $cw['name'] . ', ';
		}
		//Moodle, Vista, Wolfware, 
		echo " 			and navigate to the content section where you would like your reserves to appear. Upload the html file you saved in the step above.</p>\n";		
		echo "			<p>Choose any other options you wish and finish uploading the document. Your complete reserves list should now appear in your courseware class. The list is dynamic, so any changes you make in Course Reserves will appear instantly in your courseware class as well.</p>\n";
		echo "		</td>\n";
		echo "	</tr>\n";
		
		echo "	<tr>\n";
		echo "		<td align=\"center\">\n";
		echo "			<div style=\"padding-bottom:10px;font-weight:bold;\"><a href=\"index.php?cmd=generateBB&ci=". $ci->getCourseInstanceID()."\" target=\"_blank\">Generate Courseware Link</a></div>\n";
		echo "		</td>\n";		
		echo "	</tr>\n";		

		echo "	<tr>\n";
		echo "		<td align=\"center\">\n";
		echo " 		<p align=\"center\">";
	//	echo "			<a href=\"$g_BlackboardLink\" target=\"_blank\">Go to Vista</a>\n";
		$delim = "";
		foreach ($g_courseware as $cw){
			echo $delim . "<a href=\"" . $cw['url'] . "\" target=\"_blank\">Go to " . $cw['name'] . "</a> ";
			 
			$delim = " | ";	
		}
		echo "	        <br/><a href=\"index.php?cmd=exportClass\">Export another class</a><br> <a href=\"index.php\">Return to Home </a> </p>\n";
		echo "		</td>\n";		
		echo "	</tr>\n";			
		
		echo "</table>\n";

	}
	
	function downloadBBFile($filename, $data)
	{
		
		echo 'done';
	}

	function displayExportInstructions_learnlink($ci)
	{
		echo "<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"3\" align=\"center\">\n";
		echo "	<tr><td width=\"140\"><img src=\"public/images/spacer.gif\" width=\"1\" height=\"5\"> </td></tr>\n";
		echo "	<tr>\n";
		echo "		<td class=\"headingCell2\">Export Reserve List for ". $ci->course->displayCourseNo() . ' -- ' . $ci->course->getName() . " to Wolfware</td>\n";
		echo "	</tr>\n";
		echo "	<tr>\n";
		echo "		<td align=\"left\" valign=\"top\">\n";
		echo "			<p><strong>Instructions:</strong></p>\n";
		echo "			<p>Right-click on the link below (control-click on a Mac) to download the html file needed to export to Wolfware. Save the file to your computer as &quot;reserves.html&quot;. Be sure to remember where you save the file.</p>\n";
		echo "		</td>\n";
		echo "	</tr>\n";
		echo "	<tr>\n";
		echo "		<td align=\"center\" valign=\"top\" bgcolor=\"#CCCCCC\" class=\"strong\">\n";
		echo "			<a href=\"". exportDisplayer::getRSS_URL('export.php') ."?ci=". $ci->getCourseInstanceID() ."\" target=\"_blank\">Click Here to Download File</a>\n";
		echo "		</td>\n";
		echo "	</tr>\n";
		echo "	<tr>\n";
		echo "		<td align=\"left\" valign=\"top\">\n";
		echo "			<p>In Learnlink, open your conference and in the &quot;File&quot; menu, select &quot;Upload&quot;. Find the file on your computer and click &quot;Select&quot;. It should appear in your conference. If you open the file, it should open the course listing in the browser window.</p>\n";
		echo "			<p>Your reserve list (both electronic and physical, circulating items) will appear on the page. Physical items will have links to $g_catalogName for their bibliographic and holdings information.</p>\n";
		echo "	        <p align=\"center\"><a href=\"index.php?cmd=exportClass\">Export another class</a><br> <a href=\"index.php\">Return to Home </a> </p>\n";
		echo "		</td>\n";
		echo "	</tr>\n";
		echo "</table>\n";

	}

	function displayExportInstructions_website($ci)
	{
		echo "<table width=\"100%\" border=\"1\" cellspacing=\"0\" cellpadding=\"3\" align=\"center\">\n";
		echo "	<tr><td width=\"140\"><img src=\"public/images/spacer.gif\" width=\"1\" height=\"5\"> </td></tr>\n";
		echo "	<tr>\n";
		echo "		<td class=\"headingCell2\">Export Reserve List for ". $ci->course->displayCourseNo() . ' -- ' . $ci->course->getName() . " to your Personal Web Page</td>\n";
		echo "	</tr>\n";
		echo "	<tr>\n";
		echo "		<td align=\"left\" valign=\"top\">\n";
		echo "			<p><strong>Instructions:</strong></p>\n";
		echo "			<p>Create your page and cut and paste the following in the &lt;body&gt; &lt;/body&gt; area where you want it to appear:</p>\n";
		echo "		</td>\n";
		echo "	</tr>\n";
		echo "	<tr>\n";
		echo "		<td align=\"left\" valign=\"top\" bgcolor=\"#CCCCCC\" class=\"strong\">\n";
		echo "			&lt;script src=&quot;". exportDisplayer::getRSS_URL('rss.php') ."?ci=". $ci->getCourseInstanceID() ."&amp;style=reserves&amp;format=javascript&quot;&gt;&lt;/script&gt;\n";
		echo "		</td>\n";
		echo "	</tr>\n";
		echo "	<tr>\n";
		echo "		<td align=\"left\" valign=\"top\">\n";
		echo "			<p>If you wish to use your own stylesheet, remove &quot;style=reserves&quot; from the html.<br> The default stylesheet looks like:</p>\n";
		echo "			<p>\n";
?><pre>
.rssTable{margin:1em;}
.rssChannel{font-size:1.5em;}
.rssTitle{font-size:1.25em;}
.rssItems{
    padding-left:1em;
    border-top:1px dotted #999; 
    border-bottom:
    1px dotted #999;
    margin-bottom:1em;
}
.rssList{list-style:none;margin-left:0;padding-left:0;}
.rssList li{border-bottom:1px dotted #ccc;}
</pre>
<?php
		echo "			</p>\n";
		echo "	        <p align=\"center\"><a href=\"index.php?cmd=exportClass\">Export another class</a><br> <a href=\"index.php\">Return to Home </a> </p>\n";
		echo "		</td>\n";
		echo "	</tr>\n";
		echo "</table>\n";
	}

	function generateRSS_javascript($ci)
	{
	}

}
