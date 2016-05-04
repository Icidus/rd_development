<?php
/*******************************************************************************
Curl_Tunnel.php
Simple object to get includes and post data to/from other zones.
Uses a basic cache in get mode to prevent excessive network traffic.
Includes some basic link/href/src rewritting to get around cross 
site SSL issues.

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

class Curl_Tunnel
{
	
	/**
	 * Hostname at the other end of the tunnel
	 */
	protected $_remoteHost = '';
	
	/**
	 * The CURL resource
	 */
	protected $_curl = NULL;

	/**
	 * Use Cache?
	 */
	protected $_useCache = false;	

	/**
	 * Location for Cache
	 */
	protected $_cacheTarget = '';

	/**
	 * Duration of Cache
	 */
	protected $_cacheShelfLife = 0;

	/**
	 * Open Cache File
	 */
	protected $_cache = array('date' => 0);
	
	/**
	 * Indicates if paths should be translated, true, false to 
	 * translate all or none respectively. Set to 'external' to 
	 * translate only loaded/embedded resources. Set to 'link'
	 * to translate only links and not embedded resource.
	 */
	protected $_translatePaths = true;
	//default is true for now to remain backwards compatible. Should be false in the future.

	/**
	 * Indicates if urls in includes should be forced to httpsj
	 * urls to prevent security warnings.
	 */
	protected $_securePaths = false;
		
	protected $_expectWholeDocument = false;
	
	/**
	 * Accepts a path to a remote host (root, or deep) and an optional 
	 * configuration array. Initializes the curl connection, but will not
	 * attempt to pull any content. If the configuration specifies that caching
	 * is used, it will attempt to ensure that caching is possible by making 
	 * sure the destination folder exists.
	 * 
	 * @throws Exception if caching is turned on and it cannot cache. 
	 *
	 * @param string $remoteHostName
	 * @param array $config
	 */
	public function __construct($remoteHostName,$config=array())
	{
		$this->_remoteHost = $remoteHostName;
		$this->_curl = curl_init();
		curl_setopt($this->_curl, CURLOPT_RETURNTRANSFER, true);
		
		if (count($config) > 0 && array_key_exists('useCache',$config) && $config['useCache'] == true){
			$this->_useCache = true;
			if (array_key_exists('cacheTarget',$config)){
				$cacheTarget = $config['cacheTarget'];
			} else {
				$cacheTarget = '_cache/';
			}
			if (!file_exists($cacheTarget)){
				mkdir($cacheTarget);
				if (!file_exists($cacheTarget)){
					throw new Exception('Unable to create cache folder for remote server connection.');
				}
			}
			//TODO Make sure the destination is writable.
			$this->_cacheTarget = $this->_addTrailingSlash($cacheTarget);
			if (array_key_exists('cacheShelfLife',$config)){
				$this->_cacheShelfLife = $config['cacheShelfLife'];				
			}
		}
		if(count($config) > 0){
			if (array_key_exists('translatePaths',$config)){
				$this->_translatePaths = $config['translatePaths'];
			}
			if (array_key_exists('securePaths',$config)){
				$this->_securePaths = $config['securePaths'];
			} 
		}
		//TODO add config option for default translate paths behavior (do or not do)
		if (count($config) > 1 && array_key_exists('opt',$config) && is_array($config['opt'])){
			if(!curl_setopt_array($this->_curl, $config['opt'])){
				throw new Exception('Unable to configure remote server connection');
			}
		}
	}
	
	/**
	 * Returns the content at the path created by concatinating the 
	 * Curl_Tunnel's remoteHost setting plus the given string. Will catch any
	 * errors thrown as a side effect. If in Debug Mode, it
	 * will output the thrown error in a comment tag (only visible in HTML 
	 * source view). If caching is enabled for the Curl_Tunnel, it will attempt
	 * to first pull from cache, if the content is fresh. If it does not pull 
	 * from cache it will attempt to save any content pulled through curl.
	 *
	 * @param string $path un-normalized path to content
	 * @return mixed
	 */
	public function get($path)
	{
		//TODO add parameter to override default translate paths behavior	
		try{
			if ($this->_cacheIsFresh($path)){
				return $this->_getCache($path);
			}
		} catch (Exception $e) {
			Rd_Debug::out("<!-- caughtException {$e}, in " . __CLASS__ .' '. __METHOD__ . ' ' . __LINE__ . ' ' . __FILE__ . ' ' . " -->");
		}
		$totalPath = $this->_remoteHost. $this->_trimInitialSlash($path);
		curl_setopt($this->_curl, CURLOPT_URL, $totalPath);

		$result = curl_exec($this->_curl);
		
		if ($result){
			$translated = $this->_translatePaths($result);
			$secured = $this->_securePaths($translated);
			try{
				$this->_setCache($path,$secured);
			} catch (Exception $e) {
				Rd_Debug::out("<!-- caughtException {$e}, in " . __CLASS__ .' '. __METHOD__ . ' ' . __LINE__ . ' ' . __FILE__ . ' ' . " -->");
			}			
			return $secured;
		} else {
			$error = curl_error($this->_curl);
			if(trim($error) == ''){
				$error = 'Document returned empty.';
			}
			return "<!-- failed to tunnel {$path}, {$error} -->";
		}
	}
	
	/**
	 * 
	 *
	 */
	public function post($path, $postData, $postFiles = array())
	{
		global $logfile;
		set_time_limit(100);
		$totalPath = $this->_remoteHost. $this->_trimInitialSlash($path);
		curl_setopt($this->_curl, CURLOPT_URL, $totalPath);
		if(is_array($postFiles) && count($postFiles) > 0){
			foreach($postFiles as $postFileIndex=>$postFileName){
				$postFiles[$postFileIndex] = '@' . $postFileName;
			}
			//curl_setopt($this->_curl, CURLOPT_UPLOAD, true);
			curl_setopt($this->_curl, CURLOPT_POST, true);
			curl_setopt($this->_curl, CURLOPT_POSTFIELDS, array_merge($postFiles, $postData));
			
			$result = curl_exec($this->_curl);
			error_log("[" . date("F j, Y, g:i a") . "] ". serialize(array_merge($postFiles, $postData)) . "\n", 3, $logfile);
			error_log("[" . date("F j, Y, g:i a") . "] ". serialize(curl_getinfo($this->_curl)) . "\n", 3, $logfile);
		} else {
			curl_setopt($this->_curl, CURLOPT_POSTFIELDS, $postData);
			$result = curl_exec($this->_curl);
		}
		if ($result){
			//$translated = $this->_translatePaths($result);	
			//return $translated;
			return($result);
		} /*elseif(in_array(curl_getinfo($this->_curl), CURLINFO_HTTP_CODE), array('300', '301', '302')) {
			
		}*/
		else {
			$error = curl_error($this->_curl);
			if(trim($error) == ''){
				$error = 'Document returned empty.';
			}
			return "<!-- failed to tunnel {$path}, {$error} -->";
		}
	}
	
	/**
	 * Prints the content at the path created by concatinating the Curl_Tunnel's
	 * remoteHost setting plus the given string. Prints rather than returns.
	 * Delegates to _get().
	 *
	 * @param string $path un-normalized path to content.
	 */
	public function cPrint($path)
	{
		//TODO add parameter to override default translate paths behavior
		print($this->get($path));
		return $this;
	}
	
	/**
	 * Removes a trailing forward slash to the given string if and only if it 
	 * exists. Good for file paths since trailing slash is contraindicated.
	 *
	 * @param string $path
	 * @param string
	 */
	protected function _trimInitialSlash($path)
	{
		if(strpos($path, '/') === 0){
			return substr($path,1);
		} else {
			return $path;
		}
	}
	
	/**
	 * Adds a trailing forward slash to the given string if and only if it is
	 * missing. Good for URLs since trailing slash is convention.
	 *
	 * @param string $path
	 * @param string
	 */
	protected function _addTrailingSlash($path)
	{
		if(strrpos($path, '/') != (strlen($path) - 1) ){
			return $path . '/';
		} else {
			return $path;
		}
	}	

	/**
	 * Converts root relative links in given string to fully qualified links
	 * based on the remoteHost setting for the Curl_Tunnel. NOTE - Will not 
	 * attempt to handle file relative links! Currently corrects href, src, 
	 * and action attributes. Works for both single and double quoted 
	 * attributes. NOTE - Will not handle unquoted attributes (since they are 
	 * non-complient in any case).
	 *
	 * @param string $result
	 * @return string
	 */
	protected function _translatePaths($result, $methods = NULL)
	{
		if($this->_translatePaths == 'links' && is_null($methods)){
			if($this->_expectWholeDocument){
				$lowerResult = strtolower($result);
				$head = substr($result,0,strpos($lowerResult,'<body'));
				$body = substr($result,strpos($lowerResult,'<body'));
				return $this->_translatePaths($head,array()) . $this->_translatePaths($body, array('href','action'));
			} else {
				return $this->_translatePaths($result, array('href','action'));
			}
		}
		if($this->_translatePaths == 'external' && is_null($methods)){
			if($this->_expectWholeDocument){
				$lowerResult = strtolower($result);
				$head = substr($result,0,strpos($lowerResult,'<body'));
				$body = substr($result,strpos($lowerResult,'<body'));
				return $this->_translatePaths($head) . $this->_translatePaths($body, array('src'));
			} else {
				return $this->_translatePaths($result, array('src'));
			}
		}
		$methods =(
			is_null($methods)
			? array('href','src','action')
			: $methods
		);
		$target = array();
		$replace = array();
		foreach($methods as $method){
			$target[] =  "{$method}=\"/";
			$replace[] = "{$method}=\"{$this->_remoteHost}";
			$target[] =  "{$method}='/";
			$replace[] = "{$method}='{$this->_remoteHost}";
		}
		return (
			$this->_translatePaths
			? str_replace(
				$target, 
				$replace,
				$result
			) : $result
		);
	}	

	/**
	 * Converts non-secure links in given string to SSL specific links
	 * NOTE - Will not attempt to handle file relative links! Currently 
	 * corrects src attributes in script tags. Works for both single and 
	 * double quoted  attributes. NOTE - Will not handle unquoted 
	 * attributes (since they are non-complient in any case).
	 *
	 * @param string $result
	 * @return string
	 */
	protected function _securePaths($result, $methods=NULL)
	{
		if($this->_expectWholeDocument && is_null($methods)){
				$lowerResult = strtolower($result);
				$head = substr($result,0,strpos($lowerResult,'<body'));
				$body = substr($result,strpos($lowerResult,'<body'));
				return $this->_securePaths($head, array('src')) . $body;
		} elseif (is_null($methods)) {
			return $this->_securePaths($result, array('src'));
		}
		$target = array();
		$replace = array();
		foreach($methods as $method){
			$target[] =  "{$method}=\"http://";
			$replace[] = "{$method}=\"https://";
			$target[] =  "{$method}='http://";
			$replace[] = "{$method}='https://";
		}
		return (
			$this->_securePaths
			? str_replace(
				$target, 
				$replace,
				$result
			) : $result
		);
	}	

	/**
	 * Loads the content associated with the specified path and checks the
	 * stored date against the expiration date set in the Curl_Tunnel's config.
	 *
	 * @param string $path un-normalized path to the desired content.
	 * @return bool true if cached content exists is fresh enough to use
	 */
	protected function _cacheIsFresh($path)
	{
		if ($this->_useCache){
			$normalizedPath = $this->_normalizeCachePath($path);
			if(!file_exists($this->_cacheTarget . $normalizedPath)){
				return false;
			} else {
				$this->_cache = unserialize(file_get_contents($this->_cacheTarget . $normalizedPath));
				return (time() <= ($this->_cache['date'] + $this->_cacheShelfLife));
			}
		} else {
			return false;
		}
	}
	
	/**
	 * Retrieves the content associated with the given path. This function will
	 * short circuit if a previous call to _cacheIsFresh has already loaded the
	 * given path, otherwise it will attempt to normalize the path and retrieve.
	 *
	 * @throws Exception When no content is stored at that path.
	 * 
	 * @param string $path un-normalized path associated with the desired content.
	 * @return mixed Returns the cache associated with the given path.
	 */
	protected function _getCache($path)
	{
		if(array_key_exists('path',$this->_cache) && $this->_cache['path'] == $path){
			return $this->_cache['payload'];
		} else {
			$normalizedPath = $this->_normalizeCachePath($path);
			if(!file_exists($this->_cacheTarget . $normalizedPath)){
				throw new Exception("Attempted to get cached version of $path, but file did not exist");
			} else {
				$this->_cache = unserialize(file_get_contents($this->_cacheTarget . $normalizedPath));
				return $this->_cache['payload'];
			}
		}
	}
	
	/**
	 * Caches content associated with a given path. Nests the payload ($content)
	 * with additional data used to manage the cache. Will silently refuse if 
	 * the Curl_Tunnel object is not configured to cache information.
	 * 
	 * @throws Exception when unable to store (but not when unwilling due to config).
	 *
	 * @param unknown_type $path un-normalized path associated with the content to be cached.
	 * @param mixed $content any serializable data type is allowed.
	 */
	protected function _setCache($path, $content)
	{
		if ($this->_useCache){
			$normalizedPath = $this->_normalizeCachePath($path);
			if(!file_put_contents($this->_cacheTarget . $normalizedPath, serialize( 
				array(
					'payload' => $content,
					'date' => time(),
					'path' => $path
				)
			))){
				throw new Exception("Attempted to store cache for $path, but was unable.");
			}
		}
	}
	
	/**
	 * Replaces forward slashes in the passed string $path with a filename safe 
	 * alternative. Used to determine an appropriate cache filename for url
	 * paths that may include forward slashes, or figure out the cache filename
	 * previously assigned to a given url path.
	 *
	 * @param string $path
	 * @return string 
	 */
	protected function _normalizeCachePath($path){
		return str_replace('/', '_slash_', $path);
	}
	
	public function setTranslatePathMode($newSetting){
		$this->_translatePaths = $newSetting;
		return $this;
	}
	
}

?>
