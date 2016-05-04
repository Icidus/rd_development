		<script type="text/javascript">
				//define associative arrays indexed by term_id, containing begin and end dates
				var term_begin_dates = {
<?php
	//need this so that we can string the rest w/ preceding comma
		echo '"null":"null"';
		foreach($this->terms as $term) {
			echo ', "'.$term->getTermID().'":"'.$term->getBeginDate().'"';
		}
?>				
				};
				var term_end_dates = {
<?php
			//need this so that we can string the rest w/ preceding comma
			echo '"null":"null"';
			foreach($this->terms as $term) {
				echo ', "'.$term->getTermID().'":"'.$term->getEndDate().'"';
			}
?>				
				};
				
				function prefill_term_dates(term_id) {					
					if(document.getElementById('begin_date')) {
						document.getElementById('begin_date').value = term_begin_dates[term_id];
					}
					if(document.getElementById('end_date')) {
						document.getElementById('end_date').value = term_end_dates[term_id];
					}
				}
			</script>