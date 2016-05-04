<?php
/*******************************************************************************
rss.php
This page generate rss xml

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
if(file_exists('localize.php')){
	require_once('localize.php');
}

require_once('DefineLoad.php');
require_once('constants.php');
require_once(APPLICATION_PATH . '/lib/FileExistsInPath.php');

require_once(APPLICATION_PATH . '/config.inc.php');
require_once(APPLICATION_PATH . '/common.inc.php');

require_once(APPLICATION_PATH . '/classes/reserves.class.php');
require_once(APPLICATION_PATH . '/classes/course.class.php');
require_once(APPLICATION_PATH . '/classes/courseInstance.class.php');
require_once(APPLICATION_PATH . '/classes/reserveItem.class.php');
require_once(APPLICATION_PATH . '/classes/tree.class.php');
require_once(APPLICATION_PATH . '/interface/instructor.class.php');
require_once(APPLICATION_PATH . '/interface/student.class.php');

function diplayFailure($message)
{
	//flush;
	header("Content-Type: application/xml");
	print("<?xml version=\"1.0\"?>\n");
	print("<rss version=\"2.0\">\n");
	print("	<channel>\n");
	print("		<error>{$message}</error>\n");
	print("	</channel>\n");
	print("</rss>\n");
	die;
}

function displayFeed($ci, $walker)
{
	global $g_siteURL, $g_reservesEmail, $g_reservesViewer;
	//flush;
	header("Content-Type: application/xml");
    echo "<?xml version=\"1.0\"?>\n";
    echo "<rss version=\"2.0\">\n";
    echo "	<channel>\n\n";

    echo "		<title>" .  htmlentities(stripslashes($ci->course->displayCourseNo() . " " . $ci->course->name . " " . $ci->displayTerm())) . " - Reserve List</title>\n";
// mantis #429
    echo "		<link>".$g_siteURL . "/index.php?cmd=viewReservesList&amp;ci=".$_REQUEST['ci']."</link>\n";

// having multiple managingEditors is also invalid, but most readers handle it gracefully
    foreach($ci->instructorList as $instr)
        echo "		<managingEditor>" . $instr->getEmail() . " (" .  $instr->getName() . ") </managingEditor>\n" ;

    echo "		<webMaster>$g_reservesEmail (Reserves Desk)</webMaster>\n";

    echo "		<description>";
    echo 		"Course Reserves for " . htmlentities(stripslashes($ci->course->displayCourseNo() . " " . $ci->course->name . " " . $ci->displayTerm())) . ", ";
    echo 		"taught by:";
    foreach($ci->instructorList as $instr)
    	echo " " . $instr->getName() . " (" . $instr->getEmail() . ") ";

    echo		". Helper application for viewing reserves: Adobe Acrobat Reader, http://www.adobe.com/products/acrobat/readstep2.html .";
    echo 		"</description>\n\n";
    
    foreach($walker as $leaf) {
    	$rItem = new reserve($leaf->getID());
    	$rItem->getItem();
    	$itemNotes = $rItem->item->getNotes();
    	$resNotes = $rItem->getNotes();

    	echo "		<item>\n";
        
    	//do not show link for headings
    	if(!$rItem->item->isHeading()) {
	        if ($rItem->item->isPhysicalItem()) {
	            echo "          <link>" . htmlentities($g_reservesViewer . $rItem->item->getLocalControlKey()) . "</link>";
	        } else {
	            echo "			<link>" . htmlentities($g_siteURL."/reservesViewer.php?reserve=". $rItem->getReserveID()) . "</link>\n";
	        }
    	}
    	
        echo "			<title>" . htmlentities($rItem->item->getTitle()) . "</title>\n";

    	echo "			<description>";

    	//ouput what we have as the description
    		if ($rItem->item->getAuthor() != "")
                    echo trim($rItem->item->getAuthor()) . ". ";
                    
    		if ($rItem->item->getPerformer() != "")
                    echo "performed by: " . trim($rItem->item->getPerformer()) . ". ";

    		if ($rItem->item->getVolumeTitle() != "")
                    echo trim($rItem->item->getVolumeTitle() . " " . $rItem->item->getVolumeEdition() . " " . $rItem->item->getPagesTimes()) . ". ";
                    
    		elseif ($rItem->item->getVolumeEdition() != "")
                    echo trim($rItem->item->getVolumeEdition() . " " . $rItem->item->getPagesTimes()) . ". ";
                    
    		elseif ($rItem->item->getPagesTimes() != "")
                    echo trim($rItem->item->getPagesTimes()) . ". ";

    		if ($rItem->item->getSource() != "")
                    echo trim($rItem->item->getSource()) . ". ";

			foreach($itemNotes as $note) {
				if($note->getType() == 'Content') {
					echo $note->getText().'. ';
				}
			}
			foreach($resNotes as $note) {
				echo $note->getText().'. ';
			}

    	echo "</description>\n";
    	
    	//show category
    	if($rItem->item->isHeading()) {
    		echo '<category>heading_'.($walker->getDepth()+1).'</category>';
    	}
    	else {
    		echo "<category>reserve</category>";
    	}
    	
    	echo "		</item>\n\n";
    }


	echo "</channel>";
	echo "</rss>\n";
}

function displayHtml($ci, $walker)
{
	global $g_siteURL, $g_reservesEmail, $g_reservesViewer;
	$courseId = intval($_REQUEST['ci']);
	$courseTitle = htmlentities(stripslashes(
		$ci->course->displayCourseNo() 
		. ' ' . $ci->course->name 
		. ' ' . $ci->displayTerm()
		. ' - Reserve List'
	));
	$courseDescription = 'Course Reserves for ' 
		. htmlentities(stripslashes(
			$ci->course->displayCourseNo() . ' ' . $ci->course->name . ' ' . $ci->displayTerm()
		)) . ', taught by: <br/>';
	foreach ($ci->instructorList as $instr){
		$courseDescription .= $instr->getName() . ' (' . $instr->getEmail() . ')';
	}
	$courseDescription .= '. <br/>'
		. 'Helper application for viewing reserves: Adobe Acrobat Reader, http://www.adobe.com/products/acrobat/readstep2.html .'; //#TODO configureize this
	$courseLink = $g_siteURL . "/index.php?cmd=viewReservesList&ci={$courseId}";
?>	<div class="rssTable reservesCourse">
			<h2 class="rssChannel reservesCourseTitle"><a class="rssLink" href="<?php print($courseLink); ?>"><?php print($courseTitle); ?></a></h2>
			<p class="rssChanDesc reservesCourseDescription"><?php print($courseDescription); ?></p>
			<div class="rssItems"><ul class="rssList">
<?php
    foreach($walker as $leaf) {
    	$rItem = new reserve($leaf->getID());
    	$rItem->getItem();
    	$itemNotes = $rItem->item->getNotes();
    	$resNotes = $rItem->getNotes();
		$author = ('' != trim($rItem->item->getAuthor()) ? $rItem->item->getAuthor() . '. ' : '');
		$performer = ('' != trim($rItem->item->getPerformer()) ? $rItem->item->getPerformer() . '. ' : '');
		$hasVolumeTitle = ('' != trim($rItem->item->getVolumeTitle()));
		$hasVolumeEdition = ('' != trim($rItem->item->getVolumeEdition()));
		$hasPagesTimes = ('' != trim($rItem->item->getPagesTimes()));
		$preferedStatement = (
			$hasVolumeTitle
			? trim($rItem->item->getVolumeTitle() . ' ' . $rItem->item->getVolumeEdition() . ' ' . $rItem->item->getPagesTimes())
			: (
				$hasVolumeEdition
				? trim($rItem->item->getVolumeEdition() . ' ' . $rItem->item->getPagesTimes()) . '. '
				: (
					$hasPagesTimes
					? trim($rItem->item->getPagesTimes()) . '. '
					: ''
				)
			)
		);
		$source = ('' != trim($rItem->item->getSource()) ? $rItem->item->getSource() . '. ' : '');
		$itemNotesString = '';
		$reserveNotesString = '';
		foreach($itemNotes as $note) {
			if('Content' == $note->getType()) {
				$itemNotesString .= $note->getText() . '. ';
			}
		}
		foreach($resNotes as $note) {
			$reserveNotesString .= $note->getText() . '. ';
		}
		$description = $author . $performer . $preferedStatement . $source . $itemNotesString . $reserveNotesString;
?>
				<li class="rssItem">
<?php
if($rItem->item->isHeading()){
?>
					<h3 class="rssTitle reservesItemHeader"><?php print(htmlentities($rItem->item->getTitle())); ?></h3>
<?php
} else {
	$itemLink = (
		$rItem->item->isPhysicalItem() 
		? htmlentities($g_reservesViewer . $rItem->item->getLocalControlKey()) 
		: htmlentities("{$g_siteURL}/reservesViewer.php?reserve=" . $rItem->getReserveID())
	);
?>
					<h3 class="rssTitle reservesItemTitle"><a href="<?php print($itemLink); ?>"><?php print(htmlentities($rItem->item->getTitle())); ?></a></h3>
<?php
}
?>
					<p class="rssDesc reservesItemDescription"><?php print($description); ?></p>
				</li>
<?php
    }
?>	
			</ul></div>
		<a class="rssContact reservesSupportContact" href="mailto:<?php print($g_reservesEmail); ?>">Reserves desk: <?php print($g_reservesEmail); ?></a>
	</div>
	
<?php
}

function displayScript($ci, $walker, $style=false) 
{
	ob_start();
	displayHtml($ci, $walker);
	$html = str_replace(array("\n","'"), array('\n',"\'"), ob_get_contents());
	ob_end_clean();
	$css = (
		$style
		? // #TODO i feel bad abotu putting this here, but kind of under the gun to just get it working :P
			'<style>'
			. '.rssTable{margin:1em;}'
			. '.rssChannel{font-size:1.5em;}'
			. '.rssTitle{font-size:1.25em;}'
			. '.rssItems{padding-left:1em;border-top:1px dotted #999; border-bottom:1px dotted #999;margin-bottom:1em;}'
			. '.rssList{list-style:none;margin-left:0;padding-left:0;}'
			. '.rssList li {border-bottom:1px dotted #ccc;}'
			. '</style>'
		: ''
	);
	header("Content-Type: text/javascript");
	print("document.write('{$css}{$html}');");
}


if (!array_key_exists('ci', $_REQUEST)) {
	displayFailure('Data could not be retrieved. Course instance not specified. Please contact the systems administrator.');
}

$ci = new courseInstance($_REQUEST['ci']);
$ci->getPrimaryCourse();
try{
	$ci->getCrossListings();
} catch (Rd_Exception $e){
	
}
$ci->getInstructors();
//get reserves as a tree + recursive iterator
$walker = $ci->getReservesAsTreeWalker('getActiveReserves');

(
	array_key_exists('format', $_REQUEST) && 'javascript' == $_REQUEST['format'] 
	? displayScript($ci, $walker, array_key_exists('style', $_REQUEST))
	: displayFeed($ci, $walker) 
);

die();

