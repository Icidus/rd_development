<?php
/*******************************************************************************
Rd/Displayer/Helper/Html.php
Displayer helper utility for generating html snippets

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
 * Displayer helper for generating html
 */
abstract class Rd_Displayer_Helper_Html {
	
	protected static $_nextUniqueIdInt = 1;
	
	protected static function _getNextUniqueId(){
		return 'autoId' . (self::$_nextUniqueIdInt++);
	}
	
	public static function disabledIf($test){
		return (
			$test
			? ' disabled="disabled" '
			: ''
		);
	}
	
	public static function checkedIf($test){
		return (
			$test
			? ' checked="checked" '
			: ''
		);
	}
	
	public static function valueIf($test, $value){
		return (
			$test
			? " value=\"{$value}\" "
			: ''
		);
	}
	
	public static function attributeIf($test, $attribute, $value){
		return (
			$test
			? " {$attribute}=\"{$value}\" "
			: ''
		);
	}
	
	public static function formSelect($properties){
		$name = array_key_exists('name',$properties)//#TODO the array utilitiy does this...
			? $properties['name']
			: self::_getNextUniqueId();
		$values = array_key_exists('values', $properties)
			? $properties['values']
			: array();
?>
		<select name="<?php print(htmlentities($name)); ?>" id="<?php print(htmlentities($name)); ?>">
<?php  
		if (array_key_exists('emptyOption',$properties)) {
?>
			<option value=""><?php print(htmlentities($properties['emptyOption'])); ?></option>
<?php 	}
		foreach($values as $valuePair) {
			$optionValue = array_shift($valuePair);
			$optionName = array_shift($valuePair);			
?>
			<option value="<?php print(htmlentities($optionValue)); ?>"><?php print(htmlentities($optionName)); ?></option>
<?php 	}
?>
		</select>
<?php 		
	}
}