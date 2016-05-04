<?php
/*******************************************************************************
Service/Json/Resource/CourseInstance.php
Implements a resource query object for course instance information

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
 * Class for querying RD course instance information.
 * @author jthurtea
 *
 */

class Service_Json_Resource_CourseInstance extends Service_Json_Resource
{

	protected $_configuration = array(
		'query' => array(
			'sqlMethod' => '_courseInstanceQuery',
			'label' => 'course_instances',
			'partition' => true
		),
		'actions' => array(
			'term' => array(
				'query' => array(
					'sqlMethod' => '_simplifiedCourseQuery',
					'manualBehead' => true,
					'label' => 'term_course_instances',
					'partition' => true,
					'sort' => 'ci'
				)
			),
			'term-active' => array(
				'query' => array(
					'sqlMethod' => '_activeCourseQuery',
					'manualBehead' => true,
					'label' => 'term_active_course_instances',
					'partition' => true,
					'sort' => 'ci'
				)
			)		
		)
	);
	
	protected static function _courseInstanceQuery()
	{
		return 'SELECT `c`.course_instance_id AS `id`, `c`.primary_course_alias_id AS primary_ca, '
			. '`t`.term_id AS `term`, `c`.`term` AS term_name, `c`.`year` AS term_year, '
			. '`c`.activation_date, `c`.expiration_date, `c`.reviewed_date, '
			. '`c`.`status`, `c`.`enrollment` '
			. 'FROM course_instances AS `c` '
			. 'JOIN terms AS `t` ON `c`.`term` = `t`.term_name AND `c`.`year` = `t`.term_year;';
	}
	
	protected static function _simplifiedCourseQuery() 
	{
		return self::_commonCourseQuerySelect()
			. ', '
			. '(CURDATE() >= `i`.activation_date AND CURDATE() <= `i`.expiration_date AND `i`.status = "ACTIVE") AS active, '
			. '`i`.status AS `status` '
			. self::_commonCourseQueryTables()
			. ';';
	}
	
	protected function _commonCourseQuerySelect()
	{
		return 'SELECT `i`.course_instance_id AS ci, `d`.abbreviation AS department_code , `c`.course_number AS number, `a`.section AS `section`, '
			. '`a`.course_alias_id AS ca, `c`.course_id AS course, '
			. '`c`.department_id AS department, `c`.uniform_title AS uniform_title, '
			. '`a`.course_name AS name, `a`.registrar_key AS registrar_key, '
			. '`i`.primary_course_alias_id AS primary_alias, `i`.activation_date AS activation_date, `i`.expiration_date AS expiration_date, `i`.`enrollment` AS `enrollment`, '
			. '('
			. '  SELECT GROUP_CONCAT( `u`.last_name SEPARATOR ", ") AS name_list '
			. '  FROM access AS `x` '
			. '  JOIN users AS `u` ON `u`.user_id = `x`.user_id '
			. '  WHERE `x`.permission_level = 3 AND `x`.alias_id = `a`.course_alias_id '
			. ') AS instructor_last_names ';
	}
	
	protected function _commonCourseQueryTables()
	{
		return 'FROM{{behead}} course_instances AS `i` '
			. 'JOIN course_aliases AS `a` ON `a`.course_instance_id = `i`.course_instance_id '
			. 'JOIN courses AS `c` ON `a`.course_id = `c`.course_id '
			. 'JOIN departments AS `d` ON `d`.department_id = `c`.department_id '
			. 'JOIN terms AS `t` ON `t`.term_id = {{param}} AND `t`.term_name = `i`.term AND `t`.term_year = `i`.year ';
	}
	
	protected static function _activeCourseQuery() 
	{
		return self::_commonCourseQuerySelect()
			. self::_commonCourseQueryTables()
			. 'WHERE `i`.status = "ACTIVE";';
	}
}
