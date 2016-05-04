<?php
/*******************************************************************************
requestDisplayer.class.php


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

USED FOR ADDING ITEMS TO COURSE RESERVES

*******************************************************************************/
require_once(APPLICATION_PATH . '/classes/terms.class.php');
require_once(APPLICATION_PATH . '/classes/circRules.class.php');
require_once(APPLICATION_PATH . '/displayers/noteDisplayer.class.php');
require_once(APPLICATION_PATH . '/managers/ajaxManager.class.php');

require_once(APPLICATION_PATH . '/classes/Queue/Encoding.php');

class requestDisplayer extends noteDisplayer {
	
	protected $_displayerName = 'request';
	
	function displayAllRequest($requestList, $libList, $request, $user, $msg="")
	{
		
		echo "<script language='JavaScript1.2'>
				  var jsFunctions = new basicAJAX();
			 	  function setRequestStatus(select, request_id, notice) 
  				  {				
						var status = select.options[select.selectedIndex].value;
						var u   = 'AJAX_functions.php?f=updateRequestStatus';
						var qs  = 'request_id=' + request_id + '&status=' + status;
						
						var url = u + '&rf=' + jsFunctions.base64_encode(qs);
						
						ajax_transport(url, notice);
				  }
			 </script>
		\n";
		//Need an error message?
		echo "	<form action=\"index.php?cmd=displayRequest\" method=\"POST\">\n";
		echo "  <div class=\"selectLibrary\">\n";
		echo "  	<strong>Library:&nbsp;&nbsp;</strong>\n";
		echo "    	<select name=\"unit\">\n";
		

		$currentUnit = isset($request['unit']) ? $request['unit'] : $user->getStaffLibrary();
		$y = 0;
		foreach ($libList as $lib)
		{
			$lib_select = ($currentUnit == $lib->getLibraryID()) ? " selected " : "";
			if($y == 0){
				echo "			<option $lib_select value=\"all\">All Libraries</option>\n";
			}
			echo "		<option $lib_select value=\"" . $lib->getLibraryID() . "\">" . strtoupper($lib->getLibraryNickname()) . "</option>\n";
			$y++;
		}
		
		echo " 	  	</select>\n";

		echo "  </div>\n";		
		
		echo " 	<div>\n";
		echo "	<strong>Status:&nbsp;&nbsp;</strong> \n";
		
		$filter = (!isset($request['filter_status'])) ? "ALL" : str_replace(' ', '', strtoupper($request['filter_status']));
		$$filter = ' SELECTED '; //#TODO this is a super confusing way to do this... FIX
		echo "			<select name='filter_status'>\n";
		echo "				<option " . (isset($ALL) ? $ALL : '') . " value='all'>All Statuses</option>\n";
		echo "				<option " . (isset($INPROCESS) ? $INPROCESS : '') . " value='IN PROCESS'>IN PROCESS</option>\n";
		echo "				<option " . (isset($NEW) ? $NEW : '') . " value='NEW'>NEW</option>\n";
		echo "				<option " . (isset($COPYRIGHTREVIEW) ? $COPYRIGHTREVIEW : '') . " value='COPYRIGHT REVIEW'>COPYRIGHT REVIEW</option>\n";
		echo "				<option " . (isset($RESPONSENEEDED) ? $RESPONSENEEDED : '') . " value='RESPONSE NEEDED'>RESPONSE NEEDED</option>\n";
		echo "				<option " . (isset($RUSH) ? $RUSH : '') . " value='RUSH'>RUSH</option>\n";
		echo "				<option " . (isset($SEARCHINGSTACKS) ? $SEARCHINGSTACKS : '') . " value='SEARCHING STACKS'>SEARCHING STACKS</option>\n";
		echo "				<option " . (isset($ONORDER) ? $ONORDER : '') . " value='ON ORDER'>ON ORDER</option>\n";
		echo "				<option " . (isset($RECALLED) ? $RECALLED : '') . " value='RECALLED'>RECALLED</option>\n";
		echo "				<option " . (isset($MISSING) ? $MISSING : '') . " value='MISSING'>MISSING</option>\n";
		echo "				<option " . (isset($UNAVAILABLE) ? $UNAVAILABLE : '') . " value='UNAVAILABLE'>UNAVAILABLE</option>\n";			
		echo "				<option " . (isset($SCANNING) ? $SCANNING : '') . " value='SCANNING'>SCANNING</option>\n";		
		echo "			</select>\n";
		
		echo "			<input type=\"submit\" value=\"Go\">\n";
		echo "			</div>\n";
		echo "          </form>\n";
		
		echo "			<form action=\"index.php?sort=\"" . (array_key_exists('sort', $request) ? $request['sort'] : '') . "\" method=\"POST\">\n";
		echo "			<input type=\"hidden\" name=\"cmd\" value=\"printRequest\">\n";
        echo "			<input type=\"hidden\" name=\"sort\" value=\"" . (array_key_exists('sort', $request) ? $request['sort'] : '') . "\">\n";
        echo "			<input type=\"hidden\" name=\"no_table\">\n";
        echo "			<input type=\"hidden\" name=\"request_id\">\n";
		echo "			<div><span style=\"float:right; width:25%;\">" . count($requestList) . " requests matched</span>\n";
		echo "      	<input type=\"button\" value=\"Print Selected Request\" onClick=\"this.form.cmd.value='printRequest'; this.form.target='printPage'; this.form.submit(); checkAll(this.form, false);\">\n";
		echo "      	</div>\n";
		echo "			<div class=\"changeAll\">\n";
		echo " 				<span class=\"ExpandAll\"><a href=\"#\">Expand All</a></span> | <span class=\"CollapseAll\"><a href=\"#\">Collapse All</a></span>\n";
		echo " 			</div>\n";
		
		if (count($requestList) > 0)
		{
			requestDisplayer::displayRequestList($requestList, $request, $user, $currentUnit);
		} else {
			echo "<div>No " . (array_key_exists('filter_status', $request) ? $request['filter_status'] : '') . " Request to process for this unit.</div>";
		}
	
	}

	function printSelectedRequest($requestList, $libList, $request, $user, $msg="")
	{		
		
		$currentUnit = isset($request['unit']) ? $request['unit'] : $user->getStaffLibrary();
		echo "<script language='JavaScript1.2'>
				  var jsFunctions = new basicAJAX();				  
				  function markAsPulled(request_ids, notice)
				  {
					var status = 'SEARCHING STACKS';
					var u   = 'AJAX_functions.php?f=updateRequestStatus';
					var qs;
					var url;
									  
				  	for(var i=0;i<request_ids.length;i++)				  		
				  	{				  			
				  		qs  = 'request_id=' + request_ids[i] + '&status=' + status;
				  		url = u + '&rf=' + jsFunctions.base64_encode(qs);
						
						ajax_transport(url, notice);				  			
				  	}
				  }
			  </script>
		\n";
		
		
		
		echo "<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\" align=\"center\">\n";
		

		echo "	<tr>\n";
		echo "		<td align=\"left\">Request List</td>\n";
		echo "	</tr>\n";		
		
		echo "	<tr>\n";
		echo "		<td align=\"left\">". date('g:i A D m-d-Y') ."</td>\n";
		echo "	</tr>\n";
		
		echo "	<tr>\n";
		echo "		<td align=\"right\"><img src=\"public/images/spacer.gif\" width=\"1\" height=\"15\">[ <a href=\"javascript:window.close();\">Close Window</a> ]</td>\n";
		echo "	</tr>\n";		
		
		echo "	<tr>\n";
		echo "		<td align=\"right\"><input type=\"button\" value=\"Print\" onClick=\"window.print();\"></td>\n";
		echo "	</tr>\n";

		echo "	<tr>\n";
		echo "		<td align=\"right\"><div id=\"marked_indicator\" style='display: inline;'><img width='16px' height='16px' src='public/images/spacer.gif' /></div><input type=\"button\" value=\"Mark All As PULLED\" onClick=\"markAsPulled([". $requestList->id_list() ."], 'marked_indicator');\"></td>\n";
		echo "	</tr>\n";		
		
		
		if (!is_null($msg) && $msg != "")
			echo "	<tr><td width=\"100%\" class=\"failedText\" align=\"center\">$msg<br></td></tr>\n";

		echo " 			</table>\n";
		echo "		</td>\n";
		echo "	</tr>\n";

		echo "</table>\n";		
		if (count($requestList) > 0)
			requestDisplayer::displayRequestList($requestList, $request, $user, $currentUnit, "true");
		else 
			echo "<p style=\"text-align: center\">No Request selected for printing.</p>";


	}	
	
