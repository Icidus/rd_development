<?php
/*******************************************************************************
reservesDisplayer.class.php

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
require_once(APPLICATION_PATH . '/displayers/noteDisplayer.class.php');
require_once(APPLICATION_PATH . '/classes/tree.class.php');
require_once(APPLICATION_PATH . '/lib/Client/Sirsi/QuickLookup.php'); //#TODO #2.1.0 this is hard coded for NCSU behavior. Generalize
require_once(APPLICATION_PATH . '/lib/Client/CourseTools.php');

class reservesDisplayer extends noteDisplayer {

	protected $_displayerName = 'reserves';
	
	function displayReserves($cmd, &$ci, &$tree_walker, $reserve_count, &$hidden_reserves=null, $preview_only=false) {
		global $g_textbookSearch;
        $u = Account_Rd::getUserInterface();

		if(!($ci->course instanceof course)) {
			$ci->getPrimaryCourse();
		}
		
		//if previewing, temporarily give the current user a role of student
		//Note: this process is reversed at the end of this method.
		if($preview_only) {
			$curr_user = $u;	//save current user
			$users = new users();
			$u = $users->initUser('student', $curr_user->getUserName());	//init current user as student
		}
        
        // announce rss feed to capable browsers
        Rd_Layout::printCiRssLink($ci); //#TODO this is the wrong place for this. make a WITH directive for Course RSS.
		
		$exit_class_link = $preview_only ? '<a href="javascript:window.close();">Close Window</a>' : '<a href="index.php">Exit class</a>' ;		
?>

		<div>
			<div class="widgetHolder">
				<div class="exitHolder"><strong><?php print($exit_class_link); ?></strong></div>
			<?php if(Rd_Registry::get('courseToolsLoaded')){ /*TODO make his a more general plugin utility check */ ?>		
			
				<div class="widgetBox">
					<?php
					Client_CourseTools::getCourseWidget($ci);
					?>
				</div>
			<?php } ?>	
			</div>
			<div class="courseTitle"><?php print($ci->course->displayCourseNo() . " " . $ci->course->getName()); ?></div>			
			<div class="courseHeaders"><span class="label"><?php print($ci->displayTerm()); ?></span></div>			
			<div class="courseHeaders">
				<span class="label">Instructor(s):</span>
							
<?php 
		for($i=0;$i<count($ci->instructorList);$i++) {
			if ($i!=0) echo ',&nbsp;';
			echo '<a href="mailto:'.$ci->instructorList[$i]->getEmail().'">'.$ci->instructorList[$i]->getFirstName().'&nbsp;'.$ci->instructorList[$i]->getLastName().'</a>';
		}
?>		
	
			</div>
			<div class="courseHeaders">
				<span class="label">Crosslstings:</span>	
						
<?php
		if (count($ci->crossListings)==0) {
			echo 'None';
		}
		else {
			for ($i=0; $i<count($ci->crossListings); $i++) {
				if ($i>0) echo',&nbsp;';
				echo $ci->crossListings[$i]->displayCourseNo();
			}
		}
?>

			</div>
			<p />
			<small><strong>Helper Applications:</strong> <a href="http://www.adobe.com/products/acrobat/readstep2.html" target="_new">Adobe Acrobat</a>, <a href="http://www.real.com" target="_new">RealPlayer</a>, <a href="http://www.apple.com/quicktime/download/" target="_new">QuickTime</a>, <a href="http://office.microsoft.com/Assistance/9798/viewerscvt.aspx" target="_new">Microsoft Word</a></small>		
		</div>
		
				
		<form id="textbookForm" name="textbookForm" action="<?php print($g_textbookSearch); ?>" method="GET" target="_blank" style="display: none;">
			<input type="hidden" name="N" value="210854" />
			<input type="hidden" name="Nty" value="1" />
			<input type="hidden" name="Ntk" value="Keyword" />
			<input type="hidden" id="Ntt" name="Ntt" value="" />
		</form>

		
		<form method="post" name="editReserves" action="index.php">
		
			<input type="hidden" name="cmd" value="<?php print($cmd); ?>" />
			<input type="hidden" name="ci" value="<?php print($ci->getCourseInstanceID()); ?>" />
			<input type="hidden" name="hideSelected" value="" />
			<input type="hidden" name="showAll" value="" />

		<table width="100%" border="0" cellspacing="0" cellpadding="0" align="center" style="clear: both;">
			<tr align="left" valign="middle">
				<td class="headingCell1">COURSE MATERIALS</td>
				<td width="75%" align="right">
<?php	if(!$preview_only): ?>
					<input type="submit" name="hideSelected" value="Hide Selected" />
					<input type="submit" name="showAll" value="Show All" />
<?php	endif; ?>
				</td>
			</tr>
			<tr valign="middle">
				<td class="headingCell1" align="center" colspan="2">
					<?php echo $reserve_count; ?> Item(s) On Reserve
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<ul style="list-style:none; padding-left:0px; margin:0px;">
						<li>
							<div class="evenRow" style="padding: 3px 5px 5px 5px; background-color: #FFFFCC">
								<script type="text/javascript" language="Javascript">
									// This javascript is necessary because of a form that wraps these controls. We don't want these
									// controls to have any effect on the "real" form. So the info we enter here gets pasted into another,
									// hidden form placed above. The javascript then submits *that* form. Everything gets returned
									// false here to prevent these controls from having an effect on form that contains them.
									
									var opened=0; // Safari hack to prevent multiple windows opening.
									function runTrueForm () {
										// Put the information from the text box into the appropriate input of the hidden form and submit.
										jQuery('#textbookForm input#Ntt').val(jQuery('#falseNtt').val());
										jQuery('#textbookForm').submit();
										return false;
									}
								</script>
								<strong style="font-size: medium;">Can't find your textbook here?</strong>
								It may still be available. Try searching the catalog.<br/>
								<input type="text" id="falseNtt"  style="margin: 3px; width:60%;"
									onkeypress="javascript:
									jQuery('#falseNtt').keypress(function(e) {
										if (e.which==13 && opened == 0) {
											opened = 1; // Safari hack -- only opens window on first pass.
											return runTrueForm();
										}
									});
									opened = 0;"
								/>
								<input type="button" value="Search" onclick="javascript: return runTrueForm();" />
							</div>
						</li>
			
<?php
		//begin displaying individual reserves
		//loop
		$prev_depth = 0;
		foreach($tree_walker as $leaf) {
			//close list tags if backing out of a sublist
			if($prev_depth > $tree_walker->getDepth()) {
				echo str_repeat('</ul></li>', ($prev_depth-$tree_walker->getDepth()));
			}
			
		
			$reserve = new reserve($leaf->getID());	//init a reserve object

			//is this item hidden?
			$reserve->hidden = in_array($leaf->getID(), $hidden_reserves) ?	true : false;
			
			$rowStyle = (isset($rowStyle) && 'oddRow' == $rowStyle) ? 'evenRow' : 'oddRow';	//set the style

			//display the info
			echo '<li>';
			if($preview_only) {
				self::displayReserveRowPreview($reserve, 'class="'.$rowStyle.'"');
			}
			else {
				self::displayReserveRowView($reserve, 'class="'.$rowStyle.'"');
			}
			
			//start sublist or close list-item?
			echo ($leaf->hasChildren()) ? '<ul style="list-style:none;">' : '</li>';
			
			$prev_depth = $tree_walker->getDepth();
		}
		echo str_repeat('</ul></li>', ($prev_depth));	//close all lists
?>

					</ul>
				</td>
			</tr>
			<tr valign="middle">
				<td class="headingCell1" align="center" colspan="2">
					&nbsp;
				</td>
			</tr>
		</table>
		</form>
		
		<p />
		<div style="margin-left:5%; margin-right:5%; text-align:right;"><strong><?php print($exit_class_link); ?></strong></div>
<?php

		//if previewing, return user to original state
		if($preview_only) {
			$u = $curr_user;
		}
	}
	
	function displaySelectInstructor($user, $page, $cmd)
	{
		$subordinates = common_getUsers('instructor');

		echo "<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\" align=\"center\">\n";
		echo "	<tbody>\n";
		echo "		<tr><td width=\"100%\"><img src=\"public/images/spacer.gif\" width=\"1\" height=\"5\"> </td></tr>\n";
		echo "		<tr>\n";
        echo "			<td align=\"left\" valign=\"top\">\n";
        echo "				<table border=\"0\" align=\"center\" cellpadding=\"10\" cellspacing=\"0\">\n";
		echo "					<tr align=\"left\" valign=\"top\" class=\"headingCell1\">\n";
        echo "						<td width=\"50%\">Search by Instructor </td><td width=\"50%\">Search by Department</td>\n";
        echo "					</tr>\n";

        echo "					<tr>\n";
        echo "						<td width=\"50%\" align=\"left\" valign=\"top\" class=\"borders\" align=\"center\" NOWRAP>\n";

        echo "							<form method=\"post\" action=\"index.php\" name=\"frmReserveItem\">\n";
        //if (!is_null($courseInstance)) echo "					<input type=\"hidden\" name=\"ci\" value=\"$courseInstance\">\n";
    	echo "								<input type=\"hidden\" name=\"page\" value=\"$page\">\n";
		echo "								<input type=\"hidden\" name=\"cmd\" value=\"$cmd\">\n";
		echo "								<input type=\"hidden\" name=\"u\" value=\"".$user->getUserID()."\">\n";
		echo "								<input type=\"submit\" name=\"Submit2\" value=\"Admin Your Classes\">\n";
		echo "							</form>\n";
        echo "							<br>\n";
        echo "							<form method=\"post\" action=\"index.php\" name=\"frmReserveItem\">\n";
    	//if (!is_null($courseInstance)) echo "					<input type=\"hidden\" name=\"ci\" value=\"$courseInstance\">\n";
        echo "								<input type=\"hidden\" name=\"page\" value=\"$page\">\n";
		echo "								<input type=\"hidden\" name=\"cmd\" value=\"$cmd\">\n";
		echo "								<select name=\"u\">\n";
	    echo "									<option value=\"--\" selected>Choose an Instructor\n";
        foreach($subordinates as $subordinate)
		{
			echo "									<option value=\"" . $subordinate['user_id'] . "\">" . $subordinate['full_name'] . "</option>\n";
		}

        echo "								</select>\n";
        //echo "								<br>\n";
        //echo "								<br>\n";
        echo "								<input type=\"submit\" name=\"Submit2\" value=\"Select Instructor\">\n";
        echo "							</form>\n";
        echo "						</td>\n";
        echo "						<td width=\"50%\" align=\"left\" valign=\"top\" class=\"borders\" align=\"center\" NOWRAP>&nbsp;\n";
        echo "						</td>\n";
        echo "					</tr>\n";
        echo "				</table>\n";
		echo "			</td>\n";
		echo "		</tr>\n";
		echo "	</tbody>\n";
		echo "</table>\n";

	}

/**
 * @return void
 * @param int $ci -- user selected course_instance selected for DisplaySelectClass
 * @desc Allows user to determine how they would like to add Reserves
 * 		expected next steps
 *			searchItems::searchScreen
 *			searchItems::uploadDocument
 *			searchItems::addURL
 *			searchItems::faxReserve
 */
