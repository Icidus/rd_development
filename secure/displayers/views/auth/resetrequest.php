<div class="grid_15">
<?php if ('' != trim($this->msg)) {?>
	<div  class="helperText"><?php print($this->msg); ?></div>
	<?php } ?>

<h2 class="formHeader grid_8">Password Reset Request</h2>
<form class="bordered clearing basicPadding bottomMargin" action="./?cmd=resetPasswordRequest" method="post" name="editUser">
<div class="note">
	<p>You may request to initiate a password reset only if you have Guest Access to <?php print($this->instanceName);?>.</p>
	<p> If you are affiliated with the university (student, staff, or faculty) you should <a href="./?cmd=login&loginRealm=Shibboleth">use your Unity login and password to access this application</a>.</p>
</div>
			<p class="bottomHalfMargin">Please provide the e-mail address associated with your account:</span><strong></p>
			<label class="blockLabel bottomHalfMargin"><span class="block_3">E-mail:</span>
				<input name="email" size="40" type="text" value="<?php print($this->email); ?>" /></label>
		<div style="text-align:center;"><input type="submit" name="resetPasswordSubmit" value="Initiate Password Reset" /></div>
</form>
	
</div>