	static function displayRequestList($requestList, $request, $user, $currentUnit, $printView=null)
	{	
		global $g_catalogName; 
		
?>
			<script type="text/javascript">
				function confirmation(type, address) {
					if (type == "delete"){
						message = "Are you sure you want to Delete this reserve?";
					}
					if (type == "denyCopyright"){
						message = "Are you sure you want to Deny Copyright to this class for this reserve?";
					}
					if (type == "denyCopyrightAll"){
						message = "Are you sure you want to Deny Copyright FOR ALL USERS for this item?";
					}
					var answer = confirm(message);
					if(answer){
						window.location = address;
						return true;
					}
					else{
						return false;
					}
				}
			
				jQuery(document).ready(function() { 
					var details = jQuery('div.queueMetadata table.details');
					var detailsImgs = jQuery("div.queueMetadata img.detailsImg");
					jQuery('div.detailsButton').click(function(){
						jQuery(this).parent().find("table.details").toggle();
						if (jQuery(this).parent().find("table.details").is(":visible")) {
							jQuery(this).find("img.detailsImg").attr("src","public/images/minimize.gif");
						}
						else {
							jQuery(this).find("img.detailsImg").attr("src","public/images/maximize.gif");
						}
						return false;
					});
					
					jQuery("span.ExpandAll").click(function() {	
						detailsImgs.attr("src", "public/images/minimize.gif");
						details.show();
						return false;
					});
					jQuery("span.CollapseAll").click(function(){
						detailsImgs.attr("src", "public/images/maximize.gif");
						details.hide();
						return false;
					});
				});
			</script>
			
			<table class="queue">
				<tr class="category">
<?php if (!$printView) { ?>
					<th>
						<input type="checkbox" onchange="javascript: checkAll(this.form, this.checked);" />
					</th>
<?php } ?>
<?php
		$sortable = array('date', 'type', 'requested', 'needed', 'semester', 'class', 'instructor');
		$sortTitles = array('ID', 'TYPE', 'Requested', 'Needed By', 'Semester', 'Course', 'Instructor(s)');
		$x = 0;
		$filter = (!isset($request['filter_status'])) ? "all" : $request['filter_status'];
		$sorting = (!isset($request['sort']) ? 'date': $request['sort']);
		$dir = (!isset($request['dir']) ? 'asc' : $request['dir']);
		
		foreach($sortable as $sort){
			$generatedTag = (
				!$printView
				? (
					"<a href=\"index.php?cmd=". $request['cmd'] 
					. "&unit=". $currentUnit . "&filter_status=" 
					. $filter . "&sort=" . $sort . "&dir=" . (($sorting == $sort && $dir =='asc') ? "desc" : "asc") 
					. "\" "
					. ( $sorting == $sort ? ("class='sort-". ($dir =='asc' ? "asc" : "desc") ) : '' )
					."'>"
					. ($sorting == $sort ? "<b>$sortTitles[$x]</b>" : "$sortTitles[$x]") . "</a>"
				) : (
					"<span "
					. ( $sorting == $sort ? ("class='sort-". ($dir =='asc' ? "asc" : "desc") ) : '' )
					."'>"
					. ($sorting == $sort ? "<b>$sortTitles[$x]</b>" : "$sortTitles[$x]") . "</span>"
				)
			);
			echo "\t\t\t\t\t<th " . ($x == 0 ? "id=\"rid\"" : "") . ">"
				. $generatedTag
				. "</th>\n";
			$x++;
		}
?>
				</tr>
		
		
<?php
		$cnt = 0;
		foreach ($requestList as $r)
		{
			$item = $r->requestedItem;
			$ci = $r->courseInstance;
			
			
			$pCopy = $item->physicalCopy;
			
			
			if($pCopy->getCallNumber()){
				$callnum = $pCopy->getCallNumber();	
			}
			elseif($item->getOldId()){
				$callnum = $item->getOldId() . " <b>(Tentative)</b>";	
			}
			else{
				$callnum="";	
			}
			$requestNotes = $r->getNotes();
			
			//$$selected = "";
			$selected = str_replace(' ', '', $r->getStatus());
			$$selected = ' selected="selected" '; //#TODO this is a super confusing way to do this... FIX
			
			
			if(!array_key_exists('unit', $request) || '' ==  $request['unit']){
				$request['unit'] = $user->getStaffLibrary();
			}
			if(!array_key_exists('filter_status', $request) || '' ==  $request['filter_status']){
				$request['filter_status'] = 'IN PROCESS';
			}
			
			$processCmd = ($r->isScanRequest()) ? "addDigitalItem" : "addPhysicalItem";

			$cnt++;
			
			$location = "";
			if(count($r->holdings) > 0){
				foreach ($r->holdings as $h){
					if (is_array($h)){
						$location .= "" . $h['library'] . " " . $h['callNum'] . " " . $h['loc'] . " " . $h['type'] . "<br>";
					}
				}
			}
?>
				<tr class="queueData">
<?php if (!$printView) { ?>
					<th id="r<?php print($r->requestID); ?>"><input type="checkbox" name="selectedRequest[]" value="<?php print($r->requestID); ?>"></th>
<?php } ?>
					<td class="dataLeft"><strong><?php print(sprintf("%06s",$r->requestID)); ?></strong></td>
					<td><?php print($r->getType()); ?></td>
					<td><?php print(common_formatdate($r->getDateRequested(), "MM-DD-YYYY")); ?></td>
					<td><?php print(common_formatdate($r->getDesiredDate(), "MM-DD-YYYY")); ?></td>
					<td><?php print($ci->displayTerm()); ?></td>
					<td><?php print($ci->course->displayCourseNo()); ?></td>
					<td><?php print($ci->displayInstructors(true)); ?></td>
				</tr>
				
				<tr class="temp">
<?php if (!$printView) { ?>
					<td>&nbsp;</td>
<?php } ?>
					<td class="queueItem" colspan="7" headers="rid r<?php print($r->requestID); ?>">
<?php if(!$printView) { ?>
						<div class="queueActions">
							<div>
								<div class="queueActionsSelect">
									<div id='noticeDropDown_<?php print($r->requestID); ?>' style='display: inline;'>
										<img width='16px' height='16px' src='public/images/spacer.gif' />
									</div>
									<select name='<?php print($r->requestID); ?>_status' onChange='setRequestStatus(this, <?php print($r->requestID); ?>, "noticeDropDown_<?php print($r->requestID); ?>");'>
										<option <?php print(isset($INPROCESS) ? $INPROCESS : ''); ?> value='IN PROCESS'>IN PROCESS</option>
										<option <?php print(isset($NEW) ? $NEW : ''); ?> value='NEW'>NEW</option>
										<option <?php print(isset($COPYRIGHTREVIEW) ? $COPYRIGHTREVIEW : ''); ?> value='COPYRIGHT REVIEW'>COPYRIGHT REVIEW</option>
										<option <?php print(isset($RESPONSENEEDED) ? $RESPONSENEEDED : ''); ?> value='RESPONSE NEEDED'>RESPONSE NEEDED</option>
										<option <?php print(isset($RUSH) ? $RUSH : ''); ?> value='RUSH'>RUSH</option>
										<option <?php print(isset($SEARCHINGSTACKS) ? $SEARCHINGSTACKS : ''); ?> value='SEARCHING STACKS'>SEARCHING STACKS</option>
										<option <?php print(isset($ONORDER) ? $ONORDER : ''); ?> value='ON ORDER'>ON ORDER</option>
										<option <?php print(isset($RECALLED) ? $RECALLED : ''); ?> value='RECALLED'>RECALLED</option>
										<option <?php print(isset($MISSING) ? $MISSING : ''); ?> value='MISSING'>MISSING</option>
										<option <?php print(isset($UNAVAILABLE) ? $UNAVAILABLE : ''); ?> value='UNAVAILABLE'>UNAVAILABLE</option>
										<option <?php print(isset($SCANNING) ? $SCANNING : ''); ?> value='SCANNING'>SCANNING</option>
									</select>
								</div>
								<div class="processButtons">
									<span>
										<input type="button" class="processButton" value="Process Request" onclick="window.location='index.php?cmd=<?php print($processCmd); ?>&request_id=<?php print($r->requestID); ?>&loan_period=<?php print($r->reserve->getRequestedLoanPeriod()); ?>'" />
									</span>
									<span>
										<input type="button" class="deleteButton" value="Delete Request" onClick="confirmation('delete', 'index.php?cmd=deleteRequest&request_id=<?php print($r->requestID); ?>&unit=<?php print($request['unit']); ?>&filter_status=<?php print($request['filter_status']); ?>&sort=<?php print(array_key_exists('sort', $request) ? $request['sort'] : ''); ?>')" />
									</span>
								</div>
	<?php if ($r->getType() == "SCAN") { ?>
								<div class="denyButtons">
									<span>
										<input type="button" class="denyButton" value="Deny Copyright" onClick="confirmation('denyCopyright', 'index.php?cmd=setStatus&request_id=<?php print($r->requestID); ?>&unit=<?php print($request['unit']); ?>&filter_status=<?php print($request['filter_status']); ?>&sort=<?php print(array_key_exists('sort', $request) ? $request['sort'] : '');?>&status=DENIED')"/>
									</span>
									<span>
										<input type="button" class="denyButton"  value="Deny Copyright for all" onClick="confirmation('denyCopyrightAll', 'index.php?cmd=setStatus&request_id=<?php print($r->requestID); ?>&unit=<?php print($request['unit']); ?>&filter_status=<?php print($request['filter_status']); ?>&sort=<?php print(array_key_exists('sort', $request) ? $request['sort'] : ''); ?>&status=DENIED_ALL')"/>
									</span>
								</div>
	<?php } ?>
							</div>
						</div>
<?php }?>

<?php if(!$printView){ ?>						
						<div class="queueMetadata">

							<div id='notice_<?php print($r->requestID); ?>' class='detailsButton'>
								<img class="detailsImg" width='16px' height='16px' src='public/images/maximize.gif' alt=''/>
								DETAILS
							</div>
<?php } ?>
							<div class="queueMetadataBlock">
								<table>
									<tr class="queueMetadataRow">
										<td class="queueMetadataKey">TITLE:</td>
										<td class="queueMetadataValue"><?php print($item->getTitle()); ?></td>
									</tr>
									<tr class="queueMetadataRow">
										<td class="queueMetadataKey">AUTHOR:</td>
										<td class="queueMetadataValue"><?php print($item->getAuthor()); ?></th>
									</tr>
									<tr class="queueMetadataRow">
										<td class="queueMetadataKey">Call Number:</td>
										<td class="queueMetadataValue"><?php print($callnum); ?></td>
									</tr>
<?php if(!$printView){?>
								</table>
								<table class="details">
<?php } else{?>
									<tr class="queueMetadataRow">
										<td class="queueMetadataKey">Status:</td>
										<td class="queueMetadataValue"><?php print($r->getStatus());?></td>
									</tr>
<?php }?>
									<tr class="queueMetadataRow">
										<td class="queueMetadataKey">Volume Title:</td>
										<td class="queueMetadataValue"><?php print($item->getVolumeTitle());?></td>
									</tr>
									<tr class="queueMetadataRow">
										<td class="queueMetadataKey">Volume Edition:</td>
										<td class="queueMetadataValue"><?php print($item->getVolumeEdition());?></td>
									</tr>
									<tr class="queueMetadataRow">
										<td class="queueMetadataKey">Pages/Times:</td>
										<td class="queueMetadataValue"><?php print($item->getPagesTimes()); ?></td>
									</tr>
									<tr class="queueMetadataRow">
										<td class="queueMetadataKey">Source/Year:</td>
										<td class="queueMetadataValue"><?php print($item->getSource()); ?></td>
									</tr>
									<tr class="queueMetadataRow">
										<td class="queueMetadataKey">Requested Loan Period:</td>
										<td class="queueMetadataValue"><?php print($r->reserve->getRequestedLoanPeriod());?></td>
									</tr>

									<tr class="queueMetadataRow">
										<td class="queueMetadataKey">ISBN:</td>
										<td class="queueMetadataValue"><?php print($item->getISBN()); ?></td>
									</tr>
									<tr class="queueMetadataRow">
										<td class="queueMetadataKey">ISSN:</td>
										<td class="queueMetadataValue"><?php print($item->getISSN()); ?></td>
									</tr>
									<tr class="queueMetadataRow">
										<td class="queueMetadataKey">OCLC:</td>
										<td class="queueMetadataValue"><?php print($item->getOCLC()); ?></td>
									</tr>
<?php if($printView){?>
									<tr class= "queueMetadataRow">
										<td class="queueMetadataKey">Location:</td>
										<td class="queueMetadataValue"><?php print($location); ?></td>
									</tr>
<?php } else {?>
									<tr class="queueMetadataRow">
										<td class="queueMetadataKey">Status:</td>
										<td class="queueMetadataValue"><?php print($r->getStatus());?></td>
									</tr>
<?php } ?>
									<tr class="queueMetadataRow">
										<td class="queueMetadataKey" style="vertical-align: top">Notes:</td>
										<td class="queueMetadataValue"><?php print(self::displayNotes($requestNotes)); ?></td>
									</tr>
								</table>
							</div>
<?php if (!$printView){ //end queuemetadata block 
?>
						</div>
<?php }
if(isset($selected)){unset($$selected);}
?>
					</td>
				</tr>
<?php
		}
		
		echo " </table>\n";
		echo "</form>\n";
	}
	
