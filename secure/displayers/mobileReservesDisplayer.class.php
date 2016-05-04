<?php
/*******************************************************************************
mobileReservesDisplayer.class.php
Display code for reserve pages on mobile devices.


Created by Karl Doerr, Modified by Troy Hurteau & Jason Raitz, NCSU Libraries, NC State University (libraries.opensource@ncsu.edu).

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
require_once(APPLICATION_PATH . '/common.inc.php');
require_once(APPLICATION_PATH . '/displayers/noteDisplayer.class.php');
require_once(APPLICATION_PATH . '/classes/tree.class.php');
require_once(APPLICATION_PATH . '/classes/reserves.class.php');

class mobileReservesDisplayer extends noteDisplayer {
	
	public function displayReserves($cmd, &$ci, &$tree_walker, $reserve_count, &$hidden_reserves=null, $preview_only=false) {
  		global $u;
        
		if(!($ci->course instanceof course)) {
			$ci->getPrimaryCourse();
		}
		$noReserves = ($reserve_count==0)? '<div class="focal"><p class="notruncate">There are currently no reserves available for this course.</p></div>':'';
		echo $noReserves;
		
		$currentClass = $ci->course->displayCourseNo();
		
		//if previewing, temporarily give the current user a role of student
		//Note: this process is reversed at the end of this method.
        
        // announce rss feed to capable browsers
        echo "<link rel=\"alternate\" title=\"{$ci->course->department->name} {$ci->course->courseNo} {$ci->term} {$ci->year}\" href=\"rss.php?ci={$ci->courseInstanceID}\" type=\"application/rss+xml\"/>\n";
        echo ($reserve_count>0)? '<div class="focal"><p class="notruncate">Reserves for '.$currentClass.'.</p></div>':'';
?>
					
					<ul id="firstList" data-role="listview" class="results listWithSubtext" data-inset="false"> 
			
<?php
		//begin displaying individual reserves
		//loop
		$prev_depth = 0;
		foreach($tree_walker as $leaf) {
			//close list tags if backing out of a sublist
			if($prev_depth > $tree_walker->getDepth()) {
				echo str_repeat('</ul><ul data-role="listview" class="results listWithSubtext" data-inset="false">', ($prev_depth-$tree_walker->getDepth()));
			}
			
		
			$reserve = new reserve($leaf->getID());	//init a reserve object

			//is this item hidden?
			$reserve->hidden = in_array($leaf->getID(), $hidden_reserves) ?	true : false;

			//display the info
			echo ($leaf->hasChildren()) ? '</li></ul><ul data-role="listview" class="results listWithSubtext" data-inset="false">' : '';
			self::displayReserveRowView($reserve);
			$prev_depth = $tree_walker->getDepth();
		}
		echo str_repeat('</ul></li>', ($prev_depth));	//close all lists
?>
					</ul>
<?php

		//if previewing, return user to original state
		if($preview_only) {
			$u = $curr_user;
		}
	}
	
	public static function displayReserveRowView(&$reserve, $block_style='') {
		if(!($reserve->item instanceof reserveItem)) {
			$reserve->getItem();	//pull in item info
		}
?>

			<?php self::displayReserveInfo($reserve); ?>

<?php
	}
	
	public static function displayReserveInfo(&$reserve, $meta_style='') {
		global $u, $g_mReservesViewer,$g_uniqueIdPrefix;

		if(!($reserve->item instanceof reserveItem)) {
			$reserve->getItem();	//pull in item info
		}
		
		$notes = $reserve->getNotes(true);	//get notes
		
		if($reserve->item->isPhysicalItem()){
			$viewReserveURL = "href='". $g_mReservesViewer . $g_uniqueIdPrefix . $reserve->item->getLocalControlKey() ."'";
		}
		elseif($reserve->item->isVideoItem()){
			$viewReserveURL = "";
		}
		else{
			$viewReserveURL = "href='reservesViewer.php?reserve=" . $reserve->getReserveID() ."'";
		}
		
		if(!$reserve->item->isHeading()){
			echo '<li>';
		}
		
		if($reserve->hidden): ?>
			<div class="hiddenItem">
<?php	endif; 

		if(!$reserve->item->isHeading()){
?> 
		<a <?php print($viewReserveURL); ?>" target="_blank" class="itemTitle">
<?php
		}
		
		#=== heading ===#
		
		//only show basic info about a heading
		if($reserve->item->isHeading()):
?>
		</ul><ul data-role="listview" class="results listWithSubtext" data-inset="false">
		<li data-role="list-divider" class="ui-bar-a">
<?php
			echo $reserve->item->getTitle();
			//show notes
			noteDisplayer::displayNotes($notes);

		
			if($reserve->hidden): ?>
			</div>
<?php		endif; 
			return;	//nothing else to show, so just return
		endif;
		
		#=== reserve ===#
		
		//for non-headings show all the meta info
		$title = $reserve->item->getTitle();
		$author = $reserve->item->getAuthor();
		$url = $reserve->item->getURL();
		$performer = $reserve->item->getPerformer();
		$volTitle = $reserve->item->getVolumeTitle();
		$volEdition = $reserve->item->getVolumeEdition();
		$pagesTimes = $reserve->item->getPagesTimes();
		$source = $reserve->item->getSource();
		$itemIcon = $reserve->item->getItemIcon();
		
		
		//for physical items, pull in some other info
		if($reserve->item->isPhysicalItem()) {
			$reserve->item->getPhysicalCopy();	//get physical copy info
			$callNumber = $reserve->item->physicalCopy->getCallNumber();
			//get home library/reserve desk
			$lib = new library($reserve->item->getHomeLibraryID());
			$reserveDesk = $lib->getReserveDesk();
		}
?>
		<div class="thumb"><img src="<?php print($itemIcon); ?>" alt="icon"></div>
		
<?php	if($reserve->item->isPhysicalItem()): ?>	
		
			<h3 class="itemTitleNoLink notruncate"><?php print($title); ?></h3>
			<h4 class="itemAuthor notruncate"><span class="smallprint"><?php print($author); ?></span></h4>
			<h4 class="itemMeta notruncate"><?php print($callNumber); ?></h4>
			<p class="itemMetaPre notruncate">On Reserve at:<span class="itemMeta smallprint"><?php print($reserveDesk); ?></span></p>
			
<?php	else: ?>
			<?php if ($reserve->getStatus() == 'ACTIVE' || $reserve->getStatus() == 'INACTIVE'): ?>
				<h3 class="title notruncate"><?php print($title); ?></h3>
			<?php	else: ?>
				<h3 class="itemTitleNoLink notruncate"><?php print($title); ?></h3>
			<?php	endif; ?>
			<h4 class="itemAuthor notruncate"><span class="smallprint"><?php print($author); ?></span></h4>
			
<?php	endif; ?>

<?php	if($performer): ?>

       		<p class="itemMetaPre notruncate">Performed by: <span class="itemMeta smallprint"><?php print($performer); ?></span></p>
       		
<?php	endif; ?>
<?php	if($volTitle): ?>

       		<p class="itemMetaPre notruncate">From: <span class="itemMeta smallprint"><?php print($volTitle); ?></span></p>
       		
<?php	endif; ?>
<?php	if($volEdition): ?>

       		<p class="itemMetaPre notruncate">Volume/Edition: <span class="itemMeta smallprint"><?php print($volEdition); ?></span></p>
       		
<?php	endif; ?>
<?php	if($pagesTimes): ?>

       		<p class="itemMetaPre" notruncate>Pages/Time: <span class="itemMeta smallprint"><?php print($pagesTimes); ?></span></p>
       		
<?php	endif; ?>
<?php	if($source): ?>

       		<p class="itemMetaPre notruncate">Source/Year: <span class="itemMeta smallprint"><?php print($source); ?></span></p>
       		
<?php	endif; ?>

<?php
		//show notes
		noteDisplayer::displayNotes($notes);
			
		//show additional info
		if(!empty($reserve->additional_info)) {
			echo $reserve->additional_info;
		}
?>

<?php 
if(!$reserve->item->isHeading()){
?>		
		</a>
<?php	if($reserve->hidden): ?>
			</div>
<?php	endif; ?>
		</li>
<?php }?>
<?php
	}
}