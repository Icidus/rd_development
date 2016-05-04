<?php
/*******************************************************************************
Rd_Ils_SirsiResult
Translates results of a Sirsi search query.

Created by Karl Doerr, NCSU Libraries, NC State University (libraries.opensource@ncsu.edu).
Modified by Troy Hurteau, NCSU Libraries

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
require_once(APPLICATION_PATH . '/lib/Rd/Ils/AbstractResult.php');

class Rd_Smith_Result_Barcode extends AbstractResult
{    	

	protected $_parsedData = null;
	
	/**
    * Parse xml data and return array
    * NOTE:  this does not parse all fields only those currently used.
    * Add additional case matches to parse addtional fields
    *
    * @return Array
    */    
	public function to_a()
	{
		$search_results = array(
			'title'=>'', 
			'author'=>'', 
			'edition'=>'', 
			'performer'=>'', 
			'times_pages'=>'', 
			'volume_title'=>'', 
			'source'=>'', 
			'controlKey'=>'', 
			'personal_owner'=>null, 
			'physicalCopy'=>'', 
			'OCLC'=>'', 
			'ISSN'=>'', 
			'ISBN'=>'', 
			'holdings' => array(),
			'url' => '',
			'success' => false
		);
		$sXML = simplexml_load_string($this->getData());
		print "<pre>";
		print_r($sXML);
		print "</pre>";

		//if (is_array($sXML->record->field) && !empty($sXML->record->field))
		if (!empty($sXML->record))
		{
			foreach ($sXML->record->metadata->oai_marc->fixfield as $fixed) {

				switch ($fixed['id'])
				{
						case '001':
			   					$search_results['controlKey'] = (string)$fixed[0];
						break;
				}

			}
		
			foreach ($sXML->record->metadata->oai_marc->varfield as $field) {

			   switch ($field['id'])
			   {
					case '001':
			   		break;
			   		
					case '020':	// ISBN
						foreach($field->subfield as $subfield) {
							//if((string)$subfield['type']=='a') {	//isbn = subfield type "a"
								$search_results['ISBN'] = (string)$subfield;
							//}
						}
					break;
					
					case '022':	// ISSN
						foreach($field->subfield as $subfield) {
							if((string)$subfield['type']=='a') {	//issn = subfield type "a"
								$search_results['ISSN'] = (string)$subfield;
							}
						}
					break;

					case '035':
			   			//save this as OCLC w/o the letters
						foreach($field->subfield as $subfield) {
							//if((string)$subfield['type']=='a') {	//OCLC # = subfield type "a"
								if (preg_match('/\(OCoLC\)/', (string) $subfield)) {	
									$search_results['OCLC'] = ltrim(preg_replace('/\(OCoLC\)/', '', (string) $subfield));	
								}
							//}
						}
			   		break;

			   		case '100':
			   		case '110':
			   		case '111':
			   			foreach ($field->subfield as $subfield)
			   				$search_results['author'] .= (string)$subfield;

			   		case '245': //Title
			   			$search_results['title'] = '';
			   			foreach ($field->subfield as $subfield)
			   			{
			   					if($search_results['title'] == "")
			   						$search_results['title'] = (string)$subfield;
			   					else
			   						$search_results['title'] .= " ".(string)$subfield;
			   			}
						
						// quotation marks in titles cannot be displayed in a text input field
						$search_results['title'] = str_replace('"', '&quot;', $search_results['title']);

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
			   		
			   		case '856':
			   			$search_results['url'] = "";
			   			foreach ($field->subfield as $subfield)
			   			{
			   					//if((string)$subfield['type']=='u') {
			   						if($search_results['url'] == "")
			   							$search_results['url'] = (string)$subfield;
			   						else if ('' != trim((string)$subfield))
			   							$search_results['url'] = (string)$subfield;
			   					//}
			   			}
			   		break;
					
			   		case 'AVA':
			   			$tmpResult = array();
			   			foreach ($field->subfield as $subfield)
			   			{
			   				switch ($subfield["label"])
			   				{
			   					case 'b':
			   						$tmpResult['library'] = (string)$subfield;
			   						
			   					break;
			   					case 'c':
			   						$tmpResult['loc'] = (string)$subfield;
			   					break;			   						
			   					case 'd':
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
			$search_results['success'] = $this->_code >= 1;
		} else {
			$search_results['success'] = false;
		}
		$this->_parsedData = $search_results;
		return $search_results;
	} 
	
	
	
	public function getTitleCount(){
		$sXML = simplexml_load_string($this->getData());
		return(!empty($sXML->recordCount) ? intval($sXML->recordCount) : 0);
	}
	
	public function getHoldingCount(){
		$this->_init();
		return array_key_exists('holdings', $this->_parsedData)
			? count($this->_parsedData['holdings'])
			: 0;
	}
	
	protected function _init(){
		if (!isset($this->_parsedData)){
			$this->to_a();
		}
	}
	
	public function getTitle(){
		$this->_init();
		return $this->_parsedData['title'];
	}
	
	public function getHoldings(){
		$this->_init();
		return 
			array_key_exists('holdings', $this->_parsedData)
			? $this->_parsedData['holdings']
			: array();
	}
	
	public function __toArray(){
		return $this->to_a();
	}
	
}