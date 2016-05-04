<div class="grid_15">
<?php if ('' != trim($this->msg)) {?>
	<div  class="helperText"><?php print($this->msg); ?></div>
	<?php } ?>

<div class="cancelNavigation">[ <a href="index.php<?php print(Account_Rd::atLeastStaff() ? '?cmd=manageUser' : ''); ?>">Cancel</a> ]</div>

<h2 class="formHeader grid_8">
<?php
		print(
			$this->formCommand == 'addUser'
			? 'CREATE NEW USER'
			: 'EDIT USER - ' . $this->user->getUsername() . ' - ' . $this->user->getName(false)
		);
?>
</h2>
<form class="bordered clearing basicPadding bottomMargin" action="./?cmd=<?php print($this->formCommand); ?>" method="post" name="editUser">
	<input type="hidden" name="uid" value="<?php print($this->user->getUserId()); ?>" />
<?php	if($this->formCommand == 'addUser') { ?>
	<div class="policyNotice"><?php print(Rd_Dictionary::getXml('instructions:newUserPolicyForStaff')); ?></div>		
			<label class="blockLabel bottomHalfMargin"><span class="block_2">User Name:</span>
				<input type="text" name="username" value="<?php print($this->username); ?>" size="40" />
			</label>
<?php	} else { ?>
			<p class="bottomHalfMargin"><span class="block_2">User Name:</span><strong><?php print($this->username); ?></strong> 
			<?php if (Account_Rd::isAdmin()) { ?>(<a href="./?cmd=setGuest&uid=<?php print($this->user->getUserId()); ?>"><?php print( $this->hasGuestAccess ? 'has guest access' : 'institutional user')?>)</a>
			<?php } ?>
			</p>
<?php	} ?>
			<label class="blockLabel bottomHalfMargin"><span class="block_2">First Name:</span>
				<input type="text" name="firstName" size="40" type="text" value="<?php print($this->firstName); ?>" /></label>
			<label class="blockLabel bottomHalfMargin"><span class="block_2">Last Name:</span>
				<input type="text" name="lastName" size="40" value="<?php print($this->lastName); ?>" /></label>
			<label class="blockLabel bottomMargin"><span class="block_2">E-mail:</span>
				<input type="text" name="email" size="40" value="<?php print($this->email); ?>" /></label>	
<?php	if(count($this->allowedPermisions) > 0){ ?>
			<label class="blockLabel bottomHalfMargin"><span class="block_2">Default Role:</span>
				<select name="defaultRole">
<?php		
			foreach($this->allowedPermisions as $class=>$level){
				$selected = ($this->defaultRole == $level) 
					? ' selected="selected"' 
					: '';
?>
					<option value="<?php print($level); ?>"<?php print($selected); ?>><?php print(strtoupper($class)); ?></option>
<?php 		} ?>
				</select>
			</label>
<?php	} ?>
				
<?php	
		if ($this->formCommand != 'addUser') {
			
			if(($this->defaultRole == Account_Rd::LEVEL_FACULTY) 
				&& ($this->u->getRole() >= Account_Rd::LEVEL_STAFF) 
				&& !$this->editingSelf
			){ 
				$notTrainedSelected = !$this->trained ? 'checked="checked"' : '';
?>
					<label class="blockLabel bottomHalfMargin"><span class="block_2">Not Trained: </span><input type="checkbox" name="notTrained" value="notTrained" <?php print($notTrainedSelected); ?> /> <span class="helperText">Allow only student level access.</span></label>			
<?php		} ?>
	
<?php		if(($this->defaultRole >= Account_Rd::LEVEL_FACULTY) 
				&& (!$this->editingSelf || $this->trained)
			){ ?>
					<label class="blockLabel bottomHalfMargin"><span class="block_2">ILS User ID:</span>
						<input type="text" name="ilsUserId" size="20" value="<?php print($this->ilsUserId); ?>" /></label>
					<label class="blockLabel bottomMargin"><span class="block_2">ILS User Name:</span>
						<input type="text" name="ilsUsername" size="20" value="<?php print($this->ilsUsername); ?>" /></label>
<?php		} ?>
	
<?php		if($this->defaultRole >= Account_Rd::LEVEL_STAFF) { ?>
				<label class="blockLabel bottomHalfMargin"><span class="block_2">Primary Library:</span>
					<select name="staffLibrary">
<?php	
				foreach($this->libraries as $library){
					$librarySelected = ($this->staffLibrary == $library->getLibraryID()) 
						? ' selected="selected"' 
						: '';
?>
						<option value="<?php echo $library->getLibraryID(); ?>"<?php print($librarySelected); ?>><?php echo $library->getLibraryNickname(); ?></option>
<?php			} ?>
					</select>
				</label>
<?php		}

		}

		if ($this->formCommand == 'addUser' && $this->u->getRole() > Account_Rd::LEVEL_STAFF) {
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
<?php 
		}
?>
		<div style="text-align:center;"><input type="submit" name="editUserSubmit" value="Save Changes" /></div>

		</form>
	
</div>