	function addItem($cmd, $item_data, $hidden_fields=null) {
		global $g_permission, $g_notetype, $g_catalogName;
		
		$u = Rd_Registry::get('root:userInterface');
		
		//for ease-of-use, define helper vars for determining digital/physical items
		$isPhysical = ($cmd=='addPhysicalItem') ? true : false;
		$isDigital = ($cmd=='addDigitalItem') ? true : false;
		$isVideo = ($cmd=='addVideoItem') ? true : false;
		
		if ($isVideo) {
			$item_data['times_pages2'] = null;
		}
		
		if ($isVideo && !empty($item_data['times_pages'])) {
			$times = explode(" to ", $item_data['times_pages']);
			$item_data['times_pages'] = $times[0];
			$item_data['times_pages2'] = $times[1];
		}
		
		//get array of document types/icons/helper apps (digital items only)
		$doc_types = $isDigital ? $u->getAllDocTypeIcons() : null;
		//get array of libraries (physical items only)
		$libraries = $isPhysical ? $u->getLibraries() : null;
		
		//private user
		if (!empty($item_data['selected_owner'])) {
			//get id
			$selected_owner_id = $item_data['selected_owner'];
			$tmpUser = new user($selected_owner_id);
			//get name
			$selected_owner_name = $tmpUser->getName().' ('.$tmpUser->getUsername().')';
			unset($tmpUser);
		} else {
			$selected_owner_id = NULL;
		}
		
		//deal with barcode prefills
		if (!empty($_REQUEST['searchField'])) {
			if ($_REQUEST['searchField'] == 'barcode') {
				$barcode_select = ' selected = "selected"';
				$control_select = '';
				//assume that this index exists
				$barcode_value = $_REQUEST['searchTerm'];
			} else {
				$barcode_select = '';
				$control_select = ' selected = "selected"';
				$barcode_value = (is_array($item_data['physicalCopy']) && !empty($item_data['physicalCopy'])) ? $item_data['physicalCopy'][0]['bar'] : '';
			}
			$search_term = $_REQUEST['searchTerm'];
		} else {
			$barcode_select = ' selected = "selected"';
			$control_select = '';
			$search_term = '';
			if ($isVideo){
				$item_data['physicalCopy'] = '';
			}
			$barcode_value = (is_array($item_data['physicalCopy']) && !empty($item_data['physicalCopy'])) ? $item_data['physicalCopy'][0]['bar'] : '';
		}
		
		//deal with physical item source pre-select
		$addType_select = array('euclid'=>'', 'personal'=>'');
		if ($isPhysical) {
			if (!empty($_REQUEST['addType']) && ($_REQUEST['addType']=='PERSONAL')) {
				$addType_select['personal'] = ' checked="true"';				
			} else {
				$addType_select['euclid'] = ' checked="true"';
			}			
		}
		
		//decide if need to add form encoding type
		$form_enctype = ($isDigital || $isVideo) ? ' enctype="multipart/form-data"' : '';
?>
		<script type="text/javascript">
			//shows/hides personal item elements; marks them as required or not
			function togglePersonal(enable, req)
			{
				//show block or not?
				if (enable) {
					document.getElementById('personal_item_row').style.display = '';
				} else {
					document.getElementById('personal_item_no').checked = true;
					document.getElementById('personal_item_row').style.display = 'none';
					return;
				}
				
				//if required, show just the name search and red *
				if (req) {
					document.getElementById('personal_req_mark').style.display = '';
					document.getElementById('personal_item_choice').style.display = 'none';
					document.getElementById('personal_item_owner_block').style.display = '';
					document.getElementById('personal_item_yes').checked = true;
					togglePersonalOwnerSearch();
				} else {
					document.getElementById('personal_req_mark').style.display = 'none';
					document.getElementById('personal_item_choice').style.display = '';
					togglePersonalOwner();
				}
			}		
			
			//shows/hides personal item owner search fields
			function togglePersonalOwner()
			{
				if(document.getElementById('personal_item_no').checked) {
					document.getElementById('personal_item_owner_block').style.display = 'none';
				}
				else if(document.getElementById('personal_item_yes').checked) {
					document.getElementById('personal_item_owner_block').style.display = '';
					togglePersonalOwnerSearch();
				}	
			}	
								
			//shows/hides personal item owner search fields
			function togglePersonalOwnerSearch()
			{	
				//if personal owner set
				if(document.getElementById('personal_item_owner_curr').checked) {
					document.getElementById('personal_item_owner_search').style.visibility = 'hidden';
				}
				else if(document.getElementById('personal_item_owner_new').checked) {
					document.getElementById('personal_item_owner_search').style.visibility = 'visible';
				}	
			}
		</script>
		
		<form action="index.php" method="post" id="additem_form" name="additem_form" <?php print($form_enctype); ?>>
		<input type="hidden" name="loan_period" value="<?php print($item_data['loan_period']); ?>"/>
		
		<?php self::displayHiddenFields($hidden_fields); ?>
		<script type="text/javascript">
			function handleQuirkyAjaxLibrary()
			{
				var mainForm = document.getElementById('additem_form');
				if (checkForm(mainForm)) {
					if (!mainForm.store_request || jQuery.browser.safari) {
						jQuery('#additem_form').append('<input type=\'hidden\' name=\'store_request\' value=\'Add Item\'/>');					
					}
					mainForm.submit();
				}
				return false;
			}
        </script>


<?php	if ($isPhysical) {	//physical items; show ILS search fields ?>

		<script type="text/javascript">
			function checkForm(frm)
			{
				var addTypeValue;
		
				for (i=0;i<frm.addType.length;i++) {
					if (frm.addType[i].checked==true)
						addTypeValue = frm.addType[i].value;
				}
		
				var alertMsg = '';
				if (frm.title.value == '') { alertMsg = alertMsg + 'Please enter a title.<br>' }
				if (addTypeValue == 'PERSONAL' && frm.selected_owner.value == '') { 
					alertMsg = alertMsg + 'Please select a personal owner.<br>'; 
				}
				
				if (alertMsg == '') {
					//submit form
					return true;
				} else {
					document.getElementById('alertMsg').innerHTML = alertMsg;				
					//do not submit form
					return false;
				}
			}
	
			//disables/enables ILS elements
			function toggleILS(enable)
			{
				var frm = document.getElementById('additem_form');
				var dspl;
				if (enable) {
					frm.searchTerm.disabled=false;
					frm.searchField.disabled=false;
					dspl = '';
				} else {
					frm.searchTerm.disabled=true;
					frm.searchField.disabled=true;				
					dspl = 'none';
				}
		
				document.getElementById('ils_search').style.display = dspl;
			}
		
			//shows/hides non-manual entry elements
			function toggleNonManual(show)
			{
				if (document.getElementById('nonman_local_control_row')) {
					if(show) {
						document.getElementById('nonman_local_control_row').style.display = '';
						document.getElementById('nonman_local_control_input').disabled = false;
					} else {
						document.getElementById('nonman_local_control_row').style.display = 'none';
						document.getElementById('nonman_local_control_input').disabled = true;
					}						
				}
				
				if (document.getElementById('man_local_control_row')) {
					if (show) {
						document.getElementById('man_local_control_row').style.display = 'none';
						document.getElementById('man_local_control_input').disabled = true;
					} else {
						document.getElementById('man_local_control_row').style.display = '';
						document.getElementById('man_local_control_input').disabled = false;
					}
				}
			}

		</script>
<?php 
		if(array_key_exists('success', $item_data) && !$item_data['success']) {
?>
		<div class="warning">The search you provided did not match any items in the ILS.</div>
<?php 
		} 
?>
		<div class="headingCell1" style="width:25%; text-align:center;">Item Source</div>
		<table width="100%" border="0" cellpadding="3" cellspacing="0" class="borders">
			<tr bgcolor="#CCCCCC">
				<td width="20%" align="left" valign="middle">
					<input name="addType" type="radio" value="SMITH_ITEM" onClick="toggleILS(1); togglePersonal(0,0); toggleNonManual(1);"<?php print($addType_select['euclid']); ?>>
					<span class="strong">Library owned copy</span>
				</td>
				<td width="40%" align="left" valign="top">
					<input type="radio" name="addType" value="PERSONAL" onclick="toggleILS(1); togglePersonal(1,1); toggleNonManual(1);"<?php print($addType_select['personal']); ?>>
					<span class="strong">Personal copy (library owns title)</span>
				</td>
				<td width="40%" align="left" valign="top">
					<input type="radio" name="addType" value="MANUAL"  onclick="toggleILS(0); togglePersonal(1, 0); toggleNonManual(0);">
					<span class="strong">Enter item manually (no library copy)</span>
				</td>
			</tr>
			<tr bgcolor="#CCCCCC" id="ils_search">
				<td colspan="2" align="left" valign="middle" bgcolor="#FFFFFF">
					<input id="searchTermFocus" name="searchTerm" type="text" size="15" value="<?php print($search_term); ?>">
					<select name="searchField">
						<option value="barcode"<?php print($barcode_select); ?>>Barcode</option>
						<option value="control"<?php print($control_select); ?>>Control Number</option>
					</select>
					 
					<input type="submit" value="Search" onclick="this.form.cmd.value='<?php print($cmd); ?>';" / >
				</td>
			</tr>
		</table>
		
<?php	} else if($isDigital) {	//digital item; show upload/url fields ?>

		<script type="text/javascript">
			function checkForm(frm) {
				var alertMsg = '';
				if (frm.title.value == '') { 
					alertMsg = alertMsg + 'Please enter a title.<br>';  
				}						
		
				/*if (frm.documentType[1].checked && frm.userFile.value == '')
					alertMsg = alertMsg + 'File path is required.<br>'; 
				
				if ((frm.documentType[0].checked ||  frm.documentType[2].checked)&& frm.url.value == '')
					alertMsg = alertMsg + 'URL is required.<br>'; 							
				*/
				if((frm.documentType.value == "DOCUMENT") && (frm.userFile.value == "")) {
					alertMsg = alertMsg + "You must choose a file to upload.<br />";
				} else if((frm.documentType.value == "URL") && (frm.url.value == "")) {
					alertMsg = alertMsg + "URL is required.<br />";
				}
				if (alertMsg == '') {
					//submit form
					return true;
				} else {
					document.getElementById('alertMsg').innerHTML = alertMsg;
					//do not submit form
					return false;
				}
			}

		</script>

		<div class="headingCell1" style="width:25%; text-align:center;">Search</div>
		<div class="borders" style="background-color:#CCCCCC; padding:5px;">
			<input type="hidden" name="searchField" value="control" />
			<strong>Barcode:</strong>
			<input type="text" name="searchTerm" value="<?php !empty($_REQUEST['searchTerm']) ? $_REQUEST['searchTerm'] : ''; ?>" />
			&nbsp; <input type="submit" value="Search" onclick="this.form.cmd.value='<?php print($cmd); ?>';" / >
		</div>
		<br />
		
		<div class="headingCell1" style="width:25%; text-align:center;">Item Source</div>
		
<?php	
			if(!empty($item_data['item_id']) && !empty($item_data['url'])){	//if editing digital item 
?>
		
		<script type="text/javascript">
			var currentItemSourceOptionID;
				
			function toggleItemSourceOptions(option_id)
			{
				if (document.getElementById(currentItemSourceOptionID)) {
					document.getElementById(currentItemSourceOptionID).style.display = 'none';
				}
				if (document.getElementById(option_id)) {
					document.getElementById(option_id).style.display = '';
				}
				currentItemSourceOptionID = option_id;
			}
		</script>
		
		<div id="item_source" class="borders" style="padding:8px 8px 12px 8px; background-color:#CCCCCC;">
			<div style="overflow:auto;" class="strong">
				Current URL <small>[<a href="reservesViewer.php?item=<?php print($item_data['item_id']); ?>" target="_blank">Preview</a>]</small>: 
<?php		if($item_data['is_local_file']){ //local file ?>
				Local File &ndash; <em><?php print($item_data['url']); ?></em>
<?php		} else { //remote file - show link to everyone ?>
				<em><?php print($item_data['url']); ?></em>
<?php		} ?>
			</div>
			<small>
				Please note that items stored on the ReservesDirect server are access-restricted; use the Preview link to view the item.
				<br />
				To overwrite this URL, use the options below.
			</small>
			<p />
			<div>
				<input type="radio" name="documentType" value="" checked="checked" onclick="toggleItemSourceOptions('');" /> Maintain current URL &nbsp;
				<input type="radio" name="documentType" value="DOCUMENT" onclick="toggleItemSourceOptions('item_source_upload');" /> Upload new file &nbsp;
				<input type="radio" name="documentType" value="URL" onclick="toggleItemSourceOptions('item_source_link');" /> Change URL
			</div>
			<div style="margin-left:40px;">
				<div id="item_source_upload" style="display:none;">
					<input type="file" name="userFile" size="50" />
				</div>
				<div id="item_source_link" style="display:none;">
					<input name="url" type="text" size="50" value="<?php print($item_data['url']); ?>"/>
					<input type="button" onclick="openNewWindow(this.form.url.value, 500);" value="Preview" />
				</div>
			</div>
		</div>


<?php	} else {	//new digital item ?>		

		<table width="100%" border="0" cellpadding="3" cellspacing="0" bgcolor="#CCCCCC" class="borders">
			<tr>
				<td align="left" colspan="2" valign="top"> <p class="strong">MATERIAL TYPE (Pick One):</p></td>
			</tr>
			<tr>
				<td align="left" valign="top">
					<font color="#FF0000"><strong>*</strong></font><input type="radio" name="documentType" value="DOCUMENT" checked onclick="this.form.userFile.disabled = !this.checked; this.form.url.disabled = !this.checked;">&nbsp;<span class="strong">Upload &gt;&gt;</span>
				</td>
				<td align="left" valign="top"><input type="file" name="userFile" size="40"></td>
			</tr>
			<tr>
				<td align="left" valign="top">
					<font color="#FF0000"><strong>*</strong></font><input type="radio" name="documentType" value="URL" onclick="this.form.url.disabled = !this.checked; this.form.userFile.disabled = this.checked;">
					<span class="strong">URL &gt;&gt;</span>
				</td>
				<td align="left" valign="top">
					<input name="url" type="text" size="50" DISABLED>
				</td>
			</tr>
			<tr>
				<td>&nbsp;</td>
				<td><font size='-2'>
					   http://www.reservesdirect.org<br/>
					   http://links.jstor.org/xxxxx<br/>
					   http://dx.doi.org/10.xxxxx
					</font>
				</td>
			</tr>
		</table>

<?php			
		} ?>
			
<?php } else if($isVideo){	//digital item; show upload/url fields ?>

		<script type="text/javascript">
			function checkForm(frm)
			{
				var alertMsg = '';
				if (frm.title.value == '') { 
					alertMsg = alertMsg + 'Please enter a title.<br>'; 
				}						
		
				if (frm.videoFile.value == '') {
					alertMsg = alertMsg + 'File path is required.<br>';
				}
				var length = frm.videoFile.value.length;
				var start = length - 4;
				var mpegStart = length - 5;
				var rmStart = length - 3;
				var mpeg = frm.videoFile.value.substring(mpegStart, length);
				var ext = frm.videoFile.value.substring(start, length);
				var rm = frm.videoFile.value.substring(rmStart, length);
				
				if (frm.author.value == '') {
					alertMsg = alertMsg + 'Writer/Director is required.<br>';
				}							
					
				switch (ext) {
					case ".flv":
					case ".3gp":
					case ".mp4":
					case ".mov":
					case ".asf":
					case ".mpg":
					case ".avi":
					case ".wmv":
					case ".dat":
					case ".m4v":
						alertMsg = alertMsg + '';
						break;
					default:
						if (mpeg == '.mpeg' || rm == '.rm') {
							alertMsg = alertMsg + '';
						} else {
							alertMsg = alertMsg + 'The file format is not a supported video format<br>';			
						}
						break;
				}
				if (alertMsg == '') {
					document.getElementById('alertMsg').innerHTML = "Your video is being uploaded.  This could take a few minutes";
					return true;
				} else {
					document.getElementById('alertMsg').innerHTML = alertMsg;
					//do not submit form
					return false;
				}
			}
		</script>
		
		<div class="borders" style="margin:10px; padding:10px; background:lightgreen; text-align:center">
		
		<p>The Course Reserves video service is designed to allow you to stream
		clips of video for students to access for course-related work. Please
		note the following guidelines:</p>
		<ul>
		<li>Like other material uploaded into My Course Reserves, video
		excerpts are reviewed for copyright compliance. Please review our
		video copyright guidelines.</li>
		<li>We can accept videos in many popular formats (*.flv, *.mpg, *.mov,
		*.avi, etc). </li>
		<li>Flash video (*.flv) will be posted immediately to your class and
		available to your students </li>
		<li>All other video formats will be converted to Flash before being
		made available to students. File conversion is automated; conversion
		times (time from when you upload video to when it is available to
		students) will vary based on how long the video is and how much video
		is waiting for conversion at any given time. Please be patient. You
		will receive emails when your video is accepted for processing and when it
		is complete and available for students to view</li>
		<li>Please be as complete as possible when filling in the descriptive
		fields below </li>
		</ul>
		</div>
		

		<div class="headingCell1" style="width:25%; text-align:center;">Upload Video</div>
				<table width="100%" border="0" cellpadding="3" cellspacing="0" bgcolor="#CCCCCC" class="borders">
			<tr>
				<td align="left" valign="top">
					<input type="hidden" name="documentType" value="VIDEO" />
					<font color="#FF0000"><strong>*</strong></font><span class="strong">Upload &gt;&gt;</span>
				</td>
				<td align="left" valign="top"><input type="file" name="videoFile" size="40"></td>
			</tr>
		</table>

			
<?php			
		} // back to the case of all types...
?>
		<br />
				
		<?php 
		
		$ils = Rd_Ils::initILS();
		if ($isPhysical && $ils->supportsReserveRecords() && empty($item_data['controlKey']) && !empty($item_data['item_id'])) { ?>
			<div id="alertMsg" class="failedText">
				This item is not properly linked to <?php print($g_catalogName); ?>.  Please update the <?php print($g_catalogName); ?> Control Number.
			</div>
		<?php } ?>		
		
		<?php if($isVideo){ ?>
		<div class="headingCell1" style="width:25%; text-align:center;">Video Details</div>
		<?php } else { ?>
  		<div class="headingCell1" style="width:25%; text-align:center;">Item Details</div>
  		<?php } ?>
		
		<table width="100%" border="0" cellpadding="3" cellspacing="0" class="borders">
			<tr align="left" valign="top" id="personal_item_row">
				<td width="20%" align="right" bgcolor="#CCCCCC" class="strong">
					<span id="personal_req_mark" style="color:#FF0000;">*</span>
					Personal Copy Owner:
					<br />&nbsp;
				</td>				
				<td>
<?php
	$personal_item_choice = array('no'=>'', 'yes'=>'');
	
	if(!is_null($selected_owner_id)) {
		$personal_item_choice['yes'] = ' checked="true"';
	}
	else {
		$personal_item_choice['no'] = ' checked="true"';
	}
?>
					<div id="personal_item_choice">
						<input type="radio" name="personal_item" id="personal_item_no" value="no"<?php print($personal_item_choice['no']); ?> onclick="togglePersonalOwner();" /> No
						&nbsp;&nbsp;
						<input type="radio" name="personal_item" id="personal_item_yes" value="Yes"<?php print($personal_item_choice['yes']); ?> onclick="togglePersonalOwner();" /> Yes
					</div>
					<div id="personal_item_owner_block">
					
<?php	if(!empty($selected_owner_id)){	//if there is an existing owner, give a choice of keeping him/her or picking a new one ?>

						<input type="radio" name="personal_item_owner" id="personal_item_owner_curr" value="old" checked="checked" onclick="togglePersonalOwnerSearch();" /> Current - <strong><?php print($selected_owner_name); ?></strong>
						<br />
						<input type="radio" name="personal_item_owner" id="personal_item_owner_new" value="new" onclick="togglePersonalOwnerSearch();" /> New &nbsp;
						
<?php	} else {	//if not, then just assume we are searching for a new one ?>

						<input type="hidden" name="personal_item_owner" id="personal_item_owner_new" value="new" />

<?php	} ?>

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
			<tr valign="middle">
				<?php if ($isVideo) { ?>
				<td  width="20%" align="right" bgcolor="#CCCCCC" class="strong"><font color="#FF0000"><strong>*</strong></font>Clip Title:</td>
				<?php } else { ?>
				<td  width="20%" align="right" bgcolor="#CCCCCC" class="strong"><font color="#FF0000"><strong>*</strong></font>Title:</td>
				<?php } ?>
				<td align="left"><input name="title" type="text" size="50" value="<?php print($item_data['title']); ?>"></td>
	
				
			</tr>
			<tr valign="middle">
				<?php if($isVideo){ ?>
				<td align="right" bgcolor="#CCCCCC" class="strong"><font color="#FF0000"><strong>*</strong></font>Writer/Director:</td>
				<?php } else { ?>
				<td align="right" bgcolor="#CCCCCC" class="strong"><font color="#FF0000"></font>Author/Composer :</td>
				<?php } ?>
				<td align="left"><input name="author" type="text" size="50" value="<?php print($item_data['author']); ?>"></td>
			</tr>
			<tr valign="middle">
				<?php if($isVideo){ ?>
				<td align="right" bgcolor="#CCCCCC"><span class="strong">Performer/Actor</span><span class="strong">:</span></td>
				<?php } else { ?>
				<td align="right" bgcolor="#CCCCCC"><span class="strong">Performer</span><span class="strong">:</span></td>
				<?php } ?>
				<td align="left"><input name="performer" type="text" size="50" value="<?php print($item_data['performer']); ?>"></td>
			</tr>
			<tr valign="middle">
				<?php if($isVideo){ ?>
				<td align="right" bgcolor="#CCCCCC"><span class="strong">Movie/Work Title:</span></td>				
				<?php } else { ?>
				<td align="right" bgcolor="#CCCCCC"><span class="strong">Book/Journal/Work Title:</span></td>
				<?php } ?>
				<td align="left"><input name="volume_title" type="text" size="50" value="<?php print($item_data['volume_title']); ?>">
			</td>
			<tr valign="middle">
				<?php if($isVideo){ ?>
				<td align="right" bgcolor="#CCCCCC"><div align="right"><span class="strong">Producer / Studio:</span></td>				
				<?php } else { ?>
				<td align="right" bgcolor="#CCCCCC"><div align="right"><span class="strong">Volume / Edition:</span></td>
				<?php } ?>
				<td align="left"><input name="volume_edition" type="text" size="50" value="<?php print($item_data['edition']); ?>"></td>
			</tr>
			<tr valign="middle">
				<?php if($isVideo) { ?>
				<td align="right" bgcolor="#CCCCCC"><span class="strong">Times:</span></td>
				<td align="left"><input name="times_pages" type="text" size="20" value="<?php print($item_data['times_pages']); ?>">&nbsp;to&nbsp;
				<input name="times_pages2" type="text" size="20" value="<?php print($item_data['times_pages2']); ?>"></td>
				<?php } else { ?>
				<td align="right" bgcolor="#CCCCCC"><span class="strong">Pages/Times:</span></td>
				<td align="left"><input name="times_pages" type="text" size="50" value="<?php print($item_data['times_pages']); ?>"></td>
					<?php if ($isDigital) { ?>
							<td><small>Example: pp. 336-371 and pp. 399-442 (78 of 719)</small></td> 
					<?php } ?>
				<?php } ?>
			</tr>
			<tr valign="middle">
				<?php if($isVideo){ ?>
				<td align="right" bgcolor="#CCCCCC"><span class="strong">Year:</span></td>				
				<?php } else { ?>
				<td align="right" bgcolor="#CCCCCC"><span class="strong">Source / Year:</span></td>
				<?php } ?>
				<td align="left"><input name="source" type="text" size="50" value="<?php print($item_data['source']); ?>"> </td>
			</tr>

<?php	if ($isDigital && !empty($doc_types)) {	//document icon/mime for digital items ?>
			<tr valign="middle">
				<td align="right" bgcolor="#CCCCCC"><span class="strong">Document Type Icon:</span></td>
				<td align="left">
					<select name="selectedDocIcon" onChange="document.iconImg.src = this[this.selectedIndex].value;">
<?php		foreach ($doc_types as $doc_type_info) { ?>
						<option value="<?php print($doc_type_info['helper_app_icon']); ?>"><?php print($doc_type_info['helper_app_name']); ?></option>
<?php		} ?>
					</select>
					<img name="iconImg" width="24" height="20" border="0" src="public/images/doc_type_icons/doctype-clear.gif">
				</td>
			</tr>		
<?php	} ?>

			<tr align="left" valign="middle">
				<td class="strong" align="right" bgcolor="#cccccc">ISBN:</td>
				<td><input name="ISBN" size="15" maxlength="15" value="<?php print($item_data['ISBN']); ?>" type="text"></td>
			</tr>
			<?php if (!$isVideo) { ?>
			<tr align="left" valign="middle">
				<td class="strong" align="right" bgcolor="#cccccc">ISSN:</td>
				<td><input name="ISSN" maxlength="15" size="15" value="<?php print($item_data['ISSN']); ?>" type="text"></td>
			</tr>
			<?php } ?>
			<tr align="left" valign="middle">
				<td class="strong" align="right" bgcolor="#cccccc">OCLC:</td>
				<td><input name="OCLC" maxlength="9" size="15" value="<?php print($item_data['OCLC']); ?>" type="text"></td>
			</tr>
			<tr align="left" valign="middle">
				<td class="strong" align="right" bgcolor="#cccccc">
				<?php	if($isPhysical){ ?>
					<?php print($g_catalogName); ?> Control Number:
				<?php } else { ?>
					Barcode / Alternate ID:
				<?php } ?>
				</td>
				<td><input type="text" name="local_control_key" value="<?php print($item_data['controlKey']);?>" /></td>
			</tr>			

			
<?php
			//show reserve-desk/home-library select box
			if(!empty($libraries)){
?>
			<tr align="left" valign="top">
				<td align="right" bgcolor="#CCCCCC" class="strong">Reserve Desk:</td>
				<td>
					<select name="home_library">				
<?php			
				foreach ($libraries as $lib) {
					$selected = ($lib->getLibraryID()==$item_data['home_library']) ? ' selected="selected"' : '';
?>
						<option value="<?php print($lib->getLibraryID()); ?>"<?php print($selected); ?>><?php print($lib->getLibrary());?></option>
<?php			} ?>
					</select>
			</td></tr>
<?php		
			}
			
			//give option to choose item type and to create euclid record
			$item_group_select = array('monograph'=>'', 'multimedia'=>'');
			if($item_data['item_group']=='MULTIMEDIA') {
				$item_group_select['multimedia'] = ' checked="true"';
			}
			else {
				$item_group_select['monograph'] = ' checked="true"';
			}
			
			
?>
<?php	if($isPhysical){	//auto-set item-group for electronic items ?>		
			<tr align="left" valign="bottom">
				<td align="right" bgcolor="#CCCCCC" class="strong">Item Type:</td>
				<td>
					<input type="radio" name="item_group" value="MONOGRAPH"<?php print($item_group_select['monograph']); ?> />Monograph
					&nbsp;<input type="radio" name="item_group" value="MULTIMEDIA"<?php print($item_group_select['multimedia']); ?> /> Multimedia
					<?php if('' != trim($item_data['controlKey'])) { ?><span style="margin-left:2em;"><?php 
						self::displayQuickviewLink($item_data['controlKey'], 'Check the item type in the catalog.'); 
					?></span> 
					<?php } else { ?>
						<span style="margin-left:2em;">No control key available for link to Quickview.</span>
					<?php } ?>
				</td>
			</tr>
<?php	} else if ($isVideo){ ?>
			<tr align="left" valign="bottom">
				<td align="right" bgcolor="#CCCCCC" class="strong"></td>
				<td>
					<input type="hidden" name="item_group" value="VIDEO" />	
				</td>
			</tr>
				
<?php	} else { ?>			
			<tr align="left" valign="bottom">
				<td align="right" bgcolor="#CCCCCC" class="strong"></td>
				<td>
					<input type="hidden" name="item_group" value="ELECTRONIC" />	
					<?php if('' != trim($item_data['controlKey'])) { ?><span style="margin-left:2em;"><?php 
						self::displayQuickviewLink($item_data['controlKey'], 'Check the item type in the catalog.'); 
					?></span> 
					<?php } else { ?>
						<span style="margin-left:2em;">No control key available for link to Quickview.</span>
					<?php } ?>
				</td>
			</tr>	
			
<?php	} ?>

		</table>
		
		<br />
		<?php if($isVideo) { ?>
		<div class="headingCell1" style="width:25%; text-align:center;">Video Notes</div>
		<?php } else { ?>
		<div class="headingCell1" style="width:25%; text-align:center;">Item Notes</div>
		<?php } ?>
		<div style="padding:8px 8px 12px 8px;" class="borders">
		
<?php	if(!empty($item_data['item_id'])){	//if editing existing item, use AJAX notes handler ?>

		<script language="JavaScript1.2" src="public/javascript/basicAJAX.js"></script>
		<script language="JavaScript1.2" src="public/javascript/notes_ajax.js"></script>
		
		<?php self::displayNotesBlockAJAX($item_data['notes'], 'item', $item_data['item_id'], true); ?>

<?php }	else {	//just display plain note form ?>

		<strong>Add a new note:</strong>
		<br />
        <textarea name="new_note" cols="50" rows="3"></textarea>
        <br />
        <small>Note Type:
        <label><input type="radio" name="new_note_type" value="<?php print($g_notetype['content']); ?>" checked="true">Content Note</label>
        <label><input type="radio" name="new_note_type" value="<?php print($g_notetype['staff']); ?>">Staff Note</label>
        <label><input type="radio" name="new_note_type" value="<?php print($g_notetype['copyright']); ?>">Copyright Note</label>

<?php	} ?>
				
		</div>
				
		<br />
		<strong><font color="#FF0000">* </font></strong><span class="helperText">= required fields</span></td></tr>

		<br />
		<div style="text-align:center;"><input type="submit" name="store_request" value="Add Item" onclick="handleQuirkyAjaxLibrary();"></div>
		
		</form>
		
		<script type="text/javascript">
<?php
		//if we are adding a physical item, we need to set the proper visibility defaults, based on type of item
		//we do this w/ jscript
		if($isPhysical){
?>
		//run some code to set up the form in the beginning
		var frm = document.getElementById('additem_form');
		var addTypeValue;
		
		for (i=0;i<frm.addType.length;i++) {
			if (frm.addType[i].checked==true)
				addTypeValue = frm.addType[i].value;
		}

		if( addTypeValue == 'MANUAL' ) {
			toggleILS(0);
			togglePersonal(1, 0);
			toggleNonManual(0);
		}
		else if( addTypeValue == 'PERSONAL' ) {
			toggleILS(1);
			togglePersonal(1, 1);
			toggleNonManual(1);
		}
		else {
			toggleILS(1);
			togglePersonal(0, 0);
			toggleNonManual(1);
		}

<?php }	else { ?>

		//run code to set up the form in the beginning
		togglePersonal(1, 0);

<?php	} ?>
	</script>
		
<?php
	}
	
	
	function addSuccessful($ci, $item_id, $reserve_id, $duplicate_link=false, $ils_results='') {
		$ci->getCourseForUser();
?>
		<div class="borders" style="padding:15px; width:50%; margin:auto;">
			<strong>Item was successfully added to </strong><span class="successText"><?php print($ci->course->displayCourseNo()); ?> <?php print($ci->course->getName());?></span>		
<?php	if(!empty($ils_results)):	//show ILS record creation results ?>
				<br />
				<br />
				<div style="margin-left:20px;">
					<strong>ILS query results:</strong>
					<div style="margin-left:20px;">
						<?php print($ils_results); ?>
					</div>
				</div>
<?php	endif; ?>
			<br />
			<ul>
				<li><a href="index.php?cmd=storeRequest&amp;item_id=<?php print($item_id); ?>">Add this item to another class</a></li>				
<?php	if($duplicate_link): ?>
				<li><a href="index.php?cmd=duplicateReserve&amp;reserveID=<?php print($reserve_id); ?>">Duplicate this item and add copy to the same class</a></li>
<?php	endif; ?>				
				<li><a href="index.php?cmd=editClass&ci=<?php print($ci->getCourseInstanceID()); ?>"> Go to class</a></li>
				<li><a href="index.php?cmd=addPhysicalItem">Add another physical item</a></li>
				<li><a href="index.php?cmd=addDigitalItem">Add another electronic item</a></li>
				<li><a href="index.php?cmd=displayRequest">Return to the Requests Queue</a></li>
			</ul>	
		</div>
<?php
	}
	
	
	/**
	 * Displays list of possible CIs for the item
	 *
	 * @param array $all_possible_CIs = array(
						 * 	'rd_requests' => array(ci1-id, ci2-id, ...),
						 * 	'ils_requests => array(
						 * 		user-id1 = array(
						 * 			'requests' => array(ils-request-id1, ils-request-id2, ...),
						 * 			'ci_list' => array(ci1-id, ci2-id, ...)
						 * 		),
						 * 		user-id2 = ...
						 *	)
						 * )
	 * @param array $selected_CIs = array(ci1_id, ci2_id, ...)
	 * @param array $CI_request_matches = array(
						 * 	ci1-id => array(
						 * 		'rd_request' => rd-req-id,
						 * 		'ils_requests' => array(
						 * 			ils-req1-id => ils-req1-period,
						 * 			ils-req2-id...
						 * 		)
						 * 	),
						 * 	ci2-id = ...
						 * )
	 * @param string $requested_barcode (optional) If searched for physical item, this is the barcode matching the exact copy searched
	 */
	function displaySelectCIForItem($item_id, $all_possible_CIs, $selected_CIs, $CI_request_matches, $requested_barcode=null) {
		//get holding info for physical items
		$item = new reserveItem($item_id);
		if($item->isPhysicalItem()) {
			$zQry = RD_Ils::initILS();
			$holdingInfo = $zQry->getHoldings($item->getLocalControlKey(), 'control'); /*TODO this should be called in the manager... */
			$selected_barcode = $requested_barcode;
		}
		else {
			$holdingInfo = null;
			$selected_barcode = null;
		}
				
		//circ rules
		$circRules = new circRules();
?>
		<script type="text/javascript" language="JavaScript1.2" src="public/javascript/basicAJAX.js"></script>
		<script type="text/javascript" language="JavaScript1.2" src="public/javascript/request_ajax.js"></script>
				
		<script type="text/javascript">
			var current_form_block_id;
			
			function toggle_request_form(block_id) {
				//hide old selection
				if(document.getElementById(current_form_block_id)) {
					document.getElementById(current_form_block_id).style.display = 'none';
				}
				//show new selection
				if(document.getElementById(block_id)) {
					document.getElementById(block_id).style.display = '';
					//save new selection
					current_form_block_id = block_id;
				}				
			}
		</script>

<?php	
		//the way possible destination courses are displayed depends on request type		
		if(!empty($all_possible_CIs)):
			foreach($all_possible_CIs as $request_type=>$ci_data):
				//for RD requests, just show a simple header
				if($request_type == 'rd_requests'):
?>
		<br />
		<div class="headingCell1" style="width:30%">ReservesDirect courses requesting this item:</div>
		
<?php			elseif($request_type == 'ils_requests'): //for ILS requests, show a different header ?>

		<br />
		<div class="headingCell1" style="width:30%">ILS requests:</div>
		
<?php			endif; ?>

			<div class="headingCell1">
				<div style="width:60px; text-align:left; float:left;">&nbsp;</div>
				<div style="width:15%; text-align:left; float:left;">Course Number</div>
				<div style="width:30%; text-align:left; float:left;">Course Name</div>
				<div style="width:25%; text-align:left; float:left;">Instructor(s)</div>
				<div style="width:14%; text-align:left; float:left;">Term</div>
				<div style="width:55px; text-align:left; float:right; padding-right:5px;">Preview</div>
				<div style="clear:both;"></div>
			</div>
	
<?php
				if($request_type == 'rd_requests') {
					//the ci-data is the array of CIs
					//show those
					$selected_CIs = array(array_key_exists('ci', $_REQUEST) ? $_REQUEST['ci'] : '');
					self::displayCoursesForRequest($item_id, $ci_data, $selected_CIs, $CI_request_matches, $circRules, $holdingInfo, $selected_barcode);
				}
				elseif($request_type == 'ils_requests') {
					foreach($ci_data as $user_id=>$request_data) {
						//get instructor's name
						$instructor = new user($user_id);
						$instructor_name = $instructor->getName(false);
						
						//get a list of ILS courses requesting this item
						$ils_courses_string = '';
						foreach($request_data['requests'] as $ils_request_id) {
							//init ils request object
							$ils_request = new ILS_Request($ils_request_id);
							
							//add name to string
							$ils_courses_string .= '"<em>'.$ils_request->getCourseName().'</em>", ';
						}
						$ils_courses_string = rtrim($ils_courses_string, ', ');	//trim off the last comma
						
						//display header
?>
			<div style="padding:5px; border:1px solid black; background-color:#DFD8C6;">Item requested by <em><?php print($instructor_name); ?></em> for <em><?php print($ils_courses_string); ?></em></div>
<?php
						//display course list
						$selected_CIs = array($_REQUEST['ci']);
						self::displayCoursesForRequest($item_id, $request_data['ci_list'], $selected_CIs, $CI_request_matches, $circRules, $holdingInfo, $selected_barcode);
					}
				}
				
			endforeach;
?>			
		<p>
			<img src="public/images/astx-green.gif" alt="selected" width="15" height="15"> <span style="font-size:small;">= course requested this item</span> &nbsp;
			<img src="public/images/pencil.gif" width="24" height="20" /> <span style="font-size:small;">= active courses</span> &nbsp;
			<img src="public/images/activate.gif" width="24" height="20" /> <span style="font-size:small;">= new courses not yet in use</span> &nbsp;
			<img src="public/images/cancel.gif" width="24" height="20" /> <span style="font-size:small;">= courses canceled by the registrar</span> &nbsp;
		</p>
		<br />
		<br />

		<script type="text/javascript">
			request_ajaxify_forms();
		</script>
		
<?php	
		endif;
		
		//display ajax selectClass
		$mgr = new ajaxManager('lookupClass', 'storeRequest', 'addReserve', 'Continue', array('item_id'=>$item_id));
		$mgr->display();
	}
	
	
	/**
	 * Displays a list of CIs, along with special forms to submit ci-item combo for request
	 *
	 * @param unknown_type $course_instance_ids
	 * @param unknown_type $selected_CIs
	 * @param unknown_type $ci_request_matches
	 * @param unknown_type $propagated_data
	 * @param unknown_type $circRules
	 * @param unknown_type $holdingInfo
	 * @param unknown_type $selected_barcode
	 */
	static function displayCoursesForRequest($item_id, $course_instance_ids, $selected_CIs, $ci_request_matches, $circRules, $holdingInfo=null, $selected_barcode) {
		if (0 == count($course_instance_ids)) {
?>
		<div style="border:1px solid #666666;background-color:#fff;padding:1em;"> No courses currently requesting this item. </div>
<?php 			
			return;
		}

?>
		<div style="border-bottom:1px solid #666666;">		
<?php
		foreach ($course_instance_ids as $ci_id) {
			$ci = new courseInstance($ci_id);
			$ci->getCourseForUser();	//fetch the course object
			$ci->getInstructors();	//get a list of instructors
			
			//get crosslistings
			try {
				$crosslistings = $ci->getCrossListings();
				$crosslistings_string = '';
				foreach($crosslistings as $crosslisting) {
					$crosslistings_string .= ', '.$crosslisting->displayCourseNo().' '.$crosslisting->getName();
				}
				$crosslistings_string = ltrim($crosslistings_string, ', ');	//trim off the first comma
			} catch (Rd_Exception $e){
				if(410 == $e->getCode()) {
					$crosslistings = array();
					$crosslistings_string = '';
				} else {
					throw $e;	
				}
			}
	
			//see if there are request matches
			$requests = !empty($ci_request_matches[$ci->getCourseInstanceID()]) ? $ci_request_matches[$ci->getCourseInstanceID()] : null;
			
			//show status icon
			switch($ci->getStatus()) {
				case 'AUTOFEED':
					$edit_icon = '<img src="public/images/activate.gif" width="24" height="20" />';	//show the 'activate-me' icon
				break;
				case 'CANCELED':
					$edit_icon = '<img src="public/images/cancel.gif" alt="edit" width="24" height="20">';	//show the 'canceled' icon
				break;
				default:
					$edit_icon = '<img src="public/images/pencil.gif" alt="edit" width="24" height="20">';	//show the edit icon
				break;						
			}			
						
			$pre_select_ci_radio = '';
			//mark pre-selected courses
			if(in_array($ci->getCourseInstanceID(), $selected_CIs)) {
				$selected_img = '<img src="public/images/astx-green.gif" alt="selected" width="15" height="15">&nbsp;';
				if (sizeof($selected_CIs) == 1) 
				{
					//only one CI selected go ahead and select the radio button
					$pre_select_ci_radio = ' checked="CHECKED" ';
					$force_toggle = "<script language='JavaScript'>toggle_request_form('add_".$ci->getCourseInstanceID()."');</script>";
				} else {
                    $force_toggle ='';
                }
			} else {
				$selected_img = '';
                $force_toggle ='';
			}
						
			//display row
			$rowStyle = (empty($rowStyle) || ($rowStyle=='evenRow')) ? 'oddRow' : 'evenRow';	//set the style
			$rowStyle2 = (empty($rowStyle2) || ($rowStyle2=='oddRow')) ? 'evenRow' : 'oddRow';	//set the style
?>									
			<div class="<?php print($rowStyle); ?>" style="padding:5px;">					
				<div style="width: 30px; float:left; text-align:left;"><input id="select_ci_<?php print($ci->getCourseInstanceID()); ?>" name="ci" type="radio" value="<?php print($ci->getCourseInstanceID());?>" onclick="javascript: toggle_request_form('add_<?php print($ci->getCourseInstanceID());?>');" <?php print($pre_select_ci_radio); ?>/></div>
				<div style="width: 50px; float:left; text-align:left"><?php print($selected_img.$edit_icon); ?></div>
				<div style="width:15%; float:left;"><?php print($ci->course->displayCourseNo()); ?>&nbsp;</div>
				<div style="width:30%; float:left;"><?php print($ci->course->getName()); ?>&nbsp;</div>
				<div style="width:25%; float:left;"><?php print($ci->displayInstructors()); ?>&nbsp;</div>
				<div style="width:14%; float:left;"><?php print($ci->displayTerm()); ?>&nbsp;</div>
				<div style="width:55px; float:right;"><a href="javascript:openWindow('no_control=1&cmd=previewReservesList&ci=<?php print($ci->getCourseInstanceID()); ?>','width=800,height=600');">preview</a></div>
				<div style="clear:both;"></div>
<?php		if(!empty($crosslistings_string)): ?>
				<div style=" margin-left:30px; padding-top:5px;"><em>Crosslisted As:</em> <small><?php print($crosslistings_string); ?></small></div>
<?php		endif; ?>

				<div id="add_<?php print($ci->getCourseInstanceID()); ?>" style="display:none;">
					<?php self::displayCreateReserveForm($ci, $item_id, $circRules, $holdingInfo, $requests, $selected_barcode, $rowStyle2) ?>
				</div>
			</div>
		
<?php	
			print($force_toggle);
		}   ?>		
		</div>

<?php
	}
	
	
	/**
	 * Displays create-reserve/process-request form for the given ci and item
	 *
	 * @param unknown_type $ci
	 * @param unknown_type $item_id
	 * @param unknown_type $circRules
	 * @param unknown_type $holdingInfo
	 * @param unknown_type $requests
	 	 * $requests = array(
		 * 	ci1-id => array(
		 * 		'rd_request' => rd-req-id,
		 * 		'ils_requests' => array(
		 * 			ils-req1-id => ils-req1-period,
		 * 			ils-req2-id...
		 * 		)
		 * 	),
		 * 	ci2-id = ...
		 * )
	 * @param unknown_type $selected_barcode
	 * @param unknown_type $rowStyle
	 */
	static function displayCreateReserveForm($ci, $item_id, $circRules, $holdingInfo=null, $requests=null, $selected_barcode=null, $rowStyle='') {
		global $calendar;
		
		$item = new reserveItem($item_id);
?>
		<form name="create_reserve_form" method="post" action="index.php">
					<input type="hidden" name="cmd" value="storeRequest" />
					<input type="hidden" name="ci" value="<?php print($ci->getCourseInstanceID());?>" />
					<input type="hidden" name="item_id" value="<?php print($item_id); ?>" />
<?php
			//need to pass on request info (which requests are fullfilled by this item-ci combo)
			if(!empty($requests)) {
				//pass on RD request ID
				if(!empty($requests['rd_request'])) {
?>
					<input type="hidden" name="rd_request" value="<?php print($requests['rd_request']); ?>" />
<?php
				}
			
				if(!empty($requests['ils_requests'])) {
					foreach($requests['ils_requests'] as $ils_request_id=>$ils_requested_loan_period) {
?>
					<input type="hidden" name="ils_requests[]" value="<?php print($ils_request_id); ?>" />
<?php
					}
				}
			}
			if(!empty($ci_request_matches)) {
				if(!empty($ci_request_matches['rd_requests'])) {
					self::displayHiddenFields($ci_request_matches['rd_requests']);
				}
				foreach($ci_request_matches as $ci_request_match) {
					self::displayHiddenFields($ci_request_match);
				}
			}
			self::displayHiddenFields($propagated_data);
?>
					<br />
					<table width="90%" border="0" cellpadding="3" cellspacing="0" class="borders <?php print($rowStyle); ?>" align="center">
						<tr>
							<td width="15%">&nbsp;</td>
							<td><br /><strong>Please enter reserve information for this course:</strong><br />&nbsp;</td>
						</tr>
						<tr>
							<td align="right"><strong>Set Status:</strong></td>
							<td>
								<input type="radio" name="reserve_status" id="reserve_status_active_<?php print($ci->getCourseInstanceID()); ?>" value="ACTIVE" checked="true" />&nbsp;<span class="active">ACTIVE</span>
								<input type="radio" name="reserve_status" id="reserve_status_inactive_<?php print($ci->getCourseInstanceID()); ?>" value="INACTIVE" />&nbsp;<span class="inactive">INACTIVE</span>
							</td>
						</tr>
						<tr>
							<td align="right"><strong>Active Dates:</strong></td>
							<td>
								<input type="text" id="reserve_activation_date_<?php print($ci->getCourseInstanceID()); ?>" name="reserve_activation_date" size="10" maxlength="10" value="<?php print($ci->getActivationDate()); ?>" style="margin-top:5px;" /> <?php print($calendar->getWidgetAndTrigger('reserve_activation_date_'.$ci->getCourseInstanceID(), $ci->getActivationDate())); ?> to <input type="text" id="reserve_expiration_date_<?php print($ci->getCourseInstanceID()); ?>" name="reserve_expiration_date" size="10" maxlength="10" value="<?php print($ci->getExpirationDate()); ?>" />  <?php print($calendar->getWidgetAndTrigger('reserve_expiration_date_'.$ci->getCourseInstanceID(), $ci->getExpirationDate())); ?>(YYYY-MM-DD)
							</td>
						</tr>
<?php		if($item->isPhysicalItem()): //the rest is only needed for physical items ?>						
<?php			
				$ils = Rd_Ils::initILS();
				if(!empty($holdingInfo) && $ils->supportsReserveRecords()):	//have holding info, show physical copies ?>
						<tr>
							<td>&nbsp;</td>
							<td>
								<br />
								<span class="helperText">Below is a list of copies available through Sirsi.  <u>Select copies for which you wish to create a Sirsi 'on-reserve' record.</u>  Your selection(s) will have no impact on the Course Reserves reserves list.</span>
							</td>
						</tr>
						<tr>
							<td align="right"><strong>ILS Record:</strong></td>
							<td>
								<input type="checkbox" name="create_ils_record" value="yes" CHECKED />
								Create <?php print($ils->getName()); ?> Reserve Record
							</td>
						</tr>
						<tr>
							<td align="right"><strong>Requested Loan Period:</strong></td>
							<td><?php print( array_key_exists('loan_period', $_REQUEST) ? $_REQUEST['loan_period'] : ''); ?></td>
						</tr>
						<tr>
							<td align="right"><strong>Loan Period:</strong></td>
							<td>
								<select id="circRule_<?php print($ci->getCourseInstanceID()); ?>" name="circRule">
<?php		
				foreach($circRules->getCircRules() as $circRule):
					$rule = base64_encode(serialize($circRule));
					$display_rule = $circRule['circRule']." -- " . $circRule['alt_circRule'];
					$selected = $circRule['default'];
?>
									<option value="<?php print($rule); ?>" <?php print($selected); ?>><?php print($display_rule); ?></option>
<?php			endforeach; ?>
								</select>
<?php			if(!empty($requests['ils_requests'])):	//try to grab a requested loan period out of ils-requests data ?>
								&nbsp;(Requested loan period: <?php print(array_shift($requests['ils_requests'])); ?>)
<?php			endif; ?>
							</td>
						</tr>
						<tr>
							<td align="right" valign="top"><strong>Select Copy:</strong></td>
							<td>
<?php			
					foreach($holdingInfo as $phys_copy):
						$selected = ($phys_copy['bar'] == $selected_barcode) ? 'checked="checked"': '';
?>
						<input type="checkbox" name="physical_copy[]" value="<?php print(base64_encode(serialize($phys_copy))); ?>" <?php print($selected);?> />
						&nbsp;<?php print($phys_copy['type']); ?> | <?php print($phys_copy['library']); ?> | <?php print($phys_copy['loc']); ?> | <?php print($phys_copy['callNum']); ?> | <?php print($phys_copy['bar']); ?>
						<br />
<?php				endforeach; ?>
							</td>
						</tr>
<?php			
				endif;
			endif;
?>
						<tr>
							<td colspan="2" align="center">
								<br />
								<input type="submit" id="submit_store_item_<?php print($ci->getCourseInstanceID()); ?>" name="submit_store_item" value="Add Item to Class" style="margin-top:5px;" />
							</td>
						</tr>
					</table>					
				</form>
<?php
	}	
	
