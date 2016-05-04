<?php 
		//this will hold the jscript calls to select the initial tab view
		//it will be run after everything is rendered
		$onload_jscript = '';
		$showProxy = is_array($this->proxyCis) 
			&& count($this->proxyCis) > 0
			&& array_key_exists($this->proxySelectTerm, $this->proxyCis)
			&& is_array($this->proxyCis[$this->proxySelectTerm])
			&& count($this->proxyCis[$this->proxySelectTerm]) > 0;
		$showStudent = (is_array($this->studentCis) && count($this->studentCis) > 0)
			|| $this->u->getDefaultRole() < Account_Rd::LEVEL_FACULTY;
		if($showProxy) {	//show proxy on top	
			$onload_jscript .= "showBlock('proxy_tab', 'proxy_block');\n";
			//add call to preselect the first term sub-block
			$keys = array_keys($this->proxyCis);
			$onload_jscript .= "showTermBlock('proxy_block_{$this->proxySelectTerm}');\n";
		}
		if($showStudent) {	//show student on top
			$onload_jscript .= "showBlock('student_tab', 'student_block');\n";
		}
		if($this->u->getDefaultRole() >= Account_Rd::LEVEL_FACULTY) {	//show instructor on top
			$onload_jscript .= "showBlock('instructor_tab', 'instructor_block');\n";
			$keys = array_keys($this->instructorCis);		
			$onload_jscript .= "showTermBlock('instructor_block_{$this->proxySelectTerm}');\n";
		}
		if(empty($onload_jscript)) {	//hide everything
			$onload_jscript = "showBlock('student_tab', 'student_block');\n";
		}

?>

<script language="JavaScript" type="text/javascript">
	var current_tab_id = 'student_tab';
	var current_block_id = 'student_block';
	var current_term_blocks = new Array();
	
	/**
	 * @return false
	 * @param strin tab_id - id of the tab
	 * @param string block_id - id of the associated block
	 * @desc Marks the selected tab and switches to the associated block
	 */
	function showBlock(tab_id, block_id) {
		//unmark the last selected tab
		if(document.getElementById(current_tab_id)) {
			document.getElementById(current_tab_id).className = '';
		}
		//mark the new selection
		if(document.getElementById(tab_id)) {
			document.getElementById(tab_id).className = 'current';
		}
		
		//do the same with the blocks
		if(document.getElementById(current_block_id)) {
			document.getElementById(current_block_id).style.display = 'none';
		}
		//mark the new selection
		if(document.getElementById(block_id)) {
			document.getElementById(block_id).style.display = 'block';
		}
	
		//remember the current selections
		current_tab_id = tab_id;
		current_block_id = block_id;
		
		//try to set the term block
		if(current_term_blocks[block_id]) {
			showTermBlock(current_term_blocks[block_id]);
		}
		
		return false;
	}
	
	function showTermBlock(term_block_id) {
		//unmark the last selected term block
		if(document.getElementById(current_term_blocks[current_block_id])) {
			document.getElementById(current_term_blocks[current_block_id]).style.display = 'none';
		}
		//mark the new selection
		if(document.getElementById(term_block_id)) {
			document.getElementById(term_block_id).style.display = '';
		}
		
		//remember the selection
		current_term_blocks[current_block_id] = term_block_id;		
	}
</script>
      			
<div class="contentTabs">
	<ul>
<?php	if($this->u->getDefaultRole() >= Account_Rd::LEVEL_FACULTY){ //check DEFAULT role, so that not-trained instructors still see this tab ?>
		<li id="instructor_tab"><a href="#" onclick="return showBlock('instructor_tab', 'instructor_block');">You are teaching:</a></li>
<?php	} ?>
		<li id="student_tab" class="current"><a href="#" onclick="return showBlock('student_tab', 'student_block');">You are enrolled in:</a></li>
<?php	if($this->hasProxyCis) {
		 //only show proxy tab if user is currently proxying courses ?>
		<li id="proxy_tab"><a href="#" onclick="return showBlock('proxy_tab', 'proxy_block');">You are proxy for:</a></li>
<?php	} ?>
	</ul>
</div>
<div class="clear"></div>
	
