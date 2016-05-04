<head>
	<?php Rd_Layout::printIeStandardsMode(); ?>
	<?php Rd_Layout::printFavIcon(); ?>
	<title><?php print(Rd_Registry::get('instanceName')); ?> - Login</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link rel="stylesheet" href="public/css/reset.css" type="text/css" />
<link rel="stylesheet" href="public/css/960_custom.css" type="text/css" />
<link rel="stylesheet" href="public/css/thickbox.css" type="text/css" media="screen" />
<link rel="stylesheet" href="public/css/ReservesStyles.css" type="text/css">

<link rel="stylesheet" href="public/css/960fix.css" type="text/css" />

<script language="JavaScript1.2" src="public/javascript/jsFunctions.js"></script>

<script language="JavaScript1.2" src="public/javascript/ajax_transport.js"></script>
<script language="JavaScript1.2" src="public/javascript/basicAJAX.js"></script>
<?php Rd_Layout::printJquery(); ?>
<script type="text/javascript" src="public/javascript/thickbox-noconflict.js"></script>
<?php
	Rd_Layout::printJqueryUi(array('version' => '1.9.0'));
?>
		<style type="text/css" media="screen">
			/*
			******************************************
			Login
			******************************************
			*/
			.centered {margin-left:auto;margin-right:auto; display:block;float:none;}
			.threeQuarterWidth {width:75%;}
			.halfWidth {width:50%;}
			.rightJustified {text-align:right;}
			.centerJustified {text-align:center;}
			.leftLogin {float:left; clear:both; width:40%;}
			.sideContent {float:right; clear:none; width:59%;}
			.noTopMargin {margin-top:0 !important;}
			.noBottomMargin {margin-bottom:0 !important;}
			.altLogin, .mainLogin {text-align:left;}
			.altLogin p, .mainLogin > p {font-style:italic;}
			.error {color:#c00; font-style:normal; text-align:center;}
			label {display:block;font-weight:bold;}
			#rdLoginForm label > span {display:inline-block;width:5.5em;}
			.topMargin {margin-top:1em;}
			.bottomMargin{margin-bottom:1em;}
			.iconRight {margin-left:0.25em;}
			.largeTextButton{font-size:24px;}
			.noUnderline, 
			.noUnderline a,
			.noUnderline a:link, 
			.noUnderline a:visited, 
			.noUnderline > *, 
			.noUnderline *{border:0;text-decoration:none;}
		</style>
		<script language ="">
			$(document).ready(function() {
				$('#username').focus();
			});
		</script>

</head>

<body onload="var elmt = (document.getElementById('searchTermFocus') || document.getElementById('search_inst')); if(elmt) { elmt.focus(); } else { focusOnForm(); }">

<!--help sidebar-->
<?php 

Rd_Layout::includeFile('help/sidebar.php'); 
Rd_Layout::includeFile('header.php'); 

$excludedCommands = array('login','logout');
$commandExcluded = array_key_exists('cmd', $_GET) 
	&& in_array($_GET['cmd'],$excludedCommands);

$loginOnly = array_key_exists('cmd', $_GET) && !$commandExcluded;
if($loginOnly){
	//#TODO so a sanitize utility
	try{
		$actionLabel = Rd_Dictionary::get('actions:' . $_GET['cmd'] . ':userLabel');
		$moreSpecificAction = htmlentities(" to {$actionLabel}");
	} catch (Exception $e) {
		$moreSpecificAction = ' to Proceed';
	}
} else {
	$moreSpecificAction = '';
}

$instanceName = Rd_Registry::get('root:instanceName');
$institutionUrl = Rd_Registry::get('root:libraryUrl');
$institutionName = Rd_Registry::get('root:institutionName');

?>
<div id="main-container" class="container_16"><div class="padding">
	<h1 class="loginTitle"><?php print($instanceName); ?></h1>
	<div <?php print($loginOnly ? ' class="centered grid_6"' : ''); ?>>
		<div class="mainLogin grid_6">
		<?php if(Rd_Layout::hasMessage('loginError')){ ?>
			<p class="error"><?php print(Rd_Layout::getMessage('loginError')); ?></p>
			<?php print(Rd_Layout::getMessage('emergencyLogout')); ?>
<?php } ?>
			<?php 
				$shibAlreadyLoggedIn = Rd_Auth::pluginIsLoaded('Shibboleth') && Rd_Auth_Shibboleth::alreadyLoggedIn();
				if (
					!$shibAlreadyLoggedIn
					&& (
						!array_key_exists('cmd', $_GET) 
						|| 'logout' != $_GET['cmd']
					)
				) {
					Rd_Layout::includeFile('login/desktopForm.php','',
						array(
							'loginOnly'=>$loginOnly,
							'moreSpecificAction'=>$moreSpecificAction,
							'commandExcluded' => $commandExcluded
						)
					); 
				} else if($shibAlreadyLoggedIn) {
?>
					<p>Please use your Unity ID when logging in with Shibboleth. Other ogranizational affiliations are not allowed.</p>
<?php 					
				}	else {
?>
					<h3>You are logged out of <?php print($instanceName); ?>.</h3>
					<p>You may <a href="./?cmd=login">Log in</a> again.</p>
					<p>Return to the <a href="<?php print($institutionUrl); ?>"><?php print($institutionName) ?></a> web site.</p>
<?php 
				}
			?>
		</div>	
	</div>
	<?php if (!$loginOnly){ ?>
		<div class="grid_7 prefix_1">
			<?php Rd_Layout::includeFile('splash/general.php'); ?>		
		</div>
	<?php } ?>
	    <div class="clearFix">
	</div>
</div></div>
 	<?php Rd_Layout::includeFile('footer.php'); ?>


	</body>
	</html>
<?php 