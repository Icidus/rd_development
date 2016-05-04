<?php
/*******************************************************************************
reservelist.php
This page generates javascript code to display a plain HTML reserves list

Created by Chris Roddy (croddy@emory.edu)
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

$ci = new courseInstance($_REQUEST['ci']);
$ci->getPrimaryCourse();
try{
	$ci->getCrossListings();
} catch (Rd_Exception $e){
	
}
$ci->getInstructors();
//get reserves as a tree + recursive iterator
$walker = $ci->getReservesAsTreeWalker('getActiveReserves');



$htmloutput = '<!DOCTYPE html>
			<html lang="en">
			<head>
			
			<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css" type="text/css">
			<style>
			.header{border-bottom:3px solid #012D62; padding: 5px;}
			.rssList{list-style:none;margin-left:0;padding-left:0;}
			.rssItems{padding-left:1em;border-top:1px dotted #999; border-bottom:1px dotted #999;margin-bottom:1em;}
			</style>
			</head><body>';
$htmloutput .= '<div class="container">
	<div class="header">
		<div class="row">
			<div class="col-md-4"><img src="https://media.smith.edu/media/ereserves/html_files/images/scllogostacked280w.jpg"/></div>
		</div>
	</div>';
$htmloutput .= "<h4>";
$htmloutput .= htmlentities(stripslashes($ci->course->displayCourseNo() . " " . $ci->course->name . " " . $ci->displayTerm())) . " - Reserve List</h4>\n";

// $htmloutput .= "<h3><a href=\"{$g_siteURL}/index.php?cmd=viewReservesList&amp;ci={$_REQUEST['ci']}\">";
// $htmloutput .= htmlentities(stripslashes($ci->course->displayCourseNo() . " " . $ci->course->name . " " . $ci->displayTerm())) . " - Reserve List</a></h3>\n";

foreach($ci->instructorList as $instr)
	$htmloutput .= "<h5>Taught by: <a href=\"mailto:{$instr->getEmail()}\">{$instr->getName()}</a></h5>\n<br>" ;

$htmloutput .= "<div class=\"rssItems\"><ul class=\"rssList\">";

foreach($walker as $leaf) {
	
	$rItem = new reserve($leaf->getID());
	$rItem->getItem();
	$itemNotes = $rItem->item->getNotes();
	$resNotes = $rItem->getNotes();

    $htmloutput .= '<li>';    
	//do not show link for headings
	if(!$rItem->item->isHeading()) {
		if ($rItem->item->isPhysicalItem()) {
			$itemURL = htmlentities($g_reservesViewer . "?func=item-global&doc_library=FCL01&doc_number=" . $rItem->item->getLocalControlKey());
		} else {
			$itemURL = htmlentities($g_siteURL."/reservesViewer.php?reserve=". $rItem->getReserveID());
		}
	}
    $itemLinkOpen = (isset($itemURL) ? "<a href=\"{$itemURL}\">" : '');
    $itemLinkClose = (isset($itemURL) ? '</a>' : '');
    if(!$rItem->item->isHeading()) {	
	$htmloutput .= "<p class=\"rssTitle reservesItemTitle\">{$itemLinkOpen}" . htmlspecialchars_decode($rItem->item->getTitle()) . "{$itemLinkClose}</p>\n";
	} else {
	$htmloutput .= "<h3 class=\"rssTitle well well-sm reservesItemHeader\">" . htmlentities($rItem->item->getTitle()) . "</h3>";	
	}
	$htmloutput .= "<p class=\"rssDesc reservesItemDescription\">";
	//ouput what we have as the description
		if ($rItem->item->getAuthor() != "")
			$htmloutput .= trim($rItem->item->getAuthor()) . ". ";
                    
		if ($rItem->item->getPerformer() != "")
			$htmloutput .= "performed by: " . trim($rItem->item->getPerformer()) . ". ";

		if ($rItem->item->getVolumeTitle() != "")
			$htmloutput .= trim($rItem->item->getVolumeTitle() . " " . $rItem->item->getVolumeEdition() . " " . $rItem->item->getPagesTimes()) . ". ";
                    
		elseif ($rItem->item->getVolumeEdition() != "")
			$htmloutput .= trim($rItem->item->getVolumeEdition() . " " . $rItem->item->getPagesTimes()) . ". ";
                    
		elseif ($rItem->item->getPagesTimes() != "")
			$htmloutput .= trim($rItem->item->getPagesTimes()) . ". ";

		if ($rItem->item->getSource() != "")
			$htmloutput .= trim($rItem->item->getSource()) . ". ";

		foreach($itemNotes as $note) {
				if($note->getType() == 'Content') {
					$htmloutput .= trim(str_replace("\r", "", str_replace("\n", " ", $note->getText().'. ')));
				}
			}
		foreach($resNotes as $note) {
			$htmloutput .= $note->getText().'. ';
		}
	$htmloutput .= "</p>";	
	$htmloutput .= "</li>\n";
}
$htmloutput .= "</ul></div><br>";

$htmloutput .="<div class=\"container\">		
	<div class=\"row\">
	<div class=\"col-md-4 \">
		<a class=\"rssContact reservesSupportContact\" href=\"mailto:$g_reservesEmail\">Reserves desk: $g_reservesEmail</a>
   </div>
    <div class=\"col-md-2\"><a href=\"http://www.smith.edu/libraries/services/faculty/copyright\">Terms of Use</a></div>
	</div>
	<br>
    <div class=\"row\" style=\"border-top:3px solid #012D62; padding: 5px; border-bottom:3px solid #012D62\">
    	<div class=\"col-md-4\">
    		<img src=\"http://media.smith.edu/media/ereserves/html_files/images/smalllogo.gif\"/>
    	</div>
    	<div class=\"col-md-4 pull-right\">
    	<address>
    		<strong>Smith College Libraries</strong><br>
    		Northampton, MA 01063 | 413 585-2902<br>
    		<a href=\"mailto:#\">The Libraries Webmaster</a><br>
    		Copyright Smith College Libraries.  All Rights Reserved.
    	</address>
    	</div>
    	</div>	
    </div>
	</div>";
header('Content-Type: text/javascript ');

foreach(explode("\n", $htmloutput) as $line){
	echo "document.write('" . addslashes($line) . "');\n";
}
$htmloutput .= "</div>";
$htmloutput .= "</body>";
$htmloutput .= "</html>";
