<div class="prefix_3 grid_10 ">
	<?php if ('' != trim($this->msg)) {?>
	<div  class="helperText"><?php print($this->msg); ?></div>
	<?php } ?>
	<div class="grid_3 suffix_1"><div class="grid_3 bordered appOptions shadowed_near">
		<h3>Basic Functions</h3>
		<ul>
			<li><a href="index.php?cmd=createClass">Create Class</a></li>
			<li><a href="index.php?cmd=editClass">Edit Class</a></li>
			<li><a href="index.php?cmd=exportClass">Export Class</a></li>
		</ul>
	</div></div>			
	<div class="grid_4"><div class="grid_4 bordered appOptions shadowed_near">
		<h3>Advanced Functions</h3>
		<ul>		
			<li><a href="index.php?cmd=importClass">Reactivate Class</a></li>
			<li><a href="index.php?cmd=copyClass">Copy Reserves or Merge Classes</a></li>
			<li><a href="index.php?cmd=editCrossListings">Edit Crosslistings</a></li>
			<li><a href="index.php?cmd=deleteClass">Delete Class</a></li>
		</ul>
	</div></div>
</div>