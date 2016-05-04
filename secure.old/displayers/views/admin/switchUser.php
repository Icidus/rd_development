<div class="grid_15 sizedText">
	<?php if ('' != trim($this->msg)) {?>
	<div  class="helperText"><?php print($this->msg); ?></div>
	<?php } ?>

	<div class="grid_6 suffix_1">
		<h2>Switching Users</h2>
		<p>Are you sure you want to switch users?</p>
		<ul class="bulleted">
			<li>You will be logged out, and logged back in as the new user.</li>
			<li>Anything you do will be treated as if the selected user had done it.</li>
			<li>Your access of this account will be recorded.</li>
			<li>You will not be able to use admin permissions until you log out and log back in with your admin account.</li>
			<li>You will not be logged out of external systems (i.e. Shibboleth).</li>
		</ul>
		<form method="post" action="./?cmd=switchUser">
			<input type="hidden" name="uid" value="<?php print($this->newUser->getUserID());?>"/>
			<input type="submit" name="confirmUserSwitch" value="Switch to this account"/>
		</form>
	</div>
	<div class="grid_7"><div class="grid_3 bordered appOptions shadowed_near">
		<h3>Switch To:</h3>
		<p> Username: <?php print($this->newUser->getUsername()); ?></p>
		<p> <?php print($this->newUser->getFirstName()); ?> <?php print($this->newUser->getLastName()); ?></p>
		<p> Permissions: <?php print(ucfirst($this->newUser->getUserClass())); ?></p>
	</div></div>
</div>