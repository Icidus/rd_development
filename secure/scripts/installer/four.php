<h2>Step 4: Database Updates and Admin Account</h2>
<?php 

include_once(APPLICATION_PATH . '/scripts/installer/dbconn.php');

if(updateDb($dsn)){
	$username = 'admin';
	$password = 
		array_key_exists('admin_pass', $_REQUEST) 
			&& '' != trim($_REQUEST['admin_pass']) 
		? $_REQUEST['admin_pass'] 
		: '<i>see ' . (
			array_key_exists('dummy', $_REQUEST)
			? 'demo_data.sql'
			: 'create.sql'
		) . ' for the default password</i>';
?>
	<p>You should now be able to login to ReservesDirect with as an administrator using the default password or the value specified above.</p>
	<p>Username: <?php print($username); ?></p>				
	<p>Password: <?php print($password); ?></p>
	<p>To complete the installation, switch the APPLICATION_STATUS back to 'online', this is normally accomplished by deleting the .application_stats file.</p>
	<p>It isn't a bad idea to remove/rename the install script (/secure/scripts/installer.php), and set the file permissions on your configuration file so that it is no longer writable.</p>
	<p>Once these steps are done, you may <a href="./">proceed to the homepage.</a></p>
<?php 
}

function updateDb($dsn)
{
	try{
		Rd_Pdo::connect($dsn);
	} catch (Exception $e) {
?>
		<p class="error">A connection error occured connecting to <?php print($dsn['hostspec']); ?> as <?php print($dsn['username']); ?>.</p>
		<p><?php print($e->getMessage());?></p>
<?php 
		printStepFourRetry();
		return false;
	}
	$pathToPatches = APPLICATION_PATH . '/db/';
	$patches= array();
	$dbFiles = scandir($pathToPatches);
	foreach($dbFiles as $filename) {
		if (strpos($filename, 'upgrade_to_') === 0) {
			$endString = substr($filename,11);
			$patchName = substr($endString, 0, strrpos($endString,'.'));
			$patches[$filename] = str_replace('_', ' ', $patchName);
		}
	}
	if (!array_key_exists('admin_pass', $_REQUEST)) {
?>
	<p>Your code version is: <?php print(APPLICATION_VERSION); ?></p>
	<p>Please select which DB patches to install (usually all are recommended for new installs).</p>

	<form method="POST" action="./?install=true&step=four">
		
		<fieldset><legend>DB patches to apply</legend>
<?php 
		foreach($patches as $file=>$label) {
?>
		<label><input type="checkbox" name="files[]" value="<?php print(htmlentities($file)); ?>" checked="checked"/><?php print(htmlentities($label));?></label>
<?php
		}
?>	
		</fieldset>
			<label><p>It is also recommended you change your administrative account password at this time:</p>
			New Admin Password: <input type="text" name="admin_pass" /></label>
		<p>Optionally, you may pre-populate the DB with dummy data (not recommended for installations intended to become production instances).</p>
		<label>Install Dummy Data? <input type="checkbox" name="dummy" value="true"/></label>
		<br/>
		<input type="submit"/>
	</form>
<?php
		return false; 
	} else {
		$files = array_key_exists('files', $_REQUEST)
			? is_array($_REQUEST['files'])
				? $_REQUEST['files']
				: array($_REQUEST['files'])
			: array();
		foreach($files as $filename) {
			$htmlSafeFilename = htmlentities($filename);
			$filepath = APPLICATION_PATH . '/db/' 
				. str_replace(array('..','/','\\'), '', $filename);
			$sql = 
				file_exists($filepath)
				? file_get_contents($filepath)
				: '';
			$sqlArray = explode('//#STATEMENT', $sql);
			if ('' == $sql) {
				print_error('Unable to process the requested patch file: ' . $htmlSafeFilename);
				printStepFourRetry();
				return false;
			} else {
				Rd_Pdo::beginTransaction();
				foreach($sqlArray as $statement) {
					$rs = Rd_Pdo::query($statement); //#TODO #CRITICAL this seems to not be catching errors. sometimes the update scripts don't completely execute.
					if(Rd_Pdo_PearAdapter::isError($rs)) {
						print_error('Problem executing query: ' . Rd_Pdo_PearAdapter::getErrorMessage($rs) . "<br/>For Query: <pre>{$statement}</pre>");
						printStepFourRetry();
						Rd_Pdo::rollback();
						return false;
					}
				}
				Rd_Pdo::commit();
				
				print_success("Applied {$filename} successfully.");	
				if($rs) {
					$rs->closeCursor();	
				}
			}
		}
		if (array_key_exists('dummy', $_REQUEST) && '' != $_REQUEST['dummy']) {
			$sql = 
				file_exists(APPLICATION_PATH . '/db/demo_data.sql')
				? file_get_contents(APPLICATION_PATH . '/db/demo_data.sql')
				: '';
			$sqlArray = explode('//#STATEMENT', $sql);
			if ('' == $sql) {
				print_error('Unable to process the dummy data file.');
				printStepFourRetry();
				return false;
			} else {
				Rd_Pdo::beginTransaction();
				foreach($sqlArray as $statement) {
					$rs = Rd_Pdo::query($statement); //#TODO #CRITICAL this seems to not be catching errors. sometimes the update scripts don't completely execute.
					if(Rd_Pdo_PearAdapter::isError($rs)) {
						print_error('Problem executing query: ' . Rd_Pdo_PearAdapter::getErrorMessage($rs));
						printStepFourRetry();
						Rd_Pdo::rollback();
						return false;
					}
				}
				Rd_Pdo::commit();
			}
			print_success("Applied dummy data successfully.");
			if($rs) {
				$rs->closeCursor();	
			}
			if (!array_key_exists('admin_pass', $_REQUEST) || '' == trim($_REQUEST['admin_pass'])) {
				print_warning("The admin password has been set to the value specified in this patch file.");
			}
		}
		if (array_key_exists('admin_pass', $_REQUEST) && '' != trim($_REQUEST['admin_pass'])) {
			$htlmSafePassword = htmlentities($_REQUEST['admin_pass']);
			$sql = 'UPDATE special_users AS s SET s.password="'
				. md5($_REQUEST['admin_pass'])
				. '" WHERE s.user_id = 1 AND (SELECT username FROM users AS u WHERE u.user_id = s.user_id LIMIT 1) = "admin";';
			Rd_Pdo::beginTransaction();
			$rs = Rd_Pdo::query($sql);
			if(Rd_Pdo_PearAdapter::isError($rs)) {
				print_error('Problem executing query: ' . Rd_Pdo_PearAdapter::getErrorMessage($rs));
				printStepFourRetry();
				Rd_Pdo::rollback();
				return false;
			}
			Rd_Pdo::commit();
			print_success("Your <span class='normal'>admin</span> account password is set to <span class=''>{$htlmSafePassword}</span>.");
			if($rs) {
				$rs->closeCursor();	
			}
		}
		return true;
	}
}

function printStepFourRetry() {
?>
		<p>Failed to apply the patches properly. It is recommended to <a href="./?install=true&step=three">re-run step 3</a> to ensure DB integrity.
		<br />
		or
		<br />
		<a href="./?install=true">Start Over</a></p>			
<?php		
}
	