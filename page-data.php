<?php
/*
  FILE: page-data.php
  PURPOSE: standard recordset class for mediating with page classes
  HISTORY:
    2015-04-19 created from classes in vbz-data.php
*/

/* uncomment if we actually need to add some more functionality
class fcDataTable extends clsDataTable_Menu {

    // ++ SETUP ++ //

    public function __construct($iDB) {
	parent::__construct($iDB);
	  $this->KeyName('ID');	// default key field; can be overridden
    }

}
*/
class fcDataRecs extends clsDataRecord_Menu {

    // ++ SETUP ++ //

    protected function InitVars() {
	$this->Value_IdentityKeys(array('page','id'));
    }

    // -- SETUP -- //

}
