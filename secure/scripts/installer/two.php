<?php 
/*******************************************************************************
installer/two.php

Created by Troy Hurteau, NCSU Libraries, NC State University (libraries.opensource@ncsu.edu).

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
?>
<h2>Step 2: Configuration Editor</h2>

<?php 

check_file();

if (!array_key_exists('config_loc_source', $_REQUEST) && !array_key_exists('config_loc_target', $_REQUEST)) {
	promptSourceTarget();
} else if (!array_key_exists('config_submit', $_REQUEST)) {
	promptConfigForm();
} else {
	saveOrOutput();
}

function check_file()
{
	clearstatcache(true);
	$configInPlace = file_exists(APPLICATION_CONF);
	if($configInPlace) {
?>
	<p><em>(if you do not need to setup a config file, you can skip to <a href="./?install=true&step=three">step 3</a>.)</em></p>
<?php 		
	}
	$defaultConfigLocation = ROOT_PATH . '/secure/configs/config.xml';
	$whyItsBadIdea = "This location is not recommended for production installations unless the "
		. "&quot;secure&quot; folder is protected from being served out over HTTP by web server "
		. "settings. This location is generally acceptable for installations that are not publicly accessible "
		. "(i.e. development instances). The recommended location for the configuration file is somewhere that is not in "
		. "the web server's document root since it is an XML file which contains the DB password and other potentially "
		. "sensitive information.";
/*	if (!$configInPlace) {
		$configInDefaultPlace = file_exists($defaultConfigLocation);
		$confSource = file_exists('.application_conf') ? 'the .application_conf file settings' : 'constants.php';
		$confVerb = file_exists('.application_conf') ? 'set' : 'create';
		$prefMethod = 'set the APPLICATION_CONF value in constants.php';
		print_error("The configuration file was not found where it should be according "
			. "to {$confSource}: <span class='normal nobreak'>" . APPLICATION_CONF . "</span>. "
		);
		if ($configInDefaultPlace) {
			print_warning("It was found in the default location: <span class='normal nobreak'>{$defaultConfigLocation}</span>. "
				. $whyItsBadIdea
			);
			print_error("If you wish to keep the files in this folder, please {$prefMethod} or {$confVerb} the "
				. ".application_conf file with the contents: <span class='normal nobreak'>\"{$defaultConfigLocation}\"</span>. "
				. "<br/><br/>Otherwise {$prefMethod} or {$confVerb} the .application_conf file with the contents being the fully qualified path to the "
				. "configuration file somewhere outside of the server's document root or protected by server settings."
			);
		} else {		
			print_error("Please {$prefMethod} or {$confVerb} the .application_conf file with the contents being the fully qualified path to the "
				. "configuration file somewhere outside of the server's document root or protected by server settings."
			);
		} 

	}*/
	if (strpos(APPLICATION_CONF, ROOT_PATH) === 0) {
		print_warning("Your instance appears to expect the configuration to be in the web server's document root: <span class='normal nobreak'>" . APPLICATION_CONF . "</span>. "
			. $whyItsBadIdea
		);
	}
	return $configInPlace;
}

function promptSourceTarget() {
	$defaultXmlConfig = APPLICATION_PATH . '/configs/sample_config.xml';
	$pleaseFix = 'Please adjust the value for APPLICATION_CONF (in constants.php or .application_conf) before proceeding.';
?>
		<form method="post" action="./?install=true">
			<input type="hidden" name="step" value="two" />
						
			<h3>Configuration file location:</h3>	
			<p>This installation is set to look for the configuration file at:</p>
			<blockquote><?php print(APPLICATION_CONF);?> (<?php 
	print(
		is_writable(APPLICATION_CONF) 
		? '<span class="success">writable</span>' 
		: '<span class="warning">not writable</span>'
	);?>)</blockquote>
			<p>if this is not correct. <?php print($pleaseFix);?></p>
	<?php	
		if (!file_exists($defaultXmlConfig)) {
		print_error("Unable to find the sample config file (<span class='normal nobreak'>{$defaultXmlConfig}</span>). This generally means that the configuration is incorrect since it should be in the same folder as config.xml. {$pleaseFix}");
?>		
		</form>
<?php 		
		return;
	}?>
			<p><label>Source File: <select name="config_loc_source">
				<option value="<?php print($defaultXmlConfig); ?>"><?php print(htmlentities($defaultXmlConfig)); ?></option>
				<?php if (file_exists(APPLICATION_CONF)) { ?><option value="<?php print(APPLICATION_CONF); ?>"><?php print(htmlentities(APPLICATION_CONF)); ?></option> <?php } ?>
			</select></label></p>			
			<p><label>Target File: <input type="text" size="40" name="config_loc_target" value="<?php print(APPLICATION_CONF); ?>" /> <span class="small"><i>ex: /etc/reservesdirect/conf.xml</i></span></label></p>
			
			<p>	This should be the path of the directory where you want to save the ReservesDirect configuration file.  It is strongly recommended to place the configuration file outside of the document root of the web application.</p>
			<p class="warning">
			If you want the installer to handle configuration file writing, please check the following before proceeding:
			</p><ul>
				<li>The HTTPd user must have write permissions to the target directory/file specified above.</li>
			</ul><p class="warning">
			If you wish to edit files manually, proceed and you will be provided with the xml to insert into the configutation file manually.
			</p>
			<input type="submit" name="begin_setup" value="Create Configuration File" />
		</form>
<?php
}

