<h2>Step 3: Database Setup</h2>
<?php 

include_once(APPLICATION_PATH . '/scripts/installer/dbconn.php');

if(verifyDb($dsn) && setupDb()){
?>
	<p>You are now ready for the final step: <a href="./?install=true&step=four"> Step 4, Update DB and Setup the Admin User.</a></p>
<?php 
}

/**
 * checks to make sure MySQL server is v4+
 */
function checkMysqlVersion($targetVersion = '4.1', $comparison = '>=') {
	$version = preg_replace('|[^0-9\.]|', '', Rd_Pdo::getVersion());
	return version_compare($version, $targetVersion, $comparison);
}

function verifyDb($dsn)
{
	try{ //Rd_Pdo should no longer throw exceptions...
		$success = Rd_Pdo::connect($dsn);
		if(!$success) {
			throw new Exception(Rd_Pdo::getErrorMessage());
		}
		print_success('MySQL server connected.');
	} catch (Exception $e) {
?>
		<p class="error">A connection error occured connecting to <?php print($dsn['hostspec']); ?> as <?php print($dsn['username']); ?>.</p>
		<p><?php print($e->getMessage());?></p>
		<p>You may <a href="./?install=true&step=two">go back</a> if needed.</p>
<?php 
		return false;
	}
	$comparison = '>=';
	$matches = array(
		'<' => 'higher than',
		'lt' => 'higher than',
		'<=' => 'higher or matching',
		'le' => 'higher or matching',
		'>' => 'lower than',
		'gt' => 'lower than',
		'>=' => 'lower or matching',
		'ge' => 'lower or matching',
		'==' => 'matching',
		'eq' => 'matching',
		'!=' => 'other than',
		'<>' => 'other than',
		'ne' => 'other than'
	);
	if(!array_key_exists($comparison,$matches)) {
		throw new Exception('Invalid version match operator: ' . $comparison);
	}
	$matchDescription = $matches[$comparison];
	if (checkMysqlVersion()){
		print_success('MySQL server version is fine -- ' . Rd_Pdo::getVersion());
		return blankOrOverride();
	} else {
		print_error("MySQL server {$matchDescription} {$targetVersion} is required. Found " . Rd_Pdo::getVersion());
		return false;	
	}
}

function blankOrOverride() {
	$override = array_key_exists('override', $_GET);
	$exists = Rd_Pdo::hasTable('users');
	if ($exists && !$override) {
?>
		<p class="error">The selected DB (<?php print(Rd_Pdo::getSchemaName()); ?>) already has tabels that may be an existing RD installation, or another application with conflicting tables.</p>
		<p>You may <a href="./?install=true&step=two">go back</a> if needed.</p>
		<p>You may alternatively <a href="./?install=true&step=three&override=true">install over</a> these DB tables if you are certain.</p>
<?php
		return false;
	}
	return true;
}