function displaySearchItemMenu($ci)
{
	global $g_dbConn, $g_copyrightNoticeURL, $g_name, $g_institution;
	$u = Account_Rd::getUserInterface();
	$supportEmail = Rd_Registry::get('root:supportEmail');
	$siteUrl = Rd_Registry::get('root:mainUrlProper');
	if(array_key_exists('ca', $_REQUEST) && $_REQUEST['ca'] == 'true') {
		$u->setCopyrightAccepted();
	}
	
	if(!$u->getCopyrightAccepted()) {
?>
		<script type="text/javascript">
			jQuery(document).ready(function(){
				tb_show(null, '#TB_inline?height=600&width=625&inlineId=copyrightPolicyBox&modal=true',false);
			}); 
		</script>
<?php
	}
?>
		<div style="border:1px solid #333333; padding:8px 8px 8px 40px; width:40%;float:left;">
			<p><strong>How would you like to add an item to your class?</strong></p>
			<ul><li><a href="index.php?cmd=searchScreen&ci=<?php print($ci); ?>">Search for Items in the Reserves database</a></li>
				<li><a href="index.php?cmd=uploadDocument&ci=<?php print($ci); ?>">Upload a Document</a></li>
				<li><a href="index.php?cmd=addURL&ci=<?php print($ci); ?>">Add a URL</a></li>
				<li><a href="index.php?cmd=placeRequest&ci=<?php print($ci); ?>">Place a request with Reserves staff</a></li>	
				<li><a href="index.php?cmd=faxReserve&ci=<?php print($ci); ?>">Fax a Document</a></li>
			</ul>
		</div>
		<div style="float:right; width:40%; margin-top:25px; padding:10px; text-align:center; border:1px solid #666666; background-color:#CCCCCC;">
			<strong><a href="<?php print($g_copyrightNoticeURL); ?>" target="blank">Copyright policy</a></strong> for adding materials to ReservesDirect.
		</div>
		<div id="copyrightPolicyBox" style="display:none">
		<p align="center">COPYRIGHT GUIDELINES FOR FILES UPLOADED TO ELECTRONIC RESERVES</p>
		<ol>
			<li> The source material from which I select my uploaded files will either be owned by <?php print($g_institution); ?> or I will request that a copy be purchased.</li>
			<li> The source material from which I select my uploaded files will be completely cited in the File Information form provided by <?php print($g_name); ?>.</li>
			<li> I understand that works published in the United States before 1923 are in the public domain; all others may be protected by copyright.</li>
			<li> If not in the public domain, the total portion selected from the source material for upload to a particular course will not exceed the fair use guidelines described in section 107 of the U.S. Copyright Act of 1976, given below.</li>
			<li> If uncertain of the fair use status of my uploaded files, I will e-mail <?php print($supportEmail); ?> for a copyright analysis of the portion requested prior to uploading.</li>
			<li> Section 107 Fair Use:
				<p>For institutions of higher education, the cardinal portion of the Copyright Act is Section 107, the fair use provision. This section sets forth the factors that must be evaluated in determining whether a particular use, without prior permission, is a fair and, therefore, permitted use. The legitimate and lawful application of fair use rights provides the necessary and Constitutionally envisioned balance between the rights of the copyright holder versus societal and educational interests in the dissemination of information.</p>
				<p>In determining whether the use made of a work in any particular case is a fair use, the factors to be considered shall include:</p>
				<ul>
					<li>The purpose and character of the use -- commercial, or nonprofit educational;</li>
					<li>The nature of the copyrighted work -- highly factual, or more creative in expression;</li>
					<li>The amount and substantiality of the portion used in relation to the copyrighted work as a whole;</li>
					<li>The effect of the use upon the potential market for or value of the copyrighted work.</li>
				</ul>
			</li>
		</ol>
		<p style="text-align:center;">
			<input type="submit" id="accept" value="Accept" onclick="tb_remove();window.location='index.php?cmd=addReserve&ca=true&ci=<?php print((int)$_REQUEST['ci']); ?>'"/>
			<input type="submit" id="decline" value="Decline" onclick="window.location='<?php print($siteUrl); ?>'"/>
		</p>
	</div>
	<div style="clear:both;"></div>
<?php 
}

	/**
	 * @return void
	 * @param string $page 	  -- the current page selector
	 * @param string $subpage -- subpage selector
	 * @param string $courseInstance -- user selected courseInstance
	 * @desc Allows user search for items
	 * 		expected next steps
	 *			open catalog in new window
	 *			searchItems::displaySearchResults
	*/
	function displaySearchScreen($page, $cmd, $ci=null)
	{
		global $g_catalogName;
		$catalogUrl = Rd_Registry::get('root:catalogUrl');
		
		$instructors = common_getUsers('instructor');

		echo "<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\" align=\"center\">\n";
		echo "	<tbody>\n";
		echo "		<tr><td width=\"100%\"><img src=\"public/images/spacer.gif\" width=\"1\" height=\"5\"> </td></tr>\n";
		echo "		<tr>\n";
        echo "			<td align=\"left\" valign=\"top\">\n";
        echo "				<table border=\"0\" align=\"center\" cellpadding=\"10\" cellspacing=\"0\">\n";
		echo "					<tr align=\"left\" valign=\"top\" class=\"headingCell1\">\n";
        echo "						<td width=\"50%\">Search for Archived Materials</td><td width=\"50%\">Search by Instructor</td>\n";
        echo "					</tr>\n";

        echo "					<tr>\n";
        //		SEARCH BY Author or Title
        echo "						<td width=\"50%\" class=\"borders\" align=\"center\">\n";
        echo "							<br>\n";
        echo "							<form action=\"index.php\" method=\"post\">\n";
        echo "							<input type=\"text\" name=\"query\" size=\"25\">\n";
        echo "							<input type=\"hidden\" name=\"page\" value=\"$page\">\n";
		echo "							<input type=\"hidden\" name=\"cmd\" value=\"$cmd\">\n";
        if (!is_null($ci)) echo "							<input type=\"hidden\" name=\"ci\" value=\"$ci\">\n";
        //echo "							<br>\n";
        echo "							<select name=\"field\">\n";
        echo "								<option value=\"Title\" selected>Title</option><option value=\"Author\">Author</option>\n";
        echo "							</select>\n";
        //echo "							<br>\n";
        //echo "							<br>\n";
        echo "							<input type=\"submit\" name=\"Submit\" value=\"Find Items\">\n";
        echo "							<br>\n";
        echo "							<br>\n";
        echo "							</form>\n";
        echo "						</td>\n";

        echo "						<td width=\"50%\" align=\"left\" valign=\"top\" class=\"borders\" align=\"center\" NOWRAP>\n";
        echo "							<form method=\"post\" action=\"index.php\" name=\"frmReserveItem\">\n";
		echo "								<input type=\"hidden\" name=\"page\" value=\"$page\">\n";
		echo "								<input type=\"hidden\" name=\"cmd\" value=\"$cmd\">\n";
		echo "								<input type=\"hidden\" name=\"searchType\" value=\"reserveItem\">\n";
		echo "								<input type=\"hidden\" name=\"field\" value=\"instructor\">\n";
		if (!is_null($ci)) echo "					<input type=\"hidden\" name=\"ci\" value=\"$ci\">\n";

        echo "								<br>\n";
		echo "								<select name=\"query\">\n";
	    echo "									<option value=\"--\" selected>Choose an Instructor\n";
        foreach($instructors as $instructor)
		{
			echo "									<option value=\"" . $instructor['user_id'] . "\">" . $instructor['full_name'] . "</option>\n";
		}

        echo "								</select>\n";
        //echo "								<br>\n";
        //echo "								<br>\n";
        echo "								<input type=\"submit\" name=\"Submit2\" value=\"Get Instructor's Reserves\">\n";
        echo "							</form>\n";
        echo "						</td>\n";
        echo "					</tr>\n";
        echo "					<tr align=\"left\" valign=\"top\">\n";
		echo "						<td colspan=\"2\" class=\"borders\" align=\"center\">\n";
        echo "							<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"3\">\n";
        echo "								<tr>\n";
        echo "									<td align=\"left\" valign=\"top\" align=\"center\">\n";
        echo "										<p> You may also search the library's collection in <a href=\"{$catalogUrl}\">{$g_catalogName}</a></p><p>Use the \"Add to List\" function to collect one or more items, then click the link to your \"List\" and use the \"Add to Course Reserves\" link on your list page to request the items for your course.</p>\n";
        echo "									</td>\n";
        echo "								</tr>\n";
        echo "							</table>\n";
        echo "						</td>\n";
        echo "					</tr>\n";
        echo "				</table>\n";
		echo "			</td>\n";
		echo "		</tr>\n";
		echo "	</tbody>\n";
		echo "</table>\n";

	}

	/**
	 * @return void
	 * @param string $page 	  -- the current page selector
	 * @param string $subpage -- subpage selector
	 * @param int $courseInstance -- user selected courseInstance
	 * @param string $query -- users search terms
	 * @desc display search resulting items
	 * 		expected next steps
	 *			open catalog in new window and search for query
	 *			dependent on page value
	*/
	function displaySearchResults($user, $search, $cmd, $ci=null, $hidden_requests=null, $hidden_reserves=null, $loan_periods=null)
	{
		global $g_reservesViewer, $g_permission;

		$showNextLink = false;
		$showPrevLink = false;
		$e = 20;

		if ($search->totalCount > ($search->first + 20)){
			$showNextLink = true;
			$fNext = $search->first + 20;
		}

		if ($search->first > 0){
			$showPrevLink = true;
			$fPrev = $search->first - 20;
		}

		echo "<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\" align=\"center\">\n";
		echo "		<tbody>\n";
		echo "			<tr><td width=\"100%\" colspan=\"2\"><img src=\"public/images/spacer.gif\" width=\"1\" height=\"5\"> </td></tr>\n";
		echo "			<form name=\"searchResults\"method=\"post\" action=\"index.php\">\n";

		if (is_array($hidden_reserves) && !empty($hidden_reserves)){
			foreach($hidden_reserves as $r)
			{
				echo "<input type=\"hidden\" name=\"reserve[" . $r ."]\" value=\"" . $r ."\">\n";
			}
		}

		if (is_array($hidden_requests) && !empty($hidden_requests)){
			foreach($hidden_requests as $r)
			{
				echo "<input type=\"hidden\" name=\"request[" . $r ."]\" value=\"" . $r ."\">\n";
			}
		}

		echo "			<input type=\"hidden\" name=\"cmd\" value=\"$cmd\">\n";
		echo "			<input type=\"hidden\" name=\"ci\" value=\"$ci\">\n";

		echo "			<input type=\"hidden\" name=\"f\">\n";
		echo "			<input type=\"hidden\" name=\"e\" value=\"$e\">\n";
		echo "			<input type=\"hidden\" name=\"field\" value=\"$search->field\">\n";
		echo "			<input type=\"hidden\" name=\"query\" value=\"".urlencode($search->query)."\">\n";

		echo "			<tr>\n";
		echo "					<td align=\"left\">[ <a href=\"index.php?cmd=searchScreen&ci=$ci\" class=\"editlinks\">New Search</a> ] &nbsp;[ <a href=\"index.php?cmd=editClass&ci=$ci\" class=\"editlinks\">Cancel Search</a> ]</td>\n";
		echo "					<td align=\"right\"><input type=\"submit\" name=\"Submit\" value=\"Add Selected Materials\"></td>\n";
		echo "			</tr>\n";

	    if ($showNextLink || $showPrevLink) {
	   		echo "       	<tr><td colspan=\"2\">&nbsp;</td></tr>\n";
        	echo "			<tr><td colspan=\"2\" align='right'>";
        	if ($showPrevLink) {
        		echo "<img src=\"public/images/getPrevious.gif\" onclick=\"javaScript:document.forms.searchResults.cmd.value='searchResults';document.forms.searchResults.f.value=".$fPrev.";document.forms.searchResults.submit();\">&nbsp;&nbsp;";
        	}
        	if ($showNextLink) {
        		echo "<img src=\"public/images/getNext.gif\" onclick=\"javaScript:document.forms.searchResults.cmd.value='searchResults';document.forms.searchResults.f.value=".$fNext.";document.forms.searchResults.submit();\">";
        	}
        	echo "</td></tr>\n";
        } else {
        	echo "<tr><td>&nbsp;</tr></td>\n";
        }


        echo "			<tr>\n";
        echo "				<td colspan=\"2\">\n";
        echo "					<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\">\n";
        echo "					    <tr align=\"left\" valign=\"top\">\n";
        echo "					    	<td class=\"headingCell1\"><div align=\"center\">SEARCH RESULTS</div></td><td width=\"75%\"> <div align=\"right\"></div></td>\n";
        echo "					    </tr>\n";
        echo "					</table>\n";
        echo "				</td>\n";
        echo "			</tr>\n";
        echo "			<tr>\n";
        echo "				<td colspan=\"2\" align=\"left\" valign=\"top\" class=\"borders\">\n";
        echo "					<table width=\"100%\" border=\"0\" cellpadding=\"2\" cellspacing=\"0\" class=\"displayList\">\n";
		echo "						<tr align=\"left\" valign=\"middle\">\n";
        echo "					        <td colspan=\"2\" valign=\"left\" bgcolor=\"#FFFFFF\" class=\"headingCell2\">&nbsp;&nbsp;<i>". $search->totalCount . " items found</i></td>\n";
        echo "							<td class=\"headingCell1\">Select</td>\n";
        echo "				        </tr>\n";

		$cnt = $search->first;
		$i = 0;
		for ($ndx=0;$ndx<count($search->items);$ndx++)
		{

			$item = $search->items[$ndx];
			$physicalCopy = new physicalCopy();
			$physicalCopy->getByItemID($item->getItemID());
			$callNumber = $physicalCopy->getCallNumber();

			$title = $item->getTitle();
			$author = $item->getAuthor();
			$url = $item->getURL();
			$performer = $item->getPerformer();
			$volTitle = $item->getVolumeTitle();
			$volEdition = $item->getVolumeEdition();
			$pagesTimes = $item->getPagesTimes();
			$source = $item->getSource();
			$itemNotes = $item->getNotes();

			$cnt++;
			$rowClass = ($i++ % 2) ? "evenRow" : "oddRow";

			 if ((is_array($hidden_requests) && in_array($item->getItemID(),$hidden_requests)) || (is_array($hidden_reserves) && in_array($item->getItemID(),$hidden_reserves)))
			 {
			 	$checked = 'checked';
			 } else {
			 	$checked = '';
			 }

			echo "						<tr align=\"left\" valign=\"middle\" class=\"$rowClass\">\n";
        	echo "					        <td width=\"4%\" valign=\"top\">\n";
        	echo "								<img src=\"". $item->getitemIcon() ."\" width=\"24\" height=\"20\"></td>\n";
        	echo "							</td>\n";
        	//echo "							<td width=\"88%\"><font class=\"titlelink\">" . $title . ". " . $author . "</font>";

        	$viewReserveURL = "reservesViewer.php?item=" . $item->getItemID();
				if ($item->isPhysicalItem()) {
					//move to config file
					$viewReserveURL = $g_reservesViewer . $item->getLocalControlKey();
				}
				echo '<td width="88%">';
	            if (!$item->isPhysicalItem()) {
	            	echo '<a href="'.$viewReserveURL.'" target="_blank" class="titlelink">'.$title.'</a>';
	            } else {
	            	echo '<em>'.$title.'</em>.';
	            	$reserve = new reserve($item->getReserveId(), $item->getItemID()); //TODO this is a mess
	            	self::displayCatalogLink($reserve);
	            }
	            if ($author)
	            	echo '<br><font class="titlelink"> '. $author . '</font>';


        				if ($callNumber) {
            				echo '<br>Call Number: '.$callNumber;
            				//if ($this->itemGroup == 'MULTIMEDIA' || $this->itemGroup == 'MONOGRAPH')
            			}

           		if ($performer)
	            {
	            	echo '<br><span class="itemMetaPre">Performed by:</span><span class="itemMeta"> '.$performer.'</span>';
	            }
	            if ($volTitle)
	            {
	            	echo '<br><span class="itemMetaPre">From:</span><span class="itemMeta"> '.$volTitle.'</span>';
	            }
	            if ($volEdition)
	            {
	            	echo '<br><span class="itemMetaPre">Volume/Edition:</span><span class="itemMeta"> '.$volEdition.'</span>';
	            }
	            if ($pagesTimes)
	            {
	            	echo '<br><span class="itemMetaPre">Pages/Time:</span><span class="itemMeta"> '.$pagesTimes.'</span>';
	            }
	            if ($source)
	            {
	            	echo '<br><span class="itemMetaPre">Source/Year:</span><span class="itemMeta"> '.$source.'</span>';
	            }

				//show notes
				self::displayNotes($itemNotes);
	            
	        	if ($item->isPhysicalItem() && !is_null($loan_periods)) 
			    {
			    	echo "<br>\n";
			    	echo "<b>Requested Loan Period:<b> ";
			    	echo "	<select name=\"requestedLoanPeriod_". $item->getItemID() ."\">\n";
					for($n=0; $n < count($loan_periods); $n++)
					{
						$selected = ($loan_periods[$n]['default'] == 'true') ? " selected " : "";
			    		echo "		<option value=\"" . $loan_periods[$n]['loan_period'] . "\" $selected>". $loan_periods[$n]['loan_period'] . "</option>\n";
					}
			    	echo "	</select>\n";	    	
			    }	        	   

            echo "							</td>\n";

            echo "						    <td width=\"8%\" valign=\"top\" class=\"borders\" align=\"center\">\n";

            if ($item->getItemGroup() == "ELECTRONIC"){
				echo "                          <input type=\"checkbox\" name=\"reserve[" . $item->getItemID() ."]\" value=\"" . $item->getItemID() ."\" ".$checked.">\n";
			} else {
				echo "                          <input type=\"checkbox\" name=\"request[" . $item->getItemID() ."]\" value=\"" . $item->getItemID() ."\" ".$checked.">\n";
			}

            echo "				            </td>\n";
            echo "						</tr>\n";
		}

        echo "         			</table>\n";
        echo "         		</td>\n";
        echo "         	</tr>";
        echo "       	<tr><td colspan=\"2\">&nbsp;</td></tr>\n";

	    if ($showNextLink || $showPrevLink) {
	   		echo "       	<tr><td colspan=\"2\">&nbsp;</td></tr>\n";
        	echo "			<tr><td colspan=\"2\" align='right'>";
        	if ($showPrevLink) {
        		echo "<img src=\"public/images/getPrevious.gif\" onclick=\"javaScript:document.forms.searchResults.cmd.value='searchResults';document.forms.searchResults.f.value=".$fPrev.";document.forms.searchResults.submit();\">&nbsp;&nbsp;";
        	}
        	if ($showNextLink) {
        		echo "<img src=\"public/images/getNext.gif\" onclick=\"javaScript:document.forms.searchResults.cmd.value='searchResults';document.forms.searchResults.f.value=".$fNext.";document.forms.searchResults.submit();\">";
        	}
        	echo "</td></tr>\n";
        } else {
        	echo "<tr><td>&nbsp;</tr></td>\n";
        }        
        

		echo "			<tr><td colspan=\"2\">&nbsp;</td></tr>\n";
		echo "			<tr><td colspan=\"2\" align=\"right\"><input type=\"submit\" name=\"Submit2\" value=\"Add Selected Materials\"></td></tr>\n";
		echo "			<tr><td colspan=\"2\"><img src=\"public/images/spacer.gif\" width=\"1\" height=\"15\"></td></tr>\n";
		echo "		</tbody>\n";
		echo "</table>\n";

	}

