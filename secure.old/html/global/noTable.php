<?php

$mgr = Rd_Registry::get('root:rootManager');
$calendar = Rd_Registry::get('root:calendarWidget');
$u = Rd_Registry::get('root:userInterface');
$alertMsg = Rd_Layout::getMessage('generalAlert');
$permissions = Rd_Registry::get('root:userPermissionLevels');

?><html>
<html>
<head>
<title>ReservesDirect</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">

<link rel="stylesheet" href="public/css/<?php print($_SESSION['css']); ?>" type="text/css">

<script language="JavaScript" src="public/javascript/jsFunctions.js"></script>
<script language="JavaScript1.2" src="public/javascript/prototype.js"></script>
<script language="JavaScript1.2" src="public/javascript/ajax_transport.js"></script>
<script language="JavaScript1.2" src="public/javascript/basicAJAX.js"></script>

<!-- start JSCalendar files -->
<?php
	//load JSCalendar JS files
	$calendar->load_files();
?>
<!-- end JSCalendar files -->
</head>

<body bgcolor="#FFFFFF" text="#000000">
    
    <?php $mgr->display(); ?>

</body>
</html>
