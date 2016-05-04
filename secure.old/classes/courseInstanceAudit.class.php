<?php
/*******************************************************************************
courseInstanceAudit.class.php
Responsible for recording changes for each course instance.

Created by Karl Doerr, modfified by Adam Constabaris, NCSU Libraries, NC State University (libraries.opensource@ncsu.edu).

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
 * courseInstanceAudit
 *
 * @author ajconsta
 */
class courseInstanceAudit {
    
    /**
     * These constants map to the id field in
     * the `target_table_mapping` table.  As new rows are added to that table these
     * constants will need to be updated.  Although this is more brittle than
     * finding these values from the database, it gives us a shot at efficiency
     * while preserving a bit of mnemonic value.
     */
    const TARGET_STUDENT = 3;
    
    const TARGET_INSTRUCTOR = 1;
    
    const TARGET_PROXY = 2;
    
    const TARGET_RESERVE = 4;
    
    const TARGET_HEADING = 5;
    
    const TARGET_CROSSLISTING = 6;
    
    const TARGET_COURSE_INSTANCE = 7;
    
    const TARGET_COURSE_ALIAS = 8;
    
    const EVENT_ADD = 'add';
    
    const EVENT_REMOVE = 'remove';
    
    const EVENT_EDIT = 'edit';


    // not needed, I think
    private function getTargetTypeId($target_type_name) {
        global $g_dbConn;
        $result = $g_dbConn->query("select id FROM target_table_mapping where display_name = ?", $target_type_name);
        if ( Rd_Pdo_PearAdapter::isError($result) ) {
            trigger_error($result->getMessage(), E_USER_ERROR);
        }
        $row = $res->fetchInto();
        $return_value = 0;
        if ( $row ) {
            $return_value = $row[0];
        }
        $result->free();
        return $return_value;
    }

    public function logAction($course_instance_id, $course_alias_id, $target_id, $target_type_id, $target_object, $message, $action, $id=0) {
        global $g_dbConn;

        $u = Rd_Registry::get('userInterface');

        // FIXME: this should probably be a bit less extreme
        if ( is_null($u) || 'Account_Nonuser' == get_class($u) ) {
            @die("Unable to create audit log with unknown user");
        }
        // paranoia
        $ci_id = intval($course_instance_id);


        $serialized_target = json_encode($target_object);

        if($target_type_id == self::TARGET_HEADING && $action == self::EVENT_ADD){
        	$sql_get_heading_record = "SELECT id FROM course_instance_audit WHERE target_object_id = ? ORDER BY timestamp DESC LIMIT 1";
        	$sql_edit_heading_record = "UPDATE course_instance_audit SET target_type_id = ?, message = ?, serialized_object = ? WHERE id = ?"; 		
        	
        	$rs = $g_dbConn->query($sql_get_heading_record, array($target_id));
        	if ( Rd_Pdo_PearAdapter::isError($rs) ) {
        		trigger_error($result->getMessage(), E_USER_WARNING);
        	}
        	$row = $rs->fetch(PDO::FETCH_NUM);
        	if (Rd_Pdo_PearAdapter::isError($row)) { trigger_error($row->getMessage(), E_USER_ERROR); }
			$id = $row[0];
        	
        	$result = $g_dbConn->query($sql_edit_heading_record, array($target_type_id, $message, $serialized_target, $id));
        	if ( Rd_Pdo_PearAdapter::isError($result) ) {
            	trigger_error($result->getMessage(), E_USER_WARNING);
        	}
        }
        else{
	        $sql_insert_audit_record = "INSERT INTO course_instance_audit (course_instance_id, alias_id, user_id, action, target_object_id, target_type_id, message, serialized_object) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
	        
	        $result = $g_dbConn->query($sql_insert_audit_record, array($ci_id, $course_alias_id, $u->getUserID(), $action, $target_id, $target_type_id, $message, $serialized_target));
	        if ( Rd_Pdo_PearAdapter::isError($result) ) {
	            trigger_error($result->getMessage(), E_USER_WARNING);
	        }
        }
    }
    

    private static function getActionVerb($action) {
        switch($action) {
            case self::EVENT_ADD:
                return "Added ";
                break;
            case self::EVENT_REMOVE:
                return "Removed ";
                break;
            case self::EVENT_EDIT:
            	return "Edited ";
            	break;
            default:
                return $action . "ed";
        }
    }


    public function logStudentEvent($course_alias_id, $student, $action) {
    	$ci_id = $_REQUEST['ci'];
        return $this->logAction($ci_id, $course_alias_id, $student->getId(), self::TARGET_STUDENT, $student, self::getActionVerb($action) . $student->getName(), $action);
    }
    
    public function logInstructorEvent($course_alias_id, $instance_id, $instructorID, $action) {
    	$course = new course($course_alias_id);
    	$ci_id = isset($_REQUEST['ci']) ? $_REQUEST['ci'] : $instance_id;
    	$instructor = new user($instructorID);
        return $this->logAction($ci_id, $course_alias_id, $instructorID, self::TARGET_INSTRUCTOR, $instructor, self::getActionVerb($action) . $instructor->getName(), $action);
    }
    
    
    private function getAccessObjectByCourseInstance($course_instance_id, $user_id) {
        global $g_dbConn;
        $sql = "SELECT * from access where alias_id IN ( SELECT course_alias_id FROM course_aliases WHERE course_instance_id = ? AND user_id = ? )";
        $result = $g_dbConn->query($sql, intval($course_instance_id), intval($user_id) );
        $row = $result->fetch(PDO::FETCH_NUM);
        $result->free();
        return $row;
    }
    
