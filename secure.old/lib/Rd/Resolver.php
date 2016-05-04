<?php
/*******************************************************************************
Rd/Resolver.php
Processes the Server Request to find routing information

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
 * Utility class to analyze the URI request and parse it into common components
 * @author jthurtea
 *
 */
class Rd_Resolver {
	
	protected static $_basePath = null;
	protected static $_controller = '';
	protected static $_resource = '';
	protected static $_action = '';
	protected static $_parameters = '';
	protected static $_additional = array();
	
	protected static $_requestUri = '';
	
	protected static $_resolved = false;
	
	const RESOLVER_QUERY_DELIM = '?';
	const RESOLVER_PATH_DELIM = '/';
	const RESOLVER_DEALIAS_STRING = '.php';
	const RESOLVER_DEFAULT_CONTROLLER = '';
	
	public static function setBasePath($path){
		self::$_basePath = $path;
	}
	
	public static function multiViewsEnabled(){
		return(false === strpos(
			(
				false === strpos($_SERVER['REQUEST_URI'], self::RESOLVER_QUERY_DELIM)
				? $_SERVER['REQUEST_URI']
				: substr($_SERVER['REQUEST_URI'],0,strpos($_SERVER['REQUEST_URI'], self::RESOLVER_QUERY_DELIM))
			), '.php')
		);

	}
	
	protected static function _guessBasePath(){
		$scriptPath = $_SERVER['SCRIPT_FILENAME'];
		$uri = $_SERVER['PHP_SELF'];
		$fileNameComponents = explode(self::RESOLVER_PATH_DELIM, $scriptPath);
		$fileName = $fileNameComponents[count($fileNameComponents)-1];
		if(false === strpos($uri,$fileName)){
			throw new Exception('Resolver said, "What is this, I don\'t even."');
		}
		$longBasePath = substr($uri,0,strpos($uri,$fileName));
		self::$_basePath = rtrim(ltrim($longBasePath, self::RESOLVER_PATH_DELIM), self::RESOLVER_PATH_DELIM);
	}

	public static function getRoot(){
		return 
			(
				(array_key_exists('HTTP_HTTPS', $_SERVER) && $_SERVER['HTTP_HTTPS'])
				|| (array_key_exists('HTTPS', $_SERVER) && $_SERVER['HTTPS'])
				? 'https'
				: 'http'
			). ':' . self::RESOLVER_PATH_DELIM . self::RESOLVER_PATH_DELIM
			. $_SERVER['HTTP_HOST'] 
			. (
				$_SERVER['SERVER_PORT'] != 80 
				? ":{$_SERVER['SERVER_PORT']}"
				: ''
			) . self::RESOLVER_PATH_DELIM;
	}
	
	public static function getBasePath(){
		return self::$_basePath;
	}
	
	public static function getController(){
		return self::$_controller;
	}
	
	public static function getResource(){
		return self::$_resource;
	}
	
	public static function getAction(){
		return self::$_action;
	}
	
	public static function getParameters(){
		return self::$_parameters;
	}
	
	public static function getAdditional(){
		return self::$_additional;
	}
	
	public static function getUri(){
		if(!self::$_resolved){
			self::resolve();
		}
		return self::$_requestUri;
	}
	
	public static function resolve($uri=null)
	{
		$uri =(
			is_null($uri)
			? str_replace(self::RESOLVER_DEALIAS_STRING, '', $_SERVER['REQUEST_URI'])
			: str_replace(self::RESOLVER_DEALIAS_STRING, '', $uri)
		);		
		
		$queryComponents = explode(self::RESOLVER_QUERY_DELIM, $uri);
		$requestComponents = explode(self::RESOLVER_PATH_DELIM, $queryComponents[0]);

		if ('' != $requestComponents[count($requestComponents) - 1]) {
			$requestComponents[count($requestComponents)] = '';
		}
		self::$_requestUri = implode(self::RESOLVER_PATH_DELIM, $requestComponents) . (
			array_key_exists(1, $queryComponents) && '' != trim($queryComponents[1])
			? self::RESOLVER_PATH_DELIM . $queryComponents[1]
			: ''	
		);
		
		self::$_resolved = true;
		
		$controllerIndex = -1;
		if (is_null(self::$_basePath)) {
			self::_guessBasePath();
		}

		if ('' === self::$_basePath ) {
			$controllerIndex = 1;
		} else {
			foreach ($requestComponents as $index=>$component) {
				if (self::$_basePath == trim(strtolower($component))) {
					$controllerIndex = $index + 1;
				}
			}
		}
		if (-1 == $controllerIndex) {
			throw new Exception('Unsupported URI Request');
		} else {
			self::$_controller = (
				array_key_exists($controllerIndex, $requestComponents)
					&& '' != trim(strtolower($requestComponents[$controllerIndex]))
				? trim(strtolower($requestComponents[$controllerIndex])) 
				: self::RESOLVER_DEFAULT_CONTROLLER
			);
			self::$_resource = (
				array_key_exists($controllerIndex + 1, $requestComponents)
				? trim(strtolower($requestComponents[$controllerIndex + 1])) 
				:''
			);
			self::$_action = (
				array_key_exists($controllerIndex + 2, $requestComponents)
				? trim(strtolower($requestComponents[$controllerIndex + 2])) 
				: ''
			);
			self::$_parameters = (
				array_key_exists($controllerIndex + 3, $requestComponents)
				? trim($requestComponents[$controllerIndex + 3]) 
				: ''
			);
			self::$_additional = (
				array_key_exists($controllerIndex + 4, $requestComponents)
				? array_slice($requestComponents, $controllerIndex + 4)
				: array()
			);
		}
	}
	
	public static function getBaseResourceUri()
	{
		return self::getRoot() 
			. self::getBasePath() 
			. '/' . self::getController() 
			. ( self::multiViewsEnabled() ? '' : '.php')
			. '/' . self::getResource()
			. '/';
	}
} 
