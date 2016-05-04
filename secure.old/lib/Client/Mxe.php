<?php
/*******************************************************************************
Client/Mxe.php
Cisco Mxe integration service

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
 * Client for the Cisco MXE
 * @author jthurtea
 *
 */

class Client_Mxe{

	protected static $_initialized = false;
	protected static $_serviceUrl = '';
	protected static $_serviceUser = '';
	protected static $_servicePassword = '';
	protected static $_serviceAuthenticate = false;
	protected static $_lastStatus = '';
	protected static $_lastError = '';
	
	public static function init()
	{
		if(!self::$_initialized){
			//$serviceConfig = Rd_Config::get('video:encoderService:+');
			self::$_serviceUrl = Rd_Config::get('video:encoderService:url');
			try{
				self::$_serviceAuthenticate = 'true' == Rd_Config::get('video:encoderService:authenticate');
				self::$_serviceUser = Rd_Config::get('video:encoderService:username');
				self::$_servicePassword = Rd_Config::get('video:encoderService:password');
			} catch (Exception $e){
				if(self::$_serviceAuthenticate && '' == trim(self::$_serviceUser)){
					throw new Rd_Exception(1304);
				}
			}
				
			self::$_initialized = true;
		}
	}
	
	public static function getStatus()
	{
		self::init();
		$status = 'unknown';		
		$curl = curl_init();
		$options = array(
			CURLOPT_URL => self::$_serviceUrl.'settings/',
			CURLOPT_HEADER => false,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CONNECTTIMEOUT => 1
		);
		curl_setopt_array($curl, $options);
		try {
			$result = curl_exec($curl);
			self::$_lastStatus = $result['status'];
			self::$_lastError = curl_error($curl);
			Rd_Debug::out(curl_error($curl));
			$resultInfo = curl_getinfo($curl);
			if(200 != $resultInfo['http_code'] || 0 == $resultInfo['download_content_length']) {
				$status = 'badresponse';
					if(Rd_Debug::isEnabled()){
						print('<p>The MXE gave an bad response:</p><pre>');
						print("RESULT INFO: \n");
						print_r($resultInfo);
						print("\nRESULT BODY: \n");
						print(htmlentities($result));
						print('</pre>');
					}
			} else {
				$domDoc = new DOMDocument();
				$resultDocStatus = $domDoc->loadXML($result);
				if($resultDocStatus){
					$xpath = new DOMXPath($domDoc);
					$resultNodes = $xpath->query('/settingList/setting');
					if($resultNodes->length > 0) {
						$status = 'ready';
					} else {
						$status = 'oddresponse';
						if(Rd_Debug::isEnabled()){
							print('<p>The MXE gave an unexpected response:</p><pre>');
							print(htmlentities($domDoc->saveXML()));
							print('</pre>');
						}
					}
				} else {
					$status = 'illformedresponse';
					if(Rd_Debug::isEnabled()){
						print('<p>The MXE gave an illformed response:</p><pre>');
						print(htmlentities($result));
						print('</pre>');
					}
				}
			}
		} catch (Exception $e){
			if(Rd_Debug::isEnabled()) {
				print_r($e);
			}
		}
		curl_close($curl);
		
		return $status;			
		
	}
	
	public static function getError()
	{
		return 'Status: ' . self::$_lastStatus . ' Error:' . self::$_lastError;
	}
	
	public static function urlUnsafe($string) //#TODO move this to a utility class...
	{
		return strpbrk($string, '?+,/:;?@ <?"#%{}|\\^~[]`') !== false;
	}
	
	public static function proxy($url, $get=array(), $post=array()){
		self::init();
		$curl = curl_init();
		
		if(is_array($get)){
			if(count($get) > 0){
				$query = '?';
				foreach($get as $key=>$value){
					$query .= ('?' == $query ? '' : '&' ) 
						. urlencode($key)
						. '='
						. urlencode($value);
				}
			} else {
				$query = '';
			}
		} else if ('' != $get) {
			$testingQuery = ltrim($get,'?');
			$query = '?' . self::urlUnsafe($testingQuery) ? urlencode($testingQuery) : $testingQuery;
		} else {
			$query = '';
		}
		
		$fullUrl = rtrim(self::$_serviceUrl . $url, '/') . $query;
		
		$options = array(
			CURLOPT_URL => $fullUrl,
			CURLOPT_HEADER => false,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CONNECTTIMEOUT => 1
		);
		
		if(is_array($post) && count($post) > 0){
			//$options[CURLOPT_POST] = true;
			$options[CURLOPT_POSTFIELDS] = $post;
		} else if ('' != $post) {
			$options[CURLOPT_POST] = true;
			$options[CURLOPT_POSTFIELDS] = $post;
			//$options[CURLOPT_HTTPHEADER] = array('Content-type: text/xml');
		}

		curl_setopt_array($curl, $options);
		try {
			$result = curl_exec($curl);
			if(Rd_Debug::isEnabled()) {
				$error = curl_error($curl);
			}
			self::$_lastError = curl_error($curl);;
			$resultInfo = curl_getinfo($curl);
		} catch (Exception $e){
			self::$_lastError = $e->getMessage();
			self::$_lastStatus = 'EXCEPTION';
			$return = array(
				'url' => $fullUrl,
				'status' => 500,
				'error' => $e->getMessage(),
				'raw' => '',
				'length' => 0,
				'type' => ''
			);
			if (Rd_Debug::isEnabled()) {
				$return['stack'] = $e->getTrace();
			}
			self::$_lastStatus = $return['status'];
			return $return;
		}
		curl_close($curl);		
		
		$return  = array(
			'url' => $fullUrl,
			'status' => $resultInfo['http_code'],
			'length' => $resultInfo['download_content_length'],
			'type' => $resultInfo['content_type'],
			'raw' => $result
		);
		
		if (isset($error)) {
			$return['error'] = $error;
		}
		
		return $return;
		
		/*
		
		return array(
			'raw' => 'hi',
			'get' => $get,
			'url' => $url,
			'post' => $post,
			'headers' => apache_request_headers()
		); */
	}
}