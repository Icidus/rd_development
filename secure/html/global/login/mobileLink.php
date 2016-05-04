<div class="mobileNote centerJustified">
	<?php print(Rd_Registry::get('instanceName')); ?> Mobile : <a href="<?php print(Rd_UrlRewrite::scrubGet($_SERVER['REQUEST_URI'], array_merge($_GET, array('mobile' => 'true')), array('exclude'=>array('failureCode')))); ?>">Log in here<span class="icon iconRight"></span></a>
</div>
<br/>
<br/>