function promptConfigForm() {
	$pathToSource = (
		array_key_exists('config_loc_source', $_REQUEST)
		? $_REQUEST['config_loc_source']
		: APPLICATION_CONF
	);
	$xmlTargetPath = (
		array_key_exists('config_loc_target', $_REQUEST)
		? $_REQUEST['config_loc_target']
		: APPLICATION_CONF
	);
	$targetWritable = is_writable($xmlTargetPath);
	
	$truthyValues = array(
		'on','ON','On','oN','true','1',1,true
	);
	$ldapPresent = extension_loaded('ldap');
	$sslPresent = extension_loaded('openssl');
	$curlPresent = extension_loaded('curl');
	$fileUploads = in_array(ini_get('file_uploads'), $truthyValues, true);
	$maxPostSize = ini_get('post_max_size');
	$maxUploadSize = ini_get('upload_max_filesize');
	$openBaseDir = ini_get('open_basedir');
	$safeMode = in_array(ini_get('safe_mode'), $truthyValues, true);
	$reflection = extension_loaded('Reflection');
	$session = extension_loaded('session');
?>
		<h3>Some Additional Information about your server enviroment:</h3>
		<table>
			<tr><td>Upload Limits</td><td><?php 
				print(
					$fileUploads 
					? 'Filesize: ' . $maxUploadSize . ', Post Size: ' . $maxPostSize
					: '<span class="warning">DISABLED</span>' 
				);
			?></td></tr>
			<tr><td>LDAP Account Support</td><td><?php 
				print($ldapPresent ? 'Enabled' : '<span class="warning">DISABLED</span>');
			?></td></tr>
			<tr><td>cURL Support</td><td><?php 
				print($curlPresent ? 'Enabled' : '<span class="warning">DISABLED</span>');
			?></td></tr>
			<tr><td>SSL Support (for cULR/LDAP)</td><td><?php 
				print($sslPresent ? 'Enabled' : '<span class="warning">DISABLED</span>');
			?></td></tr>
			<tr><td>Safemode</td><td><?php 
				print(
					$safeMode 
					? ('<span class="warning">ENABLED</span> openbasedir settings can be an issue. Yours is set to: ' . $openBaseDir) 
					: 'Disabled (preferred)'
				);
			?></td></tr>
			<tr><td>Reflection Support</td><td><?php 
				print($reflection ? 'Enabled' : '<span class="error">DISABLED</span>, you will need this.');
			?></td></tr>
			<tr><td>Session Support</td><td><?php 
				print($sslPresent ? 'Enabled' : '<span class="error">DISABLED</span>, you will need this.');
			?></td></tr>
		</table>
		<p>Please see documentation at <a href="<?php print(HELP_URL); ?>"><?php print(HELP_LINK_LABEL);?></a> for help.</p>
		<form method="post" name="config_form">
			<input type="hidden" name="step" value="two" />
			<input type="hidden" name="config_loc_target" value="<?php print($xmlTargetPath); ?>" />
			<input type="hidden" name="config_loc_source" value="<?php print($pathToSource); ?>" />
			<p>Reading From: <?php print($pathToSource); ?></p> 
			<p>Writing To: <?php print($xmlTargetPath); ?> 
			(<?php print(
				$targetWritable
		 		? '<span class="success">writable</span>' 
				: '<span class="warning">not writable</span>'
			); ?>)
			</p>
			<?php print(Xml_Form::generateFields(simplexml_load_file($pathToSource))); ?>
			<input type="submit" name="config_submit" value="Save" />
		</form>
<?php 
}

function rebuildXml() {
	$pathToSource = (
		array_key_exists('config_loc_source', $_REQUEST)
		? $_REQUEST['config_loc_source']
		: APPLICATION_CONF
	);
	//load the config file
	$config = new DOMDocument('1.0', 'UTF-8');
	$config->load($pathToSource);
	$xpath = new DOMXPath($config);
	
	foreach($_REQUEST['xml'] as $node_xpath=>$node_value) {
		$node = $xpath->query($node_xpath)->item(0);
		$node_value = htmlentities($node_value);
		if(!is_null($node) && ($node_value != $node->nodeValue)) {
			$node->nodeValue = trim($node_value);
		}
	}
	return $config->saveXML($config->documentElement); //passing root node forces utf-8
}

