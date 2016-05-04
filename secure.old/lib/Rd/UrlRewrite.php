<?php
/*******************************************************************************
UrlRewrite.php
Implements a url rewriting utility

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
 * Class for altering urls
 * @author jthurtea
 *
 */

class Rd_UrlRewrite{
	
	public static function scrubGet($url, $getArray, $config)
	{
		$scrubbedUrl = 
			strpos($url, '?') !== false
			? substr($url, 0 , strpos($url, '?'))
			: $url;
		$scrubbedSearch = '?';
		foreach($getArray as $getName=>$getValue){
			if(
				(
					array_key_exists('include', $config) 
					&& (
						(is_array($config['include']) && in_array($getName, $config['include']))
						|| $getName == $config['include']
					)
				) || (
					array_key_exists('exclude', $config) 
					&& (
						(is_array($config['exclude']) && !in_array($getName, $config['exclude']))
						|| (is_string($config['exclude']) && $getName != $config['exclude'])
					)
				)
			) {
				$scrubbedSearch .= ('?' == $scrubbedSearch ? '' : '&') . urlencode($getName) . '=' . $getValue;
			}
		}
		return $scrubbedUrl . ('?' != $scrubbedSearch ? $scrubbedSearch : '');
	}
}