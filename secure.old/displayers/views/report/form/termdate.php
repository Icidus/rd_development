<?php
		$t = new terms();
		$terms = $t->getTerms(true);
?>
	<tr>
		<td colspan="2" valign="top">
<?php  $this->getDisplayer()->showTermDateScript($terms); ?>
			
			<form method="post" action="index.php">
				<input type="hidden" name="reportID" value="<?php print($this->report->getReportID()); ?>" />
				<input type="hidden" name="cmd" value="viewReport" />
				
				<br/>
<?php  
				$this->getDisplayer()->showTermDatePicker($terms);
?>
				<br/>
				<input type="submit" name="submit" value="Generate Report" />
			</form>
		</td>
	</tr>
<?php 	