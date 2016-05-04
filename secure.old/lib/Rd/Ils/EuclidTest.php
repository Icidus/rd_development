<?php
/*******************************************************************************
Rd_Ils_Euclid
Implementation of Emory University's Localized ils EUCLID_TEST

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

*******************************************************************************
This class extends RD_Ils_Euclid

Implementing Emory University's localized ils test instance
*******************************************************************************/
require_once(APPLICATION_PATH . '/lib/Rd/Ils/Euclid.php');
class Rd_Ils_EuclidTest extends Rd_Ils_Euclid 
{
	protected $create_reserve_script = "http://libtest.cc.emory.edu:30322/uhtbin/create_reserve";
	//protected $holdings_script = "http://libtest.cc.emory.edu:30322/uhtbin/holding_request";

	protected function setILSName()
	{
		$this->_ilsName = 'EUCLIDTest';
	}
		
	public function displayReserveForm()
	{
		return $this->_view->render("_EUCLID_TEST_reserve_form.phtml");
	}
	
		
}