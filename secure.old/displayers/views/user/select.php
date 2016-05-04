<div class="grid_15">
<?php if ('' != trim($this->msg)) {?>
	<div  class="helperText"><?php print($this->msg); ?></div>
	<?php } ?>

<div class="cancelNavigation">[ <a href="index.php?cmd=manageUser">Cancel</a> ]</div>

<h2 class="formHeader grid_8">SELECT USER</h2>
<form class="bordered clearing basicPadding bottomMargin" action="./?cmd=<?php print($this->formCommand); ?>" method="post" name="editUser">
	<div class="grid_7">
		<label class="blockLabel bottomHalfMargin">Search Term: <input type="text" name="searchTerm" value="<?php print($this->searchTerm); ?>" size="40" /></label>
		<div style="text-align:center;"><input type="submit" name="searchUserSubmit" value="Search Users" /></div>
	</div>
	<?php  
		if (!is_null($this->userList)) { ?>
	<div class="grid_7">
		<h3>Matching Users:</h3>
		<ul class="discList">
		<?php 
			if (0 == count($this->userList)) { 
?>
			<li>No Users Matched.</li>					
<?php
	 		}
			foreach($this->userList as $userId=>$userDisplay) { 
?>
			<li><a href="./?cmd=<?php print($this->formCommand); ?>&uid=<?php print($userId) ;?>"><?php print($userDisplay); ?></a></li>
<?php
			}
?>
		</ul>
	</div>
	<?php } ?>
	<div class="clear"></div>
</form>
	
</div>