function displayReserveAdded($user, $reserve=null, $ci, $reserveType=null, $request=null)
{
	global $g_reservesViewer, $g_permission;

	echo "<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\" align=\"center\">\n";
  echo "	<tr><td width=\"100%\"><img src=\"public/images/spacer.gif\" width=\"1\" height=\"5\">&nbsp;</td></tr>\n";
  echo "	<tr>\n";
  echo "		<td align=\"left\" valign=\"top\" class=\"borders\">\n";
  echo "			<table width=\"50%\" border=\"0\" align=\"center\" cellpadding=\"0\" cellspacing=\"5\">\n";
  if (empty($reserveType)) {
  	echo "				<tr><td><strong>Your items have been added successfully.</strong></td></tr>\n";
  }
  else {
	echo "				<tr><td><strong>Your request has been submitted successfully.</strong></td></tr>\n";
  }
  echo "              <tr><td>\n";
  echo "							<ul><li class=\"nobullet\"><a href=\"index.php?cmd=editClass&ci=$ci\">Go to class</a></li>\n";
  echo "						</ul></td></tr>\n";
  echo "				<tr>\n";
  if (empty($reserveType)) {
  	echo "					<td align=\"left\" valign=\"top\"><p>Would you like to put more items on reserve?</p><ul>\n";
  }
  else {
  	echo "					<td align=\"left\" valign=\"top\"><p>Would you like submit another request?</p><ul>\n";
  }
  echo "						<li><a href=\"index.php\">No</a></li>\n";
  echo "						<li><a href=\"index.php?cmd=addReserve&ci=$ci\">Yes, for this class.</a></li>\n";
  echo "						<li><a href=\"index.php?cmd=addReserve\">Yes, for another class.</a></li>\n";
  echo "					</ul></td>\n";
  echo "				</tr>\n";
  
 if ($reserve) {
    	
    	$reserve->getItem();
    	
    	$viewReserveURL = "reservesViewer.php?reserve=" . $reserve->getReserveID();
			if ($reserve->item->isPhysicalItem()) {
				$reserve->item->getPhysicalCopy();
				if ($reserve->item->localControlKey)
					$viewReserveURL = $g_reservesViewer . $reserve->item->getLocalControlKey();
				else
					$viewReserveURL = null;
			}

    	$itemIcon = $reserve->item->getItemIcon();
    	$title = $reserve->item->getTitle();
		$author = $reserve->item->getAuthor();
		$url = $reserve->item->getURL();
		$performer = $reserve->item->getPerformer();
		$volTitle = $reserve->item->getVolumeTitle();
		$volEdition = $reserve->item->getVolumeEdition();
		$pagesTimes = $reserve->item->getPagesTimes();
		$source = $reserve->item->getSource();
		$itemNotes = $reserve->item->getNotes();
		$reserveNotes = $reserve->getNotes();
		if (!empty($request)) {
			$requestNotes = $request->getNotes();
		}
			
    	echo "				<tr><td>&nbsp;</td></tr>\n";
    	echo "				<tr><td><strong>Review item:</strong></td></tr>\n";
    	echo "				<tr><td>&nbsp;</td></tr>\n";
    	echo '<tr><td><table border="0" cellspacing="0" cellpadding="0">';
    	echo '<tr align="left" valign="middle" class="oddRow">';
    	echo '	<td width="5%" valign="top"><img src="'.$itemIcon.'" width="24" height="20"></td>';
    	if ($viewReserveURL)
    		echo '	<td width="78%"><a href="'.$viewReserveURL.'" class="itemTitle" target="_blank">'.$title.'</a>';
    	else
    		echo '	<td width="78%"><span class="itemTitle">'.$title.'</span>';
    	if ($author)
    		echo '		<br> <span class="itemAuthor">'.$author.'</span>';
    	if ($performer)
	    	echo '<br><span class="itemMetaPre">Performed by:</span>&nbsp;<span class="itemMeta"> '.$performer.'</span>';
	    if ($volTitle)
				echo '<br><span class="itemMetaPre">From:</span>&nbsp;<span class="itemMeta"> '.$volTitle.'</span>';
	    if ($volEdition)
	    	echo '<br><span class="itemMetaPre">Volume/Edition:</span>&nbsp;<span class="itemMeta"> '.$volEdition.'</span>';
	    if ($pagesTimes)
	    	echo '<br><span class="itemMetaPre">Pages/Time:</span>&nbsp;<span class="itemMeta"> '.$pagesTimes.'</span>';
	    if ($source)
	    	echo '<br><span class="itemMetaPre">Source/Year:</span>&nbsp;<span class="itemMeta"> '.$source.'</span>';
	    	
		//show notes
		self::displayNotes($itemNotes);
		self::displayNotes($reserveNotes);
		if (!empty($request)) {
			self::displayNotes($requestNotes);
		}
			
    	echo '	</td>';
    	echo '	<td width="17%" valign="top">';
    	if ($user->getRole() >= $g_permission['staff']) {
    		echo '[ <a href="index.php?cmd=editReserve&reserveID='.$reserve->getReserveID().'" class="editlinks">edit item</a> ]';
    	}
    	else {
    		echo '&nbsp;';
    	}
    	echo '</td>';
    	echo ' 	<td width="0%">&nbsp;</td>';
    	echo '</tr>';
    	echo '</table></td></tr>';
    	if ($reserveType != null) {
    		echo "<tr><td>You requested that this item be put on " . (($reserveType == "physical"||$reserveType=="physAndElec")?"PHYSICAL":"");
    		echo (($reserveType=="physAndElec")?" and ":"");
    		echo (($reserveType == "electronic"||$reserveType=="physAndElec")?"ELECTRONIC":"") . " reserve.</td></tr>";
    	}
	}
  
  
  echo "			</table>\n";
  echo "		</td>\n";
	echo "	</tr>\n";
  echo "	<tr><td><img src=\"public/images/spacer.gif\" width=\"1\" height=\"15\"></td></tr>\n";
  echo "</table>\n";
}

