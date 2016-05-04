<?php
/*******************************************************************************
Rd_Ils_Sirsi
Implementation of NCSU's Ils Sirsi

Created by Karl Doerr and Troy Hurteau, NCSU Libraries, NC State University (libraries.opensource@ncsu.edu).

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
require_once(APPLICATION_PATH . '/lib/Rd/Ils/Abstract.php');
require_once(APPLICATION_PATH . '/lib/Rd/Ils/class_xml_check.php');
require_once(APPLICATION_PATH . '/lib/Rd/Ils/SmithResult.php');
require_once(APPLICATION_PATH . '/lib/Rd/Ils/SmithResultBarcode.php');

class Rd_Ils_Smith extends Rd_Ils_Abstract
{
	protected $create_reserve_script	= '';
	protected $holdings_script			= '';

	# if the following is set, it will be used in preference
	# to the sel_item_server congifuration under z* properties

	protected $sel_item_url				= '';

	protected $zReflector				= '';
	protected $zHost					= '';
	protected $zPort					= '';
	protected $zselItemServerPort		= '';
	protected $zDB						= '';

	public function __construct(){

		global $g_reserveScript, $g_holdingsScript, $g_zReflector, $g_zReflector, $g_zhost, $g_zport, $g_zcisport, $g_zdb, $g_ciurl;

		$this->create_reserve_script = $g_reserveScript;
		$this->sel_item_url = $g_ciurl;
		$this->holdings_script = $g_holdingsScript;
		$this->zReflector = $g_zReflector;
		$this->zHost = $g_zhost;
		$this->zPort = $g_zport;
		$this->zselItemServerPort = $g_zcisport;
		$this->zDB = $g_zdb;
	}

	protected function setILSName()
	{
		$this->_ilsName = 'Aleph';
	}
	
	public function getName(){
		return $this->_ilsName;
	} 

	
	protected function setReservableFormats()
	{
		$this->_reservable_formats = array('MONOGRAPH', 'MULTIMEDIA', 'ELECTRONIC');
	}
	
	public function createReserve(Array $form_vars, Reserve $reserve)
	{
		$barcode 				 = $form_vars['barcode'];
		$copy 					 = $form_vars['copy'];
		$libraryID 				 = $form_vars['libraryID'];
		$circ					 = $form_vars['circ'];
		list($circRule,$altCirc) = explode('|', $circ);		
		$borrower_user_id		 = $form_vars['borrower_user_id'];
		$term					 = $form_vars['term'];	
		
		$expiration				 = $reserve->expiration;
				
		$lo = new LibraryObject();
		$reservesDesk = $lo->find($libraryID);
		
		Rd_Debug::out('IS THIS CODE EVEN GETTING CALLED? 001'); //TODO Left intentionally.
		try {
			$io = new InstructorObject();
			$borrower = $io->find($borrower_user_id);		
			$borrower->getInstructorAttributes();
			$borrowerID = $borrower->instructor_attributes->ils_user_id;
		} catch (Exception $e) {
			return new RD_Sirsi_Result(RD_Sirsi_Result::FAILURE_IDENTITY_NOT_FOUND, 'Borrower Not Found');
		}
			
		$desk=$reservesDesk->ils_prefix;
		$course = strtoupper($reservesDesk->ils_prefix . $term);

		list($Y,$M,$D) = explode('-', $expiration);
		$eDate = "{$M}/{$D}/{$Y}";
	    Rd_Debug::out($this->create_reserve_script . "?itemID={$barcode}&borrowerID={$borrowerID}&courseID={$course}&reserve_desk={$desk}&circ_rule={$circRule}&alt_circ={$altCirc}&expiration={$eDate}&cpy={$copy}<BR>");

		$fp = fopen($this->create_reserve_script . "?itemID=$barcode&borrowerID=$borrowerID&courseID=$course&reserve_desk=$desk&circ_rule=$circRule&alt_circ=$altCirc&expiration=$eDate&cpy=$copy", "r");

        $rs = array();
        while (!feof ($fp)) {
        	array_push($rs, @fgets($fp, 1024));
        }
        $returnStatus = join($rs, "");

        $returnStatus = pregi_replace('/<head>.*<\/head>/', '', $returnStatus);
        $returnStatus = preg_replace('/<[A-z]*>/', '', $returnStatus);
        $returnStatus = preg_replace('/<\/[A-z]*>/', '', $returnStatus);

        $returnStatus = preg_replace('/<!.*\">/', '', $returnStatus);
        $returnStatus = preg_replace("/\n/", '', $returnStatus);

        if(!preg_match('/outcome=OK/i', $returnStatus))
        {
        	return new RD_Sirsi_Result(RD_Sirsi_Result::FAILURE_UNCATEGORIZED, "There was a problem setting the location and circ-rule in {$this->_ilsName}. <BR>{$this->_ilsName} returned:  {$returnStatus}.");
        } else {
        	return new RD_Sirsi_Result(RD_Sirsi_Result::SUCCESS, "Location and circ-rule have been successfully set in {$this->_ilsName}");
		}
	}
	
	public function displayReserveForm()
	{
		return $this->_view->render("_EUCLID_reserve_form.phtml");
	}
	
	
	public function getHoldings($key, $keyType = 'barcode')
	{
		
		if(empty($key) || empty($keyType)) {
			return array();
		}

		$rs = array();
		
		$key = preg_replace('/ocm/','o',$key);
		
		Rd_Debug::out("Holding Script at: {$this->holdings_script}?key={$key}&key_type={$keyType}<br />");
		
		$q_url = "http://fcaa.library.umass.edu:1891/rest-dlf/record/FCL01" . $key . "/holdings?view=full";
		
		
		$fp = fopen($q_url, 'rb'); //#TODO Curl this bad boy
		if(!$fp) {
			throw new RD_Ils_Exception("Could not get holdings");
		}
		while (!feof ($fp)) {
			array_push($rs, @fgets($fp, 1024));
		}
		$returnStatus = join($rs, "");
		Rd_Debug::out("return status: " . $returnStatus);
		if(preg_match("/ok0000/", $returnStatus))
		{
			list($devnull, $holdings) = explode("ok0000", $returnStatus);
			

			$thisCopies = explode("\n", $holdings);


			$j = 0;
			for($i = 0; $i < (count($thisCopies) - 1); $i++)
			{
				if (strpos($thisCopies[$i], '|') !== false) {				
					list($devnull, $devnull, $copy, $callnum, $loc, $type, $bar, $library, $status, $reservesDesk) = explode('|', $thisCopies[$i]);
					if ('' != $copy && '' != $callnum)
					{
						$tmpArray[$j]['copy']		= trim($copy);
						$tmpArray[$j]['callNum']	= trim($callnum);
						$tmpArray[$j]['loc']		= trim($loc);
						$tmpArray[$j]['type']		= trim($type);
						$tmpArray[$j]['bar']		= trim($bar);
						$tmpArray[$j]['library']	= trim($library);
						$j++;
					}
				}
 			}

			return $tmpArray;
		} else return array();
	}
	
	public function barcodeLookup($barcode){
		//$url = $this->sel_item_url . "?barcode=" . urlencode($barcode);
		$url = "http://fcaa.library.umass.edu/X?op=read-item&library=SMT50&item_barcode=" . urlencode($barcode);

		$curl = curl_init($url);
		
		curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
		curl_setopt($curl,CURLOPT_CONNECTTIMEOUT,true);
		$result = curl_exec($curl);
		$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close($curl);
		if ( $status == 200 ) {
			return array('local_control_key' => $result, 'status' => 'success');
		} else if ($status == 404) {
			return array('local_control_key' => '','status' => 'not found');
		} else {
			return array('local_control_key' => '','status' => 'error');
		} 
	}
		
	public function search($search_field, $search_term)
	{
		//use barcode to get controlNumber
		if ($search_field == 'barcode')	{
			//if ( $this->sel_item_url ) {
				// Fetch catkey using web service
				// FIXME : there should probably be a curl wrapper of some sort
				$url = "http://fcaa.library.umass.edu/X?op=read-item&library=SMT50&item_barcode=" . urlencode($search_term);
				$curl = curl_init($url);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
				curl_setopt($curl,CURLOPT_CONNECTTIMEOUT,true);
				$result = curl_exec($curl);
				$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
				curl_close($curl);
				if  ( $status == 200 ) {
					$bXML = simplexml_load_string($result);
					$doc_id = $bXML->z30->{'z30-doc-number'};
					$smtSearch = "http://fcaa.library.umass.edu/X?op=find_doc&base=smt50&doc-number=" . $doc_id;
		
					$curl = curl_init($smtSearch);
					curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
					curl_setopt($curl,CURLOPT_CONNECTTIMEOUT,true);
					$result = curl_exec($curl);
					$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
					curl_close($curl);
						if  ( $status == 200 ) {
							$smtXML = simplexml_load_string($result);
								foreach ($smtXML->record->metadata->oai_marc->varfield as $field) 
								{
								switch ($field['id']) 
								{
									case 'LKR' :
									$search_results['doc'] = "";
										foreach ($field->subfield as $subfield)
										{
											$term = str_replace('ADMFCL01', '', $subfield);
										}
									break;
								 }	
								 }
							}		
					//$term = trim($result);
					//Rd_Debug::out("Setting term to $term<br />");
				} else if ($status == 404) {
						return new RD_Smith_Result(RD_Smith_Result::FAILURE_IDENTITY_NOT_FOUND, $result, null);
				} else {
					Rd_Debug::out("WS Status [$status] : Message: $result");
					return new RD_Smith_Result(RD_Smith_Result::FAILURE_IDENTITY_NOT_FOUND, "Status Code from SelItem Service: $status =&gt; $result", null);
				} 
			//} else {
			//	  throw new RD_Ils_Exception("Attempted use of deprecated feature: SEL_ITEM_SERVER, please use SEL_ITEM_WS instead.");
			//}
		} else {
			$term = $search_term;
		}

		try {
			$this->xmlResults = $this->DoQuery($term);
			return new RD_Smith_Result(RD_Smith_Result::SUCCESS, "", $this->xmlResults);
		} catch (Exception $e) {
			$this->xmlResults = null;
			return new RD_Smith_Result(RD_Smith_Result::FAILURE, $e->getMessage(), null);
		}
	}
	
	public function supportsReserveRecords(){
		return true;
	}
	
	private function DoQuery($query)
	{
		/*
		// Executes a z39.50 search
		// Get back our results in MARC format
		yaz_syntax($zConn, "xml");
		// We only want 10 records at a time -- "$start" is the record number we want to start from
		yaz_range($zConn, $start, 10);
		// Throw in some default attributes -- (4 (Structure) = 1 (Phrase), 3 (Position) = 3 (any position), 5 (Truncate) = 1 (Right Truncate)
		yaz_search($zConn,"rpn", $query);
		// yaz_wait actually executes the query
		yaz_wait();
		*/

		$xmlresults = "";
		$qry_url = "http://fcaa.library.umass.edu/X?op=find-doc&doc_num=" . urlencode($query) . "&base=FCL01";
		
		if (isset($_SESSION) && array_key_exists('debug', $_SESSION) && $_SESSION['debug']){
			print("Query Url: {$qry_url} <br />");
		}		
		$fp = fopen($qry_url, 'r');
		if(!$fp) {
			throw new RD_Ils_Exception("Could not open {$qry_url}");
		}
		while(!feof($fp)) {
			$xmlresults.= fread($fp,1024);
		}
		fclose($fp);
		if (isset($_SESSION) && array_key_exists('debug', $_SESSION) && $_SESSION['debug']){
			print("Query Result: {$xmlresults} <br />");
		}
//		$check = new XML_check();
//		if (!$check->check_string($xmlresults))
//		{
//			throw new RD_Ils_Exception($check->get_full_error());
//		}
				
		return $xmlresults;
	}
}

