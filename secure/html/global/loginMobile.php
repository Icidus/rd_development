<html>
	<head>
		<title><?php print(Rd_Registry::get('instanceName')); ?></title>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
		<meta name="viewport" content="width=device-width, user-scalable=no">
		
		<link rel="apple-touch-icon" sizes="57x57" href="public/images/mobile/homescreen-icon-57x57.png" />
		<link rel="apple-touch-icon" sizes="114x114" href="public/images/mobile/homescreen-icon-114x114.png" />
		<link rel="apple-touch-icon-precomposed" href="public/images/mobile/homescreen-icon-57x57.png"/>
		<link rel="stylesheet" href="public/css/jquery.mobile-1.0.min.css" />
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
		<style>
			.error {color:#c00; font-style:normal; text-align:center;}	
		</style>
		
	</head>
	
	<body id="mobileBody">

		<div id="container" data-role="page" data-theme="a">
			<?php Rd_Layout::includeFile('mobile/banner.php'); ?>
		    <div id="content" data-role="content" class="reducedMargin loginForm">
		    <?php if(Rd_Layout::hasMessage('loginError')){ ?>
				<p data-role="alert" class="error smallprint"><?php print(Rd_Layout::getMessage('loginError')); ?></p>
				<?php print(Rd_Layout::getMessage('emergencyLogout')); ?>
			<?php } 
			

$supportedPlugins = Rd_Auth::getLoadedPlugins();
$requestingLocalLogin = 
	(
		array_key_exists('loginRealm',$_GET) 
		&& 'Local' == trim($_GET['loginRealm'])
	) || (
		'Local' == Rd_Auth::getDefaultPlugin()
		&& (
			count($supportedPlugins) == 1
			|| !array_key_exists('loginRealm',$_GET)
			|| 'Local' == trim($_GET['loginRealm'])
		)
	);

$whatToCallGuestLogin = 'Guest Log In';
$whatToCallInstutionalLogin = 'Students, Faculty, and Staff';

$activePlugin = (
	array_key_exists('loginRealm',$_GET) 
		&& in_array(trim($_GET['loginRealm']), $supportedPlugins)
	? $_GET['loginRealm']
	: $supportedPlugins[0]
);
$alternativePlugin = (
	$activePlugin == Rd_Auth::getDefaultPlugin() && count($supportedPlugins) > 1
	? $supportedPlugins[1]
	: $supportedPlugins[0]
);

$usernameValueAttribute = (
		array_key_exists('username', $_POST)
		? ' value="' . htmlentities($_POST['username']) .'" '
		: ''
);

if (!$requestingLocalLogin) {
	if ('Ldap' == Rd_Auth::getDefaultPlugin()){
?>
		    	<p><b><?php print($whatToCallInstutionalLogin); ?> Log In</b>
		    	<i>Please enter your user ID and password</i></p>
				<form name="RDlogin" method="post" action="<?php print(Rd_UrlRewrite::scrubGet($_SERVER['REQUEST_URI'], $_GET, array('exclude'=>array('failureCode')))); ?>">
					<p><strong>Username:</strong>
						<input name="username" id="user_name" type="text" size="15" autocapitalize="off" autocorrect="off" data-theme="c" <?php print($usernameValueAttribute);?> /></p> 
					<p><strong>Password:</strong>
						<input name="password" type="password" size="15" data-theme="c" /></p>
						<input type="hidden" name="mobile" value="true" />
						<input type="hidden" name="loginRealm" value="Ldap"/>
						<input data-ajax="false" name="submitForm" type="submit" value="Log In" />

				</form>
<?php
	} else { //use Shib
?>				
    <p><b><?php print($whatToCallInstutionalLogin); ?></b>
    <i>Use your account to log in</i></p>
	<form name="RDlogin" method="post" action="<?php print(Rd_UrlRewrite::scrubGet('./auth/', array_merge($_GET, array('mobile' => 'true','loginRealm' => 'Shibboleth')), array('exclude'=>array('failureCode')))); ?>">
		<input data-ajax="false" name="submitForm" type="submit" value="Log In" />
	</form>
<?php
	}

} else {
?>
		    	<p><b><?php print($whatToCallGuestLogin); ?></b>
		    	<i>Please enter your username and password</i></p>
				<form name="RDlogin" method="post" action="<?php print(Rd_UrlRewrite::scrubGet($_SERVER['REQUEST_URI'], $_GET, array('exclude'=>array('failureCode')))); ?>">
					<p><strong>Username:</strong>
						<input name="username" id="user_name" type="text" size="15" autocapitalize="off" autocorrect="off" data-theme="c" <?php print($usernameValueAttribute);?>/></p> 
					<p><strong>Password:</strong>
						<input name="password" type="password" size="15" data-theme="c" /></p>
						<input type="hidden" name="mobile" value="true" />
						<input type="hidden" name="loginRealm" value="Local"/>
						<input data-ajax="false" name="submitForm" type="submit" value="Log In" />
				</form>
<?php 
}
?>				
		    </div>
			<?php 
			Rd_Layout::includeFile('mobile/footer.php','', array(
				'loginPage' => (count($supportedPlugins) > 1), 
				'guestLabel' => $whatToCallGuestLogin,
				'institutionLabel' => $whatToCallInstutionalLogin,
				'realm' => $activePlugin,
				'altRealm' => $alternativePlugin
			));  ?>
		</div>
	</body>
</html>