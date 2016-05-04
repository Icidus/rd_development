<?php
/*******************************************************************************
Service/Json/Resource.php
Defines a base class for resource information

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

abstract class Service_Json_Resource
{
	protected $_configuration = array();
	
	public static function init(){
		
	}
	
	public function get(){
		return $this->_configuration;
	}
	
	public function isQueryable($action = ''){
		return (
			'' != $action 
			? (
				$this->hasAction($action)
				? array_key_exists('query', $this->_configuration['actions'][$action])
				: false
			) : array_key_exists('query', $this->_configuration)
		);
	}
	
	public function hasActions(){
		return array_key_exists('actions', $this->_configuration) 
			&& is_array($this->_configuration['actions']
		);
	}
	
	public function hasAction($action){
		return self::hasActions() 
			&& array_key_exists($action, $this->_configuration['actions']) 
			&& is_array($this->_configuration['actions'][$action]
		);
	}
	
	public function getActions(){
		$list = array();
		if (!$this->hasActions()) {
			return $list;
		}
		foreach($this->_configuration['actions'] as $actionName=>$actionInfo){
			$list[] = $actionName;
		}
		return $list;
	}
	
	public function getAction($action){
		if (!$this->hasAction($action)) {
			$class = __CLASS__;
			throw new Exception("Requested Action: {$action} does not exist for {$class}.");
		}
		return $this->_configuration['actions'][$action];
	}
	
	public function getQuery($action=''){
		if (!$this->isQueryable($action)) {
			$class = __CLASS__;
			$actionSuffix = (
				'' != $action
				? ":{$action}"
				: ''
			);
			throw new Exception("Requested query for {$class}{$actionSuffix} does not exist.");
		}
		return (
			'' == $action
			? $this->_configuration['query']
			: $this->_configuration['actions'][$action]['query']
		);
	}
	
	public function getServiceLevel($action=''){
		return (
			'' == $action
			? (
				array_key_exists('level', $this->_configuration)
				? $this->_configuration['level']
				: Service_Json::DEFAULT_SERVICE_LEVEL
			) : (
				$this->hasAction($action)
				&& array_key_exists('level',$this->_configuration['actions'][$action])
				? $this->_configuration['actions'][$action]['level']
				: self::getServiceLevel()
			)
		);
	}
	
	public function getSql($method){
		if (method_exists($this,$method)){
			return $this->$method();
		} else {
			throw new Exception('No Database query method in resource configuration.');
		}
	}
}