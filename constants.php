<?php 

// Define application version
defineLoad('APPLICATION_STATUS', 'online');

// Define application version
defineLoad('APPLICATION_VERSION', '2.0'); //#RELEASE make sure this is updated!

// Define application environment
defineLoad('APPLICATION_ENV', 'production');

// Define path to application directory
defineLoad('ROOT_PATH', realpath(dirname(__FILE__)));

// Define path to application directory
defineLoad('APPLICATION_PATH', realpath('./secure'));

// Define application configuration location
defineLoad('APPLICATION_CONF', APPLICATION_PATH . '/configs/config.xml');

// Define HTTP ports
defineLoad('STANDARD_PORT', '80');
defineLoad('SSL_PORT', '443');

define('APPLICATION_SSL', 
	array_key_exists('HTTPS', $_SERVER) 
	&& $_SERVER['HTTPS'] 
	&& $_SERVER['HTTPS'] != 'off'
);
if (!defined('APPLICATION_STATUS') || 'commandline' != APPLICATION_STATUS) {
	$cleanProtocol = substr(strtolower($_SERVER['SERVER_PROTOCOL']), 0, strpos(strtolower($_SERVER['SERVER_PROTOCOL']), '/'));
	define('APPLICATION_PROTOCOL',  $cleanProtocol . (APPLICATION_SSL ? 's' : ''));
} else {
	define('APPLICATION_PROTOCOL',  '');
}