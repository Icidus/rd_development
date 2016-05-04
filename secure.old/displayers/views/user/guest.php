<div class="grid_15">
<?php if ('' != trim($this->msg)) {?>
	<div  class="helperText"><?php print($this->msg); ?></div>
	<?php } ?>

<div class="cancelNavigation">[ <a href="index.php?cmd=manageUser">Cancel</a> ]</div>

<h2 class="formHeader grid_8">MANAGE GUEST ACCESS FOR<br/>
<a href="./?cmd=editUser&uid=<?php print($this->user->getUserID()); ?>">
<?php
		print($this->user->getUsername() . ' - ' . $this->user->getName(false));
?>
</a></h2>
<form class="bordered clearing basicPadding bottomMargin" action="./?cmd=setGuest" method="post" name="setGuest">
	<input type="hidden" name="uid" value="<?php print($this->user->getUserId()); ?>" />
	<div class="policyNotice"><?php print(Rd_Dictionary::getXml('instructions:guestUserPolicyForStaff')); ?></div>
<?php 
					$guestAccess = $this->hasGuestAccess ? 'checked="checked"' : '';
?>
			<label class="blockLabel bottomHalfMargin"><span class="block_3">Enable Guest Access: </span><input type="checkbox" name="enableGuest" value="enableGuest" <?php print($guestAccess); ?> /></label>			
	
			<label class="blockLabel bottomHalfMargin"><span class="block_2">Expires:</span>
				<input type="text" name="expireDate" size="40" type="text" value="<?php print($this->expireDate); ?>" /></label>
				<script type="text/javascript">
					$( '[name="expireDate"]' ).datepicker({
			            showOn: "button",
			            buttonImage: "public/images/icons/calendar-month_sm.png",
			            buttonImageOnly: true,
			            defaultDate: '+6m',
			            dateFormat: "yy-mm-dd",
			            constrainInput: false		            
				    });
				</script>
		<div style="text-align:center;"><input type="submit" name="setGuestSubmit" value="Save Changes" /></div>

		</form>
	
</div>