<?php
/*******************************************************************************
Rd/Email.php
Implements a Email utilities for RD

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
 * Email utility object.
 * @author jthurtea
 *
 */

class Rd_Email{
	
	protected static $_matches = array();
	protected static $_initialized = false;
	
	protected static function _init()
	{
		if (!self::$_initialized) {
			self::$_matches['instanceName'] = Rd_Registry::get('root:instanceName');
			self::$_matches['institutionName'] = Rd_Registry::get('root:institutionName');
			self::$_matches['supportEmail'] = Rd_Registry::get('root:supportEmail');
			self::$_initialized = true;
		}
	}
	
	public static function template($templateName, $params)
	{
		self::_init();
		$matches = self::$_matches;
		foreach($params as $key=>$value) {
			switch ($key) {
				case 'user':
					$matches['userUsername'] = $value->getUsername();
					$matches['userEmail'] = $value->getEmail();
					break;
				default:
					$matches[$key] = $value;
			}
		}
		$templateXml = file_get_contents(APPLICATION_PATH . '/configs/templates/email/'.$templateName . '.xml');	
		if (!$templateXml){
			if (Rd_Debug::isEnabled()) {
				Rd_Debug::out('Unable to load email template XML for ' . $templateName);
			}
			return false;
		} else {
			$template = new DOMDocument();
			$template->loadXML($templateXml);
			$toTags = $template->getElementsByTagName('to');
			$fromTags = $template->getElementsByTagName('from');
			$subjectTags = $template->getElementsByTagName('subject');
			$bodyTags = $template->getElementsByTagName('body');
			if(0 == $toTags->length){
				throw new Exception('Email template has no "to" field specified.');
			}
			if(0 == $fromTags->length){
				throw new Exception('Email template has no "from" field specified.');
			}
			if(0 == $subjectTags->length){
				throw new Exception('Email template has no "subject" field specified.');
			}
			if(0 == $bodyTags->length){
				throw new Exception('Email template has no "body" field specified.');
			}
			$to = self::translate($toTags->item(0)->nodeValue, $matches);
			$from = self::translate($fromTags->item(0)->nodeValue, $matches);
			$subject = self::translate($subjectTags->item(0)->nodeValue, $matches);
			$body = self::translate($bodyTags->item(0)->nodeValue, $matches);
			$headers = "From: {$from}\r\n"
				. "Reply-To: {$from}\r\n"
				. "X-Mailer: PHP/" . phpversion();
			mail($to, $subject, $body, $headers);
		}
	}	
	
	public static function translate($message, $matches) {
		foreach ($matches as $match=>$replace){
			$message = str_replace("{{{$match}}}", $replace, $message);
		}
		return $message;
	}
}