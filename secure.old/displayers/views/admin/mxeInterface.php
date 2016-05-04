<div id="newStyles">
<script type="text/javascript">
var  mxeServiceUrl= '<?php print(Rd_Config::get('video:encoderService:url')); ?>';
<?php $this->getDisplayer()->displayHtmlScripts(); ?>
<?php $this->getDisplayer()->displayMxeScripts(); ?>
</script>
<div class="prefix_1 iconText"><h4>Cisco MXE Status: <img height="16" width="16" src="<?php print($this->statusImage); ?>"/> <?php print($this->statusMessage); ?></h4></h4></div>
<div id="cannedMessages" class="grid_3 prefix_1">
	<?php foreach($this->messages as $message) {?>
		<p><span class="messageTarget"><?php print($message->target); ?></span>:</br><?php print($message->preview); ?><span class="hidden payload"><?php print($message->template); ?></span></p>
	<?php }?>
</div>
<form id="mxeIntegrationTest" action="index.php?cmd=testMxe" method="post" class="grid_11">
<label>Resource:
<?php print(Rd_Config::get('video:encoderService:url')); ?><input name="mxeResource" id="mxeResource" value=""/>
</label><input type="button" value="Send" id="submitMessage" name="submitMessage"/>
<label class="blockLabel">Message: <br/>
<textarea name="mxeMessage" id="mxeMessage" rows="15" cols="70"></textarea></label>

<label class="blockLabel">Response: <br/>
<textarea name="mxeResponse" id="mxeResponse" rows="15" cols="70"></textarea></label>
</form>
</div>