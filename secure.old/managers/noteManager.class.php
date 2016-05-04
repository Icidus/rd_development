<?php
/*******************************************************************************
noteManager.class.php


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
require_once(APPLICATION_PATH . '/common.inc.php');
require_once(APPLICATION_PATH . '/classes/note.class.php');
require_once(APPLICATION_PATH . '/classes/reserves.class.php');
require_once(APPLICATION_PATH . '/classes/reserveItem.class.php');
require_once(APPLICATION_PATH . '/classes/copyright.class.php');
require_once(APPLICATION_PATH . '/displayers/noteDisplayer.class.php');

class noteManager
{
	public $user;
	public $displayClass;
	public $displayFunction;
	public $argList;

	function display()
	{
		$args = array();
		foreach($this->argList as $index=>$value){
			$args[$index] =& $this->argList[$index];
		}
		if (is_callable(array($this->displayClass, $this->displayFunction))){
			call_user_func_array(array($this->displayClass, $this->displayFunction), $args);
		}
	}


	function noteManager($cmd, $user)
	{
		global $ci, $g_notetype;
		$this->displayClass = "noteDisplayer";

		switch ($cmd)
		{
			default:
			case 'addNote':
				$this->displayFunction = "displayAddNoteScreen";
				$this->argList = array($user, array('cmd'=>'saveNote', 'reserveID'=>$_REQUEST['reserveID'], 'itemID'=>$_REQUEST['itemID']));
			break;

			case 'saveNote':			
				if(!empty($_REQUEST['reserveID'])) {	//editing item+reserve
					self::saveNote('reserve', $_REQUEST['reserveID'], $_REQUEST['noteText'], $_REQUEST['noteType']);
				}
				elseif(!empty($_REQUEST['itemID'])) {	//editing item only
					self::saveNote('item', $_REQUEST['itemID'], $_REQUEST['noteText'], $_REQUEST['noteType']);
				}
				else {	//no IDs set, error
					break;
				}

				$this->displayFunction = "displaySuccess";
				$this->argList = array();
			break;
		}
	}
	
	
	/**
	 * @return array Array of note objects
	 * @param string $obj_type Type of object containing the notes - 'reserve', 'item', etc
	 * @param int $obj_id ID of the object
	 * @param boolean $get_item_notes_for_reserve (optional) If the object type is 'reserve' and this is true, will fetch item notes in addition to reserve notes; defaults to true
	 * @desc Fetches an array of note objects for the specified object
	 */	
	public static function fetchNotesForObj($obj_type, $obj_id, $get_item_notes_for_reserve=true) {
		$notes = array();
		
		if(empty($obj_type) || empty($obj_id)) {
			return $notes;
		}
		
		//item notes or reserve notes?
		switch($obj_type) {
			case 'reserve':
				//init reserve obj
				$reserve = new reserve($obj_id);
				//grab notes and include item notes if requested
				$notes = $reserve->getNotes($get_item_notes_for_reserve);		
			break;
			
			case 'item':
				//init new rItem obj
				$item = new reserveItem($obj_id);
				//get notes
				$notes = $item->getNotes();
			break;
		}
		
		return $notes;
	}
	
	
	/**
	 * @return void
	 * @param string $obj_type Type of object containing the notes - 'reserve', 'item', etc
	 * @param int $obj_id ID of the object
	 * @param string $note_text Note text
	 * @param string $note_type Note type
	 * @param int $note_id (optional) Note ID
	 * @desc Creates or edits a note; if the note_id is set, this note will be edited, else a new note will be created
	 */
	public static function saveNote($obj_type, $obj_id, $note_text, $note_type, $note_id=null) {
		global $g_notetype;
		
		if(empty($obj_type) || empty($obj_id) || empty($note_text)) {
			return;
		}
		
		//item notes or reserve notes?
		switch($obj_type) {
			case 'item':
				//init new rItem obj
				$item = new reserveItem($obj_id);
			break;
			
			case 'reserve':
				//init reserve obj
				$reserve = new reserve($obj_id);
				//get the item
				$item = new reserveItem($reserve->getItemID());
			break;			
		}
		
		//add/edit instructor note to reserve
		if(($note_type==$g_notetype['instructor']) && ($reserve instanceof reserve)) {
			$reserve->setNote(trim($note_text), $note_type, $note_id);
		} elseif($item instanceof reserveItem) {	//add/edit all other types to item
			$item->setNote(trim($note_text), $note_type, $note_id);
		}
	}
	
	
	/**
	 * @return void
	 * @param int $note_id ID of note to delete
	 * @param string $obj_type (optional) Object to witch this note is attached
	 * @param int $obj_id (optional) Object id
	 * @desc Deletes the specified note
	 */
	public static function deleteNote($note_id, $obj_type=null, $obj_id=null) {
		global $g_notetype;
		
		if(!empty($note_id)) {
			$note = new note($note_id);
			if($note->getID()) {
				
#########################################
#	HIDE COPYRIGHT UNTIL FURTHER NOTICE #			
#########################################	
#				if($note->getType() == $g_notetype['copyright']) {
#					//attempt to log it
#					if(($obj_type=='copyright') && !empty($obj_id)) {
#						$copyright = new Copyright($obj_id);
#						$copyright->log('delete note', '#'.$note->getID());
#					}
#				}
#########################################

				$note->destroy();				
			}
		}		
	}
}

