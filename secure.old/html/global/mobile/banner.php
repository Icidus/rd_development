<?php

try{
	$u = Rd_Registry::get('root:userInterface');
	$ci = Rd_Registry::get('root:selectedCourseInstance');
} catch (Exception $e) {
	$u = NULL;
	$ci = NULL;
}

if(
	is_object($ci) 
	&& is_a($ci,'courseInstance') 
	&& !is_null($ci->getCourseInstanceID())
) {
	if (
		!is_object($ci->course) 
		|| is_a($ci->course,'course')
	) {
		$ci->getCourseForUser($u->getUserID());
	}
	if (!is_null($ci->course)) {
		$header = $ci->course->displayCourseNo();
	}
} else {
	$header = Rd_Registry::get('instanceName');
}
?>

<div id ="breadcrumbs" data-role="header">
	<a href="<?php print(RD_Registry::get('libraryMobileUrl')); ?>" data-role="none">
		<img src="<?php print(Rd_Registry::get('libraryMobileLogo')); ?>" class="subpageLogo" alt="<?php print(Rd_Registry::get('institutionName')); ?> Logo">
	</a>
	<h1><?php print(Rd_Registry::get('instanceName')); ?></h1>
	<?php if ($u && $u->isLoggedIn()){ ?>
		<a href="./?cmd=logout&mobile=true">Logout</a>
	<?php } ?>
</div>