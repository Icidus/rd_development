<?php 
/*******************************************************************************
Client/CourseTools.php
NCSU Libraries' Course Tools integration service

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
 * Client for NCSU Libraries' Course Tools
 * @author jthurtea
 *
 */

class Client_CourseTools{

	protected static $_initialized = false;
	protected static $_serviceUrl = '';
	
	public static function init()
	{
		if(!self::$_initialized){
			//$serviceConfig = Rd_Config::get('video:encoderService:+');
			try {
				$config = Rd_Registry::get('root:courseTools');
			} catch (Exception $r) {
				$config = NULL;
			}
			self::$_serviceUrl = (
				is_array($config) && array_key_exists('url', $config)
				? $config['url']
				: ''
			);
			self::$_initialized = true;
		}
	}
	
	public static function getCourseWidget($courseInstance)
	{
		self::init();
		$url = self::$_serviceUrl . "/{$courseInstance->course->department->getAbbr()}/{$courseInstance->course->getCourseNo()}";
		$result = '<div class="widgetError">Failed to contact the Course Tools Service.</div>';
		$curl = curl_init();		
		$options = array(
			CURLOPT_URL => $url,
			CURLOPT_HEADER => false,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CONNECTTIMEOUT => 2
		);
		curl_setopt_array($curl, $options);
		try {
			$result = curl_exec($curl);
			if ('' != trim(curl_error($curl))) {
				Rd_Debug::out(curl_error($curl));
			}
			$resultInfo = curl_getinfo($curl);
			if(200 != $resultInfo['http_code'] || 0 == $resultInfo['download_content_length']) {
				$result = '<div class="widgetError">The Course Tools Service is currently unavailable.</div>';
				if(Rd_Debug::isEnabled()){
					print('<p>The Course Tools service gave an bad response:</p><pre>');
					print("RESULT INFO: \n");
					print_r($resultInfo);
					print("\nRESULT BODY: \n");
					print(htmlentities($result));
					print('</pre>');
				}
			}
		} catch (Exception $e){
			if(Rd_Debug::isEnabled()) {
				print_r($e);
			}
			$result = '<div class="widgetError">The Course Tools Service is currently unavailable.</div>';
		}
		curl_close($curl);
		
		print(self::_fixer("<!-- result of widget request to {$url} -->" . $result));			
	}
	
	protected static function _fixer($output)
	{
		return str_replace('seriv','serif',$output);
	}
}