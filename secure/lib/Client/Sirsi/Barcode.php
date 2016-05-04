<?php
/*******************************************************************************
NcsuLib/Client/Sirsi/Barcode.php
Sirsi service for looking up catkey by barcode

Created by Troy Hurteau, 
NCSU Libraries, NC State University (libraries.opensource@ncsu.edu).

Copyright (c) 2012 North Carolina State University, Raleigh, NC.

###LICENSE###

*******************************************************************************/

/**
 * 
 * Client for the selitem server service running on Sirsi
 * @author jthurtea
 *
 */

class NcsuLib_Client_Sirsi_Barcode{

	protected static $_selItemUrl = 'http://sirsi.lib.ncsu.edu/cgi-bin/selitem.pl';
	protected static $_selItemPort = '';
	
	static function getCatkey($barcode){
		$url = self::$_selItemUrl . "?barcode=" . urlencode($barcode);
		$curl = curl_init($url);
		curl_setopt_array($curl, array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CONNECTTIMEOUT => 2,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_MAXREDIRS => 5,
			
		));
		curl_setopt($curl,CURLOPT_CONNECTTIMEOUT,2);
		$result = curl_exec($curl);
		if (!$result) {
			$status = curl_errno($curl);
			throw new Exception("CURL[{$status}] SelItem Server Error.", $status);
		}
		$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close($curl);
		if ( $status == 200 ) {
			return $result;
		} else if ($status == 404) {
			return false;
		} else if ($status == 401) {
			throw new Exception("Invalid Barcode: $barcode");
		}  else {
			throw new Exception("HTTP[{$status}] SelItem Server returned an unexpected status code.", $status);
		} 
	}
}