    private function getAccessObjectByCourseAlias($course_alias_id, $user_id) {
        global $g_dbConn;
        $sql = "SELECT * from access where alias_id = ? and user_id = ?";
        $result = $g_dbConn->query($sql, intval($course_alias_id), intval($user_id) );
        $row = $result->fetch(PDO::FETCH_NUM);
        $result->free();
        return $row;
        
    }

    public function logProxyEvent($course_alias_id, $proxyID, $action) {
    	$ci_id = $_REQUEST['ci'];
    	$proxy = new user($proxyID);
        return $this->logAction($ci_id, $course_alias_id, $proxyID, self::TARGET_PROXY, $proxy, self::getActionVerb($action) . $proxy->getName(), $action);
    }
    
    public function logCourseInstanceEvent($instance_id, $action) {
    	$courseInstance = new courseInstance($instance_id);
    	return $this->logAction(0, 0, $instance_id, self::TARGET_COURSE_INSTANCE, $courseInstance, self::getActionVerb($action) . $courseInstance->getPrimaryCourse()->getName(), $action);
    }
    
    public function logCourseAliasEvent($alias_id, $action) {
    	$courseAlias = new course($alias_id);
    	return $this->logAction(0, 0, $alias_id, self::TARGET_COURSE_ALIAS, $courseAlias, self::getActionVerb($action) . "new course alias", $action);
    }

    /**
     *
     * @param int $target_course_id the course being added or removed as an alias to the instance.
     * @param couser $course_alias the
     * @param string $action the name of the action (e.g. "add", "remove")
     */
    public function logCrossListingAddEvent($target_course_instance_id, $course_alias_id, $action) {
        global $u;
    	$course = new course($course_alias_id);
        return $this->logAction($target_course_instance_id, $course_alias_id, $course_alias_id, self::TARGET_CROSSLISTING, $course, self::getActionVerb($action) . $course->getName(), $action);
    }
    
	public function logCrossListingRemoveEvent($course_alias_id, $action) {
        global $u;
    	$target_course_instance_id = $_REQUEST['ci'];
    	$course = new course($course_alias_id);
        return $this->logAction($target_course_instance_id, $course_alias_id, $course_alias_id, self::TARGET_CROSSLISTING, $course, self::getActionVerb($action) . $course->getName(), $action);
    }

    /**
     * Logs the addition or removal of a reserved item from
     * a particular course instance.
     * @param int $course_instance_id
     * @param <type> $reserve the reserved item being added/removed.
     * @param string $action e.g. "add", "remove"
     */

    public function logReserveEvent($course_instance_id, $itemID, $action) {
		global $u;
		//new item, not new reserve
    	$item = new reserveItem($itemID);
    	$ci = new courseInstance($course_instance_id);
    	return $this->logAction($course_instance_id, $ci->getPrimaryCourseAliasID(), $itemID, self::TARGET_RESERVE, $item, self::getActionVerb($action) . $item->getTitle(), $action);
    }

       /**
     * Logs the addition or removal of a heading (specific type of reserve) from
     * a particular course instance.
     * @param int $course_instance_id
     * @param <type> $heading
     * @param string $action e.g. "add", "remove"
     */
    public function logHeadingEvent($course_instance_id, $headingID, $action)
    {
    	$ci = new courseInstance($course_instance_id);
    	$heading = new item($headingID);
        return $this->logAction($course_instance_id, $ci->getPrimaryCourseAliasID(), $headingID, self::TARGET_HEADING, $heading, self::getActionVerb($action) . $heading->getTitle(), $action);
    }

    /**
     * Gets the audit events associated with a given course instance and, optionally,
     * with a specific alias.
     * @global PEARDB $g_dbConn
     * @param int $course_instance_id
     * @param int $course_alias_id (defaults to null)
     * @return array of rows matching the query.
     */
    public function getEventsForCourse($course_instance_id, $course_alias_id=null)
    {
        global $g_dbConn;
        $params = array(intval($course_instance_id));
        $sql = "SELECT cia.id, cia.action, cia.target_object_id, ttm.display_name, cia.timestamp, cia.message, u.username, cia.serialized_object, pl.label "
            . " FROM course_instance_audit cia JOIN target_table_mapping ttm on cia.target_type_id = ttm.id "
            . " JOIN users u on cia.user_id = u.user_id "
            . " JOIN permissions_levels pl on u.dflt_permission_level = pl.permission_id "
            . " WHERE cia.course_instance_id = ? GROUP BY cia.action, cia.target_object_id, ttm.display_name, cia.timestamp, cia.message ORDER BY cia.timestamp DESC";
            
        $rows = array();
        $result = $g_dbConn->query($sql, $params);
        if ( Rd_Pdo_PearAdapter::isError($result) ) {
            trigger_error($result->getMessage(), E_USER_WARNING);
        }
        while ( ( $row = $result->fetch(PDO::FETCH_NUM) ) ) {
            $rows[] = $row;
        }
        return $rows;
    }
}
?>
