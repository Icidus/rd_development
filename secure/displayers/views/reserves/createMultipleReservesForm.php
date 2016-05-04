<div>
	<div>You are requesting the following items to be put on reserve for:
	<div class='courseTitle' style='font-size: 100%; font-variant: inherit'>
	<?php print($this->courseInstance->course->displayCourseNo() . ' ' . $this->courseInstance->course->getName() . ' ' . $this->instructors); ?></div>
	Fill in the fields below as appropriate for the item you are requesting. Items marked with an asterisk are required.
	</div>
</div>
<?php if(count($this->itemsAlreadyProcessed) > 0){
?>
	<p>The following items were successfully requested:</p>
	<ul> <?php 
		foreach($this->itemsAlreadyProcessed as $item){
			?> <li><?php print($item->getTitle()); ?>,<?php print($item->getVolumeEdition()); ?>[<?php print (($item->getItemGroup() == 'ELECTRONIC')? 'Electronic Reserve':'Physical Reserve');?>]</li> <?php 
		}
	?></ul>
	<p>Below are items that <b>could not</b> be processed. You may try resubmitting them.</p>
<?php }?>
<script language="javascript">
	
<?php 
	$libs = $this->libraries;
	if(!empty($libs)) {	
		foreach ($libs as $library) {
			$loanPeriods = $library->getInstructorLoanPeriods();
			$tempArray[$library->getLibraryID()] = $loanPeriods;
		}
		echo '		var loanPeriods = ' . json_encode($tempArray) . ";\n";
	}
