<?php
	$loc = Rd_Layout_Location::get();
	$loc = (
		'' != trim($loc)
		? $loc . ' &gt;&gt;'
		: ''
	);
	
	$ci = Rd_Registry::get('root:selectedCourseInstance');

if(($ci instanceof courseInstance) && !is_null($ci->getCourseInstanceID())) {
	if (!$ci->course instanceof course) $ci->getCourseForUser($u->getUserID());
	if (isset($ci->course)) $currentClass = $ci->course->displayCourseNo() . " " . $ci->course->getName();
} else {
	$currentClass = "";
}

if ('' != $loc || '' !=$currentClass) {
?>
	<div id="location">
		<div class="locationIndicatorText"> <?php print($loc); ?> </div>
		<div class="currentClass"> <?php print($currentClass); ?> </div>
		<div class="clear"></div>
	</div>
<?php 
}
