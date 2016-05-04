<div class="prefix_1 grid_13 ">
	<?php if ('' != trim($this->msg)) {?>
	<div  class="helperText"><?php print($this->msg); ?></div>
	<?php } ?>

	<div class="grid_4 suffix_1"><div class="grid_4 bordered appOptions shadowed_near">
		<h3>Main</h3>
		<ul>
			<li><a href="./?cmd=admin&function=editDept">Add/Edit Departments</a></li>
			<li><a href="./?cmd=admin&function=editLib">Add/Edit Libraries</a></li>
			<li><a href="./?cmd=admin&function=editTerms">Add/Edit Terms</a></li>
			<li><a href="./?cmd=admin&function=editNews">Add/Edit News</a></li>
			<li><a href="./?cmd=admin&function=editClassFeed">Manage Course Feed for a Class</a></li>
			<li><a href="./?cmd=admin&function=clearReviewedFlag">Flag Course for Copyright Review</a></li>
		</ul>
	</div></div>
	<div class="grid_3 suffix_1"><div class="grid_3 bordered appOptions shadowed_near">
		<h3>Diagnostics</h3>
		<ul>
			<li><a href="./?cmd=switchUser">Log In As Another User</a></li>
			<li><a href="./?cmd=testMxe">Test Cisco MXE integration</a></li>
			<li><a href="json.php?rd_service_key=<?php print($this->primeServiceKey); ?>">Test JSON Service</a></li>
			<li><a href="./?cmd=manualCron">Run Scripts Manually</a></li>
		</ul>
	</div></div>
	<div class="grid_4"><div class="grid_4 bordered appOptions shadowed_near">
		<h3>Help</h3>
		<ul>
			<li><a href="./?cmd=help">Browse Articles</a></li>
			<li><a href="./?cmd=helpEditArticle">Add Article</a></li>
			<li><a href="./?cmd=helpEditCategory">Add Category</a></li>
			<li><form method="post" action="./?cmd=helpEditCategory">
				<label> Edit 
				<?php helpDisplayer::displayCategorySelect(); ?>
				Category</label>
				<input type="submit" name="submit" value="Edit" />
			</form></li>
		</ul>
	</div></div>
</div>