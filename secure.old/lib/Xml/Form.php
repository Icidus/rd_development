<?php
/*******************************************************************************
Ils.php
Initalizes proper ILS object based on config setting

Created by Emory University

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
class Xml_Form
{

	/**
	 * Recursive function to output data nodes in a simpleXML object as form inputs
	 *
	 * @param simpleXMLElement $sxmlObj
	 * @param string $path xpath
	 */
	public static function generateFields($xmlObj, $path='/')
	{
		//propogate path for xpath
		$path .= '/'.$xmlObj->getName();
		$output = '';
		
		if(count($xmlObj->children()) > 0) {
			$output .= '<fieldset>';
			$output .= '<legend>' . $xmlObj->getName() . "</legend>\n";
			if(!is_null($xmlObj['comment']) && '' != trim($xmlObj['comment'])) {
				$output .= '<span class="small"><i>' . htmlentities($xmlObj['comment']) . '</i></span>';
			}
			foreach($xmlObj->children() as $child) {
				$output .= self::generateFields($child, $path);
			}
			$output .= "</fieldset>\n";
		} else {
			$output .= '<label>' . $xmlObj->getName() . ': ';
			if(strlen($xmlObj) > 100) {
				$output .= '<textarea rows="6" cols="75" name="xml[' . $path . ']">' . htmlentities($xmlObj) . '</textarea>';
			} else {
				$output .= '<input type="text" size="40" name="xml[' . $path . ']" value="'.htmlentities($xmlObj).'" />';
			}
			$output .= '</label>';
			if (!is_null($xmlObj['comment']) && '' != trim($xmlObj['comment'])) {
				$output .= ' <span class="small"><i>' . htmlentities($xmlObj['comment']) . '</i></span>';
			}
		}
		return $output;
	}
}