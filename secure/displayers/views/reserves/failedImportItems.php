<p>Some items selected from the catalog could not be imported to ReservesDirect:</p>
<ol>
	<?php //#TODO the above reference should pull the product name from config.
		foreach($this->failedItems as $catKey) {
			$quickLookup = Rd_Client_Sirsi_QuickLookup::getText($catKey); #TODO #2.1.0 this is hard coded for NCSU behavior, generalize
	?><li><?php print($quickLookup 
		&& '' != trim(strip_tags($quickLookup))
		? $quickLookup 
		: 'Unrecognized Catalog Key: ' . $catKey
	); ?></li>
	<?php } ?>
</ol>
<p>You may 
<?php  if(count($this->importedItemIds) > 0) { ?> <a href="<?php 
	print($_SERVER['PHP_SELF'] 
		. '?cmd=addMultipleReserves&identifier=itemid&items=' 
		. implode('+', $this->importedItemIds)
	); ?>">continue with the remaining items</a><br/> or <?php } ?>
	<a href="<?php print(Rd_Config::get('catalog:cartIndex')); ?>">return to the catalog</a> to remove these items and find alternatives.
</p>
