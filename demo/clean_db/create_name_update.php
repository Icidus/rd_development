#!/usr/local/bin/php -q
<?php
/*******************************************************************************
create_name_update.php

Created by Emory University
Modified by NCSU Libraries, NC State University. Modifications by Troy Hurteau (libraries.opensource@ncsu.edu).

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
    // get contents of a file into a string
	$filename = 'fakenames.txt';
	
	$handle = fopen($filename, 'r');
	$contents = fread($handle, filesize($filename));
	
	fclose($handle);
	
	$line = explode("\n", $contents);
	unset($contents);
	
	$out = fopen('update_names.sql', 'w');

	fwrite($out,  "ALTER TABLE `users` DROP INDEX `username`;");	
	for ($i=0;$i<count($line);$i++)
	{
		list($username, $fname, $lname) = explode(' ', $line[$i]);
		$uID = $i + 1;
		$update = "UPDATE users SET username='$username', first_name='$fname', last_name='$lname', email='demo@reservesdirect.org' WHERE user_id = $uID;\n";
		if (fwrite($out, $update))
			echo "$update\n";
	}
	fwrite($out, "ALTER TABLE `users` ADD UNIQUE `username` (`username`);");
	fclose($out);
?>
