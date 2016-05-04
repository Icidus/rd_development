<?php
/*******************************************************************************
Json.php
JSON data service for accessing RD data

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

require_once(APPLICATION_PATH . '/lib/Rd/Resolver.php');
require_once(APPLICATION_PATH . '/lib/Post/Document.php');

/**
 * 
 * Service for exposing RD DB and Configuration data to external systems.
 * @author jthurtea
 *
 */

class Service_Json{
	
	protected static $_partitionSize = 10000;
	
	protected static $_debugHeap = array();
	
	protected static $_providedServiceKey = false;
	
	const RD_FACULTY_PERM_LEVEL = 3;
	
	const RD_USERNAME_ALIAS = 'unity_name';
	
	const RESOURCE_CLASS_PREFIX = 'Service_Json_Resource';
	
	const RESOURCE_CLASS_PATH = 'secure/lib/Service/Json/Resource'; //#TODO fix this!!
	
	const SERVICE_LEVEL_PUBLIC = -1;
	const SERVICE_LEVEL_TRUSTED = 5;
	const DEFAULT_SERVICE_LEVEL = 5;
	
	protected static $_invalidResource = array(
		'success' => false,
		'message' => 'Resource reqested is not valid.',
		'errorType' => 'RD_SERVICE_INVALID_RESOURCE'	
	);

	protected static $_invalidPartition = array(
		'success' => false,
		'message' => 'Partition reqested is not valid.',
		'errorType' => 'RD_SERVICE_INVALID_PARTITION'	
	);
	
	protected static $_refusedResource = array(
		'success' => false,
		'message' => 'Not authenticated, this resource requires elevated permissions. Please include a service key with your next request. If you do not support cookies, please include the service key with every request.',
		'errorType' => 'RD_SERVICE_NONAUTH_REQ'	
	);
	
	protected static $_invalidAction = array(
		'success' => false,
		'message' => 'The requested resource action is not supported.',
		'errorType' => 'RD_SERVICE_INVALID_ACTION'	
	);
	
	protected static $_notQueryable = array(
		'success' => false,
		'message' => 'The requested resource action is not directly queryable.',
		'errorType' => 'RD_SERVICE_UNINDEXED_RESOURCE'	
	);
	
	protected static $_missingParameter = array(
		'success' => false,
		'message' => 'Unable to process your request, required action parameter is missing or invalid.',
		'errorType' => 'RD_SERVICE_MISSING_PARAM'	
	);
	
	protected static $_supportedResources = array(
		'circulation_rule' => 'CirculationRule',
		'configuration' => 'Configuration',
		'course' => 'Course',
		'course_alias' => 'CourseAlias',
		'course_instance' => 'CourseInstance',
		'department' => 'Department',
		'faculty' => 'Faculty',
		'item' => 'Item',
		'library' => 'Library',
		'physical_copy' => 'PhysicalCopy',
		'request' => 'Request',
		'reserve' => 'Reserve',
		'term' => 'Term',
		'mxe' => 'Mxe',
		'fileupload' => 'FileUpload'
	);
	
	
	protected static function _mayDebug()
	{
		return 'production' != APPLICATION_ENV;
	}
	
	protected static function _unterminateQuery($query)
	{
		$trimQuery = trim($query);
		if(strrpos($trimQuery, ';') == strlen($trimQuery) - 1){
			return(substr($trimQuery, 0, strlen($trimQuery) - 1));
		}
	}
	
	protected static function _behead($query, $beheadMatch = ' FROM')
	{
		$where = strpos($query, $beheadMatch);
		
		if($where === FALSE){
			throw new Exception('Unable to behead query for this resource...');
		}
		return(substr($query, $where + strlen($beheadMatch)));
	}
	
	protected static function _manualBehead($query)
	{
		return self::_behead($query,'{{behead}}');
	}
	
	protected static function _validateResource(&$resource)
	{
		$resource = trim(strtolower($resource));
		if (array_key_exists($resource, self::$_supportedResources)){
			return true;
		} else {
			return false;
		}
	}
	
	protected static function _validKeyOrAdmin()
	{
		return self::$_providedServiceKey || Account_Rd::isAdmin();
	}
	
	protected static function _staff()
	{
		return Account_Rd::atLeastStaff();
	}
	
	protected static function _faculty()
	{
		return Account_Rd::atLeastFaculty();
	}
	
	protected static function _student()
	{
		return Account_Rd::atLeastStudent();
	}
	
