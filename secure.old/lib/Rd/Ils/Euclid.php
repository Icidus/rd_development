<?php
/*******************************************************************************
Rd_Ils_Euclid
Implementation of Emory University's Localized ils EUCLID

Created by Jason White (jbwhite@emory.edu)
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

*******************************************************************************
This class extends RD_Ils_Abstract

Implementing Emory University's localized ils
*******************************************************************************/
//#TODO Replace ereg calls with preg and split with explode. These are depricated.
require_once(APPLICATION_PATH . '/lib/Rd/Ils/Abstract.php');
require_once(APPLICATION_PATH . '/lib/Rd/Ils/class_xml_check.php');
require_once(APPLICATION_PATH . '/lib/Rd/Ils/EuclidResult.php');

class Rd_Ils_Euclid extends Rd_Ils_Abstract
{
	protected $create_reserve_script = "http://www.library.emory.edu/uhtbin/create_reserve";
	protected $holdings_script  	 = "http://www.library.emory.edu/uhtbin/holding_request";
	
	protected $zReflector			 = "http://sagan.library.emory.edu/cgi-bin/zGizmo.cgi";
	protected $zHost				 = "libcat1.cc.emory.edu";
	protected $zPort				 = "2501";	
	protected $zDB					 = "Unicorn";	
	
	protected function setILSName()
	{
		$this->_ilsName = 'EUCLID';
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
		list($circRule,$altCirc) = split('\|', $circ);		
		$borrower_user_id		 = $form_vars['borrower_user_id'];
		$term					 = $form_vars['term'];	
		
		$expiration				 = $reserve->expiration;
				
		$lo = new LibraryObject();
		$reservesDesk = $lo->find($libraryID);
		
		
		try {
			$io = new InstructorObject();
			$borrower = $io->find($borrower_user_id);
			
			$borrower->getInstructorAttributes();		
			$borrowerID = $borrower->instructor_attributes->ils_user_id;
		} catch (Exception $e) {
			return new RD_Euclid_Result(RD_Euclid_Result::FAILURE_IDENTITY_NOT_FOUND, "Borrower Not Found");
		}
			
		$desk = $reservesDesk->reserve_desk;
		$course = strtoupper($reservesDesk->ils_prefix . $term);

		list($Y,$M,$D) = split("-", $expiration);
		$eDate = "$M/$D/$Y";
	        if (true) { echo $this->create_reserve_script . "?itemID=$barcode&borrowerID=$borrowerID&courseID=$course&reserve_desk=$desk&circ_rule=$circRule&alt_circ=$altCirc&expiration=$eDate&cpy=$copy<BR>"; }


		$fp = fopen($this->create_reserve_script . "?itemID=$barcode&borrowerID=$borrowerID&courseID=$course&reserve_desk=$desk&circ_rule=$circRule&alt_circ=$altCirc&expiration=$eDate&cpy=$copy", "r");

        $rs = array();
        while (!feof ($fp)) {
        	array_push($rs, @fgets($fp, 1024));
        }
        $returnStatus = join($rs, "");

        $returnStatus = eregi_replace("<head>.*</head>", "", $returnStatus);
        $returnStatus = ereg_replace("<[A-z]*>", "", $returnStatus);
        $returnStatus = ereg_replace("</[A-z]*>", "", $returnStatus);

        $returnStatus = ereg_replace("<!.*\">", "", $returnStatus);
        $returnStatus = ereg_replace("\n", "", $returnStatus);

        if(!ereg("outcome=OK", $returnStatus))
        {
        	return new RD_Euclid_Result(RD_Euclid_Result::FAILURE_UNCATEGORIZED, "There was a problem setting the location and circ-rule in $this->_ilsName. <BR>$this->_ilsName returned:  $returnStatus.");
        } else {
        	return new RD_Euclid_Result(RD_Euclid_Result::SUCCESS, "Location and circ-rule have been successfully set in $this->_ilsName");
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
		
		$key = ereg_replace('oc[mn]','o',$key);
		$key = ereg_replace('DOBI','o',$key);
						
		$fp = fopen($this->holdings_script . "?key=" . $key . "&key_type=$keyType", "rb");
		if(!$fp) {
			throw new RD_Ils_Exception("Could not get holdings");
		}
		while (!feof ($fp)) {
			array_push($rs, @fgets($fp, 1024));
		}
		$returnStatus = join($rs, "");

		if(ereg("Outcome=OK\n", $returnStatus))
		{
			list($devnull, $holdings) = split("Outcome=OK\n", $returnStatus);

			$thisCopies = split("\n", $holdings);

			$j = 0;
			for($i = 0; $i < (count($thisCopies) - 1); $i++)
			{
				if (strpos($thisCopies[$i], '|') !== false) {				
					list($devnull, $devnull, $copy, $callnum, $loc, $type, $bar, $library, $status, $reservesDesk) = split("\|", $thisCopies[$i]);
					if ($copy != "" && $callnum != "")
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
		
	public function search($search_field, $search_term)
	{
		if ($search_field == 'barcode')  //use barcode to get controlNumber
		{
			//open socket to EUCLID widget which will return a controlNumber
			$fp = fsockopen($this->zHost, 4321, $errno, $errstr, 60);
			if (!$fp) {
				  throw new RD_Ils_Exception("Could not connect to {$this->zHost}:4321  $errstr ($errno)");
			} else {
				fwrite ($fp, $search_term);
				while (!feof($fp)) {
					$term =  fgets ($fp,128);
					$term = ereg_replace("[^A-z0-9]", "", $term);
				}
				fclose ($fp);
			}
		} else $term = $search_term;

		try {
			$this->xmlResults = $this->DoQuery('@attr 1=12 "' . $term . '"', 0, 1);
			return new RD_Euclid_Result(RD_Euclid_Result::SUCCESS, "", $this->xmlResults);
		} catch (Exception $e) {
			$this->xmlResults = null;
			return new RD_Euclid_Result(RD_Euclid_Result::FAILURE, $e->getMessage(), null);
		}
	}
	
	private function DoQuery($query, $start, $limit)
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
		$qry_url = "{$this->zReflector}?host={$this->zHost}&port={$this->zPort}&db={$this->zDB}&query=" . urlencode($query) . "&start=$start&limit=$limit";
		$fp = fopen($qry_url, "r");
		if(!$fp) {
			throw new RD_Ils_Exception("Could not open $qry_url");
		}
		while(!feof($fp)) {
			$xmlresults.= fread($fp,1024);
		}
		fclose($fp);

//		$check = new XML_check();
//		if (!$check->check_string($xmlresults))
//		{
//			throw new RD_Ils_Exception($check->get_full_error());
//		}
				
		return $xmlresults;
	}
}