?>
		function changeLibraryLoanPeriods() {
			var currentLib = jQuery("#librarySelector option:selected").val();

			<?php foreach($this->items as $item) { 
				$itemId = $item->getItemID();
			?>
			jQuery("#loanPeriod_<?php print($itemId); ?> option").hide();
			if(loanPeriods[currentLib] && loanPeriods[currentLib].length) {
				jQuery("#loanPeriod_<?php print($itemId); ?>").parent().show();
				for (i = 0; i < loanPeriods[currentLib].length; i++) {
					var currentLoanPeriod = loanPeriods[currentLib][i].loan_period;
					var currentDefaultStatus = loanPeriods[currentLib][i]["default"];
					jQuery("#loanPeriod_<?php print($itemId); ?> option[value=" + currentLoanPeriod + "]").show();
					if (currentDefaultStatus == "true") {
						jQuery("#loanPeriod_<?php print($itemId); ?> option[value=" + currentLoanPeriod + "]").attr("selected","selected");
					}
				}
			} else {
				jQuery("#loanPeriod_<?php print($itemId); ?>").parent().hide();
			}
			<?php } ?>
			
		}

		function validateField(thisfield) {
			var node=jQuery(thisfield);
			var val=node.val();
			if(node.length > 0 && (val == null || val == false || val == "" )) {
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
				<?php $first = true; foreach($this->items as $item) { 
					$itemId = $item->getItemID();
					if($first){
						$first = false;
					} else { 
						print(',');
					}
				?>
				"#requestType_<?php print($itemId); ?> option:selected" 
				<?php } ?>
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

			<?php foreach($this->items as $item) { 
				$itemId = $item->getItemID();
			?>
			// make sure we have somethign that even looks like a valid date here.
			var enteredDate = jQuery("#dateNeeded_<?php print($itemId); ?>").val();
			
			if (enteredDate.match(/^(?:(?:(?:(?:(?:1[6-9]|[2-9]\d)?(?:0[48]|[2468][048]|[13579][26])|(?:(?:16|[2468][048]|[3579][26])00)))(\/|-|\.)(?:0?2\1(?:29)))|(?:(?:(?:1[6-9]|[2-9]\d)?\d{2})(-)(?:(?:(?:0?[13578]|1[02])\2(?:31))|(?:(?:0?[1,3-9]|1[0-2])\2(29|30))|(?:(?:0?[1-9])|(?:1[0-2]))\2(?:0?[1-9]|1\d|2[0-8]))))$/) == null) {
				validate = false;
				alert("Please make sure a valid date is entered for all items.\nProper format is YYYY-MM-DD.");
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
			<?php } ?>
		}

		jQuery(document).ready(function () {
			changeLibraryLoanPeriods();
			jQuery("#librarySelector").change(function () {
				changeLibraryLoanPeriods();
			});
			
			function generateRemoveHandler(itemIdString, titleString){
				var itemId = itemIdString;
				var title = titleString;
				return function (){
					var item = jQuery(this).next().detach();
					jQuery(this).html('<button type="button" style="color:red;">Undo Remove Item</button> '+title);
					jQuery(this).removeClass("removeButton").addClass("undoButton").attr("id", "undo"+itemId);
					jQuery('#undo'+itemId).click(function(){
						item.insertAfter(this);
						jQuery(this).html('<button type="button" style="color:red;">Remove this item</button>');
						jQuery(this).removeClass("undoButton").addClass("removeButton").attr("id", "rm"+itemId);
						jQuery(this).click(generateRemoveHandler(itemId, title));
					});		
				}
				
			}
			
			<?php foreach($this->items as $item) { 
				$itemId = $item->getItemID();
				//########################################################
				//##  The following change function runs every time		##
				//##    the type of reserve is changed.					##
				//##  Later, a class is added to the request type if	##
				//##    the incoming item has a URL (electronic)		##
				//##  Some display logic is necessary to decide in each ##
				//##    case whether or not to disable/hide the inputs	##
				//##    for Loan Period, Pages, and Volumes				##
				//########################################################
			?>
			jQuery("#requestType_<?php print($itemId); ?>").change(function () {
				if (jQuery("#requestType_<?php print($itemId); ?> option:selected").val() == "electronic") {
					if (jQuery("#requestType_<?php print($itemId); ?>").hasClass('incomingElectronic')){
						jQuery("#loanPeriod_<?php print($itemId); ?>").attr("disabled", "disabled").parent().hide();
						jQuery("input[name=times_pages_<?php print($itemId); ?>]").attr("disabled", "disabled").parent().hide();
						jQuery("input[name=volume_title_<?php print($itemId); ?>]").attr("disabled", "disabled").parent().hide();
					} else {
						jQuery("#loanPeriod_<?php print($itemId); ?>").attr("disabled", "disabled").parent().hide();
						jQuery("input[name=times_pages_<?php print($itemId); ?>]").removeAttr("disabled").parent().show();
						jQuery("input[name=volume_title_<?php print($itemId); ?>]").removeAttr("disabled").parent().show();
					}
				}
				else if(jQuery("#requestType_<?php print($itemId); ?> option:selected").val() == "physAndElec") {
					if (jQuery("#requestType_<?php print($itemId); ?>").hasClass('incomingElectronic')){
						jQuery("#loanPeriod_<?php print($itemId); ?>").removeAttr("disabled").parent().show();
						jQuery("input[name=times_pages_<?php print($itemId); ?>]").attr("disabled", "disabled").parent().hide();
						jQuery("input[name=volume_title_<?php print($itemId); ?>]").removeAttr("disabled").parent().show();
					} else {
						jQuery("#loanPeriod_<?php print($itemId); ?>").removeAttr("disabled").parent().show();
						jQuery("input[name=times_pages_<?php print($itemId); ?>]").removeAttr("disabled").parent().show();
						jQuery("input[name=volume_title_<?php print($itemId); ?>]").removeAttr("disabled").parent().show();
					}				
				}else{
						jQuery("#loanPeriod_<?php print($itemId); ?>").removeAttr("disabled").parent().show();
						jQuery("input[name=times_pages_<?php print($itemId); ?>]").attr("disabled", "disabled").parent().hide();
						jQuery("input[name=volume_title_<?php print($itemId); ?>]").removeAttr("disabled").parent().show();
				}
			});
			jQuery("#requestType_<?php print($itemId); ?>").change();


			jQuery('#rm<?php echo $itemId; ?>').click(generateRemoveHandler('<?php echo $itemId; ?>',"<?php echo addslashes(substr($item->getTitle(), 0, 35))."... "; ?>"));
	


			<?php } ?>

		});	
		</script>
  		<div class="headingCell1" style="width:25%; text-align:center;">Reserve Request Form</div>
  		
		<form method="POST" action="index.php" onsubmit="javascript:return validateRequestForm()">
			<input type="hidden" name="ci" value="<?php print($this->ci); ?>" />
			<input type="hidden" name="cmd" value="addMultipleReserves" />
			<input type="hidden" name="save" value="true" />
		<div class="multiItemRequestForm">

<?php
	//show reserve-desk/home-library select box
	if(!empty($libs)){
?>
				<div class="reserveDeskSelector"><div class="padding"><label> Reserve Desk: 
						<select id="librarySelector" name="home_library">				
<?php			
		$librarySelected = (array_key_exists('home_library', $_REQUEST) ? $_REQUEST['home_library'] : $this->preferredLibrary);
		foreach($libs as $lib){
?>
							<option value="<?php print($lib->getLibraryID()); ?>" <?php print($librarySelected==$lib->getLibraryID() ? ' selected="selected"' : ''); ?>><?php print($lib->getLibrary()); ?></option>
<?php	} ?>
						</select>
						<br/><span class="emphasize">Please allow at least two weeks for processing of your request.</span>
						</label></div></div>
<?php
	}
?>
		<input type="hidden" name="identifier" value="itemid"/>
<?php 
foreach($this->items as $item){
			$coverImageUrl = (
				'' != $item->getISBN() || '' != $item->getOCLC() 
				? str_replace(array('{$isbn}','{$oclc}'), array($item->getISBN(),'&oclc=' . $item->getOCLC()),$this->coverImageUrlPattern)
				: '' 
			);
			$itemId = $item->getItemID();
			$failedSave = array_key_exists('save',$_REQUEST) && 'true' == $_REQUEST['save'];
			$notesEnterd = (array_key_exists('notes_'.$itemId, $_REQUEST) ? $_REQUEST['notes_'.$itemId] : '');
			$selectedType = (
				array_key_exists('requestType_'.$itemId, $_REQUEST) 
				? $_REQUEST['requestType_'.$itemId] 
				: ('' == $item->getURL() ? 'physical' : 'electronic')
			);
			// The incomingElectronic class is added to the request type select to facilitate jQuery selections 
			$incomingElectronic = ($selectedType == 'electronic')? ' class="incomingElectronic" ' : '';
			$selectedPeriod = (array_key_exists('loanPeriod_'.$itemId, $_REQUEST) ? $_REQUEST['loanPeriod_'.$itemId] : '');
			$selectedDate = (array_key_exists('dateNeeded_'.$itemId, $_REQUEST) ? $_REQUEST['dateNeeded_'.$itemId] : '');
			$pagesEntered = (array_key_exists('times_pages_'.$itemId, $_REQUEST) ? $_REQUEST['times_pages_'.$itemId] : '');
			$volumeEntered = (array_key_exists('volume_title_'.$itemId, $_REQUEST) ? $_REQUEST['volume_title_'.$itemId] : '');
			
			?>

			<div class="itemDetails">
				<div class="removeButton" id="rm<?php print($itemId);?>" style="text-align:right;">
					<button type="button" style="color:red;">Remove this item</button>
				</div>
			<div class="padding">
				<input type="hidden" name="items[]" value="<?php print($itemId); ?>"/>
				<div class="display">
					<?php if('' != $coverImageUrl) { ?><img style="max-width:120px;max-height:120px;" src="<?php print($coverImageUrl); ?>" alt="Cover Image for <?php print($item->getTitle()); ?>"/><?php } ?>
					<h2><?php print($item->getTitle()); ?>,<?php print($item->getVolumeEdition()); ?></h2>
					<?php if('' != $item->getSource()) { ?><p><?php print($item->getSource()); ?></p><?php  } ?>
					<p><?php print($item->getAuthor()); ?> <?php print($item->getPerformer()); ?></p>
					<div class="clear"></div>

				</div>
				<div class="form">
					<label>Type of Reserve: <select <?php echo $incomingElectronic;?>name="requestType_<?php print($itemId); ?>" id="requestType_<?php print($itemId); ?>">
							<option <?php if('physical' == $selectedType){ print('selected="selected"');} ?> value="physical">Physical</option>
							<option <?php if('electronic' == $selectedType){ print('selected="selected"');} ?> value="electronic">Electronic</option>
							<option <?php if('physAndElec' == $selectedType){ print('selected="selected"');} ?> value="physAndElec">Both Physical and Electronic</option>
						</select></label>
					<!-- <fieldset><legend>Item Type: </legend>
						<label class="first"><input type="radio" name="item_group" value="MONOGRAPH" checked="checked" />Monograph</label>
						&nbsp;<label><input type="radio" name="item_group" value="MULTIMEDIA" /> Multimedia</label>
					</fieldset>  -->

					<label> Loan Period:
					<select id="loanPeriod_<?php print($itemId); ?>" name="loanPeriod_<?php print($itemId); ?>">
<?php 				
	foreach($this->u->getLoanPeriods() as $loanPeriod){
		if($loanPeriod != "MEDIA"){
?>
							<option <?php if($selectedPeriod == $loanPeriod){ print('selected="selected"');} ?> value="<?php print($loanPeriod); ?>"><?php print($loanPeriod); ?></option>
<?php 
		}
	}
?>

					</select></label>
				<label> Date Needed By:
					<input id="dateNeeded_<?php print($itemId); ?>" name="dateNeeded_<?php print($itemId); ?>" type="text" size="20" value="<?php print($selectedDate); ?>">
					<?php print($this->calendar->getWidgetAndTrigger("dateNeeded_{$itemId}", date("Y-m-j", strtotime('+6 week')))); ?>
				</label>

				<label>Pages/Scenes/Tracks/Time Range, Duration: <br/>(for physical items being put on electronic reserve) <input name="times_pages_<?php print($itemId); ?>" type="text" size="40" value="<?php print($pagesEntered); ?>"  ></label>
				<label>Volumes/Issues Needed: <br/> (for serials or other multi-volume titles) <input name="volume_title_<?php print($itemId); ?>" type="text" size="40" value="<?php print($volumeEntered); ?>" ></label>
				
				<div class="notesSection"><label>Notes for Reserves Staff:<br/><textarea rows="5" cols="38" name="notes_<?php print($itemId); ?>"><?php print($notesEnterd); ?></textarea></label></div>
			</div>

			</div></div>
<?php } ?>
			<div class="clear"></div>

			</div>
			<div class="submitRequest"><input type="submit" value="Place Request" /></div>
		</form>
