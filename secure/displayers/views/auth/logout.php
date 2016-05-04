<div id="newStyles" class="prefix_1 suffix_1">
<?php
	session_unset();
    session_destroy();	
?>		
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
<script type="text/javascript">
$(document).ready(function() {
    $("#1").attr("src", "https://libtools.smith.edu/Shibboleth.sso/Logout");
    $("#2").attr("src", "https://idp.smith.edu/idp/logout.php?callback");

});
</script>
<div class="container">
<h2 class="lead ">Please wait while we log you out...</i></h2>
<iframe id="1" src="" style="display:none;"></iframe>
<iframe id="2" src="" style="display:none;"></iframe>
<script>
window.setTimeout(function() {
    window.location.href = 'https://libtools.smith.edu/rd/'
}, 4000);
</script>
</div>	
<!--
<h3>Logging you out of <?php print($this->instance); ?></h3>
<p>You have been logged out.</p>
<p>You may <a href="./?cmd=login">Log in</a> again.</p>
<p>Return to the <a href="<?php print($this->institutionUrl); ?>"><?php print($this->institutionName) ?></a> web site.</p>
-->
</div>