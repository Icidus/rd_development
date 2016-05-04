<?php
/*******************************************************************************
mobileClassDisplayer.class.php
Display code for class pages on mobile devices.

Created by Karl Doerr, Modified by Troy Hurteau & Jason Raitz NCSU Libraries, NC State University (libraries.opensource@ncsu.edu).

This file is part of NCSU's distribution of ReservesDirect. This version has not been downloaded from Emory University
or the original developers of ReservesDirect. Neither Emory University nor the original developers of ReservesDirect have authorized
or otherwise endorsed or approved this distribution of the software.

Copyright (c) 2004-2006 Emory University, Atlanta, Georgia.

Licensed under the NCSU ReservesDirect License, Version 2.0 (the "License"); 
you may not use this file except in compliance with the License. You may obtain a copy of the full License at
 http://www.lib.ncsu.edu/it/opensource/

ReservesDirect is distributed in the hope that it will be useful,
but is distributed "AS IS" and WITHOUT ANY WARRANTY, without even the
implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR
PURPOSE, and without any warranty as to non-infringement of any third
party's rights. See the License for the specific language governing permissions and limitations under the License.

The original version of ReservesDirect is located at:
http://www.reservesdirect.org/

This version of ReservesDirect, distributed by NCSU, is located at:
http://code.google.com/p/reservesdirect-ncsu/

*******************************************************************************/

//require_once("secure/common.inc.php");
require_once(APPLICATION_PATH . '/classes/terms.class.php');
require_once(APPLICATION_PATH . '/classes/tree.class.php');
require_once(APPLICATION_PATH . '/managers/ajaxManager.class.php');
require_once(APPLICATION_PATH . '/managers/lookupManager.class.php');
require_once(APPLICATION_PATH . '/lib/Rd/Displayer/Base.php');

class mobileClassDisplayer extends Rd_Displayer_Base {

	public function displayCourseList(&$student_CIs, &$instructor_CIs, &$proxy_CIs) {
		global $u, $g_permission;	
?>		

	
<?php	
		if(!empty($student_CIs)):
			//begin looping through courses - separate by enrollment status
			foreach($student_CIs as $status=>$courses):
?>
				<div class="focal"><p class="notruncate">
<?php 			
				if($status == 'PENDING'):	//show a label for pending courses
?>				
				Courses you have requested to join (pending approval):
<?php			elseif($status == 'DENIED'):	//show label for denied courses ?>
				Courses you may not join (denied enrollment):
<?php 			else:?>
				You are currently enrolled in these courses.
<?php
				endif;
?>
				</p></div>
				<ul data-role="listview" class="results listWithSubtext" data-inset="false">
<?php 				
				foreach($courses as $ci):
					$ci->getCourseForUser();	//get course object
					$ci->getInstructors();	//get a list of instructors
					
					$course_link = "";
					
					//only link enrolled classes
					if(($status == 'AUTOFEED') || ($status == 'APPROVED')) {
						$course_num = $ci->course->displayCourseNo();
						$course_name = $ci->course->getName();
						$course_link = 'href="index.php?cmd=viewReservesList&ci='.$ci->getCourseInstanceID() . '&mobile=true"';
						
					}
					else {
						$course_num = $ci->course->displayCourseNo();
						$course_name = $ci->course->getName();
					}
?>
				<li><a <?php print($course_link); ?>>
					<h3 class="notruncate"></strong><?php print($course_num); ?> <?php print($course_name); ?></strong></h3>
					<h4 class="notruncate"><span class="smallprint"><?php print($ci->displayTerm()); ?>&nbsp&nbsp&nbsp&nbsp
					<?php print($ci->displayInstructors()); ?></span></h4></a>
				</li>
<?php		
				endforeach; ?>
				</ul>
<?php			
			endforeach;
		else:	//not enrolled in any classes
?>
			<div class="content focal">
				<p>You are not enrolled in any classes this semester</p>
			</div>
<?php	endif; ?>
<!-- 
		<script language="JavaScript" type="text/javascript">
			<?php //print($onload_jscript); ?>
		</script>
 -->
<?php
	}
}