function setupDb()
{
	if(!is_readable(APPLICATION_PATH . '/db/create.sql')) {
		print_error('Could not locate SQL dump file at <span class="normal nobreak">'.APPLICATION_PATH.'/db/create.sql</span>');
		printStepThreeRetry();
		return false;
	}
	if(!is_readable(APPLICATION_PATH . '/db/drop_all.sql')) {
		print_error('Could not locate SQL cleanup file at <span class="normal nobreak">'.APPLICATION_PATH.'/db/drop_all.sql</span>');
		printStepThreeRetry();
		return false;
	}
	if (!array_key_exists('submit_create_db', $_REQUEST)) {
		printDbInstallForm();
		return false;
	}
	$adminQueries = array();
	$tableQueries = '';
	if (array_key_exists('db_admin_username', $_REQUEST) 
		&& array_key_exists('db_admin_pass', $_REQUEST)
	) {
		Rd_Pdo::reconnectAs($_REQUEST['db_admin_username'],$_REQUEST['db_admin_pass']);
	}
	
	if (array_key_exists('create_user', $_REQUEST)){
		$sql = "GRANT SELECT, INSERT, UPDATE, DELETE ON `" 
			. Rd_Pdo::getSchemaName() 
			. "`.* TO '" 
			. addcslashes($_REQUEST['db_admin_username'])
			. "'@'" 
			. Rd_Pdo::getHostName() 
			. "'";
		try {
			$password = Rd_Config::get('database:pwd');
			$sql .= " IDENTIFIED BY '{$password}'";
		} catch (Exception $e) {
			print_warning('The ReservesDirect MySQL user should have a password for security purposes. No password indicated in the config. Please fix this (in the DB and config) after installation is complete.');
		}
		$adminQueries[] = $sql;
	}

	if (array_key_exists('create_db', $_REQUEST)){
		$adminQueries[] = "DROP DATABASE IF EXISTS `" . Rd_Pdo::getSchemaName()  . "`";
		$adminQueries[] = "CREATE DATABASE `" . Rd_Pdo::getSchemaName()  . "`";
	}
	if (array_key_exists('create_db', $_REQUEST) 
		|| array_key_exists('create_tables', $_REQUEST)
	){
		$dropQueries = file_get_contents(APPLICATION_PATH . '/db/drop_all.sql');
		$dropArray = explode('//#STATEMENT', $dropQueries);
		$tableQueries = file_get_contents(APPLICATION_PATH . '/db/create.sql');
		$tableArray = explode('//#STATEMENT', $tableQueries);	
	}
	Rd_Pdo::beginTransaction();
	foreach($adminQueries as $sql) {
		$rs = Rd_Pdo::query($sql);
		if(Rd_Pdo_PearAdapter::isError($rs)) {
			print_error('Problem executing query: ' . Rd_Pdo_PearAdapter::getErrorMessage($rs));
			printStepThreeRetry();
			Rd_Pdo::rollback();
			return false;
		} else {
			$result = $rs->fetchAll();
		}
	}
	Rd_Pdo::commit();
	if(array_key_exists('create_db',$_REQUEST)) {
		print_success("Created <tt>" . Rd_Pdo::getSchemaName() . "</tt> database.");
	}
	if(array_key_exists('create_user',$_REQUEST)) {
		print_success("Granted access to <tt>" . Rd_Config::get('database:username') . "</tt> user to database.");
	}
	Rd_Pdo::reconnectAs(
		Rd_Config::get('database:username'), 
		Rd_Config::get('database:pwd')
	);
	if('' != $dropQueries) {
		Rd_Pdo::beginTransaction();
		foreach($dropArray as $statement) {
			$rs = Rd_Pdo::query($statement);
			if(Rd_Pdo_PearAdapter::isError($rs)) {
				print_error('Problem executing query: ' . Rd_Pdo_PearAdapter::getErrorMessage($rs) . "<br/>For Query: <pre>{$statement}</pre>");
				printStepThreeRetry();
				Rd_Pdo::rollback();
				return false;
			}
		}
		Rd_Pdo::commit();
		print_success("Dropped Old ReservesDirect Tables");
	} else {
		print_error('A problem occured attempting to load the SQL to clear tables.');
	}
	Rd_Pdo::commit();
	Rd_Pdo::reconnectAs(
		Rd_Config::get('database:username'), 
		Rd_Config::get('database:pwd')
	);
	if('' != $tableQueries) {
		Rd_Pdo::beginTransaction();
		foreach($tableArray as $statement) {
			$rs = Rd_Pdo::query($statement);
			if(Rd_Pdo_PearAdapter::isError($rs)) {
				print_error('Problem executing query: ' . Rd_Pdo_PearAdapter::getErrorMessage($rs) . "<br/>For Query: <pre>{$statement}</pre>");
				printStepThreeRetry();
				Rd_Pdo::rollback();
				return false;
			}
		}
		Rd_Pdo::commit();
		print_success("Created ReservesDirect Tables");
	} else {
		print_error('A problem occured attempting to load the SQL to initialize tables.');
	}
	Rd_Pdo::reconnectAs(
		Rd_Config::get('database:username'), 
		Rd_Config::get('database:pwd')
	);
	return true;
}

