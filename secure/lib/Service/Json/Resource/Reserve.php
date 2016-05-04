<?php
/*******************************************************************************
Service/Json/Resource/Reserve.php
Implements a resource query object for reserve information

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
 * Class for querying RD reserve information.
 * @author jthurtea
 *
 */

class Service_Json_Resource_Reserve extends Service_Json_Resource
{

	protected $_configuration = array(
		'query' => array(
			'sql' => 'SELECT reserve_id AS `id`, course_instance_id AS ci, item_id AS `item`, activation_date, `expiration` AS expriation_date, `status`, sort_order AS display_order, date_created, last_modified, requested_loan_period AS loan_period, parent_id AS parent FROM `reserves`;',
			'label' => 'reserves',
			'partition' => true
		),
		'actions' => array(
			'course_instance' => array(
				'query' => array(
					'sqlMethod' => '_simplifiedReserveQuery',
					'label' => 'course_reserves',
					'partition' => true,
					'sort' => 'ci'
				)
			)			
		)
	);
	
	protected static function _simplifiedReserveQuery() 
	{
		return 'SELECT `r`.reserve_id AS reserve, `i`.course_instance_id AS ci, `a`.course_alias_id AS ca, `c`.course_id AS course, '
			. '`c`.department_id AS department, `c`.uniform_title AS uniform_course_title, '
			. '`a`.course_name AS course_name, `a`.section AS `section`, '
			. '`i`.primary_course_alias_id AS primary_ca, `i`.activation_date AS course_activation_date, `i`.expiration_date AS course_expiration_date, '
			. '`r`.activation_date AS reserve_activation_date, `r`.expiration AS reserve_expiration_date, `r`.status AS reserve_status, `r`.sort_order AS reserve_sort_order, `r`.date_created AS reserve_create_date, `r`.last_modified AS reserve_modified_date, `r`.requested_loan_period AS requested_loan_period, `r`.parent_id AS reserve_parent, '
			. '`ri`.`title` AS item_title, `ri`.`author` AS `author`, `ri`.`performer` AS `performer`, `ri`.`source` AS item_source, `ri`.volume_title AS item_volume_title, `ri`.volume_edition AS item_volume_edition, `ri`.pages_times AS item_pages_times, `ri`.content_notes AS item_content_nomes, `ri`.local_control_key AS cat_key, `ri`.creation_date AS item_create_date, `ri`.last_modified AS item_modified_date, `ri`.`url` AS url, `ri`.`mimetype` AS mime_type, `ri`.home_library AS item_home_library, `ri`.item_group AS item_group, `ri`.item_type AS item_type, `ri`.item_icon AS `icon`, `ri`.`ISBN` AS `ISBN`, `ri`.`ISSN` AS `ISSN`, `ri`.`OCLC` AS `OCLC`, `ri`.`status` AS item_status, '
			. '`rp`.status AS physical_copy_status, `rp`.call_number AS call_number, `rp`.barcode AS barcode, `rp`.owning_library AS owning_library, `rp`.item_type AS item_type '
			. 'FROM course_instances AS `i` '
			. 'JOIN course_aliases AS `a` ON `a`.course_instance_id = `i`.course_instance_id '
			. 'JOIN courses AS `c` ON `a`.course_id = `c`.course_id '
			. 'JOIN reserves AS `r` ON `r`.course_instance_id = `i`.course_instance_id '
			. 'JOIN items AS `ri` ON `ri`.item_id = `r`.item_id '
			. 'LEFT JOIN physical_copies AS `rp` ON `rp`.reserve_id = `r`.reserve_id '
			. 'WHERE `i`.course_instance_id = {{param}}; ';
	}
}
