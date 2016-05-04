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
				<select multiple="true" name="term_id[]" size="5">
<?php		foreach($t->getTerms(true) as $term): ?>
					<option value="<?php print($term->getTermID()); ?>"><?php print($term->getTerm()); ?></option>
<?php		endforeach; ?>
				</select>
				<strong>User Groups(s):</strong>
				<br/>
				<select multiple="true" name="permission_id[]" size="5">
<?php		foreach(permissions::getAllIds() as $permissionId){ ?>
					<option selected="selected" value="<?php print($permissionId); ?>"><?php print(permissions::getLabel($permissionId)); ?></option>
<?php		} ?>
				</select>

				<br/>
				<input type="submit" name="submit" value="Generate Report" />
			</form>
		</td>
	</tr>
<?php  