function displayUploadForm($user, $ci, $type, $docTypeIcons=null)
{
	global $g_permission, $g_notetype, $g_copyrightNoticeURL, $g_maxUploadSize;
	
	if ($type == "URL")		
		$documentTest = "if (frm.url.value == \"\") alertMsg = alertMsg + \"URL is required.<br>\";\n";
	else
	{
		$documentTest  = "if (frm.userFile.value == \"\") alertMsg = alertMsg + \"File is required.<br/>\";\n";
		$documentTest .= "if (frm.pageto.value == \"\" && frm.timeto.value == \"\") alertMsg = alertMsg + \"Please specify Total Pages or Total Running Time.<br/>\";\n";
	}
	
	echo "
		<script language=\"JavaScript\">
		//<!--
			function validateForm(frm)
			{			
				var alertMsg = \"\";

				if (frm.title.value == \"\")
					alertMsg = alertMsg + \"Title is required.<br>\";
				
				$documentTest				
				
				if (!alertMsg == \"\") 
				{ 
					document.getElementById('alertMsg').innerHTML = alertMsg;
					return false;
				}
					
			}
		//-->
		</script>	
	";
	
	
	echo "<form action=\"index.php\" method=\"post\" id=\"frm\"";
	if ($type == 'DOCUMENT') echo " ENCTYPE=\"multipart/form-data\"";
	echo " onSubmit=\"return validateForm(this);\">\n";

	echo "<input type=\"hidden\" name=\"cmd\" value=\"storeUploaded\">\n";
	echo "<input type=\"hidden\" name=\"ci\" value=\"$ci\">\n";
	echo "<input type=\"hidden\" name=\"type\" value=\"$type\">\n";
	echo "<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\" align=\"center\">\n";
	echo "	<tr><td width=\"100%\"><img src=\"public/images/spacer.gif\" width=\"1\" height=\"5\"> </td></tr>\n";
	echo "	<tr>\n";
	echo "		<td align=\"left\" valign=\"top\">\n";
	echo "			<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\">\n";
	echo "				<tr><td width=\"35%\" align=\"left\" valign=\"top\" class=\"headingCell1\">FILE INFORMATION</td><td>&nbsp;</td></tr>\n";
	echo "			</table>\n";
	echo "		</td>\n";
	echo "	</tr>\n";

	echo "	<tr>\n";
	echo "		<td align=\"left\" valign=\"top\" class=\"borders\">\n";
	echo "			<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"3\">\n";
	echo "				<tr valign=\"middle\">\n";
	echo "					<td width=\"35%\" align=\"right\" bgcolor=\"#CCCCCC\"><div align=\"right\" class=\"strong\"><font color=\"#FF0000\"><strong>*</strong></font>Document Title:</div></td>\n";
	echo "					<td align=\"left\"><INPUT TYPE=\"text\" NAME=\"title\" SIZE=50></td>\n";
	echo "				</tr>\n";

	echo "				<tr valign=\"middle\">\n";
	echo "					<td width=\"35%\" height=\"31\" align=\"right\" bgcolor=\"#CCCCCC\"><div align=\"right\" class=\"strong\">Author/Composer:</div></td>\n";
	echo "					<td align=\"left\"><INPUT TYPE=\"text\" NAME=\"author\" SIZE=50></td>\n";
	echo "				</tr>\n";

	if ($type == "URL")
	{
		echo "				<tr valign=\"middle\">\n";
		echo "					<td align=\"right\" bgcolor=\"#CCCCCC\"><div align=\"right\" class=\"strong\"><font color=\"#FF0000\"><strong>*</strong></font>URL:</div></td>\n";
		echo "					<td align=\"left\"><input name=\"url\" type=\"text\" size=\"50\"></td>\n";
		echo "				</tr>\n";
		echo "				<tr>\n";
		echo "					<td align=\"right\" bgcolor=\"#CCCCCC\">&nbsp;</td>\n";
		echo "					<td><font size='-2'>\n";
		echo "						   http://www.reservesdirect.org<br/>\n";
		echo "						   http://links.jstor.org/xxxxx<br/>\n";
		echo "						   http://dx.doi.org/10.xxxxx\n";
		echo "						</font>\n";
		echo "					</td>\n";
		echo "				</tr>\n";
		
	} else {
		echo "				<tr valign=\"middle\">\n";
		echo "					<td align=\"right\" bgcolor=\"#CCCCCC\"><div align=\"right\" class=\"strong\"><font color=\"#FF0000\"><strong>*</strong></font>File:</div></td>\n";
		echo "					<td align=\"left\"><INPUT TYPE=\"file\" NAME=\"userFile\" SIZE=40></td>\n";
		echo "				</tr>\n";

		echo "				<tr valign=\"middle\">\n";
		echo "					<td align=\"right\" bgcolor=\"#CCCCCC\"><div align=\"right\" class=\"strong\">&nbsp;</div></td>\n";
		echo "					<td align=\"left\">Please limit uploaded documents to $g_maxUploadSize.</td>\n";
		echo "				</tr>\n";
	}
	
	if (!is_null($docTypeIcons))
	{
		echo "				<tr valign=\"middle\">\n";
		echo "					<td width=\"35%\" align=\"right\" bgcolor=\"#CCCCCC\"><span class=\"strong\">Document Type Icon:</span></td>\n";
		echo "					<td align=\"left\">";
		echo "						<select name=\"selectedDocIcon\" onChange=\"document.iconImg.src = this[this.selectedIndex].value;\">\n";
				
		for ($j = 0; $j<count($docTypeIcons); $j++)
		{
			//$selectedIcon = (reserveItem::getItemIcon() == $docTypeIcons[$j]['helper_app_icon']) ? " selected " : "";
			echo "							<option value=\"" . $docTypeIcons[$j]['helper_app_icon']  . "\" $selectedIcon>" . $docTypeIcons[$j]['helper_app_name'] . "</option>\n";
		}
			
		echo "						</select>\n";
		echo "					<img name=\"iconImg\" width=\"24\" height=\"20\" border=\"0\" src=\"" . reserveItem::getGenericIcon() . "\">\n";
		echo "					</td>\n";
		echo "				</tr>\n";
	}	
	
	echo "				<tr valign=\"middle\">\n";
	echo "					<td width=\"35%\" align=\"right\" bgcolor=\"#CCCCCC\"><div align=\"right\"><span class=\"strong\">Performer </span>(<em>if applicable)</em><span class=\"strong\">:</span></div></td>\n";
	echo "					<td align=\"left\"><input name=\"performer\" type=\"text\" id=\"Title3\" size=\"50\"></td>\n";
	echo "				</tr>\n";

	echo "				<tr valign=\"middle\">\n";
	echo "					<td width=\"35%\" align=\"right\" bgcolor=\"#CCCCCC\"><div align=\"right\"><span class=\"strong\">Book/Journal/Work Title</span> (<em>if applicable</em>)<span class=\"strong\">:</span></div></td>\n";
	echo "					<td align=\"left\"><INPUT TYPE=\"text\" NAME=\"volumetitle\" SIZE=50></td>\n";
	echo "				</tr>\n";

	echo "				<tr valign=\"middle\">\n";
	echo "					<td width=\"35%\" align=\"right\" bgcolor=\"#CCCCCC\"><div align=\"right\"><span class=\"strong\">Volume / Edition</span> (<em>if applicable</em>)<span class=\"strong\">:</span></div></td>\n";
	echo "					<td align=\"left\"><INPUT TYPE=\"text\" NAME=\"volume\" SIZE=50></td>\n";
	echo "				</tr>\n";

	echo "				<tr valign=\"middle\">\n";
	echo "					<td width=\"35%\" align=\"right\" bgcolor=\"#CCCCCC\"><div align=\"right\"><span class=\"strong\">Pages</span> (<font color=\"#FF0000\"><strong>*</strong></font><em>if applicable</em>)<span class=\"strong\">:</span></div></td>\n";
	echo "					<td align=\"left\"><INPUT TYPE=\"text\" NAME=\"pagefrom\" ID=\"pagefrom\" SIZE=3>  Of:  <INPUT TYPE=\"text\" NAME=\"pageto\" ID=\"pageto\" SIZE=3>\n";
	echo "						<font size='-2'>1 page uploaded of 10 pages total in work</font>\n";
	echo "					</td>\n";	
	echo "				</tr>\n";

	echo "				<tr valign=\"middle\">\n";
	echo "					<td width=\"35%\" align=\"right\" bgcolor=\"#CCCCCC\"><div align=\"right\"><span class=\"strong\">Times</span> (<font color=\"#FF0000\"><strong>*</strong></font><em>if applicable</em>)<span class=\"strong\">:</span></div></td>\n";
	echo "					<td align=\"left\"><INPUT TYPE=\"text\" NAME=\"timefrom\" ID=\"timefrom\" SIZE=3>  Of:  <INPUT TYPE=\"text\" NAME=\"timeto\" ID=\"timeto\" SIZE=3>\n";
	echo "						<font size='-2'>1:23 minutes uploaded of 10:39 minutes total in work</font>\n";
	echo "					</td>\n";		
	echo "				</tr>\n";

	echo "				<tr valign=\"middle\">\n";
	echo "					<td width=\"35%\" align=\"right\" bgcolor=\"#CCCCCC\"><div align=\"right\"><span class=\"strong\">Source / Year</span> (<em>if applicable</em>)<span class=\"strong\">:</span></div></td>\n";
	echo "					<td align=\"left\"><INPUT TYPE=\"text\" NAME=\"source\" SIZE=50></td>\n";
	echo "				</tr>\n";

	echo "				<tr valign=\"middle\">\n";
	if ($user->getRole() >= $g_permission['staff']) {
		echo "					<td width=\"35%\" align=\"right\" bgcolor=\"#CCCCCC\"><div align=\"right\"><span class=\"strong\">Note</span>(<em>if applicable</em>)<span class=\"strong\">:</span></div></td>\n";
		echo "					<td align=\"left\"><TEXTAREA NAME=\"noteText\" cols=50 rows=3>\n</TEXTAREA>\n<br>\n";
	
  	echo '      			<span class="small">Note Type:';
    echo '					<label><input type="radio" name="noteType" value="'.$g_notetype['content'].'" checked>Content Note</label>';
    echo '					<label><input type="radio" name="noteType" value="'.$g_notetype['instructor'].'">Instructor Note</label>';
    echo '					<label><input type="radio" name="noteType" value="'.$g_notetype['staff'].'">Staff Note</label>';
		echo '					<label><input type="radio" name="noteType" value="'.$g_notetype['copyright'].'">Copyright Note</label>';
		echo '					</span>';
	} else {
		echo "					<td width=\"35%\" align=\"right\" bgcolor=\"#CCCCCC\"><div align=\"right\"><span class=\"strong\">Instructor Note</span>(<em>if applicable</em>)<span class=\"strong\">:</span></div></td>\n";
		echo "					<td align=\"left\"><TEXTAREA NAME=\"noteText\" cols=50 rows=3>\n</TEXTAREA>\n<br>\n";
		echo '					<input type="hidden" name="noteType" value="'.$g_notetype['instructor'].'">';
	}
	echo "</td>";
	echo "				</tr>\n";

	echo "				<tr valign=\"middle\">\n";
	echo "					<td width=\"35%\" align=\"right\" bgcolor=\"#CCCCCC\"><div align=\"right\"><span class=\"strong\">This document is from my personal collection:</span> (<em>if applicable</em>)<span class=\"strong\">:</span></div></td>\n";
	echo "					<td align=\"left\"><INPUT TYPE=\"checkbox\" NAME=\"personal\"></td>\n";
	echo "				</tr>\n";

	

	echo "			</table>\n";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "	<tr><td><img src=\"public/images/spacer.gif\" width=\"1\" height=\"15\"></td></tr>\n";
	if ($type == "URL")
	{
		echo "				<tr><td width=\"20%\" valign=\"top\" colspan=\"3\" align=\"center\"><input type=\"submit\" name=\"Submit\" value=\"Save URL\"></td></tr>\n";
	} else {
		echo "				<tr><td width=\"20%\" valign=\"top\" colspan=\"3\" align=\"center\">\n";
		echo "						<div style=\"font:arial; font-weight:bold; font-size:small; padding:5px;\">I have read the Library's <a href=\"$g_copyrightNoticeURL\" target=\"blank\">copyright notice</a> and certify that to the best of my knowledge my use of this document falls within those guidelines.</div>\n";
		echo "						<br><input type=\"submit\" name=\"Submit\" value=\"Save Document\">\n";
		echo "				</td></tr>\n";
	}
	echo "</table>\n";
	echo "</form>\n";
}

function displayRequestForm($user, $ci) {

	global $g_permission, $g_notetype, $g_catalogName, $calendar;
	$u = Account_Rd::getUserInterface();
	$libraries = $u->getLibraries();
	$courseInst = new courseInstance($ci);
	$courseInst->getCourseForUser();
	$instructorList = $courseInst->getInstructors();
	$instructors = "";
	$i = 1;
	$courseInst->course->getDepartment();
	$preferredLibrary = $courseInst->course->department->libraryID;
	
	if (count($instructorList) > 0) {
		foreach ($instructorList as $instructor) {
			$comma = $i<count($instructorList) ? ", " : "";
			$instructors .= $instructor->lastName . $comma;
			$i++;
		}
		$instructors = "(" . $instructors . ")";
	}
	
	//print_r ($courseInst);
	//die;

	echo "<div>\n";
	echo "	<div>\n";
	echo "		You are requesting an item to be put on reserve for:";
	echo "		<div class='courseTitle' style='font-size: 100%; font-variant: inherit'>" . $courseInst->course->displayCourseNo() . " " . $courseInst->course->getName() . " " . $instructors . "</div>";
	echo "		Fill in the fields below as appropriate for the item you are requesting. Items marked with an asterisk are required.";
	echo "	</div>\n";
	echo "</div>\n";


?>

		<script language="javascript">
	
<?php 
	if(!empty($libraries)) {	
		foreach ($libraries as $library) {
			$loanPeriods = $library->getInstructorLoanPeriods();
			$tempArray[$library->getLibraryID()] = $loanPeriods;
		}
		echo "		var loanPeriods = " . json_encode($tempArray) . ";\n";
	}
?>
		function changeLibraryLoanPeriods() {
			var currentLib = jQuery("#librarySelector option:selected").val();
			jQuery("#loanPeriodSelector option").hide();
			if(loanPeriods[currentLib] && loanPeriods[currentLib].length) {
				jQuery("#loanPeriodSelector").show();
				for (i = 0; i < loanPeriods[currentLib].length; i++) {
					var currentLoanPeriod = loanPeriods[currentLib][i].loan_period;
					var currentDefaultStatus = loanPeriods[currentLib][i]["default"];
					jQuery("#loanPeriodSelector option[value=" + currentLoanPeriod + "]").show();
					if (currentDefaultStatus == "true") {
						jQuery("#loanPeriodSelector option[value=" + currentLoanPeriod + "]").attr("selected","selected");
					}
				}
			} else {
				jQuery("#loanPeriodSelector").hide();
			}
		}

		function validateField(thisfield) {
			var val=jQuery(thisfield).val();
			if(val == null || val == false || val == "" ) {
				return false;
			}
			else {
				return true;
			}
		}

		function parseDate(inputString) {
			var dateStringArray = inputString.split("-");
			var dateIntArray = new Array();
			for (var i = 0; i < dateStringArray.length; i++) {
				dateIntArray[i] = parseInt(dateStringArray[i], 10);
			}
			var dateOutput = new Date();
			dateOutput.setFullYear(dateIntArray[0]);
			dateOutput.setMonth(dateIntArray[1] - 1);
			dateOutput.setDate(dateIntArray[2]);
			return dateOutput;
		}
		
		function validateRequestForm() {
			var validate=true;
			var error;
			var validateList= new Array(
				"#requestTypeSelector option:selected", 
				"#itemType input:checked",
				"#loanPeriodSelector option:selected",
				"#titleField input"
			);

			// Check each required form field and make sure it's populated.
			for (var i = 0; i<validateList.length; i++) {
				if (validate) {
					validate = validateField(validateList[i]);
				}
				if(!validate) {
					alert("Please ensure that all required fields are filled in.");
					return validate;
					break;
				}
			}

			// make sure we have somethign that even looks like a valid date here.
			var enteredDate = jQuery("#dateNeeded").val();
			
			if (enteredDate.match(/^(?:(?:(?:(?:(?:1[6-9]|[2-9]\d)?(?:0[48]|[2468][048]|[13579][26])|(?:(?:16|[2468][048]|[3579][26])00)))(\/|-|\.)(?:0?2\1(?:29)))|(?:(?:(?:1[6-9]|[2-9]\d)?\d{2})(-)(?:(?:(?:0?[13578]|1[02])\2(?:31))|(?:(?:0?[1,3-9]|1[0-2])\2(29|30))|(?:(?:0?[1-9])|(?:1[0-2]))\2(?:0?[1-9]|1\d|2[0-8]))))$/) == null) {
				validate = false;
				alert("Please make sure a valid date is entered.\nProper format is YYYY-MM-DD.");
				return validate;
			}

			// Compare entered date to current and throw error if it's not in the future.
			var dateObj = parseDate(enteredDate);
			var today = new Date();

			if (dateObj <= today) {
				validate = false;
				alert("Please enter a later 'Date Needed By' date.\nProper format is YYYY-MM-DD.");
				return validate;
			}
		}

		jQuery(document).ready(function () {
			changeLibraryLoanPeriods();
			jQuery("#librarySelector select").change(function () {
				changeLibraryLoanPeriods();
			});

			jQuery("#requestTypeSelector select").change(function () {
				if (jQuery("#requestTypeSelector option:selected").val() == "electronic") {
					jQuery("#loanPeriodSelector select").attr("disabled", "disabled");
					jQuery("#itemType input").attr("disabled", "disabled");
					jQuery("#itemTypeLabel").removeClass('strong').addClass('disabledLabel');
					jQuery("#itemTypeLabel font").replaceWith("<font><italic>*</italic></font>");
					jQuery("#itemNames").addClass('disabledItemType');
					jQuery("#loanPeriodSelector font").replaceWith("<font><italic>*</italic></font>");
					jQuery("#loanPeriodLabel").removeClass('strong').addClass('disabledLabel'); 
				}
				else {
					jQuery("#loanPeriodSelector select").removeAttr("disabled");
					jQuery("#itemType input").removeAttr("disabled");
					jQuery("#itemTypeLabel").removeClass('disabledLabel').addClass('strong');
					jQuery("#itemTypeLabel font").replaceWith("<font color='#FF0000'><strong>*</strong></font>");
					jQuery("#itemNames").removeClass('disabledItemType');
					jQuery("#loanPeriodSelector font").replaceWith("<font color='#FF0000'><strong>*</strong></font>");
					jQuery("#loanPeriodLabel").removeClass('disabledLabel').addClass('strong');
					
				}
			});
			jQuery("#requestTypeSelector select").change();

		});	
		</script>
  		<div class="headingCell1" style="width:25%; text-align:center;">Reserve Request Form</div>
		<form method="POST" action="index.php" onsubmit="javascript:return validateRequestForm()">
			<input type="hidden" name="ci" value="<?php print($ci); ?>" />
			<input type="hidden" name="cmd" value="placeRequest" />
			<input type="hidden" name="requestSubmitted" value="submitted" />
			<table width="100%" border="0" cellpadding="3" cellspacing="0" class="borders">
				<tr id="requestTypeSelector" valign="middle">
					<td  width="20%" align="right" bgcolor="#CCCCCC" class="strong"><font color="#FF0000"><strong>*</strong></font>Type of Reserve</td>
					<td align="left">
						<select name="requestType" id="requestType">
							<option value="physical">Physical</option>
							<option value="electronic">Electronic</option>
							<option value="physAndElec">Both Physical and Electronic</option>
						</select>
					</td>
				</tr>
				<tr id="itemType" align="left" valign="top">
					<td align="right" bgcolor="#CCCCCC" class="strong" id="itemTypeLabel"><font color="#FF0000"><strong>*</strong></font>Item Type:</td>
					
					<td id="itemNames">
						<input type="radio" name="item_group" value="MONOGRAPH" checked="checked" />Monograph
						&nbsp;<input type="radio" name="item_group" value="MULTIMEDIA" /> Multimedia
					</td>
				</tr>
<?php
	//show reserve-desk/home-library select box
	if(!empty($libraries)):
?>
				<tr id="librarySelector" align="left" valign="top">
					<td align="right" bgcolor="#CCCCCC" class="strong"><font color="#FF0000"><strong>*</strong></font>Reserve Desk:</td>
					<td>
						<select name="home_library">				
<?php			
		foreach($libraries as $lib):
?>
							<option value="<?php print($lib->getLibraryID()); ?>" <?php print($preferredLibrary == $lib->getLibraryID() ? "selected='selected'" : ""); ?>><?php print($lib->getLibrary()); ?></option>
<?php	endforeach; ?>
						</select>
<?php
	endif;
?>	
				<tr id="loanPeriodSelector" align="left" valign="top">
					<td align="right" bgcolor="#CCCCCC" class="strong" id="loanPeriodLabel"><font color="#FF0000"><strong>*</strong></font>Preferred Loan Period:</td>
					<td>
						<select name="loanPeriod">
<?php 				
	foreach($u->getLoanPeriods() as $loanPeriod):
		if($loanPeriod != "MEDIA"):
?>
							<option value="<?php print($loanPeriod); ?>"><?php print($loanPeriod); ?></option>
<?php 
		endif;
	endforeach;
?>

						</select>
					</td>
				</tr>
				<tr align="left" valign="top">
					<td align="right" bgcolor="#CCCCCC" class="strong"><font color="#FF0000"><strong>*</strong></font>Date Needed By:</td>
					<td align="left">
						<input id="dateNeeded" name="dateNeeded" type="text" size="20" value='<?php echo date("Y-m-j", strtotime('+2 week')); ?>'>
						<?php print($calendar->getWidgetAndTrigger("dateNeeded", date("Y-m-j", strtotime('+2 week')))); ?>
						&nbsp;&nbsp;Please allow at least two weeks for processing of your request.
					</td>
				</tr>
				<tr align="left" valign="top">
					<td align="right" bgcolor="#CCCCCC" class="strong">Notes for Reserve Staff:</td>
					<td align="left"><textarea rows="5" cols="38" name="notes"></textarea></td>
				</tr>
				<tr id="titleField" valign="middle">
					<td  width="20%" align="right" bgcolor="#CCCCCC" class="strong"><font color="#FF0000"><strong>*</strong></font>Title:</td>
					<td align="left"><input name="title" type="text" size="50" ></td>
				</tr>
				<tr valign="middle">
					<td align="right" bgcolor="#CCCCCC" class="strong"><font color="#FF0000"></font>Author/Composer:</td>
					<td align="left"><input name="author" type="text" size="50" ></td>
					<td align="left"><i>Please provide as much information as possible. Incomplete citation information will cause delays.</i></td>
				</tr>
				<tr valign="middle">
					<td align="right" bgcolor="#CCCCCC"><span class="strong">Performer</span><span class="strong">:</span></td>
					<td align="left"><input name="performer" type="text" size="50" ></td>
				</tr>
				<tr valign="middle">
					<td align="right" bgcolor="#CCCCCC"><span class="strong">Book/Journal/Work Title:</span></td>
					<td align="left"><input name="volume_title" type="text" size="50" >
				</td>
				<tr valign="middle">
					<td align="right" bgcolor="#CCCCCC"><div align="right"><span class="strong">Volume / Edition:</span></td>
					<td align="left"><input name="volume_edition" type="text" size="50" ></td>
				</tr>
				<tr valign="middle">
					<td align="right" bgcolor="#CCCCCC"><span class="strong">Pages/Times:</span></td>
					<td align="left"><input name="times_pages" type="text" size="50" ></td>
				</tr>
				<tr valign="middle">
					<td align="right" bgcolor="#CCCCCC"><span class="strong">Source / Year:</span></td>
					<td align="left"><input name="source" type="text" size="50" > </td>
				</tr>
				<tr valign="middle">
					<td align="right" bgcolor="#CCCCCC"><span class="strong">Call Number:</span></td>
					<td align="left"><input name="call_number" type="text" size="50" /> </td>
				</tr>
	
				<tr align="left" valign="middle">
					<td class="strong" align="right" bgcolor="#cccccc">ISBN:</td>
					<td><input name="ISBN" size="15" maxlength="15" type="text"></td>
				</tr>
				<tr align="left" valign="middle">
					<td class="strong" align="right" bgcolor="#cccccc">ISSN:</td>
					<td><input name="ISSN" maxlength="15" size="15" type="text"></td>
				</tr>
				<tr align="left" valign="middle">
					<td class="strong" align="right" bgcolor="#cccccc">OCLC:</td>
					<td><input name="OCLC" maxlength="9" size="15" type="text"></td>
				</tr>			
			</table>
			<input type="submit" value="Place Request" />
			
			<script language="javascript">

jQuery('[name="ISSN"],[name="ISBN"]').removeAttr('maxlength');
jQuery('[name="ISSN"]').change(itemIdentClean(8));
jQuery('[name="ISBN"]').change(itemIdentClean(13));
function itemIdentClean (maxLength){
	return function(eventObject){
		eventObject.target.value = eventObject.target.value.replace(/[-\W]/g, '').substring(0,maxLength);
	}
}		
			</script>
		</form>
<?php	


}


function displayFaxInfo($ci)
{

	echo "<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\" align=\"center\">\n";
    echo "	<tr><td width=\"100%\"><img src=\"public/images/spacer.gif\" width=\"1\" height=\"5\"> </td></tr>\n";
    echo "	<tr>\n";
    echo "		<td align=\"left\" valign=\"top\">\n";
    echo "			<table width=\"100%\" border=\"0\" align=\"left\" cellpadding=\"3\" cellspacing=\"0\" class=\"borders\">\n";
    echo "				<tr>\n";
    echo "					<td align=\"left\" valign=\"top\">\n";
    echo "						<blockquote>\n";
    echo "							<p class=\"helperText\">ReservesDirect allows you to fax in a document and will automatically convert it to PDF. Please limit faxed documents to 25 clear, clean sheets to minimize downloading and printing time. To proceed, please fax each document individually (with no cover sheet!) to: </p>\n";
    echo "							<p><span class=\"strong\">(404) 727-9089</span> (On-campus may dial <span class=\"strong\">7-9089</span> )</p>\n";
    echo "							<p class=\"helperText\">Please note that faxes make take up to a minute per page to process during peak times. For best results, wait for a confirmation sheet to print from your fax machine before faxing another document.</p>\n";
    echo "						</blockquote>\n";
    echo "					</td>\n";
    echo "				</tr>\n";
    echo "			</table>\n";
    echo "		</td>\n";
    echo "	</tr>\n";
    echo "	<tr><td>&nbsp;</td></tr>\n";
    echo "	<tr>\n";
    echo "		<td>\n";
    echo "			<form method=\"post\" action=\"index.php\">\n";
	echo "			<input type=\"hidden\" name=\"cmd\" value=\"getFax\">\n";
	echo "			<input type=\"hidden\" name=\"ci\" value=\"$ci\">\n";
    echo "			<p align=\"center\">\n";
    echo "				<input type=\"submit\" name=\"Submit\" value=\"After your fax has finished transmitting, Click Here\">\n";
    echo "			</p>\n";
    echo "			</form>\n";
    echo "			<p align=\"center\">Unclaimed faxes are deleted at midnight.</p>\n";
    echo "		</td>\n";
    echo "	</tr>\n";
    echo "	<tr><td><img src=\"public/images/spacer.gif\" width=\"1\" height=\"15\"></td></tr>\n";
    echo "</table>\n";
}

function claimFax($faxReader, $ci)
{
	global $g_faxURL;

	echo "<form method=\"post\" action=\"index.php\">\n";
	echo "<input type=\"hidden\" name=\"cmd\" value=\"addFaxMetadata\">\n";
	echo "<input type=\"hidden\" name=\"ci\" value=\"$ci\">\n";
	echo "<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\" align=\"center\">\n";
    echo "	<tr><td width=\"100%\" colspan=\"2\"><img src=\"public/images/spacer.gif\" width=\"1\" height=\"5\"> </td></tr>\n";
    echo "	<tr>\n";
	echo "		<td width=\"50%\" align=\"left\" valign=\"top\" class=\"helperText\">Claim your fax.</td>\n";
	echo "		<td width=\"50%\" align=\"left\" valign=\"top\" align=\"right\"><a href=\"index.php?cmd=faxReserve&amp;ci=".$ci."\">Return to Previous Page</a></td>\n";
	echo "	</tr>\n";

	echo "	<tr><td height=\"14\" colspan=\"2\" align=\"left\" valign=\"top\">&nbsp;</td></tr>\n";

	echo "	<tr>\n";
	echo "		<td height=\"14\" colspan=\"2\" align=\"left\" valign=\"top\">\n";
	echo "			<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\">\n";
	echo "				<tr>\n";
	echo "					<td width=\"35%\" align=\"left\" valign=\"top\" class=\"headingCell1\">ACTIVE FAXES</td><td width=\"65%\" align=\"right\" valign=\"top\">&nbsp;</td></tr>\n";
	echo "			</table>\n";
	echo "		</td>\n";
	echo "	</tr>\n";

	if (is_array($faxReader->faxes) && !empty($faxReader->faxes)){
		echo "	<tr>\n";
		echo "		<td colspan=\"2\" align=\"left\" valign=\"top\" class=\"borders\">\n";
		echo "			<table width=\"100%\" border=\"0\" cellpadding=\"2\" cellspacing=\"0\" class=\"displayList\">\n";
		echo "				<tr align=\"left\" valign=\"middle\">\n";
		echo "					<td width=\"20%\" valign=\"top\" bgcolor=\"#FFFFFF\" class=\"headingCell1\">Fax Number</td>\n";
		echo "					<td width=\"40%\" bgcolor=\"#FFFFFF\" class=\"headingCell1\">Time of  Fax</td>\n";
		echo "					<td width=\"15%\" class=\"headingCell1\">Pages</td>\n";
		echo "					<td width=\"10%\" class=\"headingCell1\">&nbsp;</td>\n";
		echo "					<td width=\"15%\" class=\"headingCell1\">Claim Fax</td>\n";
		echo "				</tr>\n";

		for($i=0;$i<count($faxReader->faxes);$i++)
		{
			$fax =& $faxReader->faxes[$i];

			$rowClass = ($i % 2) ? "evenRow" : "oddRow";

			echo "				<tr align=\"left\" valign=\"middle\" class=\"$rowClass\">\n";
			echo "					<td width=\"20%\" valign=\"top\" class=\"$rowClass\" align=\"center\">" . $fax['phone'] . "</td>\n";
			echo "					<td width=\"40%\" class=\"$rowClass\" align=\"center\">" . $fax['time'] . "</td>\n";
			echo "					<td width=\"15%\" valign=\"top\" class=\"$rowClass\" align=\"center\">" . $fax['pages'] . "</td>\n";
			echo "					<td width=\"10%\" valign=\"top\" class=\"$rowClass\" align=\"center\"><a href=\"".$g_faxURL.$fax['file']."\" target=\"_new\">preview</a></td>\n";
			echo "					<td width=\"15%\" valign=\"top\" class=\"$rowClass\" align=\"center\"><input type=\"checkbox\" name=\"claimFax[$i]\" value=\"" . $fax['file'] . "\" onclick=\"this.form.submit.disabled=false;\"></td>\n";
			echo "				</tr>\n";

		}
		echo "				<tr align=\"left\" valign=\"middle\"><td width=\"20%\" valign=\"top\" class=\"headingCell1\">&nbsp;</td><td width=\"40%\" class=\"headingCell1\">&nbsp;</td><td width=\"15%\" valign=\"top\" class=\"headingCell1\">&nbsp;</td><td width=\"10%\" valign=\"top\" class=\"headingCell1\">&nbsp;</td><td width=\"15%\" valign=\"top\" class=\"headingCell1\">&nbsp;</td></tr>\n";
		echo "			</table>\n";
		echo "		</td>\n";
		echo "	</tr>\n";

		echo "	<tr><td colspan=\"2\">&nbsp;</td></tr>\n";
		echo "	<tr><td colspan=\"2\" align=\"center\"><input type=\"submit\" name=\"submit\" value=\"Continue\" disabled=true></td></tr>\n";
	} else {
		echo "	<tr><td colspan=\"2\" align=\"center\"><b>No faxes have been received.  Remember unclaimed faxes are deleted at midnight.</td></tr>\n";
	}
	echo "	<tr><td colspan=\"2\"><img src=\"public/images/spacer.gif\" width=\"1\" height=\"15\"></td></tr>\n";
	echo "</table>\n";
	echo "</form>\n";
}

function displayFaxMetadataForm($user, $faxes, $ci)
{
	global $g_faxURL, $g_permission, $g_notetype;

	echo "<FORM METHOD=POST ACTION=\"index.php\">\n";
	echo "	<INPUT TYPE=\"HIDDEN\" NAME=\"cmd\" VALUE=\"storeFaxMetadata\">\n";
	echo "	<INPUT TYPE=\"HIDDEN\" NAME=\"ci\" VALUE=\"$ci\">\n";

	echo "<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\" align=\"center\">\n";
	echo "	<tr><td width=\"100%\" colspan=\"2\"><img src=\"public/images/spacer.gif\" width=\"1\" height=\"5\"> </td></tr>\n";
	echo "	<tr>\n";
	echo "		<td width=\"50%\" align=\"left\" valign=\"top\" class=\"helperText\">Add information about your fax(es).</td>\n";
	echo "		<td width=\"50%\" align=\"left\" valign=\"top\" align=\"right\"><a href=\"index.php?cmd=getFax&amp;ci=".$ci."\">Return to previous page</a></td>\n";
	echo "	</tr>\n";

	echo "	<tr><td colspan=\"2\" align=\"left\" valign=\"top\">&nbsp;</td></tr>\n";

	echo "	<tr>\n";
	echo "		<td colspan=\"2\" align=\"left\" valign=\"top\">\n";
	echo "			<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\">\n";
	echo "				<tr><td width=\"35%\" class=\"headingCell1\">FAX DETAILS</td><td>&nbsp;</td></tr>\n";
	echo "			</table>\n";
	echo "		</td>\n";
	echo "	</tr>\n";


	if (is_array($faxes) && !empty($faxes))
	{
		$i = 0;
		foreach ($faxes as $fax)
		{
			$rowClass = ($i++ % 2) ? "evenRow" : "oddRow";
			echo "	<tr>\n";
			echo "		<td colspan=\"2\" align=\"left\" valign=\"top\" class=\"borders\">\n";
			echo "			<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"3\">\n";
			echo "				<tr align=\"center\" valign=\"top\" class=\"#CCCCCC\" class=\"displayList\">\n";
			echo "					<td width=\"25%\"><div align=\"center\">" . $fax['phone'] . "</div></td>\n";
			echo "					<td width=\"25%\"><div align=\"center\">" . $fax['time'] . "</div></td>\n";
			echo "					<td width=\"25%\"><div align=\"center\">" . $fax['pages'] . " page(s)</div></td>\n";
			echo "					<td width=\"25%\"><div align=\"center\"><a href=\"" . $g_faxURL . $fax['file'] . "\" target=\"preview\">preview document</a></div></td>\n";
			echo "				</tr>\n";
			echo "			</table>\n";
			echo "		</td>\n";
			echo "	</tr>\n";

			echo "	<tr>\n";
			echo "		<td colspan=\"2\" align=\"left\" valign=\"top\">\n";
			echo "			<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\">\n";
			echo "				<tr><td align=\"left\" valign=\"top\" class=\"headingCell1\">DOCUMENT INFORMATION</td></tr>\n";
			echo "			</table>\n";
			echo "		</td>\n";
			echo "	</tr>\n";

			echo "	<INPUT TYPE=\"HIDDEN\" NAME=\"file[" . str_replace('.', '_',$fax['file']) . "]\" value=\"" . $fax['file'] ."\" >\n";

			echo "	<tr>\n";
			echo "		<td colspan=\"2\" align=\"left\" valign=\"top\" class=\"borders\">\n";
			echo "			<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"3\">\n";
			echo "				<tr valign=\"middle\">\n";
			echo "					<td width=\"35%\" align=\"right\" bgcolor=\"#CCCCCC\" align=\"right\" class=\"strong\">Title:</td>\n";
			echo "					<td align=\"left\"><INPUT TYPE=\"text\" NAME=\"" . $fax['file'] . "[title]\" SIZE=50></td>\n";
			echo "				</tr>\n";

			echo "				<tr valign=\"middle\">\n";
			echo "					<td width=\"35%\" height=\"31\" align=\"right\" bgcolor=\"#CCCCCC\" align=\"right\" class=\"strong\">Author</td>\n";
			echo "					<td align=\"left\"><INPUT TYPE=\"text\" NAME=\"" . $fax['file'] . "[author]\" SIZE=50></td>\n";
			echo "				</tr>\n";

			echo "				<tr valign=\"middle\">\n";
			echo "					<td width=\"35%\" align=\"right\" bgcolor=\"#CCCCCC\" align=\"right\"><span class=\"strong\">Book/Journal/Work Title</span> (<em>if applicable</em>)<span class=\"strong\">:</span></div></td>\n";
			echo "					<td align=\"left\"><INPUT TYPE=\"text\" NAME=\"" . $fax['file'] . "[volumetitle]\" SIZE=50></td>\n";
			echo "				</tr>\n";

			echo "				<tr valign=\"middle\">\n";
			echo "					<td width=\"35%\" align=\"right\" bgcolor=\"#CCCCCC\"><div align=\"right\"><span class=\"strong\">Volume / Edition</span> (<em>if applicable</em>)<span class=\"strong\">:</span></div></td>\n";
			echo "					<td align=\"left\"><INPUT TYPE=\"text\" NAME=\"" . $fax['file'] . "[volume]\" SIZE=50></td>\n";
			echo "				</tr>\n";

			echo "				<tr valign=\"middle\">\n";
			echo "					<td width=\"35%\" align=\"right\" bgcolor=\"#CCCCCC\"><div align=\"right\"><span class=\"strong\">Pages</span> (<em>if applicable</em>)<span class=\"strong\">:</span></div></td>\n";
			echo "					<td align=\"left\">From:  <INPUT TYPE=\"text\" NAME=\"" . $fax['file'] . "[pagefrom]\" SIZE=3> To: <INPUT TYPE=\"text\" NAME=\"" . $fax['file'] . "[pageto]\" SIZE=3></td>\n";
			echo "				</tr>\n";
/* Not implemented in database
			echo "				<tr valign=\"middle\">\n";
			echo "					<td width=\"35%\" align=\"right\" bgcolor=\"#CCCCCC\"><div align=\"right\"><span class=\"strong\">Year</span> (<em>if applicable</em>)<span class=\"strong\">:</span></div></td>\n";
			echo "					<td align=\"left\"><input NAME=\"" . $fax[file] . "[year]\" type=\"text\" size=\"50\"></td>\n";
			echo "				</tr>\n";
*/
			echo "				<tr valign=\"middle\">\n";
			
			
			if ($user->getRole() >= $g_permission['staff']) {
				echo "					<td width=\"35%\" align=\"right\" bgcolor=\"#CCCCCC\"><div align=\"right\"><span class=\"strong\">Note</span>(<em>if applicable</em>)<span class=\"strong\">:</span></div></td>\n";
				echo "					<td align=\"left\"><TEXTAREA NAME=\"" . $fax['file'] . "[noteText]\" cols=50 rows=3>\n</TEXTAREA>\n<br>\n";
	
  			echo '      			<span class="small">Note Type:';
    		echo '					<label><input type="radio" name="'.$fax['file'].'[noteType]" value="'.$g_notetype['content'].'" checked>Content Note</label>';
    		echo '					<label><input type="radio" name="'.$fax['file'].'[noteType]" value="'.$g_notetype['instructor'].'">Instructor Note</label>';
    		echo '					<label><input type="radio" name="'.$fax['file'].'[noteType]" value="'.$g_notetype['staff'].'">Staff Note</label>';
				echo '					<label><input type="radio" name="'.$fax['file'].'[noteType]" value="'.$g_notetype['copyright'].'">Copyright Note</label>';
				echo '					</span>';
			} else {
				echo "					<td width=\"35%\" align=\"right\" bgcolor=\"#CCCCCC\"><div align=\"right\"><span class=\"strong\">Instructor Note</span>(<em>if applicable</em>)<span class=\"strong\">:</span></div></td>\n";
				echo "					<td align=\"left\"><TEXTAREA NAME=\"" . $fax['file'] . "[noteText]\" cols=50 rows=3>\n</TEXTAREA>\n<br>\n";
				echo '					<input type="hidden" name="'.$fax['file'].'[noteType]" value="'.$g_notetype['instructor'].'">';
			}
			
			echo "				</td></tr>\n";

			echo "				<tr valign=\"middle\">\n";
			echo "					<td align=\"right\" bgcolor=\"#CCCCCC\">&nbsp;</td>\n";
			echo "					<td align=\"left\" align=\"center\" class=\"strong\">\n";
			echo "						This Document is from my Personal Collection: \n";
			echo "						<INPUT TYPE=\"checkbox\" NAME=\"" . $fax['file'] . "[personal]\" CHECKED>\n";
			echo "					</td>\n";
			echo "				</tr>\n";
			echo "			</table>\n";
			echo "		</td>\n";
			echo "	</tr>\n";
		}
	}
	echo "	<tr>\n";
	echo "		<td colspan=\"2\" align=\"left\" valign=\"top\">\n";
	echo "			<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"3\">\n";
	echo "				<tr><td width=\"20%\" valign=\"top\" colspan=\"3\" align=\"center\">\n";
	echo "						<div style=\"font:arial; font-weight:bold; font-size:small; padding:15px 5px 5px 5px;\">I have read the Library's <a href=\"$g_copyrightNoticeURL\" target=\"blank\">copyright notice</a> and certify that to the best of my knowledge my use of this document falls within those guidelines.</div>\n";
	echo "						<input type=\"submit\" name=\"Submit\" value=\"Save Document\"></td></tr>\n";
	echo "			</table>\n";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "	<tr><td colspan=\"2\"><img src=\"public/images/spacer.gif\" width=\"1\" height=\"15\"></td></tr>\n";
	echo "</table>\n";
	echo "</form>\n";
}


/**
 * @return void
 * @param courseInstance $ci Reference to a CI object
 * @param array $reserves Reference to an array of reserve IDs
 * @desc displays sorting form
 */
	function displayCustomSort(&$ci, &$reserves) {
?>
	<div>
		<div style="text-align:right;"><strong><a href="index.php?cmd=editClass&amp;ci=<?php print($ci->getCourseInstanceID()); ?>">Return to Edit Class</a></strong></div>
	
		<div style="width:35%; align:left; text-align:center; background:#CCCCCC;" class="borders">
			<strong>Sort by:</strong> [ <a href="index.php?cmd=customSort&amp;ci=<?php print($ci->getCourseInstanceID()); ?>&amp;parentID=<?php print($_REQUEST['parentID']); ?>&amp;sortBy=title" class="editlinks">title</a> ] [ <a href="index.php?cmd=customSort&amp;ci=<?php print($ci->getCourseInstanceID()); ?>&amp;parentID=<?php print($_REQUEST['parentID']); ?>&amp;sortBy=author" class="editlinks">author</a> ]
		</div>
	</div>
	
	<form method="post" name="customSortScreen" action="index.php">		
		<input type="hidden" name="cmd" value="<?php print($_REQUEST['cmd'])?>" />
		<input type="hidden" name="ci" value="<?php print($ci->getCourseInstanceID());?>" />
		<input type="hidden" name="parentID" value="<?php print($_REQUEST['parentID']); ?>" />

		<div align="right">
			<input type="button" name="reset1" value="Reset to Saved Order" onclick="javascript:this.form.submit();">
			&nbsp;<input type="submit" name="saveOrder" value="Save Order">
		</div>
		<br />
		<div class="helperText" style="margin-right:5%; margin-left:5%;">
			<?php if ($_REQUEST['parentID'] == '') {?>
				NOTE: to sort items inside of a heading, return to the Edit Class screen and click on the <img src="public/images/sort.gif" alt="sort contents"> link next to the heading.
			<?php } ?>
		</div>
		<br />		
		<table width="100%" border="0" cellspacing="0" cellpadding="0" align="center">
			<tr valign="middle">
				<td class="headingCell1">Reserves</td>
				<td class="headingCell1" width="100">Sort Order</td>
			</tr>
			<tr>
				<td colspan="2">
				<ul style="list-style:none; padding:0px; margin:0px;">
<?php
	//begin displaying individual reserves
		$reserve_count = count($reserves);
		$order = 1;
		foreach($reserves as $r_id):
			$reserve = new reserve($r_id);	//initialize reserve object
			$reserve->getItem();
		
			$rowStyle = ($rowStyle=='oddRow') ? 'evenRow' : 'oddRow';	//set the style
			$rowClass = ($reserve->item->isHeading()) ? 'class="headingCell2"' : 'class="'.$rowStyle.'"';
?>
			
				<li>
				<div <?php print($rowClass); ?> >
				<div style="float:right; padding:7px 30px 5px 5px;">
					<input type="hidden" name="old_order[<?php print($reserve->getReserveID()); ?>]" value="<?php print($order); ?>">
					<input name="new_order[<?php print($reserve->getReserveID()); ?>]" value="<?php print($order); ?>" type="text" size="3" onChange="javascript:if (this.value <=0 || this.value > <?php print($reserve_count); ?> || !parseInt(this.value)) {alert ('Invalid value')} else {updateSort(document.forms.customSortScreen, 'old_order[<?php print($reserve->getReserveID()); ?>]', this.value, this.name)}">
				</div>
				
				<?php self::displayReserveInfo($reserve, 'class="metaBlock-wide"'); ?>
				
				<div style="clear:right;"></div>
				</div>	
				</li>

			
<?php
			$order++;
		endforeach;
?>
				</ul>
				</td>
			</tr>
			<tr valign="middle" class="headingCell1">
				<td class="HeadingCell1" colspan="2">&nbsp;</td>
			</tr>
		</table>
		<br />		
		<div style="margin-right:5%; margin-left:5%; text-align:right;">
			<input type="submit" name="reset1" value="Reset to Saved Order">
			&nbsp;<input type="submit" name="saveOrder" value="Save Order">
		</div>
	</form>
	
	<div style="margin-left:5%; margin-right:5%; text-align:right;"><strong><a href="index.php?cmd=editClass&amp;ci=<?php print($ci->getCourseInstanceID()); ?>">Return to Edit Class</a></strong></div>
<?php
	}

	/**
	 * @return void
	 * @param courseInstance object $ci CI containing all of these reserves
	 * @param array $reserve_ids Array of reserve IDs
	 * @desc Displays the screen to edit reserve-info of multiple reserves
	 */
	function displayEditMultipleReserves(&$ci, $reserve_ids) {
		global $calendar, $g_permission, $g_notetype, $u;
		$u = Account_Rd::getUserInterface();
		//set default activation/deactivation dates
		$course_activation_date = $ci->getActivationDate();	
		$course_expiration_date = $ci->getExpirationDate();
		
		//set up note form
		$available_note_types = array('instructor', 'content', 'staff', 'copyright');	//all note types valid for a reserve		
		//filter allowed note types based on permission level
		$restricted_note_types = array('content', 'staff', 'copyright');
		//filter out restricted notes if role is less than staff		
		
		if($u->getRole() < $g_permission['staff']) {
			//user does not have permission so remove restricted note types
			$available_note_types = array_diff($available_note_types, $restricted_note_types);
		}	
		
		//convert $reserve_ids into an associative array so that we can pass it to displayHiddenFields()
		$reserves_array = array('selected_reserves'=>$reserve_ids);
?>
		<script language="JavaScript">
		//<!--			
			//resets reserve dates
			function resetDates(from, to) {
				document.getElementById('reserve_activation_date').value = from;
				document.getElementById('reserve_expiration_date').value = to;
			}
			
			//highlight a fieldset
			function highlightElement(element_id, onoff) {
				if(document.getElementById(element_id)) {
					if(onoff) {
						document.getElementById(element_id).className = 'highlight';	
					}
					else {
						document.getElementById(element_id).className = '';
					}
					
					//enable/disable the form elements
					toggleDisabled(document.getElementById(element_id).childNodes, !onoff);	
				}
			}
			
			//disable/enable form elements
			function toggleDisabled(nodes, onoff) {
				for(var x = 0; x < nodes.length; x++) {
					if(nodes[x].disabled != undefined) {
						nodes[x].disabled = onoff;
					}					
					//get all the children too
					toggleDisabled(nodes[x].childNodes, onoff);
				}
			}
		//-->
		</script>
		
		<div style="text-align:right; font-weight:bold;"><a href="index.php?cmd=editClass&amp;ci=<?php print($ci->getCourseInstanceID()); ?>">Return to Class</a></div>
		
		<div class="headingCell1" style="width:30%;">EDIT MULTIPLE RESERVES</div>
		<div id="reserve_details" class="displayArea" style="padding:8px 8px 12px 8px;">	
			<div class="helperText">
				Warning: You are editing multiple reserves.  Select the checkbox next to the changes you wish to make.
			</div>
			<br />
			
			<form id="edit_multiple_form" name="edit_multiple_form" method="post" action="index.php">
				<input type="hidden" name="ci" value="<?php print($ci->getCourseInstanceID()); ?>" />
				<input type="hidden" name="cmd" value="<?php print($_REQUEST['cmd']); ?>" />
				<?php self::displayHiddenFields($reserves_array); ?>
				
				<table width="100%">
					<tr>
						<td width="30" align="center">
							<input type="checkbox" id="edit_status" name="edit_status" onclick="highlightElement('reserve_status', this.checked)" />
						</td>
						<td>			
							<fieldset id="reserve_status">
								<legend>Status</legend>					
								
								<input type="radio" name="reserve_status" id="reserve_status_active" value="ACTIVE" />&nbsp;<span class="active">ACTIVE</span>
								<input type="radio" name="reserve_status" id="reserve_status_inactive" value="INACTIVE" />&nbsp;<span class="inactive">INACTIVE</span>
								
								<?php if ($u->getRole() >= $g_permission['staff']): ?>

<!-- RD 2.4.8 version
									<br/><input type="radio" name="reserve_status" id="reserve_status_denied" value="DENIED" <?php print($reserve_status_denied); ?> />&nbsp;<span class="copyright_denied">DENY ACCESS FOR THIS CLASS ONLY</span>
									<br/><input type="radio" name="reserve_status" id="item_status_denied"    value="DENIED_ALL" <?php print($item_status_denied); ?> />&nbsp;<span class="copyright_denied">DENY ACCESS FOR ALL CLASSES</span>
-->
									<br/><input type="radio" name="reserve_status" id="reserve_status_denied" value="DENIED"  />&nbsp;<span class="copyright_denied">DENY ACCESS FOR THIS CLASS ONLY</span>
									<br/><input type="radio" name="reserve_status" id="item_status_denied"    value="DENY ALL"  />&nbsp;<span class="copyright_denied">DENY ACCESS FOR ALL CLASSES</span> 	
								<?php 	endif; ?>								
								
								<p><small>If you are editing headings, changes will also affect all reserves in those headings.</small></p>
							</fieldset>
						</td>
					</tr>
					<tr>
						<td align="center">
							<input type="checkbox" id="edit_dates" name="edit_dates" onclick="javascript: highlightElement('reserve_dates', this.checked)" />
						</td>
						<td>					
							<fieldset id="reserve_dates">
								<legend>Active Dates (YYYY-MM-DD) <small>[<a href="#" name="reset_dates" onclick="resetDates('<?php print($course_activation_date); ?>', '<?php print($course_expiration_date); ?>'); return false;">Reset dates</a>]</small></legend>						
			
								From:&nbsp;<input type="text" id="reserve_activation_date" name="reserve_activation_date" size="10" maxlength="10" value="<?php print($course_activation_date); ?>" /> <?php print($calendar->getWidgetAndTrigger('reserve_activation_date', $course_activation_date)); ?>
								To:&nbsp;<input type="text" id="reserve_expiration_date" name="reserve_expiration_date" size="10" maxlength="10" value="<?php print($course_expiration_date); ?>" />  <?php print($calendar->getWidgetAndTrigger('reserve_expiration_date', $course_expiration_date)); ?>
								
								<p><small>If you are editing headings, changes will also affect all reserves in those headings.</small></p>
							</fieldset>
						</td>
					</tr>
					<tr>
						<td align="center">
							<input type="checkbox" id="edit_heading" name="edit_heading" onclick="javascript: highlightElement('reserve_heading', this.checked)" />
						</td>
						<td>				
							<fieldset id="reserve_heading">
								<legend>Heading</legend>
								<?php self::displayHeadingSelect($ci); ?>
							</fieldset>
						</td>
					</tr>
					<tr>
						<td align="center">
							<input type="checkbox" id="edit_note" name="edit_note" onclick="javascript: highlightElement('reserve_note', this.checked)" />
						</td>
						<td>				
							<fieldset id="reserve_note">
								<legend>Note</legend>
			
								<textarea id="note_text" name="note_text" style="width:370px; height:90px; overflow:auto;"></textarea>
								<br />
								<small>
									<strong>Note Type:</strong>
			<?php
					$first = true;
					foreach($available_note_types as $note_type):
						$checked = $first ? ' checked="true"' : '';
						$first = false;			
			?>
									<input type="radio" id="note_type_<?php print($g_notetype[$note_type]); ?>" name="note_type" value="<?php print($g_notetype[$note_type]); ?>"<?php print($checked); ?> /><?php print(ucfirst(strtolower($g_notetype[$note_type]))); ?>
			<?php	endforeach; ?>
								</small>
										
							</fieldset>
						</td>
					</tr>
				</table>
				<p />
				<input type="submit" name="submit_edit_multiple" value="Submit Selected Changes" />
			</form>
		</div>
		<br />
		<div style="text-align:right; font-weight:bold;"><a href="index.php?cmd=editClass&amp;ci=<?php print($ci->getCourseInstanceID()); ?>">Return to Class</a></div>
		
		<script language="JavaScript">
		//<!--
			//disable certain form elements by default by default
			toggleDisabled(document.getElementById('reserve_status').childNodes, true);
			toggleDisabled(document.getElementById('reserve_dates').childNodes, true);
			toggleDisabled(document.getElementById('reserve_heading').childNodes, true);
			toggleDisabled(document.getElementById('reserve_note').childNodes, true);
		//-->
		</script>
<?php
	}
	
	/**
	 * @return void
	 * @desc Displays the screen to direct users to the catalog to select reserve items
	 */
	function displayGoToCatalog() {
		$model = array(
			'opacUrl' => Rd_Config::get('catalog:textbook_search')
		);
		$this->display('goToCatalog', $model);
	}
	
	/**
	 * @return void
	 * @desc Displays the screen to allow users to continue with valid items and explain which choices were not valid
	 */
	function displayFailedImportItems($cmd, $failedItems, $importResults) {
		$model = array(
			'requestedItems' => 
				explode('+', 
					array_key_exists('items', $_REQUEST) 
					? str_replace(' ', '+',$_REQUEST['items'])
					: array()
				),
			'failedItems' => $failedItems,
			'remainingCatKeys' => array(),
			'importedItemIds' => array()
		);
		foreach($model['requestedItems'] as $catKey){
			if(!in_array($catKey, $model['failedItems'])){
				$model['remainingCatKeys'][] = $catKey;
			}
		}
		foreach($importResults['importedItems'] as $importItem){
			if(array_key_exists('item_id', $importItem) 
				&& !is_null($importItem['item_id'])
				&& '' != $importItem['item_id']
				&& $importItem['item_id'] > 0) {
				$model['importedItemIds'][] = $importItem['item_id'];
			}
		}
		$this->display('failedImportItems', $model);
	}
	
	/**
	 * @return void
	 * @desc Displays the screen to allow users to enter data on reserve items.
	 */
	function createMultipleReserves($ci, $items, $itemsProcessed = array()) {
		global $g_permission, $g_notetype, $g_catalogName, $calendar;
		$u = Account_Rd::getUserInterface();
		$courseInstance = new courseInstance($ci);
		$courseInstance->getCourseForUser();
		$courseInstance->course->getDepartment();
		$instructorList = $courseInstance->getInstructors();
		$model = array(
			'libraries' => $u->getLibraries(),
			'courseInstance' => $courseInstance,
			'preferredLibrary' => $courseInstance->course->department->libraryID,
			'instructors' => '',
			'ci' => $ci,
			'u' => $u,
			'items' => $items,
			'itemsAlreadyProcessed' => $itemsProcessed,
			'calendar' => $calendar,
			'coverImageUrlPattern' => 'http://syndetics.com/index.aspx?isbn={$isbn}/MC.GIF{$oclc}&client=ncstateu'
		);
		if (count($instructorList) > 0) {
			foreach ($instructorList as $instructor) {
				$model['instructors'] .= $instructor->lastName .
					('' == $model['instructors'] ? '' : ', ');
			}
			$model['instructors'] = "({$model['instructors']})";
		}
		$this->display('createMultipleReservesForm', $model);
	}
	
	/**
	 * @return void
	 * @desc Displays the screen to confirm multiple submitted reserve items.
	 */
	function storeMultipleReserves($ci, $items, $itemsStored) {
		global $g_siteURL;
		$courseInstance = new courseInstance($ci);
		$courseInstance->getCourseForUser();
		$model = array(
			'items' => $items,
			'ci' => $ci,
			'courseInstance' => $courseInstance,
			'itemsStored' => $itemsStored,
			'coverImageUrlPattern' => 'http://syndetics.com/index.aspx?isbn={$isbn}/MC.GIF{$oclc}&client=ncstateu',
			'rdUrl' => $g_siteURL
		);
		$this->display('storeMultipleReservesConfirm', $model);
	}
	
	/**
	 * @return void
	 * @desc Displays the screen staff to select a "Manage Items" action.
	 */
	function displayStaffAddReserve($ci)
	{
		$model = array (
			'useVideoUpload' => Rd_Registry::get('root:videoReservesEnabled'),
			'ci' => $ci
		);
		$this->display('staffSelect', $model);	
	}
}