	public function addVideoItem($itemData, $ci) 
	{
		$u = Rd_Registry::get('root:userInterface');
		$itemData['times_pages2'] = null;
		if (array_key_exists('times_pages', $itemData)) {
			$times = explode(" to ", $itemData['times_pages']);
			$itemData['times_pages'] = $times[0];
			$itemData['times_pages2'] = $times[1];
		}

		$doc_types = $u->getAllDocTypeIcons();
		
		//private user
		if (array_key_exists('selected_owner', $itemData)) {
			//get id
			$selectedOwnerId = $itemData['selected_owner'];
			$tmpUser = new user($selectedOwnerId);
			//get name
			$selectedOwnerName = $tmpUser->getName().' ('.$tmpUser->getUsername().')';
			unset($tmpUser);
		} else {
			$selectedOwnerName = '';
		}
		
		//deal with physical item source pre-select
		$addTypeSelect = array('euclid'=>'', 'personal'=>'');
		$existingFiles = Queue_Encoding::getUnassigned($u->getId());
		foreach ($existingFiles as $index=>$row) {
			$existingFiles[$index]['original_filename'] = "Use existing file, {$row['original_filename']}";
		}
		$model = array(
			'itemId' => false,
			'loanPeriod' => (
				array_key_exists('loan_period', $itemData) 
				? $itemData['loan_period'] 
				: ''
			),
			'personalItem' => !empty($selectedOwnerId),
			'selectedOwnerName' => $selectedOwnerName,
			'videoUrl' => '',
			'volumeTitle' => '',
			'author' => '',
			'performer' => '',
			'volumeTitle' => '',
			'source' => '',
			'timeMinutes' => '',
			'timeSeconds' => '',
			'volumeEdition' => '', 
			'isbn' => '',
			'issn' => '',
			'oclc' => '',
			'barcode' => '',
			'notes' => (
				array_key_exists('notes',$itemData) 
				? $itemData['notes'] 
				: array()
			),
			'hasUnclaimedUploads' => Queue_Encoding::userHasUnassigned($u->getId()),
			'fileList' => $existingFiles
		);
		$this->display('videoUploadForm', $model);
	}
	
	public function displayVideoUploadScripts()
	{
		$this->printStatic('videoUpload.js');	
	}
	
	public function displayVideoUploadDirections()
	{
		$this->printStatic('videoUploadDirections.html');	
	}
}

