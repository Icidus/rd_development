<p>An error occured:</p>
<p><?php print($this->message);?></p>
<p><?php print(Rd_Config::get('supportMessage')); ?></p>
<?php if (Rd_Debug::isEnabled() && $this->exception) { ?>
	<p>Additional Debugging Information:</p>
	<p class="exceptionClass"><?php print(get_class($this->exception)); ?></p>
	<p class="mainMessage"><?php print($this->exception->getMessage()); ?></p>
	<pre class="exceptionTrace"><?php print($this->exception->getTraceAsString()); ?></pre>
<?php  } 