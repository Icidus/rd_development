<div class="prefix_1 grid_12 ">
	<?php if ('' != trim($this->msg)) {?>
	<div  class="helperText"><?php print($this->msg); ?></div>
	<?php } ?>
	<div class="grid_3 suffix_1"><div class="grid_3 bordered appOptions shadowed_near">
		<h3>Edit</h3>
		<ul>
			<li><a href="index.php?cmd=editProfile">Edit my profile</a></li>
		</ul>
	</div></div>
	<div class="grid_7 suffix_1"><div class="grid_7 bordered appOptions shadowed_near">
		<h3> Course Proxies</h3>
		<div class="padding"><div class="policyNotice"><h4>About proxies:</h4>
		<ul>
			<li>The person you set as proxy must first have an account. If they do not show up in search, have them log in and an account will automatically be created.</li>
			<li>Proxies can manage enrollment and add reserves to the course.</li>
			<li>Proxies only have access to the courses they are added to.</li>
			<li>Proxy access has to be renewed each semester.</li>
		</ul></div></div>
		<ul>
			<li><a href="index.php?cmd=addProxy">Assign Proxy to a Class</a></li>
			<li><a href="index.php?cmd=removeProxy">Remove Proxy from a Class</a></li>
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