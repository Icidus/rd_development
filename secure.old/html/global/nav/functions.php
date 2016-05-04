		<div class="functionLinks">
			<a href="index.php?cmd=editProfile">Edit Profile</a> | 
				<?php  	if (Rd_Help::getDefaultArticleId()){ /* TODO update the JS for this */ ?>
						<a href="#" onclick="javascript:help('cmd=helpViewArticle&h_a_id=<?php print(Rd_Help::getDefaultArticleId()); ?>'); return false;">Help</a> |
				<?php 	} else { ?>
					<a href="#" onclick="javascript:help('cmd=help'); return false;">Help</a> |
				<?php	} ?>
			<a href=href="#" onclick="javascript:help('cmd=helpViewArticle&h_a_id=21'); return false;">Policies</a> | 
			<a href="#" onclick="javascript:help('cmd=helpViewArticle&h_a_id=40'); return false;">Contact Us</a> |
			<a href="./?cmd=logout">Log Out</a>
		</div>