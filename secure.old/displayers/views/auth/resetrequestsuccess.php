<div id="newStyles" class="prefix_1 suffix_1">
<h3>Your Password Reset Has Been Initiated.</h3>
	<?php if ('' != trim($this->msg)) {?>
	<p class="helperText"><?php print($this->msg); ?></p>
	<?php } ?>
<p>You should receive an e-mail addressed to <strong><?php print($this->email); ?></strong> shortly. It will contain a link with a verification code that will allow you to reset your password. If you do not see this email, please check your spam folder.</p>
<p>Initiating another reset request will invalidate verification codes for any previous reset requests.</p>
<p>After you successfully reset your password or log in, the verification code will be invalidated.</p>

</div>