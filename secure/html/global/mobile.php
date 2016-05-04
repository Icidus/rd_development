<?php 

$mgr = Rd_Registry::get('root:rootManager');

?><html>
	<head>
		<title>ReservesDirect</title>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
		<meta name="viewport" content="width=device-width, user-scalable=no">
		
		<!-- <link rel="stylesheet" href="public/css/mobile.css" type="text/css"> -->
		
		<link rel="apple-touch-icon" sizes="57x57" href="public/images/mobile/homescreen-icon-57x57.png" />
			<link rel="apple-touch-icon" sizes="114x114" href="public/images/mobile/homescreen-icon-114x114.png" />
			<link rel="apple-touch-icon-precomposed" href="public/images/mobile/homescreen-icon-57x57.png"/>
		  	<link rel="stylesheet" href="public/css/jquery.mobile-1.0.min.css" />
		    <?php if (Rd_Debug::isEnabled()) {
?>
			<style>
				.ui-mobile [data-role="page"], .ui-mobile [data-role="dialog"], .ui-page {position:static;}
			</style>
<?php 
		    }?>
		    <link rel="stylesheet" href="public/themes/general-mobile-theme.min.css" />
		    
		<link rel="stylesheet" href="public/css/general-mobile.css" type="text/css">
		<script type="text/javascript" src="public/javascript/jquery-1.6.4.min.js"></script>
			<script>
				$(document).bind("mobileinit", function(){
					$.mobile.ajaxEnabled = false;
					$.mobile.pushStateEnabled = false;
					$.mobile.hashListeningEnabled = false;
					//$.mobile.attachEvents = disabled;
				});
			</script>
		<script src="public/javascript/jquery.mobile-1.0.min.js"></script>
	</head>
	<body id="mobileBody">
		<div id="container" data-role="page" data-theme="a">	
			<?php Rd_Layout::includeFile('mobile/banner.php'); ?>
		    <div id="content" data-role="content" class="reducedMargin">
		    	<?php 
		    	
		    	$mgr->display(); 
		    	
		    	?>
		    </div>
			<?php Rd_Layout::includeFile('mobile/footer.php'); ?>
		</div>
	</body>
</html>
