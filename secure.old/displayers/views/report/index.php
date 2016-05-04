<div class="grid_15 sizedText">
	<h2>Available Reports</h2>
<?php 
	if (!is_array($this->list) || 0 == count($this->list)) {
?>
	<div class="grid_7 marginCenter">No Reports Specified</div>
<?php 	
		} else { 
?>
	<div class="grid_7 marginCenter">
		<ul class="bulleted">
<?php 
			foreach($this->list as $report) {
?>
			<li><a href="./?cmd=viewReport&reportID=<?php print($report['report_id']); ?>">
				<?php print($report['title']); ?>
			</a></li>
<?php 		
			}
?>
		</ul>
	</div>
<?php 
		}
?>
</div>