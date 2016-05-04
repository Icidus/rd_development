<div id="newStyles" class="prefix_1 suffix_1">
<h3>Logging you out of <?php print($this->instance); ?> <?php print('' != $this->realmName ? ' and ' . $this->realmName : ''); ?></h3>
<iframe id="framedLogout" src="<?php print($this->logoutFrameUrl); ?>"></iframe>
<p>You may <a href="./?cmd=login">Log in</a> again.</p>
<p>Return to the <a href="<?php print($this->institutionUrl); ?>"><?php print($this->institutionName) ?></a> web site.</p>
</div>