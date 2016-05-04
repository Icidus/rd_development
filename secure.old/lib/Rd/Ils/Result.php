<?php
/*******************************************************************************
Rd/Ils/Result.php

Created by Emory University
Modified by NCSU Libraries, NC State University. Modifications by Karl Doerr (libraries.opensource@ncsu.edu).

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
 * @category   RD
 * @package    RD_Ils
 * @copyright  
 * @license    
 */
require_once(APPLICATION_PATH . '/lib/Rd/Ils/AbstractResult.php');

class Rd_Ils_Result extends AbstractResult
{    
	/**
    * Parse xml data and return array
    * NOTE:  this does not parse all fields only those currently used.
    * Add additional case matches to parse addtional fields
    *
    * @return Array
    */    
	public function to_a()
	{
		$search_results = array('title'=>'', 'author'=>'', 'edition'=>'', 'performer'=>'', 'times_pages'=>'', 'volume_title'=>'', 'source'=>'', 'controlKey'=>'', 'personal_owner'=>null, 'physicalCopy'=>'', 'OCLC'=>'', 'ISSN'=>'', 'ISBN'=>'', 'holdings' => array());
		$sXML = simplexml_load_string($this->getData());

		//if (is_array($sXML->record->field) && !empty($sXML->record->field))
		if (!empty($sXML->record->field))
		{
			foreach ($sXML->record->field as $field) {
			   switch ($field[@type]) //#TODO this is bad.
			   {
					case '001':  // control Number
			   			$search_results['controlKey'] = (string)trim($field);
			   			
			   			//also save this as OCLC w/o the letters
			   			$search_results['OCLC'] = preg_replace('/ocm/', '', (string) $field);		//strip off 'ocm' if it exists
			   			$search_results['OCLC'] = preg_replace('/o/', '', $search_results['OCLC']);	//failing that, strip off 'o'
			   		break;
			   		
					case '020':	// ISBN
						foreach($field->subfield as $subfield) {
							if((string)$subfield['type']=='a') {	//isbn = subfield type "a"
								$search_results['ISBN'] = (string)$subfield;
							}
						}
					break;
					
					case '022':	// ISSN
						foreach($field->subfield as $subfield) {
							if((string)$subfield['type']=='a') {	//issn = subfield type "a"
								$search_results['ISSN'] = (string)$subfield;
							}
						}
					break;

			   		case '100':
			   		case '110':
			   		case '111':
			   			foreach ($field->subfield as $subfield)
			   				$search_results['author'] .= (string)$subfield;

			   		case '245': //Title
			   			$search_results['title'] = "";
			   			foreach ($field->subfield as $subfield)
			   			{
			   					if($search_results['title'] == "")
			   						$search_results['title'] = (string)$subfield;
			   					else
			   						$search_results['title'] .= " ".(string)$subfield;
			   			}
			   		break;

			   		case '260':
			   			$search_results['source'] = "";
			   			foreach ($field->subfield as $subfield)
			   			{
			   					if($search_results['source'] == "")
			   						$search_results['source'] = (string)$subfield;
			   					else
			   						$search_results['source'] .= " ".(string)$subfield;
			   			}
			   		break;
			   		
			   		case '926':
			   			$tmpResult = array();
			   			foreach ($field->subfield as $subfield)
			   			{
			   				switch ($subfield['type'])
			   				{
			   					case 'a':
			   						$tmpResult['loc'] = (string)$subfield;
			   					break;
			   					case 'b':
			   						$tmpResult['status'] = (string)$subfield;
			   					break;			   						
			   					case 'c':
			   						$tmpResult['callNum'] = (string)$subfield;
			   					break;			   			
			   					case 'd':
			   						$tmpResult['type'] = (string)$subfield;
			   					break;			   					   							   					
			   				}
			   			}
			   			$search_results['holdings'][] = $tmpResult;
			   			unset($tmpResult);
			   		break;
				}
			}
		}
		return $search_results;
	}    
}
