<div class="grid_15">
<?php if ('' != trim($this->msg)) {?>
	<div  class="helperText"><?php print($this->msg); ?></div>
	<?php } ?>

<h2 class="formHeader grid_8">Password Reset</h2>
<form class="bordered clearing basicPadding bottomMargin" action="./?cmd=resetPassword" method="post" name="editUser">
	<input type="hidden" name="username" value="<?php print($this->username); ?>" />
	<input type="hidden" name="v" value="<?php print($this->secret); ?>" />
<div class="note">Please provide a new password that is at least 6 characters long.</div>
			<p class="bottomHalfMargin"><span class="block_3">User Name:</span><strong><?php print($this->username); ?></strong></p>
			<label class="blockLabel bottomHalfMargin"><span class="block_3">New Password:</span>
				<input name="password" size="40" type="password" value="" /></label>
			<label class="blockLabel bottomHalfMargin"><span class="block_3">Reenter Password:</span>
				<input name="passwordConfirm" size="40" type="password" value="" /></label>
		<div style="text-align:center;"><input type="submit" name="resetPasswordSubmit" value="Reset Password" /></div>
</form>
	
</div>