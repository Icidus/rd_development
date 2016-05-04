<?php
/*******************************************************************************
Service/Json/Resource/CourseAlias.php
Implements a resource query object for course alias information

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
/**
 * 
 * Class for querying RD course alias information.
 * @author jthurtea
 *
 */

class Service_Json_Resource_CourseAlias extends Service_Json_Resource
{

	protected $_configuration = array(
		'query' => array(
			'sql' => 'SELECT course_alias_id AS `id`, course_id AS `course`, course_instance_id AS `ci`, course_name AS `name`, section, registrar_key, override_feed FROM course_aliases;',
			'label' => 'course_aliases',
			'partition' => true
		)
	);
}
