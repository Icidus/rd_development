<?php
/*******************************************************************************
Service/Json/Resource/Term.php
Implements a resource query object for term information

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
 * Class for querying RD term information.
 * @author jthurtea
 *
 */

class Service_Json_Resource_Term extends Service_Json_Resource
{

	protected $_configuration = array(
		'query' => array(
			'sql' => 'SELECT term_id AS `id`, term_name AS `name`, term_year AS `year`, begin_date, end_date FROM `terms`;',
			'label' => 'terms',
			'sort' => 'sort_order'
		),
		'level' => Service_Json::SERVICE_LEVEL_PUBLIC
	);
	
	protected static function _termFacultyQuery()
	{
		return 'SELECT `u`.username AS `instructor_' . Service_Json::RD_USERNAME_ALIAS . '`, `u`.user_id AS `instructor_user_id`, `u`.last_name AS instructor_last_name, `u`.first_name AS instructor_first_name, '
			. '`i`.course_instance_id AS ci, `a`.course_alias_id AS ca, `c`.course_id AS course, '
			. '`c`.department_id AS department, `c`.course_number AS number, '
			. '`a`.section AS `section`, `a`.registrar_key AS registrar_key, '
			. '`i`.primary_course_alias_id AS primary_ca '
			. 'FROM access AS `x` '
			. 'JOIN `users` as `u` ON `x`.user_id = `u`.user_id '
			. 'JOIN course_aliases AS `a` ON `a`.course_alias_id = `x`.alias_id '
			. 'JOIN courses AS `c` ON `a`.course_id = `c`.course_id '
			. 'JOIN course_instances AS `i` ON `a`.course_instance_id = `i`.course_instance_id '
			. 'JOIN terms AS `t` ON `t`.term_id = {{param}} AND `t`.term_name = `i`.term AND `t`.term_year = `i`.year '
			. 'WHERE `x`.permission_level = ' . Service_Json::RD_FACULTY_PERM_LEVEL . ';';
	}
}