function saveOrOutput()
{
	$resultXml = rebuildXml();
	if(!$resultXml) {
?>
	<p class="error">Unable to build an XML document from the provided data. Maybe go back and try again?</p>
<?php 		
		return;
	}
	if (
		array_key_exists('config_loc_target', $_REQUEST)
		&& saveXmlConfig($resultXml, $_REQUEST['config_loc_target'])
	) {
?>
	<p>Your configuration file has been saved to: <span class="nobreak"><b><?php print($_REQUEST['config_loc_target']); ?></b></span></p>
	<p>The output appears as follows:</p>
<?php 
	} else {
?>
	<p>Your configuration file is displayed below. Either you didn't provide a location to store it, or the script was unable to write to this file.</p>
	<p>Place it at: <span class="nobreak"><?php print(APPLICATION_CONF); ?></span> or change your .application_conf setting to the path where you place the file.</p>
<?php 		
	}
	outputXmlConfig($resultXml);
?>
	<p><a href="./?install=true&step=three">Proceed to the next step.</a></p>
<?php 
}

function saveXmlConfig($outputXml,$xmlTargetPath)
{
	if(is_writable($xmlTargetPath)) {
		if(file_put_contents($xmlTargetPath, $outputXml) !== false) {
			return true;
		} else {
?>
		<p class="error">Unable to write to the target file (though permissions seemed appropriate).</p>
<?php 
		}
	} else {	
?>
		<p class="error">The target file is not writable to the web server.</p>
<?php 
		return false;
	}
}

function outputXmlConfig($outputXML)
{
?>
	<form><textarea cols="60" rows="20" ><?php print(htmlentities($outputXML)); ?></textarea></form>
<?php 	
}

/**
 * catch-all function for setting up the configuration file;
 * coordinates different steps
 */
function setup_config() {
	//set path of default config file
	$default_xmlConfig = RD_ROOT.'config.xml.example';
	
	//sets $xmlConfig to path of actual config xml file		
	//if file location is set by the user attempt to load the directed file otherwise load the default
	if (array_key_exists('config_loc_source', $_REQUEST) 
		&& is_readable($_REQUEST['config_loc_source'])
		&& @simplexml_load_file($_REQUEST['config_loc_source'])
	){
		$xmlConfig = $_REQUEST['config_loc_source'];
	} else if (@simplexml_load_file(APPLICATION_CONF)) {
		$xmlConfig = APPLICATION_CONF;
	} else if (@simplexml_load_file($default_xmlConfig)){
		$xmlConfig = $default_xmlConfig;
	} else {
		print_error("Default source file (<tt>{$default_xmlConfig}</tt>) is malformed.");
		die(-1);
	}
	
	if(!is_readable($xmlConfig)){
		print_error("Source file (<tt>{$xmlConfig}</tt>) is unreadable.");
		die(-1);
	}
	
	//check to see if there is already a config file at specified location		
	if(array_key_exists('config_loc', $_REQUEST) && is_writable($_REQUEST['config_loc'])) {
		//load the specified config file
		$config_path = $_REQUEST['config_loc'];
	} else if (is_writable(APPLICATION_CONF)) {	
		$config_path = APPLICATION_CONF;
	} else {
		$config_path = '';
	}
	
	//decide what to do next	
	if(array_key_exists('submit_config',$_REQUEST)) {	//handle config data
		if('' != $config_path){
			store_config($config_path);
		}
		
		//button to next step
?>
		<hr />
		Before continuing to the next step, please make sure that:
		<ol>
			<li>the configuration XML file exists in a secure location</li>
			<?php if('' == $config_path){ ?><li>The file at <?php print(APPLICATION_CONF); ?> has the correct configuration information. No valid target file was specified.</li><?php }  
			else if($config_path && APPLICATION_CONF != $config_path) {?><li>the file is moved to where APPLICATION_CONF specifies(<?php print(APPLICATION_CONF); ?>), or APPLICATION_CONF is updated to where the file was stored(<?php print($config_path); ?>)</li><?php } 
			if(!is_writable(APPLICATION_CONF) || !is_writable($config_path)){ ?><li>the HTTPd/PHP user has permissions to read this file</li> <?php } ?>
			
		</ol>
		<form method="post">
			<input type="hidden" name="step" value="three" />
			<input type="submit" name="manage_db" value="Configure database" />
		</form>
<?php
	}
	else if(array_key_exists('config_loc', $_REQUEST)) {	//show config data form
?>

<?php
	}
	else {	//get ready to edit/create the config file

	}
}	
	
