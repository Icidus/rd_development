<?php
/*******************************************************************************
Rd/Displayer/View.php
Implements a basic view object similar to Zend Framework View

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
 * Class for handling view scripts.
 * @author jthurtea
 *
 */

class Rd_Displayer_View{
	
	protected $_viewScriptPath = '';
	
	protected $_viewName = '';
	
	protected $_modelStore = array();
	
	protected $_viewScriptException = null;
	
	protected $_viewScriptExceptionBuffer = '';
	
	protected $_fileExtension = 'php';
	
	protected $_displayerName = '';
	
	protected $_registeredDisplayers = array();

	public function __construct($displayerNameOrArray = 'base', $view = 'index', $creator=null){
		if(is_array($displayerNameOrArray)){
			$this->_displayerName = 
				array_key_exists('displayer', $displayerNameOrArray) 
				? $displayerNameOrArray['displayer']
				: 'base';
			$this->_viewName = 
				array_key_exists('view', $displayerNameOrArray) 
				? $displayerNameOrArray['view']
				: $view;
			if(array_key_exists('creator', $displayerNameOrArray)){
				$this->registerDisplayer($displayerNameOrArray['creator'], '_creator_');
			}
		} else {
			$this->_displayerName = $displayerNameOrArray;
			$this->_viewName = $view;
			if(isset($creator)){
				$this->registerDisplayer($creator, '_creator_');
			}
		}
		$this->configurePath();
		
	}
	
	public function __set($name, $value) {
        $this->_modelStore[$name] = $value;
    }

    public function __get($name) {
        if (array_key_exists($name, $this->_modelStore)) {
            return $this->_modelStore[$name];
        }
		//#TODO add debugging help for unset properties.
        return null;
    }

    public function getPath(){
    	return $this->_viewScriptPath;
    }
    
    public function setPath($path){
    	$this->_viewScriptPath = $path;
    	$this->configurePath();
    	return $this;
    }
    
    public function getDisplayerName(){
    	return $this->_displayerName;
    }
    
    public function getName(){
    	return $this->_viewName;
    }
    
    public function getExtension(){
    	return $this->_fileExtension;
    }
    
    public function setExtension($ext){
    	$this->_fileExtension = "{$ext}";
    	$this->configurePath();
    	return $this;
    }    

    public function configurePath($displayer='', $view='', $extension=''){
    	$this->_displayerName = (
    			'' == $displayer 
    		? $this->_displayerName 
    		: $displayer
    	);
        $this->_viewName = (
        	'' == $view
	        ? $this->_viewName 
	        : $view
        );
    	$this->_fileExtension = (
    		'' == $extension 
    		? $this->_fileExtension 
    		: $extension
    	);
    	$this->_viewScriptPath = APPLICATION_PATH 
    		. '/displayers/views/' . $this->_displayerName 
    		. '/' . $this->_viewName 
    		. ($this->_fileExtension != '' ? '.' : '') . $this->_fileExtension;
		return $this;
    }
	
    public function __toString(){
    	if(!is_readable($this->_viewScriptPath)){
    		return '';
    	}
    	ob_start();
    	try {
    		include($this->_viewScriptPath);
    		$output = ob_get_contents();
    	} catch (Exception $e) {
    		$this->_viewScriptException = $e;
    		$this->_viewScriptExceptionBuffer = ob_get_contents();
    		$output = '';
    	}
    	ob_end_clean();
    	return $output;
    }
    
    public function getRaw(){
        if(!is_readable($this->_viewScriptPath)){
    		return '';
    	} else {
    		return file_get_contents($this->_viewScriptPath);
    	}
    }
    
    public function isAvailable(){
    	return is_readable($this->_viewScriptPath);
    }

    public function hadError(){
    	return null !== $this->_viewScriptException;
    }
    
    public function getError(){
    	return $this->_viewScriptException;
    }
    
    public function getErrorBuffer(){
    	return $this->_viewScriptExceptionBuffer;
    }
    
    public function clearError(){
    	$this->_viewScriptException = null;
    	$this->_viewScriptExceptionBuffer = '';
    	return $this;
    }
    
    public function registerDisplayer($displayer, $name=''){
    	$this->_registeredDisplayers['' != $name ? $name : $displayer->getName()] = $displayer;
    }
    
    public function getDisplayer($displayerName = '_creator_'){
    	return $this->_registeredDisplayers[$displayerName];
    }
    
    public function htmlHelper($function, $params){
    	return $this->getDisplayer()->htmlHelper($function, $params);
    }
}