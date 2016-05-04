<div>
<form action="index.php" method="post" id="additem_form" class="styled" name="additem_form" enctype="multipart/form-data">
	<input type="hidden" name="loan_period" value="<?php print($this->loanPeriod); ?>"/>
	<input type="hidden" name="documentType" value="VIDEO" />
	<script type="text/javascript">
<?php 
$this->getDisplayer()->displayVideoUploadScripts();
?>
	</script>
	<div class="borders" style="margin:10px; padding:10px; background:lightgreen; text-align:center">
<?php 
$this->getDisplayer()->displayVideoUploadDirections();
?>
	</div>
<?php if ($this->hasUnclaimedUploads) {?>
    <h2 class="sectionHeader" style="width:25%; text-align:center;">Your Previous Uploads</h2>
	<div id="fileChooser" class="borderedSection paddedSection">		
			<p>The following files are videos you have already uploaded, but have not been assigned to a class.</p> <p> Instead of uploading a file, you may select one of these files. Unassigned uploads are periodically purged.</p>
			<label class="blockLabel">Select File: <?php $this->htmlHelper('formSelect', array(array(
				'name' => 'existingFileHash',
				'values' => $this->fileList	,
				'emptyOption' => 'Upload a new file'
			)))?></label> 
			<div id="removeExistingButton" tabindex="-1" class="qq-upload-button" style="float:none;display:inline-block;">Delete this file</div>
		<div class="clear">&nbsp;</div>    
	</div>
<?php } ?>
	<h2 class="sectionHeader" style="width:25%; text-align:center;">Upload Video</h2>
	<div id="fileUploader" class="borderedSection paddedSection">		
		<noscript>			
			<p>Please enable JavaScript to use file uploader.</p>
			<label>Upload File: <input type="file" name="videoFile" size="40"></label>
		</noscript>    
		<div class="clear">&nbsp;</div>    
	</div>
    <script>               
        window.onload = videoUploadManager.createUploader;     
    </script>

	<h2 class="sectionHeader" style="width:25%; text-align:center;">Video Details</h2>
	<div id="fileDetails" class="borderedSection">		


		<fieldset>
			<legend class="fieldLabel">Personal Copy Owner:</legend>
			<div class="fieldContainer">
			<label class="noBorder horizontal"><input type="radio" name="personal_item" id="personal_item_no" value="no"<?php print($this->personalItem ? '' : $checkedString); ?> onclick="togglePersonalOwner();" /> No</label>
				&nbsp;&nbsp;
			<label class="noBorder horizontal"><input type="radio" name="personal_item" id="personal_item_yes" value="Yes"<?php print($this->personalItem ? $checkedString : ''); ?> onclick="togglePersonalOwner();" /> Yes</label>
			<label id="personal_item_owner_block" class="noBorder horizontal"><span id="personal_item_owner_search"></span>
<?php //#TODO not even!
				//ajax user lookup
				$mgr = new ajaxManager('lookupUser', null, null, null, null, false, array('min_user_role'=>3, 'field_id'=>'selected_owner'));
				$mgr->display();		
