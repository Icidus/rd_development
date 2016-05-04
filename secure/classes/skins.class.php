<?php
/*******************************************************************************
skins.class.php
methods for manipulating skin/stylesheet configurations

Created by Chris Roddy (croddy@emory.edu)
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

class skins {

    function skins() {}

    /**
    * @return string
    * @param string $skinName
    * @desc retrieve stylesheet filename from database
    */
    function getSkin($skinName) {
        global $g_dbConn;

        $skin_sql =  "SELECT skin_stylesheet AS `css` FROM skins WHERE skin_name='{$skinName}' LIMIT 1";
        $default_sql =  "SELECT skin_stylesheet AS `css` FROM skins WHERE default_selected='yes' LIMIT 1";

        $rs = $g_dbConn->query($skin_sql);
        if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }

        $row = $rs->fetch();
        
        if (count($row) != 1) {
            $rs = $g_dbConn->query($default_sql);
            $row = $rs->fetch();
        }

        if (count($row) != 1) { 
            trigger_error("No usable skin configuration: ", E_USER_ERROR);
        }

        return $row['css']; // relative pathname of CSS stylesheet

    }
}
