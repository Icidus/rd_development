<?php
?>
<p>You have submitted the following reserves for <a href="<?php print($this->rdUrl); ?>/index.php?cmd=editClass&ci=<?php print($this->ci); ?>"><?php print($this->courseInstance->course->displayCourseNo() . ' ' . $this->courseInstance->course->getName()); ?></a>.</p>
<ul class="bulleted"> <?php 
		foreach($this->itemsStored as $item){
			?> <li><?php print($item->getTitle()); ?>,<?php print($item->getVolumeEdition()); ?>[<?php print (($item->getItemGroup() == 'ELECTRONIC')? 'Electronic Reserve':'Physical Reserve');?>]</li> <?php 
		}
	?></ul>
<p>The course reserves staff will process these items as soon as possible.</p>