<?php	
	if($this->u->getDefaultRole() >= Account_Rd::LEVEL_FACULTY) { ?>
	<div id="instructor_block" style="display:none;">
		<div width="100%" class="displayList">
			<div style="padding:4px;" class="head">
				<div style="float:left;">
<?php
		//show a radio choices for terms, to act as a filter for class list display

			foreach(array_reverse(array_keys($this->instructorCis)) as $termId){
				$term = new term($termId);
				$select = ($this->instructorSelectTerm == $termId) 
					? 'checked="true"' 
					: '';
	?>
						<label><input type="radio" name="instructor_term_block" onclick="showTermBlock('instructor_block_<?php print($termId); ?>');" <?php print($select); ?> /><?php print($term->getTermName().' '.$term->getTermYear()); ?>&nbsp;(<?php print(count($this->instructorCis[$termId])); ?> Course<?php print(count($this->instructorCis[$termId]) != 1 ? 's' : ''); ?>)</label>
	<?php		
			}
?>
				</div>
<?php		if($this->u->getRole() >= Account_Rd::LEVEL_FACULTY){ ?>
				<div style="float:right;"><span class="actions">[ <a href="index.php?cmd=createClass">Create a New Class</a> ]</span></div>
<?php		} ?>
				<div style="clear:both;"></div>
			</div>
		</div>
<?php
		//loop through all the available terms/courses
		if(is_array($this->instructorCis) && count($this->instructorCis) > 0) {
			foreach($this->instructorCis as $termId=>$statusCiArray){	//split the courses by term
				$style = 
					$termId == $this->instructorSelectTerm
					? ''
					: 'style="display:none;"';
?>
		<table id="instructor_block_<?php print($termId); ?>" class="displayList" <?php print($style); ?> width="100%">
<?php
				$rowClass = 'evenRow';
				foreach($statusCiArray as $status=>$ciList){	//split courses by status
					//begin looping through courses	for this term
					foreach($ciList as $ci){
						$ci->getCourseForUser();	//get course object
						$ci->getInstructors();	//get a list of instructors	
						
						//sort out the edit/activate/view links and icons, based on effective role		
						if($this->u->getRole() < Account_Rd::LEVEL_FACULTY) {	//if the users's effective role is less than instructor (not-trained)
							$edit_icon = '';	//they get no icon
							$course_num = '<a href="index.php?cmd=viewReservesList&ci='.$ci->getCourseInstanceID().'">'.$ci->course->displayCourseNo().'</a>';									$course_name = '<a href="index.php?cmd=viewReservesList&ci='.$ci->getCourseInstanceID().'">'.$ci->course->getName().'</a>';
							$enrollment = '<span class="'.common_getEnrollmentStyleTag($ci->getEnrollment()).'">'.$ci->getEnrollment().'</span>';
						}
						else {	//full-fledged instructor
							if($ci->getStatus() == 'AUTOFEED') {	//if the course has been fed through registrar, but not activated						
								$edit_icon = '<img src="public/images/activate.gif" width="24" height="20" />';	//show the 'activate-me' icon
								$course_num = '<a href="index.php?cmd=activateClass&ci='.$ci->getCourseInstanceID().'">'.$ci->course->displayCourseNo().'</a>';									
								$course_name = '<a href="index.php?cmd=activateClass&ci='.$ci->getCourseInstanceID().'">'.$ci->course->getName().'</a>';
								$enrollment = '<span class="'.common_getEnrollmentStyleTag($ci->getEnrollment()).'">'.$ci->getEnrollment().'</span>';
							}
							elseif($ci->getStatus() == 'CANCELED') {	//if the course has been cance led by the registrar
								$edit_icon = '<img src="public/images/cancel.gif" alt="edit" width="24" height="20">';	//show the 'activate-me' icon
								$course_num = $ci->course->displayCourseNo();
								$course_name = $ci->course->getName();
								$enrollment = '<strong>[<a href="index.php?cmd=removeClass&ci='.$ci->getCourseInstanceID().'">remove</a>]</strong>';
							}
							else {
								$edit_icon = '<img src="public/images/pencil.gif" alt="edit" width="24" height="20">';	//show the edit icon
								$course_num = '<a href="index.php?cmd=editClass&ci='.$ci->getCourseInstanceID().'">'.$ci->course->displayCourseNo().'</a>';
								$course_name = '<a href="index.php?cmd=editClass&ci='.$ci->getCourseInstanceID().'">'.$ci->course->getName().'</a>';
								$enrollment = '<span class="'.common_getEnrollmentStyleTag($ci->getEnrollment()).'">'.$ci->getEnrollment().'</span>';
							}								
						}
						
						$rowClass = ($rowClass=='oddRow') ? 'evenRow' : 'oddRow';	//set the row class
?>
			<tr align="left" valign="middle" class="<?php print($rowClass); ?>">
				<td width="5%"><?php print($edit_icon); ?></td>
				<td width="15%"><?php print($course_num); ?></td>
				<td><?php print($course_name); ?></td>
				<td width="30%"><?php print($ci->displayInstructors()); ?></td>	
				<td width="10%"><?php print($enrollment); ?></td>		
			</tr>
<?php					
					}	//end loop through CIs
				}	//end for each status
?>
		</table>
<?php
			}	//end for each term
		} else {	//not teaching any courses
?>
		<div class="borders" style="padding:5px;">
			You are not teaching any courses.
		</div>
<?php	} ?>
		<p class="topMargin">
			<img src="public/images/pencil.gif" width="24" height="20" /> <span style="font-size:small;">= active courses you may edit</span>
			<br />
			<img src="public/images/activate.gif" width="24" height="20" /> <span style="font-size:small;">= new courses not yet in use</span>
			<br />
			<img src="public/images/cancel.gif" width="24" height="20" /> <span style="font-size:small;">= courses canceled by the registrar</span>
		</p>
	</div>
<?php	
	} 