function printDbInstallForm()
{
?>
	<script type="text/javascript">
		$(document).ready(function(){
			$('#create_db').change(check1);
			$('#create_tables').change(check4);
			$('#create_user').change(check2);
			$('#admin_user').change(check3);
			check1();
			check2();
		});
	
		function check1() {
			var createDb = $('#create_db');
			var createTables = $('#create_tables');
			var test = createDb.is(':checked');
			if (createDb.is(':checked')) {
				createDb.attr('checked', true);
				createTables.attr('checked', true);
				createTables.attr('disabled', true);
				$('#warnCreate').show();
			} else {
				createDb.attr('checked', false);
				createTables.attr('checked', false);
				createTables.attr('disabled', false);
				$('#warnCreate').hide();
			}
			check4();
		}
		function check4() {
			var createTables = $('#create_tables');
			if (createTables.is(':checked') && !createTables.attr('disabled')) {
				$('#warnTables').show();
			} else {
				$('#warnTables').hide();
			}
		}
		
		function check2() {
			var createUser = $('#create_user');
			var adminUser = $('#admin_user');
			if (createUser.is(':checked')) {
				$('#warnUser').show();
			} else {
				$('#warnUser').hide();
			}
			check3();
		}
		
		function check3() {
			var createUser = $('#create_user');
			var adminUser = $('#admin_user');
			var userBlock = $('#dbCredentials');
			if (createUser.is(':checked')) {
				adminUser.attr('checked', true);
				adminUser.attr('disabled', true);
			} else {
				adminUser.attr('disabled', false);
			}
			if (adminUser.is(':checked')) {
				$('input',userBlock).attr('disabled', false);
				userBlock.show();
			} else {
				$('input',userBlock).attr('disabled', true);
				userBlock.hide();
			}				
		}

	</script>		
	<form method="post" name="db_form1">
		<input type="hidden" name="step" value="three" />
		<label><input type="checkbox" id="create_db" name="create_db" /> Create <tt><?php print(Rd_Pdo::getSchemaName()); ?></tt> database on host <tt><?php print(Rd_Pdo::getHostName()); ?></tt>.</label>
		<p id="warnCreate"><span style="padding-left:2em;"><strong>WARNING:</strong> this will delete any existing database with that name!</span></p>
		<label><input type="checkbox" id="create_tables" name="create_tables" /> Create ReservesDirect tables in <tt><?php print(Rd_Pdo::getSchemaName()); ?></tt> database.</label>
		<p id="warnTables"><span style="padding-left:25px;"><strong>WARNING:</strong> this will delete any ReservesDirect tables that already exist!</span></p>
		<label><input type="checkbox" id="create_user" name="create_user" /> Create <tt><?php print(Rd_Pdo::getUserName()); ?></tt> user and grant usage access to <tt><?php print(Rd_Pdo::getSchemaName()); ?></tt> database.</label>
		<p id="warnUser"><span style="padding-left:25px;"><strong>WARNING:</strong> After completing the installation, you should review this user's privileges to make sure they are secure!</span></p>
		<label><input type="checkbox" id="admin_user" name="admin_user" /> Use these credentials to perform the operations above (otherwise will attempt to use <tt><?php print(Rd_Pdo::getUserName()); ?></tt> credentials from the application configuration, <?php print(APPLICATION_CONF);?>):</label>
		<div style="padding-left: 50px;" id="dbCredentials">
			<label>DB Admin Username: <input type="text" size="40" id="db_admin_username" name="db_admin_username" value="" disabled="disabled" /></label>
			<label>DB Admin Password: <input type="password" size="40" id="db_admin_pass" name="db_admin_pass" value="" disabled="disabled" /></label>
		</div>
		<br/>
		<input type="submit" name="submit_create_db" value="Setup Database" />
	</form>	
<?php	
}

function printStepThreeRetry() {
?>
		<p>Fix this manually and <a href="./?install=true&step=three">retry step 3</a>
		<br />
		or
		<br />
		<a href="./?install=true">Start Over</a></p>			
<?php		
}