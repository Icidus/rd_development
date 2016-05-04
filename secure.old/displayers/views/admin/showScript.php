<div class="grid_15 sizedText">
	<?php if ('' != trim($this->msg)) {?>
	<div  class="helperText"><?php print($this->msg); ?></div>
	<?php } ?>

	<div class="grid_6 suffix_1">
		<h2>Script Results</h2>
		<pre>
<?php print($this->result) ?>

</pre>
		</ul>
	</div>
</div>