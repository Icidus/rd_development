<?php 

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

$whatToCallGuestLogin = 'Guest Login';
$whatToCallInstutionalLogin = 'Students, Faculty, and Staff';

$alternativePlugin = (
	'Local' == Rd_Auth::getDefaultPlugin() && count($supportedPlugins) > 1
	? $supportedPlugins[1]
	: $supportedPlugins[0]
);

$usernameValueAttribute = (
		array_key_exists('username', $_POST)
		? ' value="' . htmlentities($_POST['username']) .'" '
		: ''
);	
	
$excludeGets = array('failureCode');
if($commandExcluded) {
	$excludeGets[] = 'cmd';
}

if (!$requestingLocalLogin) {
	if ('Ldap' == Rd_Auth::getDefaultPlugin()){
		$extraPadding = false;
		$actionUrl = Rd_UrlRewrite::scrubGet($_SERVER['REQUEST_URI'], $_GET, array('exclude'=>$excludeGets));
?>
<h2 class="noBottomMargin"><?php print($whatToCallInstutionalLogin); ?> Log In</h2>
<p class="noTopMargin">Please enter your user ID and password</p>
<form name="RDlogin" id="rdLoginForm" method="post" action="<?php print($actionUrl); ?>" style="margin:0px;">
   	<div class="threeQuarterWidth centered">
   		<label><span>Username:</span> <input name="username" id="username" type="text" size="15" autocapitalize="off" autocorrect="off" class="loginInput" <?php print($usernameValueAttribute);?>/></label> 
       	<label><span>Password:</span> <input name="password" type="password" size="15" class="loginInput"/></label>
	</div>
	<input type="hidden" name="mobile" value="false" />
	<input type="hidden" name="loginRealm" value="Ldap" />
    <input class="centered topMargin largeTextButton" name="submitForm" type="submit" value="Log In<?php print($moreSpecificAction); ?>" style="<?php print( $loginOnly ? '' : 'width:8em; '); ?>height:3em;">
</form>
<?php 
	} else { //use Shib
		$extraPadding = true;
		$actionUrl = Rd_UrlRewrite::scrubGet('./auth/', array_merge($_GET,array('loginRealm' => 'Shibboleth')), array('exclude'=>$excludeGets));
?><h2 class="noBottomMargin"><?php print($whatToCallInstutionalLogin); ?></h2>
<p class="noTopMargin">Use your account to log in</p>
<form name="RDlogin" id="rdLoginForm" method="post" action="<?php print($actionUrl); ?>" style="margin:0px;">
    <input class="centered topMargin largeTextButton" name="submitForm" type="submit" value="Log In<?php print('' != $moreSpecificAction ? $moreSpecificAction : ''); ?>" style="height:3em;">
</form>
<br/>
<?php 
	}

  Rd_Layout::includeFile('login/mobileLink.php'); 
  if ($extraPadding) { ?>
<br/>
<br/>
<?php } 

	if (count($supportedPlugins) > 1) { ?>
<div class="altLogin">
	<h2 class="noUnderline"><a href="<?php print(Rd_UrlRewrite::scrubGet($_SERVER['REQUEST_URI'], array_merge($_GET, array('loginRealm' => 'Local')), array('exclude'=>array('failureCode')))); ?>">Guest Log In</a></h2>
</div>
<?php 
	}
} else {
?>
<h2 class="noBottomMargin"><?php print($whatToCallGuestLogin); ?></h2>
<p class="noTopMargin">Please enter your username and password</p>
<form name="RDlogin" id="rdLoginForm" method="post" action="<?php print(Rd_UrlRewrite::scrubGet($_SERVER['REQUEST_URI'], $_GET, array('exclude'=>array('failureCode')))); ?>" style="margin:0px;">
   	<div class="threeQuarterWidth centered">
   		<label><span>Username:</span> <input name="username" id="username" type="text" size="15" autocapitalize="off" autocorrect="off" class="loginInput" <?php print($usernameValueAttribute);?>/></label> 
       	<label><span>Password:</span> <input name="password" type="password" size="15" class="loginInput"/></label>
	</div>
	<input type="hidden" name="mobile" value="false" />
	<input type="hidden" name="loginRealm" value="Local" />
    <input class="centered topMargin bottomMargin largeTextButton" name="submitForm" type="submit" value="Log In<?php print($moreSpecificAction); ?>" style="<?php print( $loginOnly ? '' : 'width:8em; '); ?>height:3em;">
</form>
<?php Rd_Layout::includeFile('login/mobileLink.php'); 
if (count($supportedPlugins) > 1) { ?>
<p>Access through <?php print($whatToCallGuestLogin); ?> In is only for people that do not have an institutional account. 
If you have not received an e-mail with your username and do not have an institutional account you must contact the <a href="mailto:<?php print(Rd_Registry::get('supportEmail')); ?>">reserves staff</a> to get access.</p>
<h2 class="noUnderline"><a href="./?cmd=login&loginRealm=<?php print($alternativePlugin); ?>"><?php print($whatToCallInstutionalLogin); ?> Login In</a></h2>
<?php 
	}
}