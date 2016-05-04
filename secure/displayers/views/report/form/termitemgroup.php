<?php
	    		$t = new terms();
?>
	<tr>
		<td colspan="2" valign="top">
			<form method="post" action="index.php">
			
				<input type="hidden" name="reportID" value="<?php print($this->report->getReportID()); ?>" />
				<input type="hidden" name="cmd" value="viewReport" />
				
				(Use SHIFT/CTRL keys to select multiple values)
				<br/>
				<strong>Term(s):</strong>
				<br/>
				<select multiple="multiple" name="term_id[]" size="5">
<?php		foreach($t->getTerms(true) as $term){ ?>
					<option value="<?php print($term->getTermID()); ?>"><?php print($term->getTerm()); ?></option>
<?php		} ?>
				</select>
				<strong>Item Groups(s):</strong>
				<br/>
				<select multiple="multiple" name="item_group[]" size="5">
					<option selected="selected" value="MONOGRAPH">Monograph</option>
					<option selected="selected" value="MULTIMEDIA">Multimedia</option>
					<option selected="selected" value="ELECTRONIC">Electronic</option>
					<option selected="selected" value="VIDEO">Video</option>
				</select>

				<br/>
				<input type="submit" name="submit" value="Generate Report" />
			</form>
		</td>
	</tr>
<?php  