?>


		<div id="student_block">
			<table width="100%" class="displayList">
				<tr align="right" valign="middle" class="head">
					<td colspan="4">
						<span class="actions">[ <a href="index.php?cmd=addClass">Join a Class</a> ] [ <a href="index.php?cmd=removeClass">Leave a Class</a> ]</span>
					</td>
				</tr>
<?php	
		if(is_array($this->studentCis) && count($this->studentCis) > 0){
			//begin looping through courses - separate by enrollment status
			foreach($this->studentCis as $status=>$courses){
				if ($status == 'PENDING') {	//show a label for pending courses
?>
				<tr align="left" valign="middle">
					<td colspan="4" class="divider">
						Courses you have requested to join (pending approval):
					</td>
				</tr>
				
<?php			} else if($status == 'DENIED') {	//show label for denied courses ?>

				<tr align="left" valign="middle">
					<td colspan="4" class="divider">
						Courses you may not join (denied enrollment):
					</td>
				</tr>
<?php
				}
				
				if(empty($rowClass)) {
					$rowClass = 'evenRow';
				}
				foreach($courses as $ci){
					$ci->getCourseForUser();	//get course object
					$ci->getInstructors();	//get a list of instructors
					
					//only link enrolled classes
					if(($status == 'AUTOFEED') || ($status == 'APPROVED')) {
						$course_num = '<a href="index.php?cmd=viewReservesList&ci='.$ci->getCourseInstanceID().'">'.$ci->course->displayCourseNo().'</a>';
						$course_name = '<a href="index.php?cmd=viewReservesList&ci='.$ci->getCourseInstanceID().'">'.$ci->course->getName().'</a>';
					}
					else {
						$course_num = $ci->course->displayCourseNo();
						$course_name = $ci->course->getName();
					}
					
					$rowClass = ($rowClass=='oddRow') ? 'evenRow' : 'oddRow';	//set the row class
?>
				<tr align="left" valign="middle" class="<?php print($rowClass); ?>">
					<td width="15%"><?php print($course_num); ?></td>
					<td><?php print($course_name); ?></td>
					<td width="10%"><?php print($ci->displayTerm()); ?></td>
					<td width="25%"><?php print($ci->displayInstructors()); ?></td>			
				</tr>
<?php		
				}
			}
		} else {	//not enrolled in any classes
?>
			<tr>
				<td>You are not enrolled in any classes this semester</td>
			</tr>

<?php	} ?>
			</table>
			<p />
		</div>


<?php	if ($showProxy) { ?>
		<div id="proxy_block">
			<div width="100%" class="displayList">
				<div style="padding:4px;" class="head">
<?php
			//show a radio choices for terms, to act as a filter for class list display
			foreach ($this->proxyCis as $termId=>$ciList) {
				$term = new term($termId);
				$select = ($this->proxySelectTerm == $termId) ? 'checked="true"' : '';
?>
							<input type="radio" name="proxy_term_block" onclick="showTermBlock('proxy_block_<?php print($term_id); ?>');" <?php print($select); ?> /><?php print($term->getTermName().' '.$term->getTermYear()); ?>&nbsp;
<?php		
			}
?>
				</div>
			</div>
<?php		foreach ($this->proxyCis as $term_id=>$term_ci_list) {	//split up the subarrays by term ?>
			<table id="proxy_block_<?php print($term_id); ?>" class="displayList" style="display:none;" width="100%">
<?php
				//begin looping through courses		
				$rowClass = 'evenRow';
				foreach ($term_ci_list as $ci) {
					$ci->getCourseForUser();	//get course object
					$ci->getInstructors();	//get a list of instructors				
					$edit_icon = 'public/images/pencil.gif';
					
					$rowClass = ($rowClass=='oddRow') ? 'evenRow' : 'oddRow';	//set the row class
?>
				<tr align="left" valign="middle" class="<?php print($rowClass); ?>">
					<td width="5%"><img src="<?php print($edit_icon); ?>" alt="edit" width="24" height="20"></td>
					<td width="15%"><a href="index.php?cmd=editClass&ci=<?php print($ci->getCourseInstanceID()); ?>"><?php print($ci->course->displayCourseNo()); ?></a></td>
					<td><a href="index.php?cmd=editClass&ci=<?php print($ci->getCourseInstanceID()); ?>"><?php print($ci->course->getName()); ?></a></td>
					<td width="30%"><?php print($ci->displayInstructors()); ?></td>	
					<td width="10%"><span class="<?php print(common_getEnrollmentStyleTag($ci->getEnrollment()));?>"><?php print($ci->getEnrollment()); ?></span></td>		
				</tr>
<?php			} ?>
			</table>
<?php		} ?>
			<p />
			<img src="public/images/pencil.gif" width="24" height="20"> <span style="font-size:small;">= courses you may edit</span>
			<p />
		</div>
<?php	} ?>
<script language="JavaScript" type="text/javascript">
	<?php print($onload_jscript); ?>
</script>