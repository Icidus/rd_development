<?php
/*******************************************************************************
Rd/Manager/Base.php
Base Manager abstract class

Created by Dmitriy Panteleyev (dpantel@emory.edu)
Modified by NCSU Libraries, NC State University. Modifications by Troy Hurteau (libraries.opensource@ncsu.edu).

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

require_once(APPLICATION_PATH . '/lib/Rd/Manager/Delegating.php');
require_once(APPLICATION_PATH . '/lib/Rd/Exception/Form.php');
				
/**
 * Base Manager abstract class
 * - Contains functions common to many managers
 * - To be extended by other manager classes
 */
abstract class Rd_Manager_Base extends Rd_Manager_Delegating{
	public $user = null;
	public $displayClass = '' ;
	public $displayFunction = 'index';
	public $argList = array();

	protected $_managerName = 'base';
	protected $_mappedDisplayArguments = false;
	protected $_displayer = null;
	protected $_withOptions = array();
	protected $_currentCommand = 'index';
	protected $_user = NULL;
	protected $_role = NULL;
	
	public function __construct($cmd='index', $request = array(), $with = array()){
		$className = get_class($this);
		$nameStyle = (0 === strpos($className, 'Rd_Manager_')) ? 'new' : 'old';
		$this->_managerName = (
			'new' == $nameStyle
			? strtolower(substr($className,strlen('Rd_Manager_')))
			: substr($className,0,strpos($className,'Manager'))
		);
		$this->with($with);
		/* #TODO write mechanism to look up modile rendering status and mobile availability for action.
		// possibly using the with mechanism
		$this->displayClass = (
			isset($_SESSION['mobile']) && $_SESSION['mobile'] == 'true'
			? 'mobileReservesDisplayer'
			: 'reservesDisplayer'
		);
		 */
		if (
			!isset($this->displayClass) 
			|| is_null($this->displayClass) 
			|| '' == trim($this->displayClass)
		) {
			$this->displayClass = (
				'new' == $nameStyle
				? 'Rd_Displayer_' . substr($className,strlen('Rd_Manager_'))
				: "{$this->_managerName}Displayer"
			);
		}

		$this->_user = Rd_Registry::get('root:userInterface');
		$this->_role = $this->_user->getRole();
		
		$defaultAction = "{$cmd}Action";
		$protectedAction = "_{$defaultAction}";
		if(method_exists($this, $protectedAction)){
			$this->_currentCommand = $cmd;
			$this->$protectedAction($request);
		} else if(method_exists($this, $defaultAction)){
			$this->_currentCommand = $cmd;
			$this->$defaultAction($request);
		} else if (method_exists($this, '_indexAction')) {
			if (Rd_Debug::isEnabled()) {
				Rd_Debug::out("Requested command: \"{$cmd}\" not supported. Switching to index.");
			}
			$this->_indexAction($request);
		} else {
			if (Rd_Debug::isEnabled()) {
				Rd_Debug::out("Requested command: \"{$cmd}\" not supported. Switching to index.");
			}
			$this->indexAction($request);
		}
	}
	
	public function display() {
		if(!class_exists($this->displayClass)){
			$nameStyle = (0 === strpos($this->displayClass, 'Rd_Displayer_')) ? 'new' : 'old';
			$classFile =
				'new' == $nameStyle
				? substr($this->displayClass, strlen('Rd_Displayer_'))
				: $this->displayClass;
			if (!file_exists(APPLICATION_PATH . "/displayers/{$classFile}.php")) {
				$classFile .= '.class';
			}
			include_once(APPLICATION_PATH . "/displayers/{$classFile}.php");
		}
		//print_r(array($this->displayClass, $this->displayFunction));
		$args = array();
		foreach($this->argList as $index=>$value){
			$args[$index] =& $this->argList[$index];
		}
		if (is_callable(array($this->displayClass, $this->displayFunction))){
			$this->_displayer = new $this->displayClass();
			$this->_displayer->setUser($this->_user);
			call_user_func_array(
				array(
					$this->_displayer, 
					$this->displayFunction
				), 
				$this->_mappedDisplayArguments 
				? array($args)
				: $args
			);
		} else {
			if (Rd_Debug::isEnabled()){
				Rd_Debug::out("The selected displayer: {$this->displayClass} does not have a \"{$this->displayFunction}\" method.");
			} else {
				//#TODO 404 time.
			}
		}
	}
	
	protected function _delegate($cmd, $request = NULL)
	{
		if (is_null($request)) {
			$request = $_REQUEST;
		}
		$this->_delegateManager = Rd_Dispatch::getManager($cmd, $request);
	}
	
	protected function _updateCommand($cmd){
		$this->_currentCommand = $cmd;
	}
	
	public function getDisplayer(){
		return $this->_displayer;	
	}
	
	public function setUser($userObject){
		$this->user = $userObject;	
	}
	
	public function indexAction($request = array())
	{
		$this->_delegateManager = new errorManager('notFound');
	}
	
	protected function _setTab($tab)
	{
		Rd_Layout_Tab::set($tab);
	}
	
	protected function _setLocation($location)
	{
		Rd_Layout_Location::set($location);
	}
	
	protected function _appendLocation($location)
	{
		Rd_Layout_Location::append($location);
	}
	
	protected function _setHelp($helpId)
	{
		Rd_Help::setDefaultArticleId($helpId);
	}

	public function with($option){
		if (!is_array($option)) {
			$option = array($option);
		}
		foreach($option as $subOption){
			if(!in_array($subOption, $this->_withOptions)){
				$this->_withOptions[] =  $subOption;
			}
		}
	}
	
	public function without($option){
		if (!is_array($option)) {
			$option = array($option);
		}
		foreach($option as $subOption){
			if(in_array($subOption, $this->_withOptions)){
				$removeKeys = array_keys($this->_withOptions, $subOption);
				foreach($removeKeys as $key){
					unset($this->_withOptions[$key]);
				}
			}
		}
	}
	
	public function getOptions()
	{
		$returnOptions = array();
		foreach($this->_withOptions as $option){
			$returnOptions = $option;	
		}
		return $returnOptions;
	}
	
	public function hasOption($option)
	{
		return in_array($option, $this->_withOptions);
	}
	
	public function getCurrentCommand()
	{
		return $this->_currentCommand;
	}
	
	public function autoCss()
	{
		return Rd_Layout::autoCss($this->_managerName, $this->_currentCommand);
	}
}