	protected static function _loadResource($baseClassName)
	{
		$resource = null;
		$fullClassName = self::_getResourceClassName($baseClassName);
		if (!class_exists($fullClassName)) {
			$baseClassFile = self::RESOURCE_CLASS_PATH . '.php';
			if (!class_exists(self::RESOURCE_CLASS_PREFIX)) {
				if (!fileExistsInPath($baseClassFile)){
					throw new Exception('Failed to find the generic resource configuration class.');
				} else {
					require_once($baseClassFile);
				}
			}
			$classFile = self::RESOURCE_CLASS_PATH . '/' . $baseClassName . '.php';
			if (!fileExistsInPath($classFile)){
				throw new Exception('Failed to find the requested resource configuration.');
			}
			require_once($classFile);
			if (!class_exists($fullClassName)) {
				throw new Exception('Failed to load the requested resource configuration.');
			}
			$resource = new $fullClassName();
		}
		return $resource;
	}
	
	protected static function _getResourceClassName($baseClassName){
		return self::RESOURCE_CLASS_PREFIX . '_' . $baseClassName;
	}
	
	public static function rootResource()
	{
		if(!self::_validKeyOrAdmin()){
			return self::$_refusedResource;
		}
		$resources = array();
		foreach (self::$_supportedResources as $name=>$resourceClass) {
			$resources[$name] = array();
			$resource = self::_loadResource($resourceClass);
			$resources[$name]['queryable'] = $resource->isQueryable();
			if ($resource->hasActions()) {
				$resources[$name]['resources'] = $resource->getActions();
			}
		}
		return array('success'=>null, 'resources'=>$resources);
	}
	
