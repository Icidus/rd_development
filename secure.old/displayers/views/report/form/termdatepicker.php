<strong>Term:</strong>
				<br/>
				<select name="term_id" onchange="javascript: prefill_term_dates(this.options[this.selectedIndex].value);">
<?php		foreach($this->terms as $term){ ?>
					<option value="<?php print($term->getTermID()); ?>"><?php print($term->getTerm()); ?></option>
<?php		} ?>
				</select>
				<br/>
				<strong>Dates (required):</strong>
				<br/>
				<input type="text" id="begin_date" name="begin_date" size="10" maxlength="10" value="<?php echo $this->terms[0]->getBeginDate(); ?>" />&nbsp;to&nbsp;<input type="text" id="end_date" name="end_date" size="10" maxlength="10" value="<?php echo $this->terms[0]->getEndDate(); ?>" />&nbsp; (YYYY-MM-DD)
<?php 	