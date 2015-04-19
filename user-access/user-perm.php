<?php
/*
  PURPOSE: user access control classes: available security permissions
  HISTORY:
    2013-12-29 started
*/
class clsUserPerms extends clsTable {
    private $arData;

    public function __construct($iDB) {
	parent::__construct($iDB);
	  $this->Name(KS_TABLE_USER_PERMISSION);
	  $this->KeyName('ID');
	  $this->ClassSng('clsUserPerm');
	$this->arData = NULL;
    }

    // ++ BUSINESS LOGIC ++ //

    public function AsArray() {
	if (is_null($this->arData)) {
	    $rs = $this->GetData();
	    $this->arData = $rs->AsArray();
	}
	return $this->arData;
    }

    // -- BUSINESS LOGIC -- //
}
class clsUserPerm extends clsVbzRecs {

    // ++ FIELD ACCESS ++ //

    public function Name() {
	return $this->Value('Name');
    }
    public function Descr() {
	return $this->Value('Descr');
    }

    // -- FIELD ACCESS -- //
    // ++ DATA RECORDS ACCESS ++ //

    /*----
      RETURNS: The current recordset as an associative array, indexed by ID
    */
    public function AsArray() {
	$ar = array();
	while ($this->NextRow()) {
	    $id = $this->KeyValue();
	    $ar[$id] = $this->Values();
	}
	return $ar;
    }

    // -- DATA RECORDS ACCESS -- //
}