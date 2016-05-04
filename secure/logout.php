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
    window.location.href = 'https://libtools.smith.edu/rd'
}, 4000);
</script>
</div>