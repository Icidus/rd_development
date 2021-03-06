<?php
/*******************************************************************************
itemDisplayer.class.php

Created by Dmitriy Panteleyev (dpantel@emory.edu)
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

require_once(APPLICATION_PATH . '/classes/copyright.class.php');
require_once(APPLICATION_PATH . '/classes/reserves.class.php');
require_once(APPLICATION_PATH . '/displayers/noteDisplayer.class.php');
require_once(APPLICATION_PATH . '/displayers/copyrightDisplayer.class.php');
require_once(APPLICATION_PATH . '/managers/ajaxManager.class.php');

class itemDisplayer extends noteDisplayer {

	/**
	 * @return void
	 * @param reserveItem $item reserveItem object
	 * @param reserve $reserve (optional) reserve object
	 * @param array $dub_array (optional) array of information pertaining to duplicating an item. currently 'dubReserve' flag and 'selected_instr'
	 * @desc Displays form for editing item information (optionally: reserve information)
	 */
	function displayEditItem($item, $reserve=null, $dub_array=null) {
		global $g_permission;						
		$u = Rd_Registry::get("root:userInterface");
		//determine if editing a reserve
		if(!empty($reserve) && ($reserve instanceof reserve)) {
			$edit_reserve = true;
			$edit_item_href = 'reserveID='.$reserve->getReserveID();			
		}
		else {
			$edit_reserve = false;
			$edit_item_href = 'itemID='.$item->getItemID();
		}
		
		//style the tab
		$tab_styles = array('meta'=>'', 'history'=>'', 'copyright'=>'', 'status'=>'');
		switch((array_key_exists('tab', $_REQUEST) ? $_REQUEST['tab'] : '')) {
			case 'history':
				$tab_styles['history'] = 'class="current"';
			break;
			case 'status':
				$tab_styles['status'] = 'class="current"';
			break;

#########################################
#	HIDE COPYRIGHT UNTIL FURTHER NOTICE #			
#########################################	
#			case 'copyright':
#				$tab_styles['copyright'] = 'class="current"';
#			break;
#########################################
			
			default:
				$tab_styles['meta'] = 'class="current"';
			break;
		}
		
#########################################
#	HIDE COPYRIGHT UNTIL FURTHER NOTICE #			
#########################################			
#		//check for a pending copyright-review
#		$copyright = new Copyright($item->getItemID());
#		$copyright_alert = '';
#		if(($copyright->getStatus() != 'APPROVED') && ($copyright->getStatus() != 'DENIED')) {
#			$copyright_alert = '<span class="alert">! pending review !</span>';
#		}
#########################################
?>
		<div id="alertMsg" align="center" class="failedText"></div>
        <p />  
        
<?php	if($edit_reserve): ?>
		<div style="text-align:right; font-weight:bold;"><a href="index.php?cmd=editClass&amp;ci=<?php print($reserve->getCourseInstanceID()); ?>">Return to Class</a></div>
<?php	else: ?>
		<div style="text-align:right; font-weight:bold;"><a href="index.php?cmd=doSearch&amp;search=<?php print(urlencode(array_key_exists('search', $_REQUEST) ? $_REQUEST['search'] : '')); ?>">Return to Search Results</a></div>
<?php	endif; ?>

		<div class="contentTabs">
			<ul>
				<li <?php print($tab_styles['meta']); ?>><a href="index.php?cmd=editItem&<?php print($edit_item_href); ?>&search=<?php print(array_key_exists('search', $_REQUEST) ? $_REQUEST['search'] : ''); ?>">Item Info</a></li>
<?php		if($u->getRole() >= $g_permission['staff']){ ?>
				<li <?php print($tab_styles['history']); ?>><a href="index.php?cmd=editItem&<?php print($edit_item_href); ?>&tab=history&search=<?php print(urlencode(array_key_exists('search', $_REQUEST) ? $_REQUEST['search'] : '')); ?>">History</a></li>
				<li <?php print($tab_styles['status']); ?>><a href="index.php?cmd=editItem&<?php print($edit_item_href); ?>&tab=status&search=<?php print(urlencode(array_key_exists('search', $_REQUEST) ? $_REQUEST['search'] : '')); ?>">Status</a></li>
<?php
#########################################
#	HIDE COPYRIGHT UNTIL FURTHER NOTICE #
#	(remove spaces b/n <, >, and ?)		#
#########################################

#				<li < ?=$tab_styles['copyright']? >><a href="index.php?cmd=editItem&amp;< ?=$edit_item_href? >&amp;tab=copyright">Copyright < ?=$copyright_alert? ></a></li>
#########################################
?>

<?php } ?>
			</ul>
		</div>
		<div class="clear"></div>
		
<?php
		//switch screens
		//only allow non-default tab for staff and better
		$tab = isset($_REQUEST['tab']) ? $_REQUEST['tab'] : null;
		$tab = ($u->getRole() >= $g_permission['staff']) ? $tab : null;				
		switch($tab) {
			case 'history':
				self::displayItemHistory($item);
			break;
			case 'status':
				self::displayItemStatus($item, $reserve);
			break;
			
#########################################
#	HIDE COPYRIGHT UNTIL FURTHER NOTICE #			
#########################################			
#			case 'copyright':
#				self::displayEditItemCopyright($item);
#			break;
#########################################
			
			default:
				if ($reserve instanceof Reserve)
				{
					$status = $reserve->status;
				} elseif ($item instanceof reserveItem ) {
					$status = $item->status;
				} else {
					$status = 'DENIED';
				}
				
				if ($status != 'DENIED' || $u->getRole() >= $g_permission['staff'])
				{
					self::displayEditItemMeta($item, $reserve, $dub_array);
				} else {
					echo "Access to this item has be denied.  Please contact your reserves desk for assistance.";
				}
			break;
		}
?>
<?php
	}
	
	
	/**
	 * @return void
	 * @param reserveItem object $reserveItem
	 * @desc Displays the edit-item-source block
	 */
	static function displayEditItemSource(&$reserveItem) {
		global $g_permission;
		$u = Rd_Registry::get('root:userInterface');

		
		//editing an electronic item - show URL/upload fields
		if($reserveItem->getItemGroup() == 'ELECTRONIC'):
?>
		<div class="headingCell1">ITEM SOURCE</div>
		<div id="item_source" style="padding:8px 8px 12px 8px;">
			<script language="JavaScript">
				var currentItemSourceOptionID;
				
				function toggleItemSourceOptions(option_id) {
					if(document.getElementById(currentItemSourceOptionID)) {
						document.getElementById(currentItemSourceOptionID).style.display = 'none';
					}
					if(document.getElementById(option_id)) {
						document.getElementById(option_id).style.display = '';
					}
					
					currentItemSourceOptionID = option_id;
				}
			</script>		

			<div style="overflow:auto;" class="strong">
				Current URL <small>[<a href="reservesViewer.php?item=<?php print($reserveItem->getItemID());?>" target="_blank">Preview</a>]</small>: 
<?php		if($reserveItem->isLocalFile()): //local file ?>
				Local File 
<?php			if($u->getRole() >= $g_permission['staff']): //only show local path to staff or greater ?>
				 &ndash; <em><?php print($reserveItem->getURL()); ?></em>
<?php			endif; ?>
<?php		else: //remote file - show link to everyone ?>
				<em><?php print($reserveItem->getURL());?></em>
<?php		endif; ?>
			</div>
			<small>
				Please note that items stored on the ReservesDirect server are access-restricted; use the Preview link to view the item.
				<br />
				To overwrite this URL, use the options below.
			</small>
			<p />
			<div>
				<input type="radio" name="documentType" checked="checked" onclick="toggleItemSourceOptions('');" /> Maintain current URL &nbsp;
				<input type="radio" name="documentType" value="DOCUMENT" onclick="toggleItemSourceOptions('item_source_upload');" /> Upload new file &nbsp;
				<input type="radio" name="documentType" value="URL" onclick="toggleItemSourceOptions('item_source_link');" /> Change URL
			</div>
			<div style="margin-left:40px;">
				<div id="item_source_upload" style="display:none;">
					<input type="file" name="userFile" size="50" />
				</div>
				<div id="item_source_link" style="display:none;">
					<input name="url" type="text" size="50" />
					<input type="button" onclick="openNewWindow(this.form.url.value, 500);" value="Preview" />
				</div>
			</div>

<?php	if($u->getRole() >= $g_permission['staff']): //only show status to staff or greater ?>
			<?php
				//$status = $reserveItem->getStatus();
				//$$status = " checked='CHECKED' ";
				
				switch($reserveItem->getStatus()) {
					case 'ACTIVE':
						$item_status_active = "checked='CHECKED'";
						$item_status_denied = '';
						break;
					case 'DENIED':
						$item_status_active = '';
						$item_status_denied = "checked='CHECKED'";
				}
				
			?>
			<!-- deleted from 2.4.8 version of this file. 
			<div style="overflow:auto;">
				<p>
					<div class="strong">Item Status</div>
					<div>
						<input type="radio" name="item_status" <?php print($item_status_active); ?> value="ACTIVE"/> Activate for all Classes
						<input type="radio" name="item_status" <?php print($item_status_denied); ?> value="DENIED"/> Deny use for all Classes
					</div>
				</p>
			</div> -->
<?php	endif; ?>			
		</div>	
<?php	
		//editing a physical item - show library, etc.
		//only allow staff or better to edit this info
		elseif($reserveItem->isPhysicalItem() && ($u->getRole() >= $g_permission['staff'])):	 
?>
		<div class="headingCell1">ITEM SOURCE</div>
		<div id="item_source" style="padding:8px 8px 12px 8px;">
	    	<table border="0" cellpadding="2" cellspacing="0">	
	    		<tr>
	    			<td align="right">
	    				Reserve Desk:
	    			</td>
	    			<td>
	    				<select name="home_library">
<?php
			foreach($u->getLibraries() as $lib):
				$selected_lib = ($reserveItem->getHomeLibraryID() == $lib->getLibraryID()) ? 'selected="selected"' : '';
?>
							<option value="<?php print($lib->getLibraryID()); ?>"<?php print($selected_lib); ?>><?php print($lib->getLibrary()); ?></option>			
<?php		endforeach; ?>
	    				</select>
	    			</td>
	    		</tr>
<?php
			//details from the physical copy table (barcode/call num)
			if($reserveItem->getPhysicalCopy()):
?>
	    		<tr>		
					<td align="right">
						<font color="#FF0000">*</font>&nbsp;Barcode:
					</td>
					<td>
						<input name="barcode" type="text" id="barcode" size="30" value="<?php print($reserveItem->physicalCopy->getBarcode()); ?>" />
	
					</td>
				</tr>
				<tr>		
					<td align="right">
						Call Number:
					</td>
					<td>
						<input name="call_num" type="text" id="call_num" size="30" value="<?php print($reserveItem->physicalCopy->getCallNumber()); ?>" />
					</td>				
				</tr>
<?php			endif;	//end physical copy info ?>

			</table>
		</div>
<?php		
		endif; //end physical item block
	}	//displayEditItemSource()
	
	
	/**
	 * @return void
	 * @param Reserve object $reserve
	 * @desc Displays the edit-item-reserve-details block
	 */
	static function displayEditItemReserveDetails(&$reserve) {
		global $calendar, $g_permission;
		$u = Rd_Registry::get('root:userInterface');
		switch($reserve->getStatus()) {
			case 'ACTIVE':
				$reserve_status_active = 'checked="CHECKED"';
				$reserve_status_inactive = '';
				$reserve_status_denied = '';
				$reserve_block_vis = '';
				break;
			case 'INACTIVE':
				$reserve_status_active = '';
				$reserve_status_inactive = 'checked="CHECKED"';
				$reserve_status_denied = '';
				$reserve_block_vis = ' display:none;';
				break;
			case 'DENIED':
				$reserve_status_active = '';
				$reserve_status_inactive = '';
				$reserve_status_denied = 'checked="CHECKED"';
				$reserve_block_vis = ' display:none;';
		}
		
		//dates
		$reserve_activation_date = $reserve->getActivationDate();
		$reserve_expiration_date = $reserve->getExpirationDate();
		
		//set reset dates to course dates
		$ci = new courseInstance($reserve->getCourseInstanceID());
		$course_activation_date = $ci->getActivationDate();	
		$course_expiration_date = $ci->getExpirationDate();

		//determine the parent heading
		$parent_heading_id = $reserve->getParent();		
		if(empty($parent_heading_id)) {
			$parent_heading_id = 'root';	//this will pre-select the main list
		}
?>
		<script language="JavaScript">
		//<!--			
			//shows/hides activation/expiration date form elements
			function toggleDates() {
				if(document.getElementById('reserve_status_active').checked) {
					document.getElementById('reserve_dates_block').style.display = '';
				}
				else {
					document.getElementById('reserve_dates_block').style.display = 'none';
				}
			}
			
			//resets reserve dates
			function resetDates(from, to) {
				document.getElementById('reserve_activation_date').value = from;
				document.getElementById('reserve_expiration_date').value = to;
			}
		//-->
		</script>
		
		<div class="headingCell1">RESERVE DETAILS</div>
		<div id="reserve_details" style="padding:8px 8px 12px 8px;">
<?php	if($reserve->getStatus()=='DENIED ALL'): ?>	
			<div>
				<strong>Current Status:</strong>&nbsp;<span class="copyright_denied">Item Access Denied</span>
				<br />
				Access to this item has be denied for All Classes.  You must reactive the item status before making changes. 
				<input type="hidden" name="reserve_status" value="<?php print($reserve->status); ?>"/>
			</div>
            <?php if($u->getRole() >= $g_permission['staff']): ?>
            <p>
				<div>
                        <input type="radio" name="item_status" <?php print($reserve_status_active); ?> value="ACTIVE"/> <span class="active">Activate for all Classes</span>
                        <br /> <input type="radio" name="item_status" <?php print($reserve_status_denied); ?> value="DENIED"/> <span class="inactive">Deny use for all Classes</span>
				</div>
			</p>
            <?php endif; ?>
<?php else: ?>
	<?php	if (($reserve->getStatus() != 'DENIED' && $reserve->getStatus() != 'DENIED ALL') || $u->getRole() >= $g_permission['staff']): ?>				
		<?php	if(!in_array($reserve->getStatus(), array('ACTIVE', 'INACTIVE', 'DENIED', 'DENIED ALL'))): ?>
	
				<div>
					<strong>Current Status:</strong>&nbsp;<span class="inProcess"><?php print($reserve->getStatus()); ?></span>
					<br />
					Please contact your Reserves staff to inquire about the status of this reserve.
					<input type="hidden" name="reserve_status" value="IN PROCESS" />
				</div>
							
		<?php	else: ?>
				<div style="float:left; width:30%;">
					<strong>Set Status:</strong>
					<br />

					<div style="margin-left:10px; padding:3px;">
						<input type="radio" name="reserve_status" id="reserve_status_active" value="ACTIVE" onChange="toggleDates();" <?php print($reserve_status_active); ?> />&nbsp;<span class="active">ACTIVE</span>
						<input type="radio" name="reserve_status" id="reserve_status_inactive" value="INACTIVE" onChange="toggleDates();" <?php print($reserve_status_inactive); ?> />&nbsp;<span class="inactive">INACTIVE</span>					
			<?php	if ($u->getRole() >= $g_permission['staff']): ?>
						<br/><input type="radio" name="reserve_status" id="reserve_status_denied" value="DENIED" onChange="toggleDates();" <?php print($reserve_status_denied); ?> />&nbsp;<span class="copyright_denied">DENY ACCESS FOR THIS CLASS ONLY</span>
                        <div style="overflow:auto;">
				<p>
                    <div class="strong">Item Status</div>
                    <div>
                        <input type="radio" name="item_status" <?php print($reserve_status_active); ?> value="ACTIVE"/> <span class="active">Activate for all Classes</span>
                        <br /> <input type="radio" name="item_status" <?php print($reserve_status_denied); ?> value="DENIED"/> <span class="inactive">Deny use for all Classes</span>
                    </div>
				</p>
			</div>
			<?php 	endif; ?>
					</div>
		<?php 	endif; #if in process?>
				</div>
							
				<div id="reserve_dates_block" style="float:left;<?php print($reserve_block_vis); ?>">
					<strong>Active Dates</strong> (YYYY-MM-DD) &nbsp;&nbsp; [<a href="#" name="reset_dates" onclick="resetDates('<?php print($course_activation_date); ?>', '<?php print($course_expiration_date); ?>'); return false;">Reset dates</a>]
					<br />
					<div style="margin-left:10px;">
						From:&nbsp;<input type="text" id="reserve_activation_date" name="reserve_activation_date" size="10" maxlength="10" value="<?php print($reserve_activation_date); ?>" /> <?php print($calendar->getWidgetAndTrigger('reserve_activation_date', $reserve_activation_date)); ?>
						To:&nbsp;<input type="text" id="reserve_expiration_date" name="reserve_expiration_date" size="10" maxlength="10" value="<?php print($reserve_expiration_date);?>" />  <?php print($calendar->getWidgetAndTrigger('reserve_expiration_date', $reserve_expiration_date)); ?>
					</div>
				</div>
							
			<div style="clear:left; padding-top:10px;">
				<strong>Current Heading:</strong> 
				<?php self::displayHeadingSelect($ci, $parent_heading_id); ?>
			</div>		
		</div>					
	<?php	endif; ?>	
<?php	endif; ?>		
<?php
	}
	
	
	/**
	* @return void
	* @param reserveItem $item reserveItem object
	* @desc Displays the edit-item-item-details block
	*/	
	static function displayEditItemItemDetails($item) {
		global $g_permission, $g_catalogName;
		$u = Rd_Registry::get('root:userInterface');
		//private user
		$privateUserID = $item->getPrivateUserID();
		if( !is_null($privateUserID) && $privateUserID != 0 ) {
			$item->getPrivateUser();
			$privateUser = $item->privateUser->getName(). ' ('.$item->privateUser->getUsername().')';
		} else {
			$privateUserID = NULL;
		}
?>
        
		<script language="JavaScript">
		//<!--
			//shows/hides personal item elements; marks them as required or not
			function togglePersonal(enable) {
				if(enable) {
					document.getElementById('personal_item_yes').checked = true;
					document.getElementById('personal_item_owner_block').style.display ='';
					togglePersonalOwnerSearch();
				}
				else {
					document.getElementById('personal_item_no').checked = true;
					document.getElementById('personal_item_owner_block').style.display ='none';
				}
			}
		
			//shows/hides personal item owner search fields
			function togglePersonalOwnerSearch() {
				//if no personal owner set, 
				if(document.getElementById('personal_item_owner_curr').checked) {
					document.getElementById('personal_item_owner_search').style.visibility = 'hidden';
				}
				else if(document.getElementById('personal_item_owner_new').checked) {
					document.getElementById('personal_item_owner_search').style.visibility = 'visible';
				}	
			}
		//-->
		</script>
				
		<div class="headingCell1">ITEM DETAILS</div>
		<div id="item_details" style="padding:8px 8px 12px 8px;">
			<table border="0" cellpadding="2" cellspacing="0">		
	    		<tr>
	    			<td align="right">
	    				<font color="#FF0000">*</font>&nbsp;Document Title:
	    			</td>
	    			<td>
	    				<input name="title" type="text" id="title" size="50" value="<?php print($item->getTitle()); ?>">
	    			</td>
	    		</tr>
	    		<tr>
	    			<td align="right">
	    				Author/Composer:
	    			</td>
	    			<td>
	    				<input name="author" type="text" id="author" size="50" value="<?php print($item->getAuthor()); ?>">
	    			</td>
	    		</tr>
	    		<tr>
	    			<td align="right">
	    				Performer:
	    			</td>
	    			<td>
	    				<input name="performer" type="text" id="performer" size="50" value="<?php print($item->getPerformer()); ?>">
	    			</td>
	    		</tr>
	    		<tr>
	    			<td align="right">
	    				Document Type Icon:
	    			</td>
	    			<td>
	    				<select name="selectedDocIcon" onChange="document.iconImg.src = this[this.selectedIndex].value;">
<?php
		foreach($u->getAllDocTypeIcons() as $icon):
			$selected = ($item->getItemIcon() == $icon['helper_app_icon']) ? ' selected="selected"' : '';
?>
							<option value="<?php print($icon['helper_app_icon']); ?>"<?php print($selected); ?>><?php print($icon['helper_app_name']); ?></option>
<?php	endforeach; ?>
						</select>
						<img name="iconImg" width="24" height="20" src="<?php print($item->getItemIcon()); ?>" />
	    			</td>
	    		</tr>
	    		<tr>
	    			<td align="right">
	    				Book/Journal/Work Title:
	    			</td>
	    			<td>
	    				<input name="volumeTitle" type="text" id="volumeTitle" size="50" value="<?php print($item->getVolumeTitle()); ?>">
	    			</td>
	    		</tr>
	    		<tr>
	    			<td align="right">
	    				Volume/Edition:
	    			</td>
	    			<td>
	    				<input name="volumeEdition" type="text" id="volumeEdition" size="50" value="<?php print($item->getVolumeEdition()); ?>">
	    			</td>
	    		</tr>
	    		<tr>
	    			<td align="right">
	    				Pages/Time
	    			</td>
	    			<td>
	    				<input name="pagesTimes" type="text" id="pagesTimes" size="50" value="<?php print($item->getPagesTimes()); ?>">
	    			</td>
	    			<?php if ($item->getItemGroup() == 'ELECTRONIC') { echo "<td><small>pp. 336-371 and pp. 399-442 (78 of 719)</small></td>"; } ?>	    			
	    		</tr>
	    		<tr>
	    			<td align="right">
	    				Source/Year:
	    			</td>
	    			<td>
	    				<input name="source" type="text" id="source" size="50" value="<?php print($item->getSource()); ?>">
	    			</td>
	    		</tr>
				<tr><td align="right">ISBN:</td><td><input type="text" size="15" maxlength="13" value="<?php print($item->getISBN()); ?>" name="ISBN" /></td></tr>
                <tr><td align="right">OCLC:</td><td><input type="text" size="15" maxlength="9"  value="<?php print($item->getOCLC()); ?>" name="OCLC" /></td></tr>
                <tr><td align="right">ISSN:</td><td><input type="text" size="15" maxlength="8"  value="<?php print($item->getISSN()); ?>" name="ISSN" /></td></tr>

<?php	
		if($item->isPhysicalItem()){
?>
				<tr>
					<td align="right"><?php print($g_catalogName); ?> Control Number:</td>
					<td><input type="text" name="local_control_key" size="15" value="<?php print($item->getLocalControlKey()); ?>" /></td>
				</tr>
<?php   } else { ?>
				<tr>
					<td align="right">Barcode:</td>
					<td><input type="text" name="local_control_key" size="15" value="<?php print($item->getLocalControlKey()); ?>" /></td>
				</tr>
<?php	} ?>
	    		
<?php	
		//only allow choosing personal-item owner to staff or better
		if($u->getRole() >= $g_permission['staff']) {
?>

				<tr id="personal_item_row" valign="top">
					<td align="right">
						Personal Copy Owner:
					</td>
					<td>
						<div id="personal_item_choice" style="background-color:#EEDDCC;">
							<input type="radio" name="personal_item" id="personal_item_no" value="no" onChange="togglePersonal(0);" /> No
							&nbsp;&nbsp;
							<input type="radio" name="personal_item" id="personal_item_yes" value="yes" onChange="togglePersonal(1);" /> Yes
						</div>
						<div id="personal_item_owner_block" style="padding:2px 3px 15px; background-color:#DFD8C6; border-top:1px dashed #999999;">
<?php
			//if there is an existing owner, give a choice of picking new one
			if(isset($privateUser)){
?>
							<input type="radio" name="personal_item_owner" id="personal_item_owner_curr" value="old" checked="checked" onChange="togglePersonalOwnerSearch();" /> Current - <strong><?php print($privateUser); ?></strong>
							<br />
							<input type="radio" name="personal_item_owner" id="personal_item_owner_new" value="new" onChange="togglePersonalOwnerSearch();" /> New &nbsp;
<?php
			} else {	//if not, then just assume we are searching for a new one
?>
							<input type="hidden" name="personal_item_owner" id="personal_item_owner_new" value="new" />
<?php
			};
?>
							<span id="personal_item_owner_search">
<?php
				//ajax user lookup
				$mgr = new ajaxManager('lookupUser', null, null, null, null, false, array('min_user_role'=>3, 'field_id'=>'selected_owner'));
				$mgr->display();		
?>
							</span>
						</div>
					</td>
				</tr>
<?php	} ?>
			</table>
		</div>
		
<?php	
		//only allow choosing personal-item owner to staff or better
		if ($u->getRole() >= $g_permission['staff']) {
?>		
		<script language="JavaScript">
			//set up some fields on load
			if( document.getElementById('personal_item_owner_curr') != null ) {	//if there is already a private owner
				//select current owner
				document.getElementById('personal_item_owner_curr').checked = true;
				//show private owner block
				togglePersonal(1);			
			}
			else {
				//default to no private owner
				togglePersonal(0);
			}
		</script>
<?php
		}
	}
	
	
	/**
	 * @return void
	 * @param reserveItem $item reserveItem object
	 * @param reserves $reserve (optional) reserve object
	 * @desc Displays the edit-item-notes block
	 */	
	static function displayEditItemNotes($item, $reserve=null) {
		global $g_permission;
		$u = Rd_Registry::get('root:userInterface');
		
		//item notes
		$notes = $item->getNotes();
		//referrer obj for deleting notes
		$note_ref = 'itemID='.$item->getItemID();
		
		//reserve notes - only applies if we are editing a reserve (item instance linked to a course instance)
		if( !empty($reserve) && ($reserve instanceof reserve) ) {	//we are editing reserve info
			//notes
			$notes = $reserve->getNotes(true);
			//override referrer obj
			$note_ref = 'reserveID='.$reserve->getReserveID();			
		}
?>
		<div class="headingCell1">NOTES</div>
		<div id="item_notes" style="padding:8px 8px 12px 8px;">
			<?php self::displayEditNotes($notes, $note_ref); ?>
			
<?php
		//only allow adding notes to reserves (not items) unless edited by staff
		if(($u->getRole() >= $g_permission['staff']) || ($reserve instanceof reserve)):
?>
			<div id="add_note" style="text-align:center; padding:10px; border-top:1px solid #333333;">
				<?php self::displayAddNoteButton($note_ref); //display "Add Note" button ?>			
			</div>
<?php	endif; ?>

		</div>
<?php
	}
	

	/**
 	 * @return void
	 * @param reserveItem $item reserveItem object
	 * @param reserves $reserve (optional) reserve object
	 * @desc Displays the edit-item-notes block
	 */	
	static function displayEditItemNotesAJAX($item, $reserve=null) {
		global $g_permission, $g_notetype;
		
		$u = Rd_Registry::get('root:userInterface');
		
		//reserve notes - only applies if we are editing a reserve (item instance linked to a course instance)
		if( !empty($reserve) && ($reserve instanceof reserve) ) {	//we are editing reserve info
			$obj_type = 'reserve';
			$id = $reserve->getReserveID();			
		}
		else {
			$obj_type = 'item';
			$id = $item->getItemID();
		}
		
		//fetch notes
		$notes = noteManager::fetchNotesForObj($obj_type, $id, true);
		
		//only allow adding notes to reserves (not items) unless edited by staff
		$include_addnote_button = (($u->getRole() >= $g_permission['staff']) || ($obj_type=='reserve')) ? true : false;
?>

		<script language="JavaScript1.2" src="public/javascript/basicAJAX.js"></script>
		<script language="JavaScript1.2" src="public/javascript/notes_ajax.js"></script>

		<div class="headingCell1">NOTES</div>
		<div style="padding:8px 8px 12px 8px;">
			<?php self::displayNotesBlockAJAX($notes, $obj_type, $id, $include_addnote_button); ?>
		</div>
<?php
	}
	
	
	/**
	 * @return void
	 * @param reserveItem $item reserveItem object
	 * @param reserves $reserve (optional) reserve object
	 * @param array $dub_array (optional) array of information pertaining to duplicating an item. currently 'dubReserve' flag and 'selected_instr'
	 * @desc Displays form for editing item information (optionally: reserve information)
	 */	
	function displayEditItemMeta($item, $reserve=null, $dub_array=null) {
		global $g_permission;
		$u = Rd_Registry::get('root:userInterface');
		
		//determine if editing a reserve
		if(!empty($reserve) && ($reserve instanceof reserve)) {	//valid reserve obj
			$edit_reserve = true;
			//make sure that the item is for this reserve
			$reserve->getItem();
			$item = $reserve->item;
		}
		else $edit_reserve = false;
		
		//build the form
?>
		<script language="JavaScript">
		//<!--
			function submitForm() {
				if(document.getElementById('item_form')) {
					document.getElementById('item_form').submit();
				}
			}
			
			function validateForm(frm,physicalCopy) {			
				var alertMsg = "";

				if (frm.title.value == "")
					alertMsg = alertMsg + "Title is required.<br>";
				
				if (physicalCopy) {
					//make sure this physical copy is supposed to have a barcode
					//	if it is, there will be an input element for it in the form
					if( (document.getElementById('barcode') != null) && (document.getElementById('barcode').value == '') )
						alertMsg = alertMsg + "Barcode is required.<br />";
				}
				else if((frm.documentType.value == "DOCUMENT") && (frm.userFile.value == "")) {
					alertMsg = alertMsg + "You must choose a file to upload.<br />";
				}
				else if((frm.documentType.value == "URL") && (frm.url.value == "")) {
					alertMsg = alertMsg + "URL is required.<br />";
				}
				
				if (!alertMsg == "") { 
					document.getElementById('alertMsg').innerHTML = alertMsg;
					return false;
				}					
			}
		//-->
		</script>
		
<?php	if($item->getItemGroup() == 'ELECTRONIC'): ?>
		<form id="item_form" name="item_form" enctype="multipart/form-data" action="index.php?cmd=editItem" method="post" onSubmit="return validateForm(this,false);">		
<?php	else: ?>
		<form id="item_form" name="item_form" action="index.php?cmd=editItem" method="post" onSubmit="return validateForm(this,true);">		
<?php	endif; ?>

			<input type="hidden" name="submit_edit_item_meta" value="submit" />
			<input type="hidden" name="itemID" value="<?php print($item->getItemID()); ?>" />
			<?php self::displayHiddenFields($dub_array); //add duplication info as hidden fields ?>	
<?php	if($edit_reserve): ?>
			<input type="hidden" name="reserveID" value="<?php print($reserve->getReserveID()); ?>" />	
<?php	endif; ?>
			
			<div id="item_meta" class="displayArea">		
<?php
		//show reserve details block
		if($edit_reserve) {
			self::displayEditItemReserveDetails($reserve);
		}
		
		//show item source
		self::displayEditItemSource($item);
		
		//show item details
		self::displayEditItemItemDetails($item);
?>
		</form>		
<?php
		
		//show item/reserve notes
		if($u->getRole() >= $g_permission['staff']) {	//show ajax to staff and above
			self::displayEditItemNotesAJAX($item, $reserve);
		}
		else {	//show normal notes to everyone else
			self::displayEditItemNotes($item, $reserve);
		}
?>
		</div>

		<strong style="color:#FF0000;">*</strong> <span class="helperText">= required fields</span>
		<p />
		<div style="padding:10px; text-align:center;">
			<input type="button" name="submit_edit_item_meta" value="Save Changes" onclick="javascript: submitForm();">
		</div>
<?php		
	}
	
	
	/**
	 * @return void
	 * @param reserveItem object $item
	 * @desc Displays item history screen
	 */
	function displayItemHistory($item) {
		//get dates and terms
		$creation_date = date('F d, Y', strtotime($item->getCreationDate())); 
		$creation_term = new term();
		$creation_term = $creation_term->getTermByDate($item->getCreationDate()) ? $creation_term->getTerm() : 'n/a';
		$modification_date = date('F d, Y', strtotime($item->getLastModifiedDate()));
		$modification_term = new term();
		$modification_term = $modification_term->getTermByDate($item->getLastModifiedDate()) ? $modification_term->getTerm() : 'n/a';
		
		//get creator (if electronic item), or home library (if physical item)
		if($item->isPhysicalItem()) {	//physical
			$home_lib_id = $item->getHomeLibraryID();
			if(!empty($home_lib_id)) {
				$home_lib = new library($home_lib_id);
				$owner = $home_lib->getLibrary();
			}
			else {
				$owner = 'n/a';
			}
			$owner_label = 'Owning Library';			
		}
		else {	//electronic
			$item_audit = new itemAudit($item->getItemID());
			$creator_id = $item_audit->getAddedBy();
			if(!empty($creator_id)) {
				$creator = new user($creator_id);
				$owner = $creator->getName(false).' ('.$creator->getUsername().') &ndash; '.$creator->getUserClass();
			}
			else {
				$owner = 'n/a';
			}			
			$owner_label = 'Created By';
			
		}
		
		//get reserve history
		$classes = $item->getAllCourseInstances();
		
		//get history totals
		
		//total # of classes
		$total_classes = sizeof($classes);		
		//total # of instructors
		$instructors = array();
		foreach($classes as $ci) {
			$ci->getInstructors();
			foreach($ci->instructorIDs as $instrID) {
				$instructors[] = $instrID;
			}
		}
		$instructors = array_unique($instructors);
		$total_instructors = sizeof($instructors);
?>
	<div class="displayArea">
		<div class="headingCell1">ITEM ORIGIN</div>
		<div id="item_origin" style="padding:8px 8px 12px 8px;">
			<div style="float:left; width:30%;">
				<strong>Item Created On:</strong>
				<br />
				<?php print($creation_date); ?> (<?php print($creation_term); ?>)
			</div>
			<div style="float:left; width:30%;">
				<strong><?php print($owner_label); ?>:</strong>
				<br />
				<?php print($owner); ?>
			</div>
			<div style="float:left; width:30%;">			
				<strong>Last Modified:</strong>
				<br />
				<?php print($modification_date); ?> (<?php print($modification_term); ?>)	
			</div>
			<div class="clear"></div>
		</div>
		<div class="headingCell1">CLASS HISTORY</div>
		<div id="item_history">
			<div style="padding:8px; border-bottom:1px solid #333333;">
				<strong>Total # of classes:</strong> <?php print($total_classes); ?>
				<br />
				<strong>Total # of instructors:</strong> <?php print($total_instructors); ?>
<!--				
				<br />
				<strong>Total times viewed (all semesters):</strong> ###
-->
			</div>
			
			<table width="100%" border="0" cellpadding="4" cellspacing="0">
				<tr class="headingCell2" align="left" style="text-align:left;">
					<td width="5%">&nbsp;</td>
					<td width="15%">Term</td>
					<td width="15%">Course Number</td>
					<td width="25%">Course Name</td>
					<td width="15%">Instructor</td>					
					<td width="15%">Status</td>
					<td width="10%">&nbsp;</td>					
				</tr>
<?php
			$rowClass = 'evenRow';
			//loop through the courses
			foreach($classes as $ci):
				$ci->getPrimaryCourse();	//fetch the course object
				$ci->getInstructors();	//get a list of instructors
				$rowClass = ($rowClass=='evenRow') ? 'oddRow' : 'evenRow';
				
				$reserve = new reserve();
				$reserve->getReserveByCI_Item($ci->getCourseInstanceID(), $item->getItemID());
				
				//determine if this is a currently-active class
				$now = time();  //get current time
				//if given only the date, strtotime() assumes we mean start of day, ie YYYY-MM-DD 00:00:00
				//to make sure we include the whole expiration day, we'll make it YYYY-MM-DD 23:59:59
				if(($ci->getStatus()=='ACTIVE') && (strtotime($ci->getActivationDate()) <= $now) && (strtotime($ci->getExpirationDate().' 23:59:59') >= $now)) {
					$icon = '<img src="public/images/astx-green.gif" alt="**" width="15" height="15" />';
				}
				else {
					$icon = '&nbsp;';
				}
?>
				<tr class="<?php print($rowClass); ?>">
					<td align="center"><?php print($icon); ?></td>
					<td><?php print($ci->displayTerm());?></td>
	    			<td><?php print($ci->course->displayCourseNo());?></td>
					<td><?php print($ci->course->getName());?></td>
					<td><?php print($ci->displayInstructors());?></td>					
					<td><span class="<?php print(common_getStatusStyleTag($reserve->getStatus()));?>"><?php print($reserve->getStatus()); ?></span></td>
					<td style="text-align:center;"><a href="javascript:openWindow('no_control=1&cmd=previewReservesList&ci=<?php print($ci->getCourseInstanceID()); ?>','width=800,height=600');">preview</a></td>
				</tr> 
<?php		endforeach;	?>

			</table>
			<div style="padding:8px; border-top:1px solid #333333;">
				<img src="public/images/astx-green.gif" alt="**" width="15" height="15" /> = classes currently using this item
			</div>
		</div>
	</div>
<?php
	}
	
	
	/**
	 * @return void
	 * @param reserveItem object $item
	 * @desc Displays item-copyright edit screen
	 */
	static function displayEditItemCopyright($item) {
		$copyright = new Copyright($item->getItemID());

		//get copyright library
		$home_lib_id = $item->getHomeLibraryID();
		if(!empty($home_lib_id)) {
			$home_lib = new library($home_lib_id);
			$copyright_lib = new library($home_lib->getCopyrightLibraryID());
			$copyright_lib_name = $copyright_lib->getLibrary();
		}
		else {
			$copyright_lib_name = 'n/a';
		}
		
		//get status basis
		$status_basis = $copyright->getStatusBasis();
		$status_basis = !empty($status_basis) ? $status_basis : 'n/a';
		
		//get contact name
		$contact = $copyright->getContact();
		$contact_org = $contact['org_name'];
?>
	<script language="JavaScript1.2" src="public/javascript/liveSearch.js"></script>
	<script language="JavaScript1.2" src="public/javascript/basicAJAX.js"></script>
	<script language="JavaScript1.2" src="public/javascript/notes_ajax.js"></script>
	<script language="JavaScript1.2" src="public/javascript/copyright_ajax.js"></script>
	
	<script language="JavaScript">
		function toggleDisplay(element_id, show) {
			if(document.getElementById(element_id)) {
				if(show) {				
					document.getElementById(element_id).style.display = '';
				}
				else {
					document.getElementById(element_id).style.display = 'none';
				}
			}
		}
	</script>
		
	<div id="copyright" class="displayArea">
		<div class="headingCell1">SUMMARY</div>
		<table width="100%" class="simpleList">
			<tr>
				<td width="150" class="labelCell1"><strong>Current Status:</strong></td>
				<td width="150" class="<?php print($copyright->getStatus()); ?>"><?php print($copyright->getStatus()); ?></td>
				<td width="150" class="labelCell1"><strong>Review Library:</strong></td>
				<td><?php print($copyright_lib_name);?></td>
			</tr>
			<tr>
				<td width="150" class="labelCell1"><strong>Reason:</strong></td>
				<td><?php print($status_basis); ?></td>
				<td width="150" class="labelCell1"><strong>Copyright Contact:</strong></td>
				<td><?php print($contact_org); ?></td>
			</tr>		
		</table>
<?php
		self::displayEditItemCopyrightStatus($item->getItemID());
		
		//only show the rest of the sections if the record exists
		$copyright_id = $copyright->getID();
		if(!empty($copyright_id)) {
			self::displayEditItemCopyrightContact($item->getItemID());
			self::displayEditItemCopyrightNotes($item->getItemID());
			self::displayEditItemCopyrightFiles($item->getItemID());
			self::displayEditItemCopyrightLog($item->getItemID());	
		}
?>	
	</div>
<?php	
	}
	
	
	/**
	 * @return void
	 * @desc Displays form to edit copyright status.
	 */
	static function displayEditItemCopyrightStatus($item_id) {
		$copyright = new Copyright($item_id);
?>
		<div class="contentTabs" style="text-align:center; border-top:1px solid #333333;"><a href="#" onclick="javascript: toggleDisplay('copyright_edit_status', 1); return false;">STATUS</a></div>
		<div id="copyright_edit_status" style="border-top:1px solid #333333; padding:10px; display:none;">		
			<?php copyrightDisplayer::displayCopyrightEditStatus($item_id, $copyright->getStatus(), $copyright->getStatusBasisID()); ?>	
			<input type="button" name="cancel" value="Close" onclick="javascript: toggleDisplay('copyright_edit_status', 0);" />
		</form>
		</div>
<?php
	}
	
	
	/**
	 * @return void
	 * @desc Displays form to edit copyright status.
	 */
	static function displayEditItemCopyrightContact($item_id) {
?>		
		<div class="contentTabs" style="text-align:center; border-top:1px solid #333333;"><a href="#" onclick="javascript: toggleDisplay('copyright_contact', 1); return false;">CONTACT</a></div>
		<div id="copyright_contact" style="border-top:1px solid #333333; padding:10px; display:none;">
			<?php copyrightDisplayer::displayCopyrightContactsBlockAJAX($item_id, true, true); ?>
			<input type="button" name="cancel" value="Close" onclick="javascript: toggleDisplay('copyright_contact', 0);" />
		</div>
<?php
	}
	
	
	/**
	 * @return void
	 * @param int $item_id ID of item
	 * @desc Displays copyright notes.
	 */
	static function displayEditItemCopyrightNotes($item_id) {
		//fetch notes
		$notes = noteManager::fetchNotesForObj('copyright', $item_id);
?>
		<div class="contentTabs" style="text-align:center; border-top:1px solid #333333;"><a href="#" onclick="javascript: toggleDisplay('copyright_notes', 1); return false;">NOTES</a></div>
		<div id="copyright_notes" style="border-top:1px solid #333333; padding:10px; display:none;">
			<?php self::displayNotesBlockAJAX($notes, 'copyright', $item_id, true); ?>
			<input type="button" name="cancel" value="Close" onclick="javascript: toggleDisplay('copyright_notes', 0);" />
		</div>
<?php
	}
	
	
	/**
	 * @return void
	 * @param int $item_id ID of item
	 * @desc Displays copyright files.
	 */
	static function displayEditItemCopyrightFiles($item_id) {
		
?>
		<div class="contentTabs" style="text-align:center; border-top:1px solid #333333;"><a href="#" onclick="javascript: toggleDisplay('copyright_files', 1); return false;">SUPPORTING FILES</a></div>
		<div id="copyright_files" style="border-top:1px solid #333333; padding:10px; display:none;">
		
<span style="color:red">DELETE ACTUAL FILE ON DELETE?</span>
<br /><br />

			<?php copyrightDisplayer::displayCopyrightSupportingFile($item_id); ?>
			<input type="button" name="cancel" value="Close" onclick="javascript: toggleDisplay('copyright_files', 0);" />
		</div>
<?php
	}
	

	/**
	 * @return void
	 * @desc Displays copyright log.
	 */
	static function displayEditItemCopyrightLog($item_id) {
?>
		<div class="contentTabs" style="text-align:center; border-top:1px solid #333333;"><a href="#" onclick="javascript: toggleDisplay('copyright_log', 1); return false;">LOG</a></div>
		<div id="copyright_log" style="display:none;">
			<?php copyrightDisplayer::displayCopyrightLogs($item_id); ?>
			<input type="button" name="cancel" value="Close" onclick="javascript: toggleDisplay('copyright_log', 0);" />
		</div>
<?php
	}
	
	/**
	 * @return void
	 * @param reserveItem object $item
	 * @desc Displays item status screen
	 */
	function displayItemStatus($item, $reserve) {
		global $g_recordDisplay;
		$localControlKey = $item->getLocalControlKey();
		$thisReserveId = (isset($reserve) ? $reserve->getReserveId() : '');
?>
<div class="headingCell1">TITLE (<?php print($localControlKey); ?>)</div>
<div class="headingSection">
<div id="item_title" style="padding:8px 8px 12px 8px;">
<h3>Reserve Items with this Control Key</h3>
<ul>
<?php 
	$matchingItems = $item->getMathcingItemsBy('local_control_key');
	foreach($matchingItems as $localControlKeyMatch){
?> 
	<li>(UID: <?php print($localControlKeyMatch->getItemId()); ?>)
		<?php if ($thisReserveId == $localControlKeyMatch->getReserveId()) {?>
		<b>Currently Selected Item</b>
		<?php } ?>
		<br/>
		<?php 
		?>
		<?php if ($thisReserveId != $localControlKeyMatch->getReserveId()) {?>
		<a href="?cmd=editItem&tab=status&reserveID=<?php print($localControlKeyMatch->getReserveId()); ?>">
		<?php } ?>
		<?php print('' != trim($localControlKeyMatch->getVolumeTitle()) ? ('<i>' . $localControlKeyMatch->getVolumeTitle() . '</i>, ') : '');
		print($localControlKeyMatch->getTitle()); ?>
		<?php if ($thisReserveId != $localControlKeyMatch->getReserveId()) {?></a> <?php } ?>,
		
		<?php print('' != trim($localControlKeyMatch->getVolumeEdition()) ? ($localControlKeyMatch->getVolumeEdition() . ', ') : ''); ?>
		<?php print('' != trim($localControlKeyMatch->getSource()) ? ($localControlKeyMatch->getSource() . ', ') : ''); ?>
		<?php print('' != trim($localControlKeyMatch->getPagesTimes()) ? ($localControlKeyMatch->getPagesTimes() . ', ') : ''); ?>
		<b>For: </b>
		<?php if('' != $localControlKeyMatch->getReserveId()) { 
			$course = $localControlKeyMatch->getCourseInstance();
			$primaryCourse = $course->getPrimaryCourse();
			$primaryDepartment = $primaryCourse->getDepartment();
			?>
		
			<a href="?cmd=editClass&ci=<?php print($course->getCourseInstanceID()); ?>">
			<?php print($course->getTerm()); ?> <?php print($course->getYear()); ?> 
			<?php print($primaryDepartment); ?> <?php print($primaryCourse->getCourseNo()); ?> <?php print($primaryCourse->getSection()); ?>
			</a>
		<?php } else { ?>
			<i>Not properly associated with a course.</i>
		<?php } ?></li>
<?php 
	}
	$ils = RD_Ils::initILS(); //#TODO this it totally not the right place to instantiate a resource...
?>
</ul>
<h3><?php print($ils->getName()); ?>'s Current Information on <?php print($localControlKey); ?></h3>
<?php 
	
	try{
		$ilsResult = $ils->search('controlKey',$localControlKey);
		$ilsHasNoTitles = 0 == $ilsResult->getTitleCount();
	} catch (Exception $e) {
		$ilsHasNoTitles = true;	
	}
	if (!$ilsHasNoTitles) {
?>	<div class="ilsInfo">
		<p>Found <?php print($ilsResult->getTitleCount()); ?> title, <?php print($ilsResult->getHoldingCount()); ?> holding<?php if($ilsResult->getHoldingCount() != 1) {?>s<?php } ?>.</p>
		<p><b>Title:</b> <?php print($ilsResult->getTitle()); ?></p>
		<p class="noMarginBottom"><b>Holdings:</b></p>
		<ul>
		<?php foreach ($ilsResult->getHoldings() as $holding) { /*TODO this should be called in the manager... */ ?>
			<li><?php print(
				(
					array_key_exists('library',$holding) 
					? "Library: {$holding['library']} "
					: ''
				) . (
					array_key_exists('loc',$holding) 
					? "Location: {$holding['loc']} "
					: ''
				) . (
					array_key_exists('callNum',$holding) 
					? "Call Number: {$holding['callNum']} "
					: ''
				) . (
					array_key_exists('type',$holding) 
					? "Type: {$holding['type']} "
					: ''
				)
			);?></li>
		<?php } ?>
		</ul>
	</div>

<?php }
 if ('' != $localControlKey) { ?>
<?php self::displayQuickviewLink($localControlKey, 'View this title in the catalog.'); ?>
<?php } else { ?>
<p class="warning">This item's control number does not match the expected format.</p>
<?php } ?>
</div></div>
<div class="headingCell1">PHYSICAL ITEMS</div>
<div class="headingSection">
<div id="item_title" style="padding:8px 8px 12px 8px;">
<h3>Barcodes associated with this Catkey</h3>
<ul>
<?php 
	$physicalCopies = array();
	foreach($matchingItems as $localControlKeyMatch){
		$itemPhysicalCopies = $localControlKeyMatch->getPhysicalCopies();
		foreach($itemPhysicalCopies as $physicalCopy){
			$physicalCopies[$physicalCopy->getPhysicalCopyID()] = $physicalCopy;
		}
	}
	$barcodesAllSucceed = true;
	$anyBarcodeSucceeds = false;
	foreach($physicalCopies as $physicalCopy){
?>
		<li>(UID: <?php print($physicalCopy->getItemId()); ?>)<br/>
		<?php print($physicalCopy->getBarcode()); ?>,
<?php 
		$barcodeResult = $ils->barcodeLookup($physicalCopy->getBarcode());
		$barcodeTitleLocalControlKey = $barcodeResult['local_control_key'];
?>
		<?php print(
			$barcodeTitleLocalControlKey == $localControlKey
			? ('Local Control Key: ' . $barcodeTitleLocalControlKey)
			: '<span class="warning">Local Control Key Mismatch: ' . $barcodeTitleLocalControlKey . ' </warning>'
		);?>
		</li>
<?php 
	}

?>
</ul>
<?php if ('' != $localControlKey && $ilsHasNoTitles) { ?>
	<p class="warning">This title appears to be fully shadowed (shadowed at the title level, or all items are shadowed) in the ILS!</p>
<?php } ?>
</div></div>
<?php 
	}

	
	function displayEditHeadingScreen($ci, $heading)
	{
		global $calendar;
		$heading->getItem();
		$class = new courseInstance($ci);
		$tmpActDate = $heading->getActivationDate();
		$tmpExpDate = $heading->getExpirationDate();
		$heading_activation_date = empty($tmpActDate) ? $class->getActivationDate() : $heading->getActivationDate();
		$heading_expiration_date = empty($tmpExpDate) ? $class->getExpirationDate() : $heading->getExpirationDate();
		
		
		if ($heading->getSortOrder() == 0 || $heading->getSortOrder() == null)
			$currentSortOrder = "Not Yet Specified";
		else
			$currentSortOrder = $heading->getSortOrder();
?>
		<form action="index.php" method="post" name="editHeading">
		
			<input type="hidden" name="cmd" value="processHeading">
			<input type="hidden" name="nextAction" value="editClass">
			<input type="hidden" name="ci" value="<?php print($ci);?>">
			<input type="hidden" name="headingID" value="<?php print($heading->itemID); ?>">
			<input type="hidden" name="reserveID" value="<?php print($heading->getReserveID()); ?>">
			
		<table width="100%" border="0" cellspacing="0" cellpadding="0" align="center">
			<tr>
				<td colspan="2" align="right"><strong><a href="index.php?cmd=editClass&ci=<?php print($ci); ?>">Cancel and return to class</a></strong></td>	
			</tr>
			<tr>
				<td colspan="2">
					<div class="helperText" style="align:left; padding:8px 0 20px 0; margin-right:180px;">
					Headings help organize your list of materials into topics or weeks. Headings can stand alone, 
					or you can add items to them. To add an item to a heading (like you would to a folder), go to the Edit Class
					screen, check the items to add to the heading, and scroll to the bottom of your list of materials.
					Select which heading to add the materials to and click the "Submit" button.
					</div>
				</td>
			</tr>
			<tr>
				<td class="headingCell1" width="25%" align="center">HEADING DETAILS</td>
				<td width="75%" align="center">&nbsp;</td>
			</tr>
		    <tr>
		    	<td colspan="2" class="borders">
			    	<table width="100%" border="0" cellspacing="0" cellpadding="5">
			    		<tr>
			    			<td width="30%" bgcolor="#CCCCCC" align="right" class="strong">
			    				<font color="#FF0000">*</font>&nbsp;Heading Title:
			    			</td>
			    			<td>
			    				<input name="heading" type="text" size="60" value="<?php print($heading->item->getTitle()); ?>" />
			    			</td>
			    		</tr>
			    		<tr>
			    			<td bgcolor="#CCCCCC" align="right" class="strong">
			    				Current Sort Position:
			    			</td>
	        				<td>
	        					<?php print($currentSortOrder); ?>
	        				</td>			    		
			    		</tr>
			    		<tr>
			    			<td bgcolor="#CCCCCC" align="right" class="strong">
			    				Active Dates:
			    			</td>
			    			<td>
			    				From:&nbsp;<input type="text" id="reserve_activation_date" name="reserve_activation_date" size="10" maxlength="10" value="<?php print($heading_activation_date); ?>" /> <?php print($calendar->getWidgetAndTrigger('reserve_activation_date', $heading_activation_date)); ?>
								To:&nbsp;<input type="text" id="reserve_expiration_date" name="reserve_expiration_date" size="10" maxlength="10" value="<?php print($heading_expiration_date); ?>" />  <?php print($calendar->getWidgetAndTrigger('reserve_expiration_date', $heading_expiration_date)); ?>
			    			</td>
			    		
			    		</tr>
			    		
<?php
		//notes - only deal with notes if editing a heading (as opposed to creating)
		if($heading->getReserveID()):		
			//display edit notes
			self::displayEditNotes($heading->getNotes(true), 'headingID='.$heading->getReserveID().'&amp;ci='.$ci);
			
			//display "Add Note" button
?>
						<tr>
							<td colspan="2" bgcolor="#CCCCCC" align="center" class="borders" style="border-left:0px; border-bottom:0px; border-right:0px;">
								<?php self::displayAddNoteButton('reserveID='.$heading->getReserveID()); ?>
							</td>
						</tr>
<?php	endif; ?>
					
					</table>
					
				</div>
        		</td>
      		</tr>
      		<tr>
      			<td colspan="2" align="center">
      				<br />
      				<input type="submit" name="submit" value="Save Heading" />
				</td>
      		</tr>
    	</table>
    	</form>
<?php
  
	}

	
	/**
	* @return void
	* @param int $ci_id courseInstance ID
	* @param string $search_serial serialized search _request
	* @desc Displays editItem/editReserve success screen
	*/	
	function displayItemSuccessScreen($ci_id=null, $search_serial=null)	{		
?>
		<table width="100%" border="0" cellspacing="0" cellpadding="0" align="center">
			<tr>
		    	<td width="140%"><img src="/images/spacer.gif" width="1" height="5"> </td>
		    </tr>
		    <tr>
		        <td align="left" valign="top" class="borders">
					<table width="50%" border="0" align="center" cellpadding="0" cellspacing="5">
		            	<tr>
		                	<td><strong>Your item has been updated successfully.</strong></td>
		                </tr>
		                <tr>
		                	<td align="left" valign="top">
		                		<ul>		                		
<?php	if($ci_id): ?>
					<li><a href="index.php?cmd=editClass&amp;ci=<?php print($ci_id); ?>">Return to Class</a></li>
<?php	elseif($search_serial): ?>
					<li><a href="index.php?cmd=doSearch&amp;search=<?php print($search_serial); ?>">Return to Search Results</a></li>					
<?php	endif; ?>
		                			<li><a href="index.php">Return to MyCourses</a><br></li>
		                		</ul>
		                	</td>
		                </tr>
		            </table>
				</td>
			</tr>
		</table>
<?php
	}
	
	/**
	* @return void
	* @param int $ci_id courseInstance ID
	* @desc Displays editHeading success screen
	*/	
	function displayHeadingSuccessScreen($ci_id=null)	{		
?>
		<div class="borders" style="text-align:middle;">
			<div style="width:50%; margin:auto;">
				<strong>Your heading has been added/updated successfully.</strong>
				<br />
				<ul>
	    			<li><a href="index.php?cmd=editClass&amp;ci=<?php print($ci_id); ?>">Return to class</a></li>
	    			<li><a href="index.php?cmd=editHeading&amp;ci=<?php print($ci_id); ?>">Create another heading</a></a></li>
	    			<li><a href="index.php?cmd=customSort&amp;ci=<?php print($ci_id); ?>">Change heading sort position</a></li>
	    		</ul>
			</div>
		</div>
<?php
	}
	
}
