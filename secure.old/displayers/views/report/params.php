<div class="grid_15 sizeText">
<?php 
	$size = ('class' == $this->report->getParam_group() ? 8 : 6);
?>

	<div class="cancelNavigation"><a href="./?cmd=reportsTab">Return to Reports List</a></div>	
	<div class="grid_<?php print($size);?> suffix_1"><div class="grid_<?php print($size);?> bordered appOptions shadowed_near">
		<h3><?php  print($this->report->getTitle()); ?></h3>
<?php 
	    switch ($this->report->getParam_group())
	    {
	    	case 'term_lib': 
	    		$this->getDisplayer()->showTermLibraryForm($this->report);
	    		break;	    	
	    	case 'term':
				$this->getDisplayer()->showTermForm($this->report);
				break;			
	    	case 'term_dates':
				$this->getDisplayer()->showTermDateForm($this->report);
	    		break;	    	
	    	case 'term_dates_usertype':
				$this->getDisplayer()->showTermDateUserTypeForm($this->report);
	    		break;		
			case 'fyear_dates':
				$this->getDisplayer()->showFiscalYearPicker($this->report);
				break;
			case 'term_usertype':
				$this->getDisplayer()->showTermUserTypeForm($this->report);
				break;	
			case 'term_itemgroup':
				$this->getDisplayer()->showTermItemGroupForm($this->report);
				break;    	
			case 'class':
				$classList = $this->u->getCourseInstancesToEdit($this->report);	//#TODO not an appropriate place to do this....	make this into a cmd method.		
				$this->getDisplayer()->displaySelectClass('viewReport', $classList, '', array('reportID'=>$_REQUEST['reportID']));
				break;			
			default:
				$this->getDisplayer()->showReportConfigError($report->getParam_group());
	    }
?>
	</div></div>
</div>