<div class="prefix_5 grid_8 ">
<?php 
	$withOptionalCi = $this->ci ? "&ci={$this->ci}" : '';
	if ('' != trim($this->msg)) {?>
	<div  class="helperText"><?php print($this->msg); ?></div>
	<?php } ?>
	<div class="grid_5"><div class="grid_5 bordered appOptions shadowed_near">
		<h3>Add/Process Materials</h3>
		<ul>
			<li><a href="./?cmd=displayRequest" align="center">Process Requests</a></li>
			<li><a href="./?cmd=addDigitalItem<?php print($withOptionalCi); ?>" align="center">Add an Electronic Item</a></li>
<?php 
if($this->useVideoUpload){ ?>
			<li><a href="./?cmd=addVideoItem<?php print($withOptionalCi); ?>" align="center">Upload a Video Item</a></li>
			<!-- <li><a href="./?cmd=addVideoItem2<?php print($withOptionalCi); ?>" align="center">NEW Upload a Video Item</a></li> -->
<?php } ?>
			<li><a href="./?cmd=addPhysicalItem<?php print($withOptionalCi); ?>">Add a Physical Item</a></li>
<?php 
if($this->ci) { ?>
			<li><a href="./?cmd=searchScreen&ci=<?php print($this->ci); ?>">Search for the Item</a></li>
			<li><a href="./?cmd=importClass&new_ci=<?php print($this->ci); ?>" align="center">Import Class</a></li>
<?php } ?>
		</ul>
	</div></div>
</div>