	public static function resource($resource, $partitionOrParams = '')
	{
		self::$_providedServiceKey = Service_Key::authenticate();
		if('' == $resource){
			return self::rootResource();
		}
		if (!self::_validateResource($resource)){
			Rd_Status::set(404);
			return self::$_invalidResource;
		}
		$resource = self::_loadResource(self::$_supportedResources[$resource]);
			
		$action = is_array($partitionOrParams) ? array_shift($partitionOrParams) : $partitionOrParams;
		
		if($resource->hasAction($action)){
			$resourceParam = (
				is_array($partitionOrParams) && 1 <= count($partitionOrParams) 
				? array_shift($partitionOrParams)
				: ''
			);
		} else {
			if(!$resource->isQueryable()){
				if ('' != $action) {
					Rd_Status::set(404);
					return self::$_invalidAction;
				} else {
					Rd_Status::set(400);
					return self::$_notQueryable;
				}
			} else {
				$resourceParam = $action;
				$action = '';
			}
		}

		if ($resource->hasAction($action)) {
			$serviceLevel = $resource->getServiceLevel($action);
			$query = $resource->getQuery($action); 
		} else {
			$query = $resource->getQuery();
			$serviceLevel = $resource->getServiceLevel();
		}
		
		$payload = 'payload';
		
		if($serviceLevel > self::SERVICE_LEVEL_PUBLIC
			&& $serviceLevel > Account_Rd::getLevel()
			&& !self::_validKeyOrAdmin()
		){ 
			print_r(array($serviceLevel , Account_Rd::getLevel())); die;
			return self::$_refusedResource;
		}
		
		if (!is_array($query) || 0 == count($query)) {
			throw new Exception('Uninitialized data source in JSON Service configuration for this resource.');
		}
		
		$resultType = (
			array_key_exists('payloadType', $query)
			? $query['payloadType']
			: 'array'
		);
		switch(
			array_key_exists('source', $query)
			? $query['source']
			: 'db'
		){
			case 'db' :
				if(array_key_exists('sqlMethod', $query)){
					$method = $query['sqlMethod'];
					$query['sql'] = $resource->getSql($method);
				}
				if (!array_key_exists('sql',$query)) {
					throw new Exception('No Database query in JSON Service configuration for this resource.');
				}
				$sql = $query['sql'];
				$moreAvailable = false;
				$takesParam = strpos($sql,'{{param}}') !== false;
				$takesPartition = array_key_exists('partition', $query) && $query['partition'];
				if ($takesParam) {
					if('' == $resourceParam || !is_numeric($resourceParam)) { //#TODO not all params should have to be numeric...
						Rd_Status::set(400);
						return self::$_missingParameter;
					}
					
					$resourceParam = intval($resourceParam);
					$sql = str_replace('{{param}}', $resourceParam, $sql);
					
					$partition = (
						$takesPartition
							&& is_array($partitionOrParams) 
							&& 1 <= count($partitionOrParams) 
						? array_shift($partitionOrParams)
						: ''
					);
				} else {
					$partition = $resourceParam;
					$resourceParam = '';
					
				} 
				
				if (('' != $partition && !is_numeric($partition)) || 0 > $partition){
					Rd_Status::set(400);
					return self::$_invalidPartition;
				}
				
				if ($takesPartition) {
					$partitionStart = intval($partition) * self::$_partitionSize;
					$partitionSize = self::$_partitionSize;
					$partitionQuery = str_replace(
						'{{id}}',
						(array_key_exists('sort', $query) ? $query['sort'] : 'id'),
						" ORDER BY {{id}} LIMIT {$partitionStart},{$partitionSize};"
					);
					$totalQuery = "SELECT COUNT(*) AS `count` FROM" . (
						array_key_exists('manualBehead',$query) && $query['manualBehead']
						? self::_manualBehead($sql)
						: self::_behead($sql)
					);
					
					self::$_debugHeap['total_query'] = $totalQuery;
					$totalResult = Rd_Pdo::query($totalQuery);
					
					if(!$totalResult){
						$returnObject = array(
							'success'=> false,
							'message'=> 'Error performing the record count query.',
							'errorType' => 'RD_SERVICE_QUERY_ERROR'
						);
						
						if(self::_mayDebug()){
							$returnObject['heap'] = self::$_debugHeap;
						}
						return $returnObject;
					}
					
					$total = $totalResult->fetchColumn();
					$maxResult = $partitionStart + $partitionSize;
					$moreAvailable = $maxResult < $total;
					
					$sql = self::_unterminateQuery($sql). " {$partitionQuery}";
					$sql = str_replace('{{behead}}', '', $sql);
				} else if (array_key_exists('sort',$query)){ //#TODO not sure why these are mutex
					$sortQuery = str_replace(
						'{{id}}',
						(array_key_exists('sort', $query) ? $query['sort'] : 'id'),
						" ORDER BY {{id}};"
					);
					$sql = self::_unterminateQuery($sql). " {$sortQuery}";
				}
				self::$_debugHeap['primary_query'] = $sql;
				$result = Rd_Pdo::query($sql);
				$failureMessage = 'Query failed.';
				break;
			case 'config' :
				if (!array_key_exists('name',$query) && !array_key_exists('const',$query)) {
					throw new Exception('No configuration value name source in JSON Service configuration for this resource.');
				}
				if(array_key_exists('const',$query)){
					$result = constant($query['const']);
				} else {
					$result = Rd_Config::get($query['name']);
				}
				$failureMessage = 'Configuration value unavailable.';
				break;
			case 'proxyObject' :
				if (!array_key_exists('name',$query) || !array_key_exists('method',$query)) {
					throw new Exception('Missing resolution data in JSON Service configuration for this resource.');
				}
				if(!class_exists($query['name'])){
					$classPath = 'secure/lib/' . str_replace('_', '/', $query['name']) . '.php'; //#TODO fix this
					if (!fileExistsInPath($classPath)){
						throw new Exception('Failed to find the object for this resource.');
					} else {
						require_once($classPath);
					}
					if(!is_array($partitionOrParams)){
						$partitionOrParams = array();
					}
					$result = $query['name']::$query['method'](
						$action . '/' 
							. ('' != $resourceParam ? $resourceParam . '/' : '') 
							. implode('/', $partitionOrParams),
						$_GET,
						count($_POST) > 0 ? $_POST : Post_Document::get()
					);
				}
				break;
			case 'object' :
				if (!array_key_exists('name',$query) || !array_key_exists('method',$query)) {
					throw new Exception('Missing resolution data in JSON Service configuration for this resource.');
				}
				if(!class_exists($query['name'])){
					$classPath = 'secure/lib/' . str_replace('_', '/', $query['name']) . '.php'; //#TODO fix this
					if (!fileExistsInPath($classPath)){
						throw new Exception('Failed to find the object for this resource.');
					} else {
						require_once($classPath);
					}
					if(!is_array($partitionOrParams)){
						$partitionOrParams = array();
					}
					$result = $query['name']::$query['method'](
						array_merge(
							array($resourceParam),
							$partitionOrParams
						)
					);
				}
				break;
			default:
				throw new Exception('Unsupported data source in JSON Service configuration for this resource.');
				break;
		}
		if (array_key_exists('label',$query)) {
			$payload = $query['label'];
		}

		$returnObject = array('success'=>null);

		if (!$result)
		{
			$returnObject = array(
				'success'=> false,
				'message'=> $failureMessage
			);
			
			if(self::_mayDebug()){
				$returnObject['heap'] = self::$_debugHeap;
			}
			return $returnObject;
		} else {
			$returnObject = array(
				'success' => true,
				'count' => is_object($result) && method_exists($result, 'rowCount')
					? $result->rowCount()  
					: count($result)
			);
			if(array_key_exists('partition',$query) && $query['partition']){
				$returnObject['total'] = $total;
				$nextPartition = intval($partition) + 1;
				$continueAtUri = Rd_Resolver::getBaseResourceUri()
					. (
						'' != $resourceParam
						? "{$action}/{$resourceParam}/"
						: ''
					) . "{$nextPartition}/";
				if ($moreAvailable){
					$returnObject['next_partition'] = $nextPartition;
					$returnObject['continue_at'] =  $continueAtUri;
				}
			}
			if('array' == $resultType && (is_array($result) || is_object($result))){
				foreach($result as $row){
					$returnObject[$payload][] = $row; 
				}
			} else {
				$returnObject[$payload] = $result;
			}
			return $returnObject;
		}
	}
}