?>
			</div>
			</label>
		</fieldset>
		<label class="dividerBorder blockLabel topMargin bottomMargin"><span class="fieldLabel block_2"><span class="formElementRequired">*</span>Clip Title:</span>
			<span class="fieldContainer"><input name="title" type="text" size="50" <?php print($this->htmlHelper('valueIf', array('' == $this->title, $this->title))); ?>/></span>
		</label>
		<label class="blockLabel bottomMargin"><span class="block_2"><span class="formElementRequired">*</span>Writer/Director:</span>
			<span class="fieldContainer"><input name="author" type="text" size="50" <?php print($this->htmlHelper('valueIf', array('' == $this->author, $this->author))); ?>/></span>
		</label>
		<label class="blockLabel bottomMargin"><span class=" block_2">Performer/Actor:</span>
			<span class="fieldContainer"><input name="performer" type="text" size="50" <?php print($this->htmlHelper('valueIf', array('' == $this->performer, $this->performer))); ?>/></span>
		</label>
		<label class="blockLabel bottomMargin"><span class=" block_3">Movie/Work Title:</span>		
			<span class="fieldContainer"><input name="volume_title" type="text" size="50" <?php print($this->htmlHelper('valueIf', array('' == $this->volumeTitle, $this->volumeTitle))); ?>/></span>
		</label>
		<label class="blockLabel bottomMargin"><span class=" block_3">Producer / Studio:</span>		
			<span class="fieldContainer"><input name="volume_edition" type="text" size="50" <?php print($this->htmlHelper('valueIf', array('' == $this->source, $this->source))); ?>/></span>
		</label>
		<label class="blockLabel bottomMargin"><span class=" block_2">Times:</span>		
			<span class="fieldContainer"><input name="times_pages" type="text" size="20" <?php print($this->htmlHelper('valueIf', array('' == $this->timeMinutes, $this->timeMinutes))); ?>/>&nbsp;to&nbsp;
				<input name="times_pages2" type="text" size="20" <?php print($this->htmlHelper('valueIf', array('' == $this->timeSeconds, $this->TimeSeconds))); ?>"/></span>
		</label>
		<label class="blockLabel bottomMargin"><span class=" block_2">Year:</span>		
			<span class="fieldContainer"><input name="source" type="text" size="50" <?php print($this->htmlHelper('valueIf', array('' == $this->volumeEdition, $this->volumeEdition))); ?>/></span>
		</label>
		<label class="blockLabel bottomMargin"><span class=" block_2">ISBN:</span>		
			<span class="fieldContainer"><input name="ISBN" size="15" maxlength="15" <?php print($this->htmlHelper('valueIf', array('' == $this->isbn, $this->isbn))); ?> type="text"/></span>
		</label>
		<label class="blockLabel bottomMargin"><span class=" block_2">ISSN:</span>		
			<span class="fieldContainer"><input name="ISSN" maxlength="15" size="15" <?php print($this->htmlHelper('valueIf', array('' == $this->issn, $this->issn))); ?> type="text"/></span>
		</label>
		<label class="blockLabel bottomMargin"><span class=" block_2">OCLC:</span>		
			<span class="fieldContainer"><input name="OCLC" maxlength="9" size="15" <?php print($this->htmlHelper('valueIf', array('' == $this->oclc, $this->oclc))); ?> type="text"/></span>
		</label>
		<label class="blockLabel bottomMargin"><span class=" block_3">Barcode / Alternate ID:</span>		
			<span class="fieldContainer"><input type="text" name="local_control_key" <?php print($this->htmlHelper('valueIf', array('' == $this->barcode, $this->barcode))); ?> /></span>
		</label>	
		<input type="hidden" name="item_group" value="VIDEO" />	 
		<div class="clear">&nbsp;</div>    
	</div>
		

	<h2 class="sectionHeader" style="width:25%; text-align:center;">Video Notes</h2>
	<div id="fileDetails" class="borderedSection paddedSection">	
<?php	if($this->itemId){	//if editing existing item, use AJAX notes handler ?>

		<script language="JavaScript1.2" src="public/javascript/basicAJAX.js"></script>
		<script language="JavaScript1.2" src="public/javascript/notes_ajax.js"></script>
		
<?php self::displayNotesBlockAJAX($this->itemNotes, 'item', $this->itemId, true); ?>

<?php }	else {	//just display plain note form ?>

			<label>Add a note:
			<br />
	        <textarea name="new_note" cols="50" rows="3"></textarea></label>
	        <br />
	        <fieldset>
	        	<legend>Note Type:</legend>
	        	<label class="horizontal"><input type="radio" name="new_note_type" checked="true">Content Note</label>
	        	<label class="horizontal"><input type="radio" name="new_note_type">Staff Note</label>
	        	<label class="horizontal"><input type="radio" name="new_note_type">Copyright Note</label>
	        </fieldset>

<?php	} ?>
	</div>	

	<strong><font color="#FF0000">* </font></strong><span class="helperText">= required fields</span></td></tr>

	<br />
	<div style="text-align:center;"><input type="submit" name="store_request" value="Add Item" onclick="handleQuirkyAjaxLibrary();" <?php print($this->htmlHelper('disabledIf', array('' == $this->videoUrl))); ?>></div>
		
</form>
</div>