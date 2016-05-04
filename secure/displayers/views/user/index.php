<div class="prefix_1 grid_12 ">
	<?php if ('' != trim($this->msg)) {?>
	<div  class="helperText"><?php print($this->msg); ?></div>
	<?php } ?>
	<div class="grid_4 suffix_1"><div class="grid_4 bordered appOptions shadowed_near">
		<h3>Edit</h3>
		<ul>
			<li><a href="index.php?cmd=editUser">Edit a user profile</a></li>
<?php  if (Account_Rd::isAdmin()) { ?>
			<li><a href="index.php?cmd=setGuest">Enable/Disable guest access</a></li>
			<li><a href="index.php?cmd=switchUser">Log In As Another User</a></li>
			<li><a href="index.php?cmd=mergeUsers">Merge Users</a></li>
<?php } ?>
			<br>
			<li><a href="index.php?cmd=editProfile">Edit my profile</a></li>
		</ul>
	</div></div>
	<div class="grid_3 suffix_1"><div class="grid_3 bordered appOptions shadowed_near">
		<h3>Assign</h3>
		<ul>
			<li><a href="index.php?cmd=assignProxy">Assign Proxy to a Class</a></li>
			<li><a href="index.php?cmd=assignInstr">Assign Instructor to a Class</a></li>
		</ul>
	</div></div>
<?php  if (Account_Rd::isAdmin()) { ?>
	<div class="grid_3"><div class="grid_3 bordered appOptions shadowed_near">
		<h3>Create</h3>
		<ul>
			<li><a href="index.php?cmd=addUser">Create a new user</a></li>
			<!-- <li><a href="index.php?cmd=setPwd">Set Override password</a></li> -->
		</ul>
	</div></div>
<?php  } ?>
</div>