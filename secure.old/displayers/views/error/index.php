<div id="newStyles">
<h4><?php print($this->message); ?></h4>
<?php  if($this->promptIt) {
	print(Rd_Registry::get('itAssistanceMessage'));
} else {
	print(Rd_Registry::get('assistanceMessage'));
} ?>
<p>Some information that will be helpful in diagnosing your problem:<p>
<ul><li>Requested URL: <?php print(htmlentities($this->requestUrl));?></li>
<?php  if('' != $this->referrerUrl) { ?>
	<li>Referring URL: <?php print(htmlentities($this->referrerUrl)); ?></li>
<?php } ?>
<li>Your IP: <?php print($this->host); ?></li>
<li>Your Browser ID: <?php print(htmlentities($this->agent)); ?></li></ul>
<?php if ($this->exceptionDump) { ?>
<div class="exceptionDump"><pre><?php print($this->exceptionDump); ?></pre></div>
<?php } ?>
</div>