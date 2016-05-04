<?php

require_once(APPLICATION_PATH . '/classes/reserveItem.class.php');
require_once(APPLICATION_PATH . '/classes/physicalCopy.class.php');
require_once(APPLICATION_PATH . '/lib/Rd/Pdo.php');
Rd_Pdo::autoload();

class Rd_Synchronizer_Item {
	
	protected static function _normalizeCatKeys($catKeys){
		if(!is_array($catKeys) && (!is_object($catKeys) || !method_exists('__toArray', $catKeys))){
			return explode('+', str_replace(' ', '+', $catKeys));
		}
		return $catKeys;
	}
	
	public static function importCatKeys($catKeys, $options = array()){
		$catKeys = self::_normalizeCatKeys($catKeys);
		$verbose = !array_key_exists('verbose', $options) || $options['verbose'];
		$duplicate = array_key_exists('duplicate', $options) && $options['duplicate'];
		$allowStale = !array_key_exists('allowStale', $options) || $options['allowStale'];
		$resultData = array(
			'importedAllItems' => true,
			'importedItems' => array()
		);
		foreach($catKeys as $catKey){
			$newResultData = self::importCatKey($catKey, array('duplicate'=>$duplicate,'allowStale'=>$allowStale));
			if(array_key_exists('success', $newResultData) 
				&& $newResultData['success']){
				$resultData['importedItems'][] = $newResultData;
			} else if($allowStale) {
				$resultData['importedItems'][] = $newResultData;
			}
			$resultData['importedAllItems'] = $resultData['importedAllItems'] 
				&& array_key_exists('success', $newResultData) 
				&& $newResultData['success'];
		}
		return($verbose ? $resultData : $resultData['importedAllItems']);
	}
	
	public static function importCatKey($catKey, $options = array()){
		$forceImport = array_key_exists('duplicate', $options) && $options['duplicate'];
		$allowStale = !array_key_exists('allowStale', $options) || $options['allowStale'];
		$searchResult = self::searchItem('catkey', $catKey, $forceImport);
		if(array_key_exists('ils_data',$searchResult) 
			&& array_key_exists('success', $searchResult['ils_data']) 
			&& $searchResult['ils_data']['success']
		){
			$importResult = self::storeItem($searchResult['ils_data'],false, $forceImport);
			$searchResult['success'] = $importResult['success'];
			$searchResult['item_id'] = $importResult['id'];
			if($searchResult['success']){
				return $searchResult;
			}
		} else if ($allowStale){
			$searchResult = self::searchItem('catkey', $catKey);
			$searchResult['success'] = !(
				is_null($searchResult['item_id'])
				|| '' === trim($searchResult['item_id'])
				|| 0 === $searchResult['item_id']
			);
		}

		return $searchResult;
	}
	
	public static function catKeysNotInRd($catKeys){
		$catKeys = self::_normalizeCatKeys($catKeys);
		$returnCatKeys = array();
		$localControlNumberList = '';
		foreach($catKeys as $catKey){
			$catKey = trim(strtoupper($catKey));
			$localControlNumber = intval(
				strpos($catKey,'NCSU') === 0 
				? substr($catKey, 4)
				: $catKey
			);
			if($localControlNumber == 0){
				$returnCatKeys[] = $catKey;
			} else {
				$localControlNumberList .= (
					'' == $localControlNumberList
					? ''
					: ', '	
				) . $localControlNumber;
			}
		}
		$controlNumberQuery = 'SELECT DISTINCT local_control_key '
			. "FROM items WHERE local_control_key IN({$localControlNumberList});";
		$result = Rd_Pdo::query($controlNumberQuery);
		if($result){
			$catKeysInRd = array();
			foreach($result->fetchAll() as $row){
				$catKeysInRd[] = 'NCSU' . trim($row['local_control_key']);
			}
			foreach($catKeys as $catKey){
				$catKey = trim(strtoupper($catKey));
				if(!in_array($catKey, $returnCatKeys)
					&& !in_array($catKey, $catKeysInRd)
				){
					$returnCatKeys[] = $catKey;
				}
			}
		}
		return $returnCatKeys;
	}

	/**
	 * Attempts to find an item in DB and/or (if physical item) in ILS; return array prefilled w/ item data or empty array w/ proper indeces
	 *
	 * @param string $cmd Current cmd
	 * @return array
	 */
	public static function searchItem($term, $value, $forceNew = false) {
		//create a blank array with all the needed indeces
		$item_data = array(
			'item_id' => null,
			'title' => '', 
			'author' => '', 
			'edition' => '', 
			'performer' => '', 
			'times_pages' => '', 
			'volume_title' => '', 
			'source' => '', 
			'controlKey' => '', 
			'selected_owner' => null, 
			'physicalCopy' => null, 
			'OCLC' => '', 
			'ISSN' => '', 
			'ISBN' => '', 
			'item_group' => null, 
			'notes' => null, 
			'home_library' => null, 
			'url' => '', 
			'is_local_file' => false,
			'ils_data' => array()
		);
				
		//decide if item info can be prefilled
		$item_id = null;
		if ($term == 'id') {
			$item_id = $value;
		} 
		
		$item = new reserveItem($item_id);
		
		if(is_null($item->itemID) && !$forceNew) {
			if('barcode' == $term) {
				$phys_item = new physicalCopy();
				if($phys_item->getByBarcode($value)) {
					$item->getItemByID($phys_item->getItemID());
				}
			}
			else if('catkey' == $term) {
				$item->getItemByLocalControl(str_replace('NCSU', '', $value));
			} else {
				$item->getItemByLocalControl($value);
			}					
		}

		$item_data = array(
			'item_id' => $item->getItemID(),
			'title'=> $item->getTitle(),
			'author' => $item->getAuthor(),
			'edition' => $item->getVolumeEdition(),
			'performer' => $item->getPerformer(),
			'volume_title' => $item->getVolumeTitle(),
			'times_pages' => $item->getPagesTimes(),
			'source' => $item->getSource(),
			'controlKey' => $item->getLocalControlKey(),
			'OCLC' => $item->getOCLC(),	
			'ISSN' => $item->getISSN(),
			'ISBN' => $item->getISBN(),
			'item_group' => $item->getItemGroup(),
			'home_library' => $item->getHomeLibraryID(),
			'selected_owner' => $item->getPrivateUserID(),
			'notes' => $item->getNotes(),
			'url' => $item->getURL(),
			'is_local_file' => $item->isLocalFile(),
			'ils_data' => array()
		);
		
		if('catkey' == $term) {
			$zQry = RD_Ils::initILS();
			$item_data['ils_data'] = $zQry->search('catkey', str_replace('NCSU', '', $value))->to_a();
		}
		
		return $item_data;
	}	
	
