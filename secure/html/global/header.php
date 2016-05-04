<nav class="navbar navbar-inverse navbar-fixed-top">
      <div class="container">
        <div class="navbar-header">
          <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          <a class="navbar-brand" href="#"><?php print(Rd_Registry::get('institutionName')); ?></a>
        </div>
        <div id="navbar" class="navbar-collapse collapse">
          <ul class="nav navbar-nav navbar-right">
			<li><a href="index.php?cmd=editProfile">Edit Profile</a></li>
			<?php if (Rd_Help::getDefaultArticleId()){ /* TODO update the JS for this */ ?>
				<li><a href="#" onclick="javascript:help('cmd=helpViewArticle&h_a_id=<?php print(Rd_Help::getDefaultArticleId()); ?>'); return false;">Help</a></li>
			<?php 	} else { ?>
				<li><a href="#" onclick="javascript:help('cmd=help'); return false;">Help</a></li>
				<?php	} ?>
			<li><a href=href="#" onclick="javascript:help('cmd=helpViewArticle&h_a_id=21'); return false;">Policies</a></li>
			<li><a href="#" onclick="javascript:help('cmd=helpViewArticle&h_a_id=40'); return false;">Contact Us</a></li>
			<li><a href="./?cmd=logout">Log Out</a></li>
          </ul>
        </div><!--/.nav-collapse -->
      </div>
    </nav>
<!--
	    <div id="institution_title"> 
	      <a href="<?php print(Rd_Registry::get('libraryUrl')); ?>"><img src="<?php print(Rd_Registry::get('libraryLogo')); ?>" alt="<?php print(Rd_Registry::get('institutionName')); ?>" width="254" height="39" /></a>
		</div>
	</div>
	<div class="clear"></div>
</div>
-->
