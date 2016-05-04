<?php //#TODO integrate this with the Rd_Layout_Tab utility
	$tab_addReserve    	= "";
	$tab_manageClasses 	= "";
	$tab_manageUser    	= "";
	$tab_myReserves		= "";
	$tab_reserves		= "";	
	$tab_search			= "";
	$tab_reports		= "";
	$tab_admin			= "";
	$tab_selector = "tab_" . Rd_Layout_Tab::get('manageClasses'); 
	$$tab_selector = 'phoui-tabs-selected ';
	
	//Tab index defines order of tabs aphabetically hense the aa, y and z prefixes
	$tabs = array();
	$tabs['aamyReserves']		= "<li class=\"phoui-tabs-nav-item {$tab_myReserves}\"><a href=\"index.php\">My Courses</a></li>\n";
	$tabs['addReserves']	= null;
	$tabs['manageClasses']	= null;
	$tabs['manageUser']	= null;
	$tabs['ySearch']		= null;
	$tabs['zReports']		= null;
	$tabs['zzAdmin']		= null;
	
	switch ($u->getUserClass())
	{
		case 'admin':
			$tabs['zzAdmin']	= "<li class=\"phoui-tabs-nav-item {$tab_admin}\"><a href=\"index.php?cmd=admin\">Admin</a></li>\n";
		case 'staff':
			$tabs['addReserve']	= "<li class=\"phoui-tabs-nav-item {$tab_addReserve}\"><a href=\"index.php?cmd=addReserve\">Manage Items</a></li>\n";
			$tabs['manageClasses'] 	= "<li class=\"phoui-tabs-nav-item {$tab_manageClasses}\"><a href=\"index.php?cmd=manageClasses\">Manage Classes</a></li>\n";
			$tabs['manageUser'] = "<li class=\"phoui-tabs-nav-item {$tab_manageUser}\"><a href=\"index.php?cmd=manageUser\">Manage Users</a></li>\n";
			$tabs['ySearch']	= "<li class=\"phoui-tabs-nav-item {$tab_search}\"><a href=\"index.php?cmd=searchTab\">Search</a></li>\n";
			$tabs['zReports']	= "<li class=\"phoui-tabs-nav-item {$tab_reports}\"><a href=\"index.php?cmd=reportsTab\">View Statistics</a></li>\n";			
		break;
			
		case 'instructor':
			$tabs['addReserve']	   	= "<li class=\"phoui-tabs-nav-item {$tab_addReserve}\"><a href=\"index.php?cmd=addReserve\">Add a Reserve</a></li>\n";	
			$tabs['manageUser']	   	= "<li class=\"phoui-tabs-nav-item {$tab_manageUser}\"><a href=\"index.php?cmd=manageUser\">Manage Users</a></li>\n";
			$tabs['zReports']		= "<li class=\"phoui-tabs-nav-item {$tab_reports}\"><a href=\"index.php?cmd=reportsTab\">View Statistics</a></li>\n";
		case 'proxy':
		break;
		
		case 'custodian':
			$tabs['manageUser']	= "<li class=\"phoui-tabs-nav-item {$tab_manageUser}\"><a href=\"index.php?cmd=manageUser\">Manage Users</a></li>\n";
		break;

	}
	
	ksort($tabs);
?>


		<div id="mainNav">
			<div id="mainNavTabs" class="phoui-tabs">
			    <ul class="reset-list phoui-tabs-nav">
					<?php foreach(array_keys($tabs) as $k){
						 if (!is_null($tabs[$k])){
							print($tabs[$k]);
						}
					} ?>
			    </ul>
			    <?php if ($u->isLoggedIn()) { ?>
			    <div class="user">
					Logged in as: <?php print($u->getName(false)); ?> ( <?php print($u->getUsername()); ?> )
				</div>
				<?php } ?>
			    <div class="clear"></div>
		    </div>
  	  	</div>