	/**
	 * Edits or creates a new item, using the addDigital/addPhysical item form ($_REQUEST); Returns item-id
	 *
	 * @return int
	 */
	public static function storeItem($newItemData, $manual = false, $duplicate = false) { 
		$u = Rd_Registry::get('root:userInterface');
		$result = array(
			'success' => false,
			'id' => null,
			'message' => ''
		);
		//when adding a 'MANUAL' physical item, the physical-copy data is hidden, but still passed on by the form
		//make sure that we do not use it
		if(!$manual) {
			unset($newItemData['physicalCopy']); //#TODO protip to future jthurtea: Sirsi Result doesn't even currently populate this attribute...
		}
		if (is_null($newItemData['controlKey'] || '' == trim($newItemData['controlKey']))) { //#TODO this probably should never happen, but just in case...
			$result['message'] = 'Imported item has no control key';
			return $result;
		}
		$item = new reserveItem();
		if(!$duplicate){
			$existingItem = $item->getItemByLocalControl($newItemData['controlKey']); // #TODO ... we can let this slide for now
			if($existingItem) { 
				$result['success'] = true;
				$result['id'] = $existingItem->getItemID();
				$result['message'] = 'Idempotent Action: Using exiting item already imported.';
				return $result;
			}
		}

		$item = new reserveItem();
		$item->createNewItem();	
				
		//add/edit data
		if(isset($newItemData['title'])){
			$item->setTitle($newItemData['title']);
		} 
		if(isset($newItemData['author'])){
			$item->setAuthor($newItemData['author']);
		}
		if(isset($newItemData['edition'])){
			$item->setVolumeEdition($newItemData['edition']);
		}
		if(isset($newItemData['performer'])){
			$item->setPerformer($newItemData['performer']);
		}
		if(isset($newItemData['times_pages'])){
			$item->setPagesTimes($newItemData['times_pages']);
		}
		if(isset($newItemData['volume_title'])) {
			$item->setVolumeTitle($newItemData['volume_title']);
		} 
		if(isset($newItemData['source'])) $item->setSource($newItemData['source']);				
		//#TODO these lookups into $_REQUEST don't belong here.
		if(!is_null($newItemData['controlKey']) && '' != trim($newItemData['controlKey'])){
			$item->setLocalControlKey(trim($newItemData['controlKey']));
		} elseif(isset($_REQUEST['local_control_key'])){
			$item->setLocalControlKey($_REQUEST['local_control_key']);
		}
			//check personal item owner
		if((array_key_exists('personal_item', $_REQUEST) && $_REQUEST['personal_item'] == 'yes') && ($_REQUEST['personal_item_owner'] == 'new') && !empty($_REQUEST['selected_owner']) ) {
			$item->setPrivateUserID($_REQUEST['selected_owner']);
		}		
		if(isset($newItemData['ISBN'])) $item->setISBN($newItemData['ISBN']);
		if(isset($newItemData['ISSN'])) $item->setISSN($newItemData['ISSN']);
		if(isset($newItemData['OCLC'])) $item->setOCLC($newItemData['OCLC']);
		if(isset($newItemData['url']) && '' != trim($newItemData['url'])) $item->setURL($newItemData['url']);
		//#TODO for now everything will initially import as a catalog URL until we get to the next step...
		
		//if adding electronic item, need to process file or link
		if(!$item->isPhysicalItem() && !empty($_REQUEST['documentType'])) {
			if($_REQUEST['documentType'] == 'DOCUMENT') {	//uploading a file
				$file = common_storeUploaded($_FILES['userFile'], $item->getItemID());														
				$file_loc = $file['dir'] . $file['name'] . $file['ext'];
				$item->setURL($file_loc);
				$item->setMimeTypeByFileExt($file['ext']);
			}
			elseif($_REQUEST['documentType'] == 'VIDEO') {
				//error_log("[" . date("F j, Y, g:i a") . "] ". "Got to uploading the video item!" . "\n", 3, $logfile);
				//print_r($_FILES);
				//die;
				$file = common_storeVideo($_FILES['videoFile'], $item->getItemID(), $u, $item->getTitle(), $_REQUEST['times_pages'], $_REQUEST['times_pages2']);
				//$file_loc = $file['dir'] . $file['name'] . $file['ext'];
				
				//This needs to be an html page.. What shall I return??
				$item->setURL($file);
				$item->setMimeTypeByFileExt($file['ext'], true);
			}
			elseif($_REQUEST['documentType'] == 'URL') {	//adding a link
				$item->setURL($_REQUEST['url']);
			}
			//else maintaining the same link; do nothing
		}
		$result['id'] = $item->getItemID();
		$result['success'] = ($result['id'] != '' && !is_null($result['id']) && $result['id'] > 0);
		//return id of item
		return $result;	
	}
	
}