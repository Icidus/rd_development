<?php
$year = (int)date('Y');
?>
	<script type="text/javascript">
		jQuery(document).ready(function(){
			jQuery('#fYearPicker').keyup(function(){
				var newYear = jQuery(this).val();
				if(newYear.length == 4){
					jQuery('#fYearStart').val( (parseInt(newYear) - 1 )+ '-07-01');
					jQuery('#fYearEnd').val( newYear + '-06-30');
				}
			});
		});
	</script>
	
	<form class="basicPadding" method="post" action="index.php">
		<strong>Fiscal Year:</strong>
		<br/>
		<input type="text" id="fYearPicker" name="fYear" value="<?php print($year); ?>" />
		<input type="hidden" name="reportID" value="<?php print($this->report->getReportID()); ?>" />
		<input type="hidden" name="cmd" value="viewReport" />
		
		<br/>
		<strong>Dates (required):</strong>
		<br/>
		<input type="text" id="fYearStart" name="begin_date" size="10" maxlength="10" value="<?php print(($year - 1) . '-07-01') ?>" />&nbsp;to&nbsp;<input type="text" id="fYearEnd" name="end_date" size="10" maxlength="10" value="<?php print($year.'-06-30'); ?>" />&nbsp; (YYYY-MM-DD)
		<br/>
		<input type="submit" name="submit" value="Generate Report" />
	</form>