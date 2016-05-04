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
<?php 
	$use960css = false;
	$useJQueryUi = false;
	$usePrototype = true;
	if(method_exists($mgr, 'hasOption')){
		$splashLayout = $mgr->hasOption('splash');
		if($mgr->hasOption('noPrototype')) {
			$usePrototype = false;
		}
		if( $mgr->hasOption('960css') || $splashLayout) { 
			$use960css = true;
?>
<link rel="stylesheet" href="public/css/reset.css" type="text/css" />
<link rel="stylesheet" href="public/css/960_custom.css" type="text/css" />
<?php
		}
		if($mgr->hasOption('fileuploader')) {
?>
<link rel="stylesheet" href="public/css/fileuploader.css" type="text/css">
<script src="public/javascript/fileuploader.js" type="text/javascript"></script>
<?php
		}
}
?>
<link rel="stylesheet" href="public/css/thickbox.css" type="text/css" media="screen" />

<link rel="stylesheet" href="public/css/ReservesStyles.css" type="text/css">

<?php if ($use960css) { ?>
<link rel="stylesheet" href="public/css/960fix.css" type="text/css" />
<?php } ?>
<script language="JavaScript1.2" src="public/javascript/jsFunctions.js"></script>
<?php if ($usePrototype) { ?>
<script language="JavaScript1.2" src="public/javascript/prototype.js"></script>
<?php } ?>
<script language="JavaScript1.2" src="public/javascript/ajax_transport.js"></script>
<script language="JavaScript1.2" src="public/javascript/basicAJAX.js"></script>
<?php Rd_Layout::printJquery(); ?>
<?php if ($usePrototype) { ?>
<script>jQuery.noConflict();</script>
<?php } ?>
<script type="text/javascript" src="public/javascript/thickbox-noconflict.js"></script>
<?php
	if(method_exists($mgr, 'hasOption')){
		if( $mgr->hasOption('jQueryUi')) {
			$useJQueryUi = true;
			Rd_Layout::printJqueryUi(array('version' => '1.9.0'));
		}
	}
?>
<!-- start JSCalendar files -->
<?php $calendar->load_files(); //load JSCalendar JS files $calendar is globally defined in index.php?>
<!-- end JSCalendar files -->
<?php
	if(method_exists($mgr, 'autoCss')){
		$mgr->autoCss();
	}
?>
</head>

<body onload="var elmt = (document.getElementById('searchTermFocus') || document.getElementById('search_inst')); if(elmt) { elmt.focus(); } else { focusOnForm(); }">

<!--help sidebar-->
<?php Rd_Layout::includeFile('help/sidebar.php'); ?>
<?php Rd_Layout::includeFile('header.php'); ?>
<div id="container" class="helpOff<?php print( $splashLayout ? ' container_15' : '');?>">
	<div id="contentPadding">
		<div id="navigation">
			<?php  if (!in_array('logout', Rd_Registry::get('root:commandStack'))) {
				Rd_Layout::includeFile('nav/functions.php'); 
				
			}?>
					<div class="resourceLinks"><h1><?php print(Rd_Registry::get('instanceName'));?></h1></div>
		<div class="clear"></div>
		<?php  if (!in_array('logout', Rd_Registry::get('root:commandStack'))) {
				 Rd_Layout::includeFile('nav/main.php', NULL, array('u'=>$u));
			} ?>
			<?php ?>
		</div>
		<div id="contentBorder"<?php print( $splashLayout ? ' class="grid_9"' : '');?>">
				<?php Rd_Layout::includeFile('nav/sub.php', NULL, array('u'=>$u,'permissions'=>$permissions)); ?>
				<div style="clear:both;"></div>
		
	  	<?php Rd_Layout::includeFile('nav/location.php', NULL); ?>
	  
	  	<?php
	  		
		  	//Display NoScript error if greater than proxy and javascript is not enabled
		  	if ($u->getRole() >= $permissions['proxy'])
		  		echo "<noscript><div id=\"noJavaAlert\" class=\"failedText\">" . Rd_Dictionary::get('messages:noJsWarning') . "</div></noscript>\n";
		?>
	    
	    <?php
	    	if (!is_null($news = news::getNews($u->getRole())))
	    	{    		
	    		echo "<div id=\"displayMsg\">";
	    		for($i=0; $i < count($news); $i++)
	    		{
	    			echo "<div class=\"". $news[$i]['class'] ."\">" . $news[$i]['text'] . "</div>\n";
	    		}
	    		echo "</div>";
	    	}        	
	    ?>
		 	 
		    <div id="alertMsg" class="failedText">
		        <?php if ('' != trim($alertMsg)) print(htmlentities($alertMsg)); ?>
		    </div>
		   	<div id="content" <?php if($use960css){ print($splashLayout ? 'class="grid_9"' : 'class="container_15"');} ?>>
				<?php $mgr->display(); ?>
				<div class="clear"></div>
			</div>
		</div>
		<?php  if($splashLayout) { ?>
			<div class="grid_6 prefix_twothirds"><div id="splashContent">
				<?php Rd_Layout::includeFile('splash/general.php'); ?>
			</div></div>	
		<?php } ?>
		<div class="clear"></div>
	</div>
	
 	<?php Rd_Layout::includeFile('footer.php'); ?>
</div>
</body>
</html>