<?php

$mgr = Rd_Registry::get('root:rootManager');
$calendar = Rd_Registry::get('root:calendarWidget');
$u = Rd_Registry::get('root:userInterface');
$alertMsg = Rd_Layout::getMessage('generalAlert');
$permissions = Rd_Registry::get('root:userPermissionLevels');

?><html>
<head>
<title><?php print(Rd_Registry::get('instanceName')); ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link rel="stylesheet" href="/website/960/code/css/960_custom.css" type="text/css" />
<link rel="stylesheet" href="/website/960/code/css/reset.css" type="text/css" />
<link rel="stylesheet" href="public/css/<?php print($_SESSION['css']); /*#TODO clean this up :P*/ ?>" type="text/css">
<link rel="stylesheet" href="public/css/960fix.css" type="text/css" />
<script language="JavaScript1.2" src="public/javascript/jsFunctions.js"></script>
<?php Rd_Layout::printJquery(); ?>
<?php
	if(method_exists($mgr, 'autoCss')){
		$mgr->autoCss();
	}
?>
</head>

<body onload="var elmt = (document.getElementById('searchTermFocus') || document.getElementById('search_inst')); if(elmt) { elmt.focus(); } else { focusOnForm(); }">

<div id="content" class="container_15">
				<?php $mgr->display(); ?>
				<div class="clear"></div>
</div>

</body>
</html>
