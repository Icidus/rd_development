<div class="grid_15 sizedText">
	<?php if ('' != trim($this->msg)) {?>
	<div  class="helperText"><?php print($this->msg); ?></div>
	<?php } ?>

	<div class="grid_6 suffix_1">
		<h2>Select a script</h2>
		<p class="warning">Only run these if you are certain you know what you are doing!</p>
		<ul class="bulleted">
<?php 
		foreach($this->scripts as $scriptName => $script) {
?>
			<li><a href="./?cmd=manualCron&script=<?php print($scriptName); ?>"><?php print($scriptName);?></a></li>
<?php 			
		}
?>			
		</ul>
	</div>
</div>