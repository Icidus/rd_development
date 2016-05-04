<?php
	$permissions = Rd_Registry::get('root:userPermissionLevels');
	$u = Rd_Registry::get('root:userInterface');
	$subMenu = array();
	switch(Rd_Layout_Tab::get()) {
		case 'myReserves':
			//$subMenu[] = '<a href="index.php" class="firstLink"> Home</a>';
		
			if($u->getRole() >= $permissions['instructor']) {
				$subMenu[] = '<a href="index.php?cmd=createClass">Create New Class</a>';
				$subMenu[] = '<a href="index.php?cmd=importClass">Reactivate Class</a>';
			}
			if($u->getRole() >= $permissions['proxy']) {
				$subMenu[] = '<a href="index.php?cmd=exportClass">Export Class</a>';
				$subMenu[] = '<a href="index.php?cmd=editClass&amp;tab=enrollment">Manage Enrollment</a>';
			}
			if($u->getRole() <= $permissions['proxy']) {
				$subMenu[] = '<a href="index.php?cmd=addClass">Join Class</a>';
				$subMenu[] = '<a href="index.php?cmd=removeClass">Leave Class</a>';
			}
		break;
	}
?>

<?php	foreach($subMenu as $smLink){ ?>
				<li><?php print($smLink); ?></li>
<?php	} ?>



