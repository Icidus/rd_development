<?php
/*******************************************************************************
news.class.php
news object handles term table

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

class news
{
	
	static function getByID($id)
	{
		global $g_dbConn;

		$sql = 	"SELECT news_id, news_text, font_class, permission_level, begin_time, end_time, sort_order FROM news WHERE news_id = ?";
	
		$rs = $g_dbConn->query($sql, $id);
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }
		$news = null;
		while ($row = $rs->fetch(PDO::FETCH_NUM))
		{
			$n['id'] 				= $row[0];
			$n['text'] 				= stripslashes($row[1]);
			$n['class']				= $row[2];
			$n['permission_level']	= $row[3];
			$n['begin_time']		= $row[4];
			$n['end_time']			= $row[5];
			$n['sort_order']		= $row[6];
			
		}
		return $n;		
	}
		
	
	static function createNew($permission, $font_class, $begin, $end, $text, $sort)
	{
		global $g_dbConn;

		$sql = 	"INSERT INTO news (news_text, font_class, permission_level, begin_time, end_time, sort_order)
						 VALUES (?,?,?,?,?,?)";
	
		$rs = $g_dbConn->query($sql, array($text, $font_class, $permission, $begin, $end, $sort));
		if (Rd_Pdo_PearAdapter::isError($rs)) 
		{ 
			trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); 
		}
		
		return true;		
	}

	static function update($font_class, $begin, $end, $text, $sort, $id)
	{
		global $g_dbConn;

		$sql = 	"UPDATE news SET news_text=?, font_class=?, begin_time=?, end_time=?, sort_order=?
						 WHERE news_id = ?";
	
		$rs = $g_dbConn->query($sql, array($text, $font_class, $begin, $end, $sort, $id));
		if (Rd_Pdo_PearAdapter::isError($rs)) 
		{ 
			trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); 
		}
		
		return true;					
	}
	
	static function getAll()
	{
		global $g_dbConn;

		$sql = 	"SELECT news_id, news_text, font_class, permission_level, begin_time, end_time, sort_order FROM news ORDER BY news_id DESC";
	
		$rs = $g_dbConn->query($sql);
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }
		$news = null;
		while ($row = $rs->fetch(PDO::FETCH_NUM))
		{
			$n['id'] 				= $row[0];
			$n['text'] 				= stripslashes($row[1]);
			$n['class']				= $row[2];
			$n['permission_level']	= $row[3];
			$n['begin_time']		= $row[4];
			$n['end_time']			= $row[5];
			$n['sort_order']		= $row[6];
			
			$news[] = $n;
		}
		return $news;		
	}
	
	static function getNews($permission_level = 0)
	{
		global $g_dbConn;

		$now = date("Y-m-d H:i:s",strtotime("now"));
			
		$sql = 	"SELECT news_id, news_text, font_class, begin_time, end_time, sort_order FROM news 
						 WHERE (permission_level = '$permission_level' OR permission_level is null) 
						 	AND ((begin_time <= '$now' OR begin_time IS NULL) AND ('$now' <= end_time OR end_time IS NULL))
						 ORDER BY sort_order
				";
	
		$rs = $g_dbConn->query($sql);
		if (Rd_Pdo_PearAdapter::isError($rs)) { trigger_error(Rd_Pdo_PearAdapter::getErrorMessage($rs), E_USER_ERROR); }

		$news = null;
		while ($row = $rs->fetch(PDO::FETCH_NUM))
		{
			$n['id'] 	= $row[0];
			$n['text'] 	= $row[1];
			$n['class']	= $row[2];
			
			$news[] = $n;
		}
		return $news;
	}


}
