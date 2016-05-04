<div id="newStyles" class="prefix_1 suffix_1">
<h3>Your Password Has Been Reset.</h3>
	<?php if ('' != trim($this->msg)) {?>
	<p class="helperText"><?php print($this->msg); ?></p>
	<?php } ?>
<p>You may <a href="./?cmd=login&loginRealm=Local">Log in</a>. Your username is: <?php print($this->username); ?>.</p>

</div>