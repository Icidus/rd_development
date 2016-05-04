<?php

$mgr = Rd_Registry::get('root:rootManager');
$calendar = Rd_Registry::get('root:calendarWidget');
$u = Rd_Registry::get('root:userInterface');
$alertMsg = Rd_Layout::getMessage('generalAlert');
$permissions = Rd_Registry::get('root:userPermissionLevels');
header("Content-type: text/html; charset=utf-8");
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
<!-- <link rel="stylesheet" href="public/css/960_custom.css" type="text/css" /> -->
<link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootswatch/3.3.6/yeti/bootstrap.min.css" type="text/css" />
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.6.1/css/font-awesome.min.css">


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

<!-- <link rel="stylesheet" href="public/css/ReservesStyles.css" type="text/css"> -->
<?php if ($use960css) { ?>
<link rel="stylesheet" href="public/css/960fix.css" type="text/css" />
<?php } ?>
<script language="JavaScript1.2" src="public/javascript/jsFunctions.js"></script>
<?php if ($usePrototype) { ?>
<script language="JavaScript1.2" src="public/javascript/prototype.js"></script>
<?php } ?>
<script language="JavaScript1.2" src="public/javascript/ajax_transport.js"></script>
<script language="JavaScript1.2" src="public/javascript/basicAJAX.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js" integrity="sha384-0mSbJDEHialfmuBBQP6A4Qrprq5OVfW37PRR3j5ELqxss1yVqOtnepnHVP9aJ7xS" crossorigin="anonymous"></script>

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
<style>
/*
 * Base structure
 */

/* Move down content because we have a fixed navbar that is 50px tall */
body {
  padding-top: 50px;
}


/*
 * Global add-ons
 */

.sub-header {
  padding-bottom: 10px;
  border-bottom: 1px solid #eee;
}

/*
 * Top navigation
 * Hide default border to remove 1px line.
 */
.navbar-fixed-top {
  border: 0;
}

/*
 * Sidebar
 */

/* Hide for mobile, show later */
.sidebar {
  display: none;
}
@media (min-width: 768px) {
  .sidebar {
    position: fixed;
    top: 51px;
    bottom: 0;
    left: 0;
    z-index: 1000;
    display: block;
    padding: 20px;
    overflow-x: hidden;
    overflow-y: auto; /* Scrollable contents if viewport is shorter than content. */
    background-color: #f5f5f5;
    border-right: 1px solid #eee;
  }
}

/* Sidebar navigation */
.nav-sidebar {
  margin-right: -21px; /* 20px padding + 1px border */
  margin-bottom: 20px;
  margin-left: -20px;
}
.nav-sidebar > li > a {
  padding-right: 20px;
  padding-left: 20px;
}
.nav-sidebar > .active > a,
.nav-sidebar > .active > a:hover,
.nav-sidebar > .active > a:focus {
  color: #fff;
  background-color: #428bca;
}


/*
 * Main content
 */

.main {
  padding: 20px;
}
@media (min-width: 768px) {
  .main {
    padding-right: 40px;
    padding-left: 40px;
  }
}
.main .page-header {
  margin-top: 0;
}


/*
 * Placeholder dashboard ideas
 */

.placeholders {
  margin-bottom: 30px;
  text-align: center;
}
.placeholders h4 {
  margin-bottom: 0;
}
.placeholder {
  margin-bottom: 20px;
}
.placeholder img {
  display: inline-block;
  border-radius: 50%;
}
</style>	
<body onload="var elmt = (document.getElementById('searchTermFocus') || document.getElementById('search_inst')); if(elmt) { elmt.focus(); } else { focusOnForm(); }">

<!--help sidebar-->
<?php Rd_Layout::includeFile('help/sidebar.php'); ?>

<!-- Header -->

<?php Rd_Layout::includeFile('header.php'); ?>
	<div id="container-fluid" class="helpOff<?php print( $splashLayout ? ' container_15' : '');?>">

<!-- Sidebar -->
      <div class="row">
        <div class="col-sm-3 col-md-2 sidebar">
	       	<ul class="nav nav-sidebar">
		  <?php  if (!in_array('logout', Rd_Registry::get('root:commandStack'))) {
				 Rd_Layout::includeFile('nav/main.php', NULL, array('u'=>$u));
			} ?>
			<?php Rd_Layout::includeFile('nav/sub.php', NULL, array('u'=>$u,'permissions'=>$permissions)); ?>
	       	</ul>
       </div>

<!-- Main Content -->
    <div class="col-sm-9 col-sm-offset-3 col-md-10 col-md-offset-2 main">   	
	<div id="contentPadding">
		<div id="navigation">
			<div class="resourceLinks">
				<h1 class="pull-left"><?php print(Rd_Registry::get('instanceName'));?></h1>
				<?php if ($u->isLoggedIn()) { ?>
					<span class="pull-right">Logged in as: <?php print($u->getName(false)); ?> ( <?php print($u->getUsername()); ?> )</span>
				<?php } ?>
			</div>
			<div id="contentBorder<?php print( $splashLayout ? ' class="grid_9"' : '');?>">
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
<!-- 		    Load main content -->
		   	<div>
				<?php $mgr->display(); ?>
				<div class="clear"></div>
				<?php Rd_Layout::includeFile('footer.php'); ?>
			</div>
		</div>
		<?php  if($splashLayout) { ?>
			<div class="grid_6 prefix_twothirds"><div id="splashContent">
				<?php Rd_Layout::includeFile('splash/general.php'); ?>
			</div>
		</div>	
		<?php } ?>
		<div class="clear"></div>
		</div>
    </div>
</div>	
</div>
</body>
</html>