<?php
/*******************************************************************************
noteDisplayer.class.php


Created by Dmitriy Panteleyev (dpantel@emory.edu)
Modified by NCSU Libraries, NC State University. Modifications by Karl Doerr and Troy Hurteau (libraries.opensource@ncsu.edu).

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
require_once(APPLICATION_PATH . '/lib/Rd/Displayer/Base.php');

class noteDisplayer extends Rd_Displayer_Base {

	/**
	 * @return void
	 * @param array $notes Array of note objects
	 * @param string $obj_type Type of object these notes are connected to (`reserve`, `item`, etc)
	 * @param int $obj_id ID of the object
	 * @param boolean $include_add_button If true, will include that add-note button
	 * @desc Displays notes in a table with javascript links to edit and/or delete each note; NOTE: requires basicAJAX.js, notes_ajax.js
	 */
	public static function displayNotesBlockAJAX($notes, $obj_type, $obj_id, $include_add_button=true) {
?>
		<div id="notes_content">
			<?php self::displayNotesContentAJAX($notes, $obj_type, $obj_id); //notes content ?>
		</div>
		
		<?php self::displayNotesFormAJAX($obj_type, $obj_id); //include the add/edit-note form ?>
		
<?php
		if($include_add_button) {	//include add-note button?
			self::displayAddNoteButtonAJAX();
		}
	}
	
	
	/**
	 * @return void
	 * @param array $notes Array of note objects
	 * @param string $obj_type Type of object these notes are connected to (`reserve`, `item`, etc)
	 * @param int $obj_id ID of the object
	 * @desc Displays notes in a table with javascript links to edit and/or delete each note
	 */
	public static function displayNotesContentAJAX(&$notes, $obj_type, $obj_id) {
		global $g_notetype, $g_permission;
		$u = Account_Rd::getUserInterface();
		if(empty($notes)) {
			return;
		}
		
		//some notes should be shown only to staff
		$restricted_note_types = array($g_notetype['staff'], $g_notetype['content']);
		
		//display notes
?>
		<table border="0" cellpadding="2" cellspacing="0">
<?php	
		foreach($notes as $note):
			if(in_array($note->getType(), $restricted_note_types) && ($u->getRole() < $g_permission['staff'])) {
				continue;	//skip the note if it is restricted and user is less than staff
			}
?>
			<tr valign="top">
				<td align="right" style="width:100px;">
					<strong><?php print($note->getType()); ?> Note:</strong>
					<br />
					<a href="#" onclick="javascript: notes_show_form(<?php print($note->getID()); ?>, '<?php print(str_replace('"', "'", $note->getText())); ?>', '<?php print($note->getType()); ?>'); return false;">edit</a> | <a href="#" onclick="javascript: notes_delete_note('<?php print($obj_type); ?>', <?php print($obj_id); ?>, <?php print($note->getID()); ?>); return false;">delete</a>&nbsp;
				</td>
				<td>
					<?php print(stripslashes($note->getText())); ?>
				</td>
			</tr>								
<?php	endforeach; ?>

		</table>
<?php
}


	/**
	 * @return void
	 * @param string $referrer_string String identifying object and its ID. ex: 'reserveID=5' or 'itemID=10'. Note: the addNote handler must recognize the object
	 * @desc outputs HTML for display of addNote button
	 */
	public static function displayAddNoteButtonAJAX() {
?>
		<input type="button" value="Add Note" onclick="javascript: notes_show_form('', '', ''); return false;" />
<?php
	}
	

	/**
	 * @return void
	 * @param string $obj_type Type of object these notes are connected to (`reserve`, `item`, etc)
	 * @param int $obj_id ID of the object
	 * @desc Adds the note form to a page (hidden) and the add-note button
	 */
	public static function displayNotesFormAJAX($obj_type, $obj_id) {
		global $g_notetype, $g_permission;
		
		$u = Rd_Registry::get('root:userInterface');
		//filter the type of note that may be added, based on object type
		$available_note_types = array();
		switch($obj_type) {
			case 'item':
				$available_note_types = array('content', 'staff', 'copyright');
			break;			
			case 'reserve':
				$available_note_types = array('instructor', 'content', 'staff', 'copyright');
			break;			
			case 'copyright':
				$available_note_types = array('copyright');
			break;
		}
		
		//filter allowed note types based on permission level
		$restricted_note_types = array('content', 'staff', 'copyright');
		//filter out restricted notes if role is less than staff
		if($u->getRole() < $g_permission['staff']) {
			$available_note_types = array_diff($available_note_types, $restricted_note_types);
		}
?>
			<div id="noteform_container" class="noteform_container" style="display:none;">
				<div id="noteform_bg" class="noteform_bg"></div>
				<div id="noteform" class="noteform"">
					<form id="note_form" name="note_form" onsubmit="javascript: return false;">
						<input type="hidden" id="note_id" name="note_id" value="" />
						
						<strong><big>Add/Edit Note</big></strong>
						<br />
						<textarea id="note_text" name="note_text"></textarea>
						<small>
							<strong>Note Type:</strong>
<?php
		$first = true;
		foreach($available_note_types as $note_type):
			$checked = $first ? ' checked="true"' : '';
			$first = false;			
?>
							<input type="radio" id="note_type_<?php print($g_notetype[$note_type]); ?>" name="note_type" value="<?php print($g_notetype[$note_type]); ?>"<?php print($checked); ?> /><?php print(ucfirst(strtolower($g_notetype[$note_type]))); ?>
<?php	endforeach; ?>
						</small>
						<br />
						<div style="text-align: center">
							<input type="button" value="Cancel" onclick="javascript: notes_hide_form(); return false;" />
							<input type="button" value="Save" onclick="javascript: notes_save_note('<?php print($obj_type); ?>', <?php print($obj_id); ?>, this.form); return false;" />					
						</div>
					</form>		
				</div>
			</div>			
<?php	
}


	/**
	 * @return void
	 * @param array $notes Reference to an array of note objects
	 * @param string $referrer_string Query sub-string to be used for the DELETE links.  ex: 'reserveID=5' or 'itemID=10'
	 * @param boolean $use_ajax_delete_links (optional) If true `delete` links will send click events to a `delete_note(note_id)` JS function
	 * @desc outputs HTML for display of notes edit boxes in item/reserve edit screens
	 */
	public static function displayEditNotes(&$notes, $referrer_string, $use_ajax_delete_links=false) {
		global $g_notetype, $g_permission;
		$u = Account_Rd::getUserInterface();
		if(empty($notes)) {
			return;
		}
		
		//some notes should be shown only to staff
		$restricted_note_types = array($g_notetype['staff'], $g_notetype['copyright'], $g_notetype['content']);
?>
		<table border="0" cellpadding="2" cellspacing="0">
<?php		
		foreach($notes as $note):
			if(in_array($note->getType(), $restricted_note_types) && ($u->getRole() < $g_permission['staff'])) {
				continue;	//skip the note if it is restricted and user is less than staff
			}
?>
			<tr>
				<td align="right">
					<strong><?php print($note->getType()); ?> Note:</strong>
					<br />
<?php		if($use_ajax_delete_links): ?>
					[<a href="#" onclick="javascript: notes_delete_note(<?php print($note->getID()); ?>); return false;">Delete this note</a>]&nbsp;
<?php		else: ?>
					[<a href="index.php?cmd=<?php print($_REQUEST['cmd']); ?>&amp;<?php print($referrer_string); ?>&amp;deleteNote=<?php print($note->getID()); ?>">Delete this note</a>]&nbsp;
<?php		endif; ?>
				</td>
				<td>
					<textarea name="notes[<?php print($note->getID()); ?>]" cols="50" rows="3" wrap="virtual"><?php print(stripslashes($note->getText())); ?></textarea>
				</td>
			</tr>								
<?php	endforeach; ?>

		</table>
<?php
	}
	
	
	/**
	 * @return void
	 * @param string $referrer_string String identifying object and its ID. ex: 'reserveID=5' or 'itemID=10'. Note: the addNote handler must recognize the object
	 * @desc outputs HTML for display of addNote button
	 */
	public static function displayAddNoteButton($referrer_string) {
?>
		<input type="button" name="addNote" value="Add Note" onClick="openWindow('no_table=1&amp;cmd=addNote&amp;<?php print($referrer_string); ?>','width=600,height=400');">
<?php
	}
	
	
	/**
	 * @return void
	 * @param array $notes Reference to an array of note objects
	 * @desc outputs HTML for display of notes in reserve listings
	 */
	public static function displayNotes(&$notes) {
		global $g_notetype, $g_permission, $cmd;
		
		$u = Rd_Registry::get('root:userInterface');
		
		if(empty($notes)) {
			return;
		}
		
		$r = ($cmd == 'previewStudentView') ? $g_permission['student'] : $u->getRole(); //hack hack hackety hack
		
		foreach($notes as $note):
			switch ($r)
			{
				case $g_permission['admin']:			
					$restricted_note_types = array();
				break;								
								
				case $g_permission['staff']:			
					$restricted_note_types = array($g_notetype['staff']);
				break;
				
				case $g_permission['instructor']:			
				case $g_permission['proxy']:			
					$restricted_note_types = array($g_notetype['staff'], $g_notetype['copyright']);
				break;				
				
				case $g_permission['student']:			
				default:
					$restricted_note_types = array($g_notetype['staff'], $g_notetype['copyright']);
				break;				
			}
			
			if(in_array($note->getType(), $restricted_note_types))
			{
				continue;	//skip the note if it is restricted and user is less than staff
			}			
?>
		<div class="note">
		<span class="noteType"><?php print(ucfirst($note->getType())); ?> Note:</span>&nbsp;
		<span class="noteText"><?php print(stripslashes($note->getText())); ?></span>
		</div>
<?php
		endforeach;	
	}
	
	
	/**
	* @return void
	* @param $user, $reserveID
	* @desc display Add Note form
	*/
	static function displayAddNoteScreen($user, $hidden_fields)
	{
		global $g_permission, $g_notetype;

		echo "<form name=\"addNote\" action=\"index.php?no_table=1&amp;cmd=addNote\" method=\"post\">\n";
		
		//show hidden fields
		self::displayHiddenFields($hidden_fields);

		echo '<center>';
		echo '<table width="400" border="0" cellspacing="0" cellpadding="0" style="margin-top:30px;">';
  		echo '	<tr><td align="left" valign="top"><h1>ReservesDirect</h1></td></tr>';
		echo '	<tr><td align="left" valign="top" style="padding-bottom:30px;"><h2>Add Note</h2></td></tr>';
 // 		echo '	<tr><td align="left" valign="top">&nbsp;</td></tr>';
  //		echo '	<tr><td align="left" valign="top">&nbsp;</td></tr>';
  		if ($user->getRole() >= $g_permission['staff']) {
  			echo '	<tr>';
  			echo '  	<td align="left" valign="top">';
  			echo '			<table width="100%" border="0" cellspacing="0" cellpadding="0">';
  			echo '  			<tr>';
  			echo '  				<td width="50%" class="headingCell1">Note Options</td>';
  			echo '      			<td>&nbsp;</td>';
  			echo '				</tr>';
  			echo '			</table>';
  			echo '		</td>';
  			echo '	</tr>';
  			echo '	<tr>';
  			echo '  	<td align="left" valign="top">';
  			echo '			<table width="100%" border="0" cellpadding="3" cellspacing="0" class="borders">';
  			echo '    			<tr align="left" valign="top" bgcolor="#CCCCCC">';
  			echo '      			<td width="22%" valign="top"><p class="strong">Note Type:<br>';
  			echo '	    			<span class="small-x">(This will show as the title of the note for editing';
			echo '				    purposes.)</span></p>';
			echo '					</td>';
        	echo '					<td width="78%" align="left"><p>';
       		echo '					<label><input type="radio" name="noteType" value="'.$g_notetype['content'].'" checked>Content Note</label><br>';
       		
       		//only allow instructor notes if editing reserve
       		if(!empty($hidden_fields['reserveID'])) {
       			echo '					<label><input type="radio" name="noteType" value="'.$g_notetype['instructor'].'">Instructor Note</label><br>';
       		}
       		
       		echo '					<label><input type="radio" name="noteType" value="'.$g_notetype['staff'].'">Staff Note</label><br>';
			echo '					<label><input type="radio" name="noteType" value="'.$g_notetype['copyright'].'">Copyright Note</label><br>';
			echo '					</p></td>';
			echo '				</tr>';
			echo '			</table>';
			echo '		</td>';
  			echo '	</tr>';
		} else {
			echo '					<input type="hidden" name="noteType" value="'.$g_notetype['instructor'].'">';
		}

  		echo '	<tr>';
  		echo '		<td align="left" valign="top">&nbsp;</td>';
  		echo '	</tr>';
  		echo '	<tr>';
    	echo '		<td align="left" valign="top">';
    	echo '			<table width="100%" border="0" cellspacing="0" cellpadding="0">';
      	echo '				<tr>';
        echo '					<td width="50%" class="headingCell1">Note Text</td>';
        echo '					<td>&nbsp;</td>';
      	echo '				</tr>';
    	echo '			</table>';
    	echo '		</td>';
  		echo '	</tr>';
  		echo '	<tr>';
    	echo '		<td align="left" valign="top" class="borders">';
    	echo '			<table width="100%" border="0" cellspacing="0" cellpadding="3">';
      	echo '				<tr>';
        echo '					<td align="center" valign="top"><textarea name="noteText" cols="45"></textarea></td>';
      	echo '				</tr>';
    	echo '			</table>';
    	echo '		</td>';
  		echo '	</tr>';

  		echo "    <tr><td><img src=\"public/images/spacer.gif\" width=\"1\" height=\"15\"></td></tr>\n";
  		echo "    <tr>\n";
		echo "    	<td align=\"center\"><input type=\"submit\" value=\"Save Note\"></td>\n";
		echo "	</tr>\n";
		echo "    <tr><td><img src=\"public/images/spacer.gif\" width=\"1\" height=\"15\"></td></tr>\n";
		echo '</table>';

		echo '</td>';
		echo '</tr>';
		echo '</table>';
		echo "</form>\n";
		echo "</center>";
	}


	static function displaySuccess($noteID)
	{
		echo "<script language=\"JavaScript\">this.window.opener.newWindow_returnValue='$noteID';</script>\n"; //pass value to parent window

		echo "<table width=\"400\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\" align=\"center\" style=\"margin-top:30px;\">\n"
		.	 "	<tbody>\n"
		.	 "	<tr><td align=\"left\" valign=\"top\"><h1>ReservesDirect</h1></td></tr>\n"
		.	 "	<tr><td align=\"left\" valign=\"top\" style=\"padding-bottom:30px;\"><h2>Add Note</h2></td></tr>\n"
		.	 "		<tr><td width=\"100%\"><img src=\"public/images/spacer.gif\" width=\"1\" height=\"5\"> </td></tr>\n"
		.	 "		<tr>\n"
	    .	 "			<td align=\"left\" valign=\"top\" class=\"borders\" style=\"text-align:center; padding:5px 15px 10px 15px;\">\n"
	    .	 "				<p><strong>You have successfully added a note.</strong></p>\n"
	    .	 "						<p>Your note will not appear until you Save Changes to the item or heading you are working on.</p>\n"
		.	 "						<p>Please close this window to Continue</p>\n"
		.	 "						<p><input type=\"button\" value=\"Close Window\" onClick=\"window.close();\"></p>\n"
		.	 "			</td>\n"
		.	 "		</tr>\n"
		.	 "		<tr><td><img src=\"public/images/spacer.gif\" width=\"1\" height=\"15\"></td></tr>\n"
		.	 "	</tbody>\n"
		.	 "</table>\n"
		;
	}
}

