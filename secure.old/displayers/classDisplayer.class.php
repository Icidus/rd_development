<?php
/*******************************************************************************
classDisplayer.class.php

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
require_once(APPLICATION_PATH . '/common.inc.php');
require_once(APPLICATION_PATH . '/classes/terms.class.php');
require_once(APPLICATION_PATH . '/classes/tree.class.php');
require_once(APPLICATION_PATH . '/managers/ajaxManager.class.php');
require_once(APPLICATION_PATH . '/managers/lookupManager.class.php');
require_once(APPLICATION_PATH . '/lib/Rd/Displayer/Base.php');

class classDisplayer extends Rd_Displayer_Base {

	protected $_displayerName = 'class';
	
	function displayStaffHome($user)
	{
		$model = array(
			'user' => $user
		);
		$this->display('index', $model);
	}

	/**
	 * @return void
	 * @param array $student_CIs Reference to an array of CI objects this user is enrolled in
	 * @param array $intructor_CIs Reference to an array of CI objects this user is teaching
	 * @param array $proxy_CIs Reference to an array of CI objects this user is proxying
	 * @desc Display the user's courses
	 */
	public function displayCourseList($studentCiList, $instructorCiList, $proxyCiList) {
		$model = $this->_getDefaultModel();
		$termsObj = new terms();
		$terms = array();
		$termBlocksString = '';
		$currentTerm = $termsObj->getCurrentTerm();
		$model['studentCis'] = $studentCiList;
		
		//the idea is to separate instructor/proxy lists by term
		//and also order those terms (according to their sort order)
		$instructorCiArray = array();
		$proxyCiArray = array();
		foreach($termsObj->getTerms(true) as $term) {
			//rearrange the term info as Array[year][term] = term_obj_id to quickly index it by CI-year/term
			$terms[$term->getTermYear()][$term->getTermName()] = $term->getTermID();
			//also initialize these arrays, so that the term arrays are in proper order
			$instructorCiArray[$term->getTermID()] = array();
			$proxyCiArray[$term->getTermID()] = array();
		}	
		//put CIs in sub-arrays indexed by term_id
		foreach($instructorCiList as $ciStatus=>$ciList) {	//instructor courses
			foreach($ciList as $ci) {
				$instructorCiArray[$terms[$ci->year][$ci->term]][$ciStatus][] = $ci;
			}
		}
		//the process used above to put all the terms in the correct order may have created a bunch of empty arrays
		//go through instructor/proxy arrays again, removing empty term/status arrays
		//instructor first
		$model['instructorCis'] = array();
		foreach($instructorCiArray as $termId=>$statusCiArrays) {
			if(
				(is_array($statusCiArrays) && count($statusCiArrays) > 0)
				|| $termId == $currentTerm->getTermID()
			) {
				$model['instructorCis'][$termId] = array();
			}
			foreach($statusCiArrays as $status=>$ciArray) {
				if(!empty($ciArray)) {
					$model['instructorCis'][$termId][$status] = $ciArray;
				}
			}
		}
		
		$model['hasProxyCis'] = count($proxyCiList) > 0;
		foreach($proxyCiList as $ci) {	//proxy courses
			$proxyCiArray[$terms[$ci->year][$ci->term]][] = $ci;
		}
		//repeat for proxy
		$model['proxyCis'] = array();
		foreach($proxyCiArray as $termId=>$ciArray) {
			if(				
				(is_array($ciArray) && count($ciArray) > 0)
				|| $termId == $currentTerm->getTermID()
			) {
				$model['proxyCis'][$termId] = $ciArray;
			}
		}
		//$model['instructorCiKeys'] array_keys($model['instructorCis']);
		//default to current term if no classes exist for the current term default to last term in list this could be a past term			
		$instructorCiKeys = array_keys($instructorCiArray);
		$proxyCiKeys = array_keys($proxyCiArray);
		$model['instructorSelectTerm'] = 
			(array_key_exists($termsObj->getCurrentTerm()->getTermID(), $instructorCiArray)) 
			? $termsObj->getCurrentTerm()->getTermID() 
			: end($instructorCiKeys); 
		$model['proxySelectTerm'] =
			(array_key_exists($termsObj->getCurrentTerm()->getTermID(), $proxyCiArray)) 
			? $termsObj->getCurrentTerm()->getTermID() 
			: end($proxyCiKeys);
				
		$this->display('courseList', $model);
	}
	
	static function displayEditClassHeader(&$ci, $next_cmd, $show_quicklinks_box=true) {
		global $g_permission, $calendar;
		$u = Account_Rd::getUserInterface();
		//grab all the necessary info
		$ci->getCourseForUser();
		$ci->getPrimaryCourse();
		try{
			$crosslistings = $ci->getCrossListings();
		} catch (Rd_Exception $e){
			$crosslistings = array();
			print("<h2>An error occured attempting to load crosslistings on this course. Editing this course is not recommended, please contact circulation staff for assistance.</h2>");
		}
		$instructors = $ci->getInstructors();
		$proxies = $ci->getProxies();

		//build crosslistings display string
		$crosslistings_string = '';
		if(empty($crosslistings)) {
			$crosslistings_string = 'None';
		} else {
			foreach($crosslistings as $crosslisting) {
				$crosslistings_string .= ', '.$crosslisting->displayCourseNo().' '.$crosslisting->getName();
			}
			$crosslistings_string = ltrim($crosslistings_string, ', ');	//trim off the first comma
		}
		
		//build instructors display string; assume there is at least one
		$instructors_string = '';
		foreach($instructors as $instructor) {
			$instructors_string .= ', <a href="mailto:'.$instructor->getEmail().'">'.$instructor->getName(false).'</a>';
		}
		$instructors_string = ltrim($instructors_string, ', ');	//trim off the first comma
		
		//build proxies display string
		$proxies_string = '';
		if(empty($proxies)) {
			$proxies_string = 'None';
		}
		else {
			foreach($proxies as $proxy) {
				$proxies_string .= ', '.$proxy->getName(false);
			}
			$proxies_string = ltrim($proxies_string, ', ');	//trim off the first comma
		}
		
?>
		<div style="text-align:right;"><strong><a href="index.php">Exit class</a></strong></div>
		<p />
		
		<div>			
			<div id="courseInfo">
				<div class="courseTitle"><?php print($ci->course->displayCourseNo() . " " . $ci->course->getName()); ?>&nbsp;
					<?php if (Account_Rd::atLeastFaculty()) { ?>
						<small>[ <a href="index.php?cmd=editTitle&amp;ci=<?php print($ci->getCourseInstanceID()); ?>" class="editlinks">edit</a> ]</small>
					<?php } ?>
				</div>
				<div class="courseHeaders"><span class="label"><?php print($ci->displayTerm()); ?></span></div>
			
				<div class="courseHeaders">
					<span class="label">Cross-listings&nbsp;</span>
					<?php if (Account_Rd::atLeastFaculty()) { ?>
						<small>[ <a href="index.php?cmd=editCrossListings&ci=<?php print($ci->getCourseInstanceID()); ?>" class="editlinks">edit</a> ]</small>
					<?php } ?>
					: <?php print($crosslistings_string); ?>
				</div>
				
				<div class="courseHeaders"><span class="label">Instructor(s)&nbsp;
					<?php if (Account_Rd::atLeastFaculty()) { ?>
						<small></span>[ <a href="index.php?cmd=editInstructors&ci=<?php print($ci->getCourseInstanceID()); ?>" class="editlinks">edit</a> ]</small>
					<?php } ?>
					: <?php print($instructors_string); ?>
				</div>
				
				<div class="courseHeaders"><span class="label">Proxies&nbsp;</span>
					<?php if (Account_Rd::atLeastFaculty()) { ?>
						<small>[ <a href="index.php?cmd=editProxies&ci=<?php print($ci->getCourseInstanceID()); ?>" class="editlinks">edit</a> ]</small>
					<?php } ?>
					: <?php print($proxies_string); ?>
				</div>
				
				<div class="courseHeaders"><span class="label">Enrollment&nbsp;</span><small>[ <a href="index.php?cmd=<?php print($next_cmd); ?>&amp;ci=<?php print($ci->getCourseInstanceID()); ?>&amp;tab=enrollment" class="editlinks">view</a> ]</small>: <span class="<?php print(common_getEnrollmentStyleTag($ci->getEnrollment())); ?>"><?php print(strtoupper($ci->getEnrollment())); ?></span></div>
				<div class="courseHeaders">
<?php	if($u->getRole() >= $g_permission['staff']): 	//hide activate/deactivate dates from non-staff ?>				
					<form name="change_status_form" action="index.php" method="post">
						<input type="hidden" name="cmd" value="<?php print($next_cmd); ?>" />
						<input type="hidden" name="ci" value="<?php print($ci->getCourseInstanceID()); ?>" />
						
						<span class="label">Class Status</span>:
						<input type="radio" name="status" value="ACTIVE" <?php echo ($ci->getStatus()=='ACTIVE') ? 'checked="true"' : 'moo'; ?> /> <span class="<?php print(common_getStatusStyleTag('ACTIVE')); ?>">ACTIVE</span>
						<input type="radio" name="status" value="INACTIVE" <?php echo (($ci->getStatus()=='INACTIVE') || ($ci->getStatus()=='AUTOFEED')) ? 'checked="true"' : ''; ?> /> <span class="<?php print(common_getStatusStyleTag('INACTIVE')); ?>">INACTIVE</span>
<?php 		if($ci->getStatus()=='AUTOFEED'): ?>
						(Added by Registrar)
<?php 		endif; ?>

<?php		if($ci->getStatus()=='CANCELED'): ?>
						<input type="radio" name="status" disabled="true" checked="true" /> <span class="inprocess">CANCELED BY REGISTRAR</span>
<?php		endif; ?>

						<input type="submit" name="updateClassStatus" value="Change Status">
					</form>				
					<p />
					<form name="change_dates_form" action="index.php" method="post">
						<input type="hidden" name="cmd" value="<?php print($next_cmd); ?>" />
						<input type="hidden" name="ci" value="<?php print($ci->getCourseInstanceID()); ?>" />
						<span class="label">Class Active Dates</span>: <input type="text" id="activation" name="activation" size="10" maxlength="10" value="<?php print($ci->getActivationDate()); ?>" /> <?php print($calendar->getWidgetAndTrigger('activation', $ci->getActivationDate())); ?> to <input type="text" id="expiration" name="expiration" size="10" maxlength="10" value="<?php print($ci->getExpirationDate()); ?>" /> <?php print($calendar->getWidgetAndTrigger('expiration', $ci->getExpirationDate())); ?> <input type="submit" name="updateClassDates" value="Change Dates">
					</form>
<?php	else: ?>
					<span class="label">Class Status</span>: <span class="<?php print(common_getStatusStyleTag($ci->getStatus())); ?>"><?php print($ci->getStatus()); ?></span>
					<?php if ($ci->getStatus() <> 'ACTIVE'): ?> <i>Please contact your reserves desk to Activate this class </i> <?php	endif; ?>
<?php	endif; ?>
				</div>				
			</div>			

<?php	if($show_quicklinks_box): ?>
			<div id="courseActions">
				<script language="JavaScript">
					function submit_tsv_export_form() {
						if(document.getElementById('tsv_export_form')) {
							document.getElementById('tsv_export_form').submit();
						}
						return false;
					}
				</script>
				<ul>
					<li><a href="javascript:openWindow('no_control=1&cmd=previewStudentView&amp;ci=<?php print($ci->getCourseInstanceID()); ?>','width=800,height=600');">Preview Student View</a></li>
					<li><a href="index.php?cmd=exportClass&amp;ci=<?php print($ci->getCourseInstanceID()); ?>">Export readings to Courseware</a></li>
					<li><a href="#" onclick="return submit_tsv_export_form();">Export class to Spreadsheet</a></li>
				</ul>
			</div>
<?php	endif; ?>
		
		</div>		
		<div class="clear"></div>

		<?php $color = ($ci->reviewed() ? 'green' : 'red'); ?> 
		<div class="courseHeaders">
			<form method="POST" action="index.php">
			<span class="label">Copyright</span>: <span style="color: <?php print($color); ?>;"><?php print($ci->getReviewed()); ?></span>
				<?php if ($u->getRole() >= $g_permission['staff']) { ?>		
					<?php if (!$ci->reviewed()) { ?>						
						<input type="submit" name="approve_copyright" value="Set Copyright Reviewed">
						<input type="hidden" name="cmd" value="editMultipleReserves" />
						<input type="hidden" name="itemCmd" value="approve_copyright" />
						<input type="hidden" name="ci" value="<?php print($ci->getCourseInstanceID()); ?>" />
				<?php }} ?>
			</form>			
		</div>		
		<br/>

			
<?php
	} //displayEditClassHeader()
	
	
	static function displayEditClassReservesList(&$ci, $next_cmd, $show_students_pending_warning=false) {
		global $g_permission, $g_siteURL;
		$u = Rd_Registry::get('root:userInterface');
		$students_pending_warning = $show_students_pending_warning ? '<span class="alert">&nbsp;&nbsp;&nbsp;! students requesting to join class !</span>' : '';
		
		//get reserves as a tree + recursive iterator
		$tree_walker = $ci->getReservesAsTreeWalker('getReserves');
?>
		<script languge="JavaScript">
			//a bit of a hack to highlight all <span>s with class="highlightable"
			function highlightAll() {				
				var items = document.getElementsByTagName("span");
				
				for(var x=0; x<items.length; x++) {
					if(items[x].className == "highlightable") {
						items[x].style.background = "yellow";
					}
				}
			}
		</script>
		
		<div>
			[ <a href="index.php?cmd=customSort&ci=<?php print($ci->getCourseInstanceID()); ?>&parentID=" class="editlinks">sort main list</a> ]
			[ <a href="index.php?cmd=addReserve&ci=<?php print($ci->getCourseInstanceID()); ?>" class="editlinks">add new materials</a> ]
			[ <a href="index.php?cmd=editHeading&ci=<?php print($ci->getCourseInstanceID()); ?>" class="editlinks">add new heading</a> ]
			[ <a href="#" class="editlinks" onclick="highlightAll(); return false;">highlight reserve links</a> ]
		
		</div>
		
		<div class="contentTabs">
			<ul>
				<li class="current"><a href="index.php?cmd=<?php print($next_cmd); ?>&amp;ci=<?php print($ci->getCourseInstanceID()); ?>">Course Materials</a></li>
				<li><a href="index.php?cmd=<?php print($next_cmd); ?>&amp;ci=<?php print($ci->getCourseInstanceID()); ?>&amp;tab=enrollment">Enrollment<?php print($students_pending_warning); ?></a></li>	
				<?php if($u->getRole() >= $g_permission['staff']){ ?><li><a href="index.php?cmd=<?php print($next_cmd); ?>&amp;ci=<?php print($ci->getCourseInstanceID()); ?>&amp;tab=audit">Course History</a></li><?php }?>
			</ul>
		</div>
		<div style="float:right;">
			<a href="javascript:checkAll(document.forms.editReserves, 1)">check all</a> | <a href="javascript:checkAll(document.forms.editReserves, 0)">uncheck all</a>
		</div>
		<div class="clear"></div>
		
		<div id="course_materials_block">
			<form method="post" name="editReserves" action="index.php">		
				<input type="hidden" name="cmd" value="editMultipleReserves" />
				<input type="hidden" name="ci" value="<?php print($ci->getCourseInstanceID()); ?>" />
				
			<table width="100%" border="0" cellspacing="0" cellpadding="0" align="center">
				<tr valign="middle">
					<td class="headingCell1" align="right" colspan="2">
						<div class="editOptionsTitles">
							<div class="itemNumber">
								#
							</div>	
							<div class="checkBox">
								Select
							</div>
							<div class="sortBox">
								Sort
							</div>	
							<div class="editBox">
								Edit
							</div>	
							<div class="statusBox">
								Status
							</div>					
						</div>
					</td>
				</tr>
				<tr>
					<td colspan="2">
						<ul style="list-style:none; padding-left:0px; margin:0px;">
<?php
		//begin displaying individual reserves and building dataSet for TSV export
		//loop
		$prev_depth = 0;
		$counter = 0;
        $i=0;
        $fields = array("author", "title", "source", "volumeTitle", "volumeEdition", "pagesTimes", "performer");
		foreach($tree_walker as $leaf) {
			//close list tags if backing out of a sublist
			if($prev_depth > $tree_walker->getDepth()) {
				echo str_repeat('</ul></li>', ($prev_depth-$tree_walker->getDepth()));
			}
			
			$reserve = new reserve($leaf->getID());	//init a reserve object
			$reserve->getItem();	//pull item info
			
			//set edit link and status
			if($reserve->item->isHeading()) {
				//set edit link
	        	$editURL = "index.php?cmd=editHeading&ci=".$ci->getCourseInstanceID()."&headingID=".$reserve->getReserveID();
	        	//set the status
	        	$status = 'HEADING';
            }
            else {
            	//edit link
           		$editURL = 'index.php?cmd=editItem&reserveID='.$reserve->getReserveID();
            	//status
            	$status = $reserve->getStatus();
            	//if the reserve is not supposed to be active yet, hide it
            	//if(($status=='ACTIVE') && ($reserve->getActivationDate() > date('Y-m-d'))) {
            		//$status = 'HIDDEN';
            	//}
            }
            //do not show edit link for physical items unless viewed by staff
            $reserve->edit_link = (!$reserve->item->isPhysicalItem() || $u->getRole() >= $g_permission['staff']) ? '<a href="'.$editURL.'"><img src="public/images/pencil-gray.gif" border="0" alt="edit"></a>' : '';
			$reserve->status = $status;	//pass the status
            $reserve->counter = ++$counter;	//increment and pass the counter
            
            //if this reserve is a non-empty folder, set sort link
            $reserve->sort_link = ($leaf->numChildren() > 1) ? '<a href="index.php?cmd=customSort&amp;ci='.$ci->getCourseInstanceID().'&amp;parentID='.$leaf->getID().'"><img src="public/images/sort.gif" border="0" alt="sort contents"></a>' : '';
            
            //show plain-text links to electronic reserves
           	if(!$reserve->item->isPhysicalItem()) {	//only needed for electronic items
           		$reserve->additional_info = '<br /><span style="font-weight:bold; color:#333333;">Link to this item:</span> <span class="highlightable">'.$g_siteURL.'/reservesViewer.php?reserve='.$reserve->getReserveID().'</span>';
			}
            			
			$rowStyle = (isset($rowStyle) && $rowStyle=='oddRow') ? 'evenRow' : 'oddRow';	//set the style

			//display the info
			echo '<li>';
			self::displayReserveRowEdit($reserve, 'class="'.$rowStyle.'"');
			
			//start sublist or close list-item?
			echo ($leaf->hasChildren()) ? '<ul style="list-style:none;">' : '</li>';
			
            //append to TSV dataSet
            foreach ($fields as $key => $field) 
                $dataSet[$i]["$field"] = $reserve->item->$field;
                $dataSet[$i]["url"] = $g_siteURL . "/reservesViewer.php?reserve=" . $reserve->reserveID;
            $i++;
            
			$prev_depth = $tree_walker->getDepth();
		}
		echo str_repeat('</ul></li>', ($prev_depth));	//close all lists
?>

						</ul>
					</td>
				</tr>
				<script type="text/javascript">
				function deleteSelected(){
					var optionValue = document.getElementById('itemCmd');
					if (optionValue.options[optionValue.selectedIndex].value == "delete_multiple"){
						return confirm('This will remove the selected reserves from your class. Would you like to continue?');
					}
				}
				
				function approveCopyright(){
					document.getElementById('itemCmd').value = "approve_copyright";
				}
				</script>
				<tr valign="middle">
					
					<td class="headingCell1" style="text-align:right; padding:2px;" align="right" colspan="2">
						<select name="itemCmd" id="itemCmd">
							<option value="edit_multiple" selected = "selected">Edit Selected</option>
							<option value="copy_multiple">Copy Selected to Another Class</option>
							<?php if ($u->getRole() >= $g_permission['staff']) { ?>
								<option value="copyright_deny_class">Deny Use For This Class</option>
								<option value="copyright_deny_all_classes">Deny Use For All Classes</option>
							<?php } ?>
							<option value="delete_multiple">Delete Selected</option>
						</select>
						<input type="submit" value="Go" onClick="deleteSelected();"/>
					</td>
				</tr>						
			</table>
			</form>
			<p />
			<form method="post" id="tsv_export_form" name="tsv_export_form" action="tsvGenerator.php">
				<input type="hidden" name="dataSet" value="<?php print(base64_encode(serialize($dataSet))); ?>">
            </form>
		</div>
<?php 
	} //displayEditClassReservesList()
	
	static function displayEditClassAudit(&$ci, $next_cmd, $auditList){
	?>
		<script type="text/javascript">
			jQuery(document).ready(function(){
				jQuery('a.showData').click(function(){
					var jsonLocation = jQuery(this).parents().children('.fullData').children('.fullData').attr('id');
					tb_show(null, '#TB_inline?height=400&width=625&inlineId=' + jsonLocation + '&modal=true', false);
				});
			});
		</script>
		<div class="contentTabs">
			<ul>
				<li><a href="index.php?cmd=<?php print($next_cmd); ?>&amp;ci=<?php print($ci->getCourseInstanceID()); ?>">Course Materials</a></li>
				<li><a href="index.php?cmd=<?php print($next_cmd); ?>&amp;ci=<?php print($ci->getCourseInstanceID()); ?>&amp;tab=enrollment">Enrollment</a></li>
				<li class="current"><a href="index.php?cmd=<?php print($next_cmd); ?>&amp;ci=<?php print($ci->getCourseInstanceID()); ?>&amp;tab=audit">Course History</a></li>
			</ul>
		</div>
		<div class="clear"></div>
		<div class="displayArea">
		<div class="headingCell1">COURSE HISTORY</div>
			<div id="item_history">
				<table width="100%" border="0" cellpadding="4" cellspacing="0">
					<tr class="headingCell2" align="left" style="text-align:left;">
						<td width="5%">&nbsp;</td>
						<td width="15%">Timestamp</td>
						<td width="25%">Action</td>
						<td width="25%">Description</td>
						<td width="15%">Performed By</td>
						
						
					</tr>
<?php 
					$rowClass = 'evenRow';
					foreach($auditList as $audit){ 
						$rowClass = ($rowClass=='evenRow') ? 'oddRow' : 'evenRow';
						$reserveItem = '';
						$title = '';
						$targetID = $audit[2];
						$targetType = $audit[3];
						$search = '';
						$userID = '';
						$courseID = '';
						$jsonData = json_decode($audit[7]);
						$action_seperator = explode(" ", $audit[5]);
						$action_array = explode($action_seperator[0], $audit[5]);
						$action_name = $action_array[1];
						$action_action = $action_seperator[0];
						if(in_array($targetType, array('heading', 'reserve'))){
							$reserveItem = new reserveItem($targetID);
							$title = $reserveItem->getTitle();
							$search = urlencode(base64_encode(serialize(array(0 => array("field"=> "title", "test"=>"LIKE", "term" => $title, "conjunct" => "AND")))));
						}
						if(in_array($targetType, array('instructor', 'proxy', 'student'))){
							$user = new user($targetID);
							$title = $user->getUsername();
							$userID= $user->getUserID();
						}
						if($targetType == 'cross-listing'){
							$title = $action_array[1];	
						}
												
						?> 
						<tr class="<?php print($rowClass); ?>">	
							<td></td>
							<td><?php print($audit[4]); ?></td>
							<?php if($targetType == 'reserve'){ ?>	
							<td><b><?php print($action_action . " Reserve: "); ?></b><a href="index.php?cmd=doSearch&amp;search=<?php print($search); ?>"><?php print($action_name); ?></a></td>
							<?php }elseif(in_array($targetType, array('instructor', 'proxy', 'student'))){?>
							<td><b><?php print($action_action . " " . ucfirst($audit[3]) . ": "); ?></b><a href="index.php?cmd=editUser&amp;selectedUser=<?php print($userID); ?>"><?php print($action_name); ?></a></td>
							<?php }elseif($targetType == 'heading'){ ?>
							<td><b><?php print($action_action . " Heading: " ); ?></b><?php print($action_name); ?></td>
							<?php }else{?>
							<td><b><?php print($action_action . " " . ucfirst($audit[3]) . ": "); ?></b><?php print($action_name); ?></td>
							<?php }?>
							
							<td><a class="showData" style="cursor:pointer;color:#4C83AF;"><?php print("More Info"); ?></a></td>
							<td><?php print($audit[6] . " " . "($audit[8])"); ?></td>
							<td style="display:none;" class="fullData"><div class="fullData" id="<?php print($audit[0]); ?>" style="display:none;">
							<ul>
							<?php 
							if($jsonData != null){
								foreach($jsonData as $key => $value){
									?><li><?php print($key .  ": " . $value); ?></li><?php
								}
							}?>
							</ul>
							<p style="text-align:center;"><input type="submit" id="ok" value="OK" onclick="tb_remove();">&nbsp;</p></div>
						</div></td>
						</tr>
												
<?php	
					}
?>					
				
				
				
				</table>
		
			</div>
		</div>
<?php
	}
	
	
	static function displayEditClassEnrollment(&$ci, &$roll, $next_cmd) {		
	
	global $g_permission;
	$u = Rd_Registry::get('root:userInterface');
	$pending_roll = array();
	foreach ($roll as $courseRoll)
	{
		if (key_exists('PENDING', $courseRoll))
			array_push($pending_roll, $courseRoll['PENDING']);
	}
	//echo "<pre>";print_r($pending_roll);echo"</pre>";

?>
	<form method="post" name="editReserves" action="index.php">		
		<input type="hidden" name="cmd" value="<?php print($next_cmd); ?>" />
		<input type="hidden" name="ci" value="<?php print($ci->getCourseInstanceID()); ?>" />
		<input type="hidden" name="tab" value="enrollment" />

		<div class="contentTabs">
			<ul>
				<li><a href="index.php?cmd=<?php print($next_cmd); ?>&amp;ci=<?php print($ci->getCourseInstanceID()); ?>">Course Materials</a></li>
				<li class="current"><a href="index.php?cmd=<?php print($next_cmd); ?>&amp;ci=<?php print($ci->getCourseInstanceID()); ?>&amp;tab=enrollment">Enrollment</a></li>
				<?php if($u->getRole() >= $g_permission['staff']){?><li><a href="index.php?cmd=<?php print($next_cmd); ?>&amp;ci=<?php print($ci->getCourseInstanceID()); ?>&amp;tab=audit">Course History</a></li><?php }?>	
			</ul>
		</div>
		<div class="clear"></div>
		
		<div id="enrollment_block" class="borders">
			<div id="class_enrollment" class="classEnrollmentOptions">
				<strong>Enrollment Type:</strong>
				<?php self::displayEnrollmentSelect($ci->getEnrollment(), true); ?>
				<input type="submit" name="setEnrollment" value="Set Enrollment" style="margin-top:5px;">
			</div>
			<div id="class_roll" class="classRoll">
				<div class="classRollPending">
					<strong>Add a new student to this class:</strong>
					<br />
<?php
		//ajax user lookup
		$mgr = new ajaxManager('lookupUser', null, null, null, null, false, array('min_user_role'=>0, 'field_id'=>'student_id'));
		$mgr->display();
?>
					<br />
					<small>Select a name from the menu and click "Add Student to Roll"</small>
					<br />
					<input type="hidden" name="rollAction" id="rollActionAdd" value="" />
					<input type="submit" name="submit" value="Add Student to Roll" onclick="javascript: document.getElementById('rollActionAdd').value='add';" style="margin-top:5px;" />
					<p />

	
<?php	if(!empty($pending_roll[0])): ?>
					<strong>Students requesting to join this class:</strong>
					<table align="center" class="simpleList">
						<tr>
							<td colspan="2" style="text-align:center;">
								<a href="index.php?cmd=<?php print($next_cmd); ?>&amp;ci=<?php print($ci->getCourseInstanceID()); ?>&amp;tab=enrollment&amp;rollAction=add&amp;student_id=all">approve all</a> | <a href="index.php?cmd=<?php print($next_cmd); ?>&amp;ci=<?php print($ci->getCourseInstanceID()); ?>&amp;tab=enrollment&amp;rollAction=deny&amp;student_id=all">deny all</a>
							</td>
						</tr>
<?php		foreach($pending_roll[0] as $student): ?>
						<tr bgcolor="#FFFFFF">
							<td width="60%">
								<?php print($student->getName()); ?>
							</td>
							<td width="40%" align="center">
								<a href="index.php?cmd=<?php print($next_cmd); ?>&amp;ci=<?php print($ci->getCourseInstanceID()); ?>&amp;tab=enrollment&amp;rollAction=add&amp;student_id=<?php print($student->getUserID()); ?>">approve</a> | <a href="index.php?cmd=<?php print($next_cmd); ?>&amp;ci=<?php print($ci->getCourseInstanceID()); ?>&amp;tab=enrollment&amp;rollAction=deny&amp;student_id=<?php print($student->getUserID()); ?>">deny</a>
							</td>
						</tr>
<?php		endforeach; ?>
					</table>
<?php 	else: ?>
					<strong>There are no enrollment requests.</strong>
<?php	endif; ?>			
				</div><!-- classRollPending -->
				<div style="clear:both;" ></div><!-- hack to clear floats -->				
<?php 
  if (!empty($roll)):	
  	 $i = 0;		
  	 $ca_ids = array_keys($roll);
	 foreach ($roll as $courseRoll): 
		
		$course = new course($ca_ids[$i]);
		$autofed_roll  = (array_key_exists('AUTOFEED', $courseRoll) ? $courseRoll['AUTOFEED'] : array());
		$approved_roll = (array_key_exists('APPROVED', $courseRoll) ? $courseRoll['APPROVED'] : array());
		
		if ($i % 4 == 0) //carriage return div after 4 
			echo "<div style=\"clear:both;\" ></div><!-- hack to clear floats -->";
		$i++;			
	?>
				
				<div class="classRollActive">
	<?php	if(!empty($autofed_roll) || !empty($approved_roll)): ?>
						<strong>Currently enrolled in <?php echo $course->displayCourseNo() ?>:</strong>
						<table align="center" class="simpleList">
	<?php		if(!empty($autofed_roll)): ?>
							<tr>
								<td colspan="2">
									<strong>Students added by the Registrar:</strong>
								</td>
							</tr>
	<?php			foreach($autofed_roll as $student): ?>
							<tr>
								<td colspan="2">
									<?php print($student->getName()); ?>
								</td>
							</tr>
	<?php
					endforeach;
				endif;
			
				if(!empty($approved_roll)):
					if(!empty($autofed_roll)):	//only show the label if there are both kinds of students (autofed + manual)
	?>
							<tr><td colspan="2" align="center"><strong>* * *</strong></td></tr>
							<tr>
								<td colspan="2">
									<strong>Manually-added students:</strong>
								</td>
							</tr>
	<?php		
					endif;
					foreach($approved_roll as $student):
	?>
							<tr>
								<td width="80%">
									<?php print($student->getName()); ?>
								</td>
								<td width="20%" align="center">
									<a href="index.php?cmd=<?php print($next_cmd); ?>&amp;ci=<?php print($ci->getCourseInstanceID()); ?>&amp;tab=enrollment&amp;rollAction=remove&amp;student_id=<?php print($student->getUserID()); ?>">remove</a>
								</td>
							</tr>
	<?php		
					endforeach;
				endif;
	?>
						</table>
	<?php	else: ?>
						<strong>There are no enrolled students.</strong>
	<?php	endif; ?>	
					</div>
	<?php 
		endforeach; 				
				echo "<div class=\"clear\"></div>";
			
	endif;
	echo "</div></div>";
	?>
	</form>

					
<?php
	} //displayEditClassEnrollment()
	
	
	static function displayEditClass(&$ci, $next_cmd, $tab=null) {
		//get the class roll	
		$roll = $ci->getRoll();
		//show a warning if there are students pending enrollment approval
		//check pending array
		$show_students_pending_warning = false;
		foreach($roll as $courseRoll) {
			if (!empty($courseRoll['PENDING'])) {
				$show_students_pending_warning = true;
			}
		}
		if($tab=='enrollment') {	//display enrollment screen
			self::displayEditClassHeader($ci, $next_cmd, false);	//display header without the quicklinks box
			self::displayEditClassEnrollment($ci, $roll, $next_cmd);	//display enrollment info
		}
		elseif($tab == 'audit') {
			$auditTrail = $ci->getAuditTrail();
			self::displayEditClassHeader($ci, $next_cmd, false);
			self::displayEditClassAudit($ci, $next_cmd, $auditTrail);
		}
		else {	//display reserves list screen
			self::displayEditClassHeader($ci, $next_cmd, true);	//display header with the quicklinks box
			self::displayEditClassReservesList($ci, $next_cmd, $show_students_pending_warning);	//display reseves list
		}
		
		//display footer
?>
		<p />
		<div style="text-align:right;"><strong><a href="index.php">Exit class</a></strong></div>
<?php
	}
	
	/**
	 * @return void
	 * @param string $cmd currently executing cmd
	 * @param CourseInstance $ci course_instance object being edited
	 * @param string $msg Helper message to display above the form
	 * @desc Displays form for editting title and crosslistings
	 */	

	static function displayEditTitle($cmd, $ci, $deptID, $msg=null, $potential_xlistings=null)
	{
		global $g_permission;
		Rd_Registry::get('root:userInterface');
		if(!is_null($msg)) {
			echo "<span class=\"helperText\">$msg</span><p />\n";
		}

		if('' != trim($ci->getRegistrarKey())) {
			
?>
	<div class="policyNotice">
		<p>This course was created by an automated import of campus data. Making changes to the title and cross listings manually is not recommended.</p>
		<p>If you need assistance with the listing of this course, please contact <a href="mailto:<?php print(Rd_Registry::get('root:supportEmail')); ?>">reserves staff</a>.</p>
	</div>
<?php
 		}
		
		echo "<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\" align=\"center\">\n";
		echo "<tr>\n";
		echo "<td width =\"100%\" align=\"right\" valign=\"middle\"><!--<div align=\"right\" class=\"currentClass\">".$ci->course->displayCourseNo()."&nbsp;".$ci->course->getName()."</div>--></td>\n";
		echo "</tr>\n";
		echo " <form action=\"index.php?cmd=editTitle&ci=".$ci->getCourseInstanceID()."\" method=\"post\">\n";		
		echo " <tr>\n";
		echo " 	<td width=\"100%\"><img src=\"public/images/spacer.gif\" width=\"1\" height=\"5\"> </td>\n";
		echo " </tr>\n";
		echo "	<tr><td colspan=\"3\" align=\"right\"> <a href=\"index.php?cmd=editClass&ci=".$ci->courseInstanceID."\">Return to Edit Class</a></div></td></tr>\n";
		echo " <tr>\n";
		echo " 	<td>\n";
		echo " 	<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\">\n";
		echo "     	<tr align=\"left\" valign=\"top\">\n";
		echo "         	<td width=\"40%\" class=\"headingCell1\">CLASS TITLE and CROSSLISTINGS</td>\n";
		echo " 			<td>&nbsp;</td>\n";
		echo " 		</tr>\n";
		echo " 	</table>\n";
		echo " 	</td>\n";
		echo "</tr>\n";
		echo " <tr>\n";
		echo " 	<td align=\"left\" valign=\"top\" class=\"borders\">\n";
		echo " 	<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"2\">\n";
		echo "     	<tr class=\"headingCell1\">\n";
		echo "      	<td width=\"8%\" align=\"center\" valign=\"middle\">Primary</td>\n";
		echo "          <td width=\"6%\" align=\"left\" valign=\"top\">&nbsp;</td>\n";
		echo "          <td width=\"4%\" align=\"left\" valign=\"top\">&nbsp;</td>\n";
		echo "          <td width=\"13%\" align=\"left\" valign=\"top\">&nbsp;</td>\n";
		echo "          <td width=\"4%\" align=\"left\" valign=\"top\">&nbsp;</td>\n";
		echo "          <td width=\"8%\" align=\"left\" valign=\"top\">&nbsp;</td>\n";
		echo "          <td width=\"4%\" align=\"left\" valign=\"top\">&nbsp;</td>\n";
		echo "          <td width=\"7%\" align=\"left\" valign=\"top\">&nbsp;</td>\n";
		echo "          <td width=\"13%\" align=\"left\" valign=\"top\">&nbsp;</td>\n";
		echo "          <td width=\"13%\" align=\"left\" valign=\"top\">&nbsp;</td>\n";
		echo "          <td class=\"alignRight\" width=\"11%\" align=\"right\" valign=\"middle\">Delete</td>\n";
		echo "		</tr>\n";
		echo "		<tr class=\"evenRow\">\n";
		echo "			<td align=\"center\" valign=\"middle\"><input name=\"primaryCourse\" type=\"radio\" value=\"".$ci->course->courseAliasID."\" checked></td>\n";
		echo"			<INPUT TYPE=\"HIDDEN\" NAME=\"oldPrimaryCourse\" VALUE=\"".$ci->course->courseAliasID."\">\n";
		echo "          <td align=\"right\" valign=\"middle\" class=\"strong\">Dept:</td>\n";
		echo "          <td align=\"left\" valign=\"middle\">\n";

		self::displayDepartmentSelect($deptID, true, 'primaryDept');

		echo "     		</td>\n";
		echo "          <td align=\"right\" valign=\"middle\">Course#:</td>\n";
		echo "          <td align=\"left\" valign=\"middle\"> <input name=\"primaryCourseNo\" type=\"text\" size=\"5\" maxlength=\"8\" value=\"".$ci->course->getCourseNo()."\"></td>\n";
		echo "          <td align=\"right\" valign=\"middle\">Section:</td>\n";
		echo "          <td align=\"left\" valign=\"middle\"> <input name=\"primarySection\" type=\"text\" size=\"5\" maxlength=\"8\" value=\"".$ci->course->getSection()."\"></td>\n";
		echo "          <td align=\"right\" valign=\"middle\">Title:</td>\n";
		echo "          <td align=\"left\" valign=\"middle\"> <input name=\"primaryCourseName\" type=\"text\" size=\"25\" value=\"".$ci->course->getName()."\"></td>\n";
		echo "          <td align=\"center\" valign=\"middle\"><span class=\"smallText\">Registrar Key: " . ('' != trim($ci->getRegistrarKey()) ? $ci->getRegistrarKey() : 'none') . "</span></td>\n";
		echo "          <td class=\"alignRight\" align=\"right\" valign=\"middle\"></td>\n";
		echo "		</tr>\n";
		
		$rowNumber = 0;
		for ($i=0; $i<count($ci->crossListings); $i++) 
		{
			$rowClass = ($rowNumber % 2) ? "evenRow" : "oddRow\n";
			echo "		<tr class=\"".$rowClass."\"> \n";
			echo "			<td align=\"center\" valign=\"middle\"><!--<input type=\"radio\" name=\"primaryCourse\" value=\"".$ci->crossListings[$i]->courseAliasID."\">--></td>\n";
			echo "			<td align=\"right\" valign=\"middle\" class=\"strong\">Dept:</td>\n";
			echo "			<td align=\"left\" valign=\"middle\">\n";

			self::displayDepartmentSelect($ci->crossListings[$i]->deptID, true, 'cross_listings['.$ci->crossListings[$i]->courseAliasID.'][dept]');
			
			echo "			<td align=\"right\" valign=\"middle\">Course#:</td>\n";
			echo "			<td align=\"left\" valign=\"middle\"> <input name=\"cross_listings[".$ci->crossListings[$i]->courseAliasID."][courseNo]\" type=\"text\" size=\"5\" maxlength=\"8\" value=\"".$ci->crossListings[$i]->courseNo."\"></td>\n";
			echo "			<td align=\"right\" valign=\"middle\">Section:</td>\n";
			echo "			<td align=\"left\" valign=\"middle\"> <input name=\"cross_listings[".$ci->crossListings[$i]->courseAliasID."][section]\" type=\"text\" size=\"5\" maxlength=\"8\" value=\"".$ci->crossListings[$i]->section."\"></td>\n";
			echo "			<td align=\"right\" valign=\"middle\">Title:</td>\n";
			echo "			<td align=\"left\" valign=\"middle\"> <input name=\"cross_listings[".$ci->crossListings[$i]->courseAliasID."][courseName]\" type=\"text\" size=\"25\" value=\"".$ci->crossListings[$i]->getName()."\"></td>\n";
			echo "          <td align=\"center\" valign=\"middle\"><span class=\"smallText\">Registrar Key: " . ('' != trim($ci->crossListings[$i]->registrarKey) ? $ci->crossListings[$i]->registrarKey : 'none') . "</span></td>\n";
			echo "			<td class=\"alignRight\" align=\"right\" valign=\"middle\"><input type=\"checkbox\" name=\"deleteCrossListing[".$ci->crossListings[$i]->courseAliasID."]\" value=\"".$ci->crossListings[$i]->courseAliasID."\"></td>\n";
			echo "		</tr>\n";
			$rowNumber++;
		}
		
		echo "		<tr class=\"headingCell1\">\n";
		echo "			<td align=\"left\" valign=\"top\">&nbsp;</td>\n";
		echo "			<td align=\"left\" valign=\"top\">&nbsp;</td>\n";
		echo "			<td align=\"left\" valign=\"top\">&nbsp;</td>\n";
		echo "			<td align=\"left\" valign=\"top\">&nbsp;</td>\n";
		echo "			<td align=\"left\" valign=\"top\">&nbsp;</td>\n";
		echo "			<td align=\"left\" valign=\"top\">&nbsp;</td>\n";
		echo "			<td colspan=\"4\" align=\"left\" valign=\"top\"><div align=\"right\"><input type=\"submit\" name=\"updateCrossListing\" value=\"Update Course Info\">&nbsp;<input type=\"submit\" name=\"deleteCrossListings\" value=\"Delete Selected\"></div></td>\n";
		echo "			<td align=\"left\" valign=\"top\">&nbsp;</td>\n";
		//echo "				<br>\n";
		echo "		</tr>\n";
		echo "  </table>\n";
		echo " </td>\n";
		echo " </tr>\n";
		//echo "</form>\n";
		echo " <tr>\n";
		echo " 	<td height=\"15\">&nbsp;</td>\n";
		echo " </tr>\n";
		//echo " <form action=\"index.php\" method=\"get\">\n";
		echo " <input type=\"hidden\" name=\"cmd\" value=\"editCrossListings\">\n";
		echo " <input type=\"hidden\" name=\"ci\" value=\"".$ci->getCourseInstanceID()."\">\n";
		echo " <tr> \n";
		echo " 	<td>\n";
		echo " 	<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\">\n";
		echo " 		<tr align=\"left\" valign=\"top\"> \n";
		echo " 			<td width=\"35%\" class=\"headingCell1\">ADD NEW CROSSLISTING</td>\n";
		echo " 			<td>&nbsp;</td>\n";
		echo " 		</tr>\n";
		echo " 	</table>\n";
		echo " 	</td>\n";
		echo " </tr>\n";		
		
		//SELECT EXISTING COURSE
		echo "		<tr> \n";
		echo "			<td align=\"left\" valign=\"top\" class=\"borders\" colspan=\"8\">\n";
		echo "     			<tr class=\"headingCell1\">\n";
		echo "      			<td colspan=\"8\" align=\"left\">SELECT EXISTING COURSE</td>\n";
		echo "				</tr>\n";		
		echo "			</td>\n";
		echo "	</td>\n";
		echo "</tr>\n";				
		
		//echo "   	</table>\n";
		//echo "		</td>\n";
		//echo " 	</tr>\n";		

		if (!isset($_REQUEST['xlist_new_course']))
		{
			//give list of possible xlistings or class selecter for staff or greater
			echo "</form>\n"; //close form so that class lookup will work
			self::displaySelectClass($cmd, $potential_xlistings, '', array('ci'=>$_REQUEST['ci'], 'addCrossListing' => 'true'), false, 'xlist_ci', 'index.php?cmd=editCrossListings&xlist_new_course=true&ci='.$ci->getCourseInstanceID(), null, array($ci->getCourseInstanceID()));
		} else {
		//Create New Course
			echo "<tr> "
			."    	<td align=\"left\" valign=\"top\" class=\"borders\">\n"
			."    	<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"2\">\n"
			."      	<tr class=\"headingCell1\">"
			."          	<td colspan=\"8\" align=\"left\">CREATE NEW COURSE</td>\n"	
			."			</tr>\n"
			."          <tr> "
			."          	<td align=\"left\" valign=\"middle\" class=\"strong\"><div align=\"center\">Department:</div></td>\n"
			."              <td align=\"left\" valign=\"middle\">\n";
			
			self::displayDepartmentSelect(null, true, 'newDept');
	
			echo "          <td align=\"left\" valign=\"middle\" class=\"strong\"><div align=\"center\">Course Number:</div></td>\n"
			."              <td align=\"left\" valign=\"middle\"> <div align=\"left\"><input name=\"newCourseNo\" type=\"text\" id=\"Title2\" size=\"4\" maxlength=\"6\"></div></td>\n"
			."              <td align=\"left\" valign=\"middle\" class=\"strong\"><div align=\"center\">Section:</div></td>\n"
			."              <td align=\"left\" valign=\"middle\"> <div align=\"left\"><input name=\"newSection\" type=\"text\" size=\"4\" maxlength=\"6\"></div></td>\n"
			."              <td align=\"left\" valign=\"middle\" class=\"strong\"><div align=\"center\">Title:</div></td>\n"
			."          	<td align=\"left\" valign=\"middle\"> <div align=\"left\"><input name=\"newCourseName\" type=\"text\" size=\"30\"></div></td>\n"
			."			</tr>\n";	
			
			echo "      <tr class=\"headingCell1\">\n";
			echo "          <td align=\"left\" valign=\"middle\" colspan=\"8\"><div align=\"right\"><input type=\"submit\" name=\"addCrossListing\" value=\"Add Crosslisting\"></div></td>\n";
			echo "   	</tr>\n";				
		}

		
		echo "<tr>\n"
		."          <td>&nbsp;</td>\n"
		."        </tr>\n"
		."        <tr>\n"
		."          <td><div align=\"center\"><a href=\"index.php?cmd=editClass&ci=".$ci->courseInstanceID."\">Return to Edit Class</a></div></td>\n"
		."        </tr>\n"
		."        <tr>\n"
		."          <td><img src=\"public/images/spacer.gif\" width=\"1\" height=\"15\"></td>\n"
		."        </tr>\n"
		." </table>\n";
		echo "</form>\n";
	}

	static function displayEditInstructors($ci, $addTableTitle, $dropDownDefault, $userType, $removeTableTitle, $removeButtonText, $request)
	{
        
		$u = Account_Rd::getUserInterface();	
        echo " <table width=\"95%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\" align=\"center\">";
		echo "<form name=\"editInstructors\" action=\"index.php?cmd=editInstructors&ci=".$ci->courseInstanceID."\" method=\"post\">";
		echo "<tr>";
		echo "<td colspan=\"3\" align=\"right\" valign=\"middle\"><!--<div align=\"right\" class=\"currentClass\">".$ci->course->displayCourseNo()."</div>--></td>";
		echo " 	</tr>";
		/* Use this logic if we decide to display the course numbers for the cross listings
			for ($i=0; $i<count($ci->crossListings); $i++) {
				echo "<tr>";
				echo "<td colspan=\"3\" align=\"right\" valign=\"middle\"><div align=\"right\" class=\"currentClass\">".$ci->crossListings[$i]->displayCourseNo()."</div></td>";
				echo " 	</tr>";
			}
		*/
		echo " 	<tr>";
		echo " 		<td colspan=\"3\"><img src=\"public/images/spacer.gif\" width=\"1\" height=\"5\"> </td>";
		echo " 	</tr>";
		echo " 	<tr>";
		echo " 		<td height=\"14\" colspan=\"3\" align=\"left\" valign=\"top\">";
		echo " 		<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\">";
		echo "         	<tr>";
		echo "             	<td width=\"35%\" align=\"left\" valign=\"top\" class=\"headingCell1\">".$addTableTitle."</td>";
		echo " 				<td align=\"left\" valign=\"top\">&nbsp;</td>";
		echo " 				<td width=\"35%\" align=\"left\" valign=\"top\" class=\"headingCell1\">".$removeTableTitle."</td>";
		echo " 			</tr>";
		echo " 		</table>";
		echo " 		</td>";
		echo " 	</tr>";
		echo "     <tr>";
		echo "     	<td width=\"50%\" align=\"left\" valign=\"top\">";
		echo "     	<table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" class=\"borders\">";
		echo " 			<tr>";
		echo "             	<td align=\"left\" valign=\"top\">";
		echo "             	<table width=\"100%\" border=\"0\" cellpadding=\"2\" cellspacing=\"0\" class=\"displayList\">";
		echo "                 	<tr align=\"left\" valign=\"middle\">";
		echo "                   		<td bgcolor=\"#FFFFFF\" class=\"headingCell1\">&nbsp;</td>";
		echo "                 	</tr>";
		echo "                 	<tr align=\"left\" valign=\"middle\">";
		echo "                   		<td width=\"88%\" bgcolor=\"#CCCCCC\"><div align=\"center\">";
		/*
		echo "                	    		<select name=\"prof\">";
		echo "                           		<option value=\"\" selected>-- ".$dropDownDefault." --</option>";
								foreach($instructorList as $instructor)
								{
									echo "<option value=\"" . $instructor["user_id"] . "\">" . $instructor["full_name"] . "</option>";
								}
		echo "                       		</select>";
		*/
		$selectClassMgr = new lookupManager('','lookupInstructor', $u, $request);
		$selectClassMgr->display();
		echo "                   		</div></td>";

		echo "                 	</tr>";
		echo "                 	<tr align=\"left\" valign=\"middle\">";
		echo "                   		<td class=\"headingCell1\">&nbsp;</td>";
		echo "                 	</tr>";
		echo "                 	<tr align=\"left\" valign=\"middle\">";
		echo "                   		<td><div align=\"center\"><input type=\"submit\" name=\"add".$userType."\" value=\"Add ".$userType."\"></div></td>";
		echo "                 	</tr>";
		echo "               </table>";
		echo "               </td>";
		echo " 			</tr>";
		echo " 		</table>";
		echo " 		</td>";
		echo "         <td align=\"left\" valign=\"top\"><img src=\"public/images/spacer.gif\" width=\"15\" height=\"1\"></td>";
		echo "         <td width=\"50%\" align=\"left\" valign=\"top\">";
		echo "         <table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" class=\"borders\">";
		echo "         	<tr>";
		echo "             	<td align=\"right\" valign=\"top\">";
		echo "             	<table width=\"100%\" border=\"0\" cellpadding=\"2\" cellspacing=\"0\" class=\"displayList\">";
		echo "                 	<tr align=\"left\" valign=\"middle\">";
		echo "                   		<td bgcolor=\"#FFFFFF\" class=\"headingCell1\">&nbsp;</td>";
		echo "                   		<td class=\"headingCell1\">Remove</td>";
		echo "                 	</tr>";

					$rowNumber = 0;
					if ($userType=="Instructor") {
						$numInstructors = count($ci->instructorList);
						$instruct = $ci->instructorList;
					} elseif ($userType=="Proxy") {
						$numInstructors = count($ci->proxies);
						$instruct = $ci->proxies;
					}
					for($i=0;$i<$numInstructors;$i++) {
						$rowClass = ($rowNumber++ % 2) ? "evenRow" : "oddRow\n";
						echo "                 	<tr align=\"left\" valign=\"middle\" class=\"".$rowClass."\">";
						echo "                   		<td>".$instruct[$i]->getName()."</td>";
						echo "                   		<td width=\"8%\" valign=\"top\" class=\"borders\"><div align=\"center\">";
						if ($userType=="Instructor")
							echo "<input type=\"checkbox\" name=\"".$userType."[".$instruct[$i]->userID."]\" value=\"".$instruct[$i]->userID."\">";
						echo "&nbsp;</div></td>";
							
						echo "                 	</tr>";
					}
		echo "                 	<tr align=\"left\" valign=\"middle\">";
		echo "                   		<td colspan=\"2\"  class=\"headingCell1\">&nbsp;</td>";
		echo "                 	</tr>";
		echo "                 	<tr align=\"left\" valign=\"middle\">";
		echo "                   		<td colspan=\"2\"><div align=\"center\"><input type=\"submit\" name=\"remove".$userType."\" value=\"".$removeButtonText."\"></div></td>";
		echo "                 	</tr>";
		echo " 				</table>";
		echo " 				</td>";
		echo " 			</tr>";
		echo " 		</table>";
		echo " 		</td>";
		echo " 	</tr>";
		echo "     <tr>";
		echo "     	<td colspan=\"3\">&nbsp;</td>";
		echo " 	</tr>";
		echo "     <tr>";
		echo "     	<td colspan=\"3\"><div align=\"center\"><a href=\"index.php?cmd=editClass&ci=".$ci->courseInstanceID."\">Return to Edit Class</a></div></td>";
		echo " 	</tr>";
		echo "     <tr>";
		echo "     	<td colspan=\"3\"><img src=\"public/images/spacer.gif\" width=\"1\" height=\"15\"></td>";
		echo " 	</tr>";
		echo " </form>";
		echo " </table>";
	}

	static function displayEditProxies($ci, $proxyList, $request)
	{
		$ci->getPrimaryCourse();

		echo "<form action=\"index.php?cmd=editProxies&ci=".$ci->getCourseInstanceID()."\" method=\"POST\">\n";
		echo "<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\" align=\"center\">\n";
		echo "	<tr>\n";
		echo "		<td colspan=\"3\" align=\"right\"> <a href=\"index.php?cmd=editClass&ci=". $ci->getCourseInstanceID() ."\" class=\"strong\">Return to Class</a></div></td>\n";
		echo "	</tr>\n";
		echo "	<tr>\n";
		echo "		<td colspan=\"3\">&nbsp;</td>\n";
		echo "	</tr>\n";
		echo "	<tr>\n";
		echo "		<td height=\"14\" colspan=\"3\" align=\"left\" valign=\"top\">\n";
		echo "			<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\">\n";
		echo "				<tr>\n";
		echo "					<td width=\"35%\" align=\"left\" valign=\"top\" class=\"headingCell1\">ADD A PROXY</td>\n";
		echo "					<td align=\"left\" valign=\"top\">&nbsp;</td>\n";
		echo "					<td width=\"35%\" align=\"left\" valign=\"top\" class=\"headingCell1\">CURRENT PROXIES</td>\n";
		echo "				</tr>\n";
		echo "			</table>\n";
		echo "		</td>\n";
		echo "	</tr>\n";
		echo "	<tr>\n";
		echo "		<td width=\"50%\" align=\"left\" valign=\"top\">\n";
		echo "			<table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" class=\"borders\">\n";
		echo "				<tr>\n";
		echo "					<td align=\"left\" valign=\"top\">\n";
		echo "						<table width=\"100%\" border=\"0\" cellpadding=\"2\" cellspacing=\"0\" class=\"displayList\">\n";
		echo "							<tr align=\"left\" valign=\"middle\"><td bgcolor=\"#FFFFFF\" class=\"headingCell1\">&nbsp;</td></tr>\n";
		echo "							<tr align=\"left\" valign=\"middle\">\n";
		echo "								<td bgcolor=\"#CCCCCC\" align=\"left\"><p align=\"center\"><span class=\"strong\">Search by: </span>\n";
		echo "									<select name=\"queryTerm\"><option selected value=\"last_name\">Last Name</option><option value=\"username\">User Name</option></select>\n";
		echo "									&nbsp;\n";
		echo "									<input name=\"queryText\" type=\"text\" value=\"" . (array_key_exists('queryText', $request) ? $request['queryText'] : '' ) . "\" size=\"15\">&nbsp;\n";
		echo "									<input type=\"submit\" name=\"search\" value=\"Search\"></p>\n";
		echo "								</td>\n";
		echo "							</tr>\n";
		echo "							<tr align=\"left\" valign=\"middle\">\n";
		echo "								<td width=\"88%\" height=\"68\" valign=\"top\" bgcolor=\"#CCCCCC\" align=\"center\">\n";


		$addProxyDisabled = "DISABLED";
		if (is_array($proxyList) && !empty($proxyList)){
			$addProxyDisabled = "";
			echo "									<hr align=\"center\" width=\"150\">\n";
			echo "									<span class=\"strong\">Search Results:</span>\n";
			echo "									<select name=\"proxy\" onChange='this.form.addProxy.disabled=false;'>\n";
			foreach($proxyList as $proxy)
			{
				echo "										<option value=\"".$proxy->getUserID()."\">".$proxy->getName()."</option>\n";
			}
			echo "									</select>\n";
		}

		echo "								</td>\n";
		echo "							</tr>\n";
		echo "							<tr align=\"left\" valign=\"middle\"><td class=\"headingCell1\">&nbsp;</td></tr>\n";
		echo "							<tr align=\"left\" valign=\"middle\">\n";
		echo "								<td align=\"center\"><input type=\"submit\" name=\"addProxy\" value=\"Add Proxy\" $addProxyDisabled></td>\n";
		echo "							</tr>\n";
		echo "						</table>\n";
		echo "					</td>\n";
		echo "				</tr>\n";
		echo "			</table>\n";
		echo "		</td>\n";
		echo "		<td align=\"left\" valign=\"top\"><img src=\"public/images/spacer.gif\" width=\"15\" height=\"1\"></td>\n";
		echo "		<td width=\"50%\" align=\"left\" valign=\"top\">\n";
		echo "			<table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" class=\"borders\">\n";
		echo "				<tr>\n";
		echo "					<td align=\"right\" valign=\"top\">\n";
		echo "						<table width=\"100%\" border=\"0\" cellpadding=\"2\" cellspacing=\"0\" class=\"displayList\">\n";
		echo "							<tr align=\"left\" valign=\"middle\"><td bgcolor=\"#FFFFFF\" class=\"headingCell1\">&nbsp;</td><td class=\"headingCell1\">Remove</td></tr>\n";

		if (is_array($ci->proxies) && !empty($ci->proxies))
		{
			foreach($ci->proxies as $proxy)
			{
//				echo "							<tr align=\"left\" valign=\"middle\"><td bgcolor=\"#FFFFFF\" class=\"headingCell1\">&nbsp;</td><td class=\"headingCell1\">Remove</td></tr>\n";
				echo "							<tr align=\"left\" valign=\"middle\">\n";
				echo "								<td bgcolor=\"#CCCCCC\">". $proxy->getName() ."</td>\n";
				echo "								<td width=\"8%\" valign=\"top\" bgcolor=\"#CCCCCC\" class=\"borders\" align=\"center\">\n";
				echo "									<input type=\"checkbox\" name=\"proxies[]\" value=\"".$proxy->getUserID()."\">\n";
				echo "								</td>\n";
				echo "							</tr>\n";
//				echo "							<tr align=\"left\" valign=\"middle\"><td colspan=\"2\"  class=\"headingCell1\">&nbsp;</td></tr>\n";
//				echo "							<tr align=\"left\" valign=\"middle\"><td colspan=\"2\" align=\"center\"><input type=\"submit\" name=\"removeProxy\" value=\"Remove Selected Proxies\"></td></tr>\n";
			}
		} else {
//			echo "							<tr align=\"left\" valign=\"middle\"><td bgcolor=\"#FFFFFF\" class=\"headingCell1\">&nbsp;</td><td class=\"headingCell1\">&nbsp;</td></tr>\n";
			echo "							<tr align=\"left\" valign=\"middle\"><td bgcolor=\"#CCCCCC\">This class currently has no proxies.</td><td width=\"8%\" valign=\"top\" bgcolor=\"#CCCCCC\" align=\"center\">&nbsp;</td></tr>\n";
//			echo "							<tr align=\"left\" valign=\"middle\"><td colspan=\"2\"  class=\"headingCell1\">&nbsp;</td></tr>\n";
		}
		echo "							<tr align=\"left\" valign=\"middle\"><td colspan=\"2\"  class=\"headingCell1\">&nbsp;</td></tr>\n";
		echo "							<tr align=\"left\" valign=\"middle\"><td colspan=\"2\" align=\"center\"><input type=\"submit\" name=\"removeProxy\" value=\"Remove Selected Proxies\"></td></tr>\n";
		echo "						</table>\n";
		echo "					</td>\n";
		echo "				</tr>\n";
		echo "			</table>\n";
		echo "		</td>\n";
		echo "	</tr>\n";
		echo "	<tr><td colspan=\"3\">&nbsp;</td></tr>\n";
		echo "	<tr><td colspan=\"3\"><strong>To Add a Proxy:</strong><br>\n";
		echo "			<ol><li>Type the last name of the person you would like to add into the search box and click \"Search\".</li>\n";
		echo "				<li>A drop-down menu will appear with names that match your search. Choose a name from the menu and click the \"Add Proxy\" button.</li>\n";
		echo "				<li>The name of your proxy will appear on the right under \"Current Proxies\".</li>\n";
		echo "			</ol>\n";
		echo "	<tr><td colspan=\"3\">&nbsp;</td></tr>\n";
		echo "	<tr><td colspan=\"3\"><strong>To Remove a Proxy:</strong><br>\n";
		echo "			<ol><li>Under the \"Current Proxies\" list, check the box next to the name of the proxy you wish to remove.</li>\n";
		echo "				<li>Click the \"Remove Selected Proxy\" button.</li>\n";
		echo "			</ol>\n";
		echo "		</td></tr>\n";
		echo "	<tr><td colspan=\"3\">&nbsp;</td></tr>\n";
		echo "	<tr><td colspan=\"3\" align=\"center\"> <a href=\"index.php?cmd=editClass&ci=". $ci->getCourseInstanceID() ."\" class=\"strong\">Return to Class</a></div></td></tr>\n";
		echo "	<tr><td colspan=\"3\"><img src=\"public/images/spacer.gif\" width=\"1\" height=\"15\"></td></tr>\n";
		echo "</table>\n";
		echo "</form>\n";
	}
	
	
	function displayCreateSuccess($ci_id) {
?>
		<div class="borders" style="text-align: center;">
			<div style="width:50%; margin:auto; text-align:left;">
				<strong>You have successfully created a class. What would you like to do now?</strong>
				<p />
				<ul>
					<li><a href="index.php?cmd=importClass&new_ci=<?php print($ci_id); ?>">Import materials into this class from another class (Reactivate)</a></li>
					<li><a href="index.php?cmd=addReserve&ci=<?php print($ci_id); ?>">Add new materials to this class</a></li>
					<li><a href="index.php?cmd=editClass&ci=<?php print($ci_id); ?>">Go to this class.</a></li>
					<li><a href="index.php?cmd=createClass">Create a New Class.</a></li>
				</ul>
			</div>
		</div>
<?php
	}
	
	
	function displayActivateSuccess($ci_id) {
?>
		<div class="borders" style="text-align: center;">
			<div style="width:50%; margin:auto; text-align:left;">
				<strong>You are opening this class for the first time this semester.  What would you like to do?</strong>
				<p />
				<ul>
					<li><a href="index.php?cmd=importClass&new_ci=<?php print($ci_id); ?>">Import materials into this class from another class (Reactivate)</a></li>
					<li><a href="index.php?cmd=addReserve&ci=<?php print($ci_id); ?>">Add new materials to this class</a></li>
					<li><a href="index.php?cmd=editClass&ci=<?php print($ci_id); ?>">Go to this class.</a></li>
					<li><a href="index.php?cmd=deactivateClass&ci=<?php print($ci_id); ?>"><strong>Cancel</strong> - I do not wish students to see this class.</a></li>
					<li><a href="index.php?cmd=removeClass&ci=<?php print($ci_id); ?>"><strong>Remove</strong> - I do not plan on teaching this class.</a></li>
				</ul>
			</div>
		</div>
<?php
	}
	
	
	/**
	 * @return void
	 * @param string $preproc_cmd Command that is originating a call to this method (where should the script return in case of duplicate)
	 * @param string $postproc_cmd Command to issue after the new class is successfully created [choices limited by switch() in classManager]
	 * @param array $hidden_fields Data to be passed on as hidden fields
	 * @param string $msg Helper message to display above the form
	 * @desc Displays form for creating a new class; sends data to classManager, which actually handles class creation and then forwards user to $postproc_cmd with the ID of the newly-created CI passed in $_REQUEST['new_ci'].
	 */	
	function displayCreateClass($preproc_cmd, $postproc_cmd=null, $hidden_fields=null, $msg=null) {
		global $g_permission;
		
		$this->_user = Rd_Registry::get('root:userInterface');
		
		//set defaults if they exists
		$department = !empty($_REQUEST['department']) ? $_REQUEST['department'] : '';
		$section = !empty($_REQUEST['section']) ? $_REQUEST['section'] : '';
		$course_number = !empty($_REQUEST['course_number']) ? $_REQUEST['course_number'] : '';
		$course_name = !empty($_REQUEST['course_name']) ? $_REQUEST['course_name'] : '';
		$term = !empty($_REQUEST['term']) ? $_REQUEST['term'] : '';
		$enrollment = !empty($_REQUEST['enrollment']) ? $_REQUEST['enrollment'] : '';
		
		//add the origin cmd and the next cmd to hidden fields
		//this will tell the manager where to return in case of a dupe
		//and where to proceed if class is created successfully
		$hidden_fields['preproc_cmd'] = $preproc_cmd;
		$hidden_fields['postproc_cmd'] = $postproc_cmd;
		
?>
		<script language="JavaScript">
			function validate(form) {
				var fieldCount = 0;
				var requiredFields=true;
				var errorMsg='The following fields are required: ';

				if (!(form.department.value)) {
					requiredFields=false;
					errorMsg = errorMsg + 'Department';
					fieldCount++;
				}

				if (!(form.course_number.value)) {
					requiredFields=false;
					if (fieldCount>0) errorMsg = errorMsg + ', ';
					errorMsg = errorMsg + 'Course Number';
					fieldCount++;
				}

				if (!(form.course_name.value)) {
					requiredFields=false;
					if (fieldCount>0) errorMsg = errorMsg + ', ';
					errorMsg = errorMsg + 'Course Name';
					fieldCount++;
				}
				
				if (!(form.term.value)) {
					requiredFields=false;
					if (fieldCount>0) errorMsg = errorMsg + ', ';
					errorMsg = errorMsg + 'Term';
					fieldCount++;
				}
		
				if (!(form.selected_instr.value)) {
					requiredFields=false;
					if (fieldCount>0) errorMsg = errorMsg + ', ';
					errorMsg = errorMsg + 'Instructor';
					fieldCount++;
				}

				if (!(form.activation_date.value)) {
					requiredFields=false;
					if (fieldCount>0) errorMsg = errorMsg + ', ';
					errorMsg = errorMsg + 'Activation Date';
					fieldCount++;
				}

				if (!(form.expiration_date.value)) {
					requiredFields=false;
					if (fieldCount>0) errorMsg = errorMsg + ', ';
					errorMsg = errorMsg + 'Expiration Date';
					fieldCount++;
				}

				if (requiredFields) {
					return true;
				} else {
					alert (errorMsg);
					return false;
				}
			}
		</script>
		
		<form name="frmClass" action="index.php" method="post" onSubmit="return validate(this);">	
			<input type="hidden" name="cmd" value="<?php print($preproc_cmd); ?>" />			
			<?php self::displayHiddenFields($hidden_fields); ?>

<?php	if(!empty($msg)): ?>
			<span class="helperText"><?php print($msg); ?></span><p />
<?php	endif; ?>
		
		<table width="100%" border="0" cellspacing="0" cellpadding="0" align="center">
			<tr>
				<td class="headingCell1" width="25%" align="center">CLASS DETAILS</td>
				<td width="75%" align="center">&nbsp;</td>
			</tr>
		    <tr>
		    	<td colspan="2" class="borders">
			    	<table width="100%" border="0" cellspacing="0" cellpadding="5">
			    		<tr>
			    			<td width="30%" bgcolor="#CCCCCC" align="right" class="strong">
			    				Department:
			    			</td>
			    			<td>
			    				<?php self::displayDepartmentSelect($department); ?>
			    			</td>
			    		</tr>
			    		<tr>
			    			<td width="30%" bgcolor="#CCCCCC" align="right" class="strong">
			    				Course Number:
			    			</td>
			    			<td>
			    				<input name="course_number" type="text" id="course_number" size="5" value="<?php print($course_number); ?>" />
			    			</td>
			    		</tr>
			    		<tr>
			    			<td width="30%" bgcolor="#CCCCCC" align="right" class="strong">
			    				Section:
			    			</td>
			    			<td>
			    				<input name="section" type="text" id="section" size="5" value="<?php print($section); ?>" />
			    			</td>
			    		</tr>
			    		<tr>
			    			<td width="30%" bgcolor="#CCCCCC" align="right" class="strong">
			    				Course Name:
			    			</td>
			    			<td>
			    				<input name="course_name" type="text" id="course_name" size="50" value="<?php print($course_name); ?>" />
			    			</td>
			    		</tr>
			    		<tr>
			    			<td width="30%" bgcolor="#CCCCCC" align="right" class="strong">
			    				Term:
			    			</td>
			    			<td>
<?php
		//allow staff or above to edit start/end dates
		$show_dates = ($this->_user->getRole() >=  Account_Rd::LEVEL_STAFF) ? true : false;
	
		//show term selection
		self::displayTermSelect($term, $show_dates);
?>
			    			</td>
			    		</tr>
			    		<tr>
			    			<td width="30%" bgcolor="#CCCCCC" align="right" class="strong">
			    				Enrollment:
			    			</td>
			    			<td>
			    				<?php self::displayEnrollmentSelect($enrollment, true); ?>
			    			</td>
			    		</tr>
<?php	if($this->_user->getRole() >= Account_Rd::LEVEL_STAFF): //show instructor lookup for staff ?>
			    		<tr>
			    			<td width="30%" bgcolor="#CCCCCC" align="right" class="strong">
			    				Instructor:
			    			</td>
			    			<td>
<?php
			//ajax lookup
			$mgr = new ajaxManager('lookupUser', null, null, null, null, false, array('min_user_role'=>3, 'field_id'=>'selected_instr'));
			$mgr->display();
?>
			    			</td>
			    		</tr>
<?php	 else:	//add instructor as hidden field ?>

						<input type="hidden" id="selected_instr" name="selected_instr" value="<?php print($this->_user->getUserID()); ?>" />

<?php	endif; ?>
			    	</table>
			    </td>
			</tr>
		</table>
		<p />
		<div style="text-align:center;"><input type="submit" name="Submit" value="Create Course" onClick="this.form.cmd.value='createNewClass';javascript:return validate(document.forms.frmClass);"></div>
<?php
	}

	
	static function displaySelectDept_Instr($hidden_fields=null) {
		global $g_permission;
		$u = Rd_Registry::get('root:userInterface');
		$depObj = new department();
?>
		<script>
			function checkInstructor() {
				var frm = document.getElementById('instructor_form');
				if(frm.selected_instr.options[frm.selected_instr.selectedIndex].value == '') {
					alert('Please select an instructor');
					return false;					
				}
				else {
					return true;					
				}
			}
		</script>
		
		<table width="100%" cellspacing="0" cellpadding="0" align="center">
			<tr class="headingCell1" align="center">
				<td>Search by Instructor</td>
				<td>Search by Department</td>
			</tr>
			<tr align="center">
				<td class="borders">
					<br />
					<form method="post" id="instructor_form" action="index.php">
						<input type="hidden" name="cmd" value="addClass" />
						<?php self::displayHiddenFields($hidden_fields); ?>
						
<?php
		$lookupMgr = new lookupManager('', 'lookupInstructor', $u, $_REQUEST);
		$lookupMgr->display();
?>
						<p />
						<input type="submit" name="submit_instructor" value="Look Up Classes" onclick="return checkInstructor();" />
					</form>
				</td>
				<td class="borders">
					<br />
					<form method="post" action="index.php">
						<input type="hidden" name="cmd" value="addClass" />
						<?php self::displayHiddenFields($hidden_fields); ?>
						
						<?php self::displayDepartmentSelect(); ?>

						<p />
						<input type="submit" name="submit_dept" value="Look Up Classes" />
					</form>
				</td>
			</tr>
		</table>
		<p />
<?php	if($u->getRole() >= $g_permission['instructor']): ?>
		<strong>Instructors:</strong> Adding a class through this page will only allow you to see that class as a student would. Classes that you are teaching show up automatically in your MyCourses list with a pencil icon next to them. If you do not see your class under your MyCourses list, you may try <a href="index.php?cmd=createClass">creating a class</a> or contacting the Reserves staff.
<?php		
		endif;			
	}


	function displayDeleteClass ($cmd, $u, $request) {
		//display selectClass
		$mgr = new ajaxManager('lookupClass', 'confirmDeleteClass', 'manageClasses', 'Delete Class');
		$mgr->display();
		//show warning
		echo '<div align="center" class="strong"><font color="#CC0000">CAUTION! Deleting a class cannot be undone!</font></div>';
	}

	function displayConfirmDelete ($sourceClass) {
		$roll_arrays = $sourceClass->getRoll();
		$roll = array_merge($roll_arrays['AUTOFEED'], $roll_arrays['APPROVED']);

		echo '<input type="hidden" name="ci" value="'.$sourceClass->getCourseInstanceID().'">';

		echo "<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\" align=\"center\">\n";
		echo '<tr><td width="100%"><img src="public/images/spacer.gif" width="1" height="5"></td></tr>';

		echo '<tr>';
		echo 	'<td align="left" valign="top">';
        echo 	  '<p>';
        echo 		'<span class="courseTitle">';
        echo 			$sourceClass->course->displayCourseNo().' -- '.$sourceClass->course->getName().' ('.$sourceClass->displayTerm().')</span>,';
        echo 			' <span class="helperText">Instructors: ';
        echo $sourceClass->displayInstructors();
		echo 			'</span></p></td></tr>';
		echo '<tr><td>&nbsp;</td></tr>';
		echo '<tr>';
				echo 	'<td align="left" valign="top"><p><span class="helperText">'.count($roll).' Total Enrolled Students<br><br>';
				
				foreach($roll as $student) {
					echo '<strong>'.$student->getName().'</strong><br />';
				}

				echo 	'</p></td>';
        		echo '</tr>';
        echo '<tr><td>&nbsp;</td></tr>';
        echo '<tr>';
		echo 	'<td align="left" valign="top">';
        echo 	  '<p>';
        echo 		'<span class="failedText">';
        echo 'Are you sure you want to delete this class?';
        echo 		'</span>';
        echo 	  '</p>';

        echo 	  '<p>';
        echo 		'&gt;&gt;<a href="index.php?cmd=deleteClassSuccess&ci='.$sourceClass->getCourseInstanceID().'">Yes, Delete this class</a><br>';
        echo 		'&gt;&gt;<a href="index.php?cmd=deleteClass">No, Delete another class</a><br>';
        echo 		'&gt;&gt;<a href="index.php?cmd=manageClasses">No, Return to &quot;Manage Classes&quot; home</a><br>';
        echo 	  '</p>';
        echo 	'</td>';
        echo '</tr>';

        echo '<tr><td align="left" valign="top">&nbsp;</td></tr>';
		echo "</table>\n";

	}

	function displayDeleteSuccess ($sourceClass) {

		echo "<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\" align=\"center\">\n";
		echo '<tr><td width="100%"><img src="public/images/spacer.gif" width="1" height="5"></td></tr>';

		echo '<tr>';
		echo 	'<td align="left" valign="top">';
        echo 	  '<p>';
        echo 		'<span class="strong">';
        echo 			$sourceClass->course->displayCourseNo().' -- '.$sourceClass->course->getName().' ('.$sourceClass->displayTerm().')';
        echo 		'</span>';
        echo 		'<span class="successText"> has been deleted.</span>';
        echo 	  '</p>';

        echo 	  '<p>';
        echo 		'&gt;&gt;<a href="index.php?cmd=deleteClass">Delete another class</a><br>';
        echo 		'&gt;&gt;<a href="index.php?cmd=manageClasses">Return to &quot;Manage Classes&quot; home</a><br>';
        echo 	  '</p>';
        echo 	'</td>';
        echo '</tr>';

        echo '<tr><td align="left" valign="top">&nbsp;</td></tr>';
		echo "</table>\n";

	}
	

	function displayCopyItemsSuccess ($targetClass, $originalClass, $numberCopied) {

		echo "<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\" align=\"center\">\n";
		echo '<tr><td width="100%"><img src="public/images/spacer.gif" width="1" height="5"></td></tr>';

		echo '<tr>';
		echo 	'<td align="left" valign="top">';
        echo 	  '<p>';
        echo 		'<span class="successText">'.$numberCopied.' item(s) were copied from </span>';
        echo 		'<span class="strong">';
        echo 			$originalClass->course->displayCourseNo().' -- '.$originalClass->course->getName().' ('.$originalClass->displayTerm().')';
        echo 		'</span>';
        echo 		'<span class="successText"> to </span>';
        echo 		'<span class="strong">';
        echo 			$targetClass->course->displayCourseNo().' -- '.$targetClass->course->getName().' ('.$targetClass->displayTerm().')';
        echo 		'</span>';
        echo 	  '</p>';

        echo 	  '<p>';
        echo 		'&gt;&gt;<a href="index.php?cmd=editClass&ci='.$originalClass->getCourseInstanceID().'">Return to original class</a><br>';
        echo 		'&gt;&gt;<a href="index.php?cmd=editClass&ci='.$targetClass->getCourseInstanceID().'">Go to target class</a><br>';
        echo 	  '</p>';
        echo 	'</td>';
        echo '</tr>';
        
        echo '<tr><td align="left" valign="top">&nbsp;</td></tr>';
		echo "</table>\n";		
		
	}
	
		
	function displayDuplicateCourse(&$ci, $prev_state=null) {
		global $g_permission;
		$u = Rd_Registry::get('root:userInterface');
		$dup_msg = "The course you are attempting to create is already active for this term.  Please double-check the department, course number, section, and term of your course.";  
		if (is_array($ci->duplicates))
			$dup_msg .=	"You may copy reserves by selecting one of the course(s) below.";
		
		
?>	
		<table width="100%" border="0" cellspacing="0" cellpadding="0" align="center">
			<tr><td width="100%"><img src="public/images/spacer.gif" width="1" height="5"></td></tr>
			<tr>
				<td class="failedText"><?php print($dup_msg); ?></td>
			</tr>
			<tr><td>&nbsp;</td></tr>
			<?php if (is_array($ci->duplicates)) { ?>
				<form action="index.php" method="post" id="frmCopyClass">
					<input type="hidden" name="cmd" value="processCopyClass">
					<input type="hidden" name="importClass">				
					<input type="hidden" name="ci" value="<?php print($ci->getCourseInstanceID()); ?>">
					<tr>
						<td align="left" valign="top">
							<table width="100%" border="0" align="center" cellpadding="5" cellspacing="0" class="displayList">
									<tr align="left" valign="middle" bgcolor="#CCCCCC" class="headingCell1">
										<td>&nbsp;</td><td>Course Number</td><td align="left">Course Name</td><td>Instructor</td><td>Active Term</td><td>Reserve List</td>
									</tr>
											
									<?php 
									foreach ($ci->duplicates as $dup)
									{ 							
										$dup->getPrimaryCourse();	//pull in course object
										$dup->getInstructors();	//pull in instructor info
										
										//link course name/num to editClass, if viewed by instructor
										if($u->getRole() >= $g_permission['staff']) {
											$course_num = '<a href="index.php?cmd=editClass&ci='.$dup->getCourseInstanceID().'">'.$dup->course->displayCourseNo().'</a>';
											$course_name = '<a href="index.php?cmd=editClass&ci='.$dup->getCourseInstanceID().'">'.$dup->course->getName().'</a>';
										}
										else {
											$course_num = $dup->course->displayCourseNo();
											$course_name = $dup->course->getName();
										}							
										echo "<tr align=\"left\" valign=\"middle\" class=\"oddRow\">\n";
										echo "	<td align=\"center\"><input type='radio' name='new_ci' value='".$dup->getCourseInstanceID()."' onClick=\"this.form.submit.disabled=false;\"></td>\n";
										echo "	<td width=\"15%\" align=\"center\">$course_num</td>\n";
										echo "	<td>$course_name</td>\n";
										echo "	<td width=\"20%\" align=\"center\">".$dup->displayInstructors()."</td>\n";
										echo "	<td width=\"15%\" align=\"center\">".$dup->displayTerm()."</td>\n";
										echo "	<td width=\"10%\" align=\"center\"><a href=\"javascript:openWindow('no_control&cmd=previewReservesList&ci=".$dup->getCourseInstanceID()."','width=800,height=600');\">preview</a></td>\n";
										echo "</tr>\n";
									}
									?>	
									
								</table>
						</td>
					</tr>
					
					<tr><td align="left" valign="top">&nbsp;</td></tr>
					
					<tr><td align="center" valign="top"><input type="submit" value="Copy Into Selected Course" name="submit" disabled> 
					</form>
				<?php } //isarray($ci->duplicates 
				   else 
				   { echo '<tr><td align="center" valign="top">'; }

							//make a form with hidden items and a button to return to previous screen
							if(!empty($prev_state))
							{	
								//echo "<div style=\"width:100%; margin:auto;\">\n";
								echo "	<form action=\"index.php\" method=\"post\" name=\"return_to_previous\">\n";
										self::displayHiddenFields(unserialize(base64_decode($prev_state))); 
								echo "		<input type=\"submit\" name=\"return\" value=\"Go Back to the Previous Screen\" />\n";
								echo "	</form>\n";
								//echo "</div>\n";
							}
						?>
					</td>
				</tr>						
		</table>
		<p />
<?php
	}
}
