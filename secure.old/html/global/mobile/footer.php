<div id="fullsitelink">
	<a href="./?mobile=false">Access Full <?php print(Rd_Registry::get('instanceName')); ?> Site</a>
<?php 
	if (isset($loginPage) && $loginPage) {
		if ('Local' != $realm) {
?>
	| <a href="./?loginRealm=Local"><?php print($guestLabel); ?> Log In</a>
<?php 		
		} else {
?>
	| <a href="./?loginRealm=<?php print($altRealm); ?>"><?php print($institutionLabel); ?></a>
<?php 			
		}
	}
?>	<br /><br />
</div>