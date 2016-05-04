<?php
/*******************************************************************************
searchManager.class

Created by Jason White (jbwhite@emory.edu)
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
require_once(APPLICATION_PATH . '/common.inc.php');
require_once(APPLICATION_PATH . '/classes/users.class.php');
require_once(APPLICATION_PATH . '/displayers/searchDisplayer.class.php');
require_once(APPLICATION_PATH . '/lib/Rd/Ils.php');

class searchManager extends Rd_Manager_Base
{
	public $user;
	public $displayClass;
	public $displayFunction;
	public $argList;
	private $search_sql_statement;
	
	/**
	 * Manager for Search Tab
	 *
	 * @param string $cmd - current command
	 * @param user $user  - loged in user object
	 * @param array $request - http request object
	 */	
	function searchManager($cmd, $user, $request)
	{
		//echo "searchManager::searchManager($cmd, $user, $request, $hidden_fields=null)<br>";
		global $g_permission, $ci;
		$u = Rd_Registry::get('root:userInterface');
			
		$this->displayClass = "searchDisplayer";
		$this->_setTab('search');	
		
		switch ($cmd)
		{
			case 'doSearch':
				$this->_setLocation('search for documents');
			
				//$this->search_sql_statement = (isset($request['sql']) && $request['sql'] != '') ? stripslashes(urldecode($request['sql'])) : null;
        		//$search_array = (isset($_GET['search'])) ? unserialize(base64_decode($_GET['search'])) : unserialize(base64_decode($request['search']));
        		$search_array = is_string($_REQUEST['search']) ? unserialize(base64_decode($_REQUEST['search'])) : $_REQUEST['search'];
				$limit = (array_key_exists('limit', $request) ? $request['limit'] : '');
				$item = (array_key_exists('item', $request) ? $request['item'] : '');
				$sort = (array_key_exists('sort', $request) ? $request['sort'] : '');
				$items = $this->doSearch($search_array, $limit, $item, $sort);
				
				$displayQry = '';
				if (!isset($request['displayQry']))
					for($i=0;$i<count($search_array);$i++)
					{
						if ($search_array[$i]['term'] != '')				
						{
							if ($i > 0) $displayQry .= " " . $search_array[$i-1]['conjunct'] . " ";
							$displayQry .= $search_array[$i]['term'];
						}
					}
				else 
					$displayQry = stripslashes($request['displayQry']);						
				
				$hidden_fields = array(
					'cmd'			=> 'addResultsToClass',
					'search' 			=> base64_encode(serialize($search_array)), 
					'sort' 			=> $sort,
					'displayQry'	=> $displayQry
				);
							
				$this->displayFunction = 'searchResults';				
				$this->argList = array($cmd, $items, $hidden_fields, stripslashes($displayQry));
			break;
			
			case 'addResultsToClass':
				$this->_setLocation('add items to class');
				
				//get CI and set up dates
				if(empty($request['ci'])) {	//no ci, show lookup
					$this->displayFunction = 'displaySelectClass';
					$this->argList = array('addResultsToClass', null, 'Select class to add items TO:', $request);
					break;
				}
				else {	//we have a CI
					$ci = new courseInstance($request['ci']);
					
					//set dates based on CI
					$activation_date = $ci->getActivationDate();
					$expiration_date = $ci->getExpirationDate();
				}
				
				if (!isset($request['submitButton']) || $request['submitButton'] != 'Add Items to Class')
				{
					//class not yet selected
					$removeItemFromList = (isset($request['removeItem'])) ? $request['removeItem'] : null;

					for($i=0;$i<count($request['itemSelect']);$i++)
						if ($request['itemSelect'][$i] != $removeItemFromList)
							$selectedItem[] = new reserveItem($request['itemSelect'][$i]);				
							
					$this->displayFunction = 'addResultsToClass';				
					$this->argList = array('addResultsToClass', 'storeClassItems', $u, $selectedItem, $request, $activation_date, $expiration_date, null);	
				} else {
					//class selected create reserves				
					$requests = (isset($request['requestItem'])) ? $request['requestItem'] : null;
					$items = (isset($request['reserveItem'])) ? $request['reserveItem'] : null;						
	
					$reserveCnt = 0;
	
					//add items to reserve
					if (is_array($items) && !empty($items)){
						foreach($items as $i_id)
						{
							$reserve = new reserve();
							if ($reserve->createNewReserve($ci->getCourseInstanceID(), $i_id))
							{
								$reserve->setActivationDate($request['activation_date']);
								$reserve->setExpirationDate($request['expiration_date']);
								$reserve->setStatus((array_key_exists('status', $request) ? $request['status'] : ''));
								
								//attempt to insert this reserve into order
								$reserve->getItem();
								$reserve->insertIntoSortOrder($ci->getCourseInstanceID(), $reserve->item->getTitle(), $reserve->item->getAuthor());
								
								$reserveCnt++;
							}
						}
					}
	
					//make requests
					if (is_array($requests) && !empty($requests)){
						foreach($requests as $i_id)
						{
							//store reserve with status processing
							$reserve = new reserve();
							if ($reserve->createNewReserve($ci->getCourseInstanceID(), $i_id))
							{
								$reserve->setStatus("IN PROCESS");
								$reserve->setActivationDate((array_key_exists('activation_date', $request) ? $request['activation_date'] : ''));
								$reserve->setExpirationDate((array_key_exists('expiration_date', $request) ? $request['expiration_date'] : ''));
								//attempt to insert this reserve into order
								$reserve->getItem();
								$reserve->insertIntoSortOrder($ci->getCourseInstanceID(), $reserve->item->getTitle(), $reserve->item->getAuthor());
								$reserveCnt++;
	
								//create request
								$req = new request();
								//make sure request does not exist
								//prevent duplicate requests
								if($req->getRequestByCI_Item($ci->getCourseInstanceID(), $i_id) === false) {
									$req->createNewRequest($ci->getCourseInstanceID(), $i_id);
									$req->setRequestingUser($user->getUserID());
									$req->setReserveID($reserve->getReserveID());
								}
							}
						}
					}
					$ci->getPrimaryCourse();							
					$this->displayFunction = 'addComplete';				
					$this->argList = array($cmd, $ci, "$reserveCnt item(s) were successfully added to ". $ci->course->displayCourseNo() . " " . $ci->course->getName());
				}
			break;
				
			case 'searchTab':		
			default:
				$this->_setLocation('search for documents');			
								
				$this->displayFunction = 'searchForDocuments';
				$this->argList = array($cmd, null);
		}
	}
	
	/**
	 * Query Database based on user search options
	 *
	 * @param array $search - search term, field, test and conjunct
	 * @param array $limit  - limit term, field, test and conjunct
	 * @param array $itemGroup - term, test field will always be item_group
	 * @param string $sort - sort field
	 * @return array of reserveItem
	 */
	function doSearch($search, $limit, $itemGroup, $sort)
	{
		global $g_dbConn, $g_permission;
		$u = Rd_Registry::get('root:userInterface');

        if('' == $itemGroup){
            $itemGroup = array('term'=>'','test'=>'=');
        }
        if('' == $limit){
            $limit = array();
        }

		if (is_null($this->search_sql_statement)){

			$sql_select = "SELECT DISTINCT i.item_id ";
			$sql_from 	= "FROM items as i 
				LEFT JOIN reserves as r ON i.item_id = r.item_id AND i.private_user_id IS NULL AND i.item_type = 'ITEM' 
				LEFT JOIN course_aliases as ca ON r.course_instance_id = ca.course_instance_id
				LEFT JOIN courses as c ON ca.course_id = c.course_id
				LEFT JOIN departments as d ON c.department_id = d.department_id
				LEFT JOIN access as a ON ca.course_alias_id = a.alias_id 
				LEFT JOIN users as u ON a.user_id = u.user_id AND a.permission_level = " . $g_permission['instructor'] . " "; 
				
			$sql_add = "";
									
			for($i=0;$i<count($search);$i++) {
				if ($search[$i]['field'] == 'n.note') {
					$sql_add = "LEFT JOIN notes as n ON n.target_table='items' AND n.type = 'content' AND n.target_id = i.item_id ";
					break;
				}
			}
			$sql_from .= $sql_add;
								
			if ($search[0]['term'] != '') { //if 1st term is not set we are going to ignore all others
				$sql_where = "WHERE i.item_type != 'HEADING' AND ";	
				//hide copyright denied items and reserves unless staff
				if ($u->getRole() < $g_permission['staff']) {
					$sql_where = " (i.status <> 'DENIED' AND r.status <> 'DENIED') ";
				}

				//the if statement is used only to determine whether it is a barcode search and non-empty
				if ($search[0]['field']=='barcode' && $search[0]['term']!=''){
							
					$ils = RD_Ils::initILS();
					try {
						$ilsResult = $ils->search('barcode',$search[0]['term']);  //query Sirsi with the barcode
					} catch (Exception $e) {
						$this->_delegateManager = new errorManager('message', $e->getMessage());
						return;
					}
					$ilsArray = $ilsResult->to_a();
					//**This next statement inserts the control key from sirsi as a request in the mysql query
					$sql_where = (!empty($ilsArray['controlKey']))? "WHERE i.item_type != 'HEADING' AND i.local_control_key = \"" . $ilsArray['controlKey'] . "\"" : "WHERE i.item_type != 'HEADING' AND i.local_control_key = 'barcode'";
				} else {
					for($i=0;$i<count($search);$i++) {							
						if ($search[$i]['term'] != '') {	
							$conjunction = ($i > 0 && $search[$i-1]['term'] != '') ? $search[$i-1]['conjunct'] . " " : ""; 
							switch ($search[$i]['test']){
								case 'LIKE':
									//$sql_where .= $conjunction . " match(" . $search[$i]['field'] . ") against ( \"" . strtolower($search[$i]['term']) . "\") ";
									$sql_where .= $conjunction . " (" . $search[$i]['field'] . " LIKE \"%" . strtolower($search[$i]['term']) . "%\") ";
								break;
								
								case '<>':
									//$sql_where .= $conjunction . " not match(" . $search[$i]['field'] . ") against ( \"" . strtolower($search[$i]['term']) . "\") ";
									$sql_where .= $conjunction . " (" . $search[$i]['field'] . " NOT LIKE \"%" . strtolower($search[$i]['term']) . "%\") ";
								break;
								
								case '=':
									$sql_where .= $conjunction . " lower(" . $search[$i]['field'] . ") " . $search[$i]['test'] . " \"" . strtolower($search[$i]['term']) . "\" ";
								default:	
							}
						}
					}
				}
				if(!is_array($itemGroup)) {
	            	throw new Exception('Item Group specification is not an array.');
	            }						
				if ($itemGroup['term'] != '') {
					if ($itemGroup['test'] == "=") {
						$sql_where .= " AND item_group " . $itemGroup['test'] . " \"" . $itemGroup['term'] . "\" ";
					} else {
						$sql_where .= " AND item_group " . $itemGroup['test'] . " \"%" . $itemGroup['term'] . "%\" ";
					}
				}
				for($i=0;$i<count($limit);$i++) {
					$conjunction = ($i > 0 && $limit[$i-1]['term'] != '') ? $limit[$i]['conjunct'] . " " : ""; 
					if ($limit[$i]['term'] != '') {		
						if ($limit[$i]['test'] == '=') {
							$test = $limit[$i]['test'] . " \"" . $limit[$i]['term'] . "\" ";
						} else {
							$test = $limit[$i]['test'] . " \"%" . $limit[$i]['term'] . "%\" ";
						}
						switch ($limit[$i]['field']) {
							default:
							case 'instructor':
								$sql_limit = " AND u.last_name $test ";
							break;
							
							case 'department':
								$sql_limit = " AND (d.name $test OR d.abbreviation $test) ";
							break;
							
							case 'course_name':
								$sql_limit = " AND ca.course_name $test ";
						}
						$sql_where .= $conjunction . $sql_limit;
					}						
				}
			} else {
				$sql_where = "";
			}
		}
		//echo "<h1> [$sql_select] [$sql_from] [$sql_where] </h1>";
		$this->search_sql_statement = $sql_select . $sql_from . $sql_where;
		
		if (isset($sort) && !is_null($sort) && $sort != '')
		{
			$sql_sort = " ORDER BY i.$sort ";			
			$raw_sql = explode('ORDER BY', $this->search_sql_statement);			
			$this->search_sql_statement = $raw_sql[0] . $sql_sort;
		}
		
		$this->search_sql_statment .= ' LIMIT 2000';
		Rd_Debug::out($this->search_sql_statement);
		$rs = $g_dbConn->query($this->search_sql_statement);		
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }

		$results = null;
		while ($row = $rs->fetch(PDO::FETCH_NUM))
			$results[] = new reserveItem($row[0]);			
			
		return $results;
	}
}
