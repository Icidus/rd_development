<?php 
/*******************************************************************************
doMisc.php
Handle various pre-dispatch tasks.

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

Rd_Layout::detectMobile();

//if selected set CourseInstance
if (array_key_exists('ci', $_REQUEST) && '' != trim($_REQUEST['ci'])) {
	$ci = new courseInstance(trim($_REQUEST['ci']));
	$ci->getCourseForUser();
} else {
	$ci = NULL;
}

Rd_Registry::set('root:selectedCourseInstance', $ci);

//initiate calendar object, since some files must be included in the <head> by one of the html includes
//this object should be global and used by all files (no need to create a new obj)
$calendar = new Calendar();
Rd_Registry::set('root:calendarWidget', $calendar);

//if there is a command to delete a note, do it //#TODO deprecate this for something more ajax like
if(array_key_exists('deleteNote', $_REQUEST)) {
	noteManager::deleteNote($_REQUEST['deleteNote']);
}