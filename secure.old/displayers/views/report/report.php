<?php
		$this->getDisplayer()->generateTsvLink($this->data);
?>
	<div class="cancelNavigation"><a href="./?cmd=reportsTab">Return to Reports List</a></div>
	<h2><?php print($this->title);?></h2>
<?php 
	    if (is_array($this->data) && count($this->data) > 0) {
?>
	<table width="100%" border="0" cellpadding="2" cellspacing="0" class="displayList">
					<tr>
<?php 	    foreach ($this->data[0] as $key => $value) {
?> 
						<th><b><?php print($key); ?></b></th>
<?php 		} ?>
					</tr>	    	
<?php 		
			$i = 0;
			foreach($this->data as $row) {
				$rowClass = ($i++ % 2) ? 'evenRow' : 'oddRow';
?>				
					<tr align="left" valign="middle" class="<?php  print($rowClass); ?>">
<?php 			foreach ($row as $value) {
?>
						<td><?php print(urldecode($value)); ?></td>
<?php 				
				}
?>
				</tr>
<?php 				
			}
?>
	</table>
<?php 
	    } else {
?>
				<p>Report completed with no results</p>
<?php   } ?>
<?php 