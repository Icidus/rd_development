<?php 

$dsn = array(
    'phptype'  => (string)Rd_Config::get('database:dbtype'),
    'username' => (string)Rd_Config::get('database:username'),
    'password' => (string)Rd_Config::get('database:pwd'),
    'hostspec' => (string)Rd_Config::get('database:host'),
    'database' => (string)Rd_Config::get('database:dbname'),
    'key'      => (string)Rd_Config::getOptional('database:dbkey',''),
    'cert'     => (string)Rd_Config::getOptional('database:dbcert',''),
    'ca'       => (string)Rd_Config::getOptional('database:dbca',''),
    'capath'   => (string)Rd_Config::getOptional('database:capath',''),
    'cipher'   => (string)Rd_Config::getOptional('database:cipher','')
);

$options = array(
    'ssl' 		=> (string)Rd_Config::getOptional('database:ssl','true'),
    'debug'     => (string)Rd_Config::getOptional('database:debug','false')
);

?>