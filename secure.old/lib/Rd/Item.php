<?php
/*******************************************************************************
Rd/Item.php
Implements an Item Utility for RD

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
 * Item utility class.
 * @author jthurtea
 *
 */

class Rd_Item
{

	public static function searchCatalogItem($data){
		return array();
	}
	
	/**
	 * Attempts to find an item in DB and/or (if physical item) in ILS; return array prefilled w/ item data or empty array w/ proper indeces
	 *
	 * @param string $cmd Current cmd
	 * @return array
	 */
	function searchItem($cmd) { //#TODO deprecate these in favor of a utility class
		
		//create a blank array with all the needed indeces
		$item_data = array('title'=>'', 'author'=>'', 'edition'=>'', 'performer'=>'', 'times_pages'=>'', 'volume_title'=>'', 'source'=>'', 'controlKey'=>'', 'selected_owner'=>null, 'physicalCopy'=>null, 'OCLC'=>'', 'ISSN'=>'', 'ISBN'=>'', 'item_group'=>null, 'notes'=>null, 'home_library'=>null, 'url'=>'', 'is_local_file'=>false);
				
		//decide if item info can be prefilled
		$item_id = null;
		if (array_key_exists('item_id', $_REQUEST) && !is_null($_REQUEST['item_id'])) {
			$item_id = $_REQUEST['item_id'];
		} elseif (array_key_exists('reserve_id', $_REQUEST) &&!is_null($_REQUEST['reserve_id'])) {
			$reserve = new reserve($_REQUEST['reserve_id']);
			$item_id = $reserve->getItem();			
		} elseif (array_key_exists('request_id', $_REQUEST) &&!is_null($_REQUEST['request_id'])) {
			$request = new request($_REQUEST['request_id']);
            $request->getRequestedItem();
			$item_id = $request->requestedItem->getItemID();
		}
		
		$item = new reserveItem($item_id);
		$qryField = $qryValue = null;
		
		if(!empty($_REQUEST['searchField']) && is_null($item->itemID)) {	//search info specified			
			//find item in local DB by barcode or control key
			if($_REQUEST['searchField'] == 'barcode') {	//get by barcode
				$phys_item = new physicalCopy();
				if($phys_item->getByBarcode($_REQUEST['searchTerm'])) {
					$item->getItemByID($phys_item->getItemID());
				}
			}
			else {	//get by local control
				$item->getItemByLocalControl($_REQUEST['searchTerm']);
			}					
		}
		
		if(!empty($_REQUEST['request_id'])) {	//processing request, get info out of DB
			$request = new request();
			if($request->getRequestByID($_REQUEST['request_id'])) {
				//init reserveItem object
				$request->getRequestedItem();				
				
				//alert if returned is different than item attached to request
				if (!is_null($item->itemID) && $item != $request->requestedItem)
				{
					Rd_Layout::setMessage('generalAlert', 
						'This search has matched a different item from that requested. '
						. "Before continuing, please verify you are processing \"{$item->getTitle()}\". "
						. 'If this is not correct please stop and contact your local admin.'
					);
				}				
				
			}
		} 
		
		//if item controlKey is set use it to search ILS otherwise use form values
		if (!is_null($item) && $item->getLocalControlKey() <> "")
		{
		 	//set search parameters
			$qryField = 'control';
			$qryValue = $item->getLocalControlKey();
		} else {
			$qryField = (array_key_exists('searchField',$_REQUEST) ? $_REQUEST['searchField'] : '');
			$qryValue = (array_key_exists('searchTerm',$_REQUEST) ?$_REQUEST['searchTerm'] : '');
		}
		//if searching for a physical item, then there may be an ILS record
		//this should return an indexed array, which may be populated w/ data
		if(($cmd=='addPhysicalItem') && !empty($qryValue)) {
			//query ILS
			//$zQry = new zQuery($qryValue, $qryField);
			$zQry = RD_Ils::initILS();
			$search_results = $zQry->search($qryField, $qryValue)->to_a();
		
			//if still do not have an initialized item object
			//try one more time by control key pulled from ILS
			$item_id = $item->getItemID();
			if(empty($item_id)) {
				$item->getItemByLocalControl($search_results['controlKey']);
			}

			//this is not needed at the moment, b/c do not want to show holdings for addPhysicalItem/processRequest
			//but that may change, so it's here, but commented
			$search_results['physicalCopy'] = null;
			//get holdings		
			//$search_results['physicalCopy'] = $zQry->getHoldings($qryField, $qryValue);
		}
		else {
			//otherwise just get a blank $search_results array w/ proper indeces, to avoid "no such index" notices
			$search_results = $item_data;
		}
			
		//pull item values from db if they exist otherwise default to searched values
		//this may still result in a blank initialized array, if there was no item
		
		$item_data['title'] = ($search_results['title'] <> "") ? $search_results['title'] : $item->getTitle();
		$item_data['author'] = ($search_results['author'] <> "") ? $search_results['author'] : $item->getAuthor();
		$item_data['edition'] = ( $search_results['edition'] <> "") ?  $search_results['edition']: $item->getVolumeEdition();
		$item_data['performer'] = ($search_results['performer'] <> "") ? $search_results['performer'] : $item->getPerformer();
		$item_data['volume_title'] = ($search_results['volume_title'] <> "") ? $search_results['volume_title'] : $item->getVolumeTitle();
		$item_data['times_pages'] = ($search_results['times_pages'] <> "") ? $search_results['times_pages'] : $item->getPagesTimes();
		$item_data['source'] = ($search_results['source'] <> "") ? $search_results['source'] : $item->getSource();
		$item_data['controlKey'] = ($search_results['controlKey'] <> "") ? $search_results['controlKey'] : $item->getLocalControlKey();
		$item_data['OCLC'] = ($search_results['OCLC'] <> "") ? $search_results['OCLC'] : $item->getOCLC();		
		$item_data['ISSN'] = ($search_results['ISSN'] <> "") ? $search_results['ISSN'] : $item->getISSN();
		$item_data['ISBN'] = ($search_results['ISBN'] <> "") ? $search_results['ISBN'] : $item->getISBN();
		$item_data['item_group'] = $item->getItemGroup();
		$item_data['home_library'] = $item->getHomeLibraryID();
		$item_data['selected_owner'] = $item->getPrivateUserID();
		$item_data['notes'] = $item->getNotes();
		$item_data['url'] = $item->getURL();
		$item_data['is_local_file'] = $item->isLocalFile();
		$item_data['physicalCopy'] = $search_results['physicalCopy'];
		$item_data['loan_period'] = (array_key_exists('loan_period', $_REQUEST) ? $_REQUEST['loan_period'] : '');
		if(array_key_exists('success', $search_results)){
			$item_data['success'] = $search_results['success'];
		}
		//pass on the item_id in case there was a valid DB record
		$item_data['item_id'] = $item->getItemID();
		return $item_data;
	}
}