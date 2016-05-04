<div class="grid_15">
<?php if ('' != trim($this->msg)) {?>
	<div  class="helperText"><?php print($this->msg); ?></div>
	<?php } ?>

<div class="cancelNavigation">[ <a href="index.php<?php print(Account_Rd::atLeastStaff() ? '?cmd=manageUser' : ''); ?>">Cancel</a> ]</div>

<h2 class="formHeader grid_4">Merge Users</h2>
<form class="grid_14 bordered clearing basicPadding marginBottom" action="./?cmd=mergeUsers" method="post">
<div class="prefix_1 suffix_1 grid_5  basicPadding"><?php 						
		if (isset($_REQUEST['userToKeep_'.'select_user_by']) && isset($_REQUEST['userToKeep_'.'user_qryTerm'])){
				$this->users->search($_REQUEST['userToKeep_'.'select_user_by'], $_REQUEST['userToKeep_'.'user_qryTerm']);
		}
		$this->users->displayUserSelect('mergeUsers', "", "Select User to Keep", $this->users->userList, false, $this->request, "userToKeep_", false);
			
		$this->users->userList = null;
?>
</div><div class="grid_5 basicPadding ">
 <?php 			
		if (isset($_REQUEST['userToMerge_'.'select_user_by']) && isset($_REQUEST['userToMerge_'.'user_qryTerm'])){
				$this->users->search($_REQUEST['userToMerge_'.'select_user_by'], $_REQUEST['userToMerge_'.'user_qryTerm']);
		}	
		$this->users->displayUserSelect('mergeUsers', "", "Select User to Merge", $this->users->userList, false, $this->request, "userToMerge_", false);			

		$mergeEnabled = (
			true //#TODO when this get redone with jQuery we can do this...
			? ''
			: ' disabled="disabled"'
		);		
?></div>
<div class="clear"></div>
	<input type="submit" name="submitMergeUser" value="Merge Users"<?php print($mergeEnabled);?>/>
</form>