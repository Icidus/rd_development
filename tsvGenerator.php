<?php
/***********************************************************************
tsvGenerator.php
accepts a POSTed dataSet from a report or reserve list and generates a
tab-separated spreadsheet.

Created by Chris Roddy (croddy@emory.edu)

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

header("Content-Type: text/tab-separated-values");
header("Content-Disposition: attachment; filename=\"reservesData.tsv\"");
header("Cache-Control: no-cache");
header("Pragma: no-cache");
header("Expires: -1");


$dataSet = unserialize(base64_decode($_POST['dataSet']));
if (!isset($_POST['dataSet']) || (count($dataSet) < 1)) {
    echo "Empty data set.\t";
    die();
}

//column headers
foreach ($dataSet[0] as $key => $value)
    echo str_replace("\t", "     ", "$key") . "\t";
echo "\n";

//data
for ($i=0; $i<count($dataSet); $i++) {
    foreach ($dataSet[$i] as $key => $value)
        echo str_replace("\t", "     ", "$value") . "\t";
    echo "\n";
}

?>
