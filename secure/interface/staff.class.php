<?php
/*******************************************************************************
staff.class.php
Staff Interface

Created by Jason White (jbwhite@emory.edu)
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
require_once(APPLICATION_PATH . '/interface/instructor.class.php');
require_once(APPLICATION_PATH . '/classes/request.class.php');
require_once(APPLICATION_PATH . '/classes/requestCollection.class.php');
require_once(APPLICATION_PATH . '/classes/courseInstance.class.php');

class staff extends instructor
{
	var $sp;

	function staff($userName=null)
	{
		if (!is_null($userName)) {
			$this->getUserByUserName($userName);
		}
	}


	/**
	* @return return array of classes
	* @param $courseID - courseID to retrieve course instances for
	* @param $instructorID (optional) - instructor to retrieve course instances for
	* @desc return all classes for a given course, or given course and instructor
	*/
	function getCourseInstancesByCourse($courseID,$instructorID=null)
	{
		global $g_dbConn, $g_permission;


		if (!$instructorID) {
			$sql = "SELECT DISTINCT course_instance_id "
				.	   "FROM course_aliases  "
				.	   "WHERE course_id = ?";

			$rs = $g_dbConn->query($sql, array($courseID));
			if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }

		} else {

			$sql = "SELECT DISTINCT ca.course_instance_id "
				.	   "FROM course_aliases as ca  "
				.	   "JOIN access as a ON ca.course_alias_id = a.alias_id "
				.	   "WHERE ca.course_id = ? "
				.	   "AND a.user_id = ? "
				.      "AND a.permission_level = ".$g_permission['instructor']." ";

			$rs = $g_dbConn->query($sql, array($courseID, $instructorID));
			if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }
		}

		$tmpArray = array();
		while($row = $rs->fetch(PDO::FETCH_NUM))
		{
			$ci = new courseInstance($row[0]);
			$ci->getPrimaryCourse();
			$ci->getInstructors();
			$tmpArray[] = $ci;
		}
		return $tmpArray;
	}

	function selectUserForAdmin($userClass, $cmd)
	{
		$subordinates = common_getUsers('instructor');

		echo "<table width=\"90%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\" align=\"center\">\n";
		echo "	<tbody>\n";
		echo "		<tr><td width=\"140%\"><img src=\"../images/spacer.gif\" width=\"1\" height=\"5\"> </td></tr>\n";
		echo "		<tr>\n";
        echo "			<td align=\"left\" valign=\"top\">\n";
        echo "				<table border=\"0\" align=\"center\" cellpadding=\"10\" cellspacing=\"0\">\n";
		echo "					<tr align=\"left\" valign=\"top\" class=\"headingCell1\">\n";
        echo "						<td width=\"50%\">Search by Instructor </td><td width=\"50%\">Search by Department</td>\n";
        echo "					</tr>\n";

        echo "					<tr>\n";
        echo "						<td width=\"50%\" align=\"left\" valign=\"top\" class=\"borders\" align=\"center\" NOWRAP>\n";

        echo "							<form method=\"post\" action=\"index.php\" name=\"frmReserveItem\">\n";
        //if (!is_null($courseInstance)) echo "					<input type=\"hidden\" name=\"ci\" value=\"$courseInstance\">\n";
    	echo "								<input type=\"hidden\" name=\"cmd\" value=\"$cmd\">\n";
		echo "								<input type=\"hidden\" name=\"u\" value=\"".$this->getUserID()."\">\n";
		echo "								<input type=\"submit\" name=\"Submit2\" value=\"Admin Your Classes\">\n";
		echo "							</form>\n";
        echo "							<br>\n";
        echo "							<form method=\"post\" action=\"index.php\" name=\"frmReserveItem\">\n";
    	//if (!is_null($courseInstance)) echo "					<input type=\"hidden\" name=\"ci\" value=\"$courseInstance\">\n";
        echo "								<input type=\"hidden\" name=\"cmd\" value=\"$cmd\">\n";
		echo "								<select name=\"u\">\n";
	    echo "									<option value=\"--\" selected>Choose an Instructor\n";
        foreach($subordinates as $subordinate)
		{
			echo "									<option value=\"" . $subordinate['user_id'] . "\">" . $subordinate['full_name'] . "</option>\n";
		}

        echo "								</select>\n";
        //echo "								<br>\n";
        //echo "								<br>\n";
        echo "								<input type=\"submit\" name=\"Submit2\" value=\"Select Instructor\">\n";
        echo "							</form>\n";
        echo "						</td>\n";
        echo "						<td width=\"50%\" align=\"left\" valign=\"top\" class=\"borders\" align=\"center\" NOWRAP>&nbsp;\n";
        echo "						</td>\n";
        echo "					</tr>\n";
        echo "				</table>\n";
		echo "			</td>\n";
		echo "		</tr>\n";
		echo "	</tbody>\n";
		echo "</table>\n";

	}


	//We can not extend multiple classes so the follow are duplicated in the custodian class

	function createSpecialUser($userName, $email, $date=null)
	{
		$this->sp = new specialUser();
		return $this->sp->createNewSpecialUser($userName, $email, $date);
	}

	function resetSpecialUserPassword($userName)
	{
		$this->sp = new specialUser();
		return $this->sp->resetPassword($userName);
	}

	function getSpecialUsers()
	{
		//this function is duplicated in the custodian class
		global $g_dbConn;

		$sql = "SELECT sp.user_id FROM special_users as sp JOIN users as u ON sp.user_id = u.user_id ORDER BY u.username";
		$rs = $g_dbConn->query($sql);

		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }

		$tmpArray = array();
		while($row = $rs->fetch(PDO::FETCH_NUM))
		{
			$tmpArray[] = new user($row[0]);
		}
		return $tmpArray;
	}

	/**
	* @return requestCollection
	* @param unit default=all unit to get reserves for
	* @desc get all unprocessed requests
	*/
	function getRequests($unit='all', $status="all", $sort=null, $dir='asc')
	{
		global $g_dbConn, $g_permission;
        if('' == trim($dir)){
            $dir = 'asc';
        }
		
        $sql = "SELECT DISTINCT r.request_id, d.abbreviation, c.course_number, res.status, r.type, r.date_requested, r.date_desired "
			.  "FROM requests AS r "
			.  	"JOIN items AS i ON r.item_id = i.item_id AND r.date_processed IS NULL "
			.	"JOIN reserves AS res ON r.reserve_id = res.reserve_id "
			.	"JOIN course_instances AS ci ON r.course_instance_id = ci.course_instance_id "
			.	"JOIN course_aliases AS ca ON ci.primary_course_alias_id = ca.course_alias_id "
			.  	"JOIN courses AS c ON ca.course_id = c.course_id "
			.  	"JOIN departments AS d ON c.department_id = d.department_id AND d.status IS NULL "
			.  	"JOIN libraries AS l ON i.home_library = l.library_id " //the old way... "d.library_id = l.library_id "
			.	"JOIN access AS a ON ca.course_alias_id = a.alias_id "
			.	"JOIN users AS u on a.user_id = u.user_id AND a.permission_level = " . $g_permission['instructor'] . " " ;

			
		if ($unit != 'all')
		{
			$sql .=  "WHERE "
				 .  	"CASE "
				 .  		"WHEN i.item_group = 'ELECTRONIC'  THEN l.monograph_library_id  = $unit AND r.type = 'SCAN' "
				 .  		"WHEN i.item_group = 'MONOGRAPH'  THEN l.monograph_library_id  = $unit "
				 .  		"WHEN i.item_group = 'MULTIMEDIA' THEN l.multimedia_library_id = $unit "
				 .			"ELSE l.library_id = $unit "
				 .	"END "
			;
		}		
		
		if ($status != 'all'){	$sql .= " AND res.status = '$status' ";	}
		
		switch ($sort)
		{
			case "instructor":
				$sql .= " ORDER BY u.last_name $dir";
			break;
			
			case "class":
				$sql .= " ORDER BY d.abbreviation $dir, c.course_number $dir";
			break;
			
			case "type":
				$sql .= " ORDER BY r.type $dir";
			break;
			
			case "requested":
				$sql .= " ORDER BY r.date_requested $dir";
			break;
			
			case "needed":
				$sql .= " ORDER BY r.date_desired $dir";
			break;
			
			case "semester":
				$sql .= " ORDER BY ci.year, ci.term $dir";
			break;
			
			case "date":
			default:
				$sql .= " ORDER BY r.request_id $dir";					
		}

		$rs = $g_dbConn->query($sql);
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }

		$tmpArray = new requestCollection();
		while ($row = $rs->fetch(PDO::FETCH_NUM))
		{
			$tmpRequest = new request($row[0]);
				$tmpRequest->getRequestedItem();
				$tmpRequest->requestedItem->getPhysicalCopy();
				
				$tmpRequest->getRequestingUser();
				$tmpRequest->getReserve();
				
				try{
					$tmpRequest->getCourseInstance();
					$tmpRequest->courseInstance->getPrimaryCourse();
					$tmpRequest->courseInstance->getCrossListings();				
					$tmpRequest->courseInstance->getInstructors();
					$tmpRequest->courseInstance->getCrossListings(); //#TODO why is this here twice... ?
				} catch (Rd_Exception $e){
					//#TODO this maybe should raise an error to the staff user...
				}
				$tmpArray[] = $tmpRequest;
		}
		return $tmpArray;
	}

	/**
	* @return void
	* @param $barcode, $copy, $borrowerID, $courseID, $reservesDesk, $circRule, $altCirc, $expiration
	* @desc create the ILS record
	*/
	function createILS_record($barcode, $copy, $borrowerID, $libraryID, $term, $circRule, $altCirc, $expiration)
	{
		global $g_reserveScript, $g_catalogName;
		//#TODO #2.1.0 this code creates a reserve control record in Sirsi... it should be moved to the Ils_Sirsi object.
		$reservesDesk = new library($libraryID);

		//$desk = $reservesDesk->getReserveDesk();
		$desk = $reservesDesk->getILS_prefix();
		$course = strtoupper($reservesDesk->getILS_prefix() . $term);

		list($Y,$M,$D) = explode('-', $expiration);
		$eDate = "$M/$D/$Y";
	    Rd_Debug::out($g_reserveScript . "?itemID=$barcode&borrowerID=$borrowerID&courseID=$course&reserve_desk=$desk&circ_rule=$circRule&alt_circ=$altCirc&expiration=$eDate&cpy=$copy<br/>");

		//#TODO nope curl it instead
		$fp = fopen($g_reserveScript . "?itemID=$barcode&borrowerID=$borrowerID&courseID=$course&reserve_desk=$desk&circ_rule=$circRule&alt_circ=$altCirc&expiration=$eDate&cpy=$copy", "r");

        $rs = array();
        while (!feof ($fp)) {
        	array_push($rs, @fgets($fp, 1024));
        }
        $returnStatus = join($rs, "");

        $returnStatus = preg_replace('/<head>.*<\/head>/i', '', $returnStatus);
        $returnStatus = preg_replace('/<[A-z]*>/', '', $returnStatus);
        $returnStatus = preg_replace('/<\/[A-z]*>/', '', $returnStatus);

        $returnStatus = preg_replace('/<!.*\">/', '', $returnStatus);
        $returnStatus = preg_replace("/\n/", '', $returnStatus);

        if(!preg_match('/outcome=OK/i', $returnStatus)){
        	return "There was a problem setting the location and circ-rule in {$g_catalogName}. <br/>{$g_catalogName} returned:  {$returnStatus}.";
        } else
        	return "Location and circ-rule have been successfully set in {$g_catalogName}.";
	}

	function getSpecialUserMsg() { return $this->sp->getMsg(); }

	function getStaffLibrary()
	{
		global $g_dbConn;
		
		if (!is_null($this->getUserID()))
		{
			$sql = "SELECT library_id "
				.  "FROM staff_libraries "
				.  "WHERE user_id = ?";
	
			$rs = $g_dbConn->query($sql, $this->getUserID());		
			if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }
	
			$row = $rs->fetch(PDO::FETCH_NUM);
			return $row[0];
		} else {
			return 1;  //default to first Library
		}
	}

	function assignStaffLibrary($libraryID)
	{
		$sql_find = "SELECT library_id "
			.  "FROM staff_libraries "
			.  "WHERE user_id = ?";
					
		$sql_in = "INSERT INTO staff_libraries (library_id, permission_level_id, user_id) VALUES (?, ?, ?)";
		$sql_up = "UPDATE staff_libraries SET library_id = ?, permission_level_id = ? WHERE user_id = ?";

		$rs = Rd_Pdo::query($sql_find, $this->getUserID());
		if (Rd_Pdo_PearAdapter::isError($rs)) { 
			trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); 
		}
			
		if ($rs->rowCount() < 1) {
			$rs = Rd_Pdo::query($sql_in, array(
				$libraryID, 
				$this->getRole(), 
				$this->getUserID())
			);	
		} else {
			$rs = Rd_Pdo::query($sql_up, array(
				$libraryID, 
				$this->getRole(), 
				$this->getUserID())
			);	
		}	

		if (Rd_Pdo_PearAdapter::isError($rs)) { 
			trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); 
		}
		